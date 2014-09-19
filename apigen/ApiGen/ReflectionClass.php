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

use TokenReflection, TokenReflection\IReflectionClass, TokenReflection\IReflectionMethod, TokenReflection\IReflectionProperty, TokenReflection\IReflectionConstant;
use ReflectionMethod as InternalReflectionMethod, ReflectionProperty as InternalReflectionProperty;
use InvalidArgumentException;

/**
 * Class reflection envelope.
 *
 * Alters TokenReflection\IReflectionClass functionality for ApiGen.
 */
class ReflectionClass extends ReflectionElement
{
	/**
	 * Access level for methods.
	 *
	 * @var integer
	 */
	private static $methodAccessLevels = false;

	/**
	 * Access level for properties.
	 *
	 * @var integer
	 */
	private static $propertyAccessLevels = false;

	/**
	 * Cache for list of parent classes.
	 *
	 * @var array
	 */
	private $parentClasses;

	/**
	 * Cache for list of own methods.
	 *
	 * @var array
	 */
	private $ownMethods;

	/**
	 * Cache for list of own magic methods.
	 *
	 * @var array
	 */
	private $ownMagicMethods;

	/**
	 * Cache for list of own properties.
	 *
	 * @var array
	 */
	private $ownProperties;

	/**
	 * Cache for list of own magic properties.
	 *
	 * @var array
	 */
	private $ownMagicProperties;

	/**
	 * Cache for list of own constants.
	 *
	 * @var array
	 */
	private $ownConstants;

	/**
	 * Cache for list of all methods.
	 *
	 * @var array
	 */
	private $methods;

	/**
	 * Cache for list of all properties.
	 *
	 * @var array
	 */
	private $properties;

	/**
	 * Cache for list of all constants.
	 *
	 * @var array
	 */
	private $constants;

	/**
	 * Constructor.
	 *
	 * Sets the inspected class reflection.
	 *
	 * @param \TokenReflection\IReflectionClass $reflection Inspected class reflection
	 * @param \ApiGen\Generator $generator ApiGen generator
	 */
	public function __construct(IReflectionClass $reflection, Generator $generator)
	{
		parent::__construct($reflection, $generator);

		if (false === self::$methodAccessLevels) {
			if (count(self::$config->accessLevels) < 3) {
				self::$methodAccessLevels = 0;
				self::$propertyAccessLevels = 0;

				foreach (self::$config->accessLevels as $level) {
					switch (strtolower($level)) {
						case 'public':
							self::$methodAccessLevels |= InternalReflectionMethod::IS_PUBLIC;
							self::$propertyAccessLevels |= InternalReflectionProperty::IS_PUBLIC;
							break;
						case 'protected':
							self::$methodAccessLevels |= InternalReflectionMethod::IS_PROTECTED;
							self::$propertyAccessLevels |= InternalReflectionProperty::IS_PROTECTED;
							break;
						case 'private':
							self::$methodAccessLevels |= InternalReflectionMethod::IS_PRIVATE;
							self::$propertyAccessLevels |= InternalReflectionProperty::IS_PRIVATE;
							break;
						default:
							break;
					}
				}
			} else {
				self::$methodAccessLevels = null;
				self::$propertyAccessLevels = null;
			}
		}
	}

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
	 * Returns modifiers.
	 *
	 * @return array
	 */
	public function getModifiers()
	{
		return $this->reflection->getModifiers();
	}

	/**
	 * Returns if the class is abstract.
	 *
	 * @return boolean
	 */
	public function isAbstract()
	{
		return $this->reflection->isAbstract();
	}

	/**
	 * Returns if the class is final.
	 *
	 * @return boolean
	 */
	public function isFinal()
	{
		return $this->reflection->isFinal();
	}

	/**
	 * Returns if the class is an interface.
	 *
	 * @return boolean
	 */
	public function isInterface()
	{
		return $this->reflection->isInterface();
	}

	/**
	 * Returns if the class is an exception or its descendant.
	 *
	 * @return boolean
	 */
	public function isException()
	{
		return $this->reflection->isException();
	}

	/**
	 * Returns if the current class is a subclass of the given class.
	 *
	 * @param string $class Class name
	 * @return boolean
	 */
	public function isSubclassOf($class)
	{
		return $this->reflection->isSubclassOf($class);
	}

	/**
	 * Returns visible methods.
	 *
	 * @return array
	 */
	public function getMethods()
	{
		if (null === $this->methods) {
			$this->methods = $this->getOwnMethods();
			foreach ($this->reflection->getMethods(self::$methodAccessLevels) as $method) {
				if (isset($this->methods[$method->getName()])) {
					continue;
				}
				$apiMethod = new ReflectionMethod($method, self::$generator);
				if (!$this->isDocumented() || $apiMethod->isDocumented()) {
					$this->methods[$method->getName()] = $apiMethod;
				}
			}
		}
		return $this->methods;
	}

	/**
	 * Returns visible methods declared by inspected class.
	 *
	 * @return array
	 */
	public function getOwnMethods()
	{
		if (null === $this->ownMethods) {
			$this->ownMethods = array();
			foreach ($this->reflection->getOwnMethods(self::$methodAccessLevels) as $method) {
				$apiMethod = new ReflectionMethod($method, self::$generator);
				if (!$this->isDocumented() || $apiMethod->isDocumented()) {
					$this->ownMethods[$method->getName()] = $apiMethod;
				}
			}
		}
		return $this->ownMethods;
	}

	/**
	 * Returns visible magic methods.
	 *
	 * @return array
	 */
	public function getMagicMethods()
	{
		$methods = $this->getOwnMagicMethods();

		$parent = $this->getParentClass();
		while ($parent) {
			foreach ($parent->getOwnMagicMethods() as $method) {
				if (isset($methods[$method->getName()])) {
					continue;
				}

				if (!$this->isDocumented() || $method->isDocumented()) {
					$methods[$method->getName()] = $method;
				}
			}
			$parent = $parent->getParentClass();
		}

		foreach ($this->getTraits() as $trait) {
			foreach ($trait->getOwnMagicMethods() as $method) {
				if (isset($methods[$method->getName()])) {
					continue;
				}

				if (!$this->isDocumented() || $method->isDocumented()) {
					$methods[$method->getName()] = $method;
				}
			}
		}

		return $methods;
	}

	/**
	 * Returns visible magic methods declared by inspected class.
	 *
	 * @return array
	 */
	public function getOwnMagicMethods()
	{
		if (null === $this->ownMagicMethods) {
			$this->ownMagicMethods = array();

			if (!(self::$methodAccessLevels & InternalReflectionMethod::IS_PUBLIC) || false === $this->getDocComment()) {
				return $this->ownMagicMethods;
			}

			$annotations = $this->getAnnotation('method');
			if (null === $annotations) {
				return $this->ownMagicMethods;
			}

			foreach ($annotations as $annotation) {
				if (!preg_match('~^(?:([\\w\\\\]+(?:\\|[\\w\\\\]+)*)\\s+)?(&)?\\s*(\\w+)\\s*\\(\\s*(.*)\\s*\\)\\s*(.*|$)~s', $annotation, $matches)) {
					// Wrong annotation format
					continue;
				}

				list(, $returnTypeHint, $returnsReference, $name, $args, $shortDescription) = $matches;

				$doc = $this->getDocComment();
				$tmp = $annotation;
				if ($delimiter = strpos($annotation, "\n")) {
					$tmp = substr($annotation, 0, $delimiter);
				}

				$startLine = $this->getStartLine() + substr_count(substr($doc, 0, strpos($doc, $tmp)), "\n");
				$endLine = $startLine + substr_count($annotation, "\n");

				$method = new ReflectionMethodMagic(null, self::$generator);
				$method
					->setName($name)
					->setShortDescription(str_replace("\n", ' ', $shortDescription))
					->setStartLine($startLine)
					->setEndLine($endLine)
					->setReturnsReference('&' === $returnsReference)
					->setDeclaringClass($this)
					->addAnnotation('return', $returnTypeHint);

				$this->ownMagicMethods[$name] = $method;

				$parameters = array();
				foreach (array_filter(preg_split('~\\s*,\\s*~', $args)) as $position => $arg) {
					if (!preg_match('~^(?:([\\w\\\\]+(?:\\|[\\w\\\\]+)*)\\s+)?(&)?\\s*\\$(\\w+)(?:\\s*=\\s*(.*))?($)~s', $arg, $matches)) {
						// Wrong annotation format
						continue;
					}

					list(, $typeHint, $passedByReference, $name, $defaultValueDefinition) = $matches;

					if (empty($typeHint)) {
						$typeHint = 'mixed';
					}

					$parameter = new ReflectionParameterMagic(null, self::$generator);
					$parameter
						->setName($name)
						->setPosition($position)
						->setTypeHint($typeHint)
						->setDefaultValueDefinition($defaultValueDefinition)
						->setUnlimited(false)
						->setPassedByReference('&' === $passedByReference)
						->setDeclaringFunction($method);

					$parameters[$name] = $parameter;

					$method->addAnnotation('param', ltrim(sprintf('%s $%s', $typeHint, $name)));
				}
				$method->setParameters($parameters);
			}
		}
		return $this->ownMagicMethods;
	}

	/**
	 * Returns visible methods declared by traits.
	 *
	 * @return array
	 */
	public function getTraitMethods()
	{
		$methods = array();
		foreach ($this->reflection->getTraitMethods(self::$methodAccessLevels) as $method) {
			$apiMethod = new ReflectionMethod($method, self::$generator);
			if (!$this->isDocumented() || $apiMethod->isDocumented()) {
				$methods[$method->getName()] = $apiMethod;
			}
		}
		return $methods;
	}

	/**
	 * Returns a method reflection.
	 *
	 * @param string $name Method name
	 * @return \ApiGen\ReflectionMethod
	 * @throws \InvalidArgumentException If required method does not exist.
	 */
	public function getMethod($name)
	{
		if ($this->hasMethod($name)) {
			return $this->methods[$name];
		}

		throw new InvalidArgumentException(sprintf('Method %s does not exist in class %s', $name, $this->reflection->getName()));
	}

	/**
	 * Returns visible properties.
	 *
	 * @return array
	 */
	public function getProperties()
	{
		if (null === $this->properties) {
			$this->properties = $this->getOwnProperties();
			foreach ($this->reflection->getProperties(self::$propertyAccessLevels) as $property) {
				if (isset($this->properties[$property->getName()])) {
					continue;
				}
				$apiProperty = new ReflectionProperty($property, self::$generator);
				if (!$this->isDocumented() || $apiProperty->isDocumented()) {
					$this->properties[$property->getName()] = $apiProperty;
				}
			}
		}
		return $this->properties;
	}

	/**
	 * Returns visible magic properties.
	 *
	 * @return array
	 */
	public function getMagicProperties()
	{
		$properties = $this->getOwnMagicProperties();

		$parent = $this->getParentClass();
		while ($parent) {
			foreach ($parent->getOwnMagicProperties() as $property) {
				if (isset($properties[$method->getName()])) {
					continue;
				}

				if (!$this->isDocumented() || $property->isDocumented()) {
					$properties[$property->getName()] = $property;
				}
			}
			$parent = $parent->getParentClass();
		}

		foreach ($this->getTraits() as $trait) {
			foreach ($trait->getOwnMagicProperties() as $property) {
				if (isset($properties[$method->getName()])) {
					continue;
				}

				if (!$this->isDocumented() || $property->isDocumented()) {
					$properties[$property->getName()] = $property;
				}
			}
		}

		return $properties;
	}

	/**
	 * Returns visible properties declared by inspected class.
	 *
	 * @return array
	 */
	public function getOwnProperties()
	{
		if (null === $this->ownProperties) {
			$this->ownProperties = array();
			foreach ($this->reflection->getOwnProperties(self::$propertyAccessLevels) as $property) {
				$apiProperty = new ReflectionProperty($property, self::$generator);
				if (!$this->isDocumented() || $apiProperty->isDocumented()) {
					$this->ownProperties[$property->getName()] = $apiProperty;
				}
			}
		}
		return $this->ownProperties;
	}

	/**
	 * Returns visible properties magicly declared by inspected class.
	 *
	 * @return array
	 */
	public function getOwnMagicProperties()
	{
		if (null === $this->ownMagicProperties) {
			$this->ownMagicProperties = array();

			if (!(self::$propertyAccessLevels & InternalReflectionProperty::IS_PUBLIC) || false === $this->getDocComment()) {
				return $this->ownMagicProperties;
			}

			foreach (array('property', 'property-read', 'property-write') as $annotationName) {
				$annotations = $this->getAnnotation($annotationName);
				if (null === $annotations) {
					continue;
				}

				foreach ($annotations as $annotation) {
					if (!preg_match('~^(?:([\\w\\\\]+(?:\\|[\\w\\\\]+)*)\\s+)?\\$(\\w+)(?:\\s+(.*))?($)~s', $annotation, $matches)) {
						// Wrong annotation format
						continue;
					}

					list(, $typeHint, $name, $shortDescription) = $matches;

					if (empty($typeHint)) {
						$typeHint = 'mixed';
					}

					$doc = $this->getDocComment();
					$tmp = $annotation;
					if ($delimiter = strpos($annotation, "\n")) {
						$tmp = substr($annotation, 0, $delimiter);
					}

					$startLine = $this->getStartLine() + substr_count(substr($doc, 0, strpos($doc, $tmp)), "\n");
					$endLine = $startLine + substr_count($annotation, "\n");

					$magicProperty = new ReflectionPropertyMagic(null, self::$generator);
					$magicProperty
						->setName($name)
						->setTypeHint($typeHint)
						->setShortDescription(str_replace("\n", ' ', $shortDescription))
						->setStartLine($startLine)
						->setEndLine($endLine)
						->setReadOnly('property-read' === $annotationName)
						->setWriteOnly('property-write' === $annotationName)
						->setDeclaringClass($this)
						->addAnnotation('var', $typeHint);

					$this->ownMagicProperties[$name] = $magicProperty;
				}
			}
		}

		return $this->ownMagicProperties;
	}

	/**
	 * Returns visible properties declared by traits.
	 *
	 * @return array
	 */
	public function getTraitProperties()
	{
		$properties = array();
		foreach ($this->reflection->getTraitProperties(self::$propertyAccessLevels) as $property) {
			$apiProperty = new ReflectionProperty($property, self::$generator);
			if (!$this->isDocumented() || $apiProperty->isDocumented()) {
				$properties[$property->getName()] = $apiProperty;
			}
		}
		return $properties;
	}

	/**
	 * Returns a method property.
	 *
	 * @param string $name Method name
	 * @return \ApiGen\ReflectionProperty
	 * @throws \InvalidArgumentException If required property does not exist.
	 */
	public function getProperty($name)
	{
		if ($this->hasProperty($name)) {
			return $this->properties[$name];
		}

		throw new InvalidArgumentException(sprintf('Property %s does not exist in class %s', $name, $this->reflection->getName()));
	}

	/**
	 * Returns visible properties.
	 *
	 * @return array
	 */
	public function getConstants()
	{
		if (null === $this->constants) {
			$this->constants = array();
			foreach ($this->reflection->getConstantReflections() as $constant) {
				$apiConstant = new ReflectionConstant($constant, self::$generator);
				if (!$this->isDocumented() || $apiConstant->isDocumented()) {
					$this->constants[$constant->getName()] = $apiConstant;
				}
			}
		}

		return $this->constants;
	}

	/**
	 * Returns constants declared by inspected class.
	 *
	 * @return array
	 */
	public function getOwnConstants()
	{
		if (null === $this->ownConstants) {
			$this->ownConstants = array();
			$className = $this->reflection->getName();
			foreach ($this->getConstants() as $constantName => $constant) {
				if ($className === $constant->getDeclaringClassName()) {
					$this->ownConstants[$constantName] = $constant;
				}
			}
		}
		return $this->ownConstants;
	}

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $name Constant name
	 * @return \ApiGen\ReflectionConstant
	 * @throws \InvalidArgumentException If required constant does not exist.
	 */
	public function getConstantReflection($name)
	{
		if (null === $this->constants) {
			$this->getConstants();
		}

		if (isset($this->constants[$name])) {
			return $this->constants[$name];
		}

		throw new InvalidArgumentException(sprintf('Constant %s does not exist in class %s', $name, $this->reflection->getName()));
	}

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $name Constant name
	 * @return \ApiGen\ReflectionConstant
	 */
	public function getConstant($name)
	{
		return $this->getConstantReflection($name);
	}

	/**
	 * Checks if there is a constant of the given name.
	 *
	 * @param string $constantName Constant name
	 * @return boolean
	 */
	public function hasConstant($constantName)
	{
		if (null === $this->constants) {
			$this->getConstants();
		}

		return isset($this->constants[$constantName]);
	}

	/**
	 * Checks if there is a constant of the given name.
	 *
	 * @param string $constantName Constant name
	 * @return boolean
	 */
	public function hasOwnConstant($constantName)
	{
		if (null === $this->ownConstants) {
			$this->getOwnConstants();
		}

		return isset($this->ownConstants[$constantName]);
	}

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $name Constant name
	 * @return \ApiGen\ReflectionConstant
	 * @throws \InvalidArgumentException If required constant does not exist.
	 */
	public function getOwnConstantReflection($name)
	{
		if (null === $this->ownConstants) {
			$this->getOwnConstants();
		}

		if (isset($this->ownConstants[$name])) {
			return $this->ownConstants[$name];
		}

		throw new InvalidArgumentException(sprintf('Constant %s does not exist in class %s', $name, $this->reflection->getName()));
	}

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $name Constant name
	 * @return \ApiGen\ReflectionConstant
	 */
	public function getOwnConstant($name)
	{
		return $this->getOwnConstantReflection($name);
	}

	/**
	 * Returns a parent class reflection encapsulated by this class.
	 *
	 * @return \ApiGen\ReflectionClass
	 */
	public function getParentClass()
	{
		if ($className = $this->reflection->getParentClassName()) {
			return self::$parsedClasses[$className];
		}
		return $className;
	}

	/**
	 * Returns the parent class name.
	 *
	 * @return string|null
	 */
	public function getParentClassName()
	{
		return $this->reflection->getParentClassName();
	}

	/**
	 * Returns all parent classes reflections encapsulated by this class.
	 *
	 * @return array
	 */
	public function getParentClasses()
	{
		if (null === $this->parentClasses) {
			$classes = self::$parsedClasses;
			$this->parentClasses = array_map(function(IReflectionClass $class) use ($classes) {
				return $classes[$class->getName()];
			}, $this->reflection->getParentClasses());
		}
		return $this->parentClasses;
	}


	/**
	 * Returns the parent classes names.
	 *
	 * @return array
	 */
	public function getParentClassNameList()
	{
		return $this->reflection->getParentClassNameList();
	}

	/**
	 * Returns if the class implements the given interface.
	 *
	 * @param string|object $interface Interface name or reflection object
	 * @return boolean
	 */
	public function implementsInterface($interface)
	{
		return $this->reflection->implementsInterface($interface);
	}

	/**
	 * Returns all interface reflections encapsulated by this class.
	 *
	 * @return array
	 */
	public function getInterfaces()
	{
		$classes = self::$parsedClasses;
		return array_map(function(IReflectionClass $class) use ($classes) {
			return $classes[$class->getName()];
		}, $this->reflection->getInterfaces());
	}

	/**
	 * Returns interface names.
	 *
	 * @return array
	 */
	public function getInterfaceNames()
	{
		return $this->reflection->getInterfaceNames();
	}

	/**
	 * Returns all interfaces implemented by the inspected class and not its parents.
	 *
	 * @return array
	 */
	public function getOwnInterfaces()
	{
		$classes = self::$parsedClasses;
		return array_map(function(IReflectionClass $class) use ($classes) {
			return $classes[$class->getName()];
		}, $this->reflection->getOwnInterfaces());
	}

	/**
	 * Returns names of interfaces implemented by this class, not its parents.
	 *
	 * @return array
	 */
	public function getOwnInterfaceNames()
	{
		return $this->reflection->getOwnInterfaceNames();
	}

	/**
	 * Returns all traits reflections encapsulated by this class.
	 *
	 * @return array
	 */
	public function getTraits()
	{
		$classes = self::$parsedClasses;
		return array_map(function(IReflectionClass $class) use ($classes) {
			return $classes[$class->getName()];
		}, $this->reflection->getTraits());
	}

	/**
	 * Returns names of used traits.
	 *
	 * @return array
	 */
	public function getTraitNames()
	{
		return $this->reflection->getTraitNames();
	}

	/**
	 * Returns names of traits used by this class an not its parents.
	 *
	 * @return array
	 */
	public function getOwnTraitNames()
	{
		return $this->reflection->getOwnTraitNames();
	}

	/**
	 * Returns method aliases from traits.
	 *
	 * @return array
	 */
	public function getTraitAliases()
	{
		return $this->reflection->getTraitAliases();
	}

	/**
	 * Returns all traits used by the inspected class and not its parents.
	 *
	 * @return array
	 */
	public function getOwnTraits()
	{
		$classes = self::$parsedClasses;
		return array_map(function(IReflectionClass $class) use ($classes) {
			return $classes[$class->getName()];
		}, $this->reflection->getOwnTraits());
	}

	/**
	 * Returns if the class is a trait.
	 *
	 * @return boolean
	 */
	public function isTrait()
	{
		return $this->reflection->isTrait();
	}

	/**
	 * Returns if the class uses a particular trait.
	 *
	 * @param string $trait Trait name
	 * @return boolean
	 */
	public function usesTrait($trait)
	{
		return $this->reflection->usesTrait($trait);
	}

	/**
	 * Returns reflections of direct subclasses.
	 *
	 * @return array
	 */
	public function getDirectSubClasses()
	{
		$subClasses = array();
		$name = $this->reflection->getName();
		foreach (self::$parsedClasses as $class) {
			if (!$class->isDocumented()) {
				continue;
			}
			if ($name === $class->getParentClassName()) {
				$subClasses[] = $class;
			}
		}
		return $subClasses;
	}

	/**
	 * Returns reflections of indirect subclasses.
	 *
	 * @return array
	 */
	public function getIndirectSubClasses()
	{
		$subClasses = array();
		$name = $this->reflection->getName();
		foreach (self::$parsedClasses as $class) {
			if (!$class->isDocumented()) {
				continue;
			}
			if ($name !== $class->getParentClassName() && $class->isSubclassOf($name)) {
				$subClasses[] = $class;
			}
		}
		return $subClasses;
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

		$implementers = array();
		$name = $this->reflection->getName();
		foreach (self::$parsedClasses as $class) {
			if (!$class->isDocumented()) {
				continue;
			}
			if (in_array($name, $class->getOwnInterfaceNames())) {
				$implementers[] = $class;
			}
		}
		return $implementers;
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

		$implementers = array();
		$name = $this->reflection->getName();
		foreach (self::$parsedClasses as $class) {
			if (!$class->isDocumented()) {
				continue;
			}
			if ($class->implementsInterface($name) && !in_array($name, $class->getOwnInterfaceNames())) {
				$implementers[] = $class;
			}
		}
		return $implementers;
	}

	/**
	 * Returns reflections of classes directly using this trait.
	 *
	 * @return array
	 */
	public function getDirectUsers()
	{
		if (!$this->isTrait()) {
			return array();
		}

		$users = array();
		$name = $this->reflection->getName();
		foreach (self::$parsedClasses as $class) {
			if (!$class->isDocumented()) {
				continue;
			}

			if (in_array($name, $class->getOwnTraitNames())) {
				$users[] = $class;
			}
		}
		return $users;
	}

	/**
	 * Returns reflections of classes indirectly using this trait.
	 *
	 * @return array
	 */
	public function getIndirectUsers()
	{
		if (!$this->isTrait()) {
			return array();
		}

		$users = array();
		$name = $this->reflection->getName();
		foreach (self::$parsedClasses as $class) {
			if (!$class->isDocumented()) {
				continue;
			}
			if ($class->usesTrait($name) && !in_array($name, $class->getOwnTraitNames())) {
				$users[] = $class;
			}
		}
		return $users;
	}

	/**
	 * Returns an array of inherited methods from parent classes grouped by the declaring class name.
	 *
	 * @return array
	 */
	public function getInheritedMethods()
	{
		$methods = array();
		$allMethods = array_flip(array_map(function($method) {
			return $method->getName();
		}, $this->getOwnMethods()));

		foreach (array_merge($this->getParentClasses(), $this->getInterfaces()) as $class) {
			$inheritedMethods = array();
			foreach ($class->getOwnMethods() as $method) {
				if (!array_key_exists($method->getName(), $allMethods) && !$method->isPrivate()) {
					$inheritedMethods[$method->getName()] = $method;
					$allMethods[$method->getName()] = null;
				}
			}

			if (!empty($inheritedMethods)) {
				ksort($inheritedMethods);
				$methods[$class->getName()] = array_values($inheritedMethods);
			}
		}

		return $methods;
	}

	/**
	 * Returns an array of inherited magic methods from parent classes grouped by the declaring class name.
	 *
	 * @return array
	 */
	public function getInheritedMagicMethods()
	{
		$methods = array();
		$allMethods = array_flip(array_map(function($method) {
			return $method->getName();
		}, $this->getOwnMagicMethods()));

		foreach (array_merge($this->getParentClasses(), $this->getInterfaces()) as $class) {
			$inheritedMethods = array();
			foreach ($class->getOwnMagicMethods() as $method) {
				if (!array_key_exists($method->getName(), $allMethods)) {
					$inheritedMethods[$method->getName()] = $method;
					$allMethods[$method->getName()] = null;
				}
			}

			if (!empty($inheritedMethods)) {
				ksort($inheritedMethods);
				$methods[$class->getName()] = array_values($inheritedMethods);
			}
		}

		return $methods;
	}

	/**
	 * Returns an array of used methods from used traits grouped by the declaring trait name.
	 *
	 * @return array
	 */
	public function getUsedMethods()
	{
		$usedMethods = array();
		foreach ($this->getMethods() as $method) {
			if (null === $method->getDeclaringTraitName() || $this->getName() === $method->getDeclaringTraitName()) {
				continue;
			}

			$usedMethods[$method->getDeclaringTraitName()][$method->getName()]['method'] = $method;
			if (null !== $method->getOriginalName() && $method->getName() !== $method->getOriginalName()) {
				$usedMethods[$method->getDeclaringTraitName()][$method->getName()]['aliases'][$method->getName()] = $method;
			}
		}

		// Sort
		array_walk($usedMethods, function(&$methods) {
			ksort($methods);
			array_walk($methods, function(&$aliasedMethods) {
				if (!isset($aliasedMethods['aliases'])) {
					$aliasedMethods['aliases'] = array();
				}
				ksort($aliasedMethods['aliases']);
			});
		});

		return $usedMethods;
	}

	/**
	 * Returns an array of used magic methods from used traits grouped by the declaring trait name.
	 *
	 * @return array
	 */
	public function getUsedMagicMethods()
	{
		$usedMethods = array();

		foreach ($this->getMagicMethods() as $method) {
			if (null === $method->getDeclaringTraitName() || $this->getName() === $method->getDeclaringTraitName()) {
				continue;
			}

			$usedMethods[$method->getDeclaringTraitName()][$method->getName()]['method'] = $method;
		}

		// Sort
		array_walk($usedMethods, function(&$methods) {
			ksort($methods);
			array_walk($methods, function(&$aliasedMethods) {
				if (!isset($aliasedMethods['aliases'])) {
					$aliasedMethods['aliases'] = array();
				}
				ksort($aliasedMethods['aliases']);
			});
		});

		return $usedMethods;
	}

	/**
	 * Returns an array of inherited constants from parent classes grouped by the declaring class name.
	 *
	 * @return array
	 */
	public function getInheritedConstants()
	{
		return array_filter(
			array_map(
				function(ReflectionClass $class) {
					$reflections = $class->getOwnConstants();
					ksort($reflections);
					return $reflections;
				},
				array_merge($this->getParentClasses(), $this->getInterfaces())
			)
		);
	}

	/**
	 * Returns an array of inherited properties from parent classes grouped by the declaring class name.
	 *
	 * @return array
	 */
	public function getInheritedProperties()
	{
		$properties = array();
		$allProperties = array_flip(array_map(function($property) {
			return $property->getName();
		}, $this->getOwnProperties()));

		foreach ($this->getParentClasses() as $class) {
			$inheritedProperties = array();
			foreach ($class->getOwnProperties() as $property) {
				if (!array_key_exists($property->getName(), $allProperties) && !$property->isPrivate()) {
					$inheritedProperties[$property->getName()] = $property;
					$allProperties[$property->getName()] = null;
				}
			}

			if (!empty($inheritedProperties)) {
				ksort($inheritedProperties);
				$properties[$class->getName()] = array_values($inheritedProperties);
			}
		}

		return $properties;
	}

	/**
	 * Returns an array of inherited magic properties from parent classes grouped by the declaring class name.
	 *
	 * @return array
	 */
	public function getInheritedMagicProperties()
	{
		$properties = array();
		$allProperties = array_flip(array_map(function($property) {
			return $property->getName();
		}, $this->getOwnMagicProperties()));

		foreach ($this->getParentClasses() as $class) {
			$inheritedProperties = array();
			foreach ($class->getOwnMagicProperties() as $property) {
				if (!array_key_exists($property->getName(), $allProperties)) {
					$inheritedProperties[$property->getName()] = $property;
					$allProperties[$property->getName()] = null;
				}
			}

			if (!empty($inheritedProperties)) {
				ksort($inheritedProperties);
				$properties[$class->getName()] = array_values($inheritedProperties);
			}
		}

		return $properties;
	}

	/**
	 * Returns an array of used properties from used traits grouped by the declaring trait name.
	 *
	 * @return array
	 */
	public function getUsedProperties()
	{
		$properties = array();
		$allProperties = array_flip(array_map(function($property) {
			return $property->getName();
		}, $this->getOwnProperties()));

		foreach ($this->getTraits() as $trait) {
			$usedProperties = array();
			foreach ($trait->getOwnProperties() as $property) {
				if (!array_key_exists($property->getName(), $allProperties)) {
					$usedProperties[$property->getName()] = $property;
					$allProperties[$property->getName()] = null;
				}
			}

			if (!empty($usedProperties)) {
				ksort($usedProperties);
				$properties[$trait->getName()] = array_values($usedProperties);
			}
		}

		return $properties;
	}

	/**
	 * Returns an array of used magic properties from used traits grouped by the declaring trait name.
	 *
	 * @return array
	 */
	public function getUsedMagicProperties()
	{
		$properties = array();
		$allProperties = array_flip(array_map(function($property) {
			return $property->getName();
		}, $this->getOwnMagicProperties()));

		foreach ($this->getTraits() as $trait) {
			$usedProperties = array();
			foreach ($trait->getOwnMagicProperties() as $property) {
				if (!array_key_exists($property->getName(), $allProperties)) {
					$usedProperties[$property->getName()] = $property;
					$allProperties[$property->getName()] = null;
				}
			}

			if (!empty($usedProperties)) {
				ksort($usedProperties);
				$properties[$trait->getName()] = array_values($usedProperties);
			}
		}

		return $properties;
	}

	/**
	 * Checks if there is a property of the given name.
	 *
	 * @param string $propertyName Property name
	 * @return boolean
	 */
	public function hasProperty($propertyName)
	{
		if (null === $this->properties) {
			$this->getProperties();
		}

		return isset($this->properties[$propertyName]);
	}

	/**
	 * Checks if there is a property of the given name.
	 *
	 * @param string $propertyName Property name
	 * @return boolean
	 */
	public function hasOwnProperty($propertyName)
	{
		if (null === $this->ownProperties) {
			$this->getOwnProperties();
		}

		return isset($this->ownProperties[$propertyName]);
	}

	/**
	 * Checks if there is a property of the given name.
	 *
	 * @param string $propertyName Property name
	 * @return boolean
	 */
	public function hasTraitProperty($propertyName)
	{
		$properties = $this->getTraitProperties();
		return isset($properties[$propertyName]);
	}

	/**
	 * Checks if there is a method of the given name.
	 *
	 * @param string $methodName Method name
	 * @return boolean
	 */
	public function hasMethod($methodName)
	{
		if (null === $this->methods) {
			$this->getMethods();
		}

		return isset($this->methods[$methodName]);
	}

	/**
	 * Checks if there is a method of the given name.
	 *
	 * @param string $methodName Method name
	 * @return boolean
	 */
	public function hasOwnMethod($methodName)
	{
		if (null === $this->ownMethods) {
			$this->getOwnMethods();
		}

		return isset($this->ownMethods[$methodName]);
	}

	/**
	 * Checks if there is a method of the given name.
	 *
	 * @param string $methodName Method name
	 * @return boolean
	 */
	public function hasTraitMethod($methodName)
	{
		$methods = $this->getTraitMethods();
		return isset($methods[$methodName]);
	}

	/**
	 * Returns if the class is valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		if ($this->reflection instanceof TokenReflection\Invalid\ReflectionClass) {
			return false;
		}

		return true;
	}

	/**
	 * Returns if the class should be documented.
	 *
	 * @return boolean
	 */
	public function isDocumented()
	{
		if (null === $this->isDocumented && parent::isDocumented()) {
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
