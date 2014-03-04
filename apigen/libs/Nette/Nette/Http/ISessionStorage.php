<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Http;

use Nette;



/**
 * User session storage. @see http://php.net/session_set_save_handler
 *
 * @author     David Grudl
 */
interface ISessionStorage
{

	function open($savePath, $sessionName);

	function close();

	function read($id);

	function write($id, $data);

	function remove($id);

	function clean($maxlifetime);

}
