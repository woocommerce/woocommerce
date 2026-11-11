<?php
/**
 * Shipping zone method update ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ShippingZoneAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Changes a flat rate, free shipping or local pickup method of a zone. Extension methods are refused.
 */
class ShippingZoneMethodUpdate extends AbstractDomainAbility implements AbilityDefinition {

	use ShippingZoneAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/shipping-zone-method-update';
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
			'label'               => __( 'Update shipping method', 'woocommerce' ),
			'description'         => __( 'Turn a flat rate, free shipping or local pickup method of a zone on or off, move it, or change its settings, such as the cost or the free shipping minimum. Get the instance_id from shipping-zones-query.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array_merge(
					array(
						'instance_id' => array(
							'type'    => 'integer',
							'minimum' => 1,
						),
					),
					self::get_method_write_input_properties()
				),
				'required'             => array( 'instance_id' ),
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
					'idempotent'  => true,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Write the changes to the method.
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

		$instance_id = $input['instance_id'];
		$method      = \WC_Shipping_Zones::get_shipping_method( $instance_id );
		$zone        = $method ? \WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id ) : false;
		if ( ! $method instanceof \WC_Shipping_Method || ! $zone instanceof \WC_Shipping_Zone ) {
			return new \WP_Error(
				'woocommerce_ability_shipping_method_not_found',
				/* translators: %d: shipping method instance ID. */
				sprintf( __( 'There is no shipping method with instance ID %d.', 'woocommerce' ), $instance_id ),
				array( 'status' => 404 )
			);
		}

		if ( ! self::is_core_method( $method ) ) {
			return self::get_unsupported_method_error( $method->id );
		}

		$error = self::write_method_changes( $method, $zone, $input );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		return self::get_saved_method_response( $instance_id );
	}
}
