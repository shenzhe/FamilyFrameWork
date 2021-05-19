<?php


namespace Family\Helper;

use Swoole;


use Family\Core\Config;

class Protocol
{
    /**
     * @return Swoole\Http\Request
     */
    public static function frameToRequest(
        Swoole\WebSocket\Frame $frame
    ) {
        $request = new Swoole\Http\Request();
        $request->server['request_method'] = 'POST';
        $request->server['server_protocol'] = 'WS';
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
        $request->fd = $frame->fd;
        $request->_type = 'WS';
        return $request;
    }

    /**
     * @return Swoole\Http\Request
     */
    public static function taskToRequest(
        Swoole\Server\Task $task
    ) {
        $request = new Swoole\Http\Request();
        $request->server['request_method'] = 'POST';
        $request->server['path_info'] = '/task';
        $request->server['server_protocol'] = 'HTTP/1.1';
        $request->server['server_port'] = Config::get('port', 0);
        $request->post = $task->data;
        $request->_type = 'TASK';
        return $request;
    }
}
