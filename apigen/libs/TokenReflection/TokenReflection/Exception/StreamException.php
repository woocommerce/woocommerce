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

use TokenReflection\Stream\StreamBase;

/**
 * TokenReflection exception raised when working with a token stream.
 */
class StreamException extends BaseException
{
	/**
	 * The property/element does not exist.
	 *
	 * @var integer
	 */
	const NOT_READABLE = 1001;

	/**
	 * A required PHP extension is missing.
	 *
	 * @var integer
	 */
	const READ_BEYOND_EOS = 1002;

	/**
	 * There was an error when (de)serializing the token stream.
	 *
	 * @var integer
	 */
	const SERIALIZATION_ERROR = 1003;

	/**
	 * The token stream that caused this exception to be raised.
	 *
	 * @var \TokenReflection\Stream\StreamBase
	 */
	private $stream;

	/**
	 * Constructor.
	 *
	 * @param \TokenReflection\Stream\StreamBase $stream Reflection element
	 * @param string $message Exception message
	 * @param integer $code Exception code
	 */
	public function __construct(StreamBase $stream, $message, $code)
	{
		parent::__construct($message, $code);

		$this->stream = $stream;
	}

	/**
	 * Returns the reflection element that caused the exception to be raised.
	 *
	 * @return \TokenReflection\Stream\StreamBase
	 */
	public function getStream()
	{
		return $this->stream;
	}

	/**
	 * Returns the processed file name.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->stream->getFileName();
	}

	/**
	 * Returns an exception description detail.
	 *
	 * @return string
	 */
	public function getDetail()
	{
		return sprintf('Thrown when working with file "%s" token stream.', $this->getFileName());
	}
}
