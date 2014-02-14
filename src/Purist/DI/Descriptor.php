<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 8:37 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Purist\DI;

use ReflectionClass;

/**
 * Describes an entry in the ServiceLocator collection
 * @package Purist\DI
 */
class Descriptor {

	protected $class;
	protected $signature;
	protected $reflector;

	public function __construct($class, Array $signature=null) {
		$this->class     = $class;
		$this->signature = $signature;
	}

	/**
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * @return ReflectionClass
	 */
	public function getReflector() {
		if (empty($this->reflector))
			$this->reflector = new ReflectionClass($this->class);

		return $this->reflector;
	}

	/**
	 * @return array
	 */
	public function getSignature() {
		if ($this->signature === null) {
			$this->learnSignature();
		}

		return $this->signature;
	}

	protected function learnSignature() {
		$reflector = $this->getReflector()->getConstructor();
		$this->signature = [];

		if ($reflector) {
			foreach ($reflector->getParameters() as $param) {
				$class = $param->getClass();
				$this->signature[] = $class ? $class->getName() : $param->getName();
			}
		}
	}

	public function __sleep() {
		return ['class', 'signature'];
	}

	public static function __set_state($properties) {
		return new static($properties['class'], $properties['signature']);
	}
}