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
 * Basic TokenReflection interface.
 */
interface IReflection
{
	/**
	 * Returns the name (FQN).
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns if the reflection object is internal.
	 *
	 * @return boolean
	 */
	public function isInternal();

	/**
	 * Returns if the reflection object is user defined.
	 *
	 * @return boolean
	 */
	public function isUserDefined();

	/**
	 * Returns if the current reflection comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized();

	/**
	 * Returns the reflection broker used by this reflection object.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker();

	/**
	 * Magic __get method.
	 *
	 * @param string $key Variable name
	 * @return mixed
	 */
	public function __get($key);

	/**
	 * Magic __isset method.
	 *
	 * @param string $key Variable name
	 * @return boolean
	 */
	public function __isset($key);

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName();
}
