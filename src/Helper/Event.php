<?php


namespace Family\Helper;


use Family\Core\Singleton;

class Event implements EventHandler
{
    use Singleton;

    public function start($serv)
    {
        // TODO: Implement start() method.
    }

    public function shutdown($serv)
    {
        // TODO: Implement shutdown() method.
    }

    public function workerStart($serv, $worker_id)
    {
        // TODO: Implement workerStart() method.
    }

    public function workerStop($serv, $worker_id)
    {
        // TODO: Implement workerStop() method.
    }

    public function workerError($serv, $worker_id, $worker_pid, $exit_code, $signal)
    {
        // TODO: Implement workerError() method.
    }

    public function onRequest($request)
    {
        // TODO: Implement onRequest() method.
    }

    public function requestAfter($request, $response, $result)
    {
        // TODO: Implement requestAfter() method.
    }

    public function messageAfter($serv, $frame, $result)
    {
        // TODO: Implement messageAfter() method.
        $serv->push($frame->fd, $result);
    }

    public function taskAfter($serv, $result)
    {
        // TODO: Implement taskAfter() method.
    }
}
