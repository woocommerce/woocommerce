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
 * Constant reflection envelope.
 *
 * Alters TokenReflection\IReflectionConstant functionality for ApiGen.
 */
class ReflectionConstant extends ReflectionElement
{
	/**
	 * Returns the unqualified name (UQN).
	 *
	 * @return string
	 */
	public function getShortName()
	{
		return $this->reflection->getShortName();
	}

	/**
	 * Returns constant type hint.
	 *
	 * @return string
	 */
	public function getTypeHint()
	{
		if ($annotations = $this->getAnnotation('var')) {
			list($types) = preg_split('~\s+|$~', $annotations[0], 2);
			if (!empty($types)) {
				return $types;
			}
		}

		try {
			$type = gettype($this->getValue());
			if ('null' !== strtolower($type)) {
				return $type;
			}
		} catch (\Exception $e) {
			// Nothing
		}

		return 'mixed';
	}

	/**
	 * Returns the constant declaring class.
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
	 * @return string|null
	 */
	public function getDeclaringClassName()
	{
		return $this->reflection->getDeclaringClassName();
	}

	/**
	 * Returns the constant value.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->reflection->getValue();
	}

	/**
	 * Returns the constant value definition.
	 *
	 * @return string
	 */
	public function getValueDefinition()
	{
		return $this->reflection->getValueDefinition();
	}

	/**
	 * Returns if the constant is valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		if ($this->reflection instanceof \TokenReflection\Invalid\ReflectionConstant) {
			return false;
		}

		if ($class = $this->getDeclaringClass()) {
			return $class->isValid();
		}

		return true;
	}

	/**
	 * Returns if the constant should be documented.
	 *
	 * @return boolean
	 */
	public function isDocumented()
	{
		if (null === $this->isDocumented && parent::isDocumented() && null === $this->reflection->getDeclaringClassName()) {
			$fileName = self::$generator->unPharPath($this->reflection->getFilename());
			foreach (self::$config->skipDocPath as $mask) {
				if (fnmatch($mask, $fileName, FNM_NOESCAPE)) {
					$this->isDocumented = false;
					break;
				}
			}
			if (true === $this->isDocumented) {
				foreach (self::$config->skipDocPrefix as $prefix) {
					if (0 === strpos($this->reflection->getName(), $prefix)) {
						$this->isDocumented = false;
						break;
					}
				}
			}
		}

		return $this->isDocumented;
	}
}
