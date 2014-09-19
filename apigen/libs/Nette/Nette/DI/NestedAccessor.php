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
 * The container accessor.
 *
 * @author     David Grudl
 * @internal
 */
class NestedAccessor extends Nette\Object
{
	/** @var array */
	public $parameters;

	/** @var Container */
	private $container;

	/** @var string */
	private $namespace;



	public function __construct(Container $container, $namespace)
	{
		$this->container = $container;
		$this->namespace = $namespace . '.';
		$this->parameters = & $container->parameters[$namespace];
	}



	/**
	 * @return object
	 */
	public function __call($name, $args)
	{
		if (substr($name, 0, 6) === 'create') {
			return call_user_func_array(array(
				$this->container,
				Container::getMethodName($this->namespace . substr($name, 6), FALSE)
			), $args);
		}
		throw new Nette\NotSupportedException;
	}



	/**
	 * @return object
	 */
	public function &__get($name)
	{
		$service = $this->container->getService($this->namespace . $name);
		return $service;
	}



	/**
	 * @return void
	 */
	public function __set($name, $service)
	{
		throw new Nette\NotSupportedException;
	}



	/**
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->container->hasService($this->namespace . $name);
	}



	/**
	 * @return void
	 */
	public function __unset($name)
	{
		throw new Nette\NotSupportedException;
	}

}
