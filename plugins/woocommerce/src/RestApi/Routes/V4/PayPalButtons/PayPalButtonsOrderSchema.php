<?php
/**
 * PayPalButtonsOrderSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\PayPalButtons;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;

/**
 * ShippingZoneSchema class.
 */
class PayPalButtonsOrderSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'paypal-buttons-order';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		$schema = array(
			'paypal_order_id' => array(
				'description' => __( 'Unique identifier for the PayPal order (on the PayPal side).', 'woocommerce' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'order_id'        => array(
				'description' => __( 'Unique identifier for the order (WooCommerce side).', 'woocommerce' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'return_url'      => array(
				'description' => __( 'The order return URL.', 'woocommerce' ),
				'type'        => 'string',
				'readonly'    => true,
			),
		);

		return $schema;
	}

	/**
	 * Get the item response.
	 *
	 * @param array            $order_data The order data.
	 * @param \WP_REST_Request $request Request object.
	 * @param array            $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $order_data, \WP_REST_Request $request, array $include_fields = array() ): array {
		return array(
			'paypal_order_id' => $order_data['paypal_order_id'],
			'order_id'        => $order_data['order_id'],
			'return_url'      => $order_data['return_url'],
		);
	}
}
