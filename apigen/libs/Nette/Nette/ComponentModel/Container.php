<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\ComponentModel;

use Nette;



/**
 * ComponentContainer is default implementation of IContainer.
 *
 * @author     David Grudl
 *
 * @property-read \ArrayIterator $components
 */
class Container extends Component implements IContainer
{
	/** @var IComponent[] */
	private $components = array();

	/** @var IComponent|NULL */
	private $cloning;



	/********************* interface IContainer ****************d*g**/



	/**
	 * Adds the specified component to the IContainer.
	 * @param  IComponent
	 * @param  string
	 * @param  string
	 * @return Container  provides a fluent interface
	 * @throws Nette\InvalidStateException
	 */
	public function addComponent(IComponent $component, $name, $insertBefore = NULL)
	{
		if ($name === NULL) {
			$name = $component->getName();
		}

		if (is_int($name)) {
			$name = (string) $name;

		} elseif (!is_string($name)) {
			throw new Nette\InvalidArgumentException("Component name must be integer or string, " . gettype($name) . " given.");

		} elseif (!preg_match('#^[a-zA-Z0-9_]+$#', $name)) {
			throw new Nette\InvalidArgumentException("Component name must be non-empty alphanumeric string, '$name' given.");
		}

		if (isset($this->components[$name])) {
			throw new Nette\InvalidStateException("Component with name '$name' already exists.");
		}

		// check circular reference
		$obj = $this;
		do {
			if ($obj === $component) {
				throw new Nette\InvalidStateException("Circular reference detected while adding component '$name'.");
			}
			$obj = $obj->getParent();
		} while ($obj !== NULL);

		// user checking
		$this->validateChildComponent($component);

		try {
			if (isset($this->components[$insertBefore])) {
				$tmp = array();
				foreach ($this->components as $k => $v) {
					if ($k === $insertBefore) {
						$tmp[$name] = $component;
					}
					$tmp[$k] = $v;
				}
				$this->components = $tmp;
			} else {
				$this->components[$name] = $component;
			}
			$component->setParent($this, $name);

		} catch (\Exception $e) {
			unset($this->components[$name]); // undo
			throw $e;
		}
		return $this;
	}



	/**
	 * Removes a component from the IContainer.
	 * @param  IComponent
	 * @return void
	 */
	public function removeComponent(IComponent $component)
	{
		$name = $component->getName();
		if (!isset($this->components[$name]) || $this->components[$name] !== $component) {
			throw new Nette\InvalidArgumentException("Component named '$name' is not located in this container.");
		}

		unset($this->components[$name]);
		$component->setParent(NULL);
	}



	/**
	 * Returns component specified by name or path.
	 * @param  string
	 * @param  bool   throw exception if component doesn't exist?
	 * @return IComponent|NULL
	 */
	final public function getComponent($name, $need = TRUE)
	{
		if (is_int($name)) {
			$name = (string) $name;

		} elseif (!is_string($name)) {
			throw new Nette\InvalidArgumentException("Component name must be integer or string, " . gettype($name) . " given.");

		} else {
			$a = strpos($name, self::NAME_SEPARATOR);
			if ($a !== FALSE) {
				$ext = (string) substr($name, $a + 1);
				$name = substr($name, 0, $a);
			}

			if ($name === '') {
				throw new Nette\InvalidArgumentException("Component or subcomponent name must not be empty string.");
			}
		}

		if (!isset($this->components[$name])) {
			$component = $this->createComponent($name);
			if ($component instanceof IComponent && $component->getParent() === NULL) {
				$this->addComponent($component, $name);
			}
		}

		if (isset($this->components[$name])) {
			if (!isset($ext)) {
				return $this->components[$name];

			} elseif ($this->components[$name] instanceof IContainer) {
				return $this->components[$name]->getComponent($ext, $need);

			} elseif ($need) {
				throw new Nette\InvalidArgumentException("Component with name '$name' is not container and cannot have '$ext' component.");
			}

		} elseif ($need) {
			throw new Nette\InvalidArgumentException("Component with name '$name' does not exist.");
		}
	}



	/**
	 * Component factory. Delegates the creation of components to a createComponent<Name> method.
	 * @param  string      component name
	 * @return IComponent  the created component (optionally)
	 */
	protected function createComponent($name)
	{
		$ucname = ucfirst($name);
		$method = 'createComponent' . $ucname;
		if ($ucname !== $name && method_exists($this, $method) && $this->getReflection()->getMethod($method)->getName() === $method) {
			$component = $this->$method($name);
			if (!$component instanceof IComponent && !isset($this->components[$name])) {
				$class = get_class($this);
				throw new Nette\UnexpectedValueException("Method $class::$method() did not return or create the desired component.");
			}
			return $component;
		}
	}



	/**
	 * Iterates over a components.
	 * @param  bool    recursive?
	 * @param  string  class types filter
	 * @return \ArrayIterator
	 */
	final public function getComponents($deep = FALSE, $filterType = NULL)
	{
		$iterator = new RecursiveComponentIterator($this->components);
		if ($deep) {
			$deep = $deep > 0 ? \RecursiveIteratorIterator::SELF_FIRST : \RecursiveIteratorIterator::CHILD_FIRST;
			$iterator = new \RecursiveIteratorIterator($iterator, $deep);
		}
		if ($filterType) {
			$iterator = new Nette\Iterators\InstanceFilter($iterator, $filterType);
		}
		return $iterator;
	}



	/**
	 * Descendant can override this method to disallow insert a child by throwing an Nette\InvalidStateException.
	 * @param  IComponent
	 * @return void
	 * @throws Nette\InvalidStateException
	 */
	protected function validateChildComponent(IComponent $child)
	{
	}



	/********************* cloneable, serializable ****************d*g**/



	/**
	 * Object cloning.
	 */
	public function __clone()
	{
		if ($this->components) {
			$oldMyself = reset($this->components)->getParent();
			$oldMyself->cloning = $this;
			foreach ($this->components as $name => $component) {
				$this->components[$name] = clone $component;
			}
			$oldMyself->cloning = NULL;
		}
		parent::__clone();
	}



	/**
	 * Is container cloning now?
	 * @return NULL|IComponent
	 * @internal
	 */
	public function _isCloning()
	{
		return $this->cloning;
	}

}
