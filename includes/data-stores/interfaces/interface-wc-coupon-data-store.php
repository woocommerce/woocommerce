<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Coupon Data Store Interface
 *
 * Functions that must be defined by coupon store classes.
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Coupon_Data_Store {
	/**
	 * Increase usage count for current coupon.
	 * @param WC_Coupon
	 * @param string $used_by Either user ID or billing email
	 */
	public function increase_usage_count( &$coupon, $used_by = '' );

	/**
	 *  Decrease usage count for current coupon.
	 * @param WC_Coupon
	 * @param string $used_by Either user ID or billing email
	 */
	public function decrease_usage_count( &$coupon, $used_by = '' );

	/**
	 * Get the number of uses for a coupon by user ID.
	 * @param WC_Coupon
	 * @param id $user_id
	 * @return int
	 */
	public function get_usage_by_user_id( &$coupon, $user_id );
}
