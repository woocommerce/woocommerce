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
	var $minimum_amount;
	var $customer_email;
	var $coupon_custom_fields;
	var $discount_type;
	var $coupon_amount;
	
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
            $this->minimum_amount = $coupon_data['minimum_amount'];
            $this->customer_email = $coupon_data['customer_email'];
            return true;
        else:
            $coupon_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE BINARY post_title = %s AND post_type= %s", $this->code, 'shop_coupon' ) );
			if ( $coupon_id ) $coupon = get_page($coupon_id); else return false;
            
            // Check titles match
            if ($this->code!==$coupon->post_title) return false;
            
            if ($coupon && $coupon->post_status == 'publish') :
                $this->id 					= $coupon->ID;
                $this->coupon_custom_fields = get_post_custom( $this->id );
                 
                $load_data = array(
                	'discount_type'					=> 'fixed_cart',
                	'coupon_amount'					=> 0,
                	'individual_use'				=> 'no',
                	'product_ids'					=> '',
                	'exclude_product_ids'			=> '',
                	'usage_limit'					=> '',
                	'usage_count'					=> '',
                	'expiry_date'					=> '',
                	'apply_before_tax'				=> 'yes',
                	'free_shipping'					=> 'no',
                	'product_categories'			=> array(),
                	'exclude_product_categories'	=> array(),
                	'minimum_amount'				=> '',
                	'customer_email'				=> array()
                );
                 
                foreach ($load_data as $key => $default) $this->$key = (isset($this->coupon_custom_fields[$key][0]) && $this->coupon_custom_fields[$key][0]!=='') ? $this->coupon_custom_fields[$key][0] : $default; 
                 
                // Alias
                $this->type 				= $this->discount_type;
                $this->amount 				= $this->coupon_amount;
                
                // Formatting
                $this->product_ids = array_filter(array_map('trim', explode(',', $this->product_ids)));
                $this->exclude_product_ids = array_filter(array_map('trim', explode(',', $this->exclude_product_ids)));
     			$this->expiry_date = ($this->expiry_date) ? strtotime($this->expiry_date) : '';
                $this->product_categories = array_filter(array_map('trim', (array) maybe_unserialize($this->product_categories)));
           		$this->exclude_product_categories = array_filter(array_map('trim', (array) maybe_unserialize($this->exclude_product_categories)));
   				$this->customer_email = array_filter(array_map('trim', array_map('strtolower', (array) maybe_unserialize($this->customer_email))));

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
		update_post_meta( $this->id, 'usage_count', $this->usage_count );
	}
	
	/** Decrease usage count */
	function dcr_usage_count() {
		$this->usage_count--;
		update_post_meta( $this->id, 'usage_count', $this->usage_count );
	}
		
	/**
	 * is_valid function.
	 *
	 * Check if a coupon is valid. Return a reason code if invaid. Reason codes:
	 * 
	 * @access public
	 * @return void
	 */
	function is_valid() {
		
		global $woocommerce;
				
		if ($this->id) :
		
			$valid = true;
			$error = false;
			
			// Usage Limit
			if ($this->usage_limit>0) :
				if ($this->usage_count>=$this->usage_limit) :
					$valid = false;
					$error = __( 'Coupon usage limit has been reached.', 'woocommerce' );
				endif;
			endif;
			
			// Expired
			if ($this->expiry_date) :
				if (strtotime('NOW')>$this->expiry_date) :
					$valid = false;
					$error = __( 'This coupon has expired.', 'woocommerce' );
				endif;
			endif;
			
			// Minimum spend
			if ($this->minimum_amount>0) :
				if ( $this->minimum_amount > $woocommerce->cart->subtotal ) :
					$valid = false;
					$error = sprintf( __( 'The minimum spend for this coupon is %s.', 'woocommerce' ), $this->minimum_amount );
				endif;
			endif;
			
			// Product ids - If a product included is found in the cart then its valid
			if (sizeof( $this->product_ids )>0) :
				$valid_for_cart = false;
				if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
					if (in_array($cart_item['product_id'], $this->product_ids) || in_array($cart_item['variation_id'], $this->product_ids) || in_array($cart_item['data']->get_parent(), $this->product_ids)) :
						$valid_for_cart = true;
					endif;
				endforeach; endif;
				if ( ! $valid_for_cart ) $valid = false;
			endif;
			
			// Category ids - If a product included is found in the cart then its valid
			if (sizeof( $this->product_categories )>0) :
				$valid_for_cart = false;
				if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
					
					$product_cats = wp_get_post_terms($cart_item['product_id'], 'product_cat', array("fields" => "ids"));
					
					if ( sizeof( array_intersect( $product_cats, $this->product_categories ) ) > 0 ) $valid_for_cart = true;
					
				endforeach; endif;
				if ( ! $valid_for_cart ) $valid = false;
			endif;
			
			// Cart discounts cannot be added if non-eligble product is found in cart
			if ($this->type!='fixed_product' && $this->type!='percent_product') : 
				
				// Exclude Products
				if (sizeof( $this->exclude_product_ids )>0) :
					$valid_for_cart = true;
					if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
						if (in_array($cart_item['product_id'], $this->exclude_product_ids) || in_array($cart_item['variation_id'], $this->exclude_product_ids) || in_array($cart_item['data']->get_parent(), $this->exclude_product_ids)) :
							$valid_for_cart = false;
						endif;
					endforeach; endif;
					if ( ! $valid_for_cart ) $valid = false;
				endif;
				
				// Exclude Categories
				if (sizeof( $this->exclude_product_categories )>0) :
					$valid_for_cart = true;
					if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) :
					
						$product_cats = wp_get_post_terms($cart_item['product_id'], 'product_cat', array("fields" => "ids"));
					
						if ( sizeof( array_intersect( $product_cats, $this->exclude_product_categories ) ) > 0 ) $valid_for_cart = false;

					endforeach; endif;
					if ( ! $valid_for_cart ) $valid = false;
				endif;
			
			endif;
			
			$valid = apply_filters( 'woocommerce_coupon_is_valid', $valid, $this );
			
			if ( $valid ) 
				return true;
		
		endif;
		
		return new WP_Error( 'coupon_error', apply_filters( 'woocommerce_coupon_error', $error, $this ) );
	}
}

/** Depreciated */
class woocommerce_coupon extends WC_Coupon {
	public function __construct( $code ) { 
		_deprecated_function( 'woocommerce_coupon', '1.4', 'WC_Coupon()' );
		parent::__construct( $code ); 
	} 
}