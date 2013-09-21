<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);
define('APP', ROOT . DS . 'application' . DS);

spl_autoload_register(function ($class) {
    $filename = APP . $class . '.php';

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

class Registry {

    protected static $instance;

    protected $request;
    protected $config = array();
    protected $fields = array();

    /**
     * @return Registry
     */
    public static function getInstance() {
        if (empty(static::$instance))
            static::$instance = new static;

        return static::$instance;
    }

    protected function __construct(){
        if (file_exists(APP . 'config.php')) {
            $this->config = require(APP . 'config.php');
        }
    }

    public function getConfig($key) {
        return array_get($this->config, $key);
    }

    public function setRequest(Request $request) {
        $this->request = $request;
    }

    public function getRequest() {
        return $this->request;
    }

    public function set($key, $value) {
        $this->fields[$key] = $value;
    }

    public function get($key) {
        return array_get($this->fields, $key);
    }
}

class Session implements SessionHandlerInterface {
    private static $instance = null;

    public static function detect() {
        if (isset($_COOKIE[session_name()])) {
            static::start();
        }
    }

    public static function start() {
        $class = get_called_class();
        static::$instance = new $class();

        /** @noinspection PhpParamsInspection */
        session_set_save_handler(static::$instance, true);
        session_start();
    }

    public static function active() {
        return !is_null(static::$instance);
    }

    public static function get($key, $default=null) {
        if (static::$instance === null) {
            return $default;
        }

        return array_get($_SESSION, $key, true);
    }

    public static function configure() {
        if ($options = Registry::getInstance()->getConfig('session')) {
            foreach ($options as $key => $value) {
                ini_set("session.$key", $value);
            }
        }
    }

    private $save_path;

    public function open($save_path, $name) {
        $this->save_path = $save_path;

        if (!is_dir($this->save_path)) {
            mkdir($this->save_path, 0777);
        }

        return true;
    }

    public function close() {
        return true;
    }

    public function destroy($session_id) {
        $file = "$this->save_path/sess_$session_id";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime) {
        foreach (glob("$this->save_path/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function read($session_id) {
        return (string)@file_get_contents("$this->save_path/sess_$session_id");
    }

    public function write($session_id, $session_data) {
        return file_put_contents("$this->save_path/sess_$session_id", $session_data) === false ? false : true;
    }
}

abstract class View {

    protected $controller;
    protected $request;
    protected $model;

    protected static $controller_class = null;

    public function __construct(Request $request, $model) {
        $this->request = $request;
        $this->model   = $model;

        $class = static::controllerClass();
        $this->controller = new $class($request, $model);
    }

    // Extracted to its own method for more extensibility
    protected function controllerClass(){
        // Using LSB for manual reuse of controllers, or using the IndexView::IndexController convention by default
        return empty(static::$controller_class) ? str_replace('View', 'Controller', get_class($this)) : static::$controller_class;
    }

    abstract public function render();

    public function controller() {
        return $this->controller;
    }
}

class Template {
    use Attributes;

    protected $template;

    public function __construct($template, $attributes=null) {
        $this->template = $template;

        if (is_array($attributes))
            $this->attributes = $attributes;
    }

    public function render() {
        extract($this->attributes);

        ob_start();
        include $this->template;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
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

Session::configure();
Session::detect();

// Build our foundational objects
$registry = Registry::getInstance();
$request  = new Request(array_get($_SERVER, 'PATH_INFO', '/'));
$registry->setRequest($request);

$dispatcher = new Dispatcher($request);

// Register routes
$dispatcher->router->alias('/', 'IndexModel', 'IndexView');
$dispatcher->router->dynamic('#/[a-z]{2}#i', 'IndexModel', 'IndexView', 'language');

// Handle request
$dispatcher->dispatch();