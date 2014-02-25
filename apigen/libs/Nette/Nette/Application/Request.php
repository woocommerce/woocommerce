<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application;

use Nette;



/**
 * Presenter request. Immutable object.
 *
 * @author     David Grudl
 *
 * @property   string $presenterName
 * @property   array $parameters
 * @property   array $post
 * @property   array $files
 * @property   string $method
 */
final class Request extends Nette\FreezableObject
{
	/** method */
	const FORWARD = 'FORWARD';

	/** flag */
	const SECURED = 'secured';

	/** flag */
	const RESTORED = 'restored';

	/** @var string */
	private $method;

	/** @var array */
	private $flags = array();

	/** @var string */
	private $name;

	/** @var array */
	private $params;

	/** @var array */
	private $post;

	/** @var array */
	private $files;



	/**
	 * @param  string  fully qualified presenter name (module:module:presenter)
	 * @param  string  method
	 * @param  array   variables provided to the presenter usually via URL
	 * @param  array   variables provided to the presenter via POST
	 * @param  array   all uploaded files
	 * @param  array   flags
	 */
	public function __construct($name, $method, array $params, array $post = array(), array $files = array(), array $flags = array())
	{
		$this->name = $name;
		$this->method = $method;
		$this->params = $params;
		$this->post = $post;
		$this->files = $files;
		$this->flags = $flags;
	}



	/**
	 * Sets the presenter name.
	 * @param  string
	 * @return Request  provides a fluent interface
	 */
	public function setPresenterName($name)
	{
		$this->updating();
		$this->name = $name;
		return $this;
	}



	/**
	 * Retrieve the presenter name.
	 * @return string
	 */
	public function getPresenterName()
	{
		return $this->name;
	}



	/**
	 * Sets variables provided to the presenter.
	 * @param  array
	 * @return Request  provides a fluent interface
	 */
	public function setParameters(array $params)
	{
		$this->updating();
		$this->params = $params;
		return $this;
	}



	/**
	 * Returns all variables provided to the presenter (usually via URL).
	 * @return array
	 */
	public function getParameters()
	{
		return $this->params;
	}



	/** @deprecated */
	function setParams(array $params)
	{
		trigger_error(__METHOD__ . '() is deprecated; use setParameters() instead.', E_USER_WARNING);
		return $this->setParameters($params);
	}



	/** @deprecated */
	function getParams()
	{
		trigger_error(__METHOD__ . '() is deprecated; use getParameters() instead.', E_USER_WARNING);
		return $this->getParameters();
	}



	/**
	 * Sets variables provided to the presenter via POST.
	 * @param  array
	 * @return Request  provides a fluent interface
	 */
	public function setPost(array $params)
	{
		$this->updating();
		$this->post = $params;
		return $this;
	}



	/**
	 * Returns all variables provided to the presenter via POST.
	 * @return array
	 */
	public function getPost()
	{
		return $this->post;
	}



	/**
	 * Sets all uploaded files.
	 * @param  array
	 * @return Request  provides a fluent interface
	 */
	public function setFiles(array $files)
	{
		$this->updating();
		$this->files = $files;
		return $this;
	}



	/**
	 * Returns all uploaded files.
	 * @return array
	 */
	public function getFiles()
	{
		return $this->files;
	}



	/**
	 * Sets the method.
	 * @param  string
	 * @return Request  provides a fluent interface
	 */
	public function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}



	/**
	 * Returns the method.
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}



	/**
	 * Checks if the method is the given one.
	 * @param  string
	 * @return bool
	 */
	public function isMethod($method)
	{
		return strcasecmp($this->method, $method) === 0;
	}



	/**
	 * Checks if the method is POST.
	 * @return bool
	 */
	public function isPost()
	{
		return strcasecmp($this->method, 'post') === 0;
	}



	/**
	 * Sets the flag.
	 * @param  string
	 * @param  bool
	 * @return Request  provides a fluent interface
	 */
	public function setFlag($flag, $value = TRUE)
	{
		$this->updating();
		$this->flags[$flag] = (bool) $value;
		return $this;
	}



	/**
	 * Checks the flag.
	 * @param  string
	 * @return bool
	 */
	public function hasFlag($flag)
	{
		return !empty($this->flags[$flag]);
	}

}
