<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application\UI;

use Nette;



/**
 * Signal exception.
 *
 * @author     David Grudl
 */
class BadSignalException extends Nette\Application\BadRequestException
{
	/** @var int */
	protected $defaultCode = 403;

}
