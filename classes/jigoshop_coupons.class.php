<?php
/**
 * Jigoshop coupons
 * @class 		jigoshop_coupons
 * 
 * The JigoShop coupons class gets coupon data from storage
 *
 * @author 		Jigowatt
 * @category 	Classes
 * @package 	JigoShop
 */
class jigoshop_coupons {
	
	/** get coupons from the options database */
	function get_coupons() {
		$coupons = get_option('jigoshop_coupons') ? $coupons = (array) get_option('jigoshop_coupons') : $coupons = array();
		return $coupons;
	}
	
	/** get coupon with $code */
	function get_coupon($code) {
		$coupons = get_option('jigoshop_coupons') ? $coupons = (array) get_option('jigoshop_coupons') : $coupons = array();
		if (isset($coupons[$code])) return $coupons[$code];
		return false;
	}
	
	/** Check coupon is valid by looking at cart */
	function is_valid($code) {
		$coupon = self::get_coupon($code);
		if (sizeof($coupon['products'])>0) :
			$valid = false;
			if (sizeof(jigoshop_cart::$cart_contents)>0) : foreach (jigoshop_cart::$cart_contents as $item_id => $values) :
				if (in_array($item_id, $coupon['products'])) :
					$valid = true;
				endif;
			endforeach; endif;
			return $valid;
		endif;
		return true;
	}
}