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
 * Method parameter description.
 *
 * @author     David Grudl
 *
 * @method Parameter setName(string $name)
 * @method Parameter setReference(bool $on)
 * @method Parameter setTypeHint(string $class)
 * @method Parameter setOptional(bool $on)
 * @method Parameter setDefaultValue(mixed $value)
 */
class Parameter extends Nette\Object
{
	/** @var string */
	public $name;

	/** @var bool */
	public $reference;

	/** @var string */
	public $typeHint;

	/** @var bool */
	public $optional;

	/** @var mixed */
	public $defaultValue;


	public function __call($name, $args)
	{
		return Nette\ObjectMixin::callProperty($this, $name, $args);
	}

}
