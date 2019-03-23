<?php


namespace Family\Helper;


use Family\Core\Singleton;

class ProcessEvent implements ProcessEventHandler
{
    use Singleton;


    public function workerStart($pool, $worker_id, $running)
    {
        // TODO: Implement workerStart() method.
    }

    public function workerStop($pool, $worker_id)
    {
        // TODO: Implement workerStop() method.
    }

    public function message($pool, $data)
    {
        // TODO: Implement message() method.
    }
}