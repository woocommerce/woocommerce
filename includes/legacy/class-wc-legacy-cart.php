<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Cart.
 *
 * Legacy and deprecated functions are here to keep the WC_Cart class clean.
 * This class will be removed in future versions.
 *
 * @class       WC_Legacy_Cart
 * @version     2.7.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
abstract class WC_Legacy_Cart {

	/**
	* Add a product to the cart.
	*
	* @param int $product_id contains the id of the product to add to the cart
	* @param int $quantity contains the quantity of the item to add
	* @param int $variation_id
	* @param array $variation attribute values
	* @param array $cart_item_data extra cart item data we want to pass into the item
	* @return string|bool $cart_item_key
	*/
   public function add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
	   return $this->items->add( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
   }


   /**
	* Get cart items quantities - merged so we can do accurate stock checks on items across multiple lines.
	* @return array
	*/
   public function get_cart_item_quantities() {
	   return $this->items->get_item_quantities();
   }

   /**
	* Get all tax classes for items in the cart.
	* @return array
	*/
   public function get_cart_item_tax_classes() {
	   return $this->items->get_item_tax_classes();
   }

   /**
	* Get weight of items in the cart.
	* @return int
	*/
   public function get_cart_contents_weight() {
	   return $this->items->get_item_weight();
   }

   /**
	* Get number of items in the cart.
	* @return int
	*/
   public function get_cart_contents_count() {
	   return $this->items->get_item_count();
   }

   /**
	* Returns a specific item in the cart.
	*
	* @param string $item_key Cart item key.
	* @return array Item data
	*/
   public function get_cart_item( $item_key ) {
	    return $this->items->get_item_by_key( $item_key );
   }

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'cart_contents' :
				return $this->get_cart();
			break;


			/****
			/**
			 * An array of fees.
			 * @var array
			 *
			public $fees = array();
			/** @var array Contains an array of cart items. *
			public $cart_contents = array();

			/** @var array Contains an array of removed cart items. *
			public $removed_cart_contents = array();

			/** @var array Contains an array of coupon codes applied to the cart. *
			public $applied_coupons = array();

			/** @var array Contains an array of coupon code discounts after they have been applied. *
			public $coupon_discount_amounts = array();

			/** @var array Contains an array of coupon code discount taxes. Used for tax incl pricing. *
			public $coupon_discount_tax_amounts = array();

			/** @var array Contains an array of coupon usage counts after they have been applied. *
			public $coupon_applied_count = array();

			/** @var array Array of coupons *
			public $coupons = array();

			/** @var float The total cost of the cart items. *
			public $cart_contents_total;

			/** @var float Cart grand total. *
			public $total;

			/** @var float Cart subtotal. *
			public $subtotal;

			/** @var float Cart subtotal without tax. *
			public $subtotal_ex_tax;

			/** @var float Total cart tax. *
			public $tax_total;

			/** @var array An array of taxes/tax rates for the cart. *
			public $taxes;

			/** @var array An array of taxes/tax rates for the shipping. *
			public $shipping_taxes;

			/** @var float Discount amount before tax *
			public $discount_cart;

			/** @var float Discounted tax amount. Used predominantly for displaying tax inclusive prices correctly *
			public $discount_cart_tax;

			/** @var float Total for additional fees. *
			public $fee_total;

			/** @var float Shipping cost. *
			public $shipping_total;

			/** @var float Shipping tax. *
			public $shipping_tax_total;

			/** @var array cart_session_data. Array of data the cart calculates and stores in the session with defaults *
			public $cart_session_data = array(
			 */


			case 'prices_include_tax' :
				return wc_prices_include_tax();
			break;
			case 'round_at_subtotal' :
				return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
			break;
			case 'tax_display_cart' :
				return get_option( 'woocommerce_tax_display_cart' );
			break;
			case 'dp' :
				return wc_get_price_decimals();
			break;
			case 'display_totals_ex_tax' :
			case 'display_cart_ex_tax' :
				return $this->tax_display_cart === 'excl';
			break;
			case 'cart_contents_weight' :
				return $this->get_cart_contents_weight();
			break;
			case 'cart_contents_count' :
				return $this->get_cart_contents_count();
			break;
			case 'tax' :
				_deprecated_argument( 'WC_Cart->tax', '2.3', 'Use WC_Tax:: directly' );
				$this->tax = new WC_Tax();
			return $this->tax;
			case 'discount_total':
				_deprecated_argument( 'WC_Cart->discount_total', '2.3', 'After tax coupons are no longer supported. For more information see: https://woocommerce.wordpress.com/2014/12/upcoming-coupon-changes-in-woocommerce-2-3/' );
			return 0;
		}
	}
	public function find_product_in_cart( $cart_id = false ) {
		if ( $cart_id !== false ) {
			if ( is_array( $this->cart_contents ) && isset( $this->cart_contents[ $cart_id ] ) ) {
				return $cart_id;
			}
		}
		return '';
	}
	/**
	 * Generate a unique ID for the cart item being added.
	 *
	 * @param int $product_id - id of the product the key is being generated for
	 * @param int $variation_id of the product the key is being generated for
	 * @param array $variation data for the cart item
	 * @param array $cart_item_data other cart item data passed which affects this items uniqueness in the cart
	 * @return string cart item key
	 */
	public function generate_cart_id( $product_id, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		return apply_filters( 'woocommerce_cart_id', md5( json_encode( array( $product_id, $variation_id, $variation, $cart_item_data ) ) ), $product_id, $variation_id, $variation, $cart_item_data );
	}

	/**
	 * Looks through cart items and checks the posts are not trashed or deleted.
	 *
	 * @return bool|WP_Error
	 */
	public function check_cart_item_validity() {
		$return = true;

		foreach ( $this->get_items() as $cart_item_key => $values ) {
			$_product = $values['data'];

			if ( ! $_product || ! $_product->exists() || 'trash' === $_product->post->post_status ) {
				$this->set_quantity( $cart_item_key, 0 );
				$return = new WP_Error( 'invalid', __( 'An item which is no longer available was removed from your cart.', 'woocommerce' ) );
			}
		}

		return $return;
	}

	/**
	 * Looks through the cart to check each item is in stock. If not, add an error.
	 *
	 * @return bool|WP_Error
	 */
	public function check_cart_item_stock() {
	}
	/**
	 * Save the persistent cart when the cart is updated.
	 */
	public function persistent_cart_update() {
	}

	/**
	 * Delete the persistent cart permanently.
	 */
	public function persistent_cart_destroy() {
	}
	/**
	 * Determines the value that the customer spent and the subtotal
	 * displayed, used for things like coupon validation.
	 *
	 * Since the coupon lines are displayed based on the TAX DISPLAY value
	 * of cart, this is used to determine the spend.
	 *
	 * If cart totals are shown including tax, use the subtotal.
	 * If cart totals are shown excluding tax, use the subtotal ex tax
	 * (tax is shown after coupons).
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_displayed_subtotal() {
		_deprecated_function( 'get_displayed_subtotal', '2.7', 'wc_cart_subtotal_to_display' );
		return wc_cart_subtotal_to_display();
	}

	/**
	 * Get the product row price per item.
	 *
	 * @param WC_Product $product
	 * @return string formatted price
	 */
	public function get_product_price( $product ) {
		_deprecated_function( 'get_product_price', '2.7', 'wc_cart_product_price_html' );
		return wc_cart_product_price_to_display( $product );
	}

	/**
	 * Gets the sub total (after calculation).
	 *
	 * @param bool $compound whether to include compound taxes
	 * @return string formatted price
	 */
	public function get_cart_subtotal( $compound = false ) {
		_deprecated_function( 'get_cart_subtotal', '2.7', 'wc_cart_subtotal_html' );
		return wc_cart_subtotal_html();
	}

	/**
	 * Get the product row subtotal.
	 *
	 * Gets the tax etc to avoid rounding issues.
	 *
	 * When on the checkout (review order), this will get the subtotal based on the customer's tax rate rather than the base rate.
	 *
	 * @param WC_Product $product
	 * @param int $quantity
	 * @return string formatted price
	 */
	public function get_product_subtotal( $product, $quantity ) {
		_deprecated_function( 'get_product_subtotal', '2.7', 'wc_cart_product_price_html' );
		return wc_cart_product_price_to_display( $product, $quantity );
	}

	/**
	 * Get the total of all cart discounts.
	 *
	 * @return float
	 */
	public function get_cart_discount_total() {
		return wc_cart_round_discount( $this->discount_cart, $this->dp );
	}

	/**
	 * Get the total of all cart tax discounts (used for discounts on tax inclusive prices).
	 *
	 * @return float
	 */
	public function get_cart_discount_tax_total() {
		return wc_cart_round_discount( $this->discount_cart_tax, $this->dp );
	}

	/**
	 * Loads the cart data from the PHP session during WordPress init and hooks in other methods.
	 */
	public function init() {

	}

	/**
	 * Gets the total excluding taxes.
	 *
	 * @return string formatted price
	 */
	public function get_total_ex_tax() {
		return apply_filters( 'woocommerce_cart_total_ex_tax', wc_price( min( 0, $this->total - $this->tax_total - $this->shipping_tax_total ) ) );
	}



			/**
			 * Gets and formats a list of cart item data + variations for display on the frontend.
			 *
			 * @param array $cart_item
			 * @param bool $flat (default: false)
			 * @return string
			 */
			public function get_item_data( $cart_item, $flat = false ) {
				return wc_display_item_data( $cart_item, $flat = false  );
			}

	/**
	 * Add additional fee to the cart.
	 *
	 * @param string $name Unique name for the fee. Multiple fees of the same name cannot be added.
	 * @param float $amount Fee amount.
	 * @param bool $taxable (default: false) Is the fee taxable?
	 * @param string $tax_class (default: '') The tax class for the fee if taxable. A blank string is standard tax class.
	 */
	public function add_fee( $name, $amount, $taxable = false, $tax_class = '' ) {

		$new_fee_id = sanitize_title( $name );

		// Only add each fee once
		foreach ( $this->fees as $fee ) {
			if ( $fee->id == $new_fee_id ) {
				return;
			}
		}

		$new_fee            = new stdClass();
		$new_fee->id        = $new_fee_id;
		$new_fee->name      = esc_attr( $name );
		$new_fee->amount    = (float) esc_attr( $amount );
		$new_fee->tax_class = $tax_class;
		$new_fee->taxable   = $taxable ? true : false;
		$new_fee->tax       = 0;
		$new_fee->tax_data  = array();
		$this->fees[]       = $new_fee;
	}

	/**
	 * Get fees.
	 *
	 * @return array
	 */
	public function get_fees() {
		return array_filter( (array) $this->fees );
	}

	/**
	 * Calculate fees.
	 */
	public function calculate_fees() {
		// Reset fees before calculation
		$this->fee_total = 0;
		$this->fees      = array();

		// Fire an action where developers can add their fees
		do_action( 'woocommerce_cart_calculate_fees', $this );

		// If fees were added, total them and calculate tax
		if ( ! empty( $this->fees ) ) {
			foreach ( $this->fees as $fee_key => $fee ) {
				$this->fee_total += $fee->amount;

				if ( $fee->taxable ) {
					// Get tax rates
					$tax_rates = WC_Tax::get_rates( $fee->tax_class );
					$fee_taxes = WC_Tax::calc_tax( $fee->amount, $tax_rates, false );

					if ( ! empty( $fee_taxes ) ) {
						// Set the tax total for this fee
						$this->fees[ $fee_key ]->tax = array_sum( $fee_taxes );

						// Set tax data - Since 2.2
						$this->fees[ $fee_key ]->tax_data = $fee_taxes;

						// Tax rows - merge the totals we just got
						foreach ( array_keys( $this->taxes + $fee_taxes ) as $key ) {
							$this->taxes[ $key ] = ( isset( $fee_taxes[ $key ] ) ? $fee_taxes[ $key ] : 0 ) + ( isset( $this->taxes[ $key ] ) ? $this->taxes[ $key ] : 0 );
						}
					}
				}
			}
		}
	}


	//public function check_customer_coupons( $posted ) {


	/**
	 * Applies a coupon code passed to the method.
	 *
	 * @param string $coupon_code - The code to apply
	 * @return bool	True if the coupon is applied, false if it does not exist or cannot be applied
	 */
	public function add_discount( $coupon_code ) {
		// Coupons are globally disabled
	}

	/**
	 * Returns whether or not a discount has been applied.
	 * @param string $coupon_code
	 * @return bool
	 */
	public function has_discount( $coupon_code = '' ) {
		return $coupon_code ? in_array( apply_filters( 'woocommerce_coupon_code', $coupon_code ), $this->applied_coupons ) : sizeof( $this->applied_coupons ) > 0;
	}

	/**
	 * Get array of applied coupon objects and codes.
	 * @return array of applied coupons
	 */
	public function get_coupons( $deprecated = null ) {
	}

	/**
	 * Gets the array of applied coupon codes.
	 *
	 * @return array of applied coupons
	 */
	public function get_applied_coupons() {
		return $this->applied_coupons;
	}

	/**
	 * Get the discount amount for a used coupon.
	 * @param  string $code coupon code
	 * @param  bool $ex_tax inc or ex tax
	 * @return float discount amount
	 */
	public function get_coupon_discount_amount( $code, $ex_tax = true ) {
		$discount_amount = isset( $this->coupon_discount_amounts[ $code ] ) ? $this->coupon_discount_amounts[ $code ] : 0;

		if ( ! $ex_tax ) {
			$discount_amount += $this->get_coupon_discount_tax_amount( $code );
		}

		return wc_cart_round_discount( $discount_amount, $this->dp );
	}

	/**
	 * Get the discount tax amount for a used coupon (for tax inclusive prices).
	 * @param  string $code coupon code
	 * @param  bool inc or ex tax
	 * @return float discount amount
	 */
	public function get_coupon_discount_tax_amount( $code ) {
		return wc_cart_round_discount( isset( $this->coupon_discount_tax_amounts[ $code ] ) ? $this->coupon_discount_tax_amounts[ $code ] : 0, $this->dp );
	}

	/**
	 * Remove coupons from the cart of a defined type. Type 1 is before tax, type 2 is after tax.
	 */
	public function remove_coupons( $deprecated = null ) {
		$this->applied_coupons = $this->coupon_discount_amounts = $this->coupon_discount_tax_amounts = $this->coupon_applied_count = array();
		WC()->session->set( 'applied_coupons', array() );
		WC()->session->set( 'coupon_discount_amounts', array() );
		WC()->session->set( 'coupon_discount_tax_amounts', array() );
	}

	/**
	 * Remove a single coupon by code.
	 * @param  string $coupon_code Code of the coupon to remove
	 * @return bool
	 */
	public function remove_coupon( $coupon_code ) {

	}

			/**
			 * Function to apply discounts to a product and get the discounted price (before tax is applied).
			 *
			 * @param mixed $values
			 * @param mixed $price
			 * @param bool $add_totals (default: false)
			 * @return float price
			 */
			public function get_discounted_price( $values, $price, $add_totals = false ) {
				//return apply_filters( 'woocommerce_get_discounted_price', $price, $values, $this );
			}

			/**
			 * Gets cross sells based on the items in the cart.
			 *
			 * @return array cross_sells (item ids)
			 */
			public function get_cross_sells() {
				wc_get_cart_cross_sells();
			}

			/**
			 * Gets the url to remove an item from the cart.
			 *
			 * @param string $cart_item_key contains the id of the cart item
			 * @return string url to page
			 */
			public function get_remove_url( $cart_item_key ) {
				return wc_get_cart_remove_url( $cart_item_key );
			}

			/**
			 * Gets the url to re-add an item into the cart.
			 *
			 * @param  string $cart_item_key
			 * @return string url to page
			 */
			public function get_undo_url( $cart_item_key ) {
				return wc_get_cart_undo_url( $cart_item_key );
			}

	/**
	 * Gets the total discount amount.
	 * @deprecated 2.7.0 in favor to get_cart_discount_total()
	 */
	public function get_total_discount() {
		_deprecated_function( 'get_total_discount', '2.7', 'get_cart_discount_total' );
		return wc_price( $this->get_cart_discount_total() );
	}

	/**
	 * Gets the url to the cart page.
	 *
	 * @deprecated 2.5.0 in favor to wc_get_cart_url()
	 * @return string url to page
	 */
	public function get_cart_url() {
		_deprecated_function( 'get_cart_url', '2.5', 'wc_get_cart_url' );
		return wc_get_cart_url();
	}

	/**
	 * Gets the url to the checkout page.
	 *
	 * @deprecated 2.5.0 in favor to wc_get_checkout_url()
	 * @return string url to page
	 */
	public function get_checkout_url() {
		_deprecated_function( 'get_checkout_url', '2.5', 'wc_get_checkout_url' );
		return wc_get_checkout_url();
	}

	/**
	 * Coupons enabled function. Filterable.
	 *
	 * @deprecated 2.5.0 in favor to wc_coupons_enabled()
	 * @return bool
	 */
	public function coupons_enabled() {
		_deprecated_function( 'coupons_enabled', '2.5', 'wc_coupons_enabled' );
		return wc_coupons_enabled();
	}

	/**
	 * Sees if we need a shipping address.
	 *
	 * @deprecated 2.5.0 in favor to wc_ship_to_billing_address_only()
	 * @return bool
	 */
	public function ship_to_billing_address_only() {
		_deprecated_function( 'ship_to_billing_address_only', '2.5', 'wc_ship_to_billing_address_only' );
		return wc_ship_to_billing_address_only();
	}
}
