<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/25/14
 * Time: 6:15 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist\IOC;

class Container {

	protected $instances = [];
	protected $classes = [];
	protected $aliases = [];
	protected $lazyCache = [];

	public function __construct() {
		$this->addInstance($this);
	}

	/**
	 * Attempts to resolve the string as a class alias. If it cannot, it returns the original
	 * input value.
	 *
	 * @param $alias string Alias of a class name
	 * @return string
	 */
	public function resolveClassAlias($alias) {
		return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
	}

	/**
	 * Checks if the class or alias has been registered with the Container
	 *
	 * @param $string string Class name or alias.
	 * @return bool
	 */
	public function understands($string) {
		$class = $this->resolveClassAlias($string);

		return isset($this->instances[$class]) || isset($this->classes[$class]);
	}

	public function addAlias($alias, $class) {
		$this->aliases[$alias] = $class;
	}

	public function addAliases(Array $aliases) {
		$this->aliases = array_replace($this->aliases, $aliases);
	}

	public function addInstance($object, $class = null) {
		if (empty($class)) {
			$class = get_class($object);
		}

		$this->instances[$class] = $object;
	}

	public function addInstances(Array $objects) {
		foreach ($objects as $object) {
			$this->addInstance($object);
		}
	}

	public function addSingleton($class, $details = null) {
		$options = [
			'singleton' => true,
		];

		if (is_callable($details)) {
			$options['callback'] = $details;
		} elseif (is_array($details)) {
			$options['dependencies'] = $details;
		}

		$this->add($class, $options);
	}

	public function addSingletons(Array $classes) {
		foreach ($classes as $class => $details) {
			// Could be an indexed array instead, so swap parameters around.
			if (is_int($class)) {
				$class   = $details;
				$details = null;
			}

			$this->addSingleton($class, $details);
		}
	}

	public function addClass($class, Array $dependencies = null) {
		$this->add($class, ['dependencies' => $dependencies]);
	}

	public function addClassLambda($class, callable $lambda) {
		$this->add($class, ['callback' => $lambda]);
	}

	protected function add($class, Array $options = []) {
		$this->classes[$class] = ['class' => $class] + $options + [ 'singleton' => false, 'callback' => null, 'dependencies' => null ];
	}

	public function lazyDependency($className) {
		if (empty($this->lazyCache[$className]))
			$this->lazyCache[$className] = new LazyDependency($className, $this);

		return $this->lazyCache[$className];
	}

	public function get($class, $params = []) {
		// Resolve any aliases
		$class = $this->resolveClassAlias($class);

		if ($this->understands($class)) {
			// Test for singular instances first
			if (isset($this->instances[$class])) {
				return $this->instances[$class];
			}

			$instance = null;

			if (isset($this->classes[$class]['callback']) && is_callable($this->classes[$class]['callback'])) {
				// If a closure, callable object, or array callback were given then use it as a factory.
				$instance = call_user_func($this->classes[$class]['callback'], $this);
			} else {
				$dependencies = isset($this->classes[$class]['dependencies']) ? (array) $this->classes[$class]['dependencies'] : [];
				$args = [];

				foreach ($dependencies as $dependency) {
					if (isset($params[$dependency])) {
						$args[] = $params[$dependency];
					} elseif ($dependency instanceof LazyDependency) {
						$args[] = $dependency();
					} else {
						$args[] = $this->get($dependency);
					}
				}

				$reflector = new \ReflectionClass($class);
				$instance  = $reflector->newInstanceArgs($args);
			}

			// Store a copy of the instance if the definition stated that it's a singleton
			if (isset($this->classes[$class]['singleton']) && $this->classes[$class]['singleton']) {
				$this->addInstance($instance, $class);
			}

			return $instance;
		}

		// If we didn't have a record of how to instantiate the class, run it through
		// a generic factory method.
		return $this->factory($class, $params);
	}

	public function factory($class, $params = []) {
		$reflector = new \ReflectionClass($class);
		$args      = [];

		if ($constructor = $reflector->getConstructor()) {
			foreach ($constructor->getParameters() as $parameter) {
				if (isset($params[$parameter->getName()])) {
					$args[] = $params[$parameter->getName()];
				} elseif ($klass = $parameter->getClass()) {
					$args[] = $this->get($klass->getName());
				} else {
					throw new InjectorException("Don't know how to handle parameter: $parameter");
				}
			}
		}

		return $reflector->newInstanceArgs($args);
	}

}