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
        $request->server['request_method'] = 'POST';
        $request->server['server_protocol'] = 'HTTP/1.1';
        $fun = Config::get('frame_parse_fun');
        if (!empty($fun)) {
            $ret = \call_user_func($fun, $frame->data);
            $request->server['path_info'] = $ret[0];

            $request->post = $ret[1];
        } else {
            $request->server['path_info'] = '/';
            $request->post = $frame->data;
        }
        $request->server['server_port'] = Config::get('port', 0);
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
        $request->server['server_port'] = Config::get('port', 0);
        $request->post = $task->data;
        return $request;
    }
}
