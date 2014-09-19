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

use TokenReflection, TokenReflection\IReflectionConstant, TokenReflection\Broker, TokenReflection\ReflectionBase;

/**
 * Invalid constant reflection.
 *
 * The reflected constant is not unique.
 */
class ReflectionConstant extends ReflectionElement implements IReflectionConstant
{
	/**
	 * Constant name.
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
	 * @param string $name Constant name
	 * @param string $fileName Original definiton file name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($name, $fileName, Broker $broker)
	{
		$this->name = $name;
		$this->broker = $broker;
		$this->fileName = $fileName;
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
	 * Returns the declaring class reflection.
	 *
	 * @return null
	 */
	public function getDeclaringClass()
	{
		return null;
	}

	/**
	 * Returns the declaring class name.
	 *
	 * @return null
	 */
	public function getDeclaringClassName()
	{
		return null;
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
	 * Returns if the function/method is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace()
	{
		return false !== strpos($this->name, '\\');
	}

	/**
	 * Returns the PHP extension reflection.
	 *
	 * @return null
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
		throw new Exception\BrokerException($this->getBroker(), sprintf('Constant %s was not parsed from a file', $this->getPrettyName()), Exception\BrokerException::UNSUPPORTED);
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
	 * Returns the constant value.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return null;
	}

	/**
	 * Returns the part of the source code defining the constant value.
	 *
	 * @return string
	 */
	public function getValueDefinition()
	{
		return null;
	}

	/**
	 * Returns the originaly provided value definition.
	 *
	 * @return string
	 */
	public function getOriginalValueDefinition()
	{
		return null;
	}

	/**
	 * Returns if the constant is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return false;
	}

	/**
	 * Returns if the constant is user defined.
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
	 * Returns if the reflection subject is deprecated.
	 *
	 * @return boolean
	 */
	public function isDeprecated()
	{
		return false;
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return $this->name;
	}

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf(
			"Constant [ %s %s ] { %s }\n",
			gettype(null),
			$this->getName(),
			null
		);
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
	 * Returns if the constant definition is valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return false;
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
