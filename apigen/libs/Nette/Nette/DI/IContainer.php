<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\DI;

use Nette;



/**
 * @deprecated
 */
interface IContainer
{

	/**
	 * Adds the service to the container.
	 * @param  string
	 * @param  mixed  object, class name or callback
	 * @return void
	 */
	function addService($name, $service);

	/**
	 * Gets the service object.
	 * @param  string
	 * @return mixed
	 */
	function getService($name);

	/**
	 * Removes the service from the container.
	 * @param  string
	 * @return void
	 */
	function removeService($name);

	/**
	 * Does the service exist?
	 * @return bool
	 */
	function hasService($name);

}
