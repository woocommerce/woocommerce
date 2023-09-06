<?php
/**
 * AllowUnauthenticatedAttribute class file
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes;

/**
 * Attribute that indicates if unauthenticated users can invoke a given REST API endpoint.
 * This attribute can be applied to a class or to an endpoint method;
 * the class-level attribute will apply to all the endpoint methods that don't have their own attribute.
 *
 * By default (no class nor method attribute present) no unauthenticated access is allowed.
 */
#[\Attribute]
class AllowUnauthenticatedAttribute {

	/**
	 * Whether unauthenticated users can invoke a given REST API endpoint.
	 *
	 * @var bool
	 */
	public $allow_unauthenticated;

	/**
	 * Initializes a new instance of the class.
	 *
	 * @param bool $allow_unauthenticated Whether unauthenticated users can invoke a given REST API endpoint.
	 */
	public function __construct( bool $allow_unauthenticated ) {
		$this->allow_unauthenticated = $allow_unauthenticated;
	}
}
