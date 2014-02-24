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
 * Method reflection envelope.
 *
 * Alters TokenReflection\IReflectionMethod functionality for ApiGen.
 */
class ReflectionMethod extends ReflectionFunctionBase
{
	/**
	 * Returns if the method is magic.
	 *
	 * @return boolean
	 */
	public function isMagic()
	{
		return false;
	}

	/**
	 * Returns the method declaring class.
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
	 * Returns method modifiers.
	 *
	 * @return integer
	 */
	public function getModifiers()
	{
		return $this->reflection->getModifiers();
	}

	/**
	 * Returns if the method is abstract.
	 *
	 * @return boolean
	 */
	public function isAbstract()
	{
		return $this->reflection->isAbstract();
	}

	/**
	 * Returns if the method is final.
	 *
	 * @return boolean
	 */
	public function isFinal()
	{
		return $this->reflection->isFinal();
	}

	/**
	 * Returns if the method is private.
	 *
	 * @return boolean
	 */
	public function isPrivate()
	{
		return $this->reflection->isPrivate();
	}

	/**
	 * Returns if the method is protected.
	 *
	 * @return boolean
	 */
	public function isProtected()
	{
		return $this->reflection->isProtected();
	}

	/**
	 * Returns if the method is public.
	 *
	 * @return boolean
	 */
	public function isPublic()
	{
		return $this->reflection->isPublic();
	}

	/**
	 * Returns if the method is static.
	 *
	 * @return boolean
	 */
	public function isStatic()
	{
		return $this->reflection->isStatic();
	}

	/**
	 * Returns if the method is a constructor.
	 *
	 * @return boolean
	 */
	public function isConstructor()
	{
		return $this->reflection->isConstructor();
	}

	/**
	 * Returns if the method is a destructor.
	 *
	 * @return boolean
	 */
	public function isDestructor()
	{
		return $this->reflection->isDestructor();
	}

	/**
	 * Returns the method declaring trait.
	 *
	 * @return \ApiGen\ReflectionClass|null
	 */
	public function getDeclaringTrait()
	{
		$traitName = $this->reflection->getDeclaringTraitName();
		return null === $traitName ? null : self::$parsedClasses[$traitName];
	}

	/**
	 * Returns the declaring trait name.
	 *
	 * @return string|null
	 */
	public function getDeclaringTraitName()
	{
		return $this->reflection->getDeclaringTraitName();
	}

	/**
	 * Returns the overridden method.
	 *
	 * @return \ApiGen\ReflectionMethod|null
	 */
	public function getImplementedMethod()
	{
		foreach ($this->getDeclaringClass()->getOwnInterfaces() as $interface) {
			if ($interface->hasMethod($this->getName())) {
				return $interface->getMethod($this->getName());
			}
		}

		return null;
	}

	/**
	 * Returns the overridden method.
	 *
	 * @return \ApiGen\ReflectionMethod|null
	 */
	public function getOverriddenMethod()
	{
		$parent = $this->getDeclaringClass()->getParentClass();
		if (null === $parent) {
			return null;
		}

		foreach ($parent->getMethods() as $method) {
			if ($this->getName() === $method->getName()) {
				if (!$method->isPrivate() && !$method->isAbstract()) {
					return $method;
				} else {
					return null;
				}
			}
		}

		return null;
	}

	/**
	 * Returns the original name when importing from a trait.
	 *
	 * @return string|null
	 */
	public function getOriginalName()
	{
		return $this->reflection->getOriginalName();
	}

	/**
	 * Returns the original modifiers value when importing from a trait.
	 *
	 * @return integer|null
	 */
	public function getOriginalModifiers()
	{
		return $this->reflection->getOriginalModifiers();
	}

	/**
	 * Returns the original method when importing from a trait.
	 *
	 * @return \ApiGen\ReflectionMethod|null
	 */
	public function getOriginal()
	{
		$originalName = $this->reflection->getOriginalName();
		return null === $originalName ? null : self::$parsedClasses[$this->reflection->getOriginal()->getDeclaringClassName()]->getMethod($originalName);
	}

	/**
	 * Returns if the method is valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		if ($class = $this->getDeclaringClass()) {
			return $class->isValid();
		}

		return true;
	}
}
