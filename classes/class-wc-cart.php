<?php
/**
 * WooCommerce cart
 * 
 * The WooCommerce cart class stores cart data and active coupons as well as handling customer sessions and some cart related urls.
 * The cart class also has a price calculation function which calls upon other classes to calculate totals.
 *
 * @class 		WC_Cart
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class WC_Cart {
	
	/* Public Variables */
	var $cart_contents;
	var $applied_coupons;
	
	var $cart_contents_total;
	var $cart_contents_weight;
	var $cart_contents_count;
	var $cart_contents_tax;
	
	var $total;
	var $subtotal;
	var $subtotal_ex_tax;
	var $tax_total;
	var $taxes;
	var $shipping_taxes;
	var $discount_cart;
	var $discount_total;
	var $shipping_total;
	var $shipping_tax_total;
	var $shipping_label;
	
	/* Private variables */
	var $tax;
	
	/**
	 * Constructor
	 */
	function __construct() {
		$this->tax = new WC_Tax();
		$this->prices_include_tax = (get_option('woocommerce_prices_include_tax')=='yes') ? true : false;
		$this->display_totals_ex_tax = (get_option('woocommerce_display_totals_excluding_tax')=='yes') ? true : false;
		$this->display_cart_ex_tax = (get_option('woocommerce_display_cart_prices_excluding_tax')=='yes') ? true : false;
		
		add_action('init', array(&$this, 'init'), 5);						// Get cart on init
	}
    
    /**
	 * Loads the cart data from the session during WordPress init
	 */
    function init() {
		$this->get_cart_from_session();
		
		add_action('woocommerce_check_cart_items', array(&$this, 'check_cart_items'), 1);
		add_action('woocommerce_after_checkout_validation', array(&$this, 'check_customer_coupons'), 1);
    }

 	/*-----------------------------------------------------------------------------------*/
	/* Cart Session Handling */
	/*-----------------------------------------------------------------------------------*/ 
	
		/**
		 * Get the cart data from the PHP session
		 */
		function get_cart_from_session() {
			global $woocommerce;
			
			// Load the coupons
			if ( get_option( 'woocommerce_enable_coupons' ) == 'yes' ) {
				$this->applied_coupons = (isset($_SESSION['coupons'])) ? array_unique(array_filter((array) $_SESSION['coupons'])) : array();
			}
			
			// Load the cart
			if ( isset($_SESSION['cart']) && is_array($_SESSION['cart']) ) :
				$cart = $_SESSION['cart'];
				
				foreach ($cart as $key => $values) :
				
					if ($values['variation_id'] > 0) :
						$_product = new WC_Product_Variation($values['variation_id']);
					else :
						$_product = new WC_Product($values['product_id']);
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
				
				if (!is_array($this->cart_contents)) :
					$this->cart_contents = array();
				endif;
				
			else :
				$this->cart_contents = array();
			endif;
			
			// Cookie
			if (sizeof($this->cart_contents)>0) 
				$woocommerce->cart_has_contents_cookie( true );
			else 
				$woocommerce->cart_has_contents_cookie( false );
			
			// Load totals
			$this->cart_contents_total 	= isset($_SESSION['cart_contents_total']) ? $_SESSION['cart_contents_total'] : 0;
			$this->cart_contents_weight = isset($_SESSION['cart_contents_weight']) ? $_SESSION['cart_contents_weight'] : 0;
			$this->cart_contents_count 	= isset($_SESSION['cart_contents_count']) ? $_SESSION['cart_contents_count'] : 0;
			$this->cart_contents_tax 	= isset($_SESSION['cart_contents_tax']) ? $_SESSION['cart_contents_tax'] : 0;
			$this->total 				= isset($_SESSION['total']) ? $_SESSION['total'] : 0;
			$this->subtotal 			= isset($_SESSION['subtotal']) ? $_SESSION['subtotal'] : 0;
			$this->subtotal_ex_tax 		= isset($_SESSION['subtotal_ex_tax']) ? $_SESSION['subtotal_ex_tax'] : 0;
			$this->tax_total 			= isset($_SESSION['tax_total']) ? $_SESSION['tax_total'] : 0;
			$this->taxes 				= isset($_SESSION['taxes']) ? $_SESSION['taxes'] : array();
			$this->shipping_taxes		= isset($_SESSION['shipping_taxes']) ? $_SESSION['shipping_taxes'] : array();
			$this->discount_cart 		= isset($_SESSION['discount_cart']) ? $_SESSION['discount_cart'] : 0;
			$this->discount_total 		= isset($_SESSION['discount_total']) ? $_SESSION['discount_total'] : 0;
			$this->shipping_total 		= isset($_SESSION['shipping_total']) ? $_SESSION['shipping_total'] : 0;
			$this->shipping_tax_total 	= isset($_SESSION['shipping_tax_total']) ? $_SESSION['shipping_tax_total'] : 0;
			$this->shipping_label		= isset($_SESSION['shipping_label']) ? $_SESSION['shipping_label'] : '';
			
			// Queue re-calc if subtotal is not set
			if (!$this->subtotal && sizeof($this->cart_contents)>0) $this->set_session();
		}
		
		/**
		 * Sets the php session data for the cart and coupons and re-calculates totals
		 */
		function set_session() {
			
			// Re-calc totals
			$this->calculate_totals();
			
			// Set cart and coupon session data
			$cart_session = array();
			
			if ($this->cart_contents) foreach ($this->cart_contents as $key => $values) {
				
				$cart_session[$key] = $values;
				
				// Unset product object
				unset($cart_session[$key]['data']);
			}
			
			$_SESSION['cart'] = $cart_session;
			$_SESSION['coupons'] = $this->applied_coupons;
			
			// Store totals to avoid re-calc on page load
			$_SESSION['cart_contents_total'] = $this->cart_contents_total;
			$_SESSION['cart_contents_weight'] = $this->cart_contents_weight;
			$_SESSION['cart_contents_count'] = $this->cart_contents_count;
			$_SESSION['cart_contents_tax'] = $this->cart_contents_tax;
			$_SESSION['total'] = $this->total;
			$_SESSION['subtotal'] = $this->subtotal;
			$_SESSION['subtotal_ex_tax'] = $this->subtotal_ex_tax;
			$_SESSION['tax_total'] = $this->tax_total;
			$_SESSION['shipping_taxes'] = $this->shipping_taxes;
			$_SESSION['taxes'] = $this->taxes;
			$_SESSION['discount_cart'] = $this->discount_cart;
			$_SESSION['discount_total'] = $this->discount_total;
			$_SESSION['shipping_total'] = $this->shipping_total;
			$_SESSION['shipping_tax_total'] = $this->shipping_tax_total;
			$_SESSION['shipping_label'] = $this->shipping_label;
			
			if (get_current_user_id()) $this->persistent_cart_update();
			
			do_action('woocommerce_cart_updated');
		}
		
		/**
		 * Empty the cart data and destroy the session
		 */
		function empty_cart( $clear_persistent_cart = true ) {
		
			$this->cart_contents = array();
			$this->reset();
			
			unset( $_SESSION['coupons'], $_SESSION['cart'] );
			
			if ($clear_persistent_cart && get_current_user_id()) $this->persistent_cart_destroy();
			
			do_action('woocommerce_cart_emptied');
		}

 	/*-----------------------------------------------------------------------------------*/
	/* Persistent cart handling */
	/*-----------------------------------------------------------------------------------*/ 
		
		/**
		 * Save the persistent cart when updated
		 */
		function persistent_cart_update() {
			update_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', array(
				'cart' => $_SESSION['cart'],
			));
		}
		
		/**
		 * Delete the persistent cart
		 */
		function persistent_cart_destroy() {
			delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart' );
		}
		
 	/*-----------------------------------------------------------------------------------*/
	/* Cart Data Functions */
	/*-----------------------------------------------------------------------------------*/ 

		/**
		 * Check cart items for errors
		 */
		function check_cart_items() {
			global $woocommerce;
			
			// Check item stock
			$result = $this->check_cart_item_stock();
			if (is_wp_error($result)) $woocommerce->add_error( $result->get_error_message() );
		}
		
		/**
		 * Check for user coupons (now we have billing email)
		 **/
		function check_customer_coupons( $posted ) {
			global $woocommerce;
			
			if (!empty($this->applied_coupons)) foreach ($this->applied_coupons as $key => $code) {
				$coupon = new WC_Coupon( $code );
				
				if (is_array($coupon->customer_email) && sizeof($coupon->customer_email)>0) {
					if (is_user_logged_in()) {
						$current_user = wp_get_current_user();
						$check_emails[] = $current_user->user_email;
					}
					$check_emails[] = $posted['billing_email'];
					
					if (!in_array($check_emails, $coupon->customer_email)) {
						$woocommerce->add_error( sprintf(__('Sorry, it seems the coupon "%s" is not yours - it has now been removed from your order.', 'woocommerce'), $code) );
						// Remove the coupon
						unset( $this->applied_coupons[$key] );
						$_SESSION['coupons'] = $this->applied_coupons;
						$_SESSION['refresh_totals'] = true;
					}
				}
				
			}
		}
		
		/** 
		 * looks through the cart to check each item is in stock 
		 */
		function check_cart_item_stock() {
			$error = new WP_Error();
			
			$product_qty_in_cart = array();
			
			// First stock check loop
			foreach ($this->get_cart() as $cart_item_key => $values) {
				
				$_product = $values['data'];
				
				/**
				 * Check stock based on inventory
				 */
				if ($_product->managing_stock()) {
					
					/**
					 * Check the stock for this item individually
					 */
					if (!$_product->is_in_stock() || !$_product->has_enough_stock( $values['quantity'] )) {
						$error->add( 'out-of-stock', sprintf(__('Sorry, we do not have enough "%s" in stock to fulfill your order (%s in stock). Please edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce'), $_product->get_title(), $_product->stock ) );
						return $error;
					}
					
					/**
					 * Put the item in the array to merge stock levels for items on multiple rows
					 */
					if ($values['variation_id']>0) {
						if ($_product->variation_has_stock) {
							// Variation has stock levels defined so its handled individually
							$product_qty_in_cart[$values['variation_id']] = (isset($product_qty_in_cart[$values['variation_id']])) ? $product_qty_in_cart[$values['variation_id']] + $values['quantity'] : $values['quantity'];
						} else {
							// Variation has no stock levels defined so use parents
							$product_qty_in_cart[$values['product_id']] = (isset($product_qty_in_cart[$values['product_id']])) ? $product_qty_in_cart[$values['product_id']] + $values['quantity'] : $values['quantity'];
						}
					} else {
						$product_qty_in_cart[$values['product_id']] = (isset($product_qty_in_cart[$values['product_id']])) ? $product_qty_in_cart[$values['product_id']] + $values['quantity'] : $values['quantity'];
					}
				
				/**
				 * Check stock based on stock-status
				 */
				} else {
					if (!$_product->is_in_stock()) {
						$error->add( 'out-of-stock', sprintf(__('Sorry, we do not have enough "%s" in stock to fulfill your order. Please edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce'), $_product->get_title() ) );
						return $error;
					}
				}
			}
			
			// This time check merged rows
			foreach ($this->get_cart() as $cart_item_key => $values) {
				
				$_product = $values['data'];

				if ($_product->managing_stock()) {
				
					if ($values['variation_id'] && $_product->variation_has_stock && isset($product_qty_in_cart[$values['variation_id']])) {
						
						if (!$_product->has_enough_stock( $product_qty_in_cart[$values['variation_id']] )) {
							$error->add( 'out-of-stock', sprintf(__('Sorry, we do not have enough "%s" in stock to fulfill your order (%s in stock). Please edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce'), $_product->get_title(), $_product->stock ) );
							return $error;
						}
					
					} elseif (isset($product_qty_in_cart[$values['product_id']])) {
						
						if (!$_product->has_enough_stock( $product_qty_in_cart[$values['product_id']] )) {
							$error->add( 'out-of-stock', sprintf(__('Sorry, we do not have enough "%s" in stock to fulfill your order (%s in stock). Please edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce'), $_product->get_title(), $_product->stock ) );
							return $error;
						}
						
					}
				
				}
				
			}

			return true;
		}
		
		/**
		 * Gets and formats a list of cart item data + variations for display on the frontend
		 */
		function get_item_data( $cart_item, $flat = false ) {
			global $woocommerce;
			
			$has_data = false;
			
			if (!$flat) $return = '<dl class="variation">';
			
			// Variation data
			if($cart_item['data'] instanceof WC_Product_Variation && is_array($cart_item['variation'])) :
			
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
				
				$has_data = true;
			
			endif;
			
			// Other data - returned as array with name/value values
			$other_data = apply_filters('woocommerce_get_item_data', array(), $cart_item);
			
			if ($other_data && is_array($other_data) && sizeof($other_data)>0) :
				
				$data_list = array();
				
				foreach ($other_data as $data) :
					
					$display_value = (isset($data['display']) && $data['display']) ? $data['display'] : $data['value'];
					
					if ($flat) :
						$data_list[] = $data['name'].': '.$display_value;
					else :
						$data_list[] = '<dt>'.$data['name'].':</dt><dd>'.$display_value.'</dd>';
					endif;
					
				endforeach;
				
				if ($flat) :
					$return .= implode(', ', $data_list);
				else :
					$return .= implode('', $data_list);
				endif;
				
				$has_data = true;
				
			endif;
			
			if (!$flat) $return .= '</dl>';
			
			if ($has_data) return $return;
	   				
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
			$cart_page_id = woocommerce_get_page_id('cart');
			if ($cart_page_id) return apply_filters('woocommerce_get_cart_url', get_permalink($cart_page_id));
		}
		
		/** gets the url to the checkout page */
		function get_checkout_url() {
			$checkout_page_id = woocommerce_get_page_id('checkout');
			if ($checkout_page_id) :
				if (is_ssl()) return str_replace('http:', 'https:', get_permalink($checkout_page_id));
				return apply_filters('woocommerce_get_checkout_url', get_permalink($checkout_page_id));
			endif;
		}
		
		/** gets the url to remove an item from the cart */
		function get_remove_url( $cart_item_key ) {
			global $woocommerce;
			$cart_page_id = woocommerce_get_page_id('cart');
			if ($cart_page_id) return apply_filters('woocommerce_get_remove_url', $woocommerce->nonce_url( 'cart', add_query_arg('remove_item', $cart_item_key, get_permalink($cart_page_id))));
		}
		
		/**
		 * Returns the contents of the cart
		 */
		function get_cart() {
			return (array) $this->cart_contents;
		}
		
		/**
		 * Returns the cart and shipping taxes, merged
		 */
		function get_taxes() {
			$merged_taxes = array();

			// Merge
			foreach (array_keys($this->taxes + $this->shipping_taxes) as $key) {
				$merged_taxes[$key] = (isset($this->shipping_taxes[$key]) ? $this->shipping_taxes[$key] : 0) + (isset($this->taxes[$key]) ? $this->taxes[$key] : 0);
			}
			
			return $merged_taxes;
		}	
		
 	/*-----------------------------------------------------------------------------------*/
	/* Add to cart handling */
	/*-----------------------------------------------------------------------------------*/ 
	
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
				$product_data = new WC_Product_Variation( $variation_id );
			else :
				$product_data = new WC_Product( $product_id );
			endif;
			
			// Type/Exists check
			if ( $product_data->is_type('external') || !$product_data->exists() ) :
				$woocommerce->add_error( __('This product cannot be purchased.', 'woocommerce') );
				return false; 
			endif;
			
			// Price set check
			if( $product_data->get_price() === '' ) :
				$woocommerce->add_error( __('This product cannot be purchased - the price is not yet set.', 'woocommerce') );
				return false; 
			endif;
	
			// Stock check - only check if we're managing stock and backorders are not allowed
			if ( !$product_data->has_enough_stock( $quantity ) ) :
				$woocommerce->add_error( sprintf(__('You cannot add that amount to the cart since there is not enough stock. We have %s in stock.', 'woocommerce'), $product_data->get_stock_quantity() ));
				return false; 
			elseif ( !$product_data->is_in_stock() ) :
				$woocommerce->add_error( __('You cannot add that product to the cart since the product is out of stock.', 'woocommerce') );
				return false;
			endif;
			
			// Downloadable/virtual qty check
			if ( get_option('woocommerce_limit_downloadable_product_qty')=='yes' && $product_data->is_downloadable() && $product_data->is_virtual() ) :
				$qty = ($cart_item_key) ? $this->cart_contents[$cart_item_key]['quantity'] + $quantity : $quantity;
				if ( $qty > 1 ) :
					$woocommerce->add_error( __('You already have this item in your cart.', 'woocommerce') );
					return false;
				endif;
			endif;
			
			if ($cart_item_key) :
	
				$quantity = $quantity + $this->cart_contents[$cart_item_key]['quantity'];
				
				// Stock check - this time accounting for whats already in-cart
				if ( !$product_data->has_enough_stock( $quantity ) ) :
					$woocommerce->add_error( sprintf(__('You cannot add that amount to the cart since there is not enough stock. We have %s in stock and you already have %s in your cart.', 'woocommerce'), $product_data->get_stock_quantity(), $this->cart_contents[$cart_item_key]['quantity'] ));
					return false; 
				elseif ( !$product_data->is_in_stock() ) :
					$woocommerce->add_error( __('You cannot add that product to the cart since the product is out of stock.', 'woocommerce') );
					return false;
				endif;
	
				$this->set_quantity($cart_item_key, $quantity);
	
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
			
			$woocommerce->cart_has_contents_cookie( true );

			$this->set_session();
			
			return true;
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
				do_action('woocommerce_after_cart_item_quantity_update', $this->cart_contents[$cart_item_key], $quantity);
			endif;
			
			$this->set_session();
		}

    /*-----------------------------------------------------------------------------------*/
	/* Cart Calculation Functions */
	/*-----------------------------------------------------------------------------------*/ 

		/** 
		 * Reset totals
		 */
		private function reset() {
		
			$this->total = 0;
			$this->cart_contents_total = 0;
			$this->cart_contents_weight = 0;
			$this->cart_contents_count = 0;
			$this->cart_contents_tax = 0;
			$this->tax_total = 0;
			$this->shipping_tax_total = 0;
			$this->shipping_taxes = array();
			$this->subtotal = 0;
			$this->subtotal_ex_tax = 0;
			$this->discount_total = 0;
			$this->discount_cart = 0;
			$this->shipping_total = 0;
			$this->taxes = array();
			
			unset( $_SESSION['cart_contents_total'], $_SESSION['cart_contents_weight'], $_SESSION['cart_contents_count'], $_SESSION['cart_contents_tax'], $_SESSION['total'], $_SESSION['subtotal'], $_SESSION['subtotal_ex_tax'], $_SESSION['tax_total'], $_SESSION['taxes'], $_SESSION['shipping_taxes'], $_SESSION['discount_cart'], $_SESSION['discount_total'], $_SESSION['shipping_total'], $_SESSION['shipping_tax_total'], $_SESSION['shipping_label'] );
		}
		
		/** 
		 * Function to apply discounts to a product and get the discounted price (before tax is applied)
		 */
		function get_discounted_price( $values, $price, $add_totals = false ) {
			
			if (!$price) return $price;
			
			if (!empty($this->applied_coupons)) foreach ($this->applied_coupons as $code) :
				$coupon = new WC_Coupon( $code );

				if ( $coupon->apply_before_tax() && $coupon->is_valid() ) :
					
					switch ($coupon->type) :
					
						case "fixed_product" :
						case "percent_product" :
								
							$this_item_is_discounted = false;
							
							$product_cats = wp_get_post_terms($values['product_id'], 'product_cat', array("fields" => "ids"));
				
							// Specific products get the discount
							if (sizeof($coupon->product_ids)>0) {
								
								if (in_array($values['product_id'], $coupon->product_ids) || in_array($values['variation_id'], $coupon->product_ids) || in_array($values['data']->get_parent(), $coupon->product_ids)) 
									$this_item_is_discounted = true;
							
							// Category discounts
							} elseif (sizeof($coupon->product_categories)>0) {
								
								if ( sizeof( array_intersect( $product_cats, $coupon->product_categories ) ) > 0 ) 
									$this_item_is_discounted = true;
								
							} else {
								
								// No product ids - all items discounted
								$this_item_is_discounted = true;
							
							}
				
							// Specific product ID's excluded from the discount
							if (sizeof($coupon->exclude_product_ids)>0) 
								if (in_array($values['product_id'], $coupon->exclude_product_ids) || in_array($values['variation_id'], $coupon->exclude_product_ids) || in_array($values['data']->get_parent(), $coupon->exclude_product_ids))
									$this_item_is_discounted = false;
							
							// Specific categories excluded from the discount
							if (sizeof($coupon->exclude_product_categories)>0) 
								if ( sizeof( array_intersect( $product_cats, $coupon->exclude_product_categories ) ) > 0 ) 
									$this_item_is_discounted = false;
							
							// Apply filter
							$this_item_is_discounted = apply_filters( 'woocommerce_item_is_discounted', $this_item_is_discounted, $values, $before_tax = true );
							
							// Apply the discount
							if ($this_item_is_discounted) :
								if ($coupon->type=='fixed_product') :
									
									if ($price < $coupon->amount) :
										$discount_amount = $price;
									else :
										$discount_amount = $coupon->amount;
									endif;
									
									$price = $price - $coupon->amount;
									
									if ($price<0) $price = 0;
									
									if ($add_totals) :
										$this->discount_cart = $this->discount_cart + ( $discount_amount * $values['quantity'] );
									endif;
									
								elseif ($coupon->type=='percent_product') :
								
									$percent_discount = ( $values['data']->get_price_excluding_tax() / 100 ) * $coupon->amount;
									
									if ($add_totals) $this->discount_cart = $this->discount_cart + ( $percent_discount * $values['quantity'] );
									
									$price = $price - $percent_discount;
									
								endif;
							endif;
	
						break;
						
						case "fixed_cart" :
							
							/** 
							 * This is the most complex discount - we need to divide the discount between rows based on their price in
							 * proportion to the subtotal. This is so rows with different tax rates get a fair discount, and so rows
							 * with no price (free) don't get discount too.
							 */
							
							// Get item discount by dividing item cost by subtotal to get a %
							if ($this->subtotal_ex_tax) 
								$discount_percent = ($values['data']->get_price_excluding_tax()*$values['quantity']) / $this->subtotal_ex_tax;
							else
								$discount_percent = 0;
								
							// Use pence to help prevent rounding errors
							$coupon_amount_pence = $coupon->amount * 100;
							
							// Work out the discount for the row
							$item_discount = $coupon_amount_pence * $discount_percent;
							
							// Work out discount per item
							$item_discount = $item_discount / $values['quantity'];
							
							// Pence
							$price = ( $price * 100 );
							
							// Check if discount is more than price
							if ($price < $item_discount) :
								$discount_amount = $price;
							else :
								$discount_amount = $item_discount;
							endif;
							
							// Take discount off of price (in pence)
							$price = $price - $discount_amount;
							
							// Back to pounds
							$price = $price / 100; 
							
							// Cannot be below 0
							if ($price<0) $price = 0;
							
							// Add coupon to discount total (once, since this is a fixed cart discount and we don't want rounding issues)
							if ($add_totals) $this->discount_cart = $this->discount_cart + (($discount_amount*$values['quantity']) / 100);
	
						break;
						
						case "percent" :
						
							$percent_discount = ( $values['data']->get_price(  ) / 100 ) * $coupon->amount;
									
							if ($add_totals) $this->discount_cart = $this->discount_cart + ( $percent_discount * $values['quantity'] );
							
							$price = $price - $percent_discount;
							
						break;
						
					endswitch;
					
				endif;
			endforeach;
			
			return apply_filters( 'woocommerce_get_discounted_price', $price, $values, $this );
		}
		
		/** 
		 * Function to apply product discounts after tax
		 */
		function apply_product_discounts_after_tax( $values, $price ) {
			
			if (!empty($this->applied_coupons)) foreach ($this->applied_coupons as $code) :
				$coupon = new WC_Coupon( $code );
				
				do_action( 'woocommerce_product_discount_after_tax_' . $coupon->type, $coupon );
				
				if ($coupon->type!='fixed_product' && $coupon->type!='percent_product') continue;
				
				if ( !$coupon->apply_before_tax() && $coupon->is_valid() ) :
					
					$this_item_is_discounted = false;
		
					// Specific products get the discount
					if (sizeof($coupon->product_ids)>0) {
						
						if (in_array($values['product_id'], $coupon->product_ids) || in_array($values['variation_id'], $coupon->product_ids) || in_array($values['data']->get_parent(), $coupon->product_ids)) 
							$this_item_is_discounted = true;
					
					// Category discounts
					} elseif (sizeof($coupon->product_categories)>0) {
						
						if ( sizeof( array_intersect( $product_cats, $coupon->product_categories ) ) > 0 ) 
							$this_item_is_discounted = true;
						
					} else {
						
						// No product ids - all items discounted
						$this_item_is_discounted = true;
					
					}
		
					// Specific product ID's excluded from the discount
					if (sizeof($coupon->exclude_product_ids)>0) 
						if (in_array($values['product_id'], $coupon->exclude_product_ids) || in_array($values['variation_id'], $coupon->exclude_product_ids) || in_array($values['data']->get_parent(), $coupon->exclude_product_ids))
							$this_item_is_discounted = false;
					
					// Specific categories excluded from the discount
					if (sizeof($coupon->exclude_product_categories)>0) 
						if ( sizeof( array_intersect( $product_cats, $coupon->exclude_product_categories ) ) > 0 ) 
							$this_item_is_discounted = false;
					
					// Apply filter
					$this_item_is_discounted = apply_filters( 'woocommerce_item_is_discounted', $this_item_is_discounted, $values, $before_tax = false );
					
					// Apply the discount
					if ($this_item_is_discounted) :
						if ($coupon->type=='fixed_product') :
						
							if ($price < $coupon->amount) :
								$discount_amount = $price;
							else :
								$discount_amount = $coupon->amount;
							endif;
									
							$this->discount_total = $this->discount_total + ( $discount_amount * $values['quantity'] );
							
						elseif ($coupon->type=='percent_product') :
							$this->discount_total = $this->discount_total + ( $price / 100 ) * $coupon->amount;
						endif;
					endif;
					
				endif;
			endforeach;
		}
		
		/** 
		 * Function to apply cart discounts after tax
		 */
		function apply_cart_discounts_after_tax() {	
			
			if ($this->applied_coupons) foreach ($this->applied_coupons as $code) :
				$coupon = new WC_Coupon( $code );
				
				do_action( 'woocommerce_cart_discount_after_tax_' . $coupon->type, $coupon );
				
				if ( !$coupon->apply_before_tax() && $coupon->is_valid() ) :
					
					switch ($coupon->type) :
					
						case "fixed_cart" :
	
							$this->discount_total = $this->discount_total + $coupon->amount;
							
						break;
						
						case "percent" :
							
							$percent_discount = (round( $this->cart_contents_total + $this->tax_total , 2) / 100 ) * $coupon->amount;
							
							$this->discount_total = $this->discount_total + $percent_discount;
							
						break;
						
					endswitch;
	
				endif;
			endforeach;
		}
		
		/** 
		 * calculate totals for the items in the cart 
		 */
		function calculate_totals() {
			global $woocommerce;
			
			$this->reset();
			
			do_action('woocommerce_before_calculate_totals', $this);
			
			// Get count of all items + weights + subtotal (we may need this for discounts)
			if (sizeof($this->cart_contents)>0) foreach ($this->cart_contents as $cart_item_key => $values) :
				
				$_product = $values['data'];
				
				$this->cart_contents_weight = $this->cart_contents_weight + ($_product->get_weight() * $values['quantity']);
				$this->cart_contents_count 	= $this->cart_contents_count + $values['quantity'];
				
				// Base Price (inlusive of tax for now)
				$row_base_price 		= $_product->get_price() * $values['quantity'];
				$base_tax_rates 		= $this->tax->get_shop_base_rate( $_product->tax_class );
				$tax_amount				= 0;
				
				if ($this->prices_include_tax) :
					
					if ( $_product->is_taxable() ) :
						
						$tax_rates			 	= $this->tax->get_rates( $_product->get_tax_class() );
						
						// ADJUST BASE if tax rate is different (different region or modified tax class)
						if ( $tax_rates !== $base_tax_rates ) :
							$base_taxes			= $this->tax->calc_tax( $row_base_price, $base_tax_rates, true );
							$modded_taxes		= $this->tax->calc_tax( $row_base_price - array_sum($base_taxes), $tax_rates, false );
							$row_base_price 		= ($row_base_price - array_sum($base_taxes)) + array_sum($modded_taxes);
						endif;
						
						$taxes 					= $this->tax->calc_tax( $row_base_price, $tax_rates, true );
						$tax_amount				= $this->tax->get_tax_total($taxes);
						
					endif;
	
					// Sub total is based on base prices (without discounts)
					$this->subtotal 			= $this->subtotal + $row_base_price;
					
					$this->subtotal_ex_tax 		= $this->subtotal_ex_tax + ( $row_base_price - $tax_amount);
	
				else :
					
					if ( $_product->is_taxable() ) :
					
						$tax_rates			 	= $this->tax->get_rates( $_product->get_tax_class() );
						$taxes 					= $this->tax->calc_tax( $row_base_price, $tax_rates, false );
						$tax_amount				= $this->tax->get_tax_total($taxes);
						
					endif;
					
					// Sub total is based on base prices (without discounts)
					$this->subtotal 			= $this->subtotal + $row_base_price + $tax_amount;
					$this->subtotal_ex_tax 		= $this->subtotal_ex_tax + $row_base_price;
				
				endif;
				
			endforeach;
			
			// Now calc the main totals, including discounts
			if ($this->prices_include_tax) :
			
				/** 
				 * Calculate totals for items
				 */
				if (sizeof($this->cart_contents)>0) : foreach ($this->cart_contents as $cart_item_key => $values) :
					
					/** 
					 * Prices include tax
					 *
					 * To prevent rounding issues we need to work with the inclusive price where possible
					 * otherwise we'll see errors such as when working with a 9.99 inc price, 20% VAT which would
					 * be 8.325 leading to totals being 1p off
					 *
					 * Pre tax coupons come off the price the customer thinks they are paying - tax is calculated
					 * afterwards.
					 *
					 * e.g. $100 bike with $10 coupon = customer pays $90 and tax worked backwards from that
					 *
					 * Used this excellent article for reference:
					 *	http://developer.practicalecommerce.com/articles/1473-Coding-for-Tax-Calculations-Everything-You-Never-Wanted-to-Know-Part-2
					 */
					$_product = $values['data'];
						
					// Base Price (inlusive of tax for now)
					$base_price 			= $_product->get_price();
					
					// Base Price Adjustment
					if ( $_product->is_taxable() ) :
						
						// Get rates
						$tax_rates			 		= $this->tax->get_rates( $_product->get_tax_class() );
	
						/**
						 * ADJUST TAX - Checkout calculations when customer is OUTSIDE the shop base country and prices INCLUDE tax
						 * 	OR
						 * ADJUST TAX - Checkout calculations when a tax class is modified
						 */
						if ( ( $woocommerce->customer->is_customer_outside_base() && defined('WOOCOMMERCE_CHECKOUT')) || ($_product->get_tax_class() !== $_product->tax_class) ) :
							
							// Get tax rate for the store base, ensuring we use the unmodified tax_class for the product
							$base_tax_rates 		= $this->tax->get_shop_base_rate( $_product->tax_class );
							
							// Work out new price based on region
							$row_base_price 		= $base_price * $values['quantity'];
							$base_taxes				= $this->tax->calc_tax( $row_base_price, $base_tax_rates, true );
							$taxes					= $this->tax->calc_tax( $row_base_price - array_sum($base_taxes), $tax_rates, false );
							
							// Tax amount
							$tax_amount				= array_sum($taxes);
							
							// Line subtotal + tax
							$line_subtotal_tax 		= ( get_option('woocommerce_tax_round_at_subtotal')=='no' ) ? round($tax_amount, 2) : $tax_amount;
							$line_subtotal			= $row_base_price - $this->tax->get_tax_total($base_taxes);
							
							// Adjusted price 
							$adjusted_price 		= ($row_base_price - array_sum($base_taxes) + array_sum($taxes)) / $values['quantity'];

							// Apply discounts
							$discounted_price 		= $this->get_discounted_price( $values, $adjusted_price, true );
							
							$discounted_taxes		= $this->tax->calc_tax( $discounted_price * $values['quantity'], $tax_rates, true );
							$discounted_tax_amount	= array_sum($discounted_taxes); // Sum taxes
							
						/**
						 * Regular tax calculation (customer inside base and the tax class is unmodified
						 */
						else :
							
							// Base tax for line before discount - we will store this in the order data
							$tax_amount				= array_sum($this->tax->calc_tax( $base_price * $values['quantity'], $tax_rates, true ));
							
							// Line subtotal + tax
							$line_subtotal_tax 		= ( get_option('woocommerce_tax_round_at_subtotal')=='no' ) ? round($tax_amount, 2) : $tax_amount;
							$line_subtotal			= ($base_price * $values['quantity']) - round($line_subtotal_tax, 2);
							
							// Calc prices and tax (discounted)
							$discounted_price 		= $this->get_discounted_price( $values, $base_price, true );
							$discounted_taxes		= $this->tax->calc_tax( $discounted_price * $values['quantity'], $tax_rates, true );
							$discounted_tax_amount	= array_sum($discounted_taxes); // Sum taxes
														
						endif;
						
						// Tax rows - merge the totals we just got
						foreach (array_keys($this->taxes + $discounted_taxes) as $key) {
						    $this->taxes[$key] = (isset($discounted_taxes[$key]) ? $discounted_taxes[$key] : 0) + (isset($this->taxes[$key]) ? $this->taxes[$key] : 0);
						}
						
					else :
					
						// Discounted Price (price with any pre-tax discounts applied)
						$discounted_price 		= $this->get_discounted_price( $values, $base_price, true );
						$discounted_tax_amount 	= 0;
						$tax_amount 			= 0;
						$line_subtotal_tax		= 0;
						$line_subtotal			= ($base_price * $values['quantity']);
					
					endif;	
										
					// Line prices
					$line_tax = ( get_option('woocommerce_tax_round_at_subtotal')=='no' ) ? round($discounted_tax_amount, 2) : $discounted_tax_amount;
					$line_total 		= ($discounted_price * $values['quantity']) - round($line_tax, 2);
											
					// Add any product discounts (after tax)
					$this->apply_product_discounts_after_tax( $values, $line_total + $discounted_tax_amount );
						
					// Cart contents total is based on discounted prices and is used for the final total calculation
					$this->cart_contents_total 	= $this->cart_contents_total 		+ $line_total;
					
					// Store costs + taxes for lines
					$this->cart_contents[$cart_item_key]['line_total'] 			= $line_total;
					$this->cart_contents[$cart_item_key]['line_tax'] 			= $line_tax;
					$this->cart_contents[$cart_item_key]['line_subtotal'] 		= $line_subtotal;
					$this->cart_contents[$cart_item_key]['line_subtotal_tax'] 	= $line_subtotal_tax;
					
				endforeach; endif;
			
			else :
			
				if (sizeof($this->cart_contents)>0) : foreach ($this->cart_contents as $cart_item_key => $values) :
				
					/** 
					 * Prices exclude tax
					 *
					 * This calculation is simpler - work with the base, untaxed price.
					 */
					$_product = $values['data'];
	
					// Base Price (i.e. no tax, regardless of region)
					$base_price 				= $_product->get_price();
					
					// Discounted Price (base price with any pre-tax discounts applied
					$discounted_price 			= $this->get_discounted_price( $values, $base_price, true );		
								
					// Tax Amount (For the line, based on discounted, ex.tax price)
					if ( $_product->is_taxable() ) :
						
						// Get tax rates
						$tax_rates 				= $this->tax->get_rates( $_product->get_tax_class() );
						
						// Base tax for line before discount - we will store this in the order data
						$tax_amount				= array_sum($this->tax->calc_tax( $base_price * $values['quantity'], $tax_rates, false ));
						
						// Now calc product rates			
						$discounted_taxes		= $this->tax->calc_tax( $discounted_price * $values['quantity'], $tax_rates, false );
						$discounted_tax_amount	= array_sum( $discounted_taxes );
		
						// Tax rows - merge the totals we just got
						foreach (array_keys($this->taxes + $discounted_taxes) as $key) {
						    $this->taxes[$key] = (isset($discounted_taxes[$key]) ? $discounted_taxes[$key] : 0) + (isset($this->taxes[$key]) ? $this->taxes[$key] : 0);
						}
		
					else :
						$discounted_tax_amount 	= 0;
						$tax_amount 			= 0;
					endif;
					
					// Line prices
					$line_subtotal_tax	= $tax_amount;
					$line_tax			= $discounted_tax_amount;
					$line_subtotal		= $base_price * $values['quantity'];	
					$line_total 		= $discounted_price * $values['quantity'];	
											
					// Add any product discounts (after tax)
					$this->apply_product_discounts_after_tax( $values, $line_total + $line_tax );
						
					// Cart contents total is based on discounted prices and is used for the final total calculation
					$this->cart_contents_total 	= $this->cart_contents_total 		+ $line_total;
					
					// Store costs + taxes for lines
					$this->cart_contents[$cart_item_key]['line_total'] 			= $line_total;
					$this->cart_contents[$cart_item_key]['line_tax'] 			= $line_tax;
					$this->cart_contents[$cart_item_key]['line_subtotal'] 		= $line_subtotal;
					$this->cart_contents[$cart_item_key]['line_subtotal_tax'] 	= $line_subtotal_tax;
				
				endforeach; endif;
			
			endif;
			
			// Set tax total to sum of all tax rows
			$this->tax_total	= $this->tax->get_tax_total( $this->taxes );
				
			// VAT exemption done at this point - so all totals are correct before exemption
			if ($woocommerce->customer->is_vat_exempt() || (is_cart() && get_option('woocommerce_display_cart_taxes')=='no')) :
				$this->shipping_tax_total = $this->tax_total = 0;
				$this->taxes = $this->shipping_taxes = array();
			endif;
			
			// Cart Discounts (after tax)
			$this->apply_cart_discounts_after_tax();
			
			// Only go beyond this point if on the cart/checkout
			if (!is_checkout() && !is_cart() && !defined('WOOCOMMERCE_CHECKOUT') && !defined('WOOCOMMERCE_CART')) return;
			
			// Cart Shipping
			$this->calculate_shipping(); 
			
			// VAT exemption for shipping
			if ($woocommerce->customer->is_vat_exempt()) :
				$this->shipping_tax_total = 0;
				$this->shipping_taxes = array();
			endif;
			
			// Round cart/shipping tax rows
			$this->taxes = array_map(array(&$this->tax, 'round'), $this->taxes);
			$this->shipping_taxes = array_map(array(&$this->tax, 'round'), $this->shipping_taxes);
			
			// Allow plugins to hook and alter totals before final total is calculated
			do_action('woocommerce_calculate_totals', $this);			
					
			/** 
			 * Grand Total
			 *
			 * Based on discounted product prices, discounted tax, shipping cost + tax, and any discounts to be added after tax (e.g. store credit)
			 */
			$this->total = apply_filters('woocommerce_calculated_total', number_format( $this->cart_contents_total + $this->tax_total + $this->shipping_tax_total + $this->shipping_total - $this->discount_total, 2, '.', ''), $this);
			
			if ($this->total < 0) $this->total = 0;
		}

		/** 
		 * looks at the totals to see if payment is actually required 
		 */
		function needs_payment() {
			if ( $this->total > 0 ) return true; else return false;
		}
	
    /*-----------------------------------------------------------------------------------*/
	/* Shipping related functions */
	/*-----------------------------------------------------------------------------------*/ 

		/** 
		 * Use the shipping class to calculate shipping
		 */
		function calculate_shipping() {
			global $woocommerce;
			
			if ($this->needs_shipping()) :
				$woocommerce->shipping->calculate_shipping();
			else :
				$woocommerce->shipping->reset_shipping(); 
			endif;
			
			$this->shipping_total 		= $woocommerce->shipping->shipping_total;	// Shipping Total
			$this->shipping_label 		= $woocommerce->shipping->shipping_label;	// Shipping Label
			$this->shipping_taxes		= $woocommerce->shipping->shipping_taxes;	// Shipping Taxes
			$this->shipping_tax_total 	= $this->tax->get_tax_total( $this->shipping_taxes );	// Shipping tax amount
		}
		
		/** 
		 * looks through the cart to see if shipping is actually required 
		 */
		function needs_shipping() {
			if (get_option('woocommerce_calc_shipping')=='no') return false;
			if (!is_array($this->cart_contents)) return false;
		
			$needs_shipping = false;
			
			foreach ($this->cart_contents as $cart_item_key => $values) :
				$_product = $values['data'];
				if ( $_product->needs_shipping() ) :
					$needs_shipping = true;
				endif;
			endforeach;
			
			return apply_filters( 'woocomerce_cart_needs_shipping', $needs_shipping );
		}
		
		/** 
		 * Sees if we need a shipping address 
		 */
		function ship_to_billing_address_only() {
			if (get_option('woocommerce_ship_to_billing_address_only')=='yes') return true; else return false;
		}
		
		/**
		 * gets the shipping total (after calculation)
		 */
		function get_cart_shipping_total() {
			global $woocommerce;
			
			if (isset($this->shipping_label)) :
				if ($this->shipping_total>0) :
				
					// Display ex tax if the option is set, or prices exclude tax
					if ($this->display_totals_ex_tax || !$this->prices_include_tax) :
						
						$return = woocommerce_price($this->shipping_total);
						if ($this->shipping_tax_total>0 && $this->prices_include_tax) :
							$return .= ' <small>'.$woocommerce->countries->ex_tax_or_vat().'</small>';
						endif;
						return $return;
						
					else :
						
						$return = woocommerce_price($this->shipping_total + $this->shipping_tax_total);
						if ($this->shipping_tax_total>0 && !$this->prices_include_tax) :
							$return .= ' <small>'.$woocommerce->countries->inc_tax_or_vat().'</small>';
						endif;
						return $return;
					
					endif;
	
				else :
					return __('Free!', 'woocommerce');
				endif;
			endif;
		}
		
		/**
		 * gets title of the chosen shipping method
		 */
		function get_cart_shipping_title() {
			if (isset($this->shipping_label)) :
				return __('via', 'woocommerce') . ' ' . $this->shipping_label;
			endif;
			return false;
		}

    /*-----------------------------------------------------------------------------------*/
	/* Coupons/Discount related functions */
	/*-----------------------------------------------------------------------------------*/ 

		/** 
		 *returns whether or not a discount has been applied 
		 */
		function has_discount( $code ) {
			if (in_array($code, $this->applied_coupons)) return true;
			return false;
		}

		/**
		 * Applies a coupon code
		 *
		 * @param   string	code	The code to apply
		 * @return   bool	True if the coupon is applied, false if it does not exist or cannot be applied
		 */
		function add_discount( $coupon_code ) {
			global $woocommerce;

			// Coupons are globally disabled
			if ( get_option( 'woocommerce_enable_coupons' ) == 'no' ) return false;

			$the_coupon = new WC_Coupon($coupon_code);
			
			if ($the_coupon->id) :
				
				// Check it can be used with cart
				if (!$the_coupon->is_valid()) :
					$woocommerce->add_error( __('Invalid coupon.', 'woocommerce') );
					return false;
				endif;
				
				// Check if applied
				if ($woocommerce->cart->has_discount($coupon_code)) :
					$woocommerce->add_error( __('Discount code already applied!', 'woocommerce') );
					return false;
				endif;	
				
				// If its individual use then remove other coupons
				if ($the_coupon->individual_use=='yes') :
					$this->applied_coupons = array();
				endif;
				
				foreach ($this->applied_coupons as $code) :
					$coupon = new WC_Coupon($code);
					if ($coupon->individual_use=='yes') :
						$this->applied_coupons = array();
					endif;
				endforeach;
				
				$this->applied_coupons[] = $coupon_code;
				
				$this->set_session();
				
				$woocommerce->add_message( __('Discount code applied successfully.', 'woocommerce') );
				return true;
			
			else :
				$woocommerce->add_error( __('Coupon does not exist!', 'woocommerce') );
				return false;
			endif;
			return false;
		}
	
		/**
		 * gets the array of applied coupon codes
		 */
		function get_applied_coupons() {
			return (array) $this->applied_coupons;
		}
		
		/**
		 * gets the array of applied coupon codes
		 */
		function remove_coupons( $type = 0 ) {
		
			if ($type == 1) :
				if ($this->applied_coupons) foreach ($this->applied_coupons as $index => $code) :
					$coupon = new WC_Coupon( $code );
					if ( $coupon->apply_before_tax() ) unset($this->applied_coupons[$index]);
				endforeach;
				$_SESSION['coupons'] = $this->applied_coupons;
			elseif ($type == 2) :
				if ($this->applied_coupons) foreach ($this->applied_coupons as $index => $code) :
					$coupon = new WC_Coupon( $code );
					if ( !$coupon->apply_before_tax() ) unset($this->applied_coupons[$index]);
				endforeach;
				$_SESSION['coupons'] = $this->applied_coupons;
			else :
				unset($_SESSION['coupons']);
				$this->applied_coupons = array();
			endif;
		}

    /*-----------------------------------------------------------------------------------*/
	/* Get Formatted Totals */
	/*-----------------------------------------------------------------------------------*/ 
	
		/** 
		 * Get the total of all order discounts (after tax discounts)
		 */
		function get_order_discount_total() {
			return $this->discount_total;
		}
		
		/** 
		 * Get the total of all cart discounts (before tax discounts)
		 */
		function get_cart_discount_total() {
			return $this->discount_cart;
		}
		
		/**
		 * gets the total (after calculation)
		 */
		function get_total() {
			return woocommerce_price($this->total);
		}
		
		/**
		 * gets the total excluding taxes
		 */
		function get_total_ex_tax() {
			$total = $this->total - $this->tax_total - $this->shipping_tax_total;
			if ($total<0) $total = 0;
			return woocommerce_price( $total );
		}
		
		/**
		 * gets the cart contents total (after calculation)
		 */
		function get_cart_total() {
			if (!$this->prices_include_tax) :
				return woocommerce_price($this->cart_contents_total);
			else :
				return woocommerce_price($this->cart_contents_total + $this->tax_total);
			endif;
		}
		
		/**
		 * gets the sub total (after calculation)
		 */
		function get_cart_subtotal( $compound = false ) {
			global $woocommerce;
			
			// If the cart has compound tax, we want to show the subtotal as
			// cart + shipping + non-compound taxes (after discount)
			if ($compound) :
				
				return woocommerce_price( $this->cart_contents_total + $this->shipping_total + $this->get_taxes_total( false ) );
			
			// Otherwise we show cart items totals only (before discount)
			else :
			
				// Display ex tax if the option is set, or prices exclude tax
				if ($this->display_totals_ex_tax || !$this->prices_include_tax) :
					
					$return = woocommerce_price( $this->subtotal_ex_tax );
					
					if ($this->tax_total>0 && $this->prices_include_tax) :
						$return .= ' <small>'.$woocommerce->countries->ex_tax_or_vat().'</small>';
					endif;
					return $return;
					
				else :
					
					$return = woocommerce_price( $this->subtotal );
					
					if ($this->tax_total>0 && !$this->prices_include_tax) :
						$return .= ' <small>'.$woocommerce->countries->inc_tax_or_vat().'</small>';
					endif;
					return $return;
				
				endif;
			endif;
		}
		
		/** 
		 * Get the product row subtotal
		 *
		 * Gets the tax etc to avoid rounding issues.
		 *
		 * When on the checkout (review order), this will get the subtotal based on the customer's tax rate rather than the base rate
		 */
		function get_product_subtotal( $_product, $quantity ) {
			global $woocommerce;
	
			$price 			= $_product->get_price();
			$taxable 		= $_product->is_taxable();
			$base_tax_rates = $this->tax->get_shop_base_rate( $_product->tax_class );
			$tax_rates 		= $this->tax->get_rates( $_product->get_tax_class() ); // This will get the base rate unless we're on the checkout page
			
			// Taxable
			if ( $taxable ) :
	
				if ( $this->display_cart_ex_tax && $this->prices_include_tax ) :
							
					$base_taxes 		= $this->tax->calc_tax( $price * $quantity, $base_tax_rates, true );
					$base_tax_amount	= array_sum( $base_taxes );
					
					$row_price 			= ( $price * $quantity ) - $base_tax_amount;
					
					$return = woocommerce_price( $row_price );
					$return .= ' <small class="tax_label">'.$woocommerce->countries->ex_tax_or_vat().'</small>';
				
				elseif ( !$this->display_cart_ex_tax && $tax_rates !== $base_tax_rates && $this->prices_include_tax ) :
					
					$base_taxes			= $this->tax->calc_tax( $price * $quantity, $base_tax_rates, true );
					$modded_taxes		= $this->tax->calc_tax( ( $price * $quantity ) - array_sum( $base_taxes ), $tax_rates, false );
					$row_price 			= (( $price * $quantity ) - array_sum( $base_taxes )) + array_sum( $modded_taxes );
					
					$return = woocommerce_price( $row_price );
					if (!$this->prices_include_tax) :
						$return .= ' <small class="tax_label">'.$woocommerce->countries->inc_tax_or_vat().'</small>';
					endif;
				
				else :
					
					$row_price 		= $price * $quantity;
					$return = woocommerce_price( $row_price );
				
				endif;
			
			// Non taxable
			else :
				
				$row_price 		= $price * $quantity;
				$return = woocommerce_price( $row_price );
			
			endif;
			
			return $return; 
			
		}
		
		/**
		 * gets the cart tax (after calculation)
		 */
		function get_cart_tax() {
			$return = false;
			$cart_total_tax = $this->tax_total + $this->shipping_tax_total;
			if ($cart_total_tax > 0) $return = woocommerce_price( $cart_total_tax );
			return apply_filters('woocommerce_get_cart_tax', $return);
		}
		
		/**
		 * Get tax row amounts with or without compound taxes includes
		 */
		function get_taxes_total( $compound = true ) {
			$total = 0;
			foreach ($this->taxes as $key => $tax) :
				if (!$compound && $this->tax->is_compound( $key )) continue;
				$total += $tax;
			endforeach;
			foreach ($this->shipping_taxes as $key => $tax) :
				if (!$compound && $this->tax->is_compound( $key )) continue;
				$total += $tax;
			endforeach;
			return $total;
		}
		
		/**
		 * gets the total (product) discount amount - these are applied before tax
		 */
		function get_discounts_before_tax() {
			if ($this->discount_cart) :
				return woocommerce_price($this->discount_cart); 
			endif;
			return false;
		}
		
		/**
		 * gets the order discount amount - these are applied after tax
		 */
		function get_discounts_after_tax() {
			if ($this->discount_total) :
				return woocommerce_price($this->discount_total); 
			endif;
			return false;
		}
		
		/**
		 * gets the total discount amount - both kinds
		 */
		function get_total_discount() {
			if ($this->discount_total || $this->discount_cart) :
				return woocommerce_price($this->discount_total + $this->discount_cart); 
			endif;
			return false;
		}	
}

/** Depreciated */
class woocommerce_cart extends WC_Cart {
	public function __construct() { 
		_deprecated_function( 'woocommerce_cart', '1.4', 'WC_Cart()' );
		parent::__construct(); 
	} 
}