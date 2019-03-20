<?php


namespace Family\Server\Adapter;


use Family\Core\Config;
use Family\Core\Log;
use Family\Core\Route;
use Family\Coroutine\Context;
use Family\Coroutine\Coroutine;
use Family\Exceptions\BaseException;
use Family\Pool;

use Family\Helper;

class WsHandler
{
    /**
     * @var Helper\EventHandler
     */
    public static $eventHandler;

    public static function onWorkerStart(\swoole_http_server $serv, int $worker_id)
    {
        if (0 == $worker_id) {
            if (function_exists('opcache_reset')) {
                //清除opcache 缓存，swoole模式下其实可以关闭opcache
                \opcache_reset();
            }
        }
        try {
            //加载配置，让此处加载的配置可热更新
            Config::loadLazy();
            //日志初始化
            Log::init();
            /**
             * @var $event Helper\EventHandler
             */
            $eventClass = Config::get('event_handler');
            if (!empty($eventClass)) {
                $event = new $eventClass();
                if (!($event instanceof Helper\EventHandler)) {
                    Log::error("event must implements Helper\EventHandler");
                } else {
                    self::$eventHandler = $event;
                    $event->workerStart();
                }
            }
        } catch (\Exception $e) {
            Log::exception($e);
            $serv->shutdown();
        } catch (\Throwable $throwable) {
            Log::exception($throwable);
            $serv->shutdown();
        }
    }

    public static function onRequest(
        \swoole_http_request $request,
        \swoole_http_response $response
    )
    {
        if (self::$eventHandler) {
            self::$eventHandler->onRequest();
        }
        if ('OPTIONS' === strtoupper($request->server['request_method'])) {
            $allowMethod = Config::get('allow_http_method', 'GET, HEAD, PUT, DELETE, POST, OPTIONS');
            $response->header('Allow', $allowMethod);
            $response->end();
            return;
        }
        //初始化根协程ID
        Coroutine::setBaseId();
        //初始化上下文
        $context = new Context($request, $response);
        //存放容器pool
        Pool\Context::getInstance()->put($context);
        //协程退出，自动清空
        defer(function () {
            //清空当前pool的上下文，释放资源
            Pool\Context::getInstance()->release();
        });
        try {
            //自动路由
            $result = Route::dispatch();
        } catch (\Exception $e) { //程序异常
            $exceptionHandler = Config::get('exception_handler', BaseException::class);
            Log::debug("exception_handler:" . $exceptionHandler);
            $result = forward_static_call([$exceptionHandler, 'exceptionHandler'], $e);
            Log::debug("exception ret:" . $result);
        } catch (\Error $e) { //程序错误，如fatal error
            $exceptionHandler = Config::get('exception_handler', BaseException::class);
            $result = forward_static_call([$exceptionHandler, 'exceptionHandler'], $e);
        } catch (\Throwable $e) {  //兜底
            $exceptionHandler = Config::get('exception_handler', BaseException::class);
            $result = forward_static_call([$exceptionHandler, 'exceptionHandler'], $e);
        }
        $response->end($result);
        //记录请求日志
        Log::access($request);
        if (self::$eventHandler) {
            self::$eventHandler->requestAfter();
        }
    }

    public static function onMessage(
        \swoole_websocket_server $server,
        \swoole_websocket_frame $frame
    )
    {
        //初始化根协程ID
        Coroutine::setBaseId();
        //初始化上下文
        $context = new Context(Helper\Protocol::frameToRequest($frame));
        $context->set('frame', $frame);
        //存放容器pool
        Pool\Context::getInstance()->put($context);
        //协程退出，自动清空
        defer(function () {
            //清空当前pool的上下文，释放资源
            Pool\Context::getInstance()->release();
        });

        try {
            //自动路由
            $result = Route::dispatch();
            $server->push($frame->fd, $result);
        } catch (\Exception $e) { //程序异常
            $result = BaseException::exceptionHandler($e);
            $server->push($frame->fd, $result);
        } catch (\Error $e) { //程序错误，如fatal error
            $result = BaseException::exceptionHandler($e);
            $server->push($frame->fd, $result);
        } catch (\Throwable $e) {  //兜底
            $result = BaseException::exceptionHandler($e);
            $server->push($frame->fd, $result);
        }
    }
}