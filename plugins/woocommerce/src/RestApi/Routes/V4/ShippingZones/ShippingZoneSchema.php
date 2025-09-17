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
	 * Note that context determines under which context data should be visible. For example, edit would be the context
	 * used when getting records with the intent of editing them. embed context allows the data to be visible when the
	 * item is being embedded in another response.
	 *
	 * @return array
	 */
	public static function get_item_schema_properties(): array {
		$schema = array(
			'id'        => array(
				'description' => __( 'Unique identifier for the shipping zone.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'name'      => array(
				'description' => __( 'Shipping zone name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'order'     => array(
				'description' => __( 'Shipping zone order.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'locations' => array(
				'description' => __( 'Zone locations. Returns formatted string for view context, detailed array for edit context.', 'woocommerce' ),
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
				'oneOf'       => array(
					array(
						'type'        => 'string',
						'description' => __( 'Formatted location string for display (view context).', 'woocommerce' ),
					),
					array(
						'type'        => 'array',
						'description' => __( 'Array of location objects with detailed information (edit context).', 'woocommerce' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'code'         => array(
									'description' => __( 'Location code (e.g., "US", "US:CA").', 'woocommerce' ),
									'type'        => 'string',
								),
								'type'         => array(
									'description' => __( 'Location type.', 'woocommerce' ),
									'type'        => 'string',
									'enum'        => array( 'continent', 'country', 'state', 'postcode' ),
								),
								'name'         => array(
									'description' => __( 'Human-readable location name.', 'woocommerce' ),
									'type'        => 'string',
								),
								'country_name' => array(
									'description' => __( 'Country name (for state locations only).', 'woocommerce' ),
									'type'        => 'string',
								),
							),
						),
					),
				),
			),
			'methods'   => array(
				'description' => __( 'Shipping methods for this zone.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'instance_id'      => array(
							'description' => __( 'Shipping method instance ID.', 'woocommerce' ),
							'type'        => 'integer',
						),
						'method_id'        => array(
							'description' => __( 'Shipping method ID (edit context only).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::EDIT_CONTEXT,
						),
						'title'            => array(
							'description' => __( 'Shipping method title.', 'woocommerce' ),
							'type'        => 'string',
						),
						'enabled'          => array(
							'description' => __( 'Whether the shipping method is enabled.', 'woocommerce' ),
							'type'        => 'boolean',
						),
						'rate_description' => array(
							'description' => __( 'Formatted rate description for display (view context only).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_CONTEXT,
						),
						'settings'         => array(
							'description' => __( 'Shipping method settings (edit context only).', 'woocommerce' ),
							'type'        => 'object',
							'context'     => self::EDIT_CONTEXT,
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
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'shipping_zone',
			'type'       => 'object',
			'properties' => self::get_item_schema_properties(),
		);
	}
}
