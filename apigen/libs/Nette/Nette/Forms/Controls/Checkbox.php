<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Forms\Controls;

use Nette;



/**
 * Check box control. Allows the user to select a true or false condition.
 *
 * @author     David Grudl
 */
class Checkbox extends BaseControl
{

	/**
	 * @param  string  label
	 */
	public function __construct($label = NULL)
	{
		parent::__construct($label);
		$this->control->type = 'checkbox';
		$this->value = FALSE;
	}



	/**
	 * Sets control's value.
	 * @param  bool
	 * @return Checkbox  provides a fluent interface
	 */
	public function setValue($value)
	{
		$this->value = is_scalar($value) ? (bool) $value : FALSE;
		return $this;
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Utils\Html
	 */
	public function getControl()
	{
		return parent::getControl()->checked($this->value);
	}

}
