<?php
/**
 * Shipping zones query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\SecretFields;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that lists shipping zones with their locations and methods.
 */
class ShippingZonesQuery extends AbstractDomainAbility implements AbilityDefinition {

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
	 * @return array|\WP_Error A WP_Error when an extension field fails.
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$zones   = array_values( \WC_Shipping_Zones::get_shipping_zones() );
		$zones[] = new \WC_Shipping_Zone( 0 );

		if ( isset( $input['include'] ) ) {
			$include = array_map( 'intval', (array) $input['include'] );
			$zones   = array_filter(
				$zones,
				static function ( \WC_Shipping_Zone $zone ) use ( $include ): bool {
					return in_array( $zone->get_id(), $include, true );
				}
			);
		}

		$formatted = array();
		foreach ( $zones as $zone ) {
			$data = self::format_zone_for_response( $zone );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$formatted[] = $data;
		}

		return array( 'zones' => $formatted );
	}

	/**
	 * Check shipping settings read access.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_query_shipping(): bool {
		return wc_rest_check_manager_permissions( 'settings', 'read' );
	}

	/**
	 * Format a zone for ability output.
	 *
	 * @param \WC_Shipping_Zone $zone Zone.
	 * @return array|\WP_Error A WP_Error when an extension field fails.
	 */
	private static function format_zone_for_response( \WC_Shipping_Zone $zone ) {
		$methods = array();
		foreach ( $zone->get_shipping_methods() as $method ) {
			$data = self::format_method( $method );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$methods[] = $data;
		}

		return ExtensibleAbilities::add_fields_to_object(
			array(
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
				'methods'   => $methods,
			),
			'shipping_zone',
			$zone
		);
	}

	/**
	 * Format a shipping method of a zone. Settings keep the scalar values of declared fields only, without secrets.
	 *
	 * @param \WC_Shipping_Method $method Method.
	 * @return array|\WP_Error A WP_Error when an extension field fails.
	 */
	private static function format_method( \WC_Shipping_Method $method ) {
		$method->init_instance_settings();
		$settings = array();
		foreach ( (array) $method->get_instance_form_fields() as $key => $field ) {
			$value = $method->instance_settings[ $key ] ?? null;
			$type  = (string) ( $field['type'] ?? '' );
			if ( is_scalar( $value ) && 'title' !== $key && ! SecretFields::is_secret( (string) $key, $type ) ) {
				$settings[ $key ] = (string) $value;
			}
		}

		return ExtensibleAbilities::add_fields_to_object(
			array(
				'instance_id' => (int) $method->get_instance_id(),
				'method_id'   => (string) $method->id,
				'title'       => (string) $method->get_title(),
				'enabled'     => 'yes' === $method->enabled,
				'order'       => (int) $method->method_order,
				'settings'    => (object) $settings,
			),
			'shipping_method',
			$method
		);
	}

	/**
	 * Get the schema of a zone in a response.
	 *
	 * @return array
	 */
	private static function get_zone_output_schema(): array {
		return ExtensibleAbilities::add_fields_schema(
			array(
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
			),
			'shipping_zone'
		);
	}

	/**
	 * Get the schema of a shipping method in a response.
	 *
	 * @return array
	 */
	private static function get_method_output_schema(): array {
		return ExtensibleAbilities::add_fields_schema(
			array(
				'type'                 => 'object',
				'properties'           => array(
					'instance_id' => array( 'type' => 'integer' ),
					'method_id'   => array( 'type' => 'string' ),
					'title'       => array( 'type' => 'string' ),
					'enabled'     => array( 'type' => 'boolean' ),
					'order'       => array( 'type' => 'integer' ),
					'settings'    => array(
						'type'                 => 'object',
						'description'          => __( 'Method settings such as cost or min_amount, as stored.', 'woocommerce' ),
						'additionalProperties' => array( 'type' => 'string' ),
					),
				),
				'additionalProperties' => false,
			),
			'shipping_method'
		);
	}
}
