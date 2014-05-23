<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/25/14
 * Time: 6:18 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Purist\IOC;


class LazyDependency {

	protected $className;
	protected $container;

	public function __construct($className, Container $container) {
		$this->className = $className;
		$this->container = $container;
	}

	public function resolve(Array $params=[]) {
		return $this->container->get($this->className, $params);
	}

	public function __invoke() {
		return $this->resolve(func_get_args());
	}

}