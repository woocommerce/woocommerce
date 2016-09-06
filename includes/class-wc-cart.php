<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( WC_ABSPATH . 'includes/legacy/class-wc-legacy-cart.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-items.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-fees.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-totals.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-item.php' );

/**
 * Main cart class.
 *
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart extends WC_Legacy_Cart {

	/**
	 * Cart items class.
	 * @var WC_Cart_Items
	 */
	protected $items;

	/**
	 * Cart coupons class.
	 * @var WC_Cart_Coupons
	 */
	protected $coupons;

	/**
	 * Cart totals class.
	 * @var WC_Cart_Totals
	 */
	protected $totals;

	/**
	 * Cart fees class.
	 * @var WC_Cart_Fees
	 */
	public $fees;

	/**
	 * Constructor for the cart class.
	 */
	public function __construct() {
		$this->coupons = new WC_Cart_Coupons;
		$this->fees    = new WC_Cart_Fees;
		$this->totals  = new WC_Cart_Totals;
		$this->items   = new WC_Cart_Items;

		// Recalculation
		add_action( 'woocommerce_add_to_cart', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_applied_coupon', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'calculate_totals' ), 20, 0 );

		// Item validation
		add_action( 'woocommerce_check_cart_items', array( $this->items, 'check_items' ), 1 );

		// Trigger the fees API where developers can add fees to the cart
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_fees' ) );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_shipping' ) );

		// Sessions
		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_set_cart_cookies' ) );
		add_action( 'woocommerce_cart_emptied', array( $this, 'destroy_cart_session' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'set_session' ), 20, 0 );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'set_session' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'set_session' ) );
		add_action( 'woocommerce_removed_coupon', array( $this, 'set_session' ) );
	}

	/**
	 * Returns the contents of the cart in an array.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart() {
		if ( ! did_action( 'wp_loaded' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Get cart should not be called before the wp_loaded action.', 'woocommerce' ), '2.3' );
		}
		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
			$this->session->get_cart_from_session();
		}
		return $this->items->get_items();
	}

	/**
	* Checks if the cart is empty.
	* @return bool
	*/
	public function is_empty() {
		return ! sizeof( $this->get_cart() );
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
	 * Looks through the cart to see if shipping is actually required.
	 * @return bool whether or not the cart needs shipping
	 */
	public function needs_shipping() {
		if ( ! wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
			return false;
		}
		$needs_shipping = false;

		foreach ( $this->get_cart() as $cart_item_key => $item ) {
			$product = $item->get_product();
			if ( $product && $product->needs_shipping() ) {
				$needs_shipping = true;
				break;
			}
		}
		return apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );
	}

	/**
	 * Should the shipping address form be shown.
	 * @return bool
	 */
	public function needs_shipping_address() {
		return apply_filters( 'woocommerce_cart_needs_shipping_address', $this->needs_shipping() && ! wc_ship_to_billing_address_only() );
	}

	/**
	 * Get packages to calculate shipping for.
	 *
	 * This lets us calculate costs for carts that are shipped to multiple locations.
	 *
	 * Shipping methods are responsible for looping through these packages.
	 *
	 * By default we pass the cart itself as a package - plugins can change this.
	 * through the filter and break it up.
	 *
	 * @since 1.5.4
	 * @return array of cart items
	 */
	public function get_shipping_packages() {
		return apply_filters( 'woocommerce_cart_shipping_packages',
			array(
				array(
					'contents'        => $this->items->get_items_needing_shipping(),
					'contents_cost'   => array_sum( wc_list_pluck( $this->items->get_items_needing_shipping(), 'get_price' ) ),
					'applied_coupons' => $this->get_coupons(),
					'user'            => array(
						'ID' => get_current_user_id(),
					),
					'destination' => WC()->customer->get_shipping(),
				)
			)
		);
	}

	/**
	 * Uses the shipping class to calculate shipping then gets the totals when its finished.
	 */
	public function calculate_shipping() {
		if ( $this->needs_shipping() ) {
			WC()->shipping->calculate_shipping( $this->get_shipping_packages() );
		} else {
			WC()->shipping->reset_shipping();
		}
	}

	/**
	 * Empties the cart and optionally the persistent cart too.
	 */
	public function empty_cart() {
		$this->items->set_items( false );
		$this->fees->set_fees( false );
		do_action( 'woocommerce_cart_emptied' );
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
	 * Remove item from cart.
	 * @param string $item_key Cart item key.
	 */
	public function remove_cart_item( $item_key ) {
		$this->items->remove_item( $item_key );
	}

	/**
	 * Restore item from cart.
	 * @param string $item_key Cart item key.
	 */
	public function restore_cart_item( $item_key ) {
		$this->items->restore_item( $item_key );
	}

	/**
	 * Add an item to the cart.
	 */
	public function add_item( $args ) {
		$item_key   = $this->items->generate_key( $args );
		$cart_items = $this->items->get_items();

		if ( $item = $this->items->get_item_by_key( $item_key ) ) {
			$item->set_quantity( $item->get_quantity() + $args['quantity']  );
		} else {
			$item                    = new WC_Cart_Item( $args );
			$cart_items[ $item_key ] = apply_filters( 'woocommerce_add_cart_item', $item, $item_key );
		}

		$this->items->set_items( $cart_items );

		return $item_key;
	}

	/**
	 * Set the quantity for an item in the cart.
	 *
	 * @param string	$cart_item_key	contains the id of the cart item
	 * @param int		$quantity		contains the quantity of the item
	 */
	public function set_quantity( $item_key, $quantity = 1, $deprecated = true ) {
		if ( $quantity <= 0 ) {
			$this->items->remove_item( $item_key );
		} elseif ( $item = $this->items->get_item_by_key( $item_key ) ) {
			$old_quantity = $item->get_quantity();
			$item->set_quantity( $quantity );
			do_action( 'woocommerce_after_cart_item_quantity_update', $item_key, $quantity, $old_quantity );
		}
	}

	/**
	 * Get array of fees.
	 * @return array of fees
	 */
	public function get_fees() {
		return $this->fees->get_fees();
	}

	/**
	 * Allow 3rd parties to calculate and register fees.
	 */
	public function calculate_fees() {
		// Remove any existing fees.
		$this->fees->set_fees( false );

		// Fire an action where developers can add their fees
		do_action( 'woocommerce_cart_calculate_fees', $this );
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
		$fee_key = $this->fees->generate_key( $name );
		$fees    = $this->fees->get_fees();

		// Only add each fee once
		if ( isset( $fees[ $fee_key ] ) ) {
			return;
		}

		$fees[ $fee_key ] = (object) array(
			'id'        => $fee_key,
			'name'      => wc_clean( $name ),
			'amount'    => (float) $amount,
			'tax_class' => $tax_class,
			'taxable'   => (bool) $taxable,
		);

		$this->fees->set_fees( $fees );

		return $fee_key;
	}












	/**
	 * Calculate totals for the items in the cart.
	 */
	public function calculate_totals() {
		do_action( 'woocommerce_before_calculate_totals', $this );

		$this->totals->set_coupons( $this->get_coupons() );
		$this->totals->set_fees( $this->get_fees() );
		$this->totals->set_items( $this->get_cart() );
		$this->totals->set_calculate_tax( ! WC()->customer->get_is_vat_exempt() );
		$this->totals->calculate();

		do_action( 'woocommerce_after_calculate_totals', $this );
	}

	/**
	 * Looks at the totals to see if payment is actually required.
	 *
	 * @return bool
	 */
	public function needs_payment() {
		return apply_filters( 'woocommerce_cart_needs_payment', $this->totals->get_total() > 0, $this );
	}

	/**
	 * Gets the order total (after calculation).
	 * @return string formatted price
	 */
	public function get_total() {
		return apply_filters( 'woocommerce_cart_total', wc_price( $this->totals->get_total() ) );
	}

	/**
	 * Gets the order taxes (after calculation).
	 * @return array of taxes
	 */
	public function get_taxes() {
		return apply_filters( 'woocommerce_cart_get_taxes', $this->totals->get_taxes() );
	}





	/**
	 * Get taxes, merged by code, formatted ready for output.
	 *
	 * @return array
	 */
	public function get_tax_totals() {
		$taxes      = $this->get_taxes();
		$tax_totals = array();

		foreach ( $taxes as $key => $tax ) {
			$code = WC_Tax::get_rate_code( $key );

			if ( $code || $key === apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) ) {
				if ( ! isset( $tax_totals[ $code ] ) ) {
					$tax_totals[ $code ] = new stdClass();
					$tax_totals[ $code ]->amount = 0;
				}
				$tax_totals[ $code ]->tax_rate_id       = $key;
				$tax_totals[ $code ]->is_compound       = WC_Tax::is_compound( $key );
				$tax_totals[ $code ]->label             = WC_Tax::get_rate_label( $key );
				$tax_totals[ $code ]->amount           += wc_round_tax_total( $tax );
				$tax_totals[ $code ]->formatted_amount  = wc_price( wc_round_tax_total( $tax_totals[ $code ]->amount ) );
			}
		}

		if ( apply_filters( 'woocommerce_cart_hide_zero_taxes', true ) ) {
			$amounts    = array_filter( wp_list_pluck( $tax_totals, 'amount' ) );
			$tax_totals = array_intersect_key( $tax_totals, $amounts );
		}

		return apply_filters( 'woocommerce_cart_tax_totals', $tax_totals, $this );
	}










	/**
	 * Gets the cart tax (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_tax() {
		$cart_total_tax = wc_round_tax_total( $this->tax_total + $this->shipping_tax_total );
		return apply_filters( 'woocommerce_get_cart_tax', $cart_total_tax ? wc_price( $cart_total_tax ) : '' );
	}

	/**
	 * Get a tax amount.
	 * @param  string $tax_rate_id
	 * @return float amount
	 */
	public function get_tax_amount( $tax_rate_id ) {
		return isset( $this->taxes[ $tax_rate_id ] ) ? $this->taxes[ $tax_rate_id ] : 0;
	}

	/**
	 * Get a tax amount.
	 * @param  string $tax_rate_id
	 * @return float amount
	 */
	public function get_shipping_tax_amount( $tax_rate_id ) {
		return isset( $this->shipping_taxes[ $tax_rate_id ] ) ? $this->shipping_taxes[ $tax_rate_id ] : 0;
	}

	/**
	 * Get tax row amounts with or without compound taxes includes.
	 *
	 * @param  bool $compound True if getting compound taxes
	 * @param  bool $display  True if getting total to display
	 * @return float price
	 */
	public function get_taxes_total( $compound = true, $display = true ) {
		$total = 0;
		foreach ( $this->taxes as $key => $tax ) {
			if ( ! $compound && WC_Tax::is_compound( $key ) ) continue;
			$total += $tax;
		}
		foreach ( $this->shipping_taxes as $key => $tax ) {
			if ( ! $compound && WC_Tax::is_compound( $key ) ) continue;
			$total += $tax;
		}
		if ( $display ) {
			$total = wc_round_tax_total( $total );
		}
		return apply_filters( 'woocommerce_cart_taxes_total', $total, $compound, $display, $this );
	}




	/**
	 * Applies a coupon code passed to the method.
	 *
	 * @param string $coupon_code - The code to apply
	 * @return bool	True if the coupon is applied, false if it does not exist or cannot be applied
	 */
	public function add_discount( $coupon_code ) {
		$this->coupons->add( $coupon_code );
	}

	/**
	 * Remove a single coupon by code.
	 * @param  string $coupon_code Code of the coupon to remove
	 * @return bool
	 */
	public function remove_coupon( $coupon_code ) {
		return $this->coupons->remove_coupon( $coupon_code );
	}

	/**
	 * Get array of applied coupon objects and codes.
	 * @return array of applied coupons
	 */
	public function get_coupons() {
		return $this->coupons->get_coupons();
	}

	/**
	 * Destroy cart session data.
	 * @param  boolean $clear_persistent_cart
	 */
	public function destroy_cart_session( $clear_persistent_cart = true ) {
		WC()->session->set( 'cart', null );
	}

	/**
	 * Will set cart cookies if needed, once, during WP hook.
	 */
	public function maybe_set_cart_cookies() {
		if ( headers_sent() || ! did_action( 'wp_loaded' ) ) {
			return;
		}
		if ( ! $this->is_empty() ) {
			$this->set_cart_cookies( true );
		} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
			$this->set_cart_cookies( false );
		}
	}

	/**
	 * Set cart hash cookie and items in cart.
	 *
	 * @access private
	 * @param bool $set (default: true)
	 */
	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			wc_setcookie( 'woocommerce_items_in_cart', 1 );
			wc_setcookie( 'woocommerce_cart_hash', md5( json_encode( $this->get_cart_for_session() ) ) );
		} else {
			wc_setcookie( 'woocommerce_items_in_cart', 0, time() - HOUR_IN_SECONDS );
			wc_setcookie( 'woocommerce_cart_hash', '', time() - HOUR_IN_SECONDS );
		}
		do_action( 'woocommerce_set_cart_cookies', $set );
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart_for_session() {
		return wc_list_pluck( $this->get_cart(), 'get_data' );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 */
	public function get_cart_from_session() {
		$cart = wp_parse_args( (array) WC()->session->get( 'cart', array() ), array(
			'items'         => array(),
			'removed_items' => array(),
			'coupons'       => array(),
		) );

		foreach ( $cart['items'] as $key => $values ) {
			if ( ! isset( $values['product_id'], $values['quantity'] ) || ! ( $product = wc_get_product( $values['product_id'] ) ) ) {
				unset( $cart['items'][ $key ] );
				continue;
			}
			// Put session data into array. Run through filter so other plugins can load their own session data.
			$cart['items'][ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $values, $values, $key );
		}

		$this->items->set_items( $cart['items'] );
		$this->items->set_removed_items( $cart['removed_items'] );
		$this->coupons->set_coupons( $cart['coupons'] );

		do_action( 'woocommerce_cart_loaded_from_session', $this );
	}

	/**
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		$session_data = array(
			'items'         => $this->get_cart_for_session(),
			'removed_items' => wc_list_pluck( $this->items->get_removed_items(), 'get_data' ),
			'coupons'       => $this->get_coupons(),
		);
		if ( WC()->session->set( 'cart', $session_data ) ) {
			do_action( 'woocommerce_cart_updated' );
		}
	}
}
