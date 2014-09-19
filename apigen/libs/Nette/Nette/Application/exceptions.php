<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application;

use Nette;



/**
 * The exception that is thrown when user attempts to terminate the current presenter or application.
 * This is special "silent exception" with no error message or code.
 */
class AbortException extends \Exception
{
}



/**
 * Application fatal error.
 */
class ApplicationException extends \Exception
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
 * The exception that is thrown when a presenter cannot be loaded.
 */
class InvalidPresenterException extends \Exception
{
}



/**
 * Bad HTTP / presenter request exception.
 */
class BadRequestException extends \Exception
{
	/** @var int */
	protected $defaultCode = 404;


	public function __construct($message = '', $code = 0, \Exception $previous = NULL)
	{
		if ($code < 200 || $code > 504) {
			$code = $this->defaultCode;
		}

		/*5.2*if (PHP_VERSION_ID < 50300) {
			$this->previous = $previous;
			parent::__construct($message, $code);
		} else*/ {
			parent::__construct($message, $code, $previous);
		}
	}

}



/**
 * Forbidden request exception - access denied.
 */
class ForbiddenRequestException extends BadRequestException
{
	/** @var int */
	protected $defaultCode = 403;

}
