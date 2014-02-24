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

namespace TokenReflection\Invalid;

use TokenReflection, TokenReflection\IReflectionFunction, TokenReflection\Exception, TokenReflection\Broker, TokenReflection\ReflectionBase;

/**
 * Invalid function reflection.
 *
 * The reflected function is not unique.
 */
class ReflectionFunction extends ReflectionElement implements IReflectionFunction
{
	/**
	 * Class name (FQN).
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Original definition file name.
	 *
	 * @var string
	 */
	private $fileName;

	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Constructor.
	 *
	 * @param string $name Function name
	 * @param string $fileName Original definiton file name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($name, $fileName, Broker $broker)
	{
		$this->name = ltrim($name, '\\');
		$this->broker = $broker;
		$this->fileName = $fileName;
	}

	/**
	 * Returns the name (FQN).
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the unqualified name (UQN).
	 *
	 * @return string
	 */
	public function getShortName()
	{
		$pos = strrpos($this->name, '\\');
		return false === $pos ? $this->name : substr($this->name, $pos + 1);
	}

	/**
	 * Returns the namespace name.
	 *
	 * @return string
	 */
	public function getNamespaceName()
	{
		$pos = strrpos($this->name, '\\');
		return false === $pos ? '' : substr($this->name, 0, $pos);
	}

	/**
	 * Returns if the class is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace()
	{
		return false !== strrpos($this->name, '\\');
	}

	/**
	 * Returns if the reflection object is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return false;
	}

	/**
	 * Returns if the reflection object is user defined.
	 *
	 * @return boolean
	 */
	public function isUserDefined()
	{
		return true;
	}

	/**
	 * Returns if the current reflection comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized()
	{
		return true;
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
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return $this->name . '()';
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return \TokenReflection\IReflectionExtension|null
	 */
	public function getExtension()
	{
		return null;
	}

	/**
	 * Returns the PHP extension name.
	 *
	 * @return false
	 */
	public function getExtensionName()
	{
		return false;
	}

	/**
	 * Returns the file name the reflection object is defined in.
	 *
	 * @return null
	 */
	public function getFileName()
	{
		return $this->fileName;
	}

	/**
	 * Returns a file reflection.
	 *
	 * @return \TokenReflection\ReflectionFile
	 * @throws \TokenReflection\Exception\RuntimeException If the file is not stored inside the broker
	 */
	public function getFileReflection()
	{
		throw new Exception\BrokerException($this->getBroker(), sprintf('Function was not parsed from a file', $this->getPrettyName()), Exception\BrokerException::UNSUPPORTED);
	}

	/**
	 * Returns the appropriate source code part.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return '';
	}

	/**
	 * Returns the start position in the file token stream.
	 *
	 * @return integer
	 */
	public function getStartPosition()
	{
		return -1;
	}

	/**
	 * Returns the end position in the file token stream.
	 *
	 * @return integer
	 */
	public function getEndPosition()
	{
		return -1;
	}

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return integer
	 */
	public function getStartLine()
	{
		return null;
	}

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return integer
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
	 * @return string|array|null
	 */
	public function getAnnotation($name)
	{
		return null;
	}

	/**
	 * Returns all annotations.
	 *
	 * @return array
	 */
	public function getAnnotations()
	{
		return array();
	}

	/**
	 * Returns if the function/method is a closure.
	 *
	 * @return boolean
	 */
	public function isClosure()
	{
		return false;
	}

	/**
	 * Returns if the function/method is deprecated.
	 *
	 * @return boolean
	 */
	public function isDeprecated()
	{
		return false;
	}

	/**
	 * Returns if the function/method returns its value as reference.
	 *
	 * @return boolean
	 */
	public function returnsReference()
	{
		return false;
	}

	/**
	 * Returns a function/method parameter.
	 *
	 * @param integer|string $parameter Parameter name or position
	 */
	public function getParameter($parameter)
	{
		if (is_numeric($parameter)) {
			throw new Exception\RuntimeException(sprintf('There is no parameter at position "%d".', $parameter), Exception\RuntimeException::DOES_NOT_EXIST, $this);
		} else {
			throw new Exception\RuntimeException(sprintf('There is no parameter "%s".', $parameter), Exception\RuntimeException::DOES_NOT_EXIST, $this);
		}
	}

	/**
	 * Returns function/method parameters.
	 *
	 * @return array
	 */
	public function getParameters(){
		return array();
	}

	/**
	 * Returns the number of parameters.
	 *
	 * @return integer
	 */
	public function getNumberOfParameters()
	{
		return 0;
	}

	/**
	 * Returns the number of required parameters.
	 *
	 * @return integer
	 */
	public function getNumberOfRequiredParameters()
	{
		return 0;
	}

	/**
	 * Returns static variables.
	 *
	 * @return array
	 */
	public function getStaticVariables()
	{
		return array();
	}

	/**
	 * Returns if the method is is disabled via the disable_functions directive.
	 *
	 * @return boolean
	 */
	public function isDisabled()
	{
		return false;
	}

	/**
	 * Calls the function.
	 *
	 * @return mixed
	 */
	public function invoke()
	{
		return $this->invokeArgs(array());
	}

	/**
	 * Calls the function.
	 *
	 * @param array $args Function parameter values
	 * @return mixed
	 */
	public function invokeArgs(array $args)
	{
		throw new Exception\RuntimeException('Cannot invoke invalid functions', Exception\RuntimeException::UNSUPPORTED, $this);
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
	 * Returns the function/method as closure.
	 *
	 * @return \Closure
	 */
	public function getClosure()
	{
		throw new Exception\RuntimeException('Cannot invoke invalid functions', Exception\RuntimeException::UNSUPPORTED, $this);
	}

	/**
	 * Returns the closure scope class.
	 *
	 * @return null
	 */
	public function getClosureScopeClass()
	{
		return null;
	}

	/**
	 * Returns this pointer bound to closure.
	 *
	 * @return null
	 */
	public function getClosureThis()
	{
		return null;
	}

	/**
	 * Returns if the function definition is valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return false;
	}

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf(
			"%sFunction [ <user> function %s%s ] {\n  @@ %s %d - %d\n}\n",
			$this->getDocComment() ? $this->getDocComment() . "\n" : '',
			$this->returnsReference() ? '&' : '',
			$this->getName(),
			$this->getFileName(),
			$this->getStartLine(),
			$this->getEndLine()
		);
	}

	/**
	 * Magic __get method.
	 *
	 * @param string $key Variable name
	 * @return mixed
	 */
	final public function __get($key)
	{
		return ReflectionBase::get($this, $key);
	}

	/**
	 * Magic __isset method.
	 *
	 * @param string $key Variable name
	 * @return boolean
	 */
	final public function __isset($key)
	{
		return ReflectionBase::exists($this, $key);
	}
}
