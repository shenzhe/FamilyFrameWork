<?php


namespace Family\Helper;


use Family\Core\Config;

class Protocol
{
    /**
     * @return \swoole_http_request
     */
    public static function frameToRequest(
        \swoole_websocket_frame $frame
    ) {
        $request = new \swoole_http_request();
        $request->fd = $frame->fd;
        $request->server['http_method'] = 'POST';
        $data = json_decode($frame->data);
        $request->server['path_info'] = $data[0];
        $request->post = $data[1];
        return $request;
    }

    /**
     * @return \swoole_http_request
     */
    public static function taskToRequest(
        \swoole_server_task $task
    ) {
        $request = new \swoole_http_request();
        $request->server['request_method'] = 'POST';
        $request->server['path_info'] = '/task';
        $request->server['server_protocol'] = 'HTTP/1.1';
        $request->server['server_port'] = Config::get('port');
        $request->post = $task->data;
        return $request;
    }
}
