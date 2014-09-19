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
 * Element reflection envelope.
 *
 * Alters TokenReflection\IReflection functionality for ApiGen.
 */
abstract class ReflectionElement extends ReflectionBase
{
	/**
	 * Cache for information if the element should be documented.
	 *
	 * @var boolean
	 */
	protected $isDocumented;

	/**
	 * Reflection elements annotations.
	 *
	 * @var array
	 */
	protected $annotations;

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \ApiGen\ReflectionExtension|null
	 */
	public function getExtension()
	{
		$extension = $this->reflection->getExtension();
		return null === $extension ? null : new ReflectionExtension($extension, self::$generator);
	}

	/**
	 * Returns the PHP extension name.
	 *
	 * @return boolean
	 */
	public function getExtensionName()
	{
		return $this->reflection->getExtensionName();
	}

	/**
	 * Returns the start position in the file token stream.
	 *
	 * @return integer
	 */
	public function getStartPosition()
	{
		return $this->reflection->getStartPosition();
	}

	/**
	 * Returns the end position in the file token stream.
	 *
	 * @return integer
	 */
	public function getEndPosition()
	{
		return $this->reflection->getEndPosition();
	}

	/**
	 * Returns if the element belongs to main project.
	 *
	 * @return boolean
	 */
	public function isMain()
	{
		return empty(self::$config->main) || 0 === strpos($this->getName(), self::$config->main);
	}

	/**
	 * Returns if the element should be documented.
	 *
	 * @return boolean
	 */
	public function isDocumented()
	{
		if (null === $this->isDocumented) {
			$this->isDocumented = $this->reflection->isTokenized() || $this->reflection->isInternal();

			if ($this->isDocumented) {
				if (!self::$config->php && $this->reflection->isInternal()) {
					$this->isDocumented = false;
				} elseif (!self::$config->deprecated && $this->reflection->isDeprecated()) {
					$this->isDocumented = false;
				} elseif (!self::$config->internal && ($internal = $this->reflection->getAnnotation('internal')) && empty($internal[0])) {
					$this->isDocumented = false;
				} elseif (count($this->reflection->getAnnotation('ignore')) > 0) {
					$this->isDocumented = false;
				}
			}
		}

		return $this->isDocumented;
	}

	/**
	 * Returns if the element is deprecated.
	 *
	 * @return boolean
	 */
	public function isDeprecated()
	{
		if ($this->reflection->isDeprecated()) {
			return true;
		}

		if (($this instanceof ReflectionMethod || $this instanceof ReflectionProperty || $this instanceof ReflectionConstant)
			&& $class = $this->getDeclaringClass()
		) {
			return $class->isDeprecated();
		}

		return false;
	}

	/**
	 * Returns if the element is in package.
	 *
	 * @return boolean
	 */
	public function inPackage()
	{
		return '' !== $this->getPackageName();
	}

	/**
	 * Returns element package name (including subpackage name).
	 *
	 * @return string
	 */
	public function getPackageName()
	{
		static $packages = array();

		if ($package = $this->getAnnotation('package')) {
			$packageName = preg_replace('~\s+.*~s', '', $package[0]);
			if (empty($packageName)) {
				return '';
			}

			if ($subpackage = $this->getAnnotation('subpackage')) {
				$subpackageName = preg_replace('~\s+.*~s', '', $subpackage[0]);
				if (empty($subpackageName)) {
					// Do nothing
				} elseif (0 === strpos($subpackageName, $packageName)) {
					$packageName = $subpackageName;
				} else {
					$packageName .= '\\' . $subpackageName;
				}
			}
			$packageName = strtr($packageName, '._/', '\\\\\\');

			$lowerPackageName = strtolower($packageName);
			if (!isset($packages[$lowerPackageName])) {
				$packages[$lowerPackageName] = $packageName;
			}

			return $packages[$lowerPackageName];
		}

		return '';
	}

	/**
	 * Returns element package name (including subpackage name).
	 *
	 * For internal elements returns "PHP", for elements in global space returns "None".
	 *
	 * @return string
	 */
	public function getPseudoPackageName()
	{
		if ($this->isInternal()) {
			return 'PHP';
		}

		return $this->getPackageName() ?: 'None';
	}

	/**
	 * Returns if the element is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace()
	{
		return '' !== $this->getNamespaceName();
	}

	/**
	 * Returns element namespace name.
	 *
	 * @return string
	 */
	public function getNamespaceName()
	{
		static $namespaces = array();

		$namespaceName = $this->reflection->getNamespaceName();

		if (!$namespaceName) {
			return $namespaceName;
		}

		$lowerNamespaceName = strtolower($namespaceName);
		if (!isset($namespaces[$lowerNamespaceName])) {
			$namespaces[$lowerNamespaceName] = $namespaceName;
		}

		return $namespaces[$lowerNamespaceName];
	}

	/**
	 * Returns element namespace name.
	 *
	 * For internal elements returns "PHP", for elements in global space returns "None".
	 *
	 * @return string
	 */
	public function getPseudoNamespaceName()
	{
		return $this->isInternal() ? 'PHP' : $this->getNamespaceName() ?: 'None';
	}

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases()
	{
		return $this->reflection->getNamespaceAliases();
	}

	/**
	 * Returns the short description.
	 *
	 * @return string
	 */
	public function getShortDescription()
	{
		$short = $this->reflection->getAnnotation(\TokenReflection\ReflectionAnnotation::SHORT_DESCRIPTION);
		if (!empty($short)) {
			return $short;
		}

		if ($this instanceof ReflectionProperty || $this instanceof ReflectionConstant) {
			$var = $this->getAnnotation('var');
			list(, $short) = preg_split('~\s+|$~', $var[0], 2);
		}

		return $short;
	}

	/**
	 * Returns the long description.
	 *
	 * @return string
	 */
	public function getLongDescription()
	{
		$short = $this->getShortDescription();
		$long = $this->reflection->getAnnotation(\TokenReflection\ReflectionAnnotation::LONG_DESCRIPTION);

		if (!empty($long)) {
			$short .= "\n\n" . $long;
		}

		return $short;
	}

	/**
	 * Returns the appropriate docblock definition.
	 *
	 * @return string|boolean
	 */
	public function getDocComment()
	{
		return $this->reflection->getDocComment();
	}

	/**
	 * Returns reflection element annotations.
	 *
	 * Removes the short and long description.
	 *
	 * In case of classes, functions and constants, @package, @subpackage, @author and @license annotations
	 * are added from declaring files if not already present.
	 *
	 * @return array
	 */
	public function getAnnotations()
	{
		if (null === $this->annotations) {
			static $fileLevel = array('package' => true, 'subpackage' => true, 'author' => true, 'license' => true, 'copyright' => true);

			$annotations = $this->reflection->getAnnotations();
			unset($annotations[\TokenReflection\ReflectionAnnotation::SHORT_DESCRIPTION]);
			unset($annotations[\TokenReflection\ReflectionAnnotation::LONG_DESCRIPTION]);

			if ($this->reflection instanceof \TokenReflection\ReflectionClass || $this->reflection instanceof \TokenReflection\ReflectionFunction || ($this->reflection instanceof \TokenReflection\ReflectionConstant && null === $this->reflection->getDeclaringClassName())) {
				foreach ($this->reflection->getFileReflection()->getAnnotations() as $name => $value) {
					if (isset($fileLevel[$name]) && empty($annotations[$name])) {
						$annotations[$name] = $value;
					}
				}
			}

			$this->annotations = $annotations;
		}

		return $this->annotations;
	}

	/**
	 * Returns reflection element annotation.
	 *
	 * @param string $annotation Annotation name
	 * @return array
	 */
	public function getAnnotation($annotation)
	{
		$annotations = $this->getAnnotations();
		return isset($annotations[$annotation]) ? $annotations[$annotation] : null;
	}

	/**
	 * Checks if there is a particular annotation.
	 *
	 * @param string $annotation Annotation name
	 * @return boolean
	 */
	public function hasAnnotation($annotation)
	{
		$annotations = $this->getAnnotations();
		return isset($annotations[$annotation]);
	}

	/**
	 * Adds element annotation.
	 *
	 * @param string $annotation Annotation name
	 * @param string $value Annotation value
	 * @return \ApiGen\ReflectionElement
	 */
	public function addAnnotation($annotation, $value)
	{
		if (null === $this->annotations) {
			$this->getAnnotations();
		}
		$this->annotations[$annotation][] = $value;

		return $this;
	}
}
