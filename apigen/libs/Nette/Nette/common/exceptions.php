<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette;

use Nette;



/**
 * The exception that is thrown when the value of an argument is
 * outside the allowable range of values as defined by the invoked method.
 */
class ArgumentOutOfRangeException extends \InvalidArgumentException
{
}



/**
 * The exception that is thrown when a method call is invalid for the object's
 * current state, method has been invoked at an illegal or inappropriate time.
 */
class InvalidStateException extends \RuntimeException
{
	/*5.2*
	public function __construct($message = '', $code = 0, \Exception $previous = NULL)
	{
		if (PHP_VERSION_ID < 50300) {
			$this->previous = $previous;
			parent::__construct($message, $code);
		} else {
			parent::__construct($message, $code, $previous);
		}
	}
	*/
}



/**
 * The exception that is thrown when a requested method or operation is not implemented.
 */
class NotImplementedException extends \LogicException
{
}



/**
 * The exception that is thrown when an invoked method is not supported. For scenarios where
 * it is sometimes possible to perform the requested operation, see InvalidStateException.
 */
class NotSupportedException extends \LogicException
{
}



/**
 * The exception that is thrown when a requested method or operation is deprecated.
 */
class DeprecatedException extends NotSupportedException
{
}



/**
 * The exception that is thrown when accessing a class member (property or method) fails.
 */
class MemberAccessException extends \LogicException
{
}



/**
 * The exception that is thrown when an I/O error occurs.
 */
class IOException extends \RuntimeException
{
}



/**
 * The exception that is thrown when accessing a file that does not exist on disk.
 */
class FileNotFoundException extends IOException
{
}



/**
 * The exception that is thrown when part of a file or directory cannot be found.
 */
class DirectoryNotFoundException extends IOException
{
}


/**/
/**
 * The exception that is thrown when an argument does not match with the expected value.
 */
class InvalidArgumentException extends \InvalidArgumentException
{
}



/**
 * The exception that is thrown when an illegal index was requested.
 */
class OutOfRangeException extends \OutOfRangeException
{
}



/**
 * The exception that is thrown when a value (typically returned by function) does not match with the expected value.
 */
class UnexpectedValueException extends \UnexpectedValueException
{
}
/**/


/**
 * The exception that is thrown when static class is instantiated.
 */
class StaticClassException extends \LogicException
{
}



/**
 * The exception that indicates errors that can not be recovered from. Execution of
 * the script should be halted.
 */
/**/
class FatalErrorException extends \ErrorException
{

	public function __construct($message, $code, $severity, $file, $line, $context)
	{
		parent::__construct($message, $code, $severity, $file, $line);
		$this->context = $context;
	}

}
/**/

/*5.2*
class FatalErrorException extends \Exception // ErrorException is corrupted in PHP < 5.3
{
	private $severity;

	public function __construct($message, $code, $severity, $file, $line, $context)
	{
		parent::__construct($message, $code);
		$this->severity = $severity;
		$this->file = $file;
		$this->line = $line;
		$this->context = $context;
	}

	public function getSeverity()
	{
		return $this->severity;
	}

}
*/
