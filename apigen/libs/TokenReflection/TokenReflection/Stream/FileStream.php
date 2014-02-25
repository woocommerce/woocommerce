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

namespace TokenReflection\Stream;

use TokenReflection\Broker as Broker, TokenReflection\Exception;

/**
 * Token stream iterator created from a file.
 */
class FileStream extends StreamBase
{
	/**
	 * Constructor.
	 *
	 * Creates a token substream from a file.
	 *
	 * @param string $fileName File name
	 * @throws \TokenReflection\Exception\StreamException If the file does not exist or is not readable.
	 */
	public function __construct($fileName)
	{
		parent::__construct();

		$this->fileName = Broker::getRealPath($fileName);

		if (false === $this->fileName) {
			throw new Exception\StreamException($this, 'File does not exist.', Exception\StreamException::DOES_NOT_EXIST);
		}

		$contents = @file_get_contents($this->fileName);
		if (false === $contents) {
			throw new Exception\StreamException($this, 'File is not readable.', Exception\StreamException::NOT_READABLE);
		}

		$this->processSource($contents);
	}
}