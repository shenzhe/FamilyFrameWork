<?php


namespace Family\Helper;


interface ProcessEventHandler
{


    //工作进程启动回调
    public function workerStart($serv, $worker_id);

    //工作进程关闭回调
    public function workerStop($serv, $worker_id);

    public function message($pool, $data);
}