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
        $logLevel = $this->_config['level'] ?? Level::ALL;
        if (isset(Level::$levels[$level]) && Level::$levels[$level] > $logLevel) {
            return true;
        }

        if (!empty($context)) {
            foreach ($context as $key => $val) {
                $message = str_replace($key, $val, $message);
            }
        }

        if ($original) {
            $str = $message;
        } else {
            $str =  date('Y-m-d H:i:s') . self::SEPARATOR . $message;
        }
        $baseDir = $this->_config['default_basepath'] ?: '/tmp';
        $logFile = $baseDir . DS . date('Ymd') . '.' . $level . '.log';

        if (!empty($this->fn) && $logFile !== $this->fn) {
            fclose($this->fp);
            $this->fp = null;
            $this->fn = null;
        }

        if (empty($this->fp) || !is_resource($this->fp)) {
            $this->fp = fopen($logFile, 'a');
            $this->fn = $logFile;
        }

        fwrite($this->fp, $str . "\n");

        return true;
    }
}
