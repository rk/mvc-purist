<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 1:00 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Purist;

class ServiceLocatorException extends \Exception {}

class ServiceLocator {

	protected $instances;
    protected $classes;
	protected $aliases;

    public function __construct() {
	    $this->instances = [];
        $this->aliases   = [];
        $this->classes   = [];

	    $this->addInstance($this);
    }

	/**
	 * Attempts to resolve the string as a class alias. If it cannot, it returns the original
	 * input value.
	 *
	 * @param $alias string Alias of a class name
	 * @return string
	 */
	public function resolve($alias) {
		return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
	}

	/**
	 * Checks if the class or alias has been registered with the ServiceLocator
	 *
	 * @param $string string Class name or alias.
	 * @return bool
	 */
	public function understands($string) {
		$class = $this->resolve($string);

		return isset($this->instances[$class]) || isset($this->classes[$class]);
	}

	public function addAlias($alias, $class) {
		$this->aliases[$alias] = $class;
	}

	public function addAliases(Array $aliases) {
		$this->aliases = array_replace($this->aliases, $aliases);
	}

	public function addInstance($object, $class=null) {
		if (empty($class))
			$class = get_class($object);

		$this->instances[$class] = $object;
	}

	public function addInstances(Array $objects) {
		foreach ($objects as $object) {
			$this->addInstance($object);
		}
	}

	public function addSingleton($class, $details=null) {
		$this->add($class, [
			'singleton' => true,
			'dependencies' => is_array($details) ? $details : null,
			'callback' => is_callable($details) ? $details : null,
		]);
	}

	public function addSingletons(Array $classes){
		foreach ($classes as $class => $details) {
			// Could be an indexed array instead, so swap parameters around.
			if (is_int($class)) {
				$class   = $details;
				$details = null;
			}

			$this->addSingleton($class, $details);
		}
	}

	public function addClass($class, Array $dependencies=null) {
		$this->add($class, [ 'dependencies' => $dependencies ]);
	}

	public function addClassLambda($class, callable $lambda) {
		$this->add($class, [ 'callback' => $lambda ]);
	}

	protected function add($class, Array $options=[]) {
		$this->classes[$class] = [ 'class' => $class ] + $options;
	}

	public function get($class, $params=[]) {
		// Resolve any aliases
		$class = $this->resolve($class);

		if ($this->understands($class)) {
			// Test for singular instances first
			if (isset($this->instances[$class]))
				return $this->instances[$class];

			$callback     = array_get($this->classes[$class], 'callback');
			$dependencies = array_get($this->classes[$class], 'dependencies');
			$singleton    = array_get($this->classes[$class], 'singleton', false);
			$instance     = null;
			$args         = [];

			if ($callback && is_callable($callback)) {
				// If a closure or callable object were given, invoke it.
				$instance = call_user_func($callback, $this);
			} else {
				if ($dependencies) {
					foreach ($dependencies as $dependency) {
						$args[] = isset($params[$dependency]) ? $params[$dependency] : $this->get($dependency, $params);
					}
				}

				$reflector = new \ReflectionClass($class);
				$instance  = $reflector->newInstanceArgs($args);
			}

			// Store a copy of the instance if the definition stated that it's a singleton
			if ($singleton === true)
				$this->addInstance($instance, $class);

			return $instance;
		}

		// If we didn't have a record of how to instantiate the class, run it through
		// a generic factory method.
		return $this->factory($class, $params);
	}

	public function factory($class, $params=[]) {
		$reflector = new \ReflectionClass($class);
		$args = [];

		if ($constructor = $reflector->getConstructor()) {
			foreach ($constructor->getParameters() as $parameter) {
				if (isset($params[$parameter->getName()])) {
					$args[] = $params[$parameter->getName()];
				} elseif ($klass = $parameter->getClass()) {
					$args[] = $this->get($klass->getName());
				} else {
					throw new ServiceLocatorException("Don't know how to handle parameter: $parameter");
				}
			}
		}

		return $reflector->newInstanceArgs($args);
	}
}