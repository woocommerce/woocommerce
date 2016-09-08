<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( WC_ABSPATH . 'includes/class-wc-cart-coupons.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-fees.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-item.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-items.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-session.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-totals.php' );

/**
 * Main cart class.
 *
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart extends WC_Cart_Session {

	/**
	 * Cart totals class.
	 * @var WC_Cart_Totals
	 */
	protected $totals;

	/**
	 * This stores the chosen shipping methods for the cart item packages.
	 * @var array
	 */
	protected $shipping_methods;

	/**
	 * Constructor for the cart class.
	 */
	public function __construct() {
		parent::__construct();

		$this->totals = new WC_Cart_Totals;

		add_action( 'woocommerce_add_to_cart', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_applied_coupon', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'calculate_totals' ), 20, 0 );
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
	 * Applies a coupon code.
	 * @since 2.7.0
	 * @param string $coupon_code - The code to apply
	 * @return bool	True if the coupon is applied, false if it does not exist or cannot be applied
	 */
	public function add_coupon( $coupon_code ) {
		return $this->coupons->add( $coupon_code );
	}

	/**
	 * Returns the contents of the cart in an array.
	 * @return array contents of the cart
	 */
	public function get_cart() {
		if ( ! did_action( 'wp_loaded' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Get cart should not be called before the wp_loaded action.', 'woocommerce' ), '2.3' );
		}
		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
			$this->get_cart_from_session();
		}
		return $this->items->get_items();
	}

	/**
	 * Get array of fees.
	 * @return array of fees
	 */
	public function get_fees() {
		return $this->fees->get_fees();
	}

	/**
	 * Get array of applied coupon objects and codes.
	 * @return array of applied coupons
	 */
	public function get_coupons() {
		return $this->coupons->get_coupons();
	}

	/**
	* Checks if the cart is empty.
	* @return bool
	*/
	public function is_empty() {
		return ! sizeof( $this->get_cart() );
	}

	/**
	 * Empties the cart and optionally the persistent cart too.
	 */
	public function empty_cart() {
		$this->items->set_items( false );
		$this->fees->set_fees( false );
		$this->shipping_methods = null;
		$this->totals = new WC_Cart_Totals;
		do_action( 'woocommerce_cart_emptied' );
	}

	/**
	 * Set the quantity for an item in the cart.
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
	 * Remove item from cart.
	 * @param string $item_key Cart item key.
	 */
	public function remove_cart_item( $item_key ) {
		$this->items->remove_item( $item_key );
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
	 * Returns a specific item in the cart.
	 *
	 * @param string $item_key Cart item key.
	 * @return array Item data
	 */
	public function get_cart_item( $item_key ) {
		 return $this->items->get_item_by_key( $item_key );
	}

	/**
	 * Restore item from cart.
	 * @param string $item_key Cart item key.
	 */
	public function restore_cart_item( $item_key ) {
		$this->items->restore_item( $item_key );
	}

	/**
	 * Get all tax classes for items in the cart.
	 * @return array
	 */
	public function get_cart_item_tax_classes() {
		return $this->items->get_tax_classes();
	}

	/**
	 * Get weight of items in the cart.
	 * @return int
	 */
	public function get_cart_contents_weight() {
		return $this->items->get_weight();
	}

	/**
	 * Get number of items in the cart.
	 * @return int
	 */
	public function get_cart_contents_count() {
		return $this->items->get_quantity();
	}

	/*
	|--------------------------------------------------------------------------
	| Cart shipping calculation.
	|--------------------------------------------------------------------------
	*/

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
	 * Uses the shipping class to calculate shipping then gets the totals when its finished. @todo where should this be called? After item calc? How to get totals to totals class?
	 */
	public function calculate_shipping() {
		return $this->shipping_methods = $this->needs_shipping() ? $this->get_chosen_shipping_methods( WC()->shipping->calculate_shipping( $this->get_shipping_packages() ) ) : array();
	}

	/**
	 * Given a set of packages with rates, get the chosen ones only.
	 * @since 2.7.0
	 * @param array $calculated_shipping_packages
	 * @return array
	 */
	protected function get_chosen_shipping_methods( $calculated_shipping_packages ) {
		$chosen_methods = array();

		// Get chosen methods for each package to get our totals.
		foreach ( $calculated_shipping_packages as $key => $package ) {
			$chosen_method = wc_get_chosen_shipping_method_for_package( $key );
			$changed       = wc_shipping_methods_have_changed( $key, $package['rates'] );

			// If not set, not available, or available methods have changed, set to the DEFAULT option
			if ( ! $chosen_method || $changed || ! isset( $package['rates'][ $chosen_method ] ) ) {
				$chosen_method = apply_filters( 'woocommerce_shipping_chosen_method', current( $package['rates'] ), $package['rates'], $chosen_method );
				do_action( 'woocommerce_shipping_method_chosen', $chosen_method );
			}

			$chosen_methods[ $key ] = $package['rates'][ $chosen_method ];
		}

		return $chosen_methods;
	}

	/*
	|--------------------------------------------------------------------------
	| Cart Totals.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Calculate totals for the items in the cart.
	 */
	public function calculate_totals() {
		do_action( 'woocommerce_before_calculate_totals', $this );

		// Calculate line item totals
		$this->totals->set_coupons( $this->get_coupons() );
		$this->totals->set_items( $this->get_cart() );
		$this->totals->set_calculate_tax( ! WC()->customer->get_is_vat_exempt() );
		$this->totals->calculate_item_totals();

		// Calculate fees
		$this->totals->set_fees( $this->fees->calculate_fees() );

		// Calculate shipping costs
		$this->totals->set_shipping( $this->calculate_shipping() );

		// Calc grand totals
		$this->totals->calculate_totals();

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
		$taxes = $this->get_taxes();

		if ( apply_filters( 'woocommerce_cart_hide_zero_taxes', true ) ) {
			$zero_amounts = array_filter( wp_list_pluck( $taxes, 'amount' ) );
			$taxes        = array_intersect_key( $taxes, $zero_amounts );
		}

		return apply_filters( 'woocommerce_cart_formatted_tax_totals', $taxes );
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











}
