<?php
namespace Family\Log;

use Family\Core\Config;
use Family\Core\Singleton;

class Factory
{
    use Singleton;

    public $logger;

    public function __construct()
    {
        $config = Config::get('log');
        $adapter = $config['adapter'] ?? 'File';
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        $this->logger =  new $className($config);
    }
}
