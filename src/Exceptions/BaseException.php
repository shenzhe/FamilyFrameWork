<?php

namespace Family\Exceptions;


use common\Display;
use Family\Core\Config;
use Family\Core\Log;

/**
 * 异常处理
 *
 * @package exceptions
 *
 */
class BaseException extends \Exception
{
    public function __construct($message, $code = -1)
    {
        parent::__construct($message, $code);
    }

    /**
     * @param $exception
     * @return mixed
     * @throws \Exception
     * @desc 异常处理 handler
     */
    public static function exceptionHandler(\Throwable $exception)
    {
        $class = get_class($exception);
        if (__CLASS__ == $class &&
            method_exists($exception, 'exceptionHandler')) {
            return call_user_func([$exception, 'exceptionHandler'], $exception);
        } else {
            Log::exception($exception);
            return self::display(array(
                'className' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ));
        }
    }

    /**
     * @return array
     * @throws \Exception
     * @desc fatal error处理
     */
    public static function fatalHandler()
    {
        $error = \error_get_last();
        if (empty($error)) {
            return array(
                'className' => '',
                'message' => '',
                'code' => 0,
                'file' => '',
                'line' => '',
                'trace' => array(),
            );
        }


        Log::alert([\var_export($error, true)]);

        if (!in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            return array(
                'className' => 'fatal',
                'message' => '[type:' . $error['type'] . '] ' . $error['message'],
                'code' => -1,
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => array(),
            );
        }

        return self::display(array(
            'className' => 'fatal',
            'message' => '[type:' . $error['type'] . '] ' . $error['message'],
            'code' => -1,
            'file' => $error['file'],
            'line' => $error['line'],
            'trace' => array(),
        ));
    }


    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @param $errcontext
     * @return array
     * @desc  一般错误处理
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $error = [
            'code' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'errcontext' => $errcontext,
        ];
        Log::error([\var_export($error, true)]);
        return self::display($error);
    }

    public static function display($error)
    {
        $disPlay = Config::get('display_func');
        if (!empty($disPlay)) {
            return call_user_func($disPlay, [
                'error' => $error['code'],
                'message' => $error['message']
            ]);
        }
        return json_encode([
            'error' => $error['code'],
            'message' => $error['message']
        ]);
    }

    /**
     * @param $msg
     * @param array $context
     * @return mixed
     * @desc $msg = "i am {key}", $context = ['key'=>'text'], output: i am text
     */
    public static function parseMsg($msg, array $context)
    {
        if (!empty($context)) {
            foreach ($context as $k => $v) {
                $msg = str_replace('{' . $k . '}', $v, $msg);
            }
        }

        return $msg;
    }

}
