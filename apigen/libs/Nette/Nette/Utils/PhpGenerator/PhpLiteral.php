<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Utils\PhpGenerator;

use Nette;



/**
 * PHP literal value.
 *
 * @author     David Grudl
 */
class PhpLiteral
{
	/** @var string */
	public $value = '';


	public function __construct($value)
	{
		$this->value = (string) $value;
	}

}
