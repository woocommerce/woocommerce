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
use ReflectionParameter as InternalReflectionParameter;

/**
 * Tokenized function/method parameter reflection.
 */
class ReflectionParameter extends ReflectionElement implements IReflectionParameter
{
	/**
	 * The parameter requires an array as its value.
	 *
	 * @var string
	 */
	const ARRAY_TYPE_HINT = 'array';

	/**
	 * The parameter requires a callback definition as its value.
	 *
	 * @var string
	 */
	const CALLABLE_TYPE_HINT = 'callable';

	/**
	 * Declaring class name.
	 *
	 * @var string
	 */
	private $declaringClassName;

	/**
	 * Declaring function name.
	 *
	 * @var string
	 */
	private $declaringFunctionName;

	/**
	 * Parameter default value.
	 *
	 * @var mixed
	 */
	private $defaultValue;

	/**
	 * Parameter default value definition (part of the source code).
	 *
	 * @var array|string
	 */
	private $defaultValueDefinition = array();

	/**
	 * Defines a type hint (class name or array) of parameter values.
	 *
	 * @var string
	 */
	private $typeHint;

	/**
	 * Defines a type hint (class name, array or callable) of parameter values as it was defined.
	 *
	 * @var string
	 */
	private $originalTypeHint;

	/**
	 * Position of the parameter in the function/method.
	 *
	 * @var integer
	 */
	private $position;

	/**
	 * Determines if the parameter is optional.
	 *
	 * @var boolean
	 */
	private $isOptional;

	/**
	 * Determines if the value is passed by reference.
	 *
	 * @var boolean
	 */
	private $passedByReference = false;

	/**
	 * Returns the declaring class.
	 *
	 * @return \TokenReflection\ReflectionClass|null
	 */
	public function getDeclaringClass()
	{
		return null === $this->declaringClassName ? null : $this->getBroker()->getClass($this->declaringClassName);
	}

	/**
	 * Returns the declaring class name.
	 *
	 * @return string|null
	 */
	public function getDeclaringClassName()
	{
		return $this->declaringClassName;
	}

	/**
	 * Returns the declaring function.
	 *
	 * @return \TokenReflection\ReflectionFunctionBase
	 */
	public function getDeclaringFunction()
	{
		if (null !== $this->declaringClassName) {
			// Method parameter
			$class = $this->getBroker()->getClass($this->declaringClassName);
			if (null !== $class) {
				return $class->getMethod($this->declaringFunctionName);
			}
		} else {
			// Function parameter
			return $this->getBroker()->getFunction($this->declaringFunctionName);
		}
	}

	/**
	 * Returns the declaring function name.
	 *
	 * @return string
	 */
	public function getDeclaringFunctionName()
	{
		return $this->declaringFunctionName;
	}

	/**
	 * Returns the default value.
	 *
	 * @return mixed
	 * @throws \TokenReflection\Exception\RuntimeException If the property is not optional.
	 * @throws \TokenReflection\Exception\RuntimeException If the property has no default value.
	 */
	public function getDefaultValue()
	{
		if (!$this->isOptional()) {
			throw new Exception\RuntimeException('Property is not optional.', Exception\RuntimeException::UNSUPPORTED, $this);
		}

		if (is_array($this->defaultValueDefinition)) {
			if (0 === count($this->defaultValueDefinition)) {
				throw new Exception\RuntimeException('Property has no default value.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
			}

			$this->defaultValue = Resolver::getValueDefinition($this->defaultValueDefinition, $this);
			$this->defaultValueDefinition = Resolver::getSourceCode($this->defaultValueDefinition);
		}

		return $this->defaultValue;
	}

	/**
	 * Returns the part of the source code defining the parameter default value.
	 *
	 * @return string
	 */
	public function getDefaultValueDefinition()
	{
		return is_array($this->defaultValueDefinition) ? Resolver::getSourceCode($this->defaultValueDefinition) : $this->defaultValueDefinition;
	}

	/**
	 * Retutns if a default value for the parameter is available.
	 *
	 * @return boolean
	 */
	public function isDefaultValueAvailable()
	{
		return null !== $this->getDefaultValueDefinition();
	}

	/**
	 * Returns the position within all parameters.
	 *
	 * @return integer
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * Returns if the parameter expects an array.
	 *
	 * @return boolean
	 */
	public function isArray()
	{
		return $this->typeHint === self::ARRAY_TYPE_HINT;
	}

	/**
	 * Returns if the parameter expects a callback.
	 *
	 * @return boolean
	 */
	public function isCallable()
	{
		return $this->typeHint === self::CALLABLE_TYPE_HINT;
	}

	/**
	 * Returns the original type hint as defined in the source code.
	 *
	 * @return string|null
	 */
	public function getOriginalTypeHint()
	{
		return !$this->isArray() && !$this->isCallable() ? ltrim($this->originalTypeHint, '\\') : null;
	}

	/**
	 * Returns reflection of the required class of the value.
	 *
	 * @return \TokenReflection\IReflectionClass|null
	 */
	public function getClass()
	{
		$name = $this->getClassName();
		if (null === $name) {
			return null;
		}

		return $this->getBroker()->getClass($name);
	}

	/**
	 * Returns the required class name of the value.
	 *
	 * @return string|null
	 * @throws \TokenReflection\Exception\RuntimeException If the type hint class FQN could not be determined.
	 */
	public function getClassName()
	{
		if ($this->isArray() || $this->isCallable()) {
			return null;
		}

		if (null === $this->typeHint && null !== $this->originalTypeHint) {
			if (null !== $this->declaringClassName) {
				$parent = $this->getDeclaringClass();
				if (null === $parent) {
					throw new Exception\RuntimeException('Could not load class reflection.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
				}
			} else {
				$parent = $this->getDeclaringFunction();
				if (null === $parent || !$parent->isTokenized()) {
					throw new Exception\RuntimeException('Could not load function reflection.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
				}
			}

			$lTypeHint = strtolower($this->originalTypeHint);
			if ('parent' === $lTypeHint || 'self' === $lTypeHint) {
				if (null === $this->declaringClassName) {
					throw new Exception\RuntimeException('Parameter type hint cannot be "self" nor "parent" when not a method.', Exception\RuntimeException::UNSUPPORTED, $this);
				}

				if ('parent' === $lTypeHint) {
					if ($parent->isInterface() || null === $parent->getParentClassName()) {
						throw new Exception\RuntimeException('Class has no parent.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
					}

					$this->typeHint = $parent->getParentClassName();
				} else {
					$this->typeHint = $this->declaringClassName;
				}
			} else {
				$this->typeHint = ltrim(Resolver::resolveClassFQN($this->originalTypeHint, $parent->getNamespaceAliases(), $parent->getNamespaceName()), '\\');
			}
		}

		return $this->typeHint;
	}

	/**
	 * Returns if the the parameter allows NULL.
	 *
	 * @return boolean
	 */
	public function allowsNull()
	{
		if ($this->isArray() || $this->isCallable()) {
			return 'null' === strtolower($this->getDefaultValueDefinition());
		}

		return null === $this->originalTypeHint || !empty($this->defaultValueDefinition);
	}

	/**
	 * Returns if the parameter is optional.
	 *
	 * @return boolean
	 * @throws \TokenReflection\Exception\RuntimeException If it is not possible to determine if the parameter is optional.
	 */
	public function isOptional()
	{
		if (null === $this->isOptional) {
			$function = $this->getDeclaringFunction();
			if (null === $function) {
				throw new Exception\RuntimeException('Could not get the declaring function reflection.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
			}

			$this->isOptional = true;
			foreach (array_slice($function->getParameters(), $this->position) as $reflectionParameter) {
				if (!$reflectionParameter->isDefaultValueAvailable()) {
					$this->isOptional = false;
					break;
				}
			}
		}

		return $this->isOptional;
	}

	/**
	 * Returns if the parameter value is passed by reference.
	 *
	 * @return boolean
	 */
	public function isPassedByReference()
	{
		return $this->passedByReference;
	}

	/**
	 * Returns if the paramter value can be passed by value.
	 *
	 * @return boolean
	 */
	public function canBePassedByValue()
	{
		return !$this->isPassedByReference();
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return str_replace('()', '($' . $this->name . ')', $this->getDeclaringFunction()->getPrettyName());
	}

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if ($this->getClass()) {
			$hint = $this->getClassName();
		} elseif ($this->isArray()) {
			$hint = self::ARRAY_TYPE_HINT;
		} elseif ($this->isCallable()) {
			$hint = self::CALLABLE_TYPE_HINT;
		} else {
			$hint = '';
		}

		if (!empty($hint) && $this->allowsNull()) {
			$hint .= ' or NULL';
		}

		if ($this->isDefaultValueAvailable()) {
			$default = ' = ';
			if (null === $this->getDefaultValue()) {
				$default .= 'NULL';
			} elseif (is_array($this->getDefaultValue())) {
				$default .= 'Array';
			} elseif (is_bool($this->getDefaultValue())) {
				$default .= $this->getDefaultValue() ? 'true' : 'false';
			} elseif (is_string($this->getDefaultValue())) {
				$default .= sprintf("'%s'", str_replace("'", "\\'", $this->getDefaultValue()));
			} else {
				$default .= $this->getDefaultValue();
			}
		} else {
			$default = '';
		}

		return sprintf(
			'Parameter #%d [ <%s> %s%s$%s%s ]',
			$this->getPosition(),
			$this->isOptional() ? 'optional' : 'required',
			$hint ? $hint . ' ' : '',
			$this->isPassedByReference() ? '&' : '',
			$this->getName(),
			$default
		);
	}

	/**
	 * Exports a reflected object.
	 *
	 * @param \TokenReflection\Broker $broker Broker instance
	 * @param string $function Function name
	 * @param string $parameter Parameter name
	 * @param boolean $return Return the export instead of outputting it
	 * @return string|null
	 * @throws \TokenReflection\Exception\RuntimeException If requested parameter doesn't exist.
	 */
	public static function export(Broker $broker, $function, $parameter, $return = false)
	{
		$functionName = $function;
		$parameterName = $parameter;

		$function = $broker->getFunction($functionName);
		if (null === $function) {
			throw new Exception\RuntimeException(sprintf('Function %s() does not exist.', $functionName), Exception\RuntimeException::DOES_NOT_EXIST);
		}
		$parameter = $function->getParameter($parameterName);

		if ($return) {
			return $parameter->__toString();
		}

		echo $parameter->__toString();
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
	 * Creates a parameter alias for the given method.
	 *
	 * @param \TokenReflection\ReflectionMethod $parent New parent method
	 * @return \TokenReflection\ReflectionParameter
	 */
	public function alias(ReflectionMethod $parent)
	{
		$parameter = clone $this;

		$parameter->declaringClassName = $parent->getDeclaringClassName();
		$parameter->declaringFunctionName = $parent->getName();

		return $parameter;
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
		if (!$parent instanceof ReflectionFunctionBase) {
			throw new Exception\ParseException($this, $tokenStream, 'The parent object has to be an instance of TokenReflection\ReflectionFunctionBase.', Exception\ParseException::INVALID_PARENT);
		}

		// Declaring function name
		$this->declaringFunctionName = $parent->getName();

		// Position
		$this->position = count($parent->getParameters());

		// Declaring class name
		if ($parent instanceof ReflectionMethod) {
			$this->declaringClassName = $parent->getDeclaringClassName();
		}

		return parent::processParent($parent, $tokenStream);
	}

	/**
	 * Parses reflected element metadata from the token stream.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionParameter
	 */
	protected function parse(Stream $tokenStream, IReflection $parent)
	{
		return $this
			->parseTypeHint($tokenStream)
			->parsePassedByReference($tokenStream)
			->parseName($tokenStream)
			->parseDefaultValue($tokenStream);
	}

	/**
	 * Parses the type hint.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionParameter
	 * @throws \TokenReflection\Exception\ParseException If the type hint class name could not be determined.
	 */
	private function parseTypeHint(Stream $tokenStream)
	{
		$type = $tokenStream->getType();

		if (T_ARRAY === $type) {
			$this->typeHint = self::ARRAY_TYPE_HINT;
			$this->originalTypeHint = self::ARRAY_TYPE_HINT;
			$tokenStream->skipWhitespaces(true);
		} elseif (T_CALLABLE === $type) {
			$this->typeHint = self::CALLABLE_TYPE_HINT;
			$this->originalTypeHint = self::CALLABLE_TYPE_HINT;
			$tokenStream->skipWhitespaces(true);
		} elseif (T_STRING === $type || T_NS_SEPARATOR === $type) {
			$className = '';
			do {
				$className .= $tokenStream->getTokenValue();

				$tokenStream->skipWhitespaces(true);
				$type = $tokenStream->getType();
			} while (T_STRING === $type || T_NS_SEPARATOR === $type);

			if ('' === ltrim($className, '\\')) {
				throw new Exception\ParseException($this, $tokenStream, sprintf('Invalid class name definition: "%s".', $className), Exception\ParseException::LOGICAL_ERROR);
			}

			$this->originalTypeHint = $className;
		}

		return $this;
	}

	/**
	 * Parses if parameter value is passed by reference.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionParameter
	 */
	private function parsePassedByReference(Stream $tokenStream)
	{
		if ($tokenStream->is('&')) {
			$this->passedByReference = true;
			$tokenStream->skipWhitespaces(true);
		}

		return $this;
	}

	/**
	 * Parses the constant name.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionParameter
	 * @throws \TokenReflection\Exception\ParseException If the parameter name could not be determined.
	 */
	protected function parseName(Stream $tokenStream)
	{
		if (!$tokenStream->is(T_VARIABLE)) {
			throw new Exception\ParseException($this, $tokenStream, 'The parameter name could not be determined.', Exception\ParseException::UNEXPECTED_TOKEN);
		}

		$this->name = substr($tokenStream->getTokenValue(), 1);

		$tokenStream->skipWhitespaces(true);

		return $this;
	}

	/**
	 * Parses the parameter default value.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionParameter
	 * @throws \TokenReflection\Exception\ParseException If the default value could not be determined.
	 */
	private function parseDefaultValue(Stream $tokenStream)
	{
		if ($tokenStream->is('=')) {
			$tokenStream->skipWhitespaces(true);

			$level = 0;
			while (null !== ($type = $tokenStream->getType())) {
				switch ($type) {
					case ')':
						if (0 === $level) {
							break 2;
						}
					case '}':
					case ']':
						$level--;
						break;
					case '(':
					case '{':
					case '[':
						$level++;
						break;
					case ',':
						if (0 === $level) {
							break 2;
						}
						break;
					default:
						break;
				}

				$this->defaultValueDefinition[] = $tokenStream->current();
				$tokenStream->next();
			}

			if (')' !== $type && ',' !== $type) {
				throw new Exception\ParseException($this, $tokenStream, 'The property default value is not terminated properly. Expected "," or ")".', Exception\ParseException::UNEXPECTED_TOKEN);
			}
		}

		return $this;
	}
}
