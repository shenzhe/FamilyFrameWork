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
class MysqlException extends BaseException
{

    const DEFAULT_ERROR = [
        'code' => -300,
        'msg' => '系统错误',
    ];

    const UPDATE_NO_WHERE = [
        'code' => -301,
        'msg' => 'update必需有where条件',
    ];

    const DELETE_NO_WHERE = [
        'code' => -302,
        'msg' => 'delete必需有where条件',
    ];

    const CONNECT_ERROR = [
        'code' => -303,
        'msg' => '连接失败: {msg}: {code}'
    ];

    const QUERY_ERROR = [
        'code' => -304,
        'msg' => '{msg}: {code}'
    ];

    const CONFIG_EMPTY = [
        'code' => -305,
        'msg' => 'mysql 配置为空'
    ];

    const POOL_FULL = [
        'code' => -306,
        'msg' => 'mysql连接池已满'
    ];

    const POOL_EMPTY = [
        'code' => -307,
        'msg' => 'mysql连接池已空'
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
