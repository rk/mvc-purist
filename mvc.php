<?php

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

    public function __construct(Model $model) {
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
        $this->method   = 'get';

        if (!empty($_POST)) {
            $method = isset($_POST['_method']) ? $_POST['_method'] : 'post';

            if (in_array($method, ['post', 'put', 'patch', 'delete'], true)) {
                $this->method = $method;
            }
        }
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

    public function alias($url, $controller, $view, $model) {
        $this->aliases[$url] = compact('controller', 'view', 'model');
        return $this;
    }

    public function dynamic($pattern, $controller, $view, $model){
        $this->routes[$pattern] = compact('controller', 'view', 'model');
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

    public function __construct(Request $request) {
        $this->router  = new Router;
        $this->request = $request;
    }

    public function dispatch() {
        if ($handler = $this->router->recognizes($this->request->url)) {
            $this->model      = new $handler['model'];
            $this->controller = new $handler['controller']($this->model);
            $this->view       = new $handler['view']($this->controller, $this->model);
        } else {
            throw new Exception("Unrecognized route.");
        }

        if (isset($_GET['action']) && ($action = $_GET['action']))
            $this->controller->{$action}();

        $this->view->render();
    }
}

require "IndexModel.php";
require "IndexView.php";
require "IndexController.php";

$request    = new Request($_SERVER['PATH_INFO']);
$dispatcher = new Dispatcher($request);
$dispatcher->router->alias('/', 'IndexController', 'IndexView', 'IndexModel');
$dispatcher->dispatch();