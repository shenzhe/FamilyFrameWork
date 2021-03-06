<?php


namespace Family\Helper;


use Family\Core\Config;
use Family\Core\Singleton;
use Family\Coroutine\Coroutine;
use Family\Pool\Redis as RedisPool;

class Redis
{
    use Singleton;

    /**
     * @var redis连接数组
     * @desc 不同协程不能复用redis连接，所以通过协程id进行资源隔离
     */
    private $rediss;

    /**
     * @return \Swoole\Coroutine\Redis
     */
    public function getRedis($tag)
    {
        $coId = Coroutine::getId();
        if (empty($this->rediss[$coId][$tag])) {
            //不同协程不能复用redis连接，所以通过协程id进行资源隔离
            //达到同一协程只用一个redis连接，不同协程用不同的redis连接
            $redis = RedisPool::getInstance($tag)->get();
            $this->rediss[$coId][$tag] = $redis;
            defer(function () use ($redis, $tag) {
                //利用协程的defer特性，自动回收资源
                RedisPool::getInstance($tag)->put($redis);
            });
        }
        return $this->rediss[$coId][$tag];
    }
}
