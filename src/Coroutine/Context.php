<?php
namespace Family\Coroutine;


use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use Swoole;

class Context
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;

    /**
     * @var array 一个array，可以存取想要的任何东西
     */
    private $map = [];

    public function __construct(Swoole\Http\Request $request = null, Swoole\Http\Response $response = null)
    {
        if ($request) {
            $this->request = $request;
        }
        if ($response) {
            $this->response = $response;
        }
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param $key
     * @param $val
     */
    public function set($key, $val)
    {
        $this->map[$key] = $val;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        if (isset($this->map[$key])) {
            return $this->map[$key];
        }

        return null;
    }
}