<?php
namespace Family\Exceptions;

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
        'code' => -305,
        'msg' => 'update必需有where条件',
    ];

    const DELETE_NO_WHERE = [
        'code' => -306,
        'msg' => 'delete必需有where条件',
    ];

    const CONNECT_ERROR = [
        'code' => -301,
        'msg' => '连接失败: {msg}: {code}'
    ];

    const QUERY_ERROR = [
        'code' => -304,
        'msg' => '{msg}: {code}'
    ];

    const CONFIG_EMPTY = [
        'code' => -308,
        'msg' => 'mysql 配置为空'
    ];

    const POOL_FULL = [
        'code' => -309,
        'msg' => 'mysql连接池已满'
    ];

    const POOL_EMPTY = [
        'code' => -310,
        'msg' => 'mysql连接池已空'
    ];

    const PREPARE_ERROR = [
        'code' => -312,
        'msg' => '{code}: {msg}'
    ];

    const EXEUCTE_ERROR = [
        'code' => -313,
        'msg' => '{code}: {msg}'
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
