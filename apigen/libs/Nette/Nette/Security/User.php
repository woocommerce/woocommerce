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
 * User authentication and authorization.
 *
 * @author     David Grudl
 *
 * @property-read bool $loggedIn
 * @property-read IIdentity $identity
 * @property-read mixed $id
 * @property   IAuthenticator $authenticator
 * @property-read int $logoutReason
 * @property-read array $roles
 * @property   IAuthorizator $authorizator
 */
class User extends Nette\Object
{
	/**/ /** @deprecated */
	const MANUAL = IUserStorage::MANUAL,
		INACTIVITY = IUserStorage::INACTIVITY,
		BROWSER_CLOSED = IUserStorage::BROWSER_CLOSED;/**/

	/** @var string  default role for unauthenticated user */
	public $guestRole = 'guest';

	/** @var string  default role for authenticated user without own identity */
	public $authenticatedRole = 'authenticated';

	/** @var array of function(User $sender); Occurs when the user is successfully logged in */
	public $onLoggedIn;

	/** @var array of function(User $sender); Occurs when the user is logged out */
	public $onLoggedOut;

	/** @var IUserStorage Session storage for current user */
	private $storage;

	/** @var IAuthenticator */
	private $authenticator;

	/** @var IAuthorizator */
	private $authorizator;

	/** @var Nette\DI\Container */
	private $context;



	public function __construct(IUserStorage $storage, Nette\DI\Container $context)
	{
		$this->storage = $storage;
		$this->context = $context; // with IAuthenticator, IAuthorizator
	}



	/**
	 * @return IUserStorage
	 */
	final public function getStorage()
	{
		return $this->storage;
	}



	/********************* Authentication ****************d*g**/



	/**
	 * Conducts the authentication process. Parameters are optional.
	 * @param  mixed optional parameter (e.g. username or IIdentity)
	 * @param  mixed optional parameter (e.g. password)
	 * @return void
	 * @throws AuthenticationException if authentication was not successful
	 */
	public function login($id = NULL, $password = NULL)
	{
		$this->logout(TRUE);
		if (!$id instanceof IIdentity) {
			$credentials = func_get_args();
			$id = $this->getAuthenticator()->authenticate($credentials);
		}
		$this->storage->setIdentity($id);
		$this->storage->setAuthenticated(TRUE);
		$this->onLoggedIn($this);
	}



	/**
	 * Logs out the user from the current session.
	 * @param  bool  clear the identity from persistent storage?
	 * @return void
	 */
	final public function logout($clearIdentity = FALSE)
	{
		if ($this->isLoggedIn()) {
			$this->onLoggedOut($this);
			$this->storage->setAuthenticated(FALSE);
		}
		if ($clearIdentity) {
			$this->storage->setIdentity(NULL);
		}
	}



	/**
	 * Is this user authenticated?
	 * @return bool
	 */
	final public function isLoggedIn()
	{
		return $this->storage->isAuthenticated();
	}



	/**
	 * Returns current user identity, if any.
	 * @return IIdentity|NULL
	 */
	final public function getIdentity()
	{
		return $this->storage->getIdentity();
	}



	/**
	 * Returns current user ID, if any.
	 * @return mixed
	 */
	public function getId()
	{
		$identity = $this->getIdentity();
		return $identity ? $identity->getId() : NULL;
	}



	/**
	 * Sets authentication handler.
	 * @param  IAuthenticator
	 * @return User  provides a fluent interface
	 */
	public function setAuthenticator(IAuthenticator $handler)
	{
		$this->authenticator = $handler;
		return $this;
	}



	/**
	 * Returns authentication handler.
	 * @return IAuthenticator
	 */
	final public function getAuthenticator()
	{
		return $this->authenticator ?: $this->context->getByType('Nette\Security\IAuthenticator');
	}



	/**
	 * Enables log out after inactivity.
	 * @param  string|int|DateTime number of seconds or timestamp
	 * @param  bool  log out when the browser is closed?
	 * @param  bool  clear the identity from persistent storage?
	 * @return User  provides a fluent interface
	 */
	public function setExpiration($time, $whenBrowserIsClosed = TRUE, $clearIdentity = FALSE)
	{
		$flags = ($whenBrowserIsClosed ? IUserStorage::BROWSER_CLOSED : 0) | ($clearIdentity ? IUserStorage::CLEAR_IDENTITY : 0);
		$this->storage->setExpiration($time, $flags);
		return $this;
	}



	/**
	 * Why was user logged out?
	 * @return int
	 */
	final public function getLogoutReason()
	{
		return $this->storage->getLogoutReason();
	}



	/********************* Authorization ****************d*g**/



	/**
	 * Returns a list of effective roles that a user has been granted.
	 * @return array
	 */
	public function getRoles()
	{
		if (!$this->isLoggedIn()) {
			return array($this->guestRole);
		}

		$identity = $this->getIdentity();
		return $identity && $identity->getRoles() ? $identity->getRoles() : array($this->authenticatedRole);
	}



	/**
	 * Is a user in the specified effective role?
	 * @param  string
	 * @return bool
	 */
	final public function isInRole($role)
	{
		return in_array($role, $this->getRoles(), TRUE);
	}



	/**
	 * Has a user effective access to the Resource?
	 * If $resource is NULL, then the query applies to all resources.
	 * @param  string  resource
	 * @param  string  privilege
	 * @return bool
	 */
	public function isAllowed($resource = IAuthorizator::ALL, $privilege = IAuthorizator::ALL)
	{
		$authorizator = $this->getAuthorizator();
		foreach ($this->getRoles() as $role) {
			if ($authorizator->isAllowed($role, $resource, $privilege)) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * Sets authorization handler.
	 * @param  IAuthorizator
	 * @return User  provides a fluent interface
	 */
	public function setAuthorizator(IAuthorizator $handler)
	{
		$this->authorizator = $handler;
		return $this;
	}



	/**
	 * Returns current authorization handler.
	 * @return IAuthorizator
	 */
	final public function getAuthorizator()
	{
		return $this->authorizator ?: $this->context->getByType('Nette\Security\IAuthorizator');
	}



	/********************* deprecated ****************d*g**/

	/** @deprecated */
	function setNamespace($namespace)
	{
		trigger_error(__METHOD__ . '() is deprecated; use getStorage()->setNamespace() instead.', E_USER_WARNING);
		$this->storage->setNamespace($namespace);
		return $this;
	}

	/** @deprecated */
	function getNamespace()
	{
		trigger_error(__METHOD__ . '() is deprecated; use getStorage()->getNamespace() instead.', E_USER_WARNING);
		return $this->storage->getNamespace();
	}

	/** @deprecated */
	function setAuthenticationHandler($v)
	{
		trigger_error(__METHOD__ . '() is deprecated; use setAuthenticator() instead.', E_USER_WARNING);
		return $this->setAuthenticator($v);
	}

	/** @deprecated */
	function setAuthorizationHandler($v)
	{
		trigger_error(__METHOD__ . '() is deprecated; use setAuthorizator() instead.', E_USER_WARNING);
		return $this->setAuthorizator($v);
	}

}
