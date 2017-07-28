<?php
/**
 * Legacy cart
 *
 * Legacy and deprecated functions are here to keep the WC_Cart class clean.
 * This class will be removed in future versions.
 *
 * @version  3.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy cart class.
 */
abstract class WC_Legacy_Cart {

	/**
	 * Contains an array of coupon usage counts after they have been applied.
	 *
	 * @var array
	 */
	public $coupon_applied_count = array();

	/**
	 * Store how many times each coupon is applied to cart/items.
	 *
	 * @deprecated 3.2.0
	 * @access private
	 * @param string $code
	 * @param int    $count
	 */
	protected function increase_coupon_applied_count( $code, $count = 1 ) {
		if ( empty( $this->coupon_applied_count[ $code ] ) ) {
			$this->coupon_applied_count[ $code ] = 0;
		}
		$this->coupon_applied_count[ $code ] += $count;
	}
}
