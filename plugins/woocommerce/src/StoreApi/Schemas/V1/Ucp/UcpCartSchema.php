<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * Placeholder schema for the UCP cart routes.
 *
 * The routes answer with the UCP cart envelope rather than a Store API resource,
 * and the authoritative schema for it is published at
 * `https://ucp.dev/<version>/schemas/shopping/cart.json` and named in every
 * response's capability declaration. Restating it here would be a second copy to
 * keep in sync; this exists because every Store API route is constructed with a
 * schema.
 */
class UcpCartSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ucp-cart';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-cart';

	/**
	 * Schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array();
	}
}
