<?php
/**
 * Coupon update ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\CouponAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce coupon update ability.
 */
class CouponUpdate extends AbstractDomainAbility implements AbilityDefinition {

	use CouponAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function get_name(): string {
		return 'woocommerce/coupon-update';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.3.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update coupon', 'woocommerce' ),
			'description'         => __(
				'Update an existing coupon, such as its amount, expiry date or usage limits.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'coupon', self::get_coupon_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_update_coupon' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Update a coupon.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.3.0
	 */
	public static function execute( array $input ) {
		$coupon = self::get_coupon_from_input( $input );

		if ( is_wp_error( $coupon ) ) {
			return $coupon;
		}

		if ( empty( array_diff( array_keys( $input ), array( 'id' ) ) ) ) {
			return new \WP_Error(
				'woocommerce_coupon_update_no_fields',
				__( 'At least one coupon field is required to update a coupon.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$result = self::set_coupon_props_from_input( $coupon, $input );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$status = isset( $input['status'] ) ? sanitize_key( $input['status'] ) : null;
		$saved  = self::save_coupon( $coupon, $status, 'woocommerce_coupon_update_failed' );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'coupon' => self::format_coupon_for_response( new \WC_Coupon( $coupon->get_id() ) ),
		);
	}

	/**
	 * Check coupon update access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public static function can_update_coupon( $input = array() ): bool {
		$coupon_id = self::get_id_from_input( $input );

		return $coupon_id > 0 && wc_rest_check_post_permissions( 'shop_coupon', 'edit', $coupon_id );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array_merge(
				array(
					'id' => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				self::get_coupon_writable_properties_schema()
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}
}
