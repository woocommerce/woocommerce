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
 * Component with ability to save and load its state.
 *
 * @author     David Grudl
 */
interface IStatePersistent
{

	/**
	 * Loads state informations.
	 * @param  array
	 * @return void
	 */
	function loadState(array $params);

	/**
	 * Saves state informations for next request.
	 * @param  array
	 * @return void
	 */
	function saveState(array & $params);

}
