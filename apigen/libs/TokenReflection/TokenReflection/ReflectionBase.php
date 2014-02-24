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

use TokenReflection\Exception, TokenReflection\Stream\StreamBase as Stream;

/**
 * Basic abstract TokenReflection class.
 *
 * A common ancestor of ReflectionElement and ReflectionFile.
 */
abstract class ReflectionBase implements IReflection
{
	/**
	 * Class method cache.
	 *
	 * @var array
	 */
	private static $methodCache = array();

	/**
	 * Object name (FQN).
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Docblock definition.
	 *
	 * @var \TokenReflection\ReflectionAnnotation|boolean
	 */
	protected $docComment;

	/**
	 * Parsed docblock definition.
	 *
	 * @var array
	 */
	private $parsedDocComment;

	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Constructor.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\Broker $broker Reflection broker
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 */
	public function __construct(Stream $tokenStream, Broker $broker, IReflection $parent = null)
	{
		$this->broker = $broker;

		$this->parseStream($tokenStream, $parent);
	}

	/**
	 * Parses the token substream.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 */
	abstract protected function parseStream(Stream $tokenStream, IReflection $parent = null);

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
	 * Returns the appropriate docblock definition.
	 *
	 * @return string|boolean
	 */
	public function getDocComment()
	{
		return $this->docComment->getDocComment();
	}

	/**
	 * Checks if there is a particular annotation.
	 *
	 * @param string $name Annotation name
	 * @return boolean
	 */
	final public function hasAnnotation($name)
	{
		return $this->docComment->hasAnnotation($name);
	}

	/**
	 * Returns a particular annotation value.
	 *
	 * @param string $name Annotation name
	 * @return string|array|null
	 */
	final public function getAnnotation($name)
	{
		return $this->docComment->getAnnotation($name);
	}

	/**
	 * Returns all annotations.
	 *
	 * @return array
	 */
	final public function getAnnotations()
	{
		return $this->docComment->getAnnotations();
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
	 * Returns if the reflection object is internal.
	 *
	 * Always returns false - everything is user defined.
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
	 * Always returns true - everything is user defined.
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
		return $this->hasAnnotation('deprecated');
	}

	/**
	 * Returns the appropriate source code part.
	 *
	 * @return string
	 */
	abstract public function getSource();

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
	 * Magic __get method.
	 *
	 * @param string $key Variable name
	 * @return mixed
	 */
	final public function __get($key)
	{
		return self::get($this, $key);
	}

	/**
	 * Magic __isset method.
	 *
	 * @param string $key Variable name
	 * @return boolean
	 */
	final public function __isset($key)
	{
		return self::exists($this, $key);
	}

	/**
	 * Magic __get method helper.
	 *
	 * @param \TokenReflection\IReflection $object Reflection object
	 * @param string $key Variable name
	 * @return mixed
	 * @throws \TokenReflection\Exception\RuntimeException If the requested parameter does not exist.
	 */
	final public static function get(IReflection $object, $key)
	{
		if (!empty($key)) {
			$className = get_class($object);
			if (!isset(self::$methodCache[$className])) {
				self::$methodCache[$className] = array_flip(get_class_methods($className));
			}

			$methods = self::$methodCache[$className];
			$key2 = ucfirst($key);
			if (isset($methods['get' . $key2])) {
				return $object->{'get' . $key2}();
			} elseif (isset($methods['is' . $key2])) {
				return $object->{'is' . $key2}();
			}
		}

		throw new Exception\RuntimeException(sprintf('Cannot read property "%s".', $key), Exception\RuntimeException::DOES_NOT_EXIST);
	}

	/**
	 * Magic __isset method helper.
	 *
	 * @param \TokenReflection\IReflection $object Reflection object
	 * @param string $key Variable name
	 * @return boolean
	 */
	final public static function exists(IReflection $object, $key)
	{
		try {
			self::get($object, $key);
			return true;
		} catch (RuntimeException $e) {
			return false;
		}
	}
}
