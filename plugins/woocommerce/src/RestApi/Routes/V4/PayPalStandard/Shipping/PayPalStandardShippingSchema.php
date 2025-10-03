<?php
/**
 * PayPalStandardShippingSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\PayPalStandard\Shipping;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;

/**
 * PayPalStandardShippingSchema class.
 */
class PayPalStandardShippingSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'paypal-standard-shipping';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		$schema = array(
			'id'             => array(
				'description' => __( 'Unique identifier for the PayPal order (on the PayPal side).', 'woocommerce' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'purchase_units' => array(
				'description' => __( 'Purchase units on this order.', 'woocommerce' ),
				'type'        => 'array',
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'reference_id'     => array(
							'description' => __( 'Purchase unit reference ID.', 'woocommerce' ),
							'type'        => 'string',
						),
						'amount'           => array(
							'description' => __( 'Array with the detailed amount.', 'woocommerce' ),
							'type'        => 'array',
						),
						'shipping_options' => array(
							'description' => __( 'Shipping options for the purchase unit', 'woocommerce' ),
							'type'        => 'array',
						),
					),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the item response.
	 *
	 * @param array $shipping_data The shipping data array;
	 * @param \WP_REST_Request  $request Request object.
	 * @param array            $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $shipping_data, \WP_REST_Request $request, array $include_fields = array() ): array {
		return array(
			'id'             => $shipping_data['id'],
			'purchase_units' => $shipping_data['purchase_units'],
		);
	}
}
