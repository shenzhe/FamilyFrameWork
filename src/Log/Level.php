<?php
namespace Family\Log;

class Level
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    public static $levels = array(
        self::EMERGENCY => 1,
        self::ALERT => 2,
        self::CRITICAL => 3,
        self::ERROR => 4,
        self::WARNING => 5,
        self::NOTICE => 6,
        self::INFO => 7,
        self::DEBUG => 8,
    );
    const ALL = 8;
}
