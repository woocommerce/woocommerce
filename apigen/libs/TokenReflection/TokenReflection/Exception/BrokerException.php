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

namespace TokenReflection\Exception;

use TokenReflection\Broker;

/**
 * Exception raised when working with the Broker.
 */
class BrokerException extends BaseException
{
	/**
	 * Processed file name.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Constructor.
	 *
	 * @param \TokenReflection\Broker $broker Processed file name
	 * @param string $message Exception message
	 * @param integer $code Exception code
	 * @param \TokenReflection\Exception\StreamException $parent Parent exception
	 */
	public function __construct(Broker $broker, $message, $code, StreamException $parent = null)
	{
		parent::__construct($message, $code, $parent);

		$this->broker = $broker;
	}

	/**
	 * Returns the current Broker.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker()
	{
		return $this->broker;
	}

	/**
	 * Returns an exception description detail.
	 *
	 * @return string
	 */
	public function getDetail()
	{
		return '';
	}
}
