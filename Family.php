<?php
//file: framework/Family/family.php
namespace Family;

use Family\Core\Log;
use Family\Server\Server;


class Family
{

    /**
     * @var 根目录
     */
    public static $rootPath;
    /**
     * @var 框架目录
     */
    public static $frameworkPath;
    /**
     * @var 程序目录
     */
    public static $applicationPath;

    final public static function run()
    {
        try {
            if (!defined('DS')) {
                define('DS', DIRECTORY_SEPARATOR);
            }
            self::$rootPath = dirname(dirname(__DIR__));
            self::$frameworkPath = self::$rootPath . DS . 'framework';
            self::$applicationPath = self::$rootPath . DS . 'application';

            //先注册自动加载
            \spl_autoload_register(__CLASS__ . '::autoLoader');
            \date_default_timezone_set('Asia/Shanghai');
            //服务启动
            (new Server())->start();
        } catch (\Exception $e) {
            Log::emergency($e->getMessage());
            echo $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
        } catch (\Throwable $throwable) {
            Log::emergency($throwable->getMessage());
            echo $throwable->getCode() . ':' . $throwable->getMessage() . PHP_EOL;
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
