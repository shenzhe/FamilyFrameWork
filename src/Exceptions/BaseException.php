<?php

namespace Family\Exceptions;

use Family\Core\Config;
use Family\Core\Log;
use Family\Helper\Formater;

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
        if (
            __CLASS__ == $class &&
            method_exists($exception, 'exceptionHandler')
        ) {
            return call_user_func([$exception, 'exceptionHandler'], $exception);
        } else {
            $errInfo = Formater::exception($exception);
            Log::emergency(\var_export($errInfo, true));
            return self::display($errInfo);
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
        $errInfo = Formater::fatal($error);
        Log::emergency(\var_export($errInfo, true));
        return self::display($errInfo);
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
        if (E_NOTICE === $errno) {
            return;
        }
        $error = [
            'code' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'errcontext' => $errcontext,
            'type' => 'err:' . $errno
        ];
        $errInfo = Formater::fatal($error, true, 'error_handler');
        Log::error(\var_export($errInfo, true));
        return;
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
