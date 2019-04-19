<?php
namespace Family\Log\Adapter;

use Family\Log\Base;
use Family\Log\Level;

class File extends Base
{
    private $_config;
    const SEPARATOR = ' | ';
    public function __construct($config)
    {
        if (!empty($config)) {
            $this->_config = $config;
        }
    }
    /**
     * @param $level
     * @param $message
     * @param array $context
     * @return bool
     * @throws \Exception
     * @desc {type} | {timeStamp} |{dateTime} | {$message}
     */
    public function log($level, $message, array $context = null, $original = 0)
    {
        $logLevel = $this->_config['level'] ?? Level::ALL;
        if (isset(Level::$levels[$level]) && Level::$levels[$level] >= $logLevel) {
            return true;
        }
        if ($original) {
            $str = $message;
        } else {
            if (empty($context)) {
                foreach ($context as $key => $val) {
                    $message = str_replace('{' . $key . '}', $val, $message);
                }
            }
            $str =  date('Y-m-d H:i:s') . self::SEPARATOR . $message;
        }
        $baseDir = $this->_config['default_basepath'] ?: '/tmp';
        $dir =  $baseDir . DS . date('Ymd');
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $logFile = $dir . DS . $level . '.log';
        \file_put_contents($logFile, $str . "\n", FILE_APPEND | LOCK_EX);

        return true;
    }
}
