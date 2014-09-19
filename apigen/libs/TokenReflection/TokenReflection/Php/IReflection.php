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

namespace TokenReflection\Php;

use TokenReflection;
use Reflector;

/**
 * Basic internal reflection interface.
 *
 * Common interface for all internal reflection classes.
 */
interface IReflection extends TokenReflection\IReflection
{
	/**
	 * Creates a reflection instance.
	 *
	 * @param \Reflector $internalReflection Internal reflection instance
	 * @param \TokenReflection\Broker $broker Reflection broker instance
	 * @return \TokenReflection\Php\IReflection
	 */
	public static function create(Reflector $internalReflection, TokenReflection\Broker $broker);
}
