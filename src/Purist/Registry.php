<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 1:59 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Purist;


interface Registry {

	public function get($key, $default=null);
	public function set($key, $value);

} 