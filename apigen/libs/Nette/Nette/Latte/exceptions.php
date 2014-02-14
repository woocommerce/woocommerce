<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Latte;

use Nette;



/**
 * The exception occured during Latte compilation.
 *
 * @author     David Grudl
 */
class CompileException extends Nette\Templating\FilterException
{
}


/**/class_alias('Nette\Latte\CompileException', 'Nette\Latte\ParseException');/**/
