<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:13 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

use Purist\Request;

abstract class View {

    protected $controller;
    protected $request;
    protected $model;

    protected static $controller_class = null;

    public function __construct(Request $request, $model) {
        $this->request = $request;
        $this->model   = $model;

        $class            = static::controllerClass();
        $this->controller = new $class($request, $model);
    }

    // Extracted to its own method for more extensibility
    protected function controllerClass() {
        // Using LSB for manual reuse of controllers, or using the IndexView::IndexController convention by default
        return empty(static::$controller_class) ? str_replace('View', 'Controller', get_class($this)) : static::$controller_class;
    }

    abstract public function render();

    public function controller() {
        return $this->controller;
    }
}