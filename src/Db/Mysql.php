<?php
//file framework/Family/Db/Mysql.php
namespace Family\Db;

use Family\Core\Log;
use Family\Exceptions\MysqlException;
use Swoole\Coroutine\MySQL as SwMySql;

class Mysql
{
    /**
     * @var SwMySql
     */
    private $master;   //主数据库连接
    /**
     * @var array SwMySql
     */
    private $slave;     //从数据库连接list
    private $config;    //数据库配置

    /**
     * @param $config
     * @return mixed
     * @throws \Exception
     * @desc 连接mysql
     */
    public function connect($config)
    {
        //创建主数据连接
        $master = new SwMySql();
        $res = $master->connect($config['master']);
        if ($res === false) {
            //连接失败，抛弃常
            throw new MysqlException(
                MysqlException::CONNECT_ERROR,
                [
                    'msg' => $master->connect_error,
                    'code' => $master->errno
                ]
            );
        } else {
            //存入master资源
            $this->master = $master;
        }

        if (!empty($config['slave'])) {
            //创建从数据库连接
            foreach ($config['slave'] as $conf) {
                $slave = new SwMySql();
                $res = $slave->connect($conf);
                if ($res === false) {
                    //连接失败，抛弃常
                    throw new MysqlException(
                        MysqlException::CONNECT_ERROR,
                        [
                            'msg' => $slave->connect_error,
                            'code' => $slave->errno
                        ]
                    );
                } else {
                    //存入slave资源
                    $this->slave[] = $slave;
                }
            }
        }

        $this->config = $config;
        $this->lastTime = time();
        return $res;
    }

    /**
     * @param $type
     * @param $index
     * @return SwMySql
     * @desc 单个数据库重连
     * @throws \Exception
     */
    public function reconnect($type, $index)
    {
        //通过type判断是主还是从
        if ('master' == $type) {
            //创建主数据连接
            $master = new SwMySql();
            $res = $master->connect($this->config['master']);
            if ($res === false) {
                //连接失败，抛弃常
                throw new MysqlException(
                    MysqlException::CONNECT_ERROR,
                    [
                        'msg' => $master->connect_error,
                        'code' => $master->errno
                    ]
                );
            } else {
                //更新主库连接
                $this->master = $master;
            }
            return $this->master;
        }

        if (!empty($this->config['slave'])) {
            //创建从数据连接
            $slave = new SwMySql();
            $res = $slave->connect($this->config['slave'][$index]);
            if ($res === false) {
                //连接失败，抛弃常
                throw new MysqlException(
                    MysqlException::CONNECT_ERROR,
                    [
                        'msg' => $slave->connect_error,
                        'code' => $slave->errno
                    ]
                );
            } else {
                //更新对应的重库连接
                $this->slave[$index] = $slave;
            }
            return $slave;
        }
    }

    public function query($sql, $param)
    {
        $res = $this->chooseDb($sql);
        /**
         * @var $db SwMySql
         */
        $db = $res['db'];
        $stmt = $db->prepare($sql);
        if (false === $stmt) {
            throw new MysqlException(MysqlException::PREPARE_ERROR, [
                'code' => $db->errno,
                'msg' => $db->error
            ]);
        }

        $result =  $stmt->execute($param);
        if (false === $result) {
            Log::warning('mysql query:{sql} false, params:{params}', ['{sql}' => $sql, '{params}' => $param]);
            if (!$db->connected) { //断线重连
                $db = $this->reconnect($res['type'], $res['index']);
                $time = microtime(true);
                $result = $db->query($sql, $param);
                return $this->parseResult($result, $db);
            }

            if (!empty($db->errno)) {  //有错误码，则抛出弃常
                throw new MysqlException(
                    MysqlException::QUERY_ERROR,
                    [
                        'msg' => $db->error,
                        'code' => $db->errno
                    ]
                );
            }
        }

        return $this->parseResult($result, $stmt);
    }

    public function exeucte($sql, $param)
    {
        $res = $this->chooseDb($sql);
        $db = $res['db'];
        $stmt = $db->prepare($sql);
        if (false === $stmt) {
            throw new MysqlException(MysqlException::PREPARE_ERROR, [
                'code' => $db->errno,
                'msg' => $db->error
            ]);
        }

        $result =  $stmt->execute($param);
        if (false === $result) {
            if (!$db->connected) { //断线重连
                $db = $this->reconnect($res['type'], $res['index']);
                $result = $db->exeucte($sql, $param);
                return [
                    'affected_rows' => $stmt->affected_rows,
                    'insert_id' => $stmt->insert_id,
                ];
            }

            if (!empty($db->errno)) {  //有错误码，则抛出弃常
                throw new MysqlException(
                    MysqlException::EXEUCTE_ERROR,
                    [
                        'msg' => $db->error,
                        'code' => $db->errno
                    ]
                );
            }
        }

        return [
            'affected_rows' => $stmt->affected_rows,
            'insert_id' => $stmt->insert_id,
        ];
    }

    /**
     * @param $sql
     * @return mixed
     * @desc 实现操作mysql,并能做断线重连等相关检测
     * @throws \Exception
     */
    public function querySql($sql)
    {
        $res = $this->chooseDb($sql);
        /**
         * @var SwMySql
         */
        $db = $res['db'];
        // $time = microtime(true);
        $result = $db->query($sql);
        // Log::debug($sql . ':' . (microtime(true) - $time));
        if (false === $result) {
            Log::warning('mysql query:{sql} false', ['{sql}' => $sql]);
            if (!$db->connected) { //断线重连
                $db = $this->reconnect($res['type'], $res['index']);
                $time = microtime(true);
                $result = $db->querySql($sql);
                // Log::debug($sql . ':' . (microtime(true) - $time));
                return $this->parseResult($result, $db);
            }

            if (!empty($db->errno)) {  //有错误码，则抛出弃常
                throw new MysqlException(
                    MysqlException::QUERY_ERROR,
                    [
                        'msg' => $db->error,
                        'code' => $db->errno
                    ]
                );
            }
        }
        return $this->parseResult($result, $db);
    }

    /**
     * 
     */
    public function escape($val)
    {
        if (method_exists($this->master, 'escape')) {
            return $this->master->escape($val);
        }

        return addslashes($val);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @desc 利用__call,实现操作mysql,并能做断线重连等相关检测
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $sql = $arguments[0];
        $res = $this->chooseDb($sql);
        $db = $res['db'];
        //        $result = call_user_func_array([$db, $name], $arguments);
        $time = microtime(true);
        $result = $db->$name($sql);
        // Log::debug($sql . ':' . (microtime(true) - $time));
        if (false === $result) {
            Log::warning('mysql query:{sql} false', ['{sql}' => $sql]);
            if (!$db->connected) { //断线重连
                $db = $this->reconnect($res['type'], $res['index']);
                $time = microtime(true);
                $result = $db->$name($sql);
                // Log::debug($sql . ':' . (microtime(true) - $time));
                return $this->parseResult($result, $db);
            }

            if (!empty($db->errno)) {  //有错误码，则抛出弃常
                throw new MysqlException(
                    MysqlException::QUERY_ERROR,
                    [
                        'msg' => $db->error,
                        'code' => $db->errno
                    ]
                );
            }
        }
        return $this->parseResult($result, $db);
    }

    /**
     * @param $result
     * @param $db MySQL
     * @return array
     * @desc 格式化返回结果：查询：返回结果集，插入：返回新增id, 更新删除等操作：返回影响行数
     */
    public function parseResult($result, $db)
    {
        if ($result === true) {
            return [
                'affected_rows' => $db->affected_rows,
                'insert_id' => $db->insert_id,
            ];
        }
        return $result;
    }


    /**
     * @param $sql
     * @desc 根据sql语句，选择主还是从
     * @ 判断有select 则选择从库， insert, update, delete等选择主库
     * @return array
     */
    protected function chooseDb($sql)
    {
        if (!empty($this->slave)) {
            //查询语句，随机选择一个从库
            if ('select' == strtolower(substr($sql, 0, 6))) {
                if (1 == count($this->slave)) {
                    $index = 0;
                } else {
                    $index = array_rand($this->slave);
                }
                return [
                    'type' => 'slave',
                    'index' => $index,
                    'db' => $this->slave[$index],

                ];
            }
        }

        return [
            'type' => 'master',
            'index' => 0,
            'db' => $this->master
        ];
    }

    /**
     * @desc 回收资源
     */
    public function release()
    {
        $this->master->close();
        if (!empty($this->slave)) {
            foreach ($this->slave as $slave) {
                $slave->close();
            }
        }
    }

    /**
     * @return mixed
     * @desc 返回配置信息
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function ping()
    {
        try {
            if (!$this->master->connected) {
                $this->reconnect('master', 0);
            } else {
                $this->master->query("select 1");
            }
        } catch (MysqlException $e) {
            Log::exception($e);
        }

        if (!empty($this->slave)) {
            foreach ($this->slave as $idx => $slave) {
                try {
                    if (!$slave->connected) {
                        $this->reconnect('slave', $idx);
                    } else {
                        $slave->query("select 1");
                    }
                } catch (MysqlException $e) {
                    Log::exception($e);
                }
            }
        }
    }
}
