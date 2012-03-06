<?php
/**
 * WooCommerce coupons
 * 
 * The WooCommerce coupons class gets coupon data from storage and checks coupon validity
 *
 * @class 		WC_Coupon
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class WC_Coupon {
	
	var $code;
	var $id;
	var $type;
	var $amount;
	var $individual_use;
	var $product_ids;
	var $usage_limit;
	var $usage_count;
	var $expiry_date;
	var $apply_before_tax;
	var $free_shipping;
	var $product_categories;
	var $exclude_product_categories;
	
	/** get coupon with $code */
	function __construct( $code ) {
		global $wpdb;
		 
		$this->code = esc_attr($code);
		
		$coupon_data = apply_filters('woocommerce_get_shop_coupon_data', false, $code);

        if ($coupon_data) :
            $this->id = $coupon_data['id'];
            $this->type = $coupon_data['type'];
            $this->amount = $coupon_data['amount'];
            $this->individual_use = $coupon_data['individual_use'];
            $this->product_ids = $coupon_data['product_ids'];
            $this->exclude_product_ids = $coupon_data['exclude_product_ids'];
            $this->usage_limit = $coupon_data['usage_limit'];
            $this->usage_count = $coupon_data['usage_count'];
            $this->expiry_date = $coupon_data['expiry_date'];
            $this->apply_before_tax = $coupon_data['apply_before_tax'];
            $this->free_shipping = $coupon_data['free_shipping'];
            $this->product_categories = $coupon_data['product_categories'];
            $this->exclude_product_categories = $coupon_data['exclude_product_categories'];
            return true;
        else:
            $coupon_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE BINARY post_title = %s AND post_type= %s", $this->code, 'shop_coupon' ) );
			if ( $coupon_id ) $coupon = get_page($coupon_id); else return false;
            
            // Check titles match
            if ($this->code!==$coupon->post_title) return false;
            if ($coupon && $coupon->post_status == 'publish') :
                $this->id = $coupon->ID;
                $this->type = get_post_meta($coupon->ID, 'discount_type', true);
                $this->amount = get_post_meta($coupon->ID, 'coupon_amount', true);
                $this->individual_use = get_post_meta($coupon->ID, 'individual_use', true);
                $this->product_ids = array_filter(array_map('trim', explode(',', get_post_meta($coupon->ID, 'product_ids', true))));
                $this->exclude_product_ids = array_filter(array_map('trim', explode(',', get_post_meta($coupon->ID, 'exclude_product_ids', true))));
                $this->usage_limit = get_post_meta($coupon->ID, 'usage_limit', true);
                $this->usage_count = (int) get_post_meta($coupon->ID, 'usage_count', true);
                $this->expiry_date = ($expires = get_post_meta($coupon->ID, 'expiry_date', true)) ? strtotime($expires) : '';
                $this->apply_before_tax = get_post_meta($coupon->ID, 'apply_before_tax', true);
                $this->free_shipping = get_post_meta($coupon->ID, 'free_shipping', true);
                $this->product_categories = array_filter(array_map('trim', (array) get_post_meta($coupon->ID, 'product_categories', true)));
           		$this->exclude_product_categories = array_filter(array_map('trim', (array) get_post_meta($coupon->ID, 'exclude_product_categories', true)));
                return true;
            endif;
        endif;

        return false;
	}
	
	/** Check if coupon needs applying before tax **/
	function apply_before_tax() {
		if ($this->apply_before_tax=='yes') return true; else return false;
	}
	
	function enable_free_shipping() {
		if ($this->free_shipping=='yes') return true; else return false;
	}
	
	/** Increase usage count */
	function inc_usage_count() {
		$this->usage_count++;
		update_post_meta($this->id, 'usage_count', $this->usage_count);
	}
	
	/** Check coupon is valid */
	function is_valid() {
		
		global $woocommerce;
				
		if ($this->id) :
		
			$valid = true;
			
			// Usage Limit
			if ($this->usage_limit>0) :
				if ($this->usage_count>=$this->usage_limit) :
					$valid = false;
				endif;
			endif;
			
			// Expired
			if ($this->expiry_date) :
				if (strtotime('NOW')>$this->expiry_date) :
					$valid = false;
				endif;
			endif;
			
			// Product ids - If a product included is found in the cart then its valid
			if (sizeof( $this->product_ids )>0) :
				$valid_for_cart = false;
				if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
					if (in_array($cart_item['product_id'], $this->product_ids) || in_array($cart_item['variation_id'], $this->product_ids)) :
						$valid_for_cart = true;
					endif;
				endforeach; endif;
				if (!$valid_for_cart) $valid = false;
			endif;
			
			// Category ids - If a product included is found in the cart then its valid
			if (sizeof( $this->product_categories )>0) :
				$valid_for_cart = false;
				if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
					
					$product_cats = wp_get_post_terms($cart_item['product_id'], 'product_cat', array("fields" => "ids"));
					
					if ( sizeof( array_intersect( $product_cats, $this->product_categories ) ) > 0 ) $valid_for_cart = true;
					
				endforeach; endif;
				if (!$valid_for_cart) $valid = false;
			endif;
			
			// Cart discounts cannot be added if non-eligble product is found in cart
			if ($this->type!='fixed_product' && $this->type!='percent_product') : 
				
				// Exclude Products
				if (sizeof( $this->exclude_product_ids )>0) :
					$valid_for_cart = true;
					if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
						if (in_array($cart_item['product_id'], $this->exclude_product_ids) || in_array($cart_item['variation_id'], $this->exclude_product_ids)) :
							$valid_for_cart = false;
						endif;
					endforeach; endif;
					if (!$valid_for_cart) $valid = false;
				endif;
				
				// Exclude Categories
				if (sizeof( $this->exclude_product_categories )>0) :
					$valid_for_cart = true;
					if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
					
						$product_cats = wp_get_post_terms($cart_item['product_id'], 'product_cat', array("fields" => "ids"));
					
						if ( sizeof( array_intersect( $product_cats, $this->exclude_product_categories ) ) > 0 ) $valid_for_cart = false;

					endforeach; endif;
					if (!$valid_for_cart) $valid = false;
				endif;
			
			endif;
			
			$valid = apply_filters('woocommerce_coupon_is_valid', $valid, $this);
			
			if ($valid) return true;
		
		endif;
		
		return false;
	}
}

/** Depreciated */
class woocommerce_coupon extends WC_Coupon {
	public function __construct( $code ) { 
		_deprecated_function( 'woocommerce_coupon', '1.4', 'WC_Coupon()' );
		parent::__construct( $code ); 
	} 
}