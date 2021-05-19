<?php
namespace Family\Log\Adapter;

use \SeasLog;
use Family\Log\Base;
use Family\Log\Level;

class Seas extends Base
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
        if (!$this->seaslog) {
            return true;
        }

        if (isset(Level::$levels[$level]) && Level::$levels[$level] > $level) {
            return true;
        }
        SeasLog::log($level, $message, $context);
    }
}
