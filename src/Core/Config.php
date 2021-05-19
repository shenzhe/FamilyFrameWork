<?php
namespace Family\Core;

use Family\Exceptions\ConfigException;
use Family\Family;
use Family\Helper\Dir;

class Config
{

    /**
     * @var 配置map
     */
    public static $configMap = [];

    public static $configDir = 'dev';
    public static $defaultConfigFile = 'server.php';


    /**
     * @param string|null $dir
     * @desc 读取配置，默认是application/config/server.php
     *          此配置不可热加载
     * @throws ConfigException
     */
    public static function load(string $dir = null)
    {
        if (!empty($dir)) {
            self::$configDir = $dir;
        }

        $configDir = Family::$applicationPath . DS . 'config' .
            DS . self::$configDir;

        if (!is_dir($configDir)) {
            throw new ConfigException(ConfigException::CONFIG_DIR_ERROR, ['dir' => $configDir]);
        }
        $filename = $configDir . DS . self::$defaultConfigFile;
        self::$configMap = include "{$filename}";
    }

    /**
     * @desc 读取配置，默认是application/config 下除default所有的php文件
     *          非default配置，可以热加载
     */
    public static function loadLazy()
    {
        self::loadPath('config' .
            DS . self::$configDir);
        self::loadPath('__public__config__');
    }

    public static function loadPath($dir = 'config')
    {
        $configPath = Family::$applicationPath . DS . $dir;
        if (!is_dir($configPath)) {
            return;
        }
        $files = Dir::tree($configPath, "/.php$/");
        if (!empty($files)) {
            foreach ($files as $dir => $filelist) {
                foreach ($filelist as $file) {
                    if (self::$defaultConfigFile == $file) {
                        continue;
                    }
                    $filename = $dir . DS . $file;
                    $_conf = include "{$filename}";
                    if (is_array($_conf)) {
                        self::$configMap += $_conf;
                    }
                }
            }
        }
    }

    /**
     * @param $key
     * @param $def
     * @desc 读取配置
     * @return string|null|array|object
     *
     */
    public static function get($key, $def = null)
    {
        if (isset(self::$configMap[$key])) {
            return self::$configMap[$key];
        }

        return $def;
    }

    /**
     * @param $key
     * @param $sub
     * @param $def
     * @return mixed
     * @desc 读取子配置
     */
    public static function getField($key, $sub, $def = null)
    {
        if (isset(self::$configMap[$key][$sub])) {
            return self::$configMap[$key][$sub];
        }

        return $def;
    }
}
