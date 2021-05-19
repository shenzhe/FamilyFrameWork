<?php

namespace Family\MVC;

use Swoole;

abstract class Controller
{

    /**
     * @var Swoole\Http\Request
     */
    protected $request;
    protected $template;

    const _CONTROLLER_KEY_ = '__CTR__';
    const _METHOD_KEY_ = '__METHOD__';

    public function __construct()
    {
        $this->request = Swoole\Coroutine::getContext()->request;
    }

    public function _before()
    { }
    public function _after()
    { }
}
