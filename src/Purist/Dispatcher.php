<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:15 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

use Exception;

class Dispatcher {

    public $request;
    public $router;
	public $container;

    public function __construct(ServiceLocator $container, Router $router, Request $request) {
	    $this->container = $container;
	    $this->router    = $router;
        $this->request   = $request;
    }

    public function dispatch() {
        if ($handler = $this->router->recognizes($this->request)) {
            /**
             * @var $model      Object
             * @var $controller Controller
             * @var $view       View
             */

            $model      = new $handler['model'];
            $view       = new $handler['view']($this->request, $model);
            $action     = $handler['action'];

            if ($action) {
                $view->controller()->{$action . 'Action'}();
            }

            $view->render();
	        return;
        }

        throw new Exception("Unrecognized route.");
    }
}