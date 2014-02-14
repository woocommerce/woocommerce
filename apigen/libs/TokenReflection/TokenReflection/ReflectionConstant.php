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

use TokenReflection\Stream\StreamBase as Stream, TokenReflection\Exception;

/**
 * Tokenized constant reflection.
 */
class ReflectionConstant extends ReflectionElement implements IReflectionConstant
{
	/**
	 * Name of the declaring class.
	 *
	 * @var string
	 */
	private $declaringClassName;

	/**
	 * Constant namespace name.
	 *
	 * @var string
	 */
	private $namespaceName;

	/**
	 * Constant value.
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Constant value definition in tokens.
	 *
	 * @var array|string
	 */
	private $valueDefinition = array();

	/**
	 * Imported namespace/class aliases.
	 *
	 * @var array
	 */
	private $aliases = array();

	/**
	 * Returns the unqualified name (UQN).
	 *
	 * @return string
	 */
	public function getShortName()
	{
		$name = $this->getName();
		if (null !== $this->namespaceName && $this->namespaceName !== ReflectionNamespace::NO_NAMESPACE_NAME) {
			$name = substr($name, strlen($this->namespaceName) + 1);
		}

		return $name;
	}

	/**
	 * Returns the name of the declaring class.
	 *
	 * @return string|null
	 */
	public function getDeclaringClassName()
	{
		return $this->declaringClassName;
	}

	/**
	 * Returns a reflection of the declaring class.
	 *
	 * @return \TokenReflection\ReflectionClass|null
	 */
	public function getDeclaringClass()
	{
		if (null === $this->declaringClassName) {
			return null;
		}

		return $this->getBroker()->getClass($this->declaringClassName);
	}

	/**
	 * Returns the namespace name.
	 *
	 * @return string
	 */
	public function getNamespaceName()
	{
		return null === $this->namespaceName || $this->namespaceName === ReflectionNamespace::NO_NAMESPACE_NAME ? '' : $this->namespaceName;
	}

	/**
	 * Returns if the class is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace()
	{
		return '' !== $this->getNamespaceName();
	}

	/**
	 * Returns the constant value.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		if (is_array($this->valueDefinition)) {
			$this->value = Resolver::getValueDefinition($this->valueDefinition, $this);
			$this->valueDefinition = Resolver::getSourceCode($this->valueDefinition);
		}

		return $this->value;
	}

	/**
	 * Returns the constant value definition.
	 *
	 * @return string
	 */
	public function getValueDefinition()
	{
		return is_array($this->valueDefinition) ? Resolver::getSourceCode($this->valueDefinition) : $this->valueDefinition;
	}

	/**
	 * Returns the originaly provided value definition.
	 *
	 * @return string
	 */
	public function getOriginalValueDefinition()
	{
		return $this->valueDefinition;
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
			strtolower(gettype($this->getValue())),
			$this->getName(),
			$this->getValue()
		);
	}

	/**
	 * Exports a reflected object.
	 *
	 * @param \TokenReflection\Broker $broker Broker instance
	 * @param string|object|null $class Class name, class instance or null
	 * @param string $constant Constant name
	 * @param boolean $return Return the export instead of outputting it
	 * @return string|null
	 * @throws \TokenReflection\Exception\RuntimeException If requested parameter doesn't exist.
	 */
	public static function export(Broker $broker, $class, $constant, $return = false)
	{
		$className = is_object($class) ? get_class($class) : $class;
		$constantName = $constant;

		if (null === $className) {
			$constant = $broker->getConstant($constantName);
			if (null === $constant) {
				throw new Exception\RuntimeException('Constant does not exist.', Exception\RuntimeException::DOES_NOT_EXIST);
			}
		} else {
			$class = $broker->getClass($className);
			if ($class instanceof Invalid\ReflectionClass) {
				throw new Exception\RuntimeException('Class is invalid.', Exception\RuntimeException::UNSUPPORTED);
			} elseif ($class instanceof Dummy\ReflectionClass) {
				throw new Exception\RuntimeException('Class does not exist.', Exception\RuntimeException::DOES_NOT_EXIST, $class);
			}
			$constant = $class->getConstantReflection($constantName);
		}

		if ($return) {
			return $constant->__toString();
		}

		echo $constant->__toString();
	}

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases()
	{
		return null === $this->declaringClassName ? $this->aliases : $this->getDeclaringClass()->getNamespaceAliases();
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return null === $this->declaringClassName ? parent::getPrettyName() : sprintf('%s::%s', $this->declaringClassName, $this->name);
	}

	/**
	 * Returns if the constant definition is valid.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return true;
	}

	/**
	 * Processes the parent reflection object.
	 *
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionElement
	 * @throws \TokenReflection\Exception\ParseException If an invalid parent reflection object was provided.
	 */
	protected function processParent(IReflection $parent, Stream $tokenStream)
	{
		if ($parent instanceof ReflectionFileNamespace) {
			$this->namespaceName = $parent->getName();
			$this->aliases = $parent->getNamespaceAliases();
		} elseif ($parent instanceof ReflectionClass) {
			$this->declaringClassName = $parent->getName();
		} else {
			throw new Exception\ParseException($this, $tokenStream, sprintf('Invalid parent reflection provided: "%s".', get_class($parent)), Exception\ParseException::INVALID_PARENT);
		}

		return parent::processParent($parent, $tokenStream);
	}

	/**
	 * Find the appropriate docblock.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection
	 * @return \TokenReflection\ReflectionConstant
	 */
	protected function parseDocComment(Stream $tokenStream, IReflection $parent)
	{
		$position = $tokenStream->key() - 1;
		while ($position > 0 && !$tokenStream->is(T_CONST, $position)) {
			$position--;
		}

		$actual = $tokenStream->key();

		parent::parseDocComment($tokenStream->seek($position), $parent);

		$tokenStream->seek($actual);

		return $this;
	}

	/**
	 * Parses reflected element metadata from the token stream.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionConstant
	 */
	protected function parse(Stream $tokenStream, IReflection $parent)
	{
		if ($tokenStream->is(T_CONST)) {
			$tokenStream->skipWhitespaces(true);
		}

		if (false === $this->docComment->getDocComment()) {
			parent::parseDocComment($tokenStream, $parent);
		}

		return $this
			->parseName($tokenStream)
			->parseValue($tokenStream, $parent);
	}

	/**
	 * Parses the constant name.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionConstant
	 * @throws \TokenReflection\Exception\ParseReflection If the constant name could not be determined.
	 */
	protected function parseName(Stream $tokenStream)
	{
		if (!$tokenStream->is(T_STRING)) {
			throw new Exception\ParseException($this, $tokenStream, 'The constant name could not be determined.', Exception\ParseException::LOGICAL_ERROR);
		}

		if (null === $this->namespaceName || $this->namespaceName === ReflectionNamespace::NO_NAMESPACE_NAME) {
			$this->name = $tokenStream->getTokenValue();
		} else {
			$this->name = $this->namespaceName . '\\' . $tokenStream->getTokenValue();
		}

		$tokenStream->skipWhitespaces(true);

		return $this;
	}

	/**
	 * Parses the constant value.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionConstant
	 * @throws \TokenReflection\Exception\ParseException If the constant value could not be determined.
	 */
	private function parseValue(Stream $tokenStream, IReflection $parent)
	{
		if (!$tokenStream->is('=')) {
			throw new Exception\ParseException($this, $tokenStream, 'Could not find the definition start.', Exception\ParseException::UNEXPECTED_TOKEN);
		}

		$tokenStream->skipWhitespaces(true);

		static $acceptedTokens = array(
			'-' => true,
			'+' => true,
			T_STRING => true,
			T_NS_SEPARATOR => true,
			T_CONSTANT_ENCAPSED_STRING => true,
			T_DNUMBER => true,
			T_LNUMBER => true,
			T_DOUBLE_COLON => true,
			T_CLASS_C => true,
			T_DIR => true,
			T_FILE => true,
			T_FUNC_C => true,
			T_LINE => true,
			T_METHOD_C => true,
			T_NS_C => true,
			T_TRAIT_C => true
		);

		while (null !== ($type = $tokenStream->getType())) {
			if (T_START_HEREDOC === $type) {
				$this->valueDefinition[] = $tokenStream->current();
				while (null !== $type && T_END_HEREDOC !== $type) {
					$tokenStream->next();
					$this->valueDefinition[] = $tokenStream->current();
					$type = $tokenStream->getType();
				};
				$tokenStream->next();
			} elseif (isset($acceptedTokens[$type])) {
				$this->valueDefinition[] = $tokenStream->current();
				$tokenStream->next();
			} elseif ($tokenStream->isWhitespace(true)) {
				$tokenStream->skipWhitespaces(true);
			} else {
				break;
			}
		}

		if (empty($this->valueDefinition)) {
			throw new Exception\ParseException($this, $tokenStream, 'Value definition is empty.', Exception\ParseException::LOGICAL_ERROR);
		}

		$value = $tokenStream->getTokenValue();
		if (null === $type || (',' !== $value && ';' !== $value)) {
			throw new Exception\ParseException($this, $tokenStream, 'Invalid value definition.', Exception\ParseException::LOGICAL_ERROR);
		}

		return $this;
	}
}
