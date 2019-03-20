<?php


namespace Family\Helper;


interface EventHandler
{
    //工作进程启动回调
    public function workerStart();
    //请求到达时回调
    public function onRequest();
    //请求结束后回调
    public function requestAfter();
}