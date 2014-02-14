<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:14 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

use Purist\Request;

abstract class Controller {

    protected $model;
    protected $request;

    public function __construct(Request $request, $model) {
        $this->request = $request;
        $this->model   = $model;
    }
}