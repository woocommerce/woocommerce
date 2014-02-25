<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Config;

use Nette;



/**
 * Adapter for reading and writing configuration files.
 *
 * @author     David Grudl
 */
interface IAdapter
{

	/**
	 * Reads configuration from file.
	 * @param  string  file name
	 * @return array
	 */
	function load($file);

	/**
	 * Generates configuration string.
	 * @param  array
	 * @return string
	 */
	function dump(array $data);

}
