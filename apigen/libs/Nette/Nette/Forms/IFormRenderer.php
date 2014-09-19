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
 * Defines method that must implement form renderer.
 *
 * @author     David Grudl
 */
interface IFormRenderer
{

	/**
	 * Provides complete form rendering.
	 * @param  Form
	 * @return string
	 */
	function render(Form $form);

}
