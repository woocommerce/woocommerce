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

namespace TokenReflection;

/**
 * Common reflection classes interface.
 */
interface IReflectionClass extends IReflection
{
	/**
	 * Returns the unqualified name (UQN).
	 *
	 * @return string
	 */
	public function getShortName();

	/**
	 * Returns the namespace name.
	 *
	 * @return string
	 */
	public function getNamespaceName();

	/**
	 * Returns if the class is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace();

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases();

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \TokenReflection\IReflectionExtension|null
	 */
	public function getExtension();

	/**
	 * Returns the PHP extension name.
	 *
	 * @return string|null
	 */
	public function getExtensionName();

	/**
	 * Returns the file name the reflection object is defined in.
	 *
	 * @return string
	 */
	public function getFileName();

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return integer
	 */
	public function getStartLine();

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return integer
	 */
	public function getEndLine();

	/**
	 * Returns the appropriate docblock definition.
	 *
	 * @return string|boolean
	 */
	public function getDocComment();

	/**
	 * Returns modifiers.
	 *
	 * @return array
	 */
	public function getModifiers();

	/**
	 * Returns if the class is abstract.
	 *
	 * @return boolean
	 */
	public function isAbstract();

	/**
	 * Returns if the class is final.
	 *
	 * @return boolean
	 */
	public function isFinal();

	/**
	 * Returns if the class is an interface.
	 *
	 * @return boolean
	 */
	public function isInterface();

	/**
	 * Returns if the class is an exception or its descendant.
	 *
	 * @return boolean
	 */
	public function isException();

	/**
	 * Returns if objects of this class are cloneable.
	 *
	 * Introduced in PHP 5.4.
	 *
	 * @return boolean
	 * @see http://svn.php.net/viewvc/php/php-src/trunk/ext/reflection/php_reflection.c?revision=307971&view=markup#l4059
	 */
	public function isCloneable();

	/**
	 * Returns if the class is iterateable.
	 *
	 * Returns true if the class implements the Traversable interface.
	 *
	 * @return boolean
	 */
	public function isIterateable();

	/**
	 * Returns if the current class is a subclass of the given class.
	 *
	 * @param string|object $class Class name or reflection object
	 * @return boolean
	 */
	public function isSubclassOf($class);

	/**
	 * Returns the parent class reflection.
	 *
	 * @return \TokenReflection\IReflectionClass|null
	 */
	public function getParentClass();

	/**
	 * Returns the parent class name.
	 *
	 * @return string|null
	 */
	public function getParentClassName();

	/**
	 * Returns the parent classes reflections.
	 *
	 * @return array
	 */
	public function getParentClasses();

	/**
	 * Returns the parent classes names.
	 *
	 * @return array
	 */
	public function getParentClassNameList();

	/**
	 * Returns if the class implements the given interface.
	 *
	 * @param string|object $interface Interface name or reflection object
	 * @return boolean
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid object was provided as interface.
	 */
	public function implementsInterface($interface);

	/**
	 * Returns interface reflections.
	 *
	 * @return array
	 */
	public function getInterfaces();

	/**
	 * Returns interface names.
	 *
	 * @return array
	 */
	public function getInterfaceNames();

	/**
	 * Returns interface reflections implemented by this class, not its parents.
	 *
	 * @return array
	 */
	public function getOwnInterfaces();

	/**
	 * Returns names of interfaces implemented by this class, not its parents.
	 *
	 * @return array
	 */
	public function getOwnInterfaceNames();

	/**
	 * Returns the class constructor reflection.
	 *
	 * @return \TokenReflection\IReflectionMethod|null
	 */
	public function getConstructor();

	/**
	 * Returns the class desctructor reflection.
	 *
	 * @return \TokenReflection\IReflectionMethod|null
	 */
	public function getDestructor();

	/**
	 * Returns if the class implements the given method.
	 *
	 * @param string $name Method name
	 * @return boolean
	 */
	public function hasMethod($name);

	/**
	 * Returns a method reflection.
	 *
	 * @param string $name Method name
	 * @return \TokenReflection\IReflectionMethod
	 * @throws \TokenReflection\Exception\RuntimeException If the requested method does not exist.
	 */
	public function getMethod($name);

	/**
	 * Returns method reflections.
	 *
	 * @param integer $filter Methods filter
	 * @return array
	 */
	public function getMethods($filter = null);

	/**
	 * Returns if the class implements (and not its parents) the given method.
	 *
	 * @param string $name Method name
	 * @return boolean
	 */
	public function hasOwnMethod($name);

	/**
	 * Returns method reflections declared by this class, not its parents.
	 *
	 * @param integer $filter Methods filter
	 * @return array
	 */
	public function getOwnMethods($filter = null);

	/**
	 * Returns if the class imports the given method from traits.
	 *
	 * @param string $name Method name
	 * @return boolean
	 */
	public function hasTraitMethod($name);

	/**
	 * Returns method reflections imported from traits.
	 *
	 * @param integer $filter Methods filter
	 * @return array
	 */
	public function getTraitMethods($filter = null);

	/**
	 * Returns if the class defines the given constant.
	 *
	 * @param string $name Constant name.
	 * @return boolean
	 */
	public function hasConstant($name);

	/**
	 * Returns a constant value.
	 *
	 * @param string $name Constant name
	 * @return mixed
	 * @throws \TokenReflection\Exception\RuntimeException If the requested constant does not exist.
	 */
	public function getConstant($name);

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $name Constant name
	 * @return \TokenReflection\IReflectionConstant
	 * @throws \TokenReflection\Exception\RuntimeException If the requested constant does not exist.
	 */
	public function getConstantReflection($name);

	/**
	 * Returns an array of constant values.
	 *
	 * @return array
	 */
	public function getConstants();

	/**
	 * Returns constant reflections.
	 *
	 * @return array
	 */
	public function getConstantReflections();

	/**
	 * Returns if the class (and not its parents) defines the given constant.
	 *
	 * @param string $name Constant name.
	 * @return boolean
	 */
	public function hasOwnConstant($name);

	/**
	 * Returns values of constants declared by this class, not by its parents.
	 *
	 * @return array
	 */
	public function getOwnConstants();

	/**
	 * Returns constant reflections declared by this class, not by its parents.
	 *
	 * @return array
	 */
	public function getOwnConstantReflections();

	/**
	 * Returns if the class defines the given property.
	 *
	 * @param string $name Property name
	 * @return boolean
	 */
	public function hasProperty($name);

	/**
	 * Return a property reflection.
	 *
	 * @param string $name Property name
	 * @return \TokenReflection\ReflectionProperty
	 * @throws \TokenReflection\Exception\RuntimeException If the requested property does not exist.
	 */
	public function getProperty($name);

	/**
	 * Returns property reflections.
	 *
	 * @param integer $filter Properties filter
	 * @return array
	 */
	public function getProperties($filter = null);

	/**
	 * Returns if the class (and not its parents) defines the given property.
	 *
	 * @param string $name Property name
	 * @return boolean
	 */
	public function hasOwnProperty($name);

	/**
	 * Returns property reflections declared by this class, not its parents.
	 *
	 * @param integer $filter Properties filter
	 * @return array
	 */
	public function getOwnProperties($filter = null);

	/**
	 * Returns if the class imports the given property from traits.
	 *
	 * @param string $name Property name
	 * @return boolean
	 */
	public function hasTraitProperty($name);

	/**
	 * Returns property reflections imported from traits.
	 *
	 * @param integer $filter Properties filter
	 * @return array
	 */
	public function getTraitProperties($filter = null);

	/**
	 * Returns default properties.
	 *
	 * @return array
	 */
	public function getDefaultProperties();

	/**
	 * Returns static properties reflections.
	 *
	 * @return array
	 */
	public function getStaticProperties();

	/**
	 * Returns a value of a static property.
	 *
	 * @param string $name Property name
	 * @param mixed $default Default value
	 * @return mixed
	 * @throws \TokenReflection\Exception\RuntimeException If the requested static property does not exist.
	 * @throws \TokenReflection\Exception\RuntimeException If the requested static property is not accessible.
	 */
	public function getStaticPropertyValue($name, $default = null);

	/**
	 * Returns reflections of direct subclasses.
	 *
	 * @return array
	 */
	public function getDirectSubclasses();

	/**
	 * Returns names of direct subclasses.
	 *
	 * @return array
	 */
	public function getDirectSubclassNames();

	/**
	 * Returns reflections of indirect subclasses.
	 *
	 * @return array
	 */
	public function getIndirectSubclasses();

	/**
	 * Returns names of indirect subclasses.
	 *
	 * @return array
	 */
	public function getIndirectSubclassNames();

	/**
	 * Returns reflections of classes directly implementing this interface.
	 *
	 * @return array
	 */
	public function getDirectImplementers();

	/**
	 * Returns names of classes directly implementing this interface.
	 *
	 * @return array
	 */
	public function getDirectImplementerNames();

	/**
	 * Returns reflections of classes indirectly implementing this interface.
	 *
	 * @return array
	 */
	public function getIndirectImplementers();

	/**
	 * Returns names of classes indirectly implementing this interface.
	 *
	 * @return array
	 */
	public function getIndirectImplementerNames();

	/**
	 * Returns if it is possible to create an instance of this class.
	 *
	 * @return boolean
	 */
	public function isInstantiable();

	/**
	 * Returns traits used by this class.
	 *
	 * @return array
	 */
	public function getTraits();

	/**
	 * Returns traits used by this class and not its parents.
	 *
	 * @return array
	 */
	public function getOwnTraits();

	/**
	 * Returns names of used traits.
	 *
	 * @return array
	 */
	public function getTraitNames();

	/**
	 * Returns names of traits used by this class an not its parents.
	 *
	 * @return array
	 */
	public function getOwnTraitNames();

	/**
	 * Returns method aliases from traits.
	 *
	 * @return array
	 */
	public function getTraitAliases();

	/**
	 * Returns if the class uses a particular trait.
	 *
	 * @param \ReflectionClass|\TokenReflection\IReflectionClass|string $trait Trait reflection or name
	 * @return bool
	 */
	public function usesTrait($trait);

	/**
	 * Returns if the class is a trait.
	 *
	 * @return boolean
	 */
	public function isTrait();

	/**
	 * Returns if the given object is an instance of this class.
	 *
	 * @param object $object Instance
	 * @return boolean
	 * @throws \TokenReflection\Exception\RuntimeException If the provided argument is not an object.
	 */
	public function isInstance($object);

	/**
	 * Creates a new class instance without using a constructor.
	 *
	 * @return object
	 * @throws \TokenReflection\Exception\RuntimeException If the class inherits from an internal class.
	 */
	public function newInstanceWithoutConstructor();

	/**
	 * Creates a new instance using variable number of parameters.
	 *
	 * Use any number of constructor parameters as function parameters.
	 *
	 * @param mixed $args
	 * @return object
	 */
	public function newInstance($args);

	/**
	 * Creates a new instance using an array of parameters.
	 *
	 * @param array $args Array of constructor parameters
	 * @return object
	 * @throws \TokenReflection\Exception\RuntimeException If the required class does not exist.
	 */
	public function newInstanceArgs(array $args = array());

	/**
	 * Sets a static property value.
	 *
	 * @param string $name Property name
	 * @param mixed $value Property value
	 * @throws \TokenReflection\Exception\RuntimeException If the requested static property does not exist.
	 * @throws \TokenReflection\Exception\RuntimeException If the requested static property is not accessible.
	 */
	public function setStaticPropertyValue($name, $value);

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString();

	/**
	 * Returns if the class definition is complete.
	 *
	 * That means if there are no dummy classes among parents and implemented interfaces.
	 *
	 * @return boolean
	 */
	public function isComplete();
	/**
	 * Returns if the class definition is valid.
	 *
	 * That means that the source code is valid and the class name is unique within parsed files.
	 *
	 * @return boolean
	 */
	public function isValid();

	/**
	 * Returns if the class is deprecated.
	 *
	 * @return boolean
	 */
	public function isDeprecated();
}