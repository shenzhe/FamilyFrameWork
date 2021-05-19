<?php

namespace Family\Helper;

use Family\Core\Config;

class Formater
{
    public static function fatal($error, $trace = true, $name = 'fatal')
    {
        $exceptionHash = array(
            'className' => $name,
            'message' => '[type:' . $error['type'] ?? '' . '] ' . $error['message'],
            'code' => Config::getField('project', 'default_exception_code', -1),
            'file' => $error['file'],
            'line' => $error['line'],
            'trace' => array(),
        );
        if ($trace) {
            $traceItems = debug_backtrace();
            foreach ($traceItems as $traceItem) {
                $traceHash = array(
                    'file' => isset($traceItem['file']) ? $traceItem['file'] : 'null',
                    'line' => isset($traceItem['line']) ? $traceItem['line'] : 'null',
                    'function' => isset($traceItem['function']) ? $traceItem['function'] : 'null',
                    'args' => array(),
                );
                if (!empty($traceItem['class'])) {
                    $traceHash['class'] = $traceItem['class'];
                }
                if (!empty($traceItem['type'])) {
                    $traceHash['type'] = $traceItem['type'];
                }
                if (!empty($traceItem['args'])) {
                    foreach ($traceItem['args'] as $argsItem) {
                        $traceHash['args'][] = \preg_replace('/[^(\x20-\x7F)]*/', '', \var_export($argsItem, true));
                    }
                }
                $exceptionHash['trace'][] = $traceHash;
            }
        }
        return $exceptionHash;
    }
    /**
     * @param $exception \Exception | \Error
     * @param bool $trace
     * @param bool $args
     * @return array
     * @throws \Exception
     */
    public static function exception(\Throwable $exception, $trace = true, $args = true)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();
        if (empty($code)) {
            $code = Config::getField('project', 'default_exception_code', -1);
        } elseif (!is_numeric($code)) {
            $message .= "#code:[{$code}]";
            $code = Config::getField('project', 'default_exception_code', -1);
        }
        $exceptionHash = array(
            'className' => get_class($exception),
            'message' => $message,
            'code' => $code,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => array()
        );
        if ($trace) {
            $traceItems = $exception->getTrace();
            foreach ($traceItems as $traceItem) {
                $traceHash = array(
                    'file' => isset($traceItem['file']) ? $traceItem['file'] : 'null',
                    'line' => isset($traceItem['line']) ? $traceItem['line'] : 'null',
                    'function' => isset($traceItem['function']) ? $traceItem['function'] : 'null',
                    'args' => array(),
                );
                if (!empty($traceItem['class'])) {
                    $traceHash['class'] = $traceItem['class'];
                }
                if (!empty($traceItem['type'])) {
                    $traceHash['type'] = $traceItem['type'];
                }
                if ($args) {
                    if (!empty($traceItem['args'])) {
                        foreach ($traceItem['args'] as $argsItem) {
                            $traceHash['args'][] = \preg_replace('/[^(\x20-\x7F)]*/', '', \var_export($argsItem, true));
                        }
                    }
                }
                $exceptionHash['trace'][] = $traceHash;
            }
        }
        return $exceptionHash;
    }
}
