<?php

/**
 * App类
 * @author 刘健 <code.liu@qq.com>
 */

namespace mix\swoole;

class Application extends \mix\base\Application
{

    /**
     * 执行功能 (LNSMP架构)
     */
    public function run($requester, $responder)
    {
        $request = \Mix::$app->request->setRequester($requester);
        $response = \Mix::$app->response->setResponder($responder);
        $method = strtoupper($requester->header['request_method']);
        $action = empty($requester->header['pathinfo']) ? '' : substr($requester->header['pathinfo'], 1);
        $content = $this->runAction($method, $action, $request, $response);
        $response->setContent($content)->send();
    }

    /**
     * 执行功能并返回
     * @param  string $method
     * @param  string $action
     * @param  object $request
     * @param  object $response
     * @return mixed
     */
    public function runAction($method, $action, $request, $response)
    {
        $action = "{$method} {$action}";
        // 路由匹配
        list($action, $urlParams) = \Mix::$app->route->match($action);
        // 执行功能
        if ($action) {
            // 路由参数导入请求类
            $request->setRoute($urlParams);
            // index处理
            if (isset($urlParams['controller']) && strpos($action, ':action') !== false) {
                $action = str_replace(':action', 'index', $action);
            }
            // 实例化控制器
            $action = "{$this->controllerNamespace}\\{$action}";
            $classFull = dirname($action);
            $classPath = dirname($classFull);
            $className = \Mix::$app->route->snakeToCamel(basename($classFull));
            $method = \Mix::$app->route->snakeToCamel(basename($action), true);
            $class = "{$classPath}\\{$className}Controller";
            $method = "action{$method}";
            try {
                $reflect = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                throw new \mix\exception\HttpException("URL不存在", 404);
            }
            $controller = $reflect->newInstanceArgs();
            // 判断方法是否存在
            if (method_exists($controller, $method)) {
                // 执行控制器的方法
                return $controller->$method($request, $response);
            }
        }
        throw new \mix\exception\HttpException("URL不存在", 404);
    }

}
