<?php

/**
 * Texy! - human-readable text to HTML converter.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE version 2 or 3
 * @link       http://texy.info
 * @package    Texy
 */



/**
 * TexyObject is the ultimate ancestor of all instantiable classes.
 *
 * TexyObject is copy of Nette\Object from Nette Framework (http://nettephp.com).
 *
 * It defines some handful methods and enhances object core of PHP:
 *   - access to undeclared members throws exceptions
 *   - support for conventional properties with getters and setters
 *   - support for event raising functionality
 *   - ability to add new methods to class (extension methods)
 *
 * Properties is a syntactic sugar which allows access public getter and setter
 * methods as normal object variables. A property is defined by a getter method
 * and optional setter method (no setter method means read-only property).
 * <code>
 * $val = $obj->label;     // equivalent to $val = $obj->getLabel();
 * $obj->label = 'Nette';  // equivalent to $obj->setLabel('Nette');
 * </code>
 * Property names are case-sensitive, and they are written in the camelCaps
 * or PascalCaps.
 *
 * Event functionality is provided by declaration of property named 'on{Something}'
 * Multiple handlers are allowed.
 * <code>
 * public $onClick;                // declaration in class
 * $this->onClick[] = 'callback';  // attaching event handler
 * if (!empty($this->onClick)) ... // are there any handlers?
 * $this->onClick($sender, $arg);  // raises the event with arguments
 * </code>
 *
 * Adding method to class (i.e. to all instances) works similar to JavaScript
 * prototype property. The syntax for adding a new method is:
 * <code>
 * MyClass::extensionMethod('newMethod', function(MyClass $obj, $arg, ...) { ... });
 * $obj = new MyClass;
 * $obj->newMethod($x);
 * </code>
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
abstract class TexyObject
{
	/** @var array (method => array(type => callback)) */
	private static $extMethods;



	/**
	 * Returns the name of the class of this object.
	 *
	 * @return string
	 */
	final public /*static*/ function getClass()
	{
		return /*get_called_class()*/ /**/get_class($this)/**/;
	}



	/**
	 * Access to reflection.
	 *
	 * @return ReflectionObject
	 */
	final public function getReflection()
	{
		return new ReflectionObject($this);
	}



	/**
	 * Call to undefined method.
	 *
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public function __call($name, $args)
	{
		$class = get_class($this);

		if ($name === '') {
			throw new MemberAccessException("Call to class '$class' method without name.");
		}

		// event functionality
		if (preg_match('#^on[A-Z]#', $name)) {
			$rp = new ReflectionProperty($class, $name);
			if ($rp->isPublic() && !$rp->isStatic()) {
				$list = $this->$name;
				if (is_array($list) || $list instanceof Traversable) {
					foreach ($list as $handler) {
						/**/if (is_object($handler)) {
							call_user_func_array(array($handler, '__invoke'), $args);

						} else /**/{
							call_user_func_array($handler, $args);
						}
					}
				}
				return NULL;
			}
		}

		// extension methods
		if ($cb = self::extensionMethod("$class::$name")) {
			array_unshift($args, $this);
			return call_user_func_array($cb, $args);
		}

		throw new MemberAccessException("Call to undefined method $class::$name().");
	}



	/**
	 * Call to undefined static method.
	 *
	 * @param  string  method name (in lower case!)
	 * @param  array   arguments
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public static function __callStatic($name, $args)
	{
		$class = get_called_class();
		throw new MemberAccessException("Call to undefined static method $class::$name().");
	}



	/**
	 * Adding method to class.
	 *
	 * @param  string  method name
	 * @param  mixed   callback or closure
	 * @return mixed
	 */
	public static function extensionMethod($name, $callback = NULL)
	{
		if (self::$extMethods === NULL || $name === NULL) { // for backwards compatibility
			$list = get_defined_functions();
			foreach ($list['user'] as $fce) {
				$pair = explode('_prototype_', $fce);
				if (count($pair) === 2) {
					self::$extMethods[$pair[1]][$pair[0]] = $fce;
					self::$extMethods[$pair[1]][''] = NULL;
				}
			}
			if ($name === NULL) return NULL;
		}

		$name = strtolower($name);
		$a = strrpos($name, ':'); // search ::
		if ($a === FALSE) {
			$class = strtolower(get_called_class());
			$l = & self::$extMethods[$name];
		} else {
			$class = substr($name, 0, $a - 1);
			$l = & self::$extMethods[substr($name, $a + 1)];
		}

		if ($callback !== NULL) { // works as setter
			$l[$class] = $callback;
			$l[''] = NULL;
			return NULL;
		}

		// works as getter
		if (empty($l)) {
			return FALSE;

		} elseif (isset($l[''][$class])) { // cached value
			return $l[''][$class];
		}
		$cl = $class;
		do {
			$cl = strtolower($cl);
			if (isset($l[$cl])) {
				return $l[''][$class] = $l[$cl];
			}
		} while (($cl = get_parent_class($cl)) !== FALSE);

		foreach (class_implements($class) as $cl) {
			$cl = strtolower($cl);
			if (isset($l[$cl])) {
				return $l[''][$class] = $l[$cl];
			}
		}
		return $l[''][$class] = FALSE;
	}



	/**
	 * Returns property value. Do not call directly.
	 *
	 * @param  string  property name
	 * @return mixed   property value
	 * @throws MemberAccessException if the property is not defined.
	 */
	public function &__get($name)
	{
		$class = get_class($this);

		if ($name === '') {
			throw new MemberAccessException("Cannot read a class '$class' property without name.");
		}

		// property getter support
		$name[0] = $name[0] & "\xDF"; // case-sensitive checking, capitalize first character
		$m = 'get' . $name;
		if (self::hasAccessor($class, $m)) {
			// ampersands:
			// - uses &__get() because declaration should be forward compatible (e.g. with Nette\Web\Html)
			// - doesn't call &$this->$m because user could bypass property setter by: $x = & $obj->property; $x = 'new value';
			$val = $this->$m();
			return $val;
		}

		$m = 'is' . $name;
		if (self::hasAccessor($class, $m)) {
			$val = $this->$m();
			return $val;
		}

		$name = func_get_arg(0);
		throw new MemberAccessException("Cannot read an undeclared property $class::\$$name.");
	}



	/**
	 * Sets value of a property. Do not call directly.
	 *
	 * @param  string  property name
	 * @param  mixed   property value
	 * @return void
	 * @throws MemberAccessException if the property is not defined or is read-only
	 */
	public function __set($name, $value)
	{
		$class = get_class($this);

		if ($name === '') {
			throw new MemberAccessException("Cannot assign to a class '$class' property without name.");
		}

		// property setter support
		$name[0] = $name[0] & "\xDF"; // case-sensitive checking, capitalize first character
		if (self::hasAccessor($class, 'get' . $name) || self::hasAccessor($class, 'is' . $name)) {
			$m = 'set' . $name;
			if (self::hasAccessor($class, $m)) {
				$this->$m($value);
				return;

			} else {
				$name = func_get_arg(0);
				throw new MemberAccessException("Cannot assign to a read-only property $class::\$$name.");
			}
		}

		$name = func_get_arg(0);
		throw new MemberAccessException("Cannot assign to an undeclared property $class::\$$name.");
	}



	/**
	 * Is property defined?
	 *
	 * @param  string  property name
	 * @return bool
	 */
	public function __isset($name)
	{
		$name[0] = $name[0] & "\xDF";
		return $name !== '' && self::hasAccessor(get_class($this), 'get' . $name);
	}



	/**
	 * Access to undeclared property.
	 *
	 * @param  string  property name
	 * @return void
	 * @throws MemberAccessException
	 */
	public function __unset($name)
	{
		$class = get_class($this);
		throw new MemberAccessException("Cannot unset the property $class::\$$name.");
	}



	/**
	 * Has property an accessor?
	 *
	 * @param  string  class name
	 * @param  string  method name
	 * @return bool
	 */
	private static function hasAccessor($c, $m)
	{
		static $cache;
		if (!isset($cache[$c])) {
			// get_class_methods returns private, protected and public methods of Object (doesn't matter)
			// and ONLY PUBLIC methods of descendants (perfect!)
			// but returns static methods too (nothing doing...)
			// and is much faster than reflection
			// (works good since 5.0.4)
			$cache[$c] = array_flip(get_class_methods($c));
		}
		return isset($cache[$c][$m]);
	}

}
