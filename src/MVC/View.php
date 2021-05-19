<?php
namespace Family\MVC;

use Family\Core\Config;

class View
{
    public function render($data)
    {
        $mode = Config::get('view_mode', 'Json');

    }
}