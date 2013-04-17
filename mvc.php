<?php

define('DS', DIRECTORY_SEPARATOR);

spl_autoload_register(function ($class) {
    $filename = __DIR__ . DS . $class . '.php';

    if (file_exists($filename)) {
        require $filename;
    }
});

/**
 * Inspired by Laravel's array_get(), but without the dot notation or
 * recursion. In general, we shouldn't need anything that complex.
 *
 * @param  array $array   Anything implementing ArrayAccess, or an Array
 * @param  mixed $key     The key to test for.
 * @param  mixed $default The default value
 * @return mixed
 */
function array_get($array, $key, $default=null) {
    if (isset($array[$key]))
        return $array[$key];

    return $default;
}

function url($url='') {
    return $_SERVER['SCRIPT_NAME'] . '/' . $url;
}

abstract class Model {

}

abstract class View {

    protected $controller;
    protected $request;
    protected $model;
    protected $attributes = array();

    public function __construct(Request $request, Controller $controller, Model $model) {
        $this->request    = $request;
        $this->controller = $controller;
        $this->model      = $model;
    }

    public function set($key, $value) {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function get($key, $default=null) {
        return array_get($this->attributes, $key, $default);
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
        $this->method   = strtolower(array_get($_SERVER, 'REQUEST_METHOD', 'get'));
    }

    public function segment($num) {
        return array_get($this->segments, $num);
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
            $this->view       = new $handler['view']($this->request, $this->controller, $this->model);
            $this->action     = $handler['action'];
        } else {
            throw new Exception("Unrecognized route.");
        }

        if ($this->action)
            $this->controller->{$this->action}();

        $this->view->render();
    }
}

// Build our foundational objects
$request    = new Request(array_get($_SERVER, 'PATH_INFO', '/'));
$dispatcher = new Dispatcher($request);

// Register routes
$dispatcher->router->alias('/', 'IndexModel', 'IndexView', 'IndexController');
$dispatcher->router->dynamic('#/[a-z]{2}#i', 'IndexModel', 'IndexView', 'IndexController', 'language');

// Handle request
$dispatcher->dispatch();