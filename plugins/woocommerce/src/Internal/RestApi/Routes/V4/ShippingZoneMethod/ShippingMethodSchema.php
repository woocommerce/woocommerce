<?php
/**
 * Shipping Method Schema.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

/**
 * Shipping Method Schema class.
 */
class ShippingMethodSchema extends AbstractSchema {

	/**
	 * The schema identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shipping_method';

	/**
	 * Return all properties for the item schema
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'instance_id' => array(
				'description' => __( 'Shipping method instance ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'zone_id'     => array(
				'description' => __( 'Shipping zone ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'required'    => true,
			),
			'enabled'     => array(
				'description' => __( 'Whether the shipping method is enabled.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'required'    => true,
			),
			'order'       => array(
				'description'       => __( 'Shipping method sort order.', 'woocommerce' ),
				'type'              => 'integer',
				'context'           => array( 'view', 'edit' ),
				'sanitize_callback' => 'absint',
			),
			'method_id'   => array(
				'description' => __( 'Shipping method ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'required'    => true,
			),
			'settings'    => array(
				'description'          => __( 'Shipping method settings including title and configuration.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => array( 'view', 'edit' ),
				'required'             => true,
				'properties'           => array(
					'title' => array(
						'description' => __( 'Shipping method title.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
				),
				'additionalProperties' => true,
			),
		);
	}

	/**
	 * Get the item response for a shipping method.
	 *
	 * @param object          $method Shipping method instance.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $method, WP_REST_Request $request, array $include_fields = array() ): array {
		if ( isset( $request['zone_id'] ) ) {
			$zone_id = (int) $request['zone_id'];
		} else {
			$data_store = \WC_Data_Store::load( 'shipping-zone' );
			$zone_id    = $data_store->get_zone_id_by_instance_id( $method->instance_id );
		}

		return array(
			'instance_id' => (int) $method->instance_id,
			'zone_id'     => (int) $zone_id,
			'enabled'     => wc_string_to_bool( $method->enabled ),
			'order'       => (int) $method->method_order,
			'method_id'   => $method->id,
			'settings'    => $this->get_method_settings( $method ),
		);
	}

	/**
	 * Get shipping method settings with title included.
	 *
	 * @param object $method Shipping method instance.
	 * @return array Method settings including title.
	 */
	protected function get_method_settings( $method ): array {
		$settings = array();

		// Get the method title (moved from root to settings per Ismael's feedback).
		$settings['title'] = $method->get_title();

		// Get common method settings.
		$common_fields = array( 'cost', 'min_amount', 'requires', 'class_cost', 'no_class_cost', 'tax_status' );

		foreach ( $common_fields as $field ) {
			if ( isset( $method->$field ) ) {
				$settings[ $field ] = $method->$field;
			}
		}

		// Return all available settings for maximum flexibility.
		if ( isset( $method->instance_settings ) && is_array( $method->instance_settings ) ) {
			$settings = array_merge( $settings, $method->instance_settings );
		}

		return $settings;
	}
}
