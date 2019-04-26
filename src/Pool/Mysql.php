<?php
//file framework/Family/Pool/Mysql.php
namespace Family\Pool;

use Family\Db\Mysql as DB;
use chan;
use Family\Exceptions\MysqlException;
use Family\Core\Config;
use Family\Core\Log;

class Mysql implements PoolInterface
{
    private static $instances;
    private $pool;  //连接池容器，一个channel
    private $config;


    /**
     * @param null $config
     * @return Mysql
     * @desc 获取连接池实例
     * @throws \Exception
     */
    public static function getInstance($tag)
    {
        if (empty(self::$instances[$tag])) {
            self::$instances[$tag] = new static(Config::getField('mysql', $tag));
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
        $config = Config::get('mysql');
        if (empty($config)) {
            if ($throw) {
                throw new MysqlException(MysqlException::CONFIG_EMPTY);
            }
            return false;
        }

        foreach ($config as $tag => $conf) {
            self::$instances[$tag] = new static($conf);
        }
    }

    /**
     * Mysql constructor.
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
                $mysql = new DB();
                $res = $mysql->connect($config);
                if ($res == false) {
                    //连接失败，抛弃常
                    throw new MysqlException(MysqlException::CONNECT_ERROR);
                } else {
                    //mysql连接存入channel
                    $this->put($mysql);
                }
            }
        }
    }

    /**
     * @param $mysql
     * @throws \Exception
     * @desc 放入一个mysql连接入池
     */
    public function put($mysql)
    {
        $len = $this->getLength();

        if ($len >= $this->config['pool_size']) {
            throw new MysqlException(MysqlException::POOL_FULL);
        }
        $this->pool->push($mysql);
        Log::debug("mysql pool len:" . $this->getLength());
    }

    /**
     * @return mixed
     * @desc 获取一个连接，当超时，返回一个异常
     * @throws \Exception
     */
    public function get()
    {

        $mysql = $this->pool->pop($this->config['pool_get_timeout']);
        if (false === $mysql) {
            throw new MysqlException(MysqlException::POOL_EMPTY);
        }
        Log::debug("mysql pool len:" . $this->getLength());
        return $mysql;
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
