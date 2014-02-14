<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:15 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

class Request {

    private $url;
    private $method;
    private $segments;
    private $segment_count;
    private $ajax;

    public function __construct($url) {
        $this->url           = $url;
        $this->segments      = explode('/', trim($url, '/'));
        $this->segment_count = count($this->segments);
        $this->method        = strtolower(array_get($_SERVER, 'REQUEST_METHOD', 'get'));
        $this->ajax          = strtolower(array_get($_SERVER, 'HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
    }

    public function segment($num, $default = null) {
        return array_get($this->segments, $num, $default);
    }

    public function segmentCount() {
        return $this->segment_count;
    }

    public function url() {
        return $this->url;
    }

    public function method() {
        return $this->method;
    }

    public function ajax() {
        return $this->ajax;
    }
}