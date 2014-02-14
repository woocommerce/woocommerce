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
 * Base abstract class for tokenized function and method.
 */
abstract class ReflectionFunctionBase extends ReflectionElement implements IReflectionFunctionBase
{
	/**
	 * Function/method namespace name.
	 *
	 * @var string
	 */
	protected $namespaceName;

	/**
	 * Determines if the function/method returns its value as reference.
	 *
	 * @var boolean
	 */
	private $returnsReference = false;

	/**
	 * Parameters.
	 *
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Static variables defined within the function/method.
	 *
	 * @var array
	 */
	private $staticVariables = array();

	/**
	 * Definitions of static variables defined within the function/method.
	 *
	 * @var array
	 */
	private $staticVariablesDefinition = array();

	/**
	 * Returns the name (FQN).
	 *
	 * @return string
	 */
	public function getName()
	{
		if (null !== $this->namespaceName && ReflectionNamespace::NO_NAMESPACE_NAME !== $this->namespaceName) {
			return $this->namespaceName . '\\' . $this->name;
		}

		return $this->name;
	}

	/**
	 * Returns the unqualified name (UQN).
	 *
	 * @return string
	 */
	public function getShortName()
	{
		return $this->name;
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
	 * Returns if the function/method is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace()
	{
		return '' !== $this->getNamespaceName();
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
	 * Returns this pointer bound to closure.
	 *
	 * @return null
	 */
	public function getClosureThis()
	{
		return null;
	}

	/**
	 * Returns the closure scope class.
	 *
	 * @return string|null
	 */
	public function getClosureScopeClass()
	{
		return null;
	}

	/**
	 * Returns if the function/method returns its value as reference.
	 *
	 * @return boolean
	 */
	public function returnsReference()
	{
		return $this->returnsReference;
	}

	/**
	 * Returns a particular function/method parameter.
	 *
	 * @param integer|string $parameter Parameter name or position
	 * @return \TokenReflection\ReflectionParameter
	 * @throws \TokenReflection\Exception\RuntimeException If there is no parameter of the given name.
	 * @throws \TokenReflection\Exception\RuntimeException If there is no parameter at the given position.
	 */
	public function getParameter($parameter)
	{
		if (is_numeric($parameter)) {
			if (!isset($this->parameters[$parameter])) {
				throw new Exception\RuntimeException(sprintf('There is no parameter at position "%d".', $parameter), Exception\RuntimeException::DOES_NOT_EXIST, $this);
			}
			return $this->parameters[$parameter];
		} else {
			foreach ($this->parameters as $reflection) {
				if ($reflection->getName() === $parameter) {
					return $reflection;
				}
			}

			throw new Exception\RuntimeException(sprintf('There is no parameter "%s".', $parameter), Exception\RuntimeException::DOES_NOT_EXIST, $this);
		}
	}

	/**
	 * Returns parameters.
	 *
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Returns the number of parameters.
	 *
	 * @return integer
	 */
	public function getNumberOfParameters()
	{
		return count($this->parameters);
	}

	/**
	 * Returns the number of required parameters.
	 *
	 * @return integer
	 */
	public function getNumberOfRequiredParameters()
	{
		$count = 0;
		array_walk($this->parameters, function(ReflectionParameter $parameter) use (&$count) {
			if (!$parameter->isOptional()) {
				$count++;
			}
		});
		return $count;
	}

	/**
	 * Returns static variables.
	 *
	 * @return array
	 */
	public function getStaticVariables()
	{
		if (empty($this->staticVariables) && !empty($this->staticVariablesDefinition)) {
			foreach ($this->staticVariablesDefinition as $variableName => $variableDefinition) {
				$this->staticVariables[$variableName] = Resolver::getValueDefinition($variableDefinition, $this);
			}
		}

		return $this->staticVariables;
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
	 * Creates aliases to parameters.
	 *
	 * @throws \TokenReflection\Exception\RuntimeException When called on a ReflectionFunction instance.
	 */
	protected final function aliasParameters()
	{
		if (!$this instanceof ReflectionMethod) {
			throw new Exception\RuntimeException('Only method parameters can be aliased.', Exception\RuntimeException::UNSUPPORTED, $this);
		}

		foreach ($this->parameters as $index => $parameter) {
			$this->parameters[$index] = $parameter->alias($this);
		}
	}

	/**
	 * Parses if the function/method returns its value as reference.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionFunctionBase
	 * @throws \TokenReflection\Exception\ParseException If could not be determined if the function\method returns its value by reference.
	 */
	final protected function parseReturnsReference(Stream $tokenStream)
	{
		if (!$tokenStream->is(T_FUNCTION)) {
			throw new Exception\ParseException($this, $tokenStream, 'Could not find the function keyword.', Exception\ParseException::UNEXPECTED_TOKEN);
		}

		$tokenStream->skipWhitespaces(true);

		$type = $tokenStream->getType();

		if ('&' === $type) {
			$this->returnsReference = true;
			$tokenStream->skipWhitespaces(true);
		} elseif (T_STRING !== $type) {
			throw new Exception\ParseException($this, $tokenStream, 'Unexpected token found.', Exception\ParseException::UNEXPECTED_TOKEN);
		}

		return $this;
	}

	/**
	 * Parses the function/method name.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionMethod
	 * @throws \TokenReflection\Exception\ParseException If the class name could not be determined.
	 */
	final protected function parseName(Stream $tokenStream)
	{
		$this->name = $tokenStream->getTokenValue();

		$tokenStream->skipWhitespaces(true);

		return $this;
	}

	/**
	 * Parses child reflection objects from the token stream.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionElement
	 */
	final protected function parseChildren(Stream $tokenStream, IReflection $parent)
	{
		return $this
			->parseParameters($tokenStream)
			->parseStaticVariables($tokenStream);
	}

	/**
	 * Parses function/method parameters.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionFunctionBase
	 * @throws \TokenReflection\Exception\ParseException If parameters could not be parsed.
	 */
	final protected function parseParameters(Stream $tokenStream)
	{
		if (!$tokenStream->is('(')) {
			throw new Exception\ParseException($this, $tokenStream, 'Could find the start token.', Exception\ParseException::UNEXPECTED_TOKEN);
		}

		static $accepted = array(T_NS_SEPARATOR => true, T_STRING => true, T_ARRAY => true, T_CALLABLE => true, T_VARIABLE => true, '&' => true);

		$tokenStream->skipWhitespaces(true);

		while (null !== ($type = $tokenStream->getType()) && ')' !== $type) {
			if (isset($accepted[$type])) {
				$parameter = new ReflectionParameter($tokenStream, $this->getBroker(), $this);
				$this->parameters[] = $parameter;
			}

			if ($tokenStream->is(')')) {
				break;
			}

			$tokenStream->skipWhitespaces(true);
		}

		$tokenStream->skipWhitespaces();

		return $this;
	}

	/**
	 * Parses static variables.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionFunctionBase
	 * @throws \TokenReflection\Exception\ParseException If static variables could not be parsed.
	 */
	final protected function parseStaticVariables(Stream $tokenStream)
	{
		$type = $tokenStream->getType();
		if ('{' === $type) {
			if ($this->getBroker()->isOptionSet(Broker::OPTION_PARSE_FUNCTION_BODY)) {
				$tokenStream->skipWhitespaces(true);

				while ('}' !== ($type = $tokenStream->getType())) {
					switch ($type) {
						case T_STATIC:
							$type = $tokenStream->skipWhitespaces(true)->getType();
							if (T_VARIABLE !== $type) {
								// Late static binding
								break;
							}

							while (T_VARIABLE === $type) {
								$variableName = $tokenStream->getTokenValue();
								$variableDefinition = array();

								$type = $tokenStream->skipWhitespaces(true)->getType();
								if ('=' === $type) {
									$type = $tokenStream->skipWhitespaces(true)->getType();
									$level = 0;
									while ($tokenStream->valid()) {
										switch ($type) {
											case '(':
											case '[':
											case '{':
											case T_CURLY_OPEN:
											case T_DOLLAR_OPEN_CURLY_BRACES:
												$level++;
												break;
											case ')':
											case ']':
											case '}':
												$level--;
												break;
											case ';':
											case ',':
												if (0 === $level) {
													break 2;
												}
											default:
												break;
										}

										$variableDefinition[] = $tokenStream->current();
										$type = $tokenStream->skipWhitespaces(true)->getType();
									}

									if (!$tokenStream->valid()) {
										throw new Exception\ParseException($this, $tokenStream, 'Invalid end of token stream.', Exception\ParseException::READ_BEYOND_EOS);
									}
								}

								$this->staticVariablesDefinition[substr($variableName, 1)] = $variableDefinition;

								if (',' === $type) {
									$type = $tokenStream->skipWhitespaces(true)->getType();
								} else {
									break;
								}
							}

							break;
						case T_FUNCTION:
							// Anonymous function -> skip to its end
							if (!$tokenStream->find('{')) {
								throw new Exception\ParseException($this, $tokenStream, 'Could not find beginning of the anonymous function.', Exception\ParseException::UNEXPECTED_TOKEN);
							}
							// Break missing intentionally
						case '{':
						case '[':
						case '(':
						case T_CURLY_OPEN:
						case T_DOLLAR_OPEN_CURLY_BRACES:
							$tokenStream->findMatchingBracket()->skipWhitespaces(true);
							break;
						default:
							$tokenStream->skipWhitespaces();
							break;
					}
				}
			} else {
				$tokenStream->findMatchingBracket();
			}
		} elseif (';' !== $type) {
			throw new Exception\ParseException($this, $tokenStream, 'Unexpected token found.', Exception\ParseException::UNEXPECTED_TOKEN);
		}

		return $this;
	}
}
