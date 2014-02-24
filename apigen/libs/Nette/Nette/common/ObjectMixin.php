<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette;

use Nette;



/**
 * Nette\Object behaviour mixin.
 *
 * @author     David Grudl
 */
final class ObjectMixin
{
	/** @var array */
	private static $methods;

	/** @var array */
	private static $props;


	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new StaticClassException;
	}



	/**
	 * __call() implementation.
	 * @param  object
	 * @param  string
	 * @param  array
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public static function call($_this, $name, $args)
	{
		$class = get_class($_this);
		$isProp = self::hasProperty($class, $name);

		if ($name === '') {
			throw new MemberAccessException("Call to class '$class' method without name.");

		} elseif ($isProp === 'event') { // calling event handlers
			if (is_array($_this->$name) || $_this->$name instanceof \Traversable) {
				foreach ($_this->$name as $handler) {
					Nette\Callback::create($handler)->invokeArgs($args);
				}
			} elseif ($_this->$name !== NULL) {
				throw new UnexpectedValueException("Property $class::$$name must be array or NULL, " . gettype($_this->$name) ." given.");
			}

		} elseif ($cb = Reflection\ClassType::from($_this)->getExtensionMethod($name)) { // extension methods
			array_unshift($args, $_this);
			return $cb->invokeArgs($args);

		} else {
			throw new MemberAccessException("Call to undefined method $class::$name().");
		}
	}



	/**
	 * __call() implementation for entities.
	 * @param  object
	 * @param  string
	 * @param  array
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public static function callProperty($_this, $name, $args)
	{
		if (strlen($name) > 3) {
			$op = substr($name, 0, 3);
			$prop = strtolower($name[3]) . substr($name, 4);
			if ($op === 'add' && self::hasProperty(get_class($_this), $prop.'s')) {
				$_this->{$prop.'s'}[] = $args[0];
				return $_this;

			} elseif ($op === 'set' && self::hasProperty(get_class($_this), $prop)) {
				$_this->$prop = $args[0];
				return $_this;

			} elseif ($op === 'get' && self::hasProperty(get_class($_this), $prop)) {
				return $_this->$prop;
			}
		}
		return self::call($_this, $name, $args);
	}



	/**
	 * __callStatic() implementation.
	 * @param  string
	 * @param  string
	 * @param  array
	 * @return void
	 * @throws MemberAccessException
	 */
	public static function callStatic($class, $method, $args)
	{
		throw new MemberAccessException("Call to undefined static method $class::$method().");
	}



	/**
	 * __get() implementation.
	 * @param  object
	 * @param  string  property name
	 * @return mixed   property value
	 * @throws MemberAccessException if the property is not defined.
	 */
	public static function & get($_this, $name)
	{
		$class = get_class($_this);
		$uname = ucfirst($name);

		if (!isset(self::$methods[$class])) {
			self::$methods[$class] = array_flip(get_class_methods($class)); // public (static and non-static) methods
		}

		if ($name === '') {
			throw new MemberAccessException("Cannot read a class '$class' property without name.");

		} elseif (isset(self::$methods[$class][$m = 'get' . $uname]) || isset(self::$methods[$class][$m = 'is' . $uname])) { // property getter
			$val = $_this->$m();
			return $val;

		} elseif (isset(self::$methods[$class][$name])) { // public method as closure getter
			$val = Callback::create($_this, $name);
			return $val;

		} else { // strict class
			$type = isset(self::$methods[$class]['set' . $uname]) ? 'a write-only' : 'an undeclared';
			throw new MemberAccessException("Cannot read $type property $class::\$$name.");
		}
	}



	/**
	 * __set() implementation.
	 * @param  object
	 * @param  string  property name
	 * @param  mixed   property value
	 * @return void
	 * @throws MemberAccessException if the property is not defined or is read-only
	 */
	public static function set($_this, $name, $value)
	{
		$class = get_class($_this);
		$uname = ucfirst($name);

		if (!isset(self::$methods[$class])) {
			self::$methods[$class] = array_flip(get_class_methods($class));
		}

		if ($name === '') {
			throw new MemberAccessException("Cannot write to a class '$class' property without name.");

		} elseif (self::hasProperty($class, $name)) { // unsetted property
			$_this->$name = $value;

		} elseif (isset(self::$methods[$class][$m = 'set' . $uname])) { // property setter
			$_this->$m($value);

		} else { // strict class
			$type = isset(self::$methods[$class]['get' . $uname]) || isset(self::$methods[$class]['is' . $uname])
			? 'a read-only' : 'an undeclared';
			throw new MemberAccessException("Cannot write to $type property $class::\$$name.");
		}
	}



	/**
	 * __unset() implementation.
	 * @param  object
	 * @param  string  property name
	 * @return void
	 * @throws MemberAccessException
	 */
	public static function remove($_this, $name)
	{
		$class = get_class($_this);
		if (!self::hasProperty($class, $name)) { // strict class
			throw new MemberAccessException("Cannot unset the property $class::\$$name.");
		}
	}



	/**
	 * __isset() implementation.
	 * @param  object
	 * @param  string  property name
	 * @return bool
	 */
	public static function has($_this, $name)
	{
		$class = get_class($_this);
		$name = ucfirst($name);
		if (!isset(self::$methods[$class])) {
			self::$methods[$class] = array_flip(get_class_methods($class));
		}
		return $name !== '' && (isset(self::$methods[$class]['get' . $name]) || isset(self::$methods[$class]['is' . $name]));
	}



	/**
	 * Checks if the public non-static property exists.
	 * @return mixed
	 */
	private static function hasProperty($class, $name)
	{
		$prop = & self::$props[$class][$name];
		if ($prop === NULL) {
			$prop = FALSE;
			try {
				$rp = new \ReflectionProperty($class, $name);
				if ($name === $rp->getName() && $rp->isPublic() && !$rp->isStatic()) {
					$prop = preg_match('#^on[A-Z]#', $name) ? 'event' : TRUE;
				}
			} catch (\ReflectionException $e) {}
		}
		return $prop;
	}

}
