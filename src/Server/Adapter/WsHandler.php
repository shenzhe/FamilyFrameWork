<?php


namespace Family\Server\Adapter;


use Family\Core\Config;
use Family\Core\Log;
use Family\Core\Route;
use Family\Coroutine\Context;
use Family\Coroutine\Coroutine;
use Family\Exceptions\BaseException;
use Family\Family;
use Family\Pool;

use Family\Helper;

class WsHandler
{
    /**
     * @var Helper\EventHandler
     */
    public static $eventHandler;

    public static function onStart($serv)
    {
        if (class_exists('Event')) {
            $event = new \Event();
            if (!($event instanceof Helper\EventHandler)) {
                Log::error("event must implements Helper\EventHandler");
            } else {
                self::$eventHandler = $event;
                $event->start($serv);
            }
        }

        if ('Darwin' !== PHP_OS) {
            $title = sprintf(
                "master, ws://%s:%d, running:%s",
                Config::get('host'),
                Config::get('port'),
                date("Y-m-d H:i:s")
            );
            swoole_set_process_name($title);
        }
    }

    public static function onShutDown($serv)
    {
        if (self::$eventHandler) {
            self::$eventHandler->shutdown($serv);
        }
    }

    public static function onManagerStart($serv)
    {
        if ('Darwin' !== PHP_OS) {
            $title = sprintf(
                "%s, running:%s",
                'manager',
                date("Y-m-d H:i:s")
            );
            swoole_set_process_name($title);
        }

        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'managerStart')) {
                self::$eventHandler->managerStart($serv);
            }
        }
    }

    public static function onManagerStop($serv)
    {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'managerStop')) {
                self::$eventHandler->managerStop($serv);
            }
        }
    }

    public static function onWorkerStop($serv, $worker_id)
    {
        if (self::$eventHandler) {
            self::$eventHandler->workerStop($serv, $worker_id);
        }
    }

    public static function onWorkerError($serv, $worker_id, $worker_pid, $exit_code, $signal)
    {
        if (self::$eventHandler) {
            self::$eventHandler->workerError($serv, $worker_id, $worker_pid, $exit_code, $signal);
        }
    }

    public static function onWorkerStart($serv, int $worker_id)
    {
        if (0 == $worker_id) {
            if (function_exists('opcache_reset')) {
                //清除opcache 缓存，swoole模式下其实可以关闭opcache
                \opcache_reset();
            }
        }
        try {
            if ('Darwin' !== PHP_OS) {
                $workerNum = Config::getField('swoole_setting', 'worker_num');
                $isTask = 0;
                if ($worker_id >= $workerNum) {
                    $isTask = 1;
                }
                $title = sprintf(
                    "%s, id:%d, running:%s",
                    $isTask ? 'task' : 'worker',
                    $isTask ? ($worker_id - $workerNum) : $worker_id,
                    date("Y-m-d H:i:s")
                );
                swoole_set_process_name($title);
            }
            Family::$swooleServer = $serv;
            //加载配置，让此处加载的配置可热更新
            Config::loadLazy();
            //日志初始化
            Log::init();
            /**
             * @var $event Helper\EventHandler
             */
            if (class_exists('Event')) {
                $event = new \Event();
                if (!($event instanceof Helper\EventHandler)) {
                    Log::error("event must implements Helper\EventHandler");
                } else {
                    self::$eventHandler = $event;
                    $event->workerStart($serv, $worker_id);
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
    ) {
        if (self::$eventHandler) {
            self::$eventHandler->onRequest($request);
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

        if (self::$eventHandler) {
            $ret = self::$eventHandler->requestAfter($request, $response, $result);
            if ($ret) {
                $result = $ret;
            }
        }

        $response->end($result);
    }

    public static function onMessage(
        \swoole_websocket_server $server,
        \swoole_websocket_frame $frame
    ) {
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
        } catch (\Exception $e) { //程序异常
            $result = BaseException::exceptionHandler($e);
        } catch (\Error $e) { //程序错误，如fatal error
            $result = BaseException::exceptionHandler($e);
        } catch (\Throwable $e) {  //兜底
            $result = BaseException::exceptionHandler($e);
        }

        if (self::$eventHandler) {
            self::$eventHandler->messageAfter($server, $frame, $result);
        }
    }


    public static function onOpen(
        \swoole_websocket_server $server,
        \swoole_http_request $request
    ) {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'open')) {
                self::$eventHandler->open($server, $request);
            }
        }
    }

    public static function onClose(
        \swoole_websocket_server $server,
        int $fd,
        int $reactorId
    ) {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'close')) {
                self::$eventHandler->close($server, $fd, $reactorId);
            }
        }
    }

    public static function onTask(
        \swoole_websocket_server $server,
        \swoole_server_task $task
    ) {
        go(function () use ($server, $task) {
            //初始化根协程ID
            Coroutine::setBaseId();
            //初始化上下文
            $context = new Context(Helper\Protocol::taskToRequest($task));
            $context->set('task', $task);
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
                $result = BaseException::exceptionHandler($e);
            } catch (\Error $e) { //程序错误，如fatal error
                $result = BaseException::exceptionHandler($e);
            } catch (\Throwable $e) {  //兜底
                $result = BaseException::exceptionHandler($e);
            }

            if (self::$eventHandler) {
                if (method_exists(self::$eventHandler, 'taskAfter')) {
                    $ret = self::$eventHandler->taskAfter($server, $result);
                    if ($ret) {
                        $result = $ret;
                    }
                }
            }

            $task->finish($result);
        });
    }

    public function onFinish($server, $task_id, $data)
    {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'finish')) {
                self::$eventHandler->finish($server, $task_id, $data);
            }
        }
    }
}
