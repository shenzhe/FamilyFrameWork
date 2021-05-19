<?php
namespace Family\Log\Adapter;

use Family\Log\Base;
use Family\Log\Level;

class File extends Base
{
    private $_config;
    const SEPARATOR = ' | ';
    private $fp = null;
    private $fn = null;
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
        if (empty($this->_config['log_level'])) {
            $logLevel = Level::ALL;
        } else {
            $logLevel = $this->_config['log_level'];
        }

        if (isset(Level::$levels[$level]) && Level::$levels[$level] > $logLevel) {
            return true;
        }

        if (!is_string($message)) {
            $message = var_export($message, true);
        } else {
            if ('Array' == $message) {
                $message = 'ARRAY:' . var_export(debug_backtrace(), true);
            } else {
                if (!empty($context)) {
                    foreach ($context as $key => $val) {
                        $message = str_replace($key, $val, $message);
                    }
                }
            }
        }

        if ($original) {
            $str = $message;
        } else {
            $str =  date('Y-m-d H:i:s') . self::SEPARATOR . $message;
        }
        $baseDir = $this->_config['default_basepath'] ?: '/tmp';
        if (!empty($this->_config['log_file_func']) && \is_callable($this->_config['log_file_func'])) {
            $logFile = $this->_config['log_file_func']($level);
        } else {
            $logFile = $baseDir . DS . date('Ymd') . '.' . $level . '.log';
        }

        if (!empty($this->fn[$level]) && $logFile !== $this->fn[$level]) {
            fclose($this->fp[$level]);
            $this->fp[$level] = null;
            $this->fn[$level] = null;
        }

        if (empty($this->fp[$level]) || !is_resource($this->fp[$level])) {
            $this->fp[$level] = fopen($logFile, 'a');
            $this->fn[$level] = $logFile;
        }

        fwrite($this->fp[$level], $str . "\n");

        return true;
    }
}
