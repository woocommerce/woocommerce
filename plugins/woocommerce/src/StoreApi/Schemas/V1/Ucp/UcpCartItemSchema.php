<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp;

use Automattic\WooCommerce\Enums\TaxDisplayMode;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

/**
 * Cart item schema whose responses carry raw strings and pre-tax prices, for the
 * UCP cart routes.
 *
 * Agents compare titles literally, so the storefront HTML formatter must not
 * reach them. Same rule as {@see UcpProductSchema}. Prices are always exclusive
 * of tax: UCP itemises tax in `totals`, so `price` times quantity must equal the
 * line subtotal whatever the store's cart display setting.
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

	/**
	 * Add the id the catalog hands out for the line's product, which names the values
	 * chosen for a variation's "Any" attributes.
	 *
	 * @param array $cart_item Cart item array.
	 * @return array
	 */
	public function get_item_response( $cart_item ) {
		$response = parent::get_item_response( $cart_item );
		$product  = $cart_item['data'] ?? null;

		if ( ! empty( $response ) && $product instanceof \WC_Product_Variation ) {
			$response['catalog_id'] = Utils::variant_id( $product, (array) ( $cart_item['variation'] ?? array() ) );
		}

		return $response;
	}

	/**
	 * Price item prices before tax, ignoring the cart display setting.
	 *
	 * @param string $tax_display_mode Requested mode, ignored.
	 * @return string
	 */
	protected function get_tax_display_mode( $tax_display_mode = '' ) {
		return TaxDisplayMode::EXCLUSIVE;
	}
}
