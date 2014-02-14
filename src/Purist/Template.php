<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:14 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

use Purist;

class Template {
    use Purist\Attributes;

    protected $template;

    public function __construct($template, $attributes = null) {
        $this->template = $template;

        if (is_array($attributes)) {
            $this->attributes = $attributes;
        }
    }

    public function render() {
        extract($this->attributes);

        ob_start();
        include $this->template;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}