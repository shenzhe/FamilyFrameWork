<?php
namespace Family\Log;

use Family\Core\Config;
use Family\Core\Singleton;

class Factory
{
    use Singleton;

    public function __construct()
    {
        $config = Config::get('log');
        $adapter = $config['adapter'] ?? 'File';
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return new $className($config);
    }
}
