<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;

/**
 * Product schema whose item responses carry raw strings, for the UCP catalog routes.
 *
 * Agents compare titles and SKUs literally, so the storefront HTML formatter
 * (texturized quotes, `10x20` to `10&#215;20`) would corrupt what they match on.
 *
 * @since 11.3.0
 */
class UcpProductSchema extends ProductSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ucp-product';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-product';

	/**
	 * Return content unformatted; the UCP mapper derives plain text itself.
	 *
	 * @param string|array $response Data to format.
	 * @return string|array
	 */
	protected function prepare_html_response( $response ) {
		return $response;
	}
}
