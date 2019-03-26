<?php


namespace Family\Helper;


use Family\Core\Config;
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
        $request->server['request_method'] = 'POST';
        $request->server['path_info'] = '/task';
        $request->server['server_protocol'] = 'HTTP/1.1';
        $request->server['server_port'] = Config::get('port');
        $request->post = $task->data;
        return $request;
    }
}