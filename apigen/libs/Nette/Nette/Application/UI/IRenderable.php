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
 * Component with ability to repaint.
 *
 * @author     David Grudl
 */
interface IRenderable
{

	/**
	 * Forces control to repaint.
	 * @return void
	 */
	function invalidateControl();

	/**
	 * Is required to repaint the control?
	 * @return bool
	 */
	function isControlInvalid();

}
