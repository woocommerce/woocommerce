<?php

namespace TokenReflection\Invalid;

use TokenReflection\Exception\BaseException;

/**
 * Invalid element reflection.
 *
 * The reflected element is not unique (by its fully qualified name).
 */
abstract class ReflectionElement
{
	/**
	 * Reasons why this element's reflection is invalid.
	 *
	 * @var array
	 */
	private $reasons = array();

	/**
	 * Adds a reason why this element's reflection is invalid.
	 *
	 * @param \TokenReflection\Exception\BaseException $reason Reason
	 * @return \TokenReflection\Invalid\ReflectionElement
	 */
	public function addReason(BaseException $reason)
	{
		$this->reasons[] = $reason;

		return $this;
	}

	/**
	 * Returns a list of reasons why this element's reflection is invalid.
	 *
	 * @return array
	 */
	public function getReasons()
	{
		return $this->reasons;
	}

	/**
	 * Returns if there are any known reasons why this element's reflection is invalid.
	 *
	 * @return boolean
	 */
	public function hasReasons()
	{
		return !empty($this->reasons);
	}
}
