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
	
	private static $_instance;
	public static $cart_contents_total;
	public static $cart_contents_total_ex_tax;
	public static $cart_contents_weight;
	public static $cart_contents_count;
	public static $cart_contents_tax;
	public static $cart_contents;
	public static $total;
	public static $subtotal;
	public static $subtotal_ex_tax;
	public static $tax_total;
	public static $discount_total;
	public static $shipping_total;
	public static $shipping_tax_total;
	public static $applied_coupons;
	
	/** constructor */
	function __construct() {

		add_action('init', array($this, 'init'), 1);

	}
	
	/** get class instance */
	public static function get() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    
    public function init () {
  		self::$applied_coupons = array();
		
		self::get_cart_from_session();
		
		if ( isset($_SESSION['coupons']) ) self::$applied_coupons = $_SESSION['coupons'];
		
		self::calculate_totals();
    }
	
	/** Gets the cart data from the PHP session */
	function get_cart_from_session() {

		if ( isset($_SESSION['cart']) && is_array($_SESSION['cart']) ) :
			$cart = $_SESSION['cart'];
			
			foreach ($cart as $values) :
			
				if ($values['variation_id']>0) :
					$_product = &new woocommerce_product_variation($values['variation_id']);
				else :
					$_product = &new woocommerce_product($values['product_id']);
				endif;
				
				if ($_product->exists && $values['quantity']>0) :
				
					self::$cart_contents[] = array(
						'product_id'	=> $values['product_id'],
						'variation_id'	=> $values['variation_id'],
						'variation' 	=> $values['variation'],
						'quantity' 		=> $values['quantity'],
						'data'			=> $_product
					);

				endif;
			endforeach;
			
		else :
			self::$cart_contents = array();
		endif;
		
		if (!is_array(self::$cart_contents)) self::$cart_contents = array();
	}
	
	/** sets the php session data for the cart and coupon */
	function set_session() {
		$cart = array();

		$_SESSION['cart'] = self::$cart_contents;
		
		$_SESSION['coupons'] = self::$applied_coupons;
		self::calculate_totals();
	}
	
	/** Empty the cart */
	function empty_cart() {
		unset($_SESSION['cart']);
		unset($_SESSION['coupons']);
	}
	
	/**
     * Check if product is in the cart and return cart item key
     * 
     * @param int $product_id
     * @param int $variation_id optional variation id
     * @param array $variation array of attributre values
     * @return int|null
     */
	function find_product_in_cart($product_id, $variation_id, $variation = array()) {

        foreach (self::$cart_contents as $cart_item_key => $cart_item) :
        
            if (empty($variation_id) && $cart_item['product_id'] == $product_id) :
				return $cart_item_key;
            elseif ($cart_item['product_id'] == $product_id && $cart_item['variation_id'] == $variation_id) :
                if($variation == $cart_item['variation']) :
					return $cart_item_key;
                endif;
            endif;
        
        endforeach;
        
        return NULL;
    }
	
	/**
	 * Add a product to the cart
	 *
	 * @param   string	product_id	contains the id of the product to add to the cart
	 * @param   string	quantity	contains the quantity of the item to add
	 * @param   int     variation_id
	 * @param   array   variation attribute values
	 */
	function add_to_cart( $product_id, $quantity = 1, $variation = '', $variation_id = '' ) {
		
		if ($quantity < 1) $quantity = 1;
		
		$found_cart_item_key = self::find_product_in_cart($product_id, $variation_id, $variation);
		
		$product_data = &new woocommerce_product( $product_id );
		
		// Price set check
		if( $product_data->get_price() === '' ) { 
			woocommerce::add_error( __('This product cannot be purchased - the price is not yet announced', 'woothemes') );
			return false; 
		}
		
		if (is_numeric($found_cart_item_key)) :
			
			$quantity = $quantity + self::$cart_contents[$found_cart_item_key]['quantity'];
			
			self::$cart_contents[$found_cart_item_key]['quantity'] = $quantity;
			
		else :
			
			$cart_item_key = sizeof(self::$cart_contents);
				
			self::$cart_contents[$cart_item_key] = array(
				'product_id'	=> $product_id,
				'variation_id'	=> $variation_id,
				'variation' 	=> $variation,
				'quantity' 		=> $quantity,
				'data'			=> $product_data
			);
			
		endif;
		
		self::set_session();
	}
	
	/**
	 * Set the quantity for an item in the cart
	 *
	 * @param   string	cart_item_key	contains the id of the cart item
	 * @param   string	quantity	contains the quantity of the item
	 */
	function set_quantity( $cart_item, $quantity = 1 ) {
		if ($quantity==0 || $quantity<0) :
			unset(self::$cart_contents[$cart_item]);
		else :
			self::$cart_contents[$cart_item]['quantity'] = $quantity;
		endif;

		self::set_session();
	}
	
	/**
	 * Returns the contents of the cart
	 *
	 * @return   array	cart_contents
	 */
	function get_cart() {
		return self::$cart_contents;
	}
	
	/**
	 * Gets cross sells based on the items in the cart
	 *
	 * @return   array	cross_sells	item ids of cross sells
	 */
	function get_cross_sells() {
		$cross_sells = array();
		$in_cart = array();
		if (sizeof(self::$cart_contents)>0) : foreach (self::$cart_contents as $cart_item_key => $values) :
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
		$cart_page_id = get_option('woocommerce_cart_page_id');
		if ($cart_page_id) return woocommerce::nonce_url( 'cart', add_query_arg('remove_item', $cart_item_key, get_permalink($cart_page_id)));
	}
	
	/** looks through the cart to see if shipping is actually required */
	function needs_shipping() {
	
		if (!woocommerce_shipping::$enabled) return false;
		if (!is_array(self::$cart_contents)) return false;
	
		$needs_shipping = false;
		
		foreach (self::$cart_contents as $cart_item_key => $values) :
			$_product = $values['data'];
			if ( $_product->is_type( 'simple' ) || $_product->is_type( 'variable' ) ) :
				$needs_shipping = true;
			endif;
		endforeach;
		
		return $needs_shipping;
	}
	
	/** Sees if we need a shipping address */
	function ship_to_billing_address_only() {
	
		$ship_to_billing_address_only = get_option('woocommerce_ship_to_billing_address_only');
		
		if ($ship_to_billing_address_only=='yes') return true;
		
		return false;
	}
	
	/** looks at the totals to see if payment is actually required */
	function needs_payment() {
		if ( self::$total > 0 ) return true;
		return false;
	}
	
	/** looks through the cart to check each item is in stock */
	function check_cart_item_stock() {
		$error = new WP_Error();
		foreach (self::$cart_contents as $cart_item_key => $values) :
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
	
	/** calculate totals for the items in the cart */
	public static function calculate_totals() {
		
		$_tax = &new woocommerce_tax();

		self::$total = 0;
		self::$cart_contents_total = 0;
		self::$cart_contents_total_ex_tax = 0;
		self::$cart_contents_weight = 0;
		self::$cart_contents_count = 0;
		self::$cart_contents_tax = 0;
		self::$tax_total = 0;
		self::$shipping_tax_total = 0;
		self::$subtotal = 0;
		self::$subtotal_ex_tax = 0;
		self::$discount_total = 0;
		self::$shipping_total = 0;
		
		if (sizeof(self::$cart_contents)>0) : foreach (self::$cart_contents as $cart_item_key => $values) :
			$_product = $values['data'];
			if ($_product->exists() && $values['quantity']>0) :
				
				self::$cart_contents_count = self::$cart_contents_count + $values['quantity'];
				
				self::$cart_contents_weight = self::$cart_contents_weight + ($_product->get_weight() * $values['quantity']);

				$total_item_price = $_product->get_price() * $values['quantity'];
				
				if ( get_option('woocommerce_calc_taxes')=='yes') :
					
					if ( $_product->is_taxable() ) :
					
						$rate = $_tax->get_rate( $_product->tax_class );
						
						if (get_option('woocommerce_prices_include_tax')=='yes') :
						
							$tax_amount = $_tax->calc_tax( $_product->get_price(), $rate, true ) * $values['quantity'];
							
						else :
						
							$tax_amount = $_tax->calc_tax( $_product->get_price(), $rate, false ) * $values['quantity'];
							
						endif;
						
						if (get_option('woocommerce_prices_include_tax')=='yes' && woocommerce_customer::is_customer_outside_base() && defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT ) :
							
							/**
							 * Our prices include tax so we need to take the base tax rate into consideration of our shop's country
							 *
							 * Lets get the base rate first
							 */
							$base_rate = $_tax->get_shop_base_rate( $_product->tax_class );
							
							// Calc tax for base country
							$base_tax_amount = $_tax->calc_tax( $_product->get_price(), $base_rate, true);
							
							// Now calc tax for user county (which now excludes tax)
							$tax_amount = $_tax->calc_tax( ( $_product->get_price() - $base_tax_amount ), $rate, false );
							$tax_amount = $tax_amount * $values['quantity'];
							
							// Finally, update $total_item_price to reflect tax amounts
							$total_item_price = ($total_item_price - ($base_tax_amount * $values['quantity']) + $tax_amount);
							
						endif;

					endif;
					
				endif;

				$tax_amount 				= ( isset($tax_amount) ? $tax_amount : 0 );
				
				self::$cart_contents_tax = self::$cart_contents_tax + $tax_amount;
								
				self::$cart_contents_total = self::$cart_contents_total + $total_item_price;
				self::$cart_contents_total_ex_tax = self::$cart_contents_total_ex_tax + ($_product->get_price_excluding_tax()*$values['quantity']);
				
				// Product Discounts
				if (self::$applied_coupons) foreach (self::$applied_coupons as $code) :
					$coupon = woocommerce_coupons::get_coupon($code);
					if ($coupon['type']=='fixed_product' && in_array($values['product_id'], $coupon['products'])) :
						self::$discount_total = self::$discount_total + ( $coupon['amount'] * $values['quantity'] );
					endif;
				endforeach;
				
			endif;
		endforeach; endif;
		
		// Cart Shipping
		if (self::needs_shipping()) woocommerce_shipping::calculate_shipping(); else woocommerce_shipping::reset_shipping();
		
		self::$shipping_total = woocommerce_shipping::$shipping_total;
		
		self::$shipping_tax_total = woocommerce_shipping::$shipping_tax;
		
		self::$tax_total = self::$cart_contents_tax;
		
		// Subtotal
		self::$subtotal_ex_tax = self::$cart_contents_total_ex_tax;
		self::$subtotal = self::$cart_contents_total;
		
		// Cart Discounts
		if (self::$applied_coupons) foreach (self::$applied_coupons as $code) :
			$coupon = woocommerce_coupons::get_coupon($code);
			if (woocommerce_coupons::is_valid($code)) :

				if ($coupon['type']=='fixed_cart') : 
					self::$discount_total = self::$discount_total + $coupon['amount'];
				elseif ($coupon['type']=='percent') :
					self::$discount_total = self::$discount_total + ( self::$subtotal / 100 ) * $coupon['amount'];
				endif;
			
			endif;
		endforeach;
		
		// Total
		if (get_option('woocommerce_prices_include_tax')=='yes') :
			self::$total = self::$subtotal + self::$shipping_tax_total - self::$discount_total + woocommerce_shipping::$shipping_total;
		else :
			self::$total = self::$subtotal + self::$tax_total + self::$shipping_tax_total - self::$discount_total + woocommerce_shipping::$shipping_total;
		endif;
		
		if (self::$total < 0) self::$total = 0;
	}
	
	/** gets the total (after calculation) */
	function get_total() {
		return woocommerce_price(self::$total);
	}
	
	/** gets the cart contens total (after calculation) */
	function get_cart_total() {
		return woocommerce_price(self::$cart_contents_total);
	}
	
	/** gets the sub total (after calculation) */
	function get_cart_subtotal() {
		
		if (get_option('woocommerce_display_totals_tax')=='excluding' || ( defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT )) :
			
			if (get_option('woocommerce_prices_include_tax')=='yes') :
				
				$return = woocommerce_price(self::$subtotal - self::$tax_total);
				
			else :
				
				$return = woocommerce_price(self::$subtotal);
				
			endif;
			
			if (self::$tax_total>0) :
				$return .= __(' <small>(ex. tax)</small>', 'woothemes');
			endif;
			return $return;
			
		else :
			
			if (get_option('woocommerce_prices_include_tax')=='yes') :
				
				$return = woocommerce_price(self::$subtotal);
				
			else :
				
				$return = woocommerce_price(self::$subtotal + self::$tax_total);
				
			endif;
			
			if (self::$tax_total>0) :
				$return .= __(' <small>(inc. tax)</small>', 'woothemes');
			endif;
			return $return;
		
		endif;

	}
	
	/** gets the cart tax (after calculation) */
	function get_cart_tax() {
		$cart_total_tax = self::$tax_total + self::$shipping_tax_total;
		if ($cart_total_tax > 0) return woocommerce_price( $cart_total_tax );
		return false;
	}
	
	/** gets the shipping total (after calculation) */
	function get_cart_shipping_total() {
		if (isset(woocommerce_shipping::$shipping_label)) :
			if (woocommerce_shipping::$shipping_total>0) :
			
				if (get_option('woocommerce_display_totals_tax')=='excluding') :
					
					$return = woocommerce_price(woocommerce_shipping::$shipping_total);
					if (self::$shipping_tax_total>0) :
						$return .= __(' <small>(ex. tax)</small>', 'woothemes');
					endif;
					return $return;
					
				else :
					
					$return = woocommerce_price(woocommerce_shipping::$shipping_total + woocommerce_shipping::$shipping_tax);
					if (self::$shipping_tax_total>0) :
						$return .= __(' <small>(inc. tax)</small>', 'woothemes');
					endif;
					return $return;
				
				endif;

			else :
				return __('Free!', 'woothemes');
			endif;
		endif;
	}
	
	/** gets title of the chosen shipping method */
	function get_cart_shipping_title() {
		if (isset(woocommerce_shipping::$shipping_label)) :
			return __('via ', 'woothemes') . woocommerce_shipping::$shipping_label;
		endif;
		return false;
	}
	
	/**
	 * Applies a coupon code
	 *
	 * @param   string	code	The code to apply
	 * @return   bool	True if the coupon is applied, false if it does not exist or cannot be applied
	 */
	function add_discount( $coupon_code ) {
		
		if ($the_coupon = woocommerce_coupons::get_coupon($coupon_code)) :
			
			// Check if applied
			if (woocommerce_cart::has_discount($coupon_code)) :
				woocommerce::add_error( __('Discount code already applied!', 'woothemes') );
				return false;
			endif;	
			
			// Check it can be used with cart
			if (!woocommerce_coupons::is_valid($coupon_code)) :
				woocommerce::add_error( __('Invalid coupon.', 'woothemes') );
				return false;
			endif;
			
			// If its individual use then remove other coupons
			if ($the_coupon['individual_use']==1) :
				self::$applied_coupons = array();
			endif;
			
			foreach (self::$applied_coupons as $coupon) :
				$coupon = woocommerce_coupons::get_coupon($coupon);
				if ($coupon['individual_use']==1) :
					self::$applied_coupons = array();
				endif;
			endforeach;
			
			self::$applied_coupons[] = $coupon_code;
			self::set_session();
			woocommerce::add_message( __('Discount code applied successfully.', 'woothemes') );
			return true;
		
		else :
			woocommerce::add_error( __('Coupon does not exist!', 'woothemes') );
			return false;
		endif;
		return false;
		
	}
	
	/** returns whether or not a discount has been applied */
	function has_discount( $code ) {
		if (in_array($code, self::$applied_coupons)) return true;
		return false;
	}
	
	/** gets the total discount amount */
	function get_total_discount() {
		if (self::$discount_total) return woocommerce_price(self::$discount_total); else return false;
	}
	
	/** clears the cart/coupon data and re-calcs totals */
	function clear_cache() {
		self::$cart_contents = array();
		self::$applied_coupons = array();
		unset( $_SESSION['cart'] );
		unset( $_SESSION['coupons'] );
		self::calculate_totals();
	}
	
}