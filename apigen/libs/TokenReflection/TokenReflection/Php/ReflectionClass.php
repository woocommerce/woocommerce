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
use Reflector, ReflectionClass as InternalReflectionClass, ReflectionProperty as InternalReflectionProperty, ReflectionMethod as InternalReflectionMethod;

/**
 * Reflection of a not tokenized but defined class.
 *
 * Descendant of the internal reflection with additional features.
 */
class ReflectionClass extends InternalReflectionClass implements IReflection, TokenReflection\IReflectionClass
{
	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Implemented interface reflections.
	 *
	 * @var array
	 */
	private $interfaces;

	/**
	 * Metod reflections.
	 *
	 * @var array
	 */
	private $methods;

	/**
	 * Constant reflections.
	 *
	 * @var array
	 */
	private $constants;

	/**
	 * Property reflections.
	 *
	 * @var array
	 */
	private $properties;

	/**
	 * Constructor.
	 *
	 * @param string $className Class name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($className, Broker $broker)
	{
		parent::__construct($className);
		$this->broker = $broker;
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \TokenReflection\Php\ReflectionExtension
	 */
	public function getExtension()
	{
		return ReflectionExtension::create(parent::getExtension(), $this->broker);
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
	 * Returns if the class is an exception or its descendant.
	 *
	 * @return boolean
	 */
	public function isException()
	{
		return 'Exception' === $this->getName() || $this->isSubclassOf('Exception');
	}

	/**
	 * Returns if objects of this class are cloneable.
	 *
	 * Introduced in PHP 5.4.
	 *
	 * @return boolean
	 * @see http://svn.php.net/viewvc/php/php-src/trunk/ext/reflection/php_reflection.c?revision=307971&view=markup#l4059
	 */
	public function isCloneable()
	{
		if ($this->isInterface() || $this->isAbstract()) {
			return false;
		}

		$methods = $this->getMethods();
		return isset($methods['__clone']) ? $methods['__clone']->isPublic() : true;
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
	 * Returns if the current class is a subclass of the given class.
	 *
	 * @param string|object $class Class name or reflection object
	 * @return boolean
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid parameter was provided.
	 */
	public function isSubclassOf($class)
	{
		if (is_object($class)) {
			if (!$class instanceof InternalReflectionClass && !$class instanceof IReflectionClass) {
				throw new Exception\RuntimeException('Parameter must be a string or an instance of class reflection.', Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}

			$class = $class->getName();
		}

		return in_array($class, $this->getParentClassNameList());
	}

	/**
	 * Returns parent class reflection.
	 *
	 * @return \TokenReflection\Php\ReflectionClass
	 */
	public function getParentClass()
	{
		$parent = parent::getParentClass();
		return $parent ? self::create($parent, $this->broker) : null;
	}

	/**
	 * Returns the parent class name.
	 *
	 * @return string
	 */
	public function getParentClassName()
	{
		$parent = $this->getParentClass();
		return $parent ? $parent->getName() : null;
	}

	/**
	 * Returns the parent classes reflections.
	 *
	 * @return array
	 */
	public function getParentClasses()
	{
		$broker = $this->broker;
		return array_map(function($className) use ($broker) {
			return $broker->getClass($className);
		}, $this->getParentClassNameList());
	}

	/**
	 * Returns the parent classes names.
	 *
	 * @return array
	 */
	public function getParentClassNameList()
	{
		return class_parents($this->getName());
	}

	/**
	 * Returns if the class implements the given interface.
	 *
	 * @param string|object $interface Interface name or reflection object
	 * @return boolean
	 * @throws \TokenReflection\Exception\RuntimeException If the provided parameter is not an interface.
	 */
	public function implementsInterface($interface)
	{
		if (is_object($interface)) {
			if (!$interface instanceof InternalReflectionClass && !$interface instanceof IReflectionClass) {
				throw new Exception\RuntimeException('Parameter must be a string or an instance of class reflection.', Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}

			$interfaceName = $interface->getName();

			if (!$interface->isInterface()) {
				throw new Exception\RuntimeException(sprintf('"%s" is not an interface.', $interfaceName), Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}
		} else {
			$reflection = $this->getBroker()->getClass($interface);
			if (!$reflection->isInterface()) {
				throw new Exception\RuntimeException(sprintf('"%s" is not an interface.', $interface), Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}

			$interfaceName = $interface;
		}

		$interfaces = $this->getInterfaces();
		return isset($interfaces[$interfaceName]);
	}

	/**
	 * Returns an array of interface reflections.
	 *
	 * @return array
	 */
	public function getInterfaces()
	{
		if (null === $this->interfaces) {
			$broker = $this->broker;
			$interfaceNames = $this->getInterfaceNames();

			if (empty($interfaceNames)) {
				$this->interfaces = array();
			} else {
				$this->interfaces = array_combine($interfaceNames, array_map(function($interfaceName) use ($broker) {
					return $broker->getClass($interfaceName);
				}, $interfaceNames));
			}
		}

		return $this->interfaces;
	}

	/**
	 * Returns interfaces implemented by this class, not its parents.
	 *
	 * @return array
	 */
	public function getOwnInterfaces()
	{
		$parent = $this->getParentClass();
		return $parent ? array_diff_key($this->getInterfaces(), $parent->getInterfaces()) : $this->getInterfaces();
	}

	/**
	 * Returns names of interfaces implemented by this class, not its parents.
	 *
	 * @return array
	 */
	public function getOwnInterfaceNames()
	{
		return array_keys($this->getOwnInterfaces());
	}

	/**
	 * Returns class constructor reflection.
	 *
	 * @return \TokenReflection\Php\ReflectionClass|null
	 */
	public function getConstructor()
	{
		return ReflectionMethod::create(parent::getConstructor(), $this->broker);
	}

	/**
	 * Returns class desctructor reflection.
	 *
	 * @return \TokenReflection\Php\ReflectionClass|null
	 */
	public function getDestructor()
	{
		foreach ($this->getMethods() as $method) {
			if ($method->isDestructor()) {
				return $method;
			}
		}

		return null;
	}

	/**
	 * Returns a particular method reflection.
	 *
	 * @param string $name Method name
	 * @return \TokenReflection\Php\ReflectionMethod
	 * @throws \TokenReflection\Exception\RuntimeException If the requested method does not exist.
	 */
	public function getMethod($name)
	{
		foreach ($this->getMethods() as $method) {
			if ($method->getName() === $name) {
				return $method;
			}
		}

		throw new Exception\RuntimeException(sprintf('Method %s does not exist.', $name), Exception\RuntimeException::DOES_NOT_EXIST, $this);
	}

	/**
	 * Returns class methods.
	 *
	 * @param integer $filter Methods filter
	 * @return array
	 */
	public function getMethods($filter = null)
	{
		if (null === $this->methods) {
			$broker = $this->broker;
			$this->methods = array_map(function(InternalReflectionMethod $method) use ($broker) {
				return ReflectionMethod::create($method, $broker);
			}, parent::getMethods());
		}

		if (null === $filter) {
			return $this->methods;
		}

		return array_filter($this->methods, function(ReflectionMethod $method) use ($filter) {
			return (bool) ($method->getModifiers() & $filter);
		});
	}

	/**
	 * Returns if the class implements (and not its parents) the given method.
	 *
	 * @param string $name Method name
	 * @return boolean
	 */
	public function hasOwnMethod($name)
	{
		foreach ($this->getOwnMethods() as $method) {
			if ($name === $method->getName()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns methods declared by this class, not its parents.
	 *
	 * @param integer $filter
	 * @return array
	 */
	public function getOwnMethods($filter = null)
	{
		$me = $this->getName();
		return array_filter($this->getMethods($filter), function(ReflectionMethod $method) use ($me) {
			return $method->getDeclaringClass()->getName() === $me;
		});
	}

	/**
	 * Returns if the class imports the given method from traits.
	 *
	 * @param string $name Method name
	 * @return boolean
	 * @todo Impossible with the current status of reflection
	 */
	public function hasTraitMethod($name)
	{
		return false;
	}

	/**
	 * Returns method reflections imported from traits.
	 *
	 * @param integer $filter Methods filter
	 * @return array
	 * @todo Impossible with the current status of reflection
	 */
	public function getTraitMethods($filter = null)
	{
		return array();
	}

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $name Constant name
	 * @return \TokenReflection\ReflectionConstant
	 * @throws \TokenReflection\Exception\RuntimeException If the requested constant does not exist.
	 */
	public function getConstantReflection($name)
	{
		if ($this->hasConstant($name)) {
			return new ReflectionConstant($name, $this->getConstant($name), $this->broker, $this);
		}

		throw new Exception\RuntimeException(sprintf('Constant "%s" does not exist.', $name), Exception\RuntimeException::DOES_NOT_EXIST, $this);
	}

	/**
	 * Returns an array of constant reflections.
	 *
	 * @return array
	 */
	public function getConstantReflections()
	{
		if (null === $this->constants) {
			$this->constants = array();
			foreach ($this->getConstants() as $name => $value) {
				$this->constants[$name] = $this->getConstantReflection($name);
			}
		}

		return array_values($this->constants);
	}

	/**
	 * Returns if the class (and not its parents) defines the given constant.
	 *
	 * @param string $name Constant name.
	 * @return boolean
	 */
	public function hasOwnConstant($name)
	{
		$constants = $this->getOwnConstants();
		return isset($constants[$name]);
	}

	/**
	 * Returns constants declared by this class, not its parents.
	 *
	 * @return array
	 */
	public function getOwnConstants()
	{
		return array_diff_assoc($this->getConstants(), $this->getParentClass() ? $this->getParentClass()->getConstants() : array());
	}

	/**
	 * Returns an array of constant reflections defined by this class and not its parents.
	 *
	 * @return array
	 */
	public function getOwnConstantReflections()
	{
		$constants = array();
		foreach ($this->getOwnConstants() as $name => $value) {
			$constants[] = $this->getConstantReflection($name);
		}
		return $constants;
	}

	/**
	 * Returns a particular property reflection.
	 *
	 * @param string $name Property name
	 * @return \TokenReflection\Php\ReflectionProperty
	 * @throws \TokenReflection\Exception\RuntimeException If the requested property does not exist.
	 */
	public function getProperty($name)
	{
		foreach ($this->getProperties() as $property) {
			if ($name === $property->getName()) {
				return $property;
			}
		}

		throw new Exception\RuntimeException(sprintf('Property %s does not exist.', $name), Exception\RuntimeException::DOES_NOT_EXIST, $this);
	}

	/**
	 * Returns class properties.
	 *
	 * @param integer $filter Properties filter
	 * @return array
	 */
	public function getProperties($filter = null)
	{
		if (null === $this->properties) {
			$broker = $this->broker;
			$this->properties = array_map(function(InternalReflectionProperty $property) use ($broker) {
				return ReflectionProperty::create($property, $broker);
			}, parent::getProperties());
		}

		if (null === $filter) {
			return $this->properties;
		}

		return array_filter($this->properties, function(ReflectionProperty $property) use ($filter) {
			return (bool) ($property->getModifiers() & $filter);
		});
	}

	/**
	 * Returns if the class has (and not its parents) the given property.
	 *
	 * @param string $name Property name
	 * @return boolean
	 */
	public function hasOwnProperty($name)
	{
		foreach ($this->getOwnProperties() as $property) {
			if ($name === $property->getName()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns properties declared by this class, not its parents.
	 *
	 * @param integer $filter
	 * @return array
	 */
	public function getOwnProperties($filter = null)
	{
		$me = $this->getName();
		return array_filter($this->getProperties($filter), function(ReflectionProperty $property) use ($me) {
			return $property->getDeclaringClass()->getName() === $me;
		});
	}

	/**
	 * Returns if the class imports the given property from traits.
	 *
	 * @param string $name Property name
	 * @return boolean
	 * @todo Impossible with the current status of reflection
	 */
	public function hasTraitProperty($name)
	{
		return false;
	}

	/**
	 * Returns property reflections imported from traits.
	 *
	 * @param integer $filter Properties filter
	 * @return array
	 * @todo Impossible with the current status of reflection
	 */
	public function getTraitProperties($filter = null)
	{
		return array();
	}

	/**
	 * Returns static properties reflections.
	 *
	 * @return array
	 */
	public function getStaticProperties()
	{
		return $this->getProperties(InternalReflectionProperty::IS_STATIC);
	}

	/**
	 * Returns reflections of direct subclasses.
	 *
	 * @return array
	 */
	public function getDirectSubclasses()
	{
		$that = $this->name;
		return array_filter($this->getBroker()->getClasses(Broker\Backend::INTERNAL_CLASSES | Broker\Backend::TOKENIZED_CLASSES), function(IReflectionClass $class) use ($that) {
			if (!$class->isSubclassOf($that)) {
				return false;
			}

			return null === $class->getParentClassName() || !$class->getParentClass()->isSubClassOf($that);
		});
	}

	/**
	 * Returns names of direct subclasses.
	 *
	 * @return array
	 */
	public function getDirectSubclassNames()
	{
		return array_keys($this->getDirectSubclasses());
	}

	/**
	 * Returns reflections of indirect subclasses.
	 *
	 * @return array
	 */
	public function getIndirectSubclasses()
	{
		$that = $this->name;
		return array_filter($this->getBroker()->getClasses(Broker\Backend::INTERNAL_CLASSES | Broker\Backend::TOKENIZED_CLASSES), function(IReflectionClass $class) use ($that) {
			if (!$class->isSubclassOf($that)) {
				return false;
			}

			return null !== $class->getParentClassName() && $class->getParentClass()->isSubClassOf($that);
		});
	}

	/**
	 * Returns names of indirect subclasses.
	 *
	 * @return array
	 */
	public function getIndirectSubclassNames()
	{
		return array_keys($this->getIndirectSubclasses());
	}

	/**
	 * Returns reflections of classes directly implementing this interface.
	 *
	 * @return array
	 */
	public function getDirectImplementers()
	{
		if (!$this->isInterface()) {
			return array();
		}

		$that = $this->name;
		return array_filter($this->getBroker()->getClasses(Broker\Backend::INTERNAL_CLASSES | Broker\Backend::TOKENIZED_CLASSES), function(IReflectionClass $class) use ($that) {
			if (!$class->implementsInterface($that)) {
				return false;
			}

			return null === $class->getParentClassName() || !$class->getParentClass()->implementsInterface($that);
		});
	}

	/**
	 * Returns names of classes directly implementing this interface.
	 *
	 * @return array
	 */
	public function getDirectImplementerNames()
	{
		return array_keys($this->getDirectImplementers());
	}

	/**
	 * Returns reflections of classes indirectly implementing this interface.
	 *
	 * @return array
	 */
	public function getIndirectImplementers()
	{
		if (!$this->isInterface()) {
			return array();
		}

		$that = $this->name;
		return array_filter($this->getBroker()->getClasses(Broker\Backend::INTERNAL_CLASSES | Broker\Backend::TOKENIZED_CLASSES), function(IReflectionClass $class) use ($that) {
			if (!$class->implementsInterface($that)) {
				return false;
			}

			return null !== $class->getParentClassName() && $class->getParentClass()->implementsInterface($that);
		});
	}

	/**
	 * Returns names of classes indirectly implementing this interface.
	 *
	 * @return array
	 */
	public function getIndirectImplementerNames()
	{
		return array_keys($this->getIndirectImplementers());
	}

	/**
	 * Returns if the class definition is complete.
	 *
	 * Internal classes always have the definition complete.
	 *
	 * @return boolean
	 */
	public function isComplete()
	{
		return true;
	}

	/**
	 * Returns if the class definition is valid.
	 *
	 * Internal classes are always valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return true;
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
	 * Returns the reflection broker used by this reflection object.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker()
	{
		return $this->broker;
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
	 * Returns traits used by this class.
	 *
	 * @return array
	 */
	public function getTraits()
	{
		return NATIVE_TRAITS ? parent::getTraits() : array();
	}

	/**
	 * Returns traits used by this class and not its parents.
	 *
	 * @return array
	 */
	public function getOwnTraits()
	{
		if (!NATIVE_TRAITS) {
			return array();
		}

		$parent = $this->getParentClass();
		return $parent ? array_diff_key($this->getTraits(), $parent->getTraits()) : $this->getTraits();
	}

	/**
	 * Returns names of used traits.
	 *
	 * @return array
	 */
	public function getTraitNames()
	{
		return NATIVE_TRAITS ? parent::getTraitNames() : array();
	}

	/**
	 * Returns traits used by this class and not its parents.
	 *
	 * @return array
	 */
	public function getOwnTraitNames()
	{
		return array_keys($this->getOwnTraits());
	}

	/**
	 * Returns method aliases from traits.
	 *
	 * @return array
	 */
	public function getTraitAliases()
	{
		return NATIVE_TRAITS ? parent::getTraitAliases() : array();
	}

	/**
	 * Returns if the class is a trait.
	 *
	 * @return boolean
	 */
	public function isTrait()
	{
		return NATIVE_TRAITS && parent::isTrait();
	}

	/**
	 * Returns if the class uses a particular trait.
	 *
	 * @param \ReflectionClass|\TokenReflection\IReflectionClass|string $trait Trait reflection or name
	 * @return boolean
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid parameter was provided.
	 */
	public function usesTrait($trait)
	{
		if (is_object($trait)) {
			if (!$trait instanceof InternalReflectionClass && !$trait instanceof TokenReflection\IReflectionClass) {
				throw new Exception\RuntimeException('Parameter must be a string or an instance of trait reflection.', Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}

			$traitName = $trait->getName();

			if (!$trait->isTrait()) {
				throw new Exception\RuntimeException(sprintf('"%s" is not a trait.', $traitName), Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}
		} else {
			$reflection = $this->getBroker()->getClass($trait);
			if (!$reflection->isTrait()) {
				throw new Exception\RuntimeException(sprintf('"%s" is not a trait.', $trait), Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}

			$traitName = $trait;
		}

		return in_array($traitName, $this->getTraitNames());
	}

	/**
	 * Creates a new class instance without using a constructor.
	 *
	 * @return object
	 * @throws \TokenReflection\Exception\RuntimeException If the class inherits from an internal class.
	 */
	public function newInstanceWithoutConstructor()
	{
		if ($this->isInternal()) {
			throw new Exception\RuntimeException('Could not create an instance; only user defined classes can be instantiated.', Exception\RuntimeException::UNSUPPORTED, $this);
		}

		foreach ($this->getParentClasses() as $parent) {
			if ($parent->isInternal()) {
				throw new Exception\RuntimeException('Could not create an instance; only user defined classes can be instantiated.', Exception\RuntimeException::UNSUPPORTED, $this);
			}
		}

		if (PHP_VERSION_ID >= 50400) {
			return parent::newInstanceWithoutConstructor();
		}

		return unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->getName()), $this->getName()));
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return $this->getName();
	}

	/**
	 * Creates a reflection instance.
	 *
	 * @param \ReflectionClass $internalReflection Internal reflection instance
	 * @param \TokenReflection\Broker $broker Reflection broker instance
	 * @return \TokenReflection\Php\ReflectionClass
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid internal reflection object was provided.
	 */
	public static function create(Reflector $internalReflection, Broker $broker)
	{
		if (!$internalReflection instanceof InternalReflectionClass) {
			throw new Exception\RuntimeException('Invalid reflection instance provided, ReflectionClass expected.', Exception\RuntimeException::INVALID_ARGUMENT);
		}

		return $broker->getClass($internalReflection->getName());
	}
}
