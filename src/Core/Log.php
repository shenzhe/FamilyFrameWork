<?php
namespace Family\Core;

use Family\Log\Factory;

class Log
{
    private static $logger;

    //设置日志目录
    public static function init()
    {
        self::$logger = (new Factory())->logger;
    }

    public static function getLogger()
    {
        if (empty(self::$logger)) {
            self::init();
        }

        return self::$logger;
    }


    /**
     * 记录debug日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function debug($message, array $context = array())
    {
        self::$logger->debug($message, $context);
    }

    /**
     * 记录info日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function info($message, array $context = array())
    {
        self::$logger->info($message, $context);
    }

    /**
     * 记录notice日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function notice($message, array $context = array())
    {

        self::$logger->notice($message, $context);
    }

    /**
     * 记录warning日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function warning($message, array $context = array())
    {

        self::$logger->warning($message, $context);
    }

    /**
     * 记录error日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function error($message, array $context = array())
    {

        self::$logger->error($message, $context);
    }

    /**
     * 记录critical日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function critical($message, array $context = array())
    {

        self::$logger->critical($message, $context);
    }

    /**
     * 记录alert日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function alert($message, array $context = array())
    {

        self::$logger->alert($message, $context);
    }

    /**
     * 记录emergency日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function emergency($message, array $context = array())
    {

        self::$logger->emergency($message, $context);
    }

    /**
     * 通用日志方法
     *
     * @param              $level
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function log($level, $message, array $context = array())
    {
        self::$logger->log($level, $message, $context);
    }

    /**
     * @param \Throwable $e
     * @desc 输出异常日志
     */
    public static function exception(\Throwable $e)
    {

        $array = [
            '{file}' => $e->getFile(),
            '{line}' => $e->getLine(),
            '{code}' => $e->getCode(),
            '{message}' => $e->getMessage(),
            '{trace}' => $e->getTraceAsString(),
        ];
        $message = implode(' | ', array_keys($array));
        self::emergency($message, $array);
    }
}
