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
 * Common reflection namespace interface.
 */
interface IReflectionNamespace extends IReflection
{
	/**
	 * Returns if the namespace contains a class of the given name.
	 *
	 * @param string $className Class name
	 * @return boolean
	 */
	public function hasClass($className);

	/**
	 * Return a class reflection.
	 *
	 * @param string $className Class name
	 * @return \TokenReflection\IReflectionClass
	 */
	public function getClass($className);

	/**
	 * Returns class reflections.
	 *
	 * @return array
	 */
	public function getClasses();

	/**
	 * Returns class names (FQN).
	 *
	 * @return array
	 */
	public function getClassNames();

	/**
	 * Returns class unqualified names (UQN).
	 *
	 * @return array
	 */
	public function getClassShortNames();

	/**
	 * Returns if the namespace contains a constant of the given name.
	 *
	 * @param string $constantName Constant name
	 * @return boolean
	 */
	public function hasConstant($constantName);

	/**
	 * Returns a constant reflection.
	 *
	 * @param string $constantName Constant name
	 * @return \TokenReflection\IReflectionConstant
	 */
	public function getConstant($constantName);

	/**
	 * Returns constant reflections.
	 *
	 * @return array
	 */
	public function getConstants();

	/**
	 * Returns constant names (FQN).
	 *
	 * @return array
	 */
	public function getConstantNames();

	/**
	 * Returns constant unqualified names (UQN).
	 *
	 * @return array
	 */
	public function getConstantShortNames();

	/**
	 * Returns if the namespace contains a function of the given name.
	 *
	 * @param string $functionName Function name
	 * @return boolean
	 */
	public function hasFunction($functionName);

	/**
	 * Returns a function reflection.
	 *
	 * @param string $functionName Function name
	 * @return \TokenReflection\IReflectionFunction
	 */
	public function getFunction($functionName);

	/**
	 * Returns function reflections.
	 *
	 * @return array
	 */
	public function getFunctions();

	/**
	 * Returns function names (FQN).
	 *
	 * @return array
	 */
	public function getFunctionNames();

	/**
	 * Returns function unqualified names (UQN).
	 *
	 * @return array
	 */
	public function getFunctionShortNames();

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString();
}
