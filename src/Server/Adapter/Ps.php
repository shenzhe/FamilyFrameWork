<?php


namespace Family\Server\Adapter;


use Family\Core\Config;
use Family\Core\Log;
use Swoole;

class Ps
{
    public function __construct()
    {
        if (class_exists('ProcessEvent')) {
            Log::error('ProcessEvent class no exits');
            echo 'ProcessEvent class no exits';
            return '';
        }
        //加载配置
        $configDir = '';
        $options = getopt("c::");
        if (!empty($options['c'])) {
            $configDir = $options['c'];
        }
        Config::load($configDir);
        $timeZone = Config::get('time_zone', 'Asia/Shanghai');
        \date_default_timezone_set($timeZone);

        Swoole\Runtime::enableCoroutine();

        $workerNum = Config::getField('process', 'worker_num');
        if (empty($workerNum)) {
            $workerNum = swoole_cpu_num() - 1;
        }
        $ipcType = Config::getField('process', 'ipc_type', 0);

        $queueKey = Config::getField('process', 'msgqueue_key', 0);


        $pool = new Swoole\Process\Pool($workerNum, $ipcType, $queueKey);
        $pool->on('WorkerStart', function ($pool, $workerId) {
            while (true) {
                $event = new ProcessEvent();
                $event->workerStart($pool, $workerId);
                sleep(Config::get('process', 'sleep_time', 0.1));
            }
        });

        $pool->on("WorkerStop", function ($pool, $workerId) {
            echo "Worker#{$workerId} is stopped\n";
            $event = new ProcessEvent();
            $event->workerStop($pool, $workerId);
        });

        $pool->on("Message", function ($pool, $data) {
            $event = new ProcessEvent();
            $event->message($pool, $data);
        });

        $pool->start();
    }
}