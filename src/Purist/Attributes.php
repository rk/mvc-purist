<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:10 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

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