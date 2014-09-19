<?php

/**
 * ApiGen 2.8.0 - API documentation generator for PHP 5.3+
 *
 * Copyright (c) 2010-2011 David Grudl (http://davidgrudl.com)
 * Copyright (c) 2011-2012 Jaroslav Hanslík (https://github.com/kukulich)
 * Copyright (c) 2011-2012 Ondřej Nešpor (https://github.com/Andrewsville)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace ApiGen;

use TokenReflection\IReflection;

/**
 * Base reflection envelope.
 *
 * Alters TokenReflection\IReflection functionality for ApiGen.
 */
abstract class ReflectionBase
{
	/**
	 * List of parsed classes.
	 *
	 * @var \ArrayObject
	 */
	protected static $parsedClasses;

	/**
	 * List of parsed constants.
	 *
	 * @var \ArrayObject
	 */
	protected static $parsedConstants;

	/**
	 * List of parsed functions.
	 *
	 * @var \ArrayObject
	 */
	protected static $parsedFunctions;

	/**
	 * Generator.
	 *
	 * @var \ApiGen\Generator
	 */
	protected static $generator = null;

	/**
	 * Config.
	 *
	 * @var \ApiGen\Config
	 */
	protected static $config = null;

	/**
	 * Class methods cache.
	 *
	 * @var array
	 */
	protected static $reflectionMethods = array();

	/**
	 * Reflection type (reflection class).
	 *
	 * @var string
	 */
	protected $reflectionType;

	/**
	 * Inspected class reflection.
	 *
	 * @var \TokenReflection\IReflectionClass
	 */
	protected $reflection;

	/**
	 * Constructor.
	 *
	 * Sets the inspected reflection.
	 *
	 * @param \TokenReflection\IReflection $reflection Inspected reflection
	 * @param \ApiGen\Generator $generator ApiGen generator
	 */
	public function __construct(IReflection $reflection, Generator $generator)
	{
		if (null === self::$generator) {
			self::$generator = $generator;
			self::$config = $generator->getConfig();
			self::$parsedClasses = $generator->getParsedClasses();
			self::$parsedConstants = $generator->getParsedConstants();
			self::$parsedFunctions = $generator->getParsedFunctions();
		}

		$this->reflectionType = get_class($this);
		if (!isset(self::$reflectionMethods[$this->reflectionType])) {
			self::$reflectionMethods[$this->reflectionType] = array_flip(get_class_methods($this));
		}

		$this->reflection = $reflection;
	}

	/**
	 * Retrieves a property or method value.
	 *
	 * First tries the envelope object's property storage, then its methods
	 * and finally the inspected element reflection.
	 *
	 * @param string $name Property name
	 * @return mixed
	 */
	public function __get($name)
	{
		$key = ucfirst($name);
		if (isset(self::$reflectionMethods[$this->reflectionType]['get' . $key])) {
			return $this->{'get' . $key}();
		}

		if (isset(self::$reflectionMethods[$this->reflectionType]['is' . $key])) {
			return $this->{'is' . $key}();
		}

		return $this->reflection->__get($name);
	}

	/**
	 * Checks if the given property exists.
	 *
	 * First tries the envelope object's property storage, then its methods
	 * and finally the inspected element reflection.
	 *
	 * @param mixed $name Property name
	 * @return boolean
	 */
	public function __isset($name)
	{
		$key = ucfirst($name);
		return isset(self::$reflectionMethods[$this->reflectionType]['get' . $key]) || isset(self::$reflectionMethods[$this->reflectionType]['is' . $key]) || $this->reflection->__isset($name);
	}

	/**
	 * Returns the reflection broker used by this reflection object.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker()
	{
		return $this->reflection->getBroker();
	}

	/**
	 * Returns the name (FQN).
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->reflection->getName();
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return $this->reflection->getPrettyName();
	}

	/**
	 * Returns if the reflection object is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return $this->reflection->isInternal();
	}

	/**
	 * Returns if the reflection object is user defined.
	 *
	 * @return boolean
	 */
	public function isUserDefined()
	{
		return $this->reflection->isUserDefined();
	}

	/**
	 * Returns if the current reflection comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized()
	{
		return $this->reflection->isTokenized();
	}

	/**
	 * Returns the file name the reflection object is defined in.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->reflection->getFileName();
	}

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return integer
	 */
	public function getStartLine()
	{
		$startLine = $this->reflection->getStartLine();

		if ($doc = $this->getDocComment()) {
			$startLine -= substr_count($doc, "\n") + 1;
		}

		return $startLine;
	}

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return integer
	 */
	public function getEndLine()
	{
		return $this->reflection->getEndLine();
	}
}
