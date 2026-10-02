<?php
/**
 * Coupon create ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\CouponAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce coupon create ability.
 */
class CouponCreate extends AbstractDomainAbility implements AbilityDefinition {

	use CouponAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function get_name(): string {
		return 'woocommerce/coupon-create';
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
			'label'               => __( 'Create coupon', 'woocommerce' ),
			'description'         => __(
				'Create a coupon. Defaults to a published fixed cart discount.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'coupon', self::get_coupon_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_create_coupon' ),
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
	 * Create a coupon.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.3.0
	 */
	public static function execute( array $input ) {
		$coupon = new \WC_Coupon();

		$result = self::set_coupon_props_from_input( $coupon, $input );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$saved = self::save_coupon( $coupon, sanitize_key( $input['status'] ?? 'publish' ), 'woocommerce_coupon_create_failed' );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'coupon' => self::format_coupon_for_response( new \WC_Coupon( $coupon->get_id() ) ),
		);
	}

	/**
	 * Check coupon create access.
	 *
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public static function can_create_coupon(): bool {
		return wc_rest_check_post_permissions( 'shop_coupon', 'create' );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => self::get_coupon_writable_properties_schema(),
			'required'             => array( 'code' ),
			'additionalProperties' => false,
		);
	}
}
