<?php
namespace Family\Log\Adapter\Seas;


use \SeasLog;

class Seas
{
    private $seaslog = false;
    private $level = 8;

    //设置日志目录
    public function __construct($config)
    {
        if (class_exists('SeasLog')) {
            $this->seaslog = true;
            SeasLog::setBasePath($config['default_basepath']);
            $this->level = $config['log_level'];
        }
    }

    //代理seaglog的静态方法，如 SeasLog::debug
    public function __callStatic($name, $arguments)
    {
        if ($this->seaslog) {
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
    public function debug($message, array $context = array())
    {
        #$level = SEASLOG_DEBUG
        if ($this->seaslog && $this->level > 7) {
            SeasLog::debug($message, $context);
        }
    }

    /**
     * 记录info日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public function info($message, array $context = array())
    {
        #$level = SEASLOG_INFO
        if ($this->seaslog && $this->level > 6) {
            SeasLog::info($message, $context);
        }
    }

    /**
     * 记录notice日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public function notice($message, array $context = array())
    {
        #$level = SEASLOG_NOTICE
        if ($this->seaslog && $this->level > 5) {
            SeasLog::notice($message, $context);
        }
    }

    /**
     * 记录warning日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public function warning($message, array $context = array())
    {
        #$level = SEASLOG_WARNING
        if ($this->seaslog && $this->level > 4) {
            SeasLog::warning($message, $context);
        }
    }

    /**
     * 记录error日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public function error($message, array $context = array())
    {
        #$level = SEASLOG_ERROR
        if ($this->seaslog && $this->level > 3) {
            SeasLog::error($message, $context);
        }
    }

    /**
     * 记录critical日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public function critical($message, array $context = array())
    {
        #$level = SEASLOG_CRITICAL
        if ($this->seaslog && $this->level > 2) {
            SeasLog::critical($message, $context);
        }
    }

    /**
     * 记录alert日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public function alert($message, array $context = array())
    {
        #$level = SEASLOG_ALERT
        if ($this->seaslog && $this->level > 1) {
            SeasLog::alert($message, $context);
        }
    }

    /**
     * 记录emergency日志
     *
     * @param string|array $message
     * @param array $context
     * @param string $module
     */
    public function emergency($message, array $context = array())
    {
        #$level = SEASLOG_EMERGENCY
        if ($this->seaslog && $this->level > 0) {
            SeasLog::emergency($message, $context);
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
    public function log($level, $message, array $context = array(), $original = 0)
    {
        if ($this->seaslog) {
            SeasLog::log($level, $message, $context);
        }
    }
}
