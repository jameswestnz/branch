<?php
namespace Branch;

class Singleton {
	private static $instance;
	
	public $_data = array();
	
	public static function instance()
	{
		$name = get_called_class();
		if(!(self::$instance instanceof $name)) {
			$class = new \ReflectionClass($name);
			self::$instance = $class->newInstanceArgs(func_get_args());
			return self::$instance;
		}
		
		return self::$instance;
	}
	
	public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_data))
        {
            return $this->_data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }
}