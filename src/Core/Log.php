<?php
//file: framework/family/Core/Log.php
namespace Family\Core;


use Family\Family;

use SeasLog;

class Log
{
    private static $seaslog = false;
    private static $level = 8;

    //设置日志目录
    public static function init()
    {
        if (class_exists('SeasLog')) {
            self::$seaslog = true;
            SeasLog::setBasePath(Config::getField('log', 'default_basepath'));
            self::$level = Config::getField('log', 'log_level', 8);
        }
    }

    //代理seaglog的静态方法，如 SeasLog::debug
    public static function __callStatic($name, $arguments)
    {
        if (self::$seaslog) {
            forward_static_call_array(['SeasLog', $name], $arguments);
        }
    }

    /**
     * 记录debug日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function debug($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_DEBUG
        if (self::$seaslog && self::$level > 7) {
            SeasLog::debug($message, $context, $module);
        }
    }

    /**
     * 记录info日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function info($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_INFO
        if (self::$seaslog && self::$level > 6) {
            SeasLog::info($message, $context, $module);
        }
    }

    /**
     * 记录notice日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function notice($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_NOTICE
        if (self::$seaslog && self::$level > 5) {
            SeasLog::notice($message, $context, $module);
        }
    }

    /**
     * 记录warning日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function warning($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_WARNING
        if (self::$seaslog && self::$level > 4) {
            SeasLog::warning($message, $context, $module);
        }
    }

    /**
     * 记录error日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function error($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_ERROR
        if (self::$seaslog && self::$level > 3) {
            SeasLog::error($message, $context, $module);
        }
    }

    /**
     * 记录critical日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function critical($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_CRITICAL
        if (self::$seaslog && self::$level > 2) {
            SeasLog::critical($message, $context, $module);
        }
    }

    /**
     * 记录alert日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function alert($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_ALERT
        if (self::$seaslog && self::$level > 1) {
            SeasLog::alert($message, $context, $module);
        }
    }

    /**
     * 记录emergency日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function emergency($message, array $context = array(), $module = '')
    {
        #$level = SEASLOG_EMERGENCY
        if (self::$seaslog && self::$level > 0) {
            SeasLog::emergency($message, $context, $module);
        }
    }

    /**
     * 通用日志方法
     *
     * @param              $level
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public static function log($level, $message, array $context = array(), $module = '')
    {
        if (self::$seaslog) {
            SeasLog::log($level, $message, $context, $module);
        }
    }

    /**
     * @param \Throwable $e
     * @desc 输出异常日志
     */
    public static function exception(\Throwable $e)
    {
        if (self::$seaslog && self::$level > 0) {
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

    public static function access(\swoole_http_request $request)
    {
        if (self::$seaslog && self::$level > 6) {
            Log::info(
                json_encode([
                        "@timestamp" => time(),
                        "app" => Config::get('app_name'), // 应用名称
                        "method" => $request->server['request_method'], // 请求方法
                        "request_uri" => $request->server['request_uri'], // 请求地址（带参数）
                        "request_body" => $request->rawContent() ?? '', // 请求体
                        "X-SSPP-UID" => $request->header['X-SSPP-UID'] ?? '', // 伪UID，直接从http header获取
                        "X-B3-TraceId" => $request->header['X-SSPP-UID'] ?? '', // 集成sleuth
                        "X-B3-SpanId" => $request->header['X-SSPP-UID'] ?? '', // 集成sleuth
                        "X-B3-ParentSpanId" => $request->header['X-SSPP-UID'] ?? '', // 集成sleuth
                        "reqIp" => $request->header['X-Forwarded-For'] ?? '', // 用户ip，从http header[X-Forwarded-For]获取
                        "status" => 200, // 本次请求返回的状态码
                        "request_time" => (microtime(true) - $request->server['request_time_float'])// 本次请求响应时间，单位ms
                    ]
                ));
        }
    }
}