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
 * Access control list (ACL) functionality and privileges management.
 *
 * This solution is mostly based on Zend_Acl (c) Zend Technologies USA Inc. (http://www.zend.com), new BSD license
 *
 * @copyright  Copyright (c) 2005, 2007 Zend Technologies USA Inc.
 * @author     David Grudl
 *
 * @property-read array $roles
 * @property-read array $resources
 * @property-read mixed $queriedRole
 * @property-read mixed $queriedResource
 */
class Permission extends Nette\Object implements IAuthorizator
{
	/** @var array  Role storage */
	private $roles = array();

	/** @var array  Resource storage */
	private $resources = array();

	/** @var array  Access Control List rules; whitelist (deny everything to all) by default */
	private $rules = array(
		'allResources' => array(
			'allRoles' => array(
				'allPrivileges' => array(
					'type' => self::DENY,
					'assert' => NULL,
				),
				'byPrivilege' => array(),
			),
			'byRole' => array(),
		),
		'byResource' => array(),
	);

	/** @var mixed */
	private $queriedRole, $queriedResource;



	/********************* roles ****************d*g**/



	/**
	 * Adds a Role to the list. The most recently added parent
	 * takes precedence over parents that were previously added.
	 * @param  string
	 * @param  string|array
	 * @throws Nette\InvalidArgumentException
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	public function addRole($role, $parents = NULL)
	{
		$this->checkRole($role, FALSE);
		if (isset($this->roles[$role])) {
			throw new Nette\InvalidStateException("Role '$role' already exists in the list.");
		}

		$roleParents = array();

		if ($parents !== NULL) {
			if (!is_array($parents)) {
				$parents = array($parents);
			}

			foreach ($parents as $parent) {
				$this->checkRole($parent);
				$roleParents[$parent] = TRUE;
				$this->roles[$parent]['children'][$role] = TRUE;
			}
		}

		$this->roles[$role] = array(
			'parents'  => $roleParents,
			'children' => array(),
		);

		return $this;
	}



	/**
	 * Returns TRUE if the Role exists in the list.
	 * @param  string
	 * @return bool
	 */
	public function hasRole($role)
	{
		$this->checkRole($role, FALSE);
		return isset($this->roles[$role]);
	}



	/**
	 * Checks whether Role is valid and exists in the list.
	 * @param  string
	 * @param  bool
	 * @throws Nette\InvalidStateException
	 * @return void
	 */
	private function checkRole($role, $need = TRUE)
	{
		if (!is_string($role) || $role === '') {
			throw new Nette\InvalidArgumentException("Role must be a non-empty string.");

		} elseif ($need && !isset($this->roles[$role])) {
			throw new Nette\InvalidStateException("Role '$role' does not exist.");
		}
	}



	/**
	 * Returns all Roles.
	 * @return array
	 */
	public function getRoles()
	{
		return array_keys($this->roles);
	}



	/**
	 * Returns existing Role's parents ordered by ascending priority.
	 * @param  string
	 * @return array
	 */
	public function getRoleParents($role)
	{
		$this->checkRole($role);
		return array_keys($this->roles[$role]['parents']);
	}



	/**
	 * Returns TRUE if $role inherits from $inherit. If $onlyParents is TRUE,
	 * then $role must inherit directly from $inherit.
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @throws Nette\InvalidStateException
	 * @return bool
	 */
	public function roleInheritsFrom($role, $inherit, $onlyParents = FALSE)
	{
		$this->checkRole($role);
		$this->checkRole($inherit);

		$inherits = isset($this->roles[$role]['parents'][$inherit]);

		if ($inherits || $onlyParents) {
			return $inherits;
		}

		foreach ($this->roles[$role]['parents'] as $parent => $foo) {
			if ($this->roleInheritsFrom($parent, $inherit)) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * Removes the Role from the list.
	 *
	 * @param  string
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	public function removeRole($role)
	{
		$this->checkRole($role);

		foreach ($this->roles[$role]['children'] as $child => $foo) {
			unset($this->roles[$child]['parents'][$role]);
		}

		foreach ($this->roles[$role]['parents'] as $parent => $foo) {
			unset($this->roles[$parent]['children'][$role]);
		}

		unset($this->roles[$role]);

		foreach ($this->rules['allResources']['byRole'] as $roleCurrent => $rules) {
			if ($role === $roleCurrent) {
				unset($this->rules['allResources']['byRole'][$roleCurrent]);
			}
		}

		foreach ($this->rules['byResource'] as $resourceCurrent => $visitor) {
			if (isset($visitor['byRole'])) {
				foreach ($visitor['byRole'] as $roleCurrent => $rules) {
					if ($role === $roleCurrent) {
						unset($this->rules['byResource'][$resourceCurrent]['byRole'][$roleCurrent]);
					}
				}
			}
		}

		return $this;
	}



	/**
	 * Removes all Roles from the list.
	 *
	 * @return Permission  provides a fluent interface
	 */
	public function removeAllRoles()
	{
		$this->roles = array();

		foreach ($this->rules['allResources']['byRole'] as $roleCurrent => $rules) {
			unset($this->rules['allResources']['byRole'][$roleCurrent]);
		}

		foreach ($this->rules['byResource'] as $resourceCurrent => $visitor) {
			foreach ($visitor['byRole'] as $roleCurrent => $rules) {
				unset($this->rules['byResource'][$resourceCurrent]['byRole'][$roleCurrent]);
			}
		}

		return $this;
	}



	/********************* resources ****************d*g**/



	/**
	 * Adds a Resource having an identifier unique to the list.
	 *
	 * @param  string
	 * @param  string
	 * @throws Nette\InvalidArgumentException
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	public function addResource($resource, $parent = NULL)
	{
		$this->checkResource($resource, FALSE);

		if (isset($this->resources[$resource])) {
			throw new Nette\InvalidStateException("Resource '$resource' already exists in the list.");
		}

		if ($parent !== NULL) {
			$this->checkResource($parent);
			$this->resources[$parent]['children'][$resource] = TRUE;
		}

		$this->resources[$resource] = array(
			'parent'   => $parent,
			'children' => array()
		);

		return $this;
	}



	/**
	 * Returns TRUE if the Resource exists in the list.
	 * @param  string
	 * @return bool
	 */
	public function hasResource($resource)
	{
		$this->checkResource($resource, FALSE);
		return isset($this->resources[$resource]);
	}



	/**
	 * Checks whether Resource is valid and exists in the list.
	 * @param  string
	 * @param  bool
	 * @throws Nette\InvalidStateException
	 * @return void
	 */
	private function checkResource($resource, $need = TRUE)
	{
		if (!is_string($resource) || $resource === '') {
			throw new Nette\InvalidArgumentException("Resource must be a non-empty string.");

		} elseif ($need && !isset($this->resources[$resource])) {
			throw new Nette\InvalidStateException("Resource '$resource' does not exist.");
		}
	}



	/**
	 * Returns all Resources.
	 * @return array
	 */
	public function getResources()
	{
		return array_keys($this->resources);
	}



	/**
	 * Returns TRUE if $resource inherits from $inherit. If $onlyParents is TRUE,
	 * then $resource must inherit directly from $inherit.
	 *
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @throws Nette\InvalidStateException
	 * @return bool
	 */
	public function resourceInheritsFrom($resource, $inherit, $onlyParent = FALSE)
	{
		$this->checkResource($resource);
		$this->checkResource($inherit);

		if ($this->resources[$resource]['parent'] === NULL) {
			return FALSE;
		}

		$parent = $this->resources[$resource]['parent'];
		if ($inherit === $parent) {
			return TRUE;

		} elseif ($onlyParent) {
			return FALSE;
		}

		while ($this->resources[$parent]['parent'] !== NULL) {
			$parent = $this->resources[$parent]['parent'];
			if ($inherit === $parent) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * Removes a Resource and all of its children.
	 *
	 * @param  string
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	public function removeResource($resource)
	{
		$this->checkResource($resource);

		$parent = $this->resources[$resource]['parent'];
		if ($parent !== NULL) {
			unset($this->resources[$parent]['children'][$resource]);
		}

		$removed = array($resource);
		foreach ($this->resources[$resource]['children'] as $child => $foo) {
			$this->removeResource($child);
			$removed[] = $child;
		}

		foreach ($removed as $resourceRemoved) {
			foreach ($this->rules['byResource'] as $resourceCurrent => $rules) {
				if ($resourceRemoved === $resourceCurrent) {
					unset($this->rules['byResource'][$resourceCurrent]);
				}
			}
		}

		unset($this->resources[$resource]);
		return $this;
	}



	/**
	 * Removes all Resources.
	 * @return Permission  provides a fluent interface
	 */
	public function removeAllResources()
	{
		foreach ($this->resources as $resource => $foo) {
			foreach ($this->rules['byResource'] as $resourceCurrent => $rules) {
				if ($resource === $resourceCurrent) {
					unset($this->rules['byResource'][$resourceCurrent]);
				}
			}
		}

		$this->resources = array();
		return $this;
	}



	/********************* defining rules ****************d*g**/



	/**
	 * Allows one or more Roles access to [certain $privileges upon] the specified Resource(s).
	 * If $assertion is provided, then it must return TRUE in order for rule to apply.
	 *
	 * @param  string|array|Permission::ALL  roles
	 * @param  string|array|Permission::ALL  resources
	 * @param  string|array|Permission::ALL  privileges
	 * @param  callable    assertion
	 * @return Permission  provides a fluent interface
	 */
	public function allow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = NULL)
	{
		$this->setRule(TRUE, self::ALLOW, $roles, $resources, $privileges, $assertion);
		return $this;
	}



	/**
	 * Denies one or more Roles access to [certain $privileges upon] the specified Resource(s).
	 * If $assertion is provided, then it must return TRUE in order for rule to apply.
	 *
	 * @param  string|array|Permission::ALL  roles
	 * @param  string|array|Permission::ALL  resources
	 * @param  string|array|Permission::ALL  privileges
	 * @param  callable    assertion
	 * @return Permission  provides a fluent interface
	 */
	public function deny($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = NULL)
	{
		$this->setRule(TRUE, self::DENY, $roles, $resources, $privileges, $assertion);
		return $this;
	}



	/**
	 * Removes "allow" permissions from the list in the context of the given Roles, Resources, and privileges.
	 *
	 * @param  string|array|Permission::ALL  roles
	 * @param  string|array|Permission::ALL  resources
	 * @param  string|array|Permission::ALL  privileges
	 * @return Permission  provides a fluent interface
	 */
	public function removeAllow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL)
	{
		$this->setRule(FALSE, self::ALLOW, $roles, $resources, $privileges);
		return $this;
	}



	/**
	 * Removes "deny" restrictions from the list in the context of the given Roles, Resources, and privileges.
	 *
	 * @param  string|array|Permission::ALL  roles
	 * @param  string|array|Permission::ALL  resources
	 * @param  string|array|Permission::ALL  privileges
	 * @return Permission  provides a fluent interface
	 */
	public function removeDeny($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL)
	{
		$this->setRule(FALSE, self::DENY, $roles, $resources, $privileges);
		return $this;
	}



	/**
	 * Performs operations on Access Control List rules.
	 * @param  bool  operation add?
	 * @param  bool  type
	 * @param  string|array|Permission::ALL  roles
	 * @param  string|array|Permission::ALL  resources
	 * @param  string|array|Permission::ALL  privileges
	 * @param  callable    assertion
	 * @throws Nette\InvalidStateException
	 * @return Permission  provides a fluent interface
	 */
	protected function setRule($toAdd, $type, $roles, $resources, $privileges, $assertion = NULL)
	{
		// ensure that all specified Roles exist; normalize input to array of Roles or NULL
		if ($roles === self::ALL) {
			$roles = array(self::ALL);

		} else {
			if (!is_array($roles)) {
				$roles = array($roles);
			}

			foreach ($roles as $role) {
				$this->checkRole($role);
			}
		}

		// ensure that all specified Resources exist; normalize input to array of Resources or NULL
		if ($resources === self::ALL) {
			$resources = array(self::ALL);

		} else {
			if (!is_array($resources)) {
				$resources = array($resources);
			}

			foreach ($resources as $resource) {
				$this->checkResource($resource);
			}
		}

		// normalize privileges to array
		if ($privileges === self::ALL) {
			$privileges = array();

		} elseif (!is_array($privileges)) {
			$privileges = array($privileges);
		}

		$assertion = $assertion ? new Nette\Callback($assertion) : NULL;

		if ($toAdd) { // add to the rules
			foreach ($resources as $resource) {
				foreach ($roles as $role) {
					$rules = & $this->getRules($resource, $role, TRUE);
					if (count($privileges) === 0) {
						$rules['allPrivileges']['type'] = $type;
						$rules['allPrivileges']['assert'] = $assertion;
						if (!isset($rules['byPrivilege'])) {
							$rules['byPrivilege'] = array();
						}
					} else {
						foreach ($privileges as $privilege) {
							$rules['byPrivilege'][$privilege]['type'] = $type;
							$rules['byPrivilege'][$privilege]['assert'] = $assertion;
						}
					}
				}
			}

		} else { // remove from the rules
			foreach ($resources as $resource) {
				foreach ($roles as $role) {
					$rules = & $this->getRules($resource, $role);
					if ($rules === NULL) {
						continue;
					}
					if (count($privileges) === 0) {
						if ($resource === self::ALL && $role === self::ALL) {
							if ($type === $rules['allPrivileges']['type']) {
								$rules = array(
									'allPrivileges' => array(
										'type' => self::DENY,
										'assert' => NULL
										),
									'byPrivilege' => array()
									);
							}
							continue;
						}
						if ($type === $rules['allPrivileges']['type']) {
							unset($rules['allPrivileges']);
						}
					} else {
						foreach ($privileges as $privilege) {
							if (isset($rules['byPrivilege'][$privilege]) &&
								$type === $rules['byPrivilege'][$privilege]['type']) {
								unset($rules['byPrivilege'][$privilege]);
							}
						}
					}
				}
			}
		}
		return $this;
	}



	/********************* querying the ACL ****************d*g**/



	/**
	 * Returns TRUE if and only if the Role has access to [certain $privileges upon] the Resource.
	 *
	 * This method checks Role inheritance using a depth-first traversal of the Role list.
	 * The highest priority parent (i.e., the parent most recently added) is checked first,
	 * and its respective parents are checked similarly before the lower-priority parents of
	 * the Role are checked.
	 *
	 * @param  string|Permission::ALL|IRole  role
	 * @param  string|Permission::ALL|IResource  resource
	 * @param  string|Permission::ALL  privilege
	 * @throws Nette\InvalidStateException
	 * @return bool
	 */
	public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL)
	{
		$this->queriedRole = $role;
		if ($role !== self::ALL) {
			if ($role instanceof IRole) {
				$role = $role->getRoleId();
			}
			$this->checkRole($role);
		}

		$this->queriedResource = $resource;
		if ($resource !== self::ALL) {
			if ($resource instanceof IResource) {
				$resource = $resource->getResourceId();
			}
			$this->checkResource($resource);
		}

		do {
			// depth-first search on $role if it is not 'allRoles' pseudo-parent
			if ($role !== NULL && NULL !== ($result = $this->searchRolePrivileges($privilege === self::ALL, $role, $resource, $privilege))) {
				break;
			}

			if ($privilege === self::ALL) {
				if ($rules = $this->getRules($resource, self::ALL)) { // look for rule on 'allRoles' psuedo-parent
					foreach ($rules['byPrivilege'] as $privilege => $rule) {
						if (self::DENY === ($result = $this->getRuleType($resource, NULL, $privilege))) {
							break 2;
						}
					}
					if (NULL !== ($result = $this->getRuleType($resource, NULL, NULL))) {
						break;
					}
				}
			} else {
				if (NULL !== ($result = $this->getRuleType($resource, NULL, $privilege))) { // look for rule on 'allRoles' pseudo-parent
					break;

				} elseif (NULL !== ($result = $this->getRuleType($resource, NULL, NULL))) {
					break;
				}
			}

			$resource = $this->resources[$resource]['parent']; // try next Resource
		} while (TRUE);

		$this->queriedRole = $this->queriedResource = NULL;
		return $result;
	}



	/**
	 * Returns real currently queried Role. Use by assertion.
	 * @return mixed
	 */
	public function getQueriedRole()
	{
		return $this->queriedRole;
	}



	/**
	 * Returns real currently queried Resource. Use by assertion.
	 * @return mixed
	 */
	public function getQueriedResource()
	{
		return $this->queriedResource;
	}



	/********************* internals ****************d*g**/



	/**
	 * Performs a depth-first search of the Role DAG, starting at $role, in order to find a rule
	 * allowing/denying $role access to a/all $privilege upon $resource.
	 * @param  bool  all (true) or one?
	 * @param  string
	 * @param  string
	 * @param  string  only for one
	 * @return mixed  NULL if no applicable rule is found, otherwise returns ALLOW or DENY
	 */
	private function searchRolePrivileges($all, $role, $resource, $privilege)
	{
		$dfs = array(
			'visited' => array(),
			'stack' => array($role),
		);

		while (NULL !== ($role = array_pop($dfs['stack']))) {
			if (isset($dfs['visited'][$role])) {
				continue;
			}
			if ($all) {
				if ($rules = $this->getRules($resource, $role)) {
					foreach ($rules['byPrivilege'] as $privilege2 => $rule) {
						if (self::DENY === $this->getRuleType($resource, $role, $privilege2)) {
							return self::DENY;
						}
					}
					if (NULL !== ($type = $this->getRuleType($resource, $role, NULL))) {
						return $type;
					}
				}
			} else {
				if (NULL !== ($type = $this->getRuleType($resource, $role, $privilege))) {
					return $type;

				} elseif (NULL !== ($type = $this->getRuleType($resource, $role, NULL))) {
					return $type;
				}
			}

			$dfs['visited'][$role] = TRUE;
			foreach ($this->roles[$role]['parents'] as $roleParent => $foo) {
				$dfs['stack'][] = $roleParent;
			}
		}
		return NULL;
	}



	/**
	 * Returns the rule type associated with the specified Resource, Role, and privilege.
	 * @param  string|Permission::ALL
	 * @param  string|Permission::ALL
	 * @param  string|Permission::ALL
	 * @return mixed  NULL if a rule does not exist or assertion fails, otherwise returns ALLOW or DENY
	 */
	private function getRuleType($resource, $role, $privilege)
	{
		if (!$rules = $this->getRules($resource, $role)) {
			return NULL;
		}

		if ($privilege === self::ALL) {
			if (isset($rules['allPrivileges'])) {
				$rule = $rules['allPrivileges'];
			} else {
				return NULL;
			}
		} elseif (!isset($rules['byPrivilege'][$privilege])) {
			return NULL;

		} else {
			$rule = $rules['byPrivilege'][$privilege];
		}

		if ($rule['assert'] === NULL || $rule['assert']->__invoke($this, $role, $resource, $privilege)) {
			return $rule['type'];

		} elseif ($resource !== self::ALL || $role !== self::ALL || $privilege !== self::ALL) {
			return NULL;

		} elseif (self::ALLOW === $rule['type']) {
			return self::DENY;

		} else {
			return self::ALLOW;
		}
	}



	/**
	 * Returns the rules associated with a Resource and a Role, or NULL if no such rules exist.
	 * If the $create parameter is TRUE, then a rule set is first created and then returned to the caller.
	 * @param  string|Permission::ALL
	 * @param  string|Permission::ALL
	 * @param  bool
	 * @return array|NULL
	 */
	private function & getRules($resource, $role, $create = FALSE)
	{
		$null = NULL;
		if ($resource === self::ALL) {
			$visitor = & $this->rules['allResources'];
		} else {
			if (!isset($this->rules['byResource'][$resource])) {
				if (!$create) {
					return $null;
				}
				$this->rules['byResource'][$resource] = array();
			}
			$visitor = & $this->rules['byResource'][$resource];
		}

		if ($role === self::ALL) {
			if (!isset($visitor['allRoles'])) {
				if (!$create) {
					return $null;
				}
				$visitor['allRoles']['byPrivilege'] = array();
			}
			return $visitor['allRoles'];
		}

		if (!isset($visitor['byRole'][$role])) {
			if (!$create) {
				return $null;
			}
			$visitor['byRole'][$role]['byPrivilege'] = array();
		}

		return $visitor['byRole'][$role];
	}

}
