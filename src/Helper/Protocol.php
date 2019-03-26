<?php


namespace Family\Helper;


use Swoole\Http\Request;
use Swoole\Server\Task;

class Protocol
{
    public static function frameToRequest(
        \swoole_websocket_frame $frame
    ): Request
    {
        $request = new Request();
        $request->fd = $frame->fd;
        $request->server['http_method'] = 'POST';
        $data = json_decode($frame->data);
        $request->server['path_info'] = $data[0];
        $request->post = $data[1];
        return $request;
    }

    public static function taskToRequest(
        Task $task
    ): Request
    {
        $request = new Request();
        $request->server['http_method'] = 'POST';
        $request->server['path_info'] = '/task';
        $request->post = $task->data;
        return $request;
    }
}