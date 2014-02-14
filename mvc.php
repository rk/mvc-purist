<?php

require 'vendor/autoload.php';

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);
define('APP', ROOT . DS . 'application' . DS);

/**
 * Inspired by Laravel's array_get(), but without the dot notation or
 * recursion. In general, we shouldn't need anything that complex.
 *
 * @param  array $array   Anything implementing ArrayAccess, or an Array
 * @param  mixed $key     The key to test for.
 * @param  mixed $default The default value
 * @return mixed
 */
function array_get($array, $key, $default=null) {
    if (isset($array[$key]))
        return $array[$key];

    return $default;
}

function url($url='') {
    return $_SERVER['SCRIPT_NAME'] . '/' . $url;
}

// Register WHOOPS! error handler
$run = new Whoops\Run();
$run->pushHandler(new Whoops\Handler\PrettyPageHandler());
$run->register();

// Initialize our service locator
$container  = new Purist\ServiceLocator();

// Register our Foundational Objects
$container->addInstance(new Purist\Request(array_get($_SERVER, 'PATH_INFO', '/')));

$container->addAliases([
	'router'   => 'Purist\\Router',
	'registry' => 'Purist\\ConfigRegistry',
]);

// Register classes that should only have a singular instance (but aren't actually singletons)
$container->addSingletons([
	'Purist\\Router',
	'Purist\\ConfigRegistry',
	'Purist\\Dispatcher' => ['Purist\\ServiceLocator', 'Purist\\Router', 'Purist\\Request']
]);

$dispatcher = $container->get('Purist\\Dispatcher');
$registry   = $container->get('Purist\\ConfigRegistry');

Purist\Session::configure($registry);
Purist\Session::detect();

// Register routes
$dispatcher->router->alias('/', 'IndexModel', 'IndexView');
$dispatcher->router->dynamic('#/[a-z]{2}#i', 'IndexModel', 'IndexView', 'language');

// Handle request
$dispatcher->dispatch();