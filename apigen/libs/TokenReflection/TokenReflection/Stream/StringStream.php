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

/**
 * Token stream iterator created from a string.
 */
class StringStream extends StreamBase
{
	/**
	 * Constructor.
	 *
	 * Creates a token substream from a string.
	 *
	 * @param string $source PHP source code
	 * @param string $fileName File name
	 */
	public function __construct($source, $fileName)
	{
		parent::__construct();

		$this->fileName = $fileName;
		$this->processSource($source);
	}
}