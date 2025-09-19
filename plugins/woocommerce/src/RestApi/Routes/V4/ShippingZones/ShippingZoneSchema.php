<?php
/**
 * ShippingZoneSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;

/**
 * ShippingZoneSchema class.
 */
class ShippingZoneSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shipping_zone';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public static function get_item_schema_properties(): array {
		$schema = array(
			'id'        => array(
				'description' => __( 'Unique identifier for the shipping zone.', 'woocommerce' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'name'      => array(
				'description' => __( 'Shipping zone name.', 'woocommerce' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'order'     => array(
				'description' => __( 'Shipping zone order.', 'woocommerce' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'locations' => array(
				'description' => __( 'Array of location names for this zone.', 'woocommerce' ),
				'type'        => 'array',
				'readonly'    => true,
				'items'       => array(
					'type' => 'string',
				),
			),
			'methods'   => array(
				'description' => __( 'Shipping methods for this zone.', 'woocommerce' ),
				'type'        => 'array',
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'instance_id' => array(
							'description' => __( 'Shipping method instance ID.', 'woocommerce' ),
							'type'        => 'integer',
						),
						'title'       => array(
							'description' => __( 'Shipping method title.', 'woocommerce' ),
							'type'        => 'string',
						),
						'enabled'     => array(
							'description' => __( 'Whether the shipping method is enabled.', 'woocommerce' ),
							'type'        => 'boolean',
						),
						'method_id'   => array(
							'description' => __( 'Shipping method ID (e.g., flat_rate, free_shipping).', 'woocommerce' ),
							'type'        => 'string',
						),
						'settings'    => array(
							'description' => __( 'Raw shipping method settings for frontend processing.', 'woocommerce' ),
							'type'        => 'object',
						),
					),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the schema.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return self::get_item_schema();
	}
}
