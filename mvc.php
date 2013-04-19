<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);

spl_autoload_register(function ($class) {
    $filename = ROOT . $class . '.php';

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

/**
 * Makes a given class capable of holding attributes via the magic methods.
 * This is more reusable than implementing it many times over.
 */
trait Attributes {

    /**
     * @var array Holds the attributes assigned to this instance.
     */
    protected $attributes = array();

    /**
     * @param $key string
     * @return mixed
     */
    public function __get($key) {
        return array_get($this->attributes, $key);
    }

    /**
     * @param $key   string
     * @param $value mixed
     */
    public function __set($key, $value) {
        $this->attributes[$key] = $value;
    }

    /**
     * @param $key string
     * @return bool
     */
    public function __isset($key) {
        return isset($this->attributes[$key]);
    }

    /**
     * @param $key string
     */
    public function __unset($key) {
        unset($this->attributes[$key]);
    }

}

abstract class View {
    use Attributes;

    protected $controller;
    protected $request;
    protected $model;

    protected static $controller_class = null;

    public function __construct(Request $request, $model) {
        $this->request = $request;
        $this->model   = $model;

        // Using LSB for manual reuse of controllers, or using the IndexView:IndexController convention by default
        $class = empty(static::$controller_class) ? str_replace('View', 'Controller', get_class($this)) : static::$controller_class;
        $this->controller = new $class($request, $model);
    }

    abstract public function render();

    public function controller() {
        return $this->controller;
    }
}

abstract class Controller {

    protected $model;
    protected $request;

    public function __construct(Request $request, $model) {
        $this->request = $request;
        $this->model = $model;
    }
}

class Request {

    private $url;
    private $method;
    private $segments;
    private $segment_count;
    private $ajax;

    public function __construct($url) {
        $this->url           = $url;
        $this->segments      = explode('/', trim($url, '/'));
        $this->segment_count = count($this->segments);
        $this->method        = strtolower(array_get($_SERVER, 'REQUEST_METHOD', 'get'));
        $this->ajax          = strtolower(array_get($_SERVER, 'HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
    }

    public function segment($num, $default=null) {
        return array_get($this->segments, $num, $default);
    }

    public function segmentCount() {
        return $this->segment_count;
    }

    public function url() {
        return $this->url;
    }

    public function method() {
        return $this->method;
    }

    public function ajax() {
        return $this->ajax;
    }

}

class Router {

    private $aliases = array();
    private $routes  = array();

    public function alias($url, $model, $view, $action = '') {
        $this->aliases[$url] = compact('view', 'model', 'action');
        return $this;
    }

    public function dynamic($pattern, $model, $view, $action = '') {
        $this->routes[$pattern] = compact('view', 'model', 'action');
        return $this;
    }

    private function conventional(Request $request) {
        if ($request->segmentCount() > 1) {
            // snake-case or snake_case to CamelCase for class names
            $first  = strtolower($request->segment(0));
            $first  = ucwords(str_replace(['-', '_'], ' ', $first));

            $view   = $first . 'View';
            $model  = $first . 'Model';
            $action = is_int($request->segment(1)) ? $request->segment(1) : 'index';

            return compact('view', 'model', 'action');
        }

        return null;
    }

    public function recognizes(Request $request) {
        $url = $request->url();

        if (isset($this->aliases[$url])) {
            return $this->aliases[$url];
        }

        foreach ($this->routes as $pattern => $parameters) {
            if (preg_match($pattern, $url)) {
                return $parameters;
            }
        }

        if ($parameters = $this->conventional($request)) {
            return $parameters;
        }

        return null;
    }

}

class Dispatcher {

    public $request;
    public $router;

    public function __construct(Request $request) {
        $this->router  = new Router;
        $this->request = $request;
    }

    public function dispatch() {
        if ($handler = $this->router->recognizes($this->request)) {
            /**
             * @var $model Object
             * @var $controller Controller
             * @var $view View
             */

            $model      = new $handler['model'];
            $view       = new $handler['view']($this->request, $model);
            $controller = $view->controller();
            $action     = $handler['action'];

            if ($action)
                $controller->{$action}();

            $view->render();
            exit;
        }

        throw new Exception("Unrecognized route.");
    }
}

// Build our foundational objects
$request    = new Request(array_get($_SERVER, 'PATH_INFO', '/'));
$dispatcher = new Dispatcher($request);

// Register routes
$dispatcher->router->alias('/', 'IndexModel', 'IndexView');
$dispatcher->router->dynamic('#/[a-z]{2}#i', 'IndexModel', 'IndexView', 'language');

// Handle request
$dispatcher->dispatch();