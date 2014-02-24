<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Http;

use Nette;



/**
 * Session section.
 *
 * @author     David Grudl
 */
final class SessionSection extends Nette\Object implements \IteratorAggregate, \ArrayAccess
{
	/** @var Session */
	private $session;

	/** @var string */
	private $name;

	/** @var array  session data storage */
	private $data;

	/** @var array  session metadata storage */
	private $meta = FALSE;

	/** @var bool */
	public $warnOnUndefined = FALSE;



	/**
	 * Do not call directly. Use Session::getSection().
	 */
	public function __construct(Session $session, $name)
	{
		if (!is_string($name)) {
			throw new Nette\InvalidArgumentException("Session namespace must be a string, " . gettype($name) ." given.");
		}

		$this->session = $session;
		$this->name = $name;
	}



	/**
	 * Do not call directly. Use Session::getNamespace().
	 */
	private function start()
	{
		if ($this->meta === FALSE) {
			$this->session->start();
			$this->data = & $_SESSION['__NF']['DATA'][$this->name];
			$this->meta = & $_SESSION['__NF']['META'][$this->name];
		}
	}



	/**
	 * Returns an iterator over all section variables.
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		$this->start();
		if (isset($this->data)) {
			return new \ArrayIterator($this->data);
		} else {
			return new \ArrayIterator;
		}
	}



	/**
	 * Sets a variable in this session section.
	 * @param  string  name
	 * @param  mixed   value
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->start();
		$this->data[$name] = $value;
		if (is_object($value)) {
			$this->meta[$name]['V'] = Nette\Reflection\ClassType::from($value)->getAnnotation('serializationVersion');
		}
	}



	/**
	 * Gets a variable from this session section.
	 * @param  string    name
	 * @return mixed
	 */
	public function &__get($name)
	{
		$this->start();
		if ($this->warnOnUndefined && !array_key_exists($name, $this->data)) {
			trigger_error("The variable '$name' does not exist in session section", E_USER_NOTICE);
		}

		return $this->data[$name];
	}



	/**
	 * Determines whether a variable in this session section is set.
	 * @param  string    name
	 * @return bool
	 */
	public function __isset($name)
	{
		if ($this->session->exists()) {
			$this->start();
		}
		return isset($this->data[$name]);
	}



	/**
	 * Unsets a variable in this session section.
	 * @param  string    name
	 * @return void
	 */
	public function __unset($name)
	{
		$this->start();
		unset($this->data[$name], $this->meta[$name]);
	}



	/**
	 * Sets a variable in this session section.
	 * @param  string  name
	 * @param  mixed   value
	 * @return void
	 */
	public function offsetSet($name, $value)
	{
		$this->__set($name, $value);
	}



	/**
	 * Gets a variable from this session section.
	 * @param  string    name
	 * @return mixed
	 */
	public function offsetGet($name)
	{
		return $this->__get($name);
	}



	/**
	 * Determines whether a variable in this session section is set.
	 * @param  string    name
	 * @return bool
	 */
	public function offsetExists($name)
	{
		return $this->__isset($name);
	}



	/**
	 * Unsets a variable in this session section.
	 * @param  string    name
	 * @return void
	 */
	public function offsetUnset($name)
	{
		$this->__unset($name);
	}



	/**
	 * Sets the expiration of the section or specific variables.
	 * @param  string|int|DateTime  time, value 0 means "until the browser is closed"
	 * @param  mixed   optional list of variables / single variable to expire
	 * @return SessionSection  provides a fluent interface
	 */
	public function setExpiration($time, $variables = NULL)
	{
		$this->start();
		if (empty($time)) {
			$time = NULL;
			$whenBrowserIsClosed = TRUE;
		} else {
			$time = Nette\DateTime::from($time)->format('U');
			$max = ini_get('session.gc_maxlifetime');
			if ($time - time() > $max + 3) { // bulgarian constant
				trigger_error("The expiration time is greater than the session expiration $max seconds", E_USER_NOTICE);
			}
			$whenBrowserIsClosed = FALSE;
		}

		if ($variables === NULL) { // to entire section
			$this->meta['']['T'] = $time;
			$this->meta['']['B'] = $whenBrowserIsClosed;

		} elseif (is_array($variables)) { // to variables
			foreach ($variables as $variable) {
				$this->meta[$variable]['T'] = $time;
				$this->meta[$variable]['B'] = $whenBrowserIsClosed;
			}

		} else { // to variable
			$this->meta[$variables]['T'] = $time;
			$this->meta[$variables]['B'] = $whenBrowserIsClosed;
		}
		return $this;
	}



	/**
	 * Removes the expiration from the section or specific variables.
	 * @param  mixed   optional list of variables / single variable to expire
	 * @return void
	 */
	public function removeExpiration($variables = NULL)
	{
		$this->start();
		if ($variables === NULL) {
			// from entire section
			unset($this->meta['']['T'], $this->meta['']['B']);

		} elseif (is_array($variables)) {
			// from variables
			foreach ($variables as $variable) {
				unset($this->meta[$variable]['T'], $this->meta[$variable]['B']);
			}
		} else {
			unset($this->meta[$variables]['T'], $this->meta[$variable]['B']);
		}
	}



	/**
	 * Cancels the current session section.
	 * @return void
	 */
	public function remove()
	{
		$this->start();
		$this->data = NULL;
		$this->meta = NULL;
	}

}
