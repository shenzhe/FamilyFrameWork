<?php


namespace Family\Helper;


use Family\Core\Singleton;

class Event implements EventHandler
{
    use Singleton;
    public function serverStart()
    {
        // TODO: Implement serverStart() method.
    }

    public function workerStart()
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