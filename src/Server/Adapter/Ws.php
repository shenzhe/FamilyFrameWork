<?php
namespace Family\Server\Adapter;

use Family\Core\Config;
use Family\Family;
use Swoole;
use Family\Core\Log;

class Ws
{
    public function __construct()
    {
        Swoole\Runtime::enableCoroutine();

        $http = new Swoole\WebSocket\Server(Config::get('host'), Config::get('port'));
        $http->set(Config::get('swoole_setting'));
        $http->on('start', function (Swoole\Server $serv) {
            //服务启动
            //日志初始化
            //            Log::init();
            file_put_contents(Family::$rootPath . DS . 'bin' . DS . 'master.pid', $serv->master_pid);
            file_put_contents(Family::$rootPath . DS . 'bin' . DS . 'manager.pid', $serv->manager_pid);
            Log::info("http server starting! {host}: {port}, masterId:{masterId}, managerId: {managerId}", [
                '{host}' => Config::get('host'),
                '{port}' => Config::get('port'),
                '{masterId}' => $serv->master_pid,
                '{managerId}' => $serv->manager_pid,
            ]);
            echo "http server staring! ://" . Config::get('host') . ":" . Config::get('port');
            WsHandler::onStart($serv);
        });

        $http->on('shutdown', function ($serv) {
            //服务关闭，删除进程id
            unlink(Family::$rootPath . DS . 'bin' . DS . 'master.pid');
            unlink(Family::$rootPath . DS . 'bin' . DS . 'manager.pid');
            Log::info("http server shutdown");
            echo "http server shutdown";
            WsHandler::onShutDown($serv);
        });

        $http->on('managerStart', function ($serv) {
            WsHandler::onManagerStart($serv);
        });

        $http->on('workerStop', function (Swoole\WebSocket\Server $serv, int $worker_id) {
            WsHandler::onWorkerStop($serv, $worker_id);
        });
        

        $http->on('workerStart', function (Swoole\WebSocket\Server $serv, int $worker_id) {
            WsHandler::onWorkerStart($serv, $worker_id);
        });

        $http->on('WorkerError', function (Swoole\WebSocket\Server $serv, int $worker_id, int $worker_pid, int $exit_code, int $signal) {
            WsHandler::onWorkerError($serv, $worker_id, $worker_pid, $exit_code, $signal);
        });

        $http->on('workerExit', function (Swoole\WebSocket\Server $serv, int $worker_id) {
            WsHandler::onWorkerExit($serv, $worker_id);
        });

        $http->on('request', function (
            Swoole\Http\Request $request,
            Swoole\Http\Response $response
        ) {
            WsHandler::onRequest($request, $response);
        });
        $http->on('message', function (
            Swoole\WebSocket\Server $server,
            Swoole\WebSocket\Frame $frame
        ) {
            WsHandler::onMessage($server, $frame);
        });

        $http->on('open', function (
            Swoole\WebSocket\Server $server,
            Swoole\Http\Request $request
        ) {
            WsHandler::onOpen($server, $request);
        });

        $http->on('close', function (
            Swoole\WebSocket\Server $server,
            int $fd,
            int $reactorId
        ) {
            WsHandler::onClose($server, $fd, $reactorId);
        });

        $http->on('task', function (
            Swoole\WebSocket\Server $server,
            Swoole\Server\Task $task
        ) {
            WsHandler::onTask($server, $task);
        });

        $http->on('finish', function (
            $server,
            int $task_id,
            $data
        ) {
            WsHandler::onFinish($server, $task_id, $data);
        });

        $http->on('pipeMessage', function (
            $server,
            int $src_worker_id,
            $data
        ) {
            WsHandler::onPipeMessage($server, $src_worker_id, $data);
        });
        $http->start();
    }
}
