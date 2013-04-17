<?php

define('DS', DIRECTORY_SEPARATOR);

spl_autoload_register(function ($class) {
    $filename = __DIR__ . DS . $class . '.php';

    if (file_exists($filename)) {
        require $filename;
    }
});

abstract class Model {

}

abstract class View {

    protected $controller;
    protected $model;
    protected $attributes = array();

    public function __construct(Controller $controller, Model $model) {
        $this->controller = $controller;
        $this->model      = $model;
    }

    public function set($key, $value) {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function get($key) {
        return $this->attributes[$key];
    }

    abstract public function render();
}

abstract class Controller {

    protected $model;
    protected $request;

    public function __construct(Request $request, Model $model) {
        $this->request = $request;
        $this->model = $model;
    }
}

/**
 * Class Request
 *
 * @property $url      string
 * @property $method   string
 * @property $segments array
 */
class Request {

    public $url;
    public $method;
    public $segments;

    public function __construct($url) {
        $this->url      = $url;
        $this->segments = explode('/', trim($url, '/'));
        $this->method   = strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function segment($num) {
        if (isset($this->segments[$num])) {
            return $this->segments[$num];
        }

        return null;
    }
}

class Router {

    private $aliases = array();
    private $routes  = array();

    public function alias($url, $model, $view, $controller, $action='') {
        $this->aliases[$url] = compact('controller', 'view', 'model', 'action');
        return $this;
    }

    public function dynamic($pattern, $model, $view, $controller, $action='') {
        $this->routes[$pattern] = compact('controller', 'view', 'model', 'action');
        return $this;
    }

    public function recognizes($url){
        if (isset($this->aliases[$url])) {
            return $this->aliases[$url];
        }

        foreach ($this->routes as $pattern => $parameters) {
            if (preg_match($pattern, $url)) {
                return $parameters;
            }
        }

        return null;
    }

}

/**
 * Class Dispatcher
 *
 * @property Controller $controller
 * @property Model $model
 * @property View $view
 */
class Dispatcher {

    public $request;
    public $router;

    private $controller;
    private $model;
    private $view;
    private $action;

    public function __construct(Request $request) {
        $this->router  = new Router;
        $this->request = $request;
    }

    public function dispatch() {
        if ($handler = $this->router->recognizes($this->request->url)) {
            $this->model      = new $handler['model'];
            $this->controller = new $handler['controller']($this->request, $this->model);
            $this->view       = new $handler['view']($this->controller, $this->model);
            $this->action     = $handler['action'];
        } else {
            throw new Exception("Unrecognized route.");
        }

        if ($this->action)
            $this->controller->{$this->action}();

        $this->view->render();
    }
}

function url($url='') {
    return $_SERVER['SCRIPT_NAME'] . '/' . $url;
}

// Build our foundational objects
$request    = new Request($_SERVER['PATH_INFO']);
$dispatcher = new Dispatcher($request);

// Register routes
$dispatcher->router->alias('/', 'IndexModel', 'IndexView', 'IndexController');
$dispatcher->router->dynamic('#/[a-z]{2}#i', 'IndexModel', 'IndexView', 'IndexController', 'language');

// Handle request
$dispatcher->dispatch();