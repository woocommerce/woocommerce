<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Coupon {

	/**
	 * Create a dummy coupon.
	 *
	 * @return WC_Coupon
	 */
	public static function create_coupon( $coupon_code = 'dummycoupon' ) {
		// Insert post
		$coupon_id = wp_insert_post( array(
			'post_title'   => $coupon_code,
			'post_type'    => 'shop_coupon',
			'post_status'  => 'publish',
			'post_excerpt' => 'This is a dummy coupon',
		) );

		// Update meta
		update_post_meta( $coupon_id, 'discount_type', 'fixed_cart' );
		update_post_meta( $coupon_id, 'coupon_amount', '1' );
		update_post_meta( $coupon_id, 'individual_use', 'no' );
		update_post_meta( $coupon_id, 'product_ids', '' );
		update_post_meta( $coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $coupon_id, 'usage_limit', '' );
		update_post_meta( $coupon_id, 'usage_limit_per_user', '' );
		update_post_meta( $coupon_id, 'limit_usage_to_x_items', '' );
		update_post_meta( $coupon_id, 'expiry_date', '' );
		update_post_meta( $coupon_id, 'free_shipping', 'no' );
		update_post_meta( $coupon_id, 'exclude_sale_items', 'no' );
		update_post_meta( $coupon_id, 'product_categories', array() );
		update_post_meta( $coupon_id, 'exclude_product_categories', array() );
		update_post_meta( $coupon_id, 'minimum_amount', '' );
		update_post_meta( $coupon_id, 'maximum_amount', '' );
		update_post_meta( $coupon_id, 'customer_email', array() );
		update_post_meta( $coupon_id, 'usage_count', '0' );

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
}
