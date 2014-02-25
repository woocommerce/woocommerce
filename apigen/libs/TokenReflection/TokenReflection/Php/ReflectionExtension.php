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
use Reflector, ReflectionExtension as InternalReflectionExtension;

/**
 * Reflection of a not tokenized but defined extension.
 *
 * Descendant of the internal reflection with additional features.
 */
class ReflectionExtension extends InternalReflectionExtension implements IReflection, TokenReflection\IReflectionExtension
{
	/**
	 * Defined classes.
	 *
	 * @var array
	 */
	private $classes;

	/**
	 * Defined constants.
	 *
	 * @var array
	 */
	private $constants;

	/**
	 * Defined functions.
	 *
	 * @var array
	 */
	private $functions;

	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Constructor.
	 *
	 * @param string $name Extension name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($name, Broker $broker)
	{
		parent::__construct($name);
		$this->broker = $broker;
	}

	/**
	 * Returns if the constant is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return true;
	}

	/**
	 * Returns if the constant is user defined.
	 *
	 * @return boolean
	 */
	public function isUserDefined()
	{
		return false;
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
	 * Returns a class reflection.
	 *
	 * @param string $name Class name
	 * @return \TokenReflection\IReflectionClass|null
	 */
	public function getClass($name)
	{
		$classes = $this->getClasses();
		return isset($classes[$name]) ? $classes[$name] : null;
	}

	/**
	 * Returns classes defined by this extension.
	 *
	 * @return array
	 */
	public function getClasses()
	{
		if (null === $this->classes) {
			$broker = $this->broker;
			$this->classes = array_map(function($className) use ($broker) {
				return $broker->getClass($className);
			}, $this->getClassNames());
		}

		return $this->classes;
	}

	/**
	 * Returns a constant value.
	 *
	 * @param string $name Constant name
	 * @return mixed|false
	 */
	public function getConstant($name)
	{
		$constants = $this->getConstants();
		return isset($constants[$name]) ? $constants[$name] : false;
	}

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $name Constant name
	 * @return \TokenReflection\IReflectionConstant
	 */
	public function getConstantReflection($name)
	{
		$constants = $this->getConstantReflections();
		return isset($constants[$name]) ? $constants[$name] : null;
	}

	/**
	 * Returns reflections of defined constants.
	 *
	 * @return array
	 */
	public function getConstantReflections()
	{
		if (null === $this->constants) {
			$broker = $this->broker;
			$this->constants = array_map(function($constantName) use ($broker) {
				return $broker->getConstant($constantName);
			}, array_keys($this->getConstants()));
		}

		return $this->constants;
	}

	/**
	 * Returns a function reflection.
	 *
	 * @param string $name Function name
	 * @return \TokenReflection\IReflectionFunction
	 */
	public function getFunction($name)
	{
		$functions = $this->getFunctions();
		return isset($functions[$name]) ? $functions[$name] : null;
	}

	/**
	 * Returns functions defined by this extension.
	 *
	 * @return array
	 */
	public function getFunctions()
	{
		if (null === $this->functions) {
			$broker = $this->broker;
			$this->classes = array_map(function($functionName) use ($broker) {
				return $broker->getFunction($functionName);
			}, array_keys(parent::getFunctions()));
		}

		return $this->functions;
	}

	/**
	 * Returns names of functions defined by this extension.
	 *
	 * @return array
	 */
	public function getFunctionNames()
	{
		return array_keys($this->getFunctions());
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
	 * Creates a reflection instance.
	 *
	 * @param \ReflectionClass $internalReflection Internal reflection instance
	 * @param \TokenReflection\Broker $broker Reflection broker instance
	 * @return \TokenReflection\Php\ReflectionExtension
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid internal reflection object was provided.
	 */
	public static function create(Reflector $internalReflection, Broker $broker)
	{
		static $cache = array();

		if (!$internalReflection instanceof InternalReflectionExtension) {
			throw new Exception\RuntimeException('Invalid reflection instance provided, ReflectionExtension expected.', Exception\RuntimeException::INVALID_ARGUMENT);
		}

		if (!isset($cache[$internalReflection->getName()])) {
			$cache[$internalReflection->getName()] = new self($internalReflection->getName(), $broker);
		}

		return $cache[$internalReflection->getName()];
	}
}
