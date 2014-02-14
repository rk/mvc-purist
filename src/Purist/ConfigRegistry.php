<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:11 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

class ConfigRegistry implements Registry {

    protected $config = array();

    public function __construct() {
        if (file_exists(ROOT . 'config.php')) {
            $this->config = require(ROOT . 'config.php');
        }
    }

    public function set($key, $value) {
        $this->config[$key] = $value;
    }

    public function get($key, $default=null) {
        return array_get($this->config, $key, $default);
    }
}