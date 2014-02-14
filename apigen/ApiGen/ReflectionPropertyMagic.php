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

use ReflectionProperty as InternalReflectionProperty;

/**
 * Envelope for magic properties that are defined
 * only as @property, @property-read or @property-write annotation.
 */
class ReflectionPropertyMagic extends ReflectionProperty
{
	/**
	 * Property name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Defines a type hint of parameter values.
	 *
	 * @var string
	 */
	protected $typeHint;

	/**
	 * Short description.
	 *
	 * @var string
	 */
	protected $shortDescription;

	/**
	 * Start line number in the file.
	 *
	 * @var integer
	 */
	protected $startLine;

	/**
	 * End line number in the file.
	 *
	 * @var integer
	 */
	protected $endLine;

	/**
	 * If the property is read-only.
	 *
	 * @var boolean
	 */
	protected $readOnly;

	/**
	 * If the property is write-only.
	 *
	 * @var boolean
	 */
	protected $writeOnly;

	/**
	 * The declaring class.
	 *
	 * @var \ApiGen\ReflectionClass
	 */
	protected $declaringClass;

	/**
	 * Constructor.
	 *
	 * @param \TokenReflection\IReflection $reflection Inspected reflection
	 * @param \ApiGen\Generator $generator ApiGen generator
	 */
	public function __construct(IReflection $reflection = null, Generator $generator = null)
	{
		$this->reflectionType = get_class($this);
		if (!isset(self::$reflectionMethods[$this->reflectionType])) {
			self::$reflectionMethods[$this->reflectionType] = array_flip(get_class_methods($this));
		}
	}

	/**
	 * Sets property name.
	 *
	 * @param string $name
	 * @return \Apigen\ReflectionPropertyMagic
	 */
	public function setName($name)
	{
		$this->name = (string) $name;
		return $this;

	}

	/**
	 * Sets type hint.
	 *
	 * @param string $typeHint
	 * @return \ApiGen\ReflectionParameterUnlimited
	 */
	public function setTypeHint($typeHint)
	{
		$this->typeHint = (string) $typeHint;
		return $this;
	}

	/**
	 * Sets short description.
	 *
	 * @param string $shortDescription
	 * @return \Apigen\ReflectionPropertyMagic
	 */
	public function setShortDescription($shortDescription)
	{
		$this->shortDescription = (string) $shortDescription;
		return $this;
	}

	/**
	 * Sets start line.
	 *
	 * @param integer $startLine
	 * @return \Apigen\ReflectionPropertyMagic
	 */
	public function setStartLine($startLine)
	{
		$this->startLine = (int) $startLine;
		return $this;
	}

	/**
	 * Sets end line.
	 *
	 * @param integer $endLine
	 * @return \Apigen\ReflectionPropertyMagic
	 */
	public function setEndLine($endLine)
	{
		$this->endLine = (int) $endLine;
		return $this;
	}

	/**
	 * Sets if the property is read-only.
	 *
	 * @param boolean $readOnly
	 * @return \Apigen\ReflectionPropertyMagic
	 */
	public function setReadOnly($readOnly)
	{
		$this->readOnly = (bool) $readOnly;
		return $this;
	}

	/**
	 * Sets if the property is write only.
	 *
	 * @param boolean $writeOnly
	 * @return \Apigen\ReflectionPropertyMagic
	 */
	public function setWriteOnly($writeOnly)
	{
		$this->writeOnly = (bool) $writeOnly;
		return $this;
	}

	/**
	 * Sets declaring class.
	 *
	 * @param \ApiGen\ReflectionClass $declaringClass
	 * @return \ApiGen\ReflectionPropertyMagic
	 */
	public function setDeclaringClass(ReflectionClass $declaringClass)
	{
		$this->declaringClass = $declaringClass;
		return $this;
	}

	/**
	 * Returns the reflection broker used by this reflection object.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker()
	{
		return $this->declaringClass->getBroker();
	}

	/**
	 * Returns the start position in the file token stream.
	 *
	 * @return integer
	 */
	public function getStartPosition()
	{
		return $this->declaringClass->getStartPosition();
	}

	/**
	 * Returns the end position in the file token stream.
	 *
	 * @return integer
	 */
	public function getEndPosition()
	{
		return $this->declaringClass->getEndPosition();
	}

	/**
	 * Returns the name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the type hint.
	 *
	 * @return string
	 */
	public function getTypeHint()
	{
		return $this->typeHint;
	}

	/**
	 * Returns the short description.
	 *
	 * @return string
	 */
	public function getShortDescription()
	{
		return $this->shortDescription;
	}

	/**
	 * Returns the long description.
	 *
	 * @return string
	 */
	public function getLongDescription()
	{
		return $this->shortDescription;
	}

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return integer
	 */
	public function getStartLine()
	{
		return $this->startLine;
	}

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return integer
	 */
	public function getEndLine()
	{
		return $this->endLine;
	}

	/**
	 * Returns if the property is read-only.
	 *
	 * @return boolean
	 */
	public function isReadOnly()
	{
		return $this->readOnly;
	}

	/**
	 * Returns if the property is write-only.
	 *
	 * @return boolean
	 */
	public function isWriteOnly()
	{
		return $this->writeOnly;
	}

	/**
	 * Returns if the property is magic.
	 *
	 * @return boolean
	 */
	public function isMagic()
	{
		return true;
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \ApiGen\ReflectionExtension|null
	 */
	public function getExtension()
	{
		return null;
	}

	/**
	 * Returns the PHP extension name.
	 *
	 * @return boolean
	 */
	public function getExtensionName()
	{
		return false;
	}

	/**
	 * Returns if the property should be documented.
	 *
	 * @return boolean
	 */
	public function isDocumented()
	{
		if (null === $this->isDocumented) {
			$this->isDocumented = self::$config->deprecated || !$this->isDeprecated();
		}

		return $this->isDocumented;
	}

	/**
	 * Returns if the property is deprecated.
	 *
	 * @return boolean
	 */
	public function isDeprecated()
	{
		return $this->declaringClass->isDeprecated();
	}

	/**
	 * Returns property package name (including subpackage name).
	 *
	 * @return string
	 */
	public function getPackageName()
	{
		return $this->declaringClass->getPackageName();
	}

	/**
	 * Returns property namespace name.
	 *
	 * @return string
	 */
	public function getNamespaceName()
	{
		return $this->declaringClass->getNamespaceName();
	}

	/**
	 * Returns property annotations.
	 *
	 * @return array
	 */
	public function getAnnotations()
	{
		if (null === $this->annotations) {
			$this->annotations = array();
		}
		return $this->annotations;
	}

	/**
	 * Returns the property declaring class.
	 *
	 * @return \ApiGen\ReflectionClass|null
	 */
	public function getDeclaringClass()
	{
		return $this->declaringClass;
	}

	/**
	 * Returns the name of the declaring class.
	 *
	 * @return string
	 */
	public function getDeclaringClassName()
	{
		return $this->declaringClass->getName();
	}

	/**
	 * Returns the property default value.
	 *
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return null;
	}

	/**
	 * Returns the part of the source code defining the property default value.
	 *
	 * @return string
	 */
	public function getDefaultValueDefinition()
	{
		return '';
	}

	/**
	 * Returns if the property was created at compile time.
	 *
	 * @return boolean
	 */
	public function isDefault()
	{
		return false;
	}

	/**
	 * Returns property modifiers.
	 *
	 * @return integer
	 */
	public function getModifiers()
	{
		return InternalReflectionProperty::IS_PUBLIC;
	}

	/**
	 * Returns if the property is private.
	 *
	 * @return boolean
	 */
	public function isPrivate()
	{
		return false;
	}

	/**
	 * Returns if the property is protected.
	 *
	 * @return boolean
	 */
	public function isProtected()
	{
		return false;
	}

	/**
	 * Returns if the property is public.
	 *
	 * @return boolean
	 */
	public function isPublic()
	{
		return true;
	}

	/**
	 * Returns if the poperty is static.
	 *
	 * @return boolean
	 */
	public function isStatic()
	{
		return false;
	}

	/**
	 * Returns if the property is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return false;
	}

	/**
	 * Returns the property declaring trait.
	 *
	 * @return \ApiGen\ReflectionClass|null
	 */
	public function getDeclaringTrait()
	{
		return $this->declaringClass->isTrait() ? $this->declaringClass : null;
	}

	/**
	 * Returns the declaring trait name.
	 *
	 * @return string|null
	 */
	public function getDeclaringTraitName()
	{
		if ($declaringTrait = $this->getDeclaringTrait()) {
			return $declaringTrait->getName();
		}
		return null;
	}

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases()
	{
		return $this->declaringClass->getNamespaceAliases();
	}

	/**
	 * Returns an property pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return sprintf('%s::$%s', $this->declaringClass->getName(), $this->name);
	}

	/**
	 * Returns the file name the property is defined in.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->declaringClass->getFileName();
	}

	/**
	 * Returns if the property is user defined.

	 * @return boolean
	 */
	public function isUserDefined()
	{
		return true;
	}

	/**
	 * Returns if the property comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized()
	{
		return true;
	}

	/**
	 * Returns the appropriate docblock definition.
	 *
	 * @return string|boolean
	 */
	public function getDocComment()
	{
		$docComment = "/**\n";

		if (!empty($this->shortDescription)) {
			$docComment .= $this->shortDescription . "\n\n";
		}

		if ($annotations = $this->getAnnotation('var')) {
			$docComment .= sprintf("@var %s\n", $annotations[0]);
		}

		$docComment .= "*/\n";

		return $docComment;
	}

	/**
	 * Checks if there is a particular annotation.
	 *
	 * @param string $name Annotation name
	 * @return boolean
	 */
	public function hasAnnotation($name)
	{
		$annotations = $this->getAnnotations();
		return array_key_exists($name, $annotations);
	}

	/**
	 * Returns a particular annotation value.
	 *
	 * @param string $name Annotation name
	 * @return string|array|null
	 */
	public function getAnnotation($name)
	{
		$annotations = $this->getAnnotations();
		if (array_key_exists($name, $annotations)) {
			return $annotations[$name];
		}
		return null;
	}

	/**
	 * Retrieves a property or method value.
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

		return null;
	}

	/**
	 * Checks if the given property exists.
	 *
	 * @param mixed $name Property name
	 * @return boolean
	 */
	public function __isset($name)
	{
		$key = ucfirst($name);
		return isset(self::$reflectionMethods[$this->reflectionType]['get' . $key]) || isset(self::$reflectionMethods[$this->reflectionType]['is' . $key]);
	}
}
