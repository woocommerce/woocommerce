<?php
/**
 * PaypalShippingSchema class.
 *
 * @package Automattic\WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\PayPal\Shipping\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;

/**
 * PaypalShippingSchema class.
 */
class PaypalShippingSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'paypal-shipping';

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
				'description' => __( 'Purchase units for this PayPal order.', 'woocommerce' ),
				'type'        => 'array',
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'reference_id'     => array(
							'description' => __( 'Unit reference ID.', 'woocommerce' ),
							'type'        => 'string',
						),
						'amount'           => array(
							'description' => __( 'Breakdown of the unit amount.', 'woocommerce' ),
							'type'        => 'array',
						),
						'shipping_options' => array(
							'description' => __( 'Shipping options for this unit.', 'woocommerce' ),
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
	 * @param array            $shipping_data PayPal shipping data.
	 * @param \WP_REST_Request $request Request object.
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
