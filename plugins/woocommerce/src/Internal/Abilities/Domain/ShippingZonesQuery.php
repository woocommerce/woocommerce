<?php
/**
 * Shipping zones query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that lists shipping zones with their locations and methods.
 */
class ShippingZonesQuery extends AbstractDomainAbility implements AbilityDefinition {

	/**
	 * Methods whose settings are returned. Extensions keep credentials in method settings under any name.
	 */
	private const CORE_METHOD_CLASSES = array( \WC_Shipping_Flat_Rate::class, \WC_Shipping_Free_Shipping::class, \WC_Shipping_Local_Pickup::class );

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/shipping-zones-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query shipping zones', 'woocommerce' ),
			'description'         => __( 'List shipping zones in matching order, with the regions each covers and its shipping methods. Zone 0 covers every location no other zone matches.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'include' => array(
						'type'        => 'array',
						'description' => __( 'Return only these zone IDs.', 'woocommerce' ),
						'items'       => array(
							'type'    => 'integer',
							'minimum' => 0,
						),
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'zones' => array(
						'type'  => 'array',
						'items' => self::get_zone_output_schema(),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_shipping' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * List shipping zones.
	 *
	 * @param array $input Ability input.
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ): array {
		$zones   = array_values( \WC_Shipping_Zones::get_shipping_zones() );
		$zones[] = new \WC_Shipping_Zone( 0 );

		if ( isset( $input['include'] ) ) {
			$include = $input['include'];
			$zones   = array_filter(
				$zones,
				static function ( \WC_Shipping_Zone $zone ) use ( $include ): bool {
					return in_array( $zone->get_id(), $include, true );
				}
			);
		}

		return array( 'zones' => array_values( array_map( array( __CLASS__, 'format_zone_for_response' ), $zones ) ) );
	}

	/**
	 * Check shipping settings read access.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_query_shipping(): bool {
		return (bool) wc_rest_check_manager_permissions( 'settings', 'read' );
	}

	/**
	 * Format a zone for ability output.
	 *
	 * @param \WC_Shipping_Zone $zone Zone.
	 * @return array
	 */
	private static function format_zone_for_response( \WC_Shipping_Zone $zone ): array {
		return array(
			'id'        => $zone->get_id(),
			'name'      => $zone->get_zone_name(),
			'order'     => $zone->get_zone_order(),
			'locations' => array_map(
				static function ( $location ): array {
					return array(
						'code' => (string) $location->code,
						'type' => (string) $location->type,
					);
				},
				array_values( $zone->get_zone_locations() )
			),
			'methods'   => array_map( array( __CLASS__, 'format_method' ), array_values( $zone->get_shipping_methods() ) ),
		);
	}

	/**
	 * Format a shipping method of a zone. Only Core methods get settings, limited to the
	 * fields Core declares, since extensions can add fields to them through filters.
	 *
	 * @param \WC_Shipping_Method $method Method.
	 * @return array
	 */
	private static function format_method( \WC_Shipping_Method $method ): array {
		$data = array(
			'instance_id' => (int) $method->get_instance_id(),
			'method_id'   => (string) $method->id,
			'title'       => (string) $method->get_title(),
			'enabled'     => 'yes' === $method->enabled,
			'order'       => (int) $method->method_order,
		);

		$class = get_class( $method );
		if ( in_array( $class, self::CORE_METHOD_CLASSES, true ) ) {
			// A fresh instance, since a woocommerce_shipping_zone_shipping_methods callback can change the zone's one.
			$core = new $class( $method->get_instance_id() );
			$core->init_instance_settings();
			$settings = array();
			foreach ( array_keys( $core->instance_form_fields ) as $key ) {
				$value = $core->instance_settings[ $key ] ?? null;
				if ( 'title' !== $key && is_scalar( $value ) ) {
					$settings[ $key ] = (string) $value;
				}
			}
			$data['settings'] = (object) $settings;
		}

		return $data;
	}

	/**
	 * Get the schema of a zone in a response.
	 *
	 * @return array
	 */
	private static function get_zone_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'        => array( 'type' => 'integer' ),
				'name'      => array( 'type' => 'string' ),
				'order'     => array( 'type' => 'integer' ),
				'locations' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'code' => array(
								'type'        => 'string',
								'description' => __( 'Country code, COUNTRY:STATE, postcode pattern or continent code, by type.', 'woocommerce' ),
							),
							'type' => array(
								'type' => 'string',
								'enum' => array( 'country', 'state', 'postcode', 'continent' ),
							),
						),
						'additionalProperties' => false,
					),
				),
				'methods'   => array(
					'type'  => 'array',
					'items' => self::get_method_output_schema(),
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the schema of a shipping method in a response.
	 *
	 * @return array
	 */
	private static function get_method_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'instance_id' => array( 'type' => 'integer' ),
				'method_id'   => array( 'type' => 'string' ),
				'title'       => array( 'type' => 'string' ),
				'enabled'     => array( 'type' => 'boolean' ),
				'order'       => array( 'type' => 'integer' ),
				'settings'    => array(
					'type'                 => 'object',
					'description'          => __( 'Settings such as cost or min_amount, as stored. Only for the flat rate, free shipping and local pickup methods.', 'woocommerce' ),
					'additionalProperties' => array( 'type' => 'string' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
