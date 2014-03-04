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
 * Property reflection envelope.
 *
 * Alters TokenReflection\IReflectionProperty functionality for ApiGen.
 */
class ReflectionProperty extends ReflectionElement
{
	/**
	 * Returns if the property is read-only.
	 *
	 * @return boolean
	 */
	public function isReadOnly()
	{
		return false;
	}

	/**
	 * Returns if the property is write-only.
	 *
	 * @return boolean
	 */
	public function isWriteOnly()
	{
		return false;
	}

	/**
	 * Returns if the property is magic.
	 *
	 * @return boolean
	 */
	public function isMagic()
	{
		return false;
	}

	/**
	 * Returns property type hint.
	 *
	 * @return string
	 */
	public function getTypeHint()
	{
		if ($annotations = $this->getAnnotation('var')) {
			list($types) = preg_split('~\s+|$~', $annotations[0], 2);
			if (!empty($types) && '$' !== $types[0]) {
				return $types;
			}
		}

		try {
			$type = gettype($this->getDefaultValue());
			if ('null' !== strtolower($type)) {
				return $type;
			}
		} catch (\Exception $e) {
			// Nothing
		}

		return 'mixed';
	}

	/**
	 * Returns the property declaring class.
	 *
	 * @return \ApiGen\ReflectionClass|null
	 */
	public function getDeclaringClass()
	{
		$className = $this->reflection->getDeclaringClassName();
		return null === $className ? null : self::$parsedClasses[$className];
	}

	/**
	 * Returns the name of the declaring class.
	 *
	 * @return string
	 */
	public function getDeclaringClassName()
	{
		return $this->reflection->getDeclaringClassName();
	}

	/**
	 * Returns the property default value.
	 *
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return $this->reflection->getDefaultValue();
	}

	/**
	 * Returns the part of the source code defining the property default value.
	 *
	 * @return string
	 */
	public function getDefaultValueDefinition()
	{
		return $this->reflection->getDefaultValueDefinition();
	}

	/**
	 * Returns if the property was created at compile time.
	 *
	 * @return boolean
	 */
	public function isDefault()
	{
		return $this->reflection->isDefault();
	}

	/**
	 * Returns property modifiers.
	 *
	 * @return integer
	 */
	public function getModifiers()
	{
		return $this->reflection->getModifiers();
	}

	/**
	 * Returns if the property is private.
	 *
	 * @return boolean
	 */
	public function isPrivate()
	{
		return $this->reflection->isPrivate();
	}

	/**
	 * Returns if the property is protected.
	 *
	 * @return boolean
	 */
	public function isProtected()
	{
		return $this->reflection->isProtected();
	}

	/**
	 * Returns if the property is public.
	 *
	 * @return boolean
	 */
	public function isPublic()
	{
		return $this->reflection->isPublic();
	}

	/**
	 * Returns if the poperty is static.
	 *
	 * @return boolean
	 */
	public function isStatic()
	{
		return $this->reflection->isStatic();
	}

	/**
	 * Returns the property declaring trait.
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
	 * Returns if the property is valid.
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
