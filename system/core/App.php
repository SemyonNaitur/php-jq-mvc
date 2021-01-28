<?php

namespace System\Core;

class App
{
    private static App $instance;

    private Loader $loader;
    private Request $request;
    private Router $router;

    private ILogger $logger;

    private Controller $controller;

    private function __construct()
    {
    }

    public static function bootstrap(
        Loader $loader,
        Request $request,
        Router $router
    ) {
        if (isset(self::$instance)) {
            throw new \Exception('App already created');
        }
        $instance = self::$instance = new self();
        $instance->loader = $loader;
        $instance->request = $request;
        $instance->router = $router;
        $instance->logger = $loader->library('Logger');
        return $instance;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public function run()
    {
        try {
            $preload = app_config('preload');

            if ($preload['db']) $this->loader->db();

            ['route' => $route, 'params' => $params] = $this->router->matchUrl($this->request->uri());

            if ($route) {
                [$controller_path, $method] = explode('::', $route['method']);

                $cls = Loader::checkSuffix('controller', $controller_path);
                $cls = app_config('controllers_path') . '/' . $cls;
                $cls = str_replace('/', '\\', $cls);

                $c = new $cls();
                $c->$method($params, $route['data']);
                $this->controller = $c;
            } else {
                $this->errorResponse(404, 'Not Found');
            }
        } catch (\Throwable $e) {
            $this->handleException($e);
            $this->errorResponse(500, 'Internal Server Error');
        }
    }

    protected function getController(): Controller
    {
        return $this->controller;
    }

    protected function handleException(\Throwable $e)
    {
        $this->logger->error($e);
    }

    protected function errorResponse(int $code, string $message)
    {
        http_response_code($code);
        die("<h2>$message</h2>");
    }

    public static function request(): Request
    {
        return self::$instance->getRequest();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public static function loader(): Loader
    {
        return self::$instance->getLoader();
    }

    public function getLoader(): Loader
    {
        return $this->loader;
    }

    public static function logger(): ILogger
    {
        return self::$instance->getLogger();
    }

    public function getLogger(): ILogger
    {
        return $this->logger;
    }
}
