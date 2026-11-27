<?php
/**
 * Coupons query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
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
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/coupons-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		$output_schema                        = self::get_collection_output_schema( 'coupons', self::get_coupon_output_schema() );
		$output_schema['properties']['total'] = array( 'type' => 'integer' );
		return array(
			'label'               => __( 'Query coupons', 'woocommerce' ),
			'description'         => __(
				'Find coupons by ID, exact code, or search term.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => $output_schema,
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
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$page     = (int) ( $input['page'] ?? 1 );
		$per_page = (int) ( $input['per_page'] ?? 10 );
		$args     = array(
			'post_type'      => 'shop_coupon',
			'post_status'    => ! empty( $input['status'] ) ? $input['status'] : self::get_coupon_status_slugs(),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( isset( $input['search'] ) && '' !== $input['search'] ) {
			$args['s'] = wc_clean( $input['search'] );
		}

		if ( isset( $input['include'] ) ) {
			$args['post__in'] = $input['include'];
		}
		if ( isset( $input['code'] ) ) {
			$args['title'] = wc_format_coupon_code( $input['code'] );
			if ( '' === $args['title'] ) {
				return new \WP_Error( 'woocommerce_ability_coupon_code_required', __( 'Coupon code is required.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}
		if ( isset( $input['discount_type'] ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Coupon discount types are stored only in post meta.
				array(
					'key'   => 'discount_type',
					'value' => $input['discount_type'],
				),
			);
		}

		$extension_args = ExtensibleAbilities::get_query_args( 'coupon', $input );
		if ( is_wp_error( $extension_args ) ) {
			return $extension_args;
		}
		if ( isset( $args['post__in'] ) ) {
			// A recursive merge would add the extension IDs to include, and WP_Query ignores post__not_in next to post__in.
			$include = array_diff( $args['post__in'], $extension_args['post__not_in'] ?? array() );
			if ( isset( $extension_args['post__in'] ) ) {
				$include = array_intersect( $include, $extension_args['post__in'] );
			}
			$args['post__in'] = empty( $include ) ? array( 0 ) : array_values( $include );
			unset( $extension_args['post__in'], $extension_args['post__not_in'] );
		}
		$args = array_merge_recursive( $args, $extension_args );

		$args['orderby'] = array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		);
		$query           = new \WP_Query( $args );
		$total           = (int) $query->found_posts;
		if ( empty( $query->posts ) && $page > 1 ) {
			$args['paged']          = 1;
			$args['posts_per_page'] = 1;
			$args['fields']         = 'ids';
			$count_query            = new \WP_Query( $args );
			$total                  = (int) $count_query->found_posts;
		}

		$coupons = array_map(
			static function ( $coupon_id ) {
				return new \WC_Coupon( $coupon_id instanceof \WP_Post ? $coupon_id->ID : $coupon_id );
			},
			$query->posts
		);
		ExtensibleAbilities::prime( 'coupon', $coupons );
		$formatted = array();
		foreach ( $coupons as $coupon ) {
			$data = self::format_coupon_for_response( $coupon );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$formatted[] = $data;
		}

		return array(
			'coupons'     => $formatted,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
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
	 * @since 11.4.0
	 */
	public static function can_query_coupons( $input = array() ): bool {
		return (bool) wc_rest_check_post_permissions( 'shop_coupon', 'read', self::get_id_from_input( $input ) );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'include'       => array(
					'type'     => 'array',
					'minItems' => 1,
					'items'    => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				'discount_type' => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_coupon_types() ),
				),
				'code'          => array(
					'type'        => 'string',
					'description' => __( 'Exact coupon code. Matches coupons in any supported status.', 'woocommerce' ),
				),
				'search'        => array( 'type' => 'string' ),
				'status'        => array(
					'type'     => 'array',
					'items'    => array(
						'type' => 'string',
						'enum' => self::get_coupon_status_slugs(),
					),
					'minItems' => 1,
				),
				'page'          => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page'      => array(
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
		return ExtensibleAbilities::add_query_filters_schema( $schema, 'coupon' );
	}
}
