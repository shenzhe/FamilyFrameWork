<?php
//file framework/Family/Cache/Redis.php
namespace Family\Cache;

use Family\Core\Log;
use Family\Exceptions\RedisException;
use Swoole\Coroutine\Redis as coRedis;

class Redis
{
    /**
     * @var coRedis
     */
    private $redis;
    private $config;

    private $noSupport = ['scan', 'object', 'sort', 'migrate', 'hscan', 'sscan', 'zscan'];

    /**
     * @param $config
     * @return mixed
     * @throws \Exception
     * @desc 连接redis
     */
    public function connect($config)
    {
        //创建主数据连接
        if (empty($config)) {
            throw new RedisException(RedisException::CONFIG_EMPTY);
        }

        $redis = new coRedis();
        $res = $redis->connect($config['host'], $config['port']);
        if ($res === false) {
            //连接失败，抛弃常
            throw new RedisException(
                RedisException::CONNECT_ERROR,
                [
                    'msg' => $redis->errMsg,
                    'code' => $redis->errCode
                ]
            );
        } else {
            if (empty($config['password'])) {
                $res = $redis->auth($config['password']);
                if (false === $res) { //鉴权失败
                    throw new RedisException(
                        RedisException::AUTH_ERROR,
                        [
                            'msg' => $redis->errMsg,
                            'code' => $redis->errCode
                        ]
                    );
                }
            }
            $this->redis = $redis;
            if (!empty($config['options'])) {
                $this->redis->setOptions($config['options']);
            }

            if (!empty($config['db'])) {
                $this->select($config['db']);
            }
        }


        $this->config = $config;

        return $res;
    }

    /**
     * @return coRedis
     * @desc 获取redis操作实例
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @desc 利用__call,实现操作redis,并能做断线重连等相关检测
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (in_array($name, $this->noSupport)) {
            throw new RedisException(RedisException::NO_SUPPORT_CMD, ['cmd' => $name]);
        }
        $time = microtime(true);
        $result = call_user_func_array([$this->redis, $name], $arguments);
        Log::debug($name . ':' . (microtime(true) - $time));
        if (false === $result) {
            if (!$this->redis->connected) { //断线重连
                $this->connect($this->config);
                $time = microtime(true);
                $result = call_user_func_array([$this->redis, $name], $arguments);
                Log::debug($name . ':' . (microtime(true) - $time));
            }

            if (!empty($this->redis->errCode)) {  //有错误码，则抛出弃常
                throw new RedisException(
                    RedisException::QUERY_ERROR,
                    [
                        'msg' => $this->redis->errMsg,
                        'code' => $this->redis->errCode
                    ]
                );
            }
        }
        return $result;
    }


    /**
     * @desc 回收资源
     */
    public function release()
    {
        $this->redis->close();
    }

    /**
     * @return mixed
     * @desc 返回配置信息
     */
    public function getConfig()
    {
        return $this->config;
    }
}
