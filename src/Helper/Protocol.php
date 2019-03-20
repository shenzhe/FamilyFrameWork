<?php


namespace Family\Helper;


use Swoole\Http\Request;

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
}