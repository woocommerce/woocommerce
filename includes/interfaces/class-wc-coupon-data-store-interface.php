<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Coupon Data Store Interface
 *
 * Functions that must be defined by coupon store classes.
 *
 * @version  3.0.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Coupon_Data_Store_Interface {
	/**
	 * Increase usage count for current coupon.
	 * @param WC_Coupon &$coupon
	 * @param string $used_by Either user ID or billing email
	 */
	public function increase_usage_count( &$coupon, $used_by = '' );

	/**
	 *  Decrease usage count for current coupon.
	 * @param WC_Coupon &$coupon
	 * @param string $used_by Either user ID or billing email
	 */
	public function decrease_usage_count( &$coupon, $used_by = '' );

	/**
	 * Get the number of uses for a coupon by user ID.
	 * @param WC_Coupon &$coupon
	 * @param int $user_id
	 * @return int
	 */
	public function get_usage_by_user_id( &$coupon, $user_id );

	/**
	 * Return a coupon code for a specific ID.
	 * @param int $id
	 * @return string Coupon Code
	 */
	 public function get_code_by_id( $id );

	 /**
	  * Return an array of IDs for for a specific coupon code.
	  * Can return multiple to check for existence.
	  * @param string $code
	  * @return array Array of IDs.
	  */
	 public function get_ids_by_code( $code );
}
