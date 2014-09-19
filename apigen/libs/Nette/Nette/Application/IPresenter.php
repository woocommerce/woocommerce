<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application;

use Nette;



/**
 * Presenter converts Request to IResponse.
 *
 * @author     David Grudl
 */
interface IPresenter
{

	/**
	 * @param  Request
	 * @return IResponse
	 */
	function run(Request $request);

}
