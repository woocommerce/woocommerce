<?php
/**
 * Cart schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * CartSchema class.
 *
 * @since 2.5.0
 */
class CartSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'cart';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	protected function get_properties() {
		return [
			'currency'       => array(
				'description' => __( 'Currency code (in ISO format) of the cart item prices.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => get_woocommerce_currency(),
				'enum'        => array_keys( get_woocommerce_currencies() ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'item_count'     => array(
				'description' => __( 'Number of items in the cart.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'items'          => array(
				'description' => __( 'List of cart items.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->force_schema_readonly( ( new CartItemSchema() )->get_properties() ),
				),
			),
			'needs_shipping' => array(
				'description' => __( 'True if the cart needs shipping. False for carts with only digital goods or stores with no shipping methods set-up.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total_price'    => array(
				'description' => __( 'Total price of all products in the cart.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'total_weight'   => array(
				'description' => __( 'Total weight (in grams) of all products in the cart.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		];
	}

	/**
	 * Convert a woo cart into an object suitable for the response.
	 *
	 * @param \WC_Cart $cart Cart class instance.
	 * @return array
	 */
	public function get_item_response( $cart ) {
		$cart_item_schema = new CartItemSchema();
		return [
			'currency'       => get_woocommerce_currency(),
			'item_count'     => $cart->get_cart_contents_count(),
			'items'          => array_values( array_map( [ $cart_item_schema, 'get_item_response' ], array_filter( $cart->get_cart() ) ) ),
			'needs_shipping' => $cart->needs_shipping(),
			'total_price'    => $cart->get_cart_contents_total(),
			'total_weight'   => wc_get_weight( $cart->get_cart_contents_weight(), 'g' ),
		];
	}
}
