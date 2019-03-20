<?php


namespace Family\Server;


use Family\Core\Config;

class Server
{
    public function start()
    {
        $serverMode = Config::get('server_mode', 'ws');
        $adapter = ucfirst(strtolower($serverMode));
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return new $className();
    }
}