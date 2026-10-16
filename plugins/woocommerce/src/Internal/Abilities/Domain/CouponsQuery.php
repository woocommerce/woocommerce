<?php
/**
 * Coupons query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\CouponAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce coupons query ability.
 */
class CouponsQuery extends AbstractDomainAbility implements AbilityDefinition {

	use CouponAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function get_name(): string {
		return 'woocommerce/coupons-query';
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
			'label'               => __( 'Query coupons', 'woocommerce' ),
			'description'         => __(
				'Find coupons by ID, exact code, or search term.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_collection_output_schema( 'coupons', self::get_coupon_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_coupons' ),
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
	 * Query coupons.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.3.0
	 */
	public static function execute( array $input ) {
		if ( ! empty( $input['code'] ) && empty( $input['id'] ) ) {
			$input['id'] = wc_get_coupon_id_by_code( (string) $input['code'] );

			if ( ! $input['id'] ) {
				return new \WP_Error(
					'woocommerce_coupon_not_found',
					__( 'Coupon not found.', 'woocommerce' ),
					array( 'status' => 404 )
				);
			}
		}

		if ( ! empty( $input['id'] ) ) {
			$coupon = self::get_coupon_from_input( $input );

			if ( is_wp_error( $coupon ) ) {
				return $coupon;
			}

			return array(
				'coupons'     => array( self::format_coupon_for_response( $coupon ) ),
				'total_pages' => 1,
				'page'        => 1,
				'per_page'    => 1,
			);
		}

		$page     = (int) ( $input['page'] ?? 1 );
		$per_page = (int) ( $input['per_page'] ?? 10 );
		$args     = array(
			'post_type'      => 'shop_coupon',
			'post_status'    => ! empty( $input['status'] ) ? sanitize_key( $input['status'] ) : self::get_coupon_status_slugs(),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = wc_clean( $input['search'] );
		}

		$query = new \WP_Query( $args );

		return array(
			'coupons'     => array_map(
				static function ( $coupon_id ) {
					return self::format_coupon_for_response( new \WC_Coupon( (int) $coupon_id ) );
				},
				$query->posts
			),
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Check coupon read access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public static function can_query_coupons( $input = array() ): bool {
		return wc_rest_check_post_permissions( 'shop_coupon', 'read', self::get_id_from_input( $input ) );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'code'     => array(
					'type'        => 'string',
					'description' => __( 'Exact coupon code. Only published coupons are matched.', 'woocommerce' ),
				),
				'search'   => array( 'type' => 'string' ),
				'status'   => array(
					'type' => 'string',
					'enum' => self::get_coupon_status_slugs(),
				),
				'page'     => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
	}
}
