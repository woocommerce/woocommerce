<?php
/**
 * WooCommerce coupons
 * 
 * The WooCommerce coupons class gets coupon data from storage
 *
 * @class 		woocommerce_coupons
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_coupons {
	
	/** get coupon with $code */
	function get_coupon( $code ) {
		
		$coupon = get_page_by_title( $code, 'OBJECT', 'shop_coupon' );
		
		if ($coupon && $coupon->post_status == 'publish') :
		
			$type 			= get_post_meta($coupon->ID, 'discount_type', true);
			$amount 		= get_post_meta($coupon->ID, 'coupon_amount', true);
			$individual_use = get_post_meta($coupon->ID, 'individual_use', true);
			$product_ids 	= array_map('trim', explode(',', get_post_meta($coupon->ID, 'product_ids', true)));
			$usage_limit 	= get_post_meta($coupon->ID, 'usage_limit', true);
			$usage_count 	= (int) get_post_meta($coupon->ID, 'usage_count', true);
			
			if (!$amount) return false;
			
			return array(
				'id'				=> $coupon->ID,
				'code' 				=> $code,
				'type' 				=> $type,
				'amount'			=> $amount,
				'individual_use'	=> $individual_use,
				'products'			=> $product_ids,
				'usage_limit'		=> $usage_limit,
				'usage_count'		=> $usage_count
			);
			
		endif;
		
		return false;
	}
	
	/** Increase usage count */
	function inc_usage_count( $code ) {
		$coupon = get_page_by_title( $code, 'OBJECT', 'shop_coupon' );
		if ($coupon) :
			$usage_count 	= (int) get_post_meta($coupon->ID, 'usage_count', true);
			$usage_count++;
			update_post_meta($coupon->ID, 'usage_count', $usage_count);
		endif;
	}
	
	/** Check coupon is valid */
	function is_valid($code) {
		$coupon = self::get_coupon($code);
		
		if ($coupon) :
		
			if (sizeof($coupon['products'])>0) :
				$valid = false;
				if (sizeof(woocommerce_cart::$cart_contents)>0) : foreach (woocommerce_cart::$cart_contents as $item_id => $values) :
					if (in_array($item_id, $coupon['products'])) :
						$valid = true;
					endif;
				endforeach; endif;
				if (!$valid) return false;
			endif;
			
			if ($coupon['usage_limit']>0) :
				if ($coupon['usage_count']>$coupon['usage_limit']) :
					return false;
				endif;
			endif;
			
			return true;
		
		endif;
		
		return false;
	}
}
