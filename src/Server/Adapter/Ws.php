<?php


namespace Family\Server\Adapter;

use Family\Core\Config;
use Family\Family;
use Swoole;

class Ws
{
    public function __construct()
    {
        Swoole\Runtime::enableCoroutine();

        $http = new Swoole\WebSocket\Server(Config::get('host'), Config::get('port'));
        $http->set(Config::get('swoole_setting'));
        $http->on('start', function (\swoole_server $serv) {
            //设置时区
            $timeZone = Config::get('time_zone');
            if ($timeZone) {
                \date_default_timezone_set($timeZone);
            }
            //服务启动
            //日志初始化
            //            Log::init();
            file_put_contents(Family::$rootPath . DS . 'bin' . DS . 'master.pid', $serv->master_pid);
            file_put_contents(Family::$rootPath . DS . 'bin' . DS . 'manager.pid', $serv->manager_pid);
            //            Log::info("http server starting! {host}: {port}, masterId:{masterId}, managerId: {managerId}", [
            //                '{host}' => Config::get('host'),
            //                '{port}' => Config::get('port'),
            //                '{masterId}' => $serv->master_pid,
            //                '{managerId}' => $serv->manager_pid,
            //            ]);
            echo "http server staring! ://" . Config::get('host') . ":" . Config::get('port');
            WsHandler::onStart($serv);
        });

        $http->on('shutdown', function ($serv) {
            //服务关闭，删除进程id
            unlink(Family::$rootPath . DS . 'bin' . DS . 'master.pid');
            unlink(Family::$rootPath . DS . 'bin' . DS . 'manager.pid');
            //            Log::info("http server shutdown");
            echo "http server shutdown";
            WsHandler::onShutDown($serv);
        });

        $http->on('managerStart', function ($serv) {
            WsHandler::onManagerStart($serv);
        });

        $http->on('workerStop', function (\swoole_http_server $serv, int $worker_id) {
            WsHandler::onWorkerStop($serv, $worker_id);
        });

        $http->on('workerStart', function (\swoole_http_server $serv, int $worker_id) {
            WsHandler::onWorkerStart($serv, $worker_id);
        });

        $http->on('WorkerError', function (\swoole_http_server $serv, int $worker_id, int $worker_pid, int $exit_code, int $signal) {
            WsHandler::onWorkerError($serv, $worker_id, $worker_pid, $exit_code, $signal);
        });
        $http->on('request', function (
            \swoole_http_request $request,
            \swoole_http_response $response
        ) {
            WsHandler::onRequest($request, $response);
        });
        $http->on('message', function (
            \swoole_websocket_server $server,
            \swoole_websocket_frame $frame
        ) {
            WsHandler::onMessage($server, $frame);
        });

        $http->on('open', function (
            swoole_websocket_server $server,
            swoole_http_request $request
        ) {
            WsHandler::onOpen($server, $request);
        });

        $http->on('task', function (
            \swoole_websocket_server $server,
            \swoole_server_task $task
        ) {
            WsHandler::onTask($server, $task);
        });

        $http->on('finish', function (
            swoole_server $server,
            int $task_id,
            string $data
        ) {
            WsHandler::onFinish($server, $task_id, $data);
        });
        $http->start();
    }
}
