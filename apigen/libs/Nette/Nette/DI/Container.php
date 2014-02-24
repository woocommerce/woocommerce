<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\DI;

use Nette;



/**
 * The dependency injection container default implementation.
 *
 * @author     David Grudl
 */
class Container extends Nette\FreezableObject implements IContainer
{
	const TAGS = 'tags';

	/** @var array  user parameters */
	/*private*/public $parameters = array();

	/** @deprecated */
	public $params = array();

	/** @var array */
	public $classes = array();

	/** @var array  storage for shared objects */
	private $registry = array();

	/** @var array  storage for service factories */
	private $factories = array();

	/** @var array */
	public $meta = array();

	/** @var array circular reference detector */
	private $creating;



	public function __construct(array $params = array())
	{
		$this->parameters = $params + $this->parameters;
		$this->params = &$this->parameters;
	}



	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}



	/**
	 * Adds the service or service factory to the container.
	 * @param  string
	 * @param  mixed   object, class name or callable
	 * @param  array   service meta information
	 * @return Container  provides a fluent interface
	 */
	public function addService($name, $service, array $meta = NULL)
	{
		$this->updating();
		if (!is_string($name) || $name === '') {
			throw new Nette\InvalidArgumentException("Service name must be a non-empty string, " . gettype($name) . " given.");
		}

		if (isset($this->registry[$name])) {
			throw new Nette\InvalidStateException("Service '$name' has already been registered.");
		}

		if (is_object($service) && !$service instanceof \Closure && !$service instanceof Nette\Callback) {
			$this->registry[$name] = $service;
			$this->meta[$name] = $meta;
			return $this;

		} elseif (!is_string($service) || strpos($service, ':') !== FALSE/*5.2* || $service[0] === "\0"*/) { // callable
			$service = new Nette\Callback($service);
		}

		$this->factories[$name] = array($service);
		$this->registry[$name] = & $this->factories[$name][1]; // forces cloning using reference
		$this->meta[$name] = $meta;
		return $this;
	}



	/**
	 * Removes the service from the container.
	 * @param  string
	 * @return void
	 */
	public function removeService($name)
	{
		$this->updating();
		unset($this->registry[$name], $this->factories[$name], $this->meta[$name]);
	}



	/**
	 * Gets the service object by name.
	 * @param  string
	 * @return object
	 */
	public function getService($name)
	{
		if (isset($this->registry[$name])) {
			return $this->registry[$name];

		} elseif (isset($this->creating[$name])) {
			throw new Nette\InvalidStateException("Circular reference detected for services: "
				. implode(', ', array_keys($this->creating)) . ".");
		}

		if (isset($this->factories[$name])) {
			list($factory) = $this->factories[$name];
			if (is_string($factory)) {
				if (!class_exists($factory)) {
					throw new Nette\InvalidStateException("Cannot instantiate service, class '$factory' not found.");
				}
				try {
					$this->creating[$name] = TRUE;
					$service = new $factory;
				} catch (\Exception $e) {}

			} elseif (!$factory->isCallable()) {
				throw new Nette\InvalidStateException("Unable to create service '$name', factory '$factory' is not callable.");

			} else {
				$this->creating[$name] = TRUE;
				try {
					$service = $factory/*5.2*->invoke*/($this);
				} catch (\Exception $e) {}
			}

		} elseif (method_exists($this, $factory = Container::getMethodName($name)) && $this->getReflection()->getMethod($factory)->getName() === $factory) {
			$this->creating[$name] = TRUE;
			try {
				$service = $this->$factory();
			} catch (\Exception $e) {}

		} else {
			throw new MissingServiceException("Service '$name' not found.");
		}

		unset($this->creating[$name]);

		if (isset($e)) {
			throw $e;

		} elseif (!is_object($service)) {
			throw new Nette\UnexpectedValueException("Unable to create service '$name', value returned by factory '$factory' is not object.");
		}

		return $this->registry[$name] = $service;
	}



	/**
	 * Does the service exist?
	 * @param  string service name
	 * @return bool
	 */
	public function hasService($name)
	{
		return isset($this->registry[$name])
			|| isset($this->factories[$name])
			|| method_exists($this, $method = Container::getMethodName($name)) && $this->getReflection()->getMethod($method)->getName() === $method;
	}



	/**
	 * Is the service created?
	 * @param  string service name
	 * @return bool
	 */
	public function isCreated($name)
	{
		if (!$this->hasService($name)) {
			throw new MissingServiceException("Service '$name' not found.");
		}
		return isset($this->registry[$name]);
	}



	/**
	 * Resolves service by type.
	 * @param  string  class or interface
	 * @param  bool    throw exception if service doesn't exist?
	 * @return object  service or NULL
	 * @throws MissingServiceException
	 */
	public function getByType($class, $need = TRUE)
	{
		$lower = ltrim(strtolower($class), '\\');
		if (!isset($this->classes[$lower])) {
			if ($need) {
				throw new MissingServiceException("Service of type $class not found.");
			}
		} elseif ($this->classes[$lower] === FALSE) {
			throw new MissingServiceException("Multiple services of type $class found.");
		} else {
			return $this->getService($this->classes[$lower]);
		}
	}



	/**
	 * Gets the service names of the specified tag.
	 * @param  string
	 * @return array of [service name => tag attributes]
	 */
	public function findByTag($tag)
	{
		$found = array();
		foreach ($this->meta as $name => $meta) {
			if (isset($meta[self::TAGS][$tag])) {
				$found[$name] = $meta[self::TAGS][$tag];
			}
		}
		return $found;
	}



	/********************* autowiring ****************d*g**/



	/**
	 * Creates new instance using autowiring.
	 * @param  string  class
	 * @param  array   arguments
	 * @return object
	 * @throws Nette\InvalidArgumentException
	 */
	public function createInstance($class, array $args = array())
	{
		$rc = Nette\Reflection\ClassType::from($class);
		if (!$rc->isInstantiable()) {
			throw new ServiceCreationException("Class $class is not instantiable.");

		} elseif ($constructor = $rc->getConstructor()) {
			return $rc->newInstanceArgs(Helpers::autowireArguments($constructor, $args, $this));

		} elseif ($args) {
			throw new ServiceCreationException("Unable to pass arguments, class $class has no constructor.");
		}
		return new $class;
	}



	/**
	 * Calls method using autowiring.
	 * @param  mixed   class, object, function, callable
	 * @param  array   arguments
	 * @return mixed
	 */
	public function callMethod($function, array $args = array())
	{
		$callback = new Nette\Callback($function);
		return $callback->invokeArgs(Helpers::autowireArguments($callback->toReflection(), $args, $this));
	}



	/********************* shortcuts ****************d*g**/



	/**
	 * Expands %placeholders%.
	 * @param  mixed
	 * @return mixed
	 */
	public function expand($s)
	{
		return Helpers::expand($s, $this->parameters);
	}



	/**
	 * Gets the service object, shortcut for getService().
	 * @param  string
	 * @return object
	 */
	public function &__get($name)
	{
		if (!isset($this->registry[$name])) {
			$this->getService($name);
		}
		return $this->registry[$name];
	}



	/**
	 * Adds the service object.
	 * @param  string
	 * @param  object
	 * @return void
	 */
	public function __set($name, $service)
	{
		$this->updating();
		if (!is_string($name) || $name === '') {
			throw new Nette\InvalidArgumentException("Service name must be a non-empty string, " . gettype($name) . " given.");

		} elseif (isset($this->registry[$name])) {
			throw new Nette\InvalidStateException("Service '$name' has already been registered.");

		} elseif (!is_object($service)) {
			throw new Nette\InvalidArgumentException("Service must be a object, " . gettype($service) . " given.");
		}
		$this->registry[$name] = $service;
	}



	/**
	 * Does the service exist?
	 * @param  string
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->hasService($name);
	}



	/**
	 * Removes the service, shortcut for removeService().
	 * @return void
	 */
	public function __unset($name)
	{
		$this->removeService($name);
	}



	public static function getMethodName($name, $isService = TRUE)
	{
		$uname = ucfirst($name);
		return ($isService ? 'createService' : 'create') . ($name === $uname ? '__' : '') . str_replace('.', '__', $uname);
	}

}
