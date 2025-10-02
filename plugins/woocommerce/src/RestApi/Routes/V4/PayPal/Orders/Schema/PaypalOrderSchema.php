<?php
/**
 * PaypalOrderSchema class.
 *
 * @package Automattic\WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\PayPal\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;

/**
 * PaypalOrderSchema class.
 */
class PaypalOrderSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'paypal-order';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		$schema = array(
			'order_id'        => array(
				'description' => __( 'Order unique identifier.', 'woocommerce' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'paypal_order_id'      => array(
				'description' => __( 'Unique identifier for the order on the PayPal side.', 'woocommerce' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'return_url'     => array(
				'description' => __( 'Store return URL.', 'woocommerce' ),
				'type'        => 'string',
				'readonly'    => true,
			),
		);

		return $schema;
	}

	/**
	 * Get the item response.
	 *
	 * @param array            $order_data PayPal order data.
	 * @param \WP_REST_Request $request Request object.
	 * @param array            $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $order_data, \WP_REST_Request $request, array $include_fields = array() ): array {
		return array(
			'paypal_order_id' => $order_data['id'] ?? null,
			'order_id'        => $order_data['order_id'] ?? null,
			'return_url'      => $order_data['return_url'] ?? null,
		);
	}
}
