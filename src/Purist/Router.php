<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:15 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

class Router {

    private $aliases = array();
    private $routes = array();

    public function alias($url, $model, $view, $action = '') {
        $this->aliases[$url] = compact('view', 'model', 'action');

        return $this;
    }

    public function dynamic($pattern, $model, $view, $action = '') {
        $this->routes[$pattern] = compact('view', 'model', 'action');

        return $this;
    }

    private function conventional(Request $request) {
        if ($request->segmentCount() > 1) {
            // snake-case or snake_case to CamelCase for class names
            $first = strtolower($request->segment(0));
            $first = ucwords(str_replace(['-', '_'], ' ', $first));

            $view   = $first . 'View';
            $model  = $first . 'Model';
            $action = is_int($request->segment(1)) ? $request->segment(1) : 'index';

            return compact('view', 'model', 'action');
        }

        return null;
    }

    public function recognizes(Request $request) {
        $url = $request->url();

        if (isset($this->aliases[$url])) {
            return $this->aliases[$url];
        }

        foreach ($this->routes as $pattern => $parameters) {
            if (preg_match($pattern, $url, $matches)) {
                if (count($matches) > 1) {
                    $parameters['parameters'] = array_slice($matches, 1);
                }

                return $parameters;
            }
        }

        if ($parameters = $this->conventional($request)) {
            return $parameters;
        }

        return null;
    }
}