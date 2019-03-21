<?php


namespace Family\Helper;


use Family\Core\Singleton;

class Event implements EventHandler
{
    use Singleton;

    public function workerStart($serv, $worker_id)
    {
        // TODO: Implement workerStart() method.
    }

    public function onRequest()
    {
        // TODO: Implement onRequest() method.
    }

    public function requestAfter()
    {
        // TODO: Implement requestAfter() method.
    }
}