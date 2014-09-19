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
 * PHP callback encapsulation.
 *
 * @author     David Grudl
 * @property-read bool $callable
 * @property-read string|array|\Closure $native
 * @property-read bool $static
 */
final class Callback extends Object
{
	/** @var callable */
	private $cb;



	/**
	 * Factory. Workaround for missing (new Callback)->invoke() in PHP 5.3.
	 * @param  mixed   class, object, callable
	 * @param  string  method
	 * @return Callback
	 */
	public static function create($callback, $m = NULL)
	{
		return new self($callback, $m);
	}



	/**
	 * @param  mixed   class, object, callable
	 * @param  string  method
	 */
	public function __construct($cb, $m = NULL)
	{
		if ($m !== NULL) {
			$cb = array($cb, $m);

		} elseif ($cb instanceof self) { // prevents wrapping itself
			$this->cb = $cb->cb;
			return;
		}

		/*5.2*
		if (PHP_VERSION_ID < 50202 && is_string($cb) && strpos($cb, '::')) {
			$cb = explode('::', $cb, 2);
		} elseif (is_object($cb) && !$cb instanceof Closure) {
			$cb = array($cb, '__invoke');
		}

		// remove class namespace
		if (is_string($this->cb) && $a = strrpos($this->cb, '\\')) {
			$this->cb = substr($this->cb, $a + 1);

		} elseif (is_array($this->cb) && is_string($this->cb[0]) && $a = strrpos($this->cb[0], '\\')) {
			$this->cb[0] = substr($this->cb[0], $a + 1);
		}
		*/
		if (!is_callable($cb, TRUE)) {
			throw new InvalidArgumentException("Invalid callback.");
		}
		$this->cb = $cb;
	}



	/**
	 * Invokes callback. Do not call directly.
	 * @return mixed
	 */
	public function __invoke()
	{
		if (!is_callable($this->cb)) {
			throw new InvalidStateException("Callback '$this' is not callable.");
		}
		$args = func_get_args();
		return call_user_func_array($this->cb, $args);
	}



	/**
	 * Invokes callback.
	 * @return mixed
	 */
	public function invoke()
	{
		if (!is_callable($this->cb)) {
			throw new InvalidStateException("Callback '$this' is not callable.");
		}
		$args = func_get_args();
		return call_user_func_array($this->cb, $args);
	}



	/**
	 * Invokes callback with an array of parameters.
	 * @param  array
	 * @return mixed
	 */
	public function invokeArgs(array $args)
	{
		if (!is_callable($this->cb)) {
			throw new InvalidStateException("Callback '$this' is not callable.");
		}
		return call_user_func_array($this->cb, $args);
	}



	/**
	 * Verifies that callback can be called.
	 * @return bool
	 */
	public function isCallable()
	{
		return is_callable($this->cb);
	}



	/**
	 * Returns PHP callback pseudotype.
	 * @return string|array|\Closure
	 */
	public function getNative()
	{
		return $this->cb;
	}



	/**
	 * Returns callback reflection.
	 * @return Nette\Reflection\GlobalFunction|Nette\Reflection\Method
	 */
	public function toReflection()
	{
		if (is_string($this->cb) && strpos($this->cb, '::')) {
			return new Nette\Reflection\Method($this->cb);
		} elseif (is_array($this->cb)) {
			return new Nette\Reflection\Method($this->cb[0], $this->cb[1]);
		} elseif (is_object($this->cb) && !$this->cb instanceof \Closure) {
			return new Nette\Reflection\Method($this->cb, '__invoke');
		} else {
			return new Nette\Reflection\GlobalFunction($this->cb);
		}
	}



	/**
	 * @return bool
	 */
	public function isStatic()
	{
		return is_array($this->cb) ? is_string($this->cb[0]) : is_string($this->cb);
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		if ($this->cb instanceof \Closure) {
			return '{closure}';
		} elseif (is_string($this->cb) && $this->cb[0] === "\0") {
			return '{lambda}';
		} else {
			is_callable($this->cb, TRUE, $textual);
			return $textual;
		}
	}

}
