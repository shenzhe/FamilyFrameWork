<?php
//file framework/Family/Pool/Redis.php
namespace Family\Pool;

use Family\Cache\Redis as Cache;
use chan;
use Family\Core\Config;
use Family\Exceptions\RedisException;

class Redis implements PoolInterface
{
    private $pool;  //连接池容器，一个channel
    private $config;

    private static $instances;

    /**
     * @param null $config
     * @return Mysql
     * @desc 获取连接池实例
     * @throws \Exception
     */
    public static function getInstance($tag)
    {
        if (empty(self::$instances[$tag])) {
            self::$instances[$tag] = new static(Config::getField('redis', $tag));
        }
        return self::$instances[$tag];
    }

    /**
     * @param null
     * @return null
     * @desc 初始化连接池实例
     * @throws \Exception
     */
    public static function init($throw = false)
    {
        $config = Config::get('redis');
        if (empty($config)) {
            if ($throw) {
                throw new RedisException(RedisException::CONFIG_EMPTY);
            } else {
                return false;
            }
        }

        foreach ($config as $tag => $conf) {
            self::$instances[$tag] = new static($conf);
        }
    }

    /**
     * Redis constructor.
     * @param $config
     * @throws \Exception
     * @desc 初始化，自动创建实例,需要放在workerstart中执行
     */
    public function __construct($config)
    {
        if (empty($this->pool)) {
            $this->config = $config;
            $this->pool = new chan($config['pool_size']);
            for ($i = 0; $i < $config['pool_size']; $i++) {
                $redis = new Cache();
                $res = $redis->connect($config);
                if ($res == false) {
                    //连接失败，抛弃常
                    throw new RedisException(RedisException::CONNECT_ERROR);
                } else {
                    //redis连接存入channel
                    $this->put($redis);
                }
            }
        }
    }

    /**
     * @param $redis
     * @throws \Exception
     * @desc 放入一个redis连接入池
     */
    public function put($redis)
    {
        if ($this->getLength() >= $this->config['pool_size']) {
            throw new RedisException(RedisException::POOL_FULL);
        }
        $this->pool->push($redis);
    }

    /**
     * @return mixed
     * @desc 获取一个连接，当超时，返回一个异常
     * @throws \Exception
     */
    public function get()
    {
        $redis = $this->pool->pop($this->config['pool_get_timeout']);
        if (false === $redis) {
            throw new RedisException(RedisException::POOL_EMPTY);
        }
        return $redis;
    }

    /**
     * @return mixed
     * @desc 获取当时连接池可用对象
     */
    public function getLength()
    {
        return $this->pool->length();
    }

    /**
     * @return bool|mixed
     * @desc 回收处理
     */
    public function release()
    {
        if ($this->getLength() < $this->config['pool_size']) {
            //还有未归源的资源
            return true;
        }
        for ($i = 0; $i < $this->config['pool_size']; $i++) {
            $db = $this->pool->pop($this->config['pool_get_timeout']);
            if (false !== $db) {
                $db->release();
            }
        }
        return $this->pool->close();
    }
}
