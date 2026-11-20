<?php
/**
 * Shipping zone method create ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ShippingZoneAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a flat rate, free shipping or local pickup method to a zone.
 *
 * A method is a table row plus an option, not a WC_Data object, so a failed settings write removes the method again instead of rolling back.
 * Listeners then see woocommerce_shipping_zone_method_added and, right after, woocommerce_shipping_zone_method_deleted.
 */
class ShippingZoneMethodCreate extends AbstractDomainAbility implements AbilityDefinition {

	use ShippingZoneAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/shipping-zone-method-create';
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
			'label'               => __( 'Add shipping method to zone', 'woocommerce' ),
			'description'         => __( 'Add a flat rate, free shipping or local pickup method to a shipping zone, with its settings. Zone 0 covers every location no other zone matches.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array_merge(
					array(
						'zone_id'   => array(
							'type'    => 'integer',
							'minimum' => 0,
						),
						'method_id' => array(
							'type' => 'string',
							'enum' => array_keys( self::$writable_method_settings ),
						),
					),
					self::get_method_write_input_properties()
				),
				'required'             => array( 'zone_id', 'method_id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => self::get_entity_output_schema( 'method', self::get_method_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_edit_shipping' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Add the method to the zone and write its settings.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$disabled = self::get_shipping_disabled_error();
		if ( null !== $disabled ) {
			return $disabled;
		}

		$zone = self::get_zone_or_error( $input['zone_id'] );
		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		// A woocommerce_shipping_methods callback can remove a Core method or replace its class.
		$available = WC()->shipping()->get_shipping_methods();
		$prototype = $available[ $input['method_id'] ] ?? null;
		if ( ! $prototype instanceof \WC_Shipping_Method || ! self::is_core_method( $prototype ) ) {
			return self::get_unsupported_method_error( $input['method_id'] );
		}

		$instance_id = $zone->add_shipping_method( $input['method_id'] );
		$method      = $instance_id ? \WC_Shipping_Zones::get_shipping_method( $instance_id ) : false;
		if ( ! $method instanceof \WC_Shipping_Method ) {
			if ( $instance_id ) {
				$zone->delete_shipping_method( $instance_id );
			}
			return new \WP_Error( 'woocommerce_ability_shipping_method_create_failed', __( 'The shipping method could not be added to the zone.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$error = self::write_method_changes( $method, $zone, $input );
		if ( is_wp_error( $error ) ) {
			$zone->delete_shipping_method( $instance_id );
			return $error;
		}

		return self::get_saved_method_response( $instance_id );
	}
}
