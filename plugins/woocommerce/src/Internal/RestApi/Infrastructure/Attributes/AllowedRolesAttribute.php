<?php
/**
 * AllowedRolesAttribute class file
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes;

/**
 * Attribute that indicates which user roles are allowed to invoke a given REST API endpoint.
 * This attribute can be applied to a class or to an endpoint method;
 * the class-level attribute will apply to all the endpoint methods that don't have their own attribute.
 *
 * By default (no class nor method attribute present) any user role is allowed.
 */
#[\Attribute]
class AllowedRolesAttribute {

	/**
	 * Comma-separated list of roles that are allowed to invoke a given REST API endpoint.
	 * An empty string means "any role".
	 *
	 * @var string
	 */
	public $roles;

	/**
	 * Initializes a new instance of the class.
	 *
	 * @param string|null $roles Comma-separated list of roles that are allowed to invoke a given REST API endpoint, empty string or null for "any role".
	 */
	public function __construct( ?string $roles ) {
		$this->roles = $roles ?? '';
	}
}
