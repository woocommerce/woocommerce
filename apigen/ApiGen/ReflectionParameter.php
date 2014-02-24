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

/**
 * Parameter reflection envelope.
 *
 * Alters TokenReflection\IReflectionParameter functionality for ApiGen.
 */
class ReflectionParameter extends ReflectionBase
{
	/**
	 * Returns parameter type hint.
	 *
	 * @return string
	 */
	public function getTypeHint()
	{
		if ($this->isArray()) {
			return 'array';
		} elseif ($this->isCallable()) {
			return 'callable';
		} elseif ($className = $this->getClassName()) {
			return $className;
		} elseif ($annotations = $this->getDeclaringFunction()->getAnnotation('param')) {
			if (!empty($annotations[$this->getPosition()])) {
				list($types) = preg_split('~\s+|$~', $annotations[$this->getPosition()], 2);
				if (!empty($types) && '$' !== $types[0]) {
					return $types;
				}
			}
		}

		return 'mixed';
	}

	/**
	 * Returns the part of the source code defining the parameter default value.
	 *
	 * @return string
	 */
	public function getDefaultValueDefinition()
	{
		return $this->reflection->getDefaultValueDefinition();
	}

	/**
	 * Retutns if a default value for the parameter is available.
	 *
	 * @return boolean
	 */
	public function isDefaultValueAvailable()
	{
		return $this->reflection->isDefaultValueAvailable();
	}

	/**
	 * Returns the position within all parameters.
	 *
	 * @return integer
	 */
	public function getPosition()
	{
		return $this->reflection->position;
	}

	/**
	 * Returns if the parameter expects an array.
	 *
	 * @return boolean
	 */
	public function isArray()
	{
		return $this->reflection->isArray();
	}

	/**
	 * Returns if the parameter expects a callback.
	 *
	 * @return boolean
	 */
	public function isCallable()
	{
		return $this->reflection->isCallable();
	}

	/**
	 * Returns reflection of the required class of the parameter.
	 *
	 * @return \ApiGen\ReflectionClass|null
	 */
	public function getClass()
	{
		$className = $this->reflection->getClassName();
		return null === $className ? null : self::$parsedClasses[$className];
	}

	/**
	 * Returns the required class name of the value.
	 *
	 * @return string|null
	 */
	public function getClassName()
	{
		return $this->reflection->getClassName();
	}

	/**
	 * Returns if the the parameter allows NULL.
	 *
	 * @return boolean
	 */
	public function allowsNull()
	{
		return $this->reflection->allowsNull();
	}

	/**
	 * Returns if the parameter is optional.
	 *
	 * @return boolean
	 */
	public function isOptional()
	{
		return $this->reflection->isOptional();
	}

	/**
	 * Returns if the parameter value is passed by reference.
	 *
	 * @return boolean
	 */
	public function isPassedByReference()
	{
		return $this->reflection->isPassedByReference();
	}

	/**
	 * Returns if the paramter value can be passed by value.
	 *
	 * @return boolean
	 */
	public function canBePassedByValue()
	{
		return $this->reflection->canBePassedByValue();
	}

	/**
	 * Returns the declaring function.
	 *
	 * @return \ApiGen\ReflectionFunctionBase
	 */
	public function getDeclaringFunction()
	{
		$functionName = $this->reflection->getDeclaringFunctionName();

		if ($className = $this->reflection->getDeclaringClassName()) {
			return self::$parsedClasses[$className]->getMethod($functionName);
		} else {
			return self::$parsedFunctions[$functionName];
		}
	}

	/**
	 * Returns the declaring function name.
	 *
	 * @return string
	 */
	public function getDeclaringFunctionName()
	{
		return $this->reflection->getDeclaringFunctionName();
	}

	/**
	 * Returns the function/method declaring class.
	 *
	 * @return \ApiGen\ReflectionClass|null
	 */
	public function getDeclaringClass()
	{
		$className = $this->reflection->getDeclaringClassName();
		return null === $className ? null : self::$parsedClasses[$className];
	}

	/**
	 * Returns the declaring class name.
	 *
	 * @return string|null
	 */
	public function getDeclaringClassName()
	{
		return $this->reflection->getDeclaringClassName();
	}

	/**
	 * If the parameter can be used unlimited.
	 *
	 * @return boolean
	 */
	public function isUnlimited()
	{
		return false;
	}
}
