<?php
/**
 * Project: purist
 * User: robert1
 * Date: 2/13/14
 * Time: 12:12 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Purist;

use SessionHandlerInterface;

class Session implements SessionHandlerInterface {
    private static $instance = null;

    public static function detect() {
        if (isset($_COOKIE[session_name()])) {
            static::start();
        }
    }

	public static function configure(ConfigRegistry $registry) {
		if ($options = $registry->get('session')) {
			foreach ($options as $key => $value) {
				ini_set("session.$key", $value);
			}
		}
	}

    public static function start() {
        $class            = get_called_class();
        static::$instance = new $class();

        /** @noinspection PhpParamsInspection */
        session_set_save_handler(static::$instance, true);
        session_start();
    }

    public static function active() {
        return !is_null(static::$instance);
    }

    public static function get($key, $default = null) {
        if (static::$instance === null) {
            return $default;
        }

        return array_get($_SESSION, $key, true);
    }

    private $save_path;

    public function open($save_path, $name) {
        $this->save_path = $save_path;

        if (!is_dir($this->save_path)) {
            mkdir($this->save_path, 0777);
        }

        return true;
    }

    public function close() {
        return true;
    }

    public function destroy($session_id) {
        $file = "$this->save_path/sess_$session_id";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime) {
        foreach (glob("$this->save_path/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function read($session_id) {
        return (string) @file_get_contents("$this->save_path/sess_$session_id");
    }

    public function write($session_id, $session_data) {
        return file_put_contents("$this->save_path/sess_$session_id", $session_data) === false ? false : true;
    }
}