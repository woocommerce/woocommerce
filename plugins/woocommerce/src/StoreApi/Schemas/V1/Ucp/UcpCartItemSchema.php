<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

/**
 * Cart item schema whose responses carry raw strings, for the UCP cart routes.
 *
 * Agents compare titles literally, so the storefront HTML formatter must not
 * reach them. Same rule as {@see UcpProductSchema}.
 *
 * @since 11.3.0
 */
class UcpCartItemSchema extends CartItemSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ucp-cart-item';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-cart-item';

	/**
	 * Return content unformatted.
	 *
	 * @param string|array $response Data to format.
	 * @return string|array
	 */
	protected function prepare_html_response( $response ) {
		return $response;
	}
}
