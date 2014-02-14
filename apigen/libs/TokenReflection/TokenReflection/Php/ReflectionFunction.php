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
use Reflector, ReflectionFunction as InternalReflectionFunction, ReflectionParameter as InternalReflectionParameter;

/**
 * Reflection of a not tokenized but defined function.
 *
 * Descendant of the internal reflection with additional features.
 */
class ReflectionFunction extends InternalReflectionFunction implements IReflection, TokenReflection\IReflectionFunction
{
	/**
	 * Function parameter reflections.
	 *
	 * @var array
	 */
	private $parameters;

	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Constructor.
	 *
	 * @param string $functionName Function name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($functionName, Broker $broker)
	{
		parent::__construct($functionName);
		$this->broker = $broker;
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \TokenReflection\IReflectionExtension
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
	 * Returns if the current reflection comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized()
	{
		return false;
	}

	/**
	 * Returns a particular parameter.
	 *
	 * @param integer|string $parameter Parameter name or position
	 * @return \TokenReflection\Php\ReflectionParameter
	 * @throws \TokenReflection\Exception\RuntimeException If there is no parameter of the given name.
	 * @throws \TokenReflection\Exception\RuntimeException If there is no parameter at the given position.
	 */
	public function getParameter($parameter)
	{
		$parameters = $this->getParameters();

		if (is_numeric($parameter)) {
			if (!isset($parameters[$parameter])) {
				throw new Exception\RuntimeException(sprintf('There is no parameter at position "%d".', $parameter), Exception\RuntimeException::DOES_NOT_EXIST, $this);
			}

			return $parameters[$parameter];
		} else {
			foreach ($parameters as $reflection) {
				if ($reflection->getName() === $parameter) {
					return $reflection;
				}
			}

			throw new Exception\RuntimeException(sprintf('There is no parameter "%s".', $parameter), Exception\RuntimeException::DOES_NOT_EXIST, $this);
		}
	}

	/**
	 * Returns function parameters.
	 *
	 * @return array
	 */
	public function getParameters()
	{
		if (null === $this->parameters) {
			$broker = $this->broker;
			$parent = $this;
			$this->parameters = array_map(function(InternalReflectionParameter $parameter) use ($broker, $parent) {
				return ReflectionParameter::create($parameter, $broker, $parent);
			}, parent::getParameters());
		}

		return $this->parameters;
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
	 * Returns the function/method as closure.
	 *
	 * @return \Closure
	 */
	public function getClosure()
	{
		if (PHP_VERSION >= 50400) {
			return parent::getClosure();
		} else {
			$that = $this;
			return function() use ($that) {
				return $that->invokeArgs(func_get_args());
			};
		}
	}

	/**
	 * Returns the closure scope class.
	 *
	 * @return string|null
	 */
	public function getClosureScopeClass()
	{
		return PHP_VERSION >= 50400 ? parent::getClosureScopeClass() : null;
	}

	/**
	 * Returns this pointer bound to closure.
	 *
	 * @return null
	 */
	public function getClosureThis()
	{
		return PHP_VERSION >= 50400 ? parent::getClosureThis() : null;
	}

	/**
	 * Returns if the function definition is valid.
	 *
	 * Internal functions are always valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return true;
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return $this->getName() . '()';
	}

	/**
	 * Creates a reflection instance.
	 *
	 * @param \ReflectionClass $internalReflection Internal reflection instance
	 * @param \TokenReflection\Broker $broker Reflection broker instance
	 * @return \TokenReflection\Php\ReflectionFunction
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid internal reflection object was provided.
	 */
	public static function create(Reflector $internalReflection, Broker $broker)
	{
		if (!$internalReflection instanceof InternalReflectionFunction) {
			throw new Exception\RuntimeException('Invalid reflection instance provided, ReflectionFunction expected.', Exception\RuntimeException::INVALID_ARGUMENT);
		}

		return $broker->getFunction($internalReflection->getName());
	}
}