<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * Placeholder schema for the UCP catalog routes.
 *
 * The routes answer with the UCP envelope rather than a Store API resource, so
 * they declare no properties; this exists because every Store API route is
 * constructed with a schema.
 */
class UcpCatalogSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ucp-catalog';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-catalog';

	/**
	 * Schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array();
	}
}
