<?php


namespace Family\Helper;


interface EventHandler
{
    //服务启动回调s
    public function start($serv);

    //服务关闭回调
    public function shutdown($serv);

    //工作进程启动回调
    public function workerStart($serv, $worker_id);

    //工作进程关闭回调
    public function workerStop($serv, $worker_id);

    //工作进程错误退出回调
    public function workerError($serv, $worker_id, $worker_pid, $exit_code, $signal);

    //请求到达时回调
    public function onRequest();

    //请求结束后回调
    public function requestAfter();
}