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
use ReflectionFunction as InternalReflectionFunction;

/**
 * Tokenized function reflection.
 */
class ReflectionFunction extends ReflectionFunctionBase implements IReflectionFunction
{
	/**
	 * Imported namespace/class aliases.
	 *
	 * @var array
	 */
	private $aliases = array();

	/**
	 * Returns if the function is is disabled via the disable_functions directive.
	 *
	 * @return boolean
	 */
	public function isDisabled()
	{
		return $this->hasAnnotation('disabled');
	}

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$parameters = '';
		if ($this->getNumberOfParameters() > 0) {
			$buffer = '';
			foreach ($this->getParameters() as $parameter) {
				$buffer .= "\n    " . $parameter->__toString();
			}
			$parameters = sprintf(
				"\n\n  - Parameters [%d] {%s\n  }",
				$this->getNumberOfParameters(),
				$buffer
			);
		}
		return sprintf(
			"%sFunction [ <user> function %s%s ] {\n  @@ %s %d - %d%s\n}\n",
			$this->getDocComment() ? $this->getDocComment() . "\n" : '',
			$this->returnsReference() ? '&' : '',
			$this->getName(),
			$this->getFileName(),
			$this->getStartLine(),
			$this->getEndLine(),
			$parameters
		);
	}

	/**
	 * Exports a reflected object.
	 *
	 * @param \TokenReflection\Broker $broker Broker instance
	 * @param string $function Function name
	 * @param boolean $return Return the export instead of outputting it
	 * @return string|null
	 * @throws \TokenReflection\Exception\RuntimeException If requested parameter doesn't exist.
	 */
	public static function export(Broker $broker, $function, $return = false)
	{
		$functionName = $function;

		$function = $broker->getFunction($functionName);
		if (null === $function) {
			throw new Exception\RuntimeException(sprintf('Function %s() does not exist.', $functionName), Exception\RuntimeException::DOES_NOT_EXIST);
		}

		if ($return) {
			return $function->__toString();
		}

		echo $function->__toString();
	}

	/**
	 * Calls the function.
	 *
	 * @return mixed
	 */
	public function invoke()
	{
		return $this->invokeArgs(func_get_args());
	}

	/**
	 * Calls the function.
	 *
	 * @param array $args Function parameter values
	 * @return mixed
	 * @throws \TokenReflection\Exception\RuntimeException If the required function does not exist.
	 */
	public function invokeArgs(array $args = array())
	{
		if (!function_exists($this->getName())) {
			throw new Exception\RuntimeException('Could not invoke function; function is not defined.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
		}

		return call_user_func_array($this->getName(), $args);
	}

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases()
	{
		return $this->aliases;
	}

	/**
	 * Returns the function/method as closure.
	 *
	 * @return \Closure
	 */
	public function getClosure()
	{
		if (!function_exists($this->getName())) {
			throw new Exception\RuntimeException('Could not invoke function; function is not defined.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
		}

		$that = $this;
		return function() use ($that) {
			return $that->invokeArgs(func_get_args());
		};
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
	 * Returns if the function definition is valid.
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
		if (!$parent instanceof ReflectionFileNamespace) {
			throw new Exception\ParseException($this, $tokenStream, 'The parent object has to be an instance of TokenReflection\ReflectionFileNamespace.', Exception\ParseException::INVALID_PARENT);
		}

		$this->namespaceName = $parent->getName();
		$this->aliases = $parent->getNamespaceAliases();
		return parent::processParent($parent, $tokenStream);
	}

	/**
	 * Parses reflected element metadata from the token stream.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionFunction
	 */
	protected function parse(Stream $tokenStream, IReflection $parent)
	{
		return $this
			->parseReturnsReference($tokenStream)
			->parseName($tokenStream);
	}
}
