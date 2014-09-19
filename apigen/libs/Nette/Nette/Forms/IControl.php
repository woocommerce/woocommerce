<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Forms;

use Nette;



/**
 * Defines method that must be implemented to allow a component to act like a form control.
 *
 * @author     David Grudl
 */
interface IControl
{

	/**
	 * Loads HTTP data.
	 * @return void
	 */
	function loadHttpData();

	/**
	 * Sets control's value.
	 * @param  mixed
	 * @return void
	 */
	function setValue($value);

	/**
	 * Returns control's value.
	 * @return mixed
	 */
	function getValue();

	/**
	 * @return Rules
	 */
	function getRules();

	/**
	 * Returns errors corresponding to control.
	 * @return array
	 */
	function getErrors();

	/**
	 * Is control disabled?
	 * @return bool
	 */
	function isDisabled();

	/**
	 * Returns translated string.
	 * @param  string
	 * @param  int      plural count
	 * @return string
	 */
	function translate($s, $count = NULL);

}
