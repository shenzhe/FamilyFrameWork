<?php


namespace Family\Server\Adapter;


use Family\Core\Config;
use Family\Coroutine\Coroutine;
use Family\Family;
use Swoole;

class Ps
{
    public function __construct()
    {
        if (!class_exists('ProcessEvent')) {
            Log::error('ProcessEvent class no exits');
            echo 'ProcessEvent class no exits';
            return '';
        }

        $daemonize = Config::getField('process', 'daemonize', 0);
        if ($daemonize) {
            //守护进程化
            Swoole\Process::daemon();
        }

        Swoole\Runtime::enableCoroutine();

        $workerNum = Config::getField('process', 'worker_num');
        if (empty($workerNum)) {
            $workerNum = swoole_cpu_num() - 1;
        }
        $ipcType = Config::getField('process', 'ipc_type', 0);

        $queueKey = Config::getField('process', 'msgqueue_key', 0);
        $binDir = Family::$applicationPath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin';
        if (is_dir($binDir)) {
            //写入进程id
            $pid = posix_getpid();
            file_put_contents($binDir . DIRECTORY_SEPARATOR . 'master.pid', $pid);
            file_put_contents($binDir . DIRECTORY_SEPARATOR . 'manager.pid', $pid);
        }

        $pool = new Swoole\Process\Pool($workerNum, $ipcType, $queueKey);
        $pool->on('WorkerStart', function ($pool, $workerId) {
            $running = true;
            Swoole\Process::signal(SIGTERM, function () use (&$running) {
                $running = false;
            });
            Coroutine::create(function () use ($pool, $workerId, &$running) {
                while ($running) {
                    $event = new \ProcessEvent();
                    $event->workerStart($pool, $workerId, $running);
                    sleep(Config::getField('process', 'sleep_time', 0.1));
                }
            });
        });

        $pool->on("WorkerStop", function ($pool, $workerId) {
            Coroutine::create(function () use ($pool, $workerId) {
                $event = new \ProcessEvent();
                $event->workerStop($pool, $workerId);
            });
        });


        $listen = Config::getField('process', 'listen');
        if (!empty($listen)) {
            $pool->listen($listen['host'], $listen['port'], $listen['backlog'] ?? 2048);
            $pool->on("Message", function ($pool, $data) {
                Coroutine::create(function () use ($pool, $data) {
                    $event = new \ProcessEvent();
                    $event->message($pool, $data);
                });
            });
        }

        $pool->start();
    }
}