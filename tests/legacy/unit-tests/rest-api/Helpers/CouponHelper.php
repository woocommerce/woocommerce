<?php
/**
 * CouponHelper.
 *
 * This helper class should ONLY be used for unit tests!.
 */

namespace Automattic\WooCommerce\RestApi\UnitTests\Helpers;

defined( 'ABSPATH' ) || exit;

use \WC_Coupon;

/**
 * Class CouponHelper.
 */
class CouponHelper {

	protected static $custom_types = array();

	/**
	 * Create a dummy coupon.
	 *
	 * @param string $coupon_code
	 * @param array  $meta
	 *
	 * @return WC_Coupon
	 */
	public static function create_coupon( $coupon_code = 'dummycoupon', $meta = array() ) {
		// Insert post
		$coupon_id = wp_insert_post(
			array(
				'post_title'   => $coupon_code,
				'post_type'    => 'shop_coupon',
				'post_status'  => 'publish',
				'post_excerpt' => 'This is a dummy coupon',
			)
		);

		$meta = wp_parse_args(
			$meta,
			array(
				'discount_type'              => 'fixed_cart',
				'coupon_amount'              => '1',
				'individual_use'             => 'no',
				'product_ids'                => '',
				'exclude_product_ids'        => '',
				'usage_limit'                => '',
				'usage_limit_per_user'       => '',
				'limit_usage_to_x_items'     => '',
				'expiry_date'                => '',
				'free_shipping'              => 'no',
				'exclude_sale_items'         => 'no',
				'product_categories'         => array(),
				'exclude_product_categories' => array(),
				'minimum_amount'             => '',
				'maximum_amount'             => '',
				'customer_email'             => array(),
				'usage_count'                => '0',
			)
		);

		// Update meta.
		foreach ( $meta as $key => $value ) {
			update_post_meta( $coupon_id, $key, $value );
		}

		return new WC_Coupon( $coupon_code );
	}

	/**
	 * Delete a coupon.
	 *
	 * @param $coupon_id
	 *
	 * @return bool
	 */
	public static function delete_coupon( $coupon_id ) {
		wp_delete_post( $coupon_id, true );

		return true;
	}

	/**
	 * Register a custom coupon type.
	 *
	 * @param string $coupon_type
	 */
	public static function register_custom_type( $coupon_type ) {
		static $filters_added = false;
		if ( isset( self::$custom_types[ $coupon_type ] ) ) {
			return;
		}

		self::$custom_types[ $coupon_type ] = "Testing custom type {$coupon_type}";

		if ( ! $filters_added ) {
			add_filter( 'woocommerce_coupon_discount_types', array( __CLASS__, 'filter_discount_types' ) );
			add_filter( 'woocommerce_coupon_get_discount_amount', array( __CLASS__, 'filter_get_discount_amount' ), 10, 5 );
			add_filter( 'woocommerce_coupon_is_valid_for_product', '__return_true' );
			$filters_added = true;
		}
	}

	/**
	 * Unregister custom coupon type.
	 *
	 * @param $coupon_type
	 */
	public static function unregister_custom_type( $coupon_type ) {
		unset( self::$custom_types[ $coupon_type ] );
		if ( empty( self::$custom_types ) ) {
			remove_filter( 'woocommerce_coupon_discount_types', array( __CLASS__, 'filter_discount_types' ) );
			remove_filter( 'woocommerce_coupon_get_discount_amount', array( __CLASS__, 'filter_get_discount_amount' ) );
			remove_filter( 'woocommerce_coupon_is_valid_for_product', '__return_true' );
		}
	}

	/**
	 * Register custom discount types.
	 *
	 * @param array $discount_types
	 * @return array
	 */
	public static function filter_discount_types( $discount_types ) {
		return array_merge( $discount_types, self::$custom_types );
	}

	/**
	 * Get custom discount type amount. Works like 'percent' type.
	 *
	 * @param float      $discount
	 * @param float      $discounting_amount
	 * @param array|null $item
	 * @param bool       $single
	 * @param WC_Coupon  $coupon
	 *
	 * @return float
	 */
	public static function filter_get_discount_amount( $discount, $discounting_amount, $item, $single, $coupon ) {
		if ( ! isset( self::$custom_types [ $coupon->get_discount_type() ] ) ) {
			return $discount;
		}

		return (float) $coupon->get_amount() * ( $discounting_amount / 100 );
	}
}
