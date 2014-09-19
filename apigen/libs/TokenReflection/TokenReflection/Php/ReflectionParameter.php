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
use Reflector, ReflectionParameter as InternalReflectionParameter, ReflectionFunctionAbstract as InternalReflectionFunctionAbstract;

/**
 * Reflection of a not tokenized but defined method/function parameter.
 *
 * Descendant of the internal reflection with additional features.
 */
class ReflectionParameter extends InternalReflectionParameter implements IReflection, TokenReflection\IReflectionParameter
{
	/**
	 * Determined if the parameter (along with the function/method) is user defined.
	 *
	 * @var boolean
	 */
	private $userDefined;

	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Constructor.
	 *
	 * @param string|array $function Defining function/method
	 * @param string $paramName Parameter name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 * @param \ReflectionFunctionAbstract $parent Parent reflection object
	 */
	public function __construct($function, $paramName, Broker $broker, InternalReflectionFunctionAbstract $parent)
	{
		parent::__construct($function, $paramName);
		$this->broker = $broker;
		$this->userDefined = $parent->isUserDefined();
	}

	/**
	 * Returns the declaring class reflection.
	 *
	 * @return \TokenReflection\IReflectionClass
	 */
	public function getDeclaringClass()
	{
		$class = parent::getDeclaringClass();
		return $class ? ReflectionClass::create($class, $this->broker) : null;
	}

	/**
	 * Returns the declaring class name.
	 *
	 * @return string|null
	 */
	public function getDeclaringClassName()
	{
		$class = parent::getDeclaringClass();
		return $class ? $class->getName() : null;
	}

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases()
	{
		return $this->getDeclaringFunction()->getNamespaceAliases();
	}

	/**
	 * Returns the file name the reflection object is defined in.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->getDeclaringFunction()->getFileName();
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \TokenReflection\Php\ReflectionExtension
	 */
	public function getExtension()
	{
		return $this->getDeclaringFunction()->getExtension();
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
	 * Returns the declaring function reflection.
	 *
	 * @return \TokenReflection\Php\ReflectionFunction|\TokenReflection\Php\ReflectionMethod
	 */
	public function getDeclaringFunction()
	{
		$class = $this->getDeclaringClass();
		$function = parent::getDeclaringFunction();

		return $class ? $class->getMethod($function->getName()) : ReflectionFunction::create($function, $this->broker);
	}

	/**
	 * Returns the declaring function name.
	 *
	 * @return string|null
	 */
	public function getDeclaringFunctionName()
	{
		$function = parent::getDeclaringFunction();
		return $function ? $function->getName() : $function;
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
	 * Returns the part of the source code defining the paramter default value.
	 *
	 * @return string|null
	 */
	public function getDefaultValueDefinition()
	{
		$value = $this->getDefaultValue();
		return null === $value ? null : var_export($value, true);
	}

	/**
	 * Returns if the parameter expects a callback.
	 *
	 * @return boolean
	 */
	public function isCallable()
	{
		return PHP_VERSION >= 50400 && parent::isCallable();
	}

	/**
	 * Returns the original type hint as defined in the source code.
	 *
	 * @return string|null
	 */
	public function getOriginalTypeHint()
	{
		return !$this->isArray() && !$this->isCallable() ? $this->getClass() : null;
	}

	/**
	 * Returns the required class name of the value.
	 *
	 * @return string|null
	 */
	public function getClassName()
	{
		return $this->getClass() ? $this->getClass()->getName() : null;
	}

	/**
	 * Returns if the parameter is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return !$this->userDefined;
	}

	/**
	 * Returns if the parameter is user defined.
	 *
	 * @return boolean
	 */
	public function isUserDefined()
	{
		return $this->userDefined;
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
	 * Returns if the paramter value can be passed by value.
	 *
	 * @return boolean
	 */
	public function canBePassedByValue()
	{
		return method_exists($this, 'canBePassedByValue') ? parent::canBePassedByValue() : !$this->isPassedByReference();
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return str_replace('()', '($' . $this->getName() . ')', $this->getDeclaringFunction()->getPrettyName());
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
	 * @return \TokenReflection\Php\ReflectionParameter
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid internal reflection object was provided.
	 */
	public static function create(Reflector $internalReflection, Broker $broker)
	{
		static $cache = array();

		if (!$internalReflection instanceof InternalReflectionParameter) {
			throw new Exception\RuntimeException('Invalid reflection instance provided, ReflectionParameter expected.', Exception\RuntimeException::INVALID_ARGUMENT);
		}

		$class = $internalReflection->getDeclaringClass();
		$function = $internalReflection->getDeclaringFunction();

		$key = $class ? $class->getName() . '::' : '';
		$key .= $function->getName() . '(' . $internalReflection->getName() . ')';

		if (!isset($cache[$key])) {
			$cache[$key] = new self($class ? array($class->getName(), $function->getName()) : $function->getName(), $internalReflection->getName(), $broker, $function);
		}

		return $cache[$key];
	}
}
