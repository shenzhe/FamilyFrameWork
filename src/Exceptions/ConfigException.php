<?php
namespace Family\Exceptions;
/**
 * 异常处理
 *
 * @package Family\Exceptions
 *
 */
class ConfigException extends BaseException
{
    const DEFAULT_ERROR = [
        'code' => -200,
        'msg' => '配置系统错误',
    ];

    const CONFIG_DIR_ERROR = [
        'code' => -201,
        'msg' => '配置目录{dir}不存在',
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
