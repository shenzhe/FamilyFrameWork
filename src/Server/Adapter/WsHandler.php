<?php

namespace Family\Server\Adapter;

use Family\Core\Config;
use Family\Core\Log;
use Family\Core\Route;
use Family\Coroutine\Coroutine;
use Family\Exceptions\BaseException;
use Family\Family;
use Family\Helper;
use Swoole;

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
                "%s:master, ws://%s:%d, running:%s",
                Config::get('app_name', ''),
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
                "%s:manager, running:%s",
                Config::get('app_name', ''),
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

    public static function onWorkerExit($serv, $worker_id)
    {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'workerExit')) {
                self::$eventHandler->workerExit($serv, $worker_id);
            }
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
            \set_error_handler(Config::get('error_handler', BaseException::class . '::errorHandler'));
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
        Swoole\Http\Request $request,
        Swoole\Http\Response $response
    ) {
        if (self::$eventHandler) {
            self::$eventHandler->onRequest($request);
        }

        if (empty($request->server['request_method'])) {
            Log::error('unknow request' . var_export($request, true));
            $response->end();
            return;
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
        $context = Swoole\Coroutine::getContext();
        $context->request = $request;
        $context->response = $response;
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
        Swoole\WebSocket\Server $server,
        Swoole\WebSocket\Frame $frame
    ) {
        $frame->request_time_float = microtime(true);
        //初始化根协程ID
        Coroutine::setBaseId();
        //初始化上下文
        Swoole\Coroutine::getContext()->request = Helper\Protocol::frameToRequest($frame);

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
        Swoole\WebSocket\Server $server,
        Swoole\Http\Request $request
    ) {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'open')) {
                self::$eventHandler->open($server, $request);
            }
        }
    }

    public static function onClose(
        Swoole\WebSocket\Server $server,
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
        Swoole\WebSocket\Server $server,
        Swoole\Server\Task $task
    ) {
        go(function () use ($server, $task) {
            //初始化根协程ID
            Coroutine::setBaseId();
            //初始化上下文
            Swoole\Coroutine::getContext()->request = Helper\Protocol::taskToRequest($task);

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

    public static function onFinish($server, $task_id, $data)
    {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'finish')) {
                self::$eventHandler->finish($server, $task_id, $data);
            }
        }
    }

    public static function onPipeMessage($server, $src_worker_id, $data)
    {
        if (self::$eventHandler) {
            if (method_exists(self::$eventHandler, 'pipeMessage')) {
                self::$eventHandler->pipeMessage($server, $src_worker_id, $data);
            }
        }
    }
}
