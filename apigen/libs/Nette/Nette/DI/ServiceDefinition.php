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
 * Definition used by ContainerBuilder.
 *
 * @author     David Grudl
 */
class ServiceDefinition extends Nette\Object
{
	/** @var string  class or interface name */
	public $class;

	/** @var Statement */
	public $factory;

	/** @var Statement[] */
	public $setup = array();

	/** @var array */
	public $parameters = array();

	/** @var array */
	public $tags = array();

	/** @var mixed */
	public $autowired = TRUE;

	/** @var bool */
	public $shared = TRUE;

	/** @var bool */
	public $internal = FALSE;



	public function setClass($class, array $args = array())
	{
		$this->class = $class;
		if ($args) {
			$this->setFactory($class, $args);
		}
		return $this;
	}



	public function setFactory($factory, array $args = array())
	{
		$this->factory = new Statement($factory, $args);
		return $this;
	}



	public function setArguments(array $args = array())
	{
		if ($this->factory) {
			$this->factory->arguments = $args;
		} else {
			$this->setClass($this->class, $args);
		}
		return $this;
	}



	public function addSetup($target, $args = NULL)
	{
		if (!is_array($args)) {
			$args = func_get_args();
			array_shift($args);
		}
		$this->setup[] = new Statement($target, $args);
		return $this;
	}



	public function setParameters(array $params)
	{
		$this->shared = $this->autowired = FALSE;
		$this->parameters = $params;
		return $this;
	}



	public function addTag($tag, $attrs = TRUE)
	{
		$this->tags[$tag] = $attrs;
		return $this;
	}



	public function setAutowired($on)
	{
		$this->autowired = $on;
		return $this;
	}



	public function setShared($on)
	{
		$this->shared = (bool) $on;
		$this->autowired = $this->shared ? $this->autowired : FALSE;
		return $this;
	}



	public function setInternal($on)
	{
		$this->internal = (bool) $on;
		return $this;
	}

}
