<?php
/**
 * Shipping zones query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ShippingZoneAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ability that lists shipping zones with their locations and methods.
 */
class ShippingZonesQuery extends AbstractDomainAbility implements AbilityDefinition {

	use ShippingZoneAbilityTrait;

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
}
