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
 * Defines method that must be implemented to allow a control to submit web form.
 *
 * @author     David Grudl
 */
interface ISubmitterControl extends IControl
{

	/**
	 * Tells if the form was submitted by this button.
	 * @return bool
	 */
	function isSubmittedBy();

	/**
	 * Gets the validation scope. Clicking the button validates only the controls within the specified scope.
	 * @return mixed
	 */
	function getValidationScope();

}
