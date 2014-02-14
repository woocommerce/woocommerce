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

/**
 * Common reflection parameter interface.
 */
interface IReflectionParameter extends IReflection
{
	/**
	 * Returns the declaring class.
	 *
	 * @return \TokenReflection\IReflectionClass|null
	 */
	public function getDeclaringClass();

	/**
	 * Returns the declaring class name.
	 *
	 * @return string|null
	 */
	public function getDeclaringClassName();

	/**
	 * Returns the declaring function.
	 *
	 * @return \TokenReflection\IReflectionFunctionBase
	 */
	public function getDeclaringFunction();

	/**
	 * Returns the declaring function name.
	 *
	 * @return string
	 */
	public function getDeclaringFunctionName();

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return integer
	 */
	public function getStartLine();

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return integer
	 */
	public function getEndLine();

	/**
	 * Returns the appropriate docblock definition.
	 *
	 * @return string|boolean
	 */
	public function getDocComment();

	/**
	 * Returns the default value.
	 *
	 * @return mixed
	 */
	public function getDefaultValue();

	/**
	 * Returns the part of the source code defining the paramter default value.
	 *
	 * @return string
	 */
	public function getDefaultValueDefinition();

	/**
	 * Retutns if a default value for the parameter is available.
	 *
	 * @return boolean
	 */
	public function isDefaultValueAvailable();

	/**
	 * Returns the position within all parameters.
	 *
	 * @return integer
	 */
	public function getPosition();

	/**
	 * Returns if the parameter expects an array.
	 *
	 * @return boolean
	 */
	public function isArray();

	/**
	 * Returns reflection of the required class of the value.
	 *
	 * @return \TokenReflection\IReflectionClass|null
	 */
	public function getClass();

	/**
	 * Returns the required class name of the value.
	 *
	 * @return string|null
	 */
	public function getClassName();

	/**
	 * Returns if the the parameter allows NULL.
	 *
	 * @return boolean
	 */
	public function allowsNull();

	/**
	 * Returns if the parameter is optional.
	 *
	 * @return boolean
	 */
	public function isOptional();

	/**
	 * Returns if the parameter value is passed by reference.
	 *
	 * @return boolean
	 */
	public function isPassedByReference();

	/**
	 * Returns if the paramter value can be passed by value.
	 *
	 * @return boolean
	 */
	public function canBePassedByValue();

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString();
}
