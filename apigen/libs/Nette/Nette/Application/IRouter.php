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
 * The bi-directional router.
 *
 * @author     David Grudl
 */
interface IRouter
{
	/** only matching route */
	const ONE_WAY = 1;

	/** HTTPS route */
	const SECURED = 2;

	/**
	 * Maps HTTP request to a Request object.
	 * @param  Nette\Http\IRequest
	 * @return Request|NULL
	 */
	function match(Nette\Http\IRequest $httpRequest);

	/**
	 * Constructs absolute URL from Request object.
	 * @param  Request
	 * @param  Nette\Http\Url referential URI
	 * @return string|NULL
	 */
	function constructUrl(Request $appRequest, Nette\Http\Url $refUrl);

}
