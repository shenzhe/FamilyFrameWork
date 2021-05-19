<?php
namespace Family\Core;

use Family\Exceptions\RouterException;
use Family\MVC\Controller;
use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;
use Swoole;

class Route
{
    /**
     * @return mixed|string
     * @throws \Throwable
     * @desc 自动路由
     */
    public static function dispatch()
    {
        $context = Swoole\Coroutine::getContext();
        /**
         * @var $request Request
         */
        $request = $context->request;
        $path = $request->server['path_info'];
        $httpMethod = $request->server['request_method'];

        if ('/favicon.ico' == $path) {
            return '';
        }

        if (empty($path) || '/' == $path) {
            return self::run('Index', 'Index');
        }

        //静态路由
        $sr = Config::get('static_router');
        if (!empty($sr)) {
            if (isset($sr[$path])) { //找到方法
                if (in_array($httpMethod, $sr[$path][0])) {
                    if (is_callable($sr[$path][1])) {
                        if (empty($sr[$path][2])) {
                            return $sr[$path][1]($request, $context->response??NULL);
                        }
                        return $sr[$path][1]($request, $context->response??NULL, ...$sr[$path][2]);
                    }
                    return self::run($sr[$path][1], $sr[$path][2]);
                }
                throw new RouterException(RouterException::METHOD_NOT_ALLOWED);
            }
        }

        $r = Config::get('router');
        //没有路由配置或者配置不可执行，则走默认路由
        if (empty($r) || !is_callable($r)) {
            return self::normal($path);
        }

        //引入fastrouter，进行路由检测
        $dispatcher = simpleDispatcher($r);
        $routeInfo = $dispatcher->dispatch($httpMethod, $path);

        //匹配到了
        if (Dispatcher::FOUND === $routeInfo[0]) {
            //匹配的是数组, 格式：['controllerName', 'MethodName']
            if (is_array($routeInfo[1])) {
                if (!empty($routeInfo[2]) && is_array($routeInfo[2])) {
                    //有默认参数
                    if ($request->get) {
                        $request->get += $routeInfo[2];
                    } else {
                        $request->get = $routeInfo[2];
                    }
                }
                $result = self::run($routeInfo[1][0], $routeInfo[1][1]);
            } elseif (is_string($routeInfo[1])) {
                //字符串, 格式：controllerName@MethodName
                list($controllerName, $methodName) = explode('@', $routeInfo[1]);
                if (!empty($routeInfo[2]) && is_array($routeInfo[2])) {

                    if ('{c}' === $controllerName && !empty($routeInfo[2]['c'])) {
                        $controllerName = $routeInfo[2]['c'];
                        unset($routeInfo[2]['c']);
                    }

                    if ('{m}' === $methodName && !empty($routeInfo[2]['m'])) {
                        $methodName = $routeInfo[2]['m'];
                        unset($routeInfo[2]['m']);
                    }

                    if (!empty($routeInfo[2])) {
                        //有默认参数
                        if ($request->get) {
                            $request->get += $routeInfo[2];
                        } else {
                            $request->get = $routeInfo[2];
                        }
                    }
                }
                $result = self::run($controllerName, $methodName);
            } elseif (is_callable($routeInfo[1])) {
                //回调函数，直接执行
                $result = $routeInfo[1]($request, $context->response, ...$routeInfo[2]);
            } else {
                throw new RouterException();
            }

            return $result;
        }

        //没找到路由，走默认的路由 http://xxx.com/{controllerName}/{MethodName}
        if (Dispatcher::NOT_FOUND === $routeInfo[0]) {

            return self::normal($path);
        }

        //匹配到了，但不允许的http method
        if (Dispatcher::METHOD_NOT_ALLOWED === $routeInfo[0]) {
            throw new RouterException(RouterException::METHOD_NOT_ALLOWED);
        }
    }

    /**
     * @param $path
     * @param $request
     * @return mixed
     * @throws \Throwable
     * @desc 没有匹配到路由，走默认的路由规则 http://xxx.com/{controllerName}/{MethodName}
     */
    public static function normal($path)
    {
        //默认访问 controller/index.php 的 index方法
        if (empty($path) || '/' == $path) {
            $controllerName = 'Index';
            $methodName = 'Index';
        } else {
            $maps = explode('/', $path);

            if (count($maps) < 2) {
                $controllerName = 'Index';
                $methodName = 'Index';
            } else {
                $controllerName = $maps[1];
                if (empty($maps[2])) {
                    $methodName = 'Index';
                } else {
                    $methodName = $maps[2];
                }
            }
        }

        return self::run($controllerName, $methodName);
    }

    private static function run($controllerName, $methodName)
    {
        $context = Swoole\Coroutine::getContext();
        $context->request->attributes[Controller::_CONTROLLER_KEY_] = $controllerName;
        $context->request->attributes[Controller::_METHOD_KEY_] = $methodName;
        $controllerName = "controller\\{$controllerName}";
        $controller = new $controllerName();
        try {
            $controller->_before();
            $result = $controller->$methodName();
        } catch (\Throwable $t) {
            $controller->_after(); //after必需执行
            throw $t;
        }
        return $result;
    }
}
