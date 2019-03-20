<?php

namespace Family\Exceptions;


use Family\Core\Log;
use Family\Core\Singleton;

/**
 * 异常处理
 *
 * @package Family\Exceptions
 *
 */
class RouterException extends BaseException
{

    const DEFAULT_ERROR = [
        'code' => -100,
        'msg' => '系统错误',
    ];

    const METHOD_NOT_ALLOWED = [
        'code' => -101,
        'msg' => '错误的请求方法',
    ];

    public function __construct(array $error = null, array $context = [])
    {
        if (empty($error)) {
            $error = self::DEFAULT_ERROR;
        }
        $msg = self::parseMsg($error['msg'], $context);
        parent::__construct($msg, $error['code']);
    }
}
