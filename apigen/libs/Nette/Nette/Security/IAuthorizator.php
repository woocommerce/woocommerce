<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Security;

use Nette;



/**
 * Authorizator checks if a given role has authorization
 * to access a given resource.
 *
 * @author     David Grudl
 */
interface IAuthorizator
{
	/** Set type: all */
	const ALL = NULL;

	/** Permission type: allow */
	const ALLOW = TRUE;

	/** Permission type: deny */
	const DENY = FALSE;


	/**
	 * Performs a role-based authorization.
	 * @param  string  role
	 * @param  string  resource
	 * @param  string  privilege
	 * @return bool
	 */
	function isAllowed($role/*5.2* = self::ALL*/, $resource/*5.2* = self::ALL*/, $privilege/*5.2* = self::ALL*/);

}
