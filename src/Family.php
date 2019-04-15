<?php
//file: framework/Family/family.php
namespace Family;

use Family\Core\Config;
use Family\Core\Log;
use Family\Server\Server;


class Family
{

    /**
     * @var 根目录
     */
    public static $rootPath;
    /**
     * @var 程序目录
     */
    public static $applicationPath;

    /**
     * @var \swoole_server
     */
    public static $swooleServer;

    public static function setRootPath($rootPath)
    {
        self::$rootPath = $rootPath;
    }

    final public static function run()
    {
        try {
            if (!defined('DS')) {
                define('DS', DIRECTORY_SEPARATOR);
            }
            if (empty(self::$rootPath)) {
                self::$rootPath = dirname(dirname(dirname(dirname(__DIR__))));
            }
            self::$applicationPath = self::$rootPath . DS . 'application';

            //先注册自动加载
            \spl_autoload_register(__CLASS__ . '::autoLoader');
            //加载配置
            $configDir = '';
            $options = getopt("c::");
            if (!empty($options['c'])) {
                $configDir = $options['c'];
            }
            Config::load($configDir);
            $timeZone = Config::get('time_zone', 'Asia/Shanghai');
            \date_default_timezone_set($timeZone);
            Log::init();
            //服务启动
            (new Server())->start();
        } catch (\Exception $e) {
            Log::exception($e);
//            echo $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
            print_r($e);
        } catch (\Throwable $throwable) {
            Log::exception($throwable);
//            echo $throwable->getCode() . ':' . $throwable->getMessage() . PHP_EOL;
            print_r($throwable);
        }
    }


    /**
     * @param $class
     * @desc 自动加载类
     */
    final public static function autoLoader($class)
    {

        //把类转为目录，eg \a\b\c => /a/b/c.php
        $classPath = \str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        //约定框架类都在framework目录下, 业务类都在application下
        $findPath = [
            self::$rootPath . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR,
        ];


        //遍历目录，查找文件
        foreach ($findPath as $path) {
            //如果找到文件，则require进来
            $realPath = $path . $classPath;
            if (is_file($realPath)) {
                require "{$realPath}";
                return;
            }
        }
    }
}
