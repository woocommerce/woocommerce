<?php
/**
 * WooCommerce cart
 * 
 * The WooCommerce cart class stores cart data and active coupons as well as handling customer sessions and some cart related urls.
 * The cart class also has a price calculation function which calls upon other classes to calcualte totals.
 *
 * @class 		woocommerce_cart
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_cart {
	
	/* Public Variables */
	var $cart_contents;
	var $applied_coupons;
	
	var $cart_contents_total;
	var $cart_contents_total_ex_tax;
	var $cart_contents_weight;
	var $cart_contents_count;
	var $cart_contents_tax;
	
	var $total;
	var $subtotal;
	var $subtotal_ex_tax;
	var $tax_total;
	var $discount_total;
	var $shipping_total;
	var $shipping_tax_total;
	
	/**
	 * Constructor
	 */
	function __construct() {
		add_action('init', array(&$this, 'init'), 1);				// Get cart on init
		add_action('wp', array(&$this, 'calculate_totals'), 1);		// Defer calculate totals so we can detect page
	}
    
    /**
	 * Loads the cart data from the session during WordPress init
	 */
    function init() {
  		$this->applied_coupons = array();
		$this->get_cart_from_session();
		if ( isset($_SESSION['coupons']) ) $this->applied_coupons = $_SESSION['coupons'];
		
		add_action('woocommerce_check_cart_items', array(&$this, 'check_cart_items'), 1);
    }
	
	/**
	 * Get the cart data from the PHP session
	 */
	function get_cart_from_session() {

		if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) :
			$cart = $_SESSION['cart'];
			
			foreach ($cart as $key => $values) :
			
				if ($values['variation_id'] > 0) :
					$_product = &new woocommerce_product_variation($values['variation_id']);
				else :
					$_product = &new woocommerce_product($values['product_id']);
				endif;
				
				if ($_product->exists && $values['quantity']>0) :
					
					// Put session data into array. Run through filter so other plugins can load their own session data
					$this->cart_contents[$key] = apply_filters('woocommerce_get_cart_item_from_session', array(
						'product_id'	=> $values['product_id'],
						'variation_id'	=> $values['variation_id'],
						'variation' 	=> $values['variation'],
						'quantity' 		=> $values['quantity'],
						'data'			=> $_product
					), $values);

				endif;
			endforeach;
			
		else :
			$this->cart_contents = array();
		endif;
		
		if (!is_array($this->cart_contents)) $this->cart_contents = array();
	}
	
	/**
	 * Sets the php session data for the cart and coupons
	 */
	function set_session() {
		$cart = array();
		
		// Set cart and coupon session data
		$_SESSION['cart'] = $this->cart_contents;
		$_SESSION['coupons'] = $this->applied_coupons;
		
		// Cart contents change so reset shipping
		unset($_SESSION['_chosen_shipping_method']);
		
		// Calculate totals
		$this->calculate_totals();
	}
	
	/**
	 * Empty the cart data and destroy the session
	 */
	function empty_cart() {
		$this->cart_contents = array();
		$this->total = 0;
		$this->cart_contents_total = 0;
		$this->cart_contents_total_ex_tax = 0;
		$this->cart_contents_weight = 0;
		$this->cart_contents_count = 0;
		$this->cart_contents_tax = 0;
		$this->tax_total = 0;
		$this->shipping_tax_total = 0;
		$this->subtotal = 0;
		$this->subtotal_ex_tax = 0;
		$this->discount_total = 0;
		$this->shipping_total = 0;
		unset($_SESSION['cart']);
		unset($_SESSION['coupons']);
	}
	
	/**
     * Check if product is in the cart and return cart item key
     * 
     * Cart item key will be unique based on the item and its properties, such as variations
     */
    function find_product_in_cart( $cart_id = false ) {
        if ($cart_id !== false) foreach ($this->cart_contents as $cart_item_key => $cart_item) if ($cart_item_key == $cart_id) return $cart_item_key;
    }
	
	/**
     * Generate a unique ID for the cart item being added
     */
    function generate_cart_id( $product_id, $variation_id = '', $variation = '', $cart_item_data = '' ) {
        
        $id_parts = array( $product_id );
        
        if ($variation_id) $id_parts[] = $variation_id;
 
        if (is_array($variation)) :
            $variation_key = '';
            foreach ($variation as $key => $value) :
                $variation_key .= trim($key) . trim($value);
            endforeach;
            $id_parts[] = $variation_key;
        endif;
        
        if (is_array($cart_item_data)) :
            $cart_item_data_key = '';
            foreach ($cart_item_data as $key => $value) :
            	if (is_array($value)) $value = http_build_query($value);
                $cart_item_data_key .= trim($key) . trim($value);
            endforeach;
            $id_parts[] = $cart_item_data_key;
        endif;

        return md5( implode('_', $id_parts) );
    }	
	
	/**
	 * Add a product to the cart
	 *
	 * @param   string	product_id	contains the id of the product to add to the cart
	 * @param   string	quantity	contains the quantity of the item to add
	 * @param   int     variation_id
	 * @param   array   variation attribute values
	 */
	function add_to_cart( $product_id, $quantity = 1, $variation_id = '', $variation = '' ) {
		global $woocommerce;
		
		if ($quantity < 1) return false;
		
		// Load cart item data - may be added by other plugins
		$cart_item_data = (array) apply_filters('woocommerce_add_cart_item_data', array(), $product_id);
		
		// Generate a ID based on product ID, variation ID, variation data, and other cart item data
		$cart_id = $this->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );
		
		// See if this product and its options is already in the cart
		$cart_item_key = $this->find_product_in_cart($cart_id);
		
		if ($variation_id>0) :
			$product_data = &new woocommerce_product_variation( $variation_id );
		else :
			$product_data = &new woocommerce_product( $product_id );
		endif;
		
		// Type check
		if ( $product_data->is_type('external') ) :
			$woocommerce->add_error( __('This product cannot be purchased.', 'woothemes') );
			return false; 
		endif;
		
		// Price set check
		if( $product_data->get_price() === '' ) :
			$woocommerce->add_error( __('This product cannot be purchased - the price is not yet set.', 'woothemes') );
			return false; 
		endif;

		// Stock check - only check if we're managing stock and backorders are not allowed
		if ( !$product_data->has_enough_stock( $quantity ) ) :
			$woocommerce->add_error( sprintf(__('You cannot add that amount to the cart since there is not enough stock. We have %s in stock.', 'woothemes'), $product_data->get_stock_quantity() ));
			return false; 
		elseif ( !$product_data->is_in_stock() ) :
			$woocommerce->add_error( __('You cannot add that product to the cart since the product is out of stock.', 'woothemes') );
			return false;
		endif;
		
		if ($cart_item_key) :

			$quantity = $quantity + $this->cart_contents[$cart_item_key]['quantity'];
			
			// Stock check - this time accounting for whats already in-cart
			if ( !$product_data->has_enough_stock( $quantity ) ) :
				$woocommerce->add_error( sprintf(__('You cannot add that amount to the cart since there is not enough stock. We have %s in stock and you already have %s in your cart.', 'woothemes'), $product_data->get_stock_quantity(), $this->cart_contents[$cart_item_key]['quantity'] ));
				return false; 
			elseif ( !$product_data->is_in_stock() ) :
				$woocommerce->add_error( __('You cannot add that product to the cart since the product is out of stock.', 'woothemes') );
				return false;
			endif;

			$this->cart_contents[$cart_item_key]['quantity'] = $quantity;

		else :
			
			// Add item after merging with $cart_item_data - hook to allow plugins to modify cart item
			$this->cart_contents[$cart_id] = apply_filters('woocommerce_add_cart_item', array_merge( $cart_item_data, array(
				'product_id'	=> $product_id,
				'variation_id'	=> $variation_id,
				'variation' 	=> $variation,
				'quantity' 		=> $quantity,
				'data'			=> $product_data
			)));
		
		endif;

		$this->set_session();
		
		return true;
	}

	/**
	 * Applies a coupon code
	 *
	 * @param   string	code	The code to apply
	 * @return   bool	True if the coupon is applied, false if it does not exist or cannot be applied
	 */
	function add_discount( $coupon_code ) {
		global $woocommerce;
		
		$the_coupon = &new woocommerce_coupon($coupon_code);
		
		if ($the_coupon->id) :
			
			// Check if applied
			if ($woocommerce->cart->has_discount($coupon_code)) :
				$woocommerce->add_error( __('Discount code already applied!', 'woothemes') );
				return false;
			endif;	
			
			// Check it can be used with cart
			if (!$the_coupon->is_valid()) :
				$woocommerce->add_error( __('Invalid coupon.', 'woothemes') );
				return false;
			endif;
			
			// If its individual use then remove other coupons
			if ($the_coupon->individual_use=='yes') :
				$this->applied_coupons = array();
			endif;
			
			foreach ($this->applied_coupons as $code) :
				$coupon = &new woocommerce_coupon($code);
				if ($coupon->individual_use=='yes') :
					$this->applied_coupons = array();
				endif;
			endforeach;
			
			$this->applied_coupons[] = $coupon_code;
			$this->set_session();
			$woocommerce->add_message( __('Discount code applied successfully.', 'woothemes') );
			return true;
		
		else :
			$woocommerce->add_error( __('Coupon does not exist!', 'woothemes') );
			return false;
		endif;
		return false;
	}
	
	/**
	 * Set the quantity for an item in the cart
	 *
	 * @param   string	cart_item_key	contains the id of the cart item
	 * @param   string	quantity	contains the quantity of the item
	 */
	function set_quantity( $cart_item_key, $quantity = 1 ) {
		if ($quantity==0 || $quantity<0) :
			unset($this->cart_contents[$cart_item_key]);
		else :
			$this->cart_contents[$cart_item_key]['quantity'] = $quantity;
		endif;

		$this->set_session();
	}
	
	/** 
	 * calculate totals for the items in the cart 
	 */
	function calculate_totals() {
		global $woocommerce;
		
		$_tax = &new woocommerce_tax();

		$this->total = 0;
		$this->cart_contents_total = 0;
		$this->cart_contents_total_ex_tax = 0;
		$this->cart_contents_weight = 0;
		$this->cart_contents_count = 0;
		$this->cart_contents_tax = 0;
		$this->tax_total = 0;
		$this->shipping_tax_total = 0;
		$this->subtotal = 0;
		$this->subtotal_ex_tax = 0;
		$this->discount_total = 0;
		$this->shipping_total = 0;
		
		if (sizeof($this->cart_contents)>0) : foreach ($this->cart_contents as $cart_item_key => $values) :
			
			// Get product from cart data
			$_product = $values['data'];
			
			if ($_product->exists() && $values['quantity']>0) :
				
				$this->cart_contents_count = $this->cart_contents_count + $values['quantity'];
				
				$this->cart_contents_weight = $this->cart_contents_weight + ($_product->get_weight() * $values['quantity']);

				$total_item_price = $_product->get_price() * $values['quantity'];
				
				if ( get_option('woocommerce_calc_taxes')=='yes') :
					
					if ( $_product->is_taxable() ) :
					
						$rate = $_tax->get_rate( $_product->get_tax_class() );
						
						if (get_option('woocommerce_prices_include_tax')=='yes') :
							// Price incldues tax
							$tax_amount = $_tax->calc_tax( $_product->get_price(), $rate, true ) * $values['quantity'];
						else :
							// Price excludes tax
							$tax_amount = $_tax->calc_tax( $_product->get_price(), $rate, false ) * $values['quantity'];
						endif;
						
						/**
						 * Checkout calculations when customer is OUTSIDE the shop base country and price INCLUDE tax
						 */
						if (get_option('woocommerce_prices_include_tax')=='yes' && $woocommerce->customer->is_customer_outside_base() && defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT ) :
							// Get the base rate first
							$base_rate = $_tax->get_shop_base_rate( $_product->tax_class );
							
							// Calc tax for base country
							$base_tax_amount = $_tax->calc_tax( $_product->get_price(), $base_rate, true);
							
							// Now calc tax for user county (which now excludes tax)
							$tax_amount = $_tax->calc_tax( ( $_product->get_price() - $base_tax_amount ), $rate, false );
							$tax_amount = $tax_amount * $values['quantity'];
							
							// Finally, update $total_item_price to reflect tax amounts
							$total_item_price = ($total_item_price - ($base_tax_amount * $values['quantity']) + $tax_amount);
							
						/**
						 * Checkout calculations when customer is INSIDE the shop base country and price INCLUDE tax
						 */
						elseif (get_option('woocommerce_prices_include_tax')=='yes' && $_product->get_tax_class() !== $_product->tax_class) :
							
							// Calc tax for original rate
							$original_tax_amount = $_tax->calc_tax( $_product->get_price(), $_tax->get_rate( $_product->tax_class ), true);
							
							// Now calc tax for new rate (which now excludes tax)
							$tax_amount = $_tax->calc_tax( ( $_product->get_price() - $original_tax_amount ), $rate, false );
							$tax_amount = $tax_amount * $values['quantity'];
							
							$total_item_price = ($total_item_price - ($original_tax_amount * $values['quantity']) + $tax_amount);
							
						endif;

					endif;
					
				endif;

				$tax_amount = ( isset($tax_amount) ? $tax_amount : 0 );
				
				$this->cart_contents_tax = $this->cart_contents_tax + $tax_amount;			
				$this->cart_contents_total = $this->cart_contents_total + $total_item_price;
				$this->cart_contents_total_ex_tax = $this->cart_contents_total_ex_tax + ($_product->get_price_excluding_tax()*$values['quantity']);
				
				// Product Discounts
				if ($this->applied_coupons) foreach ($this->applied_coupons as $code) :
					$coupon = &new woocommerce_coupon( $code );
					
					$this_item_is_discounted = false;
					
					// Specific product ID's get the discount
					if (sizeof($coupon->product_ids)>0) :
						
						if ((in_array($values['product_id'], $coupon->product_ids) || in_array($values['variation_id'], $coupon->product_ids))) :
							$this_item_is_discounted = true;
						endif;
					
					else :
						
						// No product ids - all items discounted
						$this_item_is_discounted = true;
					
					endif;
					
					// Specific product ID's excluded from the discount
					if (sizeof($coupon->exclude_product_ids)>0) :
						
						if ((in_array($values['product_id'], $coupon->exclude_product_ids) || in_array($values['variation_id'], $coupon->exclude_product_ids))) :
							$this_item_is_discounted = false;
						endif;
						
					endif;
					
					// Apply filter
					$this_item_is_discounted = apply_filters( 'woocommerce_item_is_discounted', $this_item_is_discounted, $values );
					
					// Apply the discount
					if ($this_item_is_discounted) :
						if ($coupon->type=='fixed_product') :
							$this->discount_total = $this->discount_total + ( $coupon->amount * $values['quantity'] );
						elseif ($coupon->type=='percent_product') :
							$this->discount_total = $this->discount_total + ( $total_item_price / 100 ) * $coupon->amount;
						endif;
					endif;
					
				endforeach;
				
			endif;
		endforeach; endif;
		
		// Calculate final totals
		$this->tax_total 			= $this->cart_contents_tax;					// Tax Total
		$this->subtotal_ex_tax 		= $this->cart_contents_total_ex_tax;		// Subtotal without tax
		$this->subtotal 			= $this->cart_contents_total;				// Subtotal
		
		// Only go beyond this point if on the cart/checkout
		if (!is_checkout() && !is_cart() && !defined('WOOCOMMERCE_CHECKOUT') && !is_ajax()) return;
		
		// Cart Discounts
		if ($this->applied_coupons) foreach ($this->applied_coupons as $code) :
			$coupon = &new woocommerce_coupon( $code );
			if ($coupon->is_valid()) :
				if ($coupon->type=='fixed_cart') : 
				
					$this->discount_total = $this->discount_total + $coupon->amount;
					
				elseif ($coupon->type=='percent') :

					if (get_option('woocommerce_prices_include_tax')=='yes') :
						$this->discount_total = $this->discount_total + ( $this->subtotal / 100 ) * $coupon->amount;
					else :
						$this->discount_total = $this->discount_total + ( ($this->subtotal + $this->cart_contents_tax) / 100 ) * $coupon->amount;
					endif;
					
				endif;
			endif;
		endforeach;
		
		// Cart Shipping
		if ($this->needs_shipping()) $woocommerce->shipping->calculate_shipping(); else $woocommerce->shipping->reset_shipping();
		
		$this->shipping_total 		= $woocommerce->shipping->shipping_total;	// Shipping Total
		$this->shipping_tax_total 	= $woocommerce->shipping->shipping_tax;		// Shipping Tax
		
		// VAT excemption done at this point - so all totals are correct before exemption
		if ($woocommerce->customer->is_vat_exempt()) :
			$this->shipping_tax_total = 0;
			$this->tax_total = 0;
		endif;
		
		// Allow plugins to hook and alter totals before final total is calculated
		do_action('woocommerce_calculate_totals', $this);
				
		// Grand Total
		if (get_option('woocommerce_prices_include_tax')=='yes') :
			$this->total = $this->subtotal + $this->shipping_tax_total - $this->discount_total + $woocommerce->shipping->shipping_total;
		else :
			$this->total = $this->subtotal + $this->tax_total + $this->shipping_tax_total - $this->discount_total + $woocommerce->shipping->shipping_total;
		endif;
		
		if ($this->total < 0) $this->total = 0;
	}
	
	/** 
	 *returns whether or not a discount has been applied 
	 */
	function has_discount( $code ) {
		if (in_array($code, $this->applied_coupons)) return true;
		return false;
	}
	
	/** 
	 * looks through the cart to see if shipping is actually required 
	 */
	function needs_shipping() {
		global $woocommerce;
		
		if (!$woocommerce->shipping->enabled) return false;
		if (!is_array($this->cart_contents)) return false;
	
		$needs_shipping = false;
		
		foreach ($this->cart_contents as $cart_item_key => $values) :
			$_product = $values['data'];
			if ( $_product->needs_shipping() ) :
				$needs_shipping = true;
			endif;
		endforeach;
		
		return $needs_shipping;
	}
	
	/** 
	 * Sees if we need a shipping address 
	 */
	function ship_to_billing_address_only() {
		if (get_option('woocommerce_ship_to_billing_address_only')=='yes') return true; else return false;
	}
	
	/** 
	 * looks at the totals to see if payment is actually required 
	 */
	function needs_payment() {
		if ( $this->total > 0 ) return true; else return false;
	}


	/**
	 * Check cart items for errors
	 */
	function check_cart_items() {
	
		$result = $this->check_cart_item_stock();
		if (is_wp_error($result)) $woocommerce->add_error( $result->get_error_message() );
	
	}
	
	/** 
	 * looks through the cart to check each item is in stock 
	 */
	function check_cart_item_stock() {
		$error = new WP_Error();
		foreach ($this->cart_contents as $cart_item_key => $values) :
			$_product = $values['data'];
			if ($_product->managing_stock()) :
				if ($_product->is_in_stock() && $_product->has_enough_stock( $values['quantity'] )) :
					// :)
				else :
					$error->add( 'out-of-stock', sprintf(__('Sorry, we do not have enough "%s" in stock to fulfill your order (%s in stock). Please edit your cart and try again. We apologise for any inconvenience caused.', 'woothemes'), $_product->get_title(), $_product->stock ) );
					return $error;
				endif;
			else :
				if (!$_product->is_in_stock()) :
					$error->add( 'out-of-stock', sprintf(__('Sorry, we do not have enough "%s" in stock to fulfill your order. Please edit your cart and try again. We apologise for any inconvenience caused.', 'woothemes'), $_product->get_title() ) );
					return $error;
				endif;
			endif;
		endforeach;
		return true;
	}
	
	/**
	 * Gets and formats a list of cart item data + variations for display on the frontend
	 */
	function get_item_data( $cart_item, $flat = false ) {
		global $woocommerce;
		
		if (!$flat) $return = '<dl class="variation">';
		
		// Variation data
		if($cart_item['data'] instanceof woocommerce_product_variation && is_array($cart_item['variation'])) :
		
			$variation_list = array();
			
			foreach ($cart_item['variation'] as $name => $value) :
				
				if (!$value) continue;
				
				// If this is a term slug, get the term's nice name
	            if (taxonomy_exists(esc_attr(str_replace('attribute_', '', $name)))) :
	            	$term = get_term_by('slug', $value, esc_attr(str_replace('attribute_', '', $name)));
	            	if (!is_wp_error($term) && $term->name) :
	            		$value = $term->name;
	            	endif;
	            else :
	            	$value = ucfirst($value);
	            endif;
				
				if ($flat) :
					$variation_list[] = $woocommerce->attribute_label(str_replace('attribute_', '', $name)).': '.$value;
				else :
					$variation_list[] = '<dt>'.$woocommerce->attribute_label(str_replace('attribute_', '', $name)).':</dt><dd>'.$value.'</dd>';
				endif;
				
			endforeach;
			
			if ($flat) :
				$return .= implode(', ', $variation_list);
			else :
				$return .= implode('', $variation_list);
			endif;
		
		endif;
		
		// Other data - returned as array with name/value values
		$other_data = apply_filters('woocommerce_get_item_data', array(), $cart_item);
		
		if ($other_data && is_array($other_data) && sizeof($other_data)>0) :
			
			$data_list = array();
			
			foreach ($other_data as $data) :
				
				if ($flat) :
					$data_list[] = $data['name'].': '.$data['value'];
				else :
					$data_list[] = '<dt>'.$data['name'].':</dt><dd>'.$data['value'].'</dd>';
				endif;
				
			endforeach;
			
			if ($flat) :
				$return .= implode(', ', $data_list);
			else :
				$return .= implode('', $data_list);
			endif;
			
		endif;
		
		if (!$flat) $return .= '</dl>';
		
		return $return;
   				
	}

	/**
	 * Gets cross sells based on the items in the cart
	 *
	 * @return   array	cross_sells	item ids of cross sells
	 */
	function get_cross_sells() {
		$cross_sells = array();
		$in_cart = array();
		if (sizeof($this->cart_contents)>0) : foreach ($this->cart_contents as $cart_item_key => $values) :
			if ($values['quantity']>0) :
				$cross_sells = array_merge($values['data']->get_cross_sells(), $cross_sells);
				$in_cart[] = $values['product_id'];
			endif;
		endforeach; endif;
		$cross_sells = array_diff($cross_sells, $in_cart);
		return $cross_sells;
	}
	
	/** gets the url to the cart page */
	function get_cart_url() {
		$cart_page_id = get_option('woocommerce_cart_page_id');
		if ($cart_page_id) return get_permalink($cart_page_id);
	}
	
	/** gets the url to the checkout page */
	function get_checkout_url() {
		$checkout_page_id = get_option('woocommerce_checkout_page_id');
		if ($checkout_page_id) :
			if (is_ssl()) return str_replace('http:', 'https:', get_permalink($checkout_page_id));
			return get_permalink($checkout_page_id);
		endif;
	}
	
	/** gets the url to remove an item from the cart */
	function get_remove_url( $cart_item_key ) {
		global $woocommerce;
		$cart_page_id = get_option('woocommerce_cart_page_id');
		if ($cart_page_id) return $woocommerce->nonce_url( 'cart', add_query_arg('remove_item', $cart_item_key, get_permalink($cart_page_id)));
	}
	
	/**
	 * Returns the contents of the cart
	 */
	function get_cart() {
		return $this->cart_contents;
	}	
	
	/**
	 * gets the total (after calculation)
	 */
	function get_total() {
		return woocommerce_price($this->total);
	}
	
	/**
	 * gets the cart contens total (after calculation)
	 */
	function get_cart_total() {
		return woocommerce_price($this->cart_contents_total);
	}
	
	/**
	 * gets the sub total (after calculation)
	 */
	function get_cart_subtotal() {
		global $woocommerce;
		
		if (get_option('woocommerce_display_totals_tax')=='excluding' || ( defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT )) :
			
			if (get_option('woocommerce_prices_include_tax')=='yes') :
				$return = woocommerce_price($this->subtotal - $this->tax_total);
			else :
				$return = woocommerce_price($this->subtotal);
			endif;
			
			if ($this->tax_total>0) :
				$return .= ' <small>'.$woocommerce->countries->ex_tax_or_vat().'</small>';
			endif;
			return $return;
			
		else :
			
			if (get_option('woocommerce_prices_include_tax')=='yes') :
				$return = woocommerce_price($this->subtotal);
			else :
				$return = woocommerce_price($this->subtotal + $this->tax_total);
			endif;
			
			if ($this->tax_total>0) :
				$return .= ' <small>'.$woocommerce->countries->inc_tax_or_vat().'</small>';
			endif;
			return $return;
		
		endif;

	}
	
	/**
	 * gets the cart tax (after calculation)
	 */
	function get_cart_tax() {
		$cart_total_tax = $this->tax_total + $this->shipping_tax_total;
		if ($cart_total_tax > 0) return woocommerce_price( $cart_total_tax );
		return false;
	}
	
	/**
	 * gets the shipping total (after calculation)
	 */
	function get_cart_shipping_total() {
		global $woocommerce;
		
		if (isset($woocommerce->shipping->shipping_label)) :
			if ($woocommerce->shipping->shipping_total>0) :
			
				if (get_option('woocommerce_display_totals_tax')=='excluding') :
					
					$return = woocommerce_price($woocommerce->shipping->shipping_total);
					if ($this->shipping_tax_total>0) :
						$return .= ' <small>'.$woocommerce->countries->ex_tax_or_vat().'</small>';
					endif;
					return $return;
					
				else :
					
					$return = woocommerce_price($woocommerce->shipping->shipping_total + $woocommerce->shipping->shipping_tax);
					if ($this->shipping_tax_total>0) :
						$return .= ' <small>'.$woocommerce->countries->inc_tax_or_vat().'</small>';
					endif;
					return $return;
				
				endif;

			else :
				return __('Free!', 'woothemes');
			endif;
		endif;
	}
	
	/**
	 * gets title of the chosen shipping method
	 */
	function get_cart_shipping_title() {
		global $woocommerce;
		if (isset($woocommerce->shipping->shipping_label)) :
			return __('via', 'woothemes') . ' ' . $woocommerce->shipping->shipping_label;
		endif;
		return false;
	}	
	
	/**
	 * gets the total discount amount
	 */
	function get_total_discount() {
		if ($this->discount_total) return woocommerce_price($this->discount_total); else return false;
	}
	
	/**
	 * gets the array of applied coupon codes
	 */
	function get_applied_coupons() {
		return (array) $this->applied_coupons;
	}
	
}