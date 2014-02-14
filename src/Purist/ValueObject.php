<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 5:36 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Purist;

/**
 * A value object is an immutable class that contains protected data. The get/isset methods
 * permit access of protected level properties as read-only data.
 */
abstract class ValueObject {
	function __get($name) {
		return $this->$name;
	}

	function __isset($name) {
		return isset($this->$name);
	}

	function __set($name, $value) {
		throw new \Exception("Cannot set the property $name on an immutable ValueObject");
	}

	function __unset($name) {
		throw new \Exception("Cannot unset the property $name on an immutable ValueObject");
	}
}