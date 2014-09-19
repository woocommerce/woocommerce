<?php
/**
 * PHP Token Reflection
 *
 * Version 1.3.1
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this library in the file LICENSE.
 *
 * @author Ondřej Nešpor
 * @author Jaroslav Hanslík
 */

namespace TokenReflection\Php;

use TokenReflection;
use TokenReflection\Broker, TokenReflection\Exception;
use Reflector, ReflectionProperty as InternalReflectionProperty;

/**
 * Reflection of a not tokenized but defined class property.
 *
 * Descendant of the internal reflection with additional features.
 */
class ReflectionProperty extends InternalReflectionProperty implements IReflection, TokenReflection\IReflectionProperty
{
	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Is the property accessible despite its access level.
	 *
	 * @var boolean
	 */
	private $accessible = false;

	/**
	 * Constructor.
	 *
	 * @param string|\TokenReflection\Php\ReflectionClass|\ReflectionClass $class Defining class
	 * @param string $propertyName Property name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($class, $propertyName, Broker $broker)
	{
		parent::__construct($class, $propertyName);
		$this->broker = $broker;
	}

	/**
	 * Returns the declaring class reflection.
	 *
	 * @return \TokenReflection\IReflectionClass
	 */
	public function getDeclaringClass()
	{
		return ReflectionClass::create(parent::getDeclaringClass(), $this->broker);
	}

	/**
	 * Returns the declaring class name.
	 *
	 * @return string
	 */
	public function getDeclaringClassName()
	{
		return $this->getDeclaringClass()->getName();
	}

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return null
	 */
	public function getStartLine()
	{
		return null;
	}

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return null
	 */
	public function getEndLine()
	{
		return null;
	}

	/**
	 * Returns the appropriate docblock definition.
	 *
	 * @return boolean
	 */
	public function getDocComment()
	{
		return false;
	}

	/**
	 * Checks if there is a particular annotation.
	 *
	 * @param string $name Annotation name
	 * @return boolean
	 */
	public function hasAnnotation($name)
	{
		return false;
	}

	/**
	 * Returns a particular annotation value.
	 *
	 * @param string $name Annotation name
	 * @return null
	 */
	public function getAnnotation($name)
	{
		return null;
	}

	/**
	 * Returns parsed docblock.
	 *
	 * @return array
	 */
	public function getAnnotations()
	{
		return array();
	}

	/**
	 * Returns the property default value.
	 *
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		$values = $this->getDeclaringClass()->getDefaultProperties();
		return $values[$this->getName()];
	}

	/**
	 * Returns the part of the source code defining the property default value.
	 *
	 * @return string
	 */
	public function getDefaultValueDefinition()
	{
		$value = $this->getDefaultValue();
		return null === $value ? null : var_export($value, true);
	}

	/**
	 * Returns if the property is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return $this->getDeclaringClass()->isInternal();
	}

	/**
	 * Returns if the property is user defined.
	 *
	 * @return boolean
	 */
	public function isUserDefined()
	{
		return $this->getDeclaringClass()->isUserDefined();
	}

	/**
	 * Returns if the current reflection comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized()
	{
		return false;
	}

	/**
	 * Returns if the reflection subject is deprecated.
	 *
	 * @return boolean
	 */
	public function isDeprecated()
	{
		return false;
	}

	/**
	 * Returns the reflection broker used by this reflection object.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker()
	{
		return $this->broker;
	}

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases()
	{
		return array();
	}

	/**
	 * Returns the defining trait.
	 *
	 * @return \TokenReflection\IReflectionClass|null
	 */
	public function getDeclaringTrait()
	{
		return null;
	}

	/**
	 * Returns the declaring trait name.
	 *
	 * @return string|null
	 */
	public function getDeclaringTraitName()
	{
		return null;
	}

	/**
	 * Returns if the property is set accessible.
	 *
	 * @return boolean
	 */
	public function isAccessible()
	{
		return $this->accessible;
	}

	/**
	 * Sets a property to be accessible or not.
	 *
	 * @param boolean $accessible If the property should be accessible.
	 */
	public function setAccessible($accessible)
	{
		$this->accessible = (bool) $accessible;

		parent::setAccessible($accessible);
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \TokenReflection\Php\ReflectionExtension
	 */
	public function getExtension()
	{
		return $this->getDeclaringClass()->getExtension();
	}

	/**
	 * Returns the PHP extension name.
	 *
	 * @return string|boolean
	 */
	public function getExtensionName()
	{
		$extension = $this->getExtension();
		return $extension ? $extension->getName() : false;
	}

	/**
	 * Returns the file name the reflection object is defined in.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->getDeclaringClass()->getFileName();
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return sprintf('%s::$%s', $this->getDeclaringClassName(), $this->getName());
	}

	/**
	 * Magic __get method.
	 *
	 * @param string $key Variable name
	 * @return mixed
	 */
	final public function __get($key)
	{
		return TokenReflection\ReflectionElement::get($this, $key);
	}

	/**
	 * Magic __isset method.
	 *
	 * @param string $key Variable name
	 * @return boolean
	 */
	final public function __isset($key)
	{
		return TokenReflection\ReflectionElement::exists($this, $key);
	}

	/**
	 * Creates a reflection instance.
	 *
	 * @param \ReflectionClass $internalReflection Internal reflection instance
	 * @param \TokenReflection\Broker $broker Reflection broker instance
	 * @return \TokenReflection\Php\ReflectionProperty
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid internal reflection object was provided.
	 */
	public static function create(Reflector $internalReflection, Broker $broker)
	{
		static $cache = array();

		if (!$internalReflection instanceof InternalReflectionProperty) {
			throw new Exception\RuntimeException('Invalid reflection instance provided, ReflectionProperty expected.', Exception\RuntimeException::INVALID_ARGUMENT);
		}

		$key = $internalReflection->getDeclaringClass()->getName() . '::' . $internalReflection->getName();
		if (!isset($cache[$key])) {
			$cache[$key] = new self($internalReflection->getDeclaringClass()->getName(), $internalReflection->getName(), $broker);
		}

		return $cache[$key];
	}
}
