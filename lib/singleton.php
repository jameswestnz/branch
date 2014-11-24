<?php
namespace Branch;

class Singleton {
	public static $instance;

	public static function instance() {
		$name = get_called_class();
		if(!(static::$instance instanceof $name)) {
			$class = new \ReflectionClass($name);
			$instance = $class->newInstanceArgs(func_get_args());
			static::$instance = $instance;
		}
		
		return static::$instance;
	}
}