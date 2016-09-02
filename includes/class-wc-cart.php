<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( WC_ABSPATH . 'includes/legacy/class-wc-legacy-cart.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-session.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-items.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-shipping.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-fees.php' );



/*

add_action( 'woocommerce_add_to_cart', array( $this, 'calculate' ), 20, 0 );
add_action( 'woocommerce_applied_coupon', array( $this, 'calculate' ), 20, 0 );
woocommerce_cart_item_removed
woocommerce_cart_item_restored
 */

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
	 * Cart session class.
	 * @var WC_Cart_Session
	 */
	public $session;

	protected $_data = array(
		'subtotal' => 0,
		'total'    => 0,
		'taxes'    => array(),
	);

	/**
	 * Constructor for the cart class.
	 */
	public function __construct() {
		$this->session = new WC_Cart_Session;
		$this->items   = new WC_Cart_Items;
		$this->coupons = new WC_Cart_Coupons;
		$this->fees    = new WC_Cart_Fees;
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
		return array_filter( wp_list_pluck( (array) $this->items->get_items(), 'values' ) );
	}

	/**
	 * Empties the cart and optionally the persistent cart too.
	 */
	public function empty_cart() {
		$this->items->set_items( 0 );
		do_action( 'woocommerce_cart_emptied' );
	}

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
		try {
			// Ensure we don't add a variation to the cart directly by variation ID
			if ( 'product_variation' === get_post_type( $product_id ) ) {
				$variation_id = $product_id;
				$product_id   = wp_get_post_parent_id( $variation_id );
			}

			// Get the product
			$product = wc_get_product( $variation_id ? $variation_id : $product_id );

			// Get quantity
			if ( $product->is_sold_individually() ) {
				$quantity = apply_filters( 'woocommerce_add_to_cart_sold_individually_quantity', 1, $quantity, $product_id, $variation_id, $cart_item_data );
			} else {
				$quantity = min( 0, $quantity );
			}

			$this->items->validate_item( $product, $quantity );

			// Load cart item data - may be added by other plugins
			$cart_item_data = array_merge(
				(array) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id ),
				array(
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'variation'    => $variation,
					'quantity'     => $quantity,
					'data'         => $product,
				)
			);

			// Generate a ID based on product ID, variation ID, variation data, and other cart item data.
			$cart_item_key = $this->items->generate_id( $cart_item_data );

			// If cart_item_key is set, the item is already in the cart
			if ( $this->items->get_item_by_key( $cart_item_key ) ) {
				$this->items->set_quantity( $cart_item_key, $quantity + $this->items[ $cart_item_key ]['quantity'] );
			} else {
				$this->items->add_item( apply_filters( 'woocommerce_add_cart_item', $cart_item_data, $cart_item_key ), $cart_item_key );
			}

			do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );

			return $cart_item_key;
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
		}
		return false;
	}

	/**
	* Checks if the cart is empty.
	* @return bool
	*/
	public function is_empty() {
		return ! sizeof( $this->get_cart() );
	}

	/**
	 * Looks at the totals to see if payment is actually required.
	 *
	 * @return bool
	 */
	public function needs_payment() {
		return apply_filters( 'woocommerce_cart_needs_payment', $this->get_total() > 0, $this );
	}

	/**
	 * Calculate totals for the items in the cart.
	 */
	public function calculate_totals() {
		$this->coupons = $this->get_coupons();

		do_action( 'woocommerce_before_calculate_totals', $this );
$this->session->set_session();
		if ( $this->is_empty() ) {
			//$this->set_session();
			return;
		}





		$this->items->calculate();
		$this->fees->calculate();
		$this->shipping->calculate();







		$cart_item_totals = new WC_Cart_Item_Totals();
		$cart_item_totals->set_items( $this->get_cart() );
		$cart_item_totals->set_coupons( $this->get_coupons() );

		$this->subtotal                    = $cart_item_totals->get_subtotal( true );
		$this->subtotal_ex_tax             = $cart_item_totals->get_subtotal( false );
		$this->taxes                       = $cart_item_totals->get_tax_data();
		$this->cart_contents_total         = $cart_item_totals->get_total( false );
		$this->discount_cart               = $cart_item_totals->get_discount_total();
		$this->discount_cart_tax           = $cart_item_totals->get_discount_total_tax();
		$this->coupon_discount_amounts     = wp_list_pluck( $cart_item_totals->get_coupons(), 'total' );
		$this->coupon_discount_tax_amounts = wp_list_pluck( $cart_item_totals->get_coupons(), 'total_tax' );

		foreach ( $cart_item_totals->get_items() as $cart_item_key => $item ) {
			/**
			 * Store costs + taxes for lines. For tax inclusive prices, we do some extra rounding logic so the stored
			 * values "add up" when viewing the order in admin. This does have the disadvatage of not being able to
			 * recalculate the tax total/subtotal accurately in the future, but it does ensure the data looks correct.
			 *
			 * Tax exclusive prices are not affected.
			 */
			if ( ! $item->product->is_taxable() || wc_prices_include_tax() ) {
				$this->cart_contents[ $cart_item_key ]['line_total']        = round( $item->total + $item->tax - wc_round_tax_total( $item->tax ), $this->dp );
				$this->cart_contents[ $cart_item_key ]['line_subtotal']     = round( $item->subtotal + $item->subtotal_tax - wc_round_tax_total( $item->subtotal_tax ), $this->dp );
				$this->cart_contents[ $cart_item_key ]['line_tax']          = wc_round_tax_total( $item->tax );
				$this->cart_contents[ $cart_item_key ]['line_subtotal_tax'] = wc_round_tax_total( $item->subtotal_tax );
				$this->cart_contents[ $cart_item_key ]['line_tax_data']     = array( 'total' => array_map( 'wc_round_tax_total', $item->tax_data ), 'subtotal' => array_map( 'wc_round_tax_total', $item->subtotal_tax_data ) );
			} else {
				$this->cart_contents[ $cart_item_key ]['line_total']        = $item->total;
				$this->cart_contents[ $cart_item_key ]['line_subtotal']     = $item->subtotal;
				$this->cart_contents[ $cart_item_key ]['line_tax']          = $item->tax;
				$this->cart_contents[ $cart_item_key ]['line_subtotal_tax'] = $item->subtotal_tax;
				$this->cart_contents[ $cart_item_key ]['line_tax_data']     = array( 'total' => $item->tax_data, 'subtotal' => $item->subtotal_tax_data );
			}
		}

		// Only calculate the grand total + shipping if on the cart/checkout
		if ( is_checkout() || is_cart() || defined('WOOCOMMERCE_CHECKOUT') || defined('WOOCOMMERCE_CART') ) {

			// Calculate the Shipping
			$this->calculate_shipping();

			// Trigger the fees API where developers can add fees to the cart
			$this->calculate_fees();

			// Total up/round taxes and shipping taxes
			if ( $this->round_at_subtotal ) {
				$this->tax_total          = WC_Tax::get_tax_total( $this->taxes );
				$this->shipping_tax_total = WC_Tax::get_tax_total( $this->shipping_taxes );
				$this->taxes              = array_map( array( 'WC_Tax', 'round' ), $this->taxes );
				$this->shipping_taxes     = array_map( array( 'WC_Tax', 'round' ), $this->shipping_taxes );
			} else {
				$this->tax_total          = array_sum( $this->taxes );
				$this->shipping_tax_total = array_sum( $this->shipping_taxes );
			}

			// VAT exemption done at this point - so all totals are correct before exemption
			if ( WC()->customer->get_is_vat_exempt() ) {
				$this->remove_taxes();
			}

			// Allow plugins to hook and alter totals before final total is calculated
			do_action( 'woocommerce_calculate_totals', $this );

			// Grand Total - Discounted product prices, discounted tax, shipping cost + tax
			$this->total = max( 0, apply_filters( 'woocommerce_calculated_total', round( $this->cart_contents_total + $this->tax_total + $this->shipping_tax_total + $this->shipping_total + $this->fee_total, $this->dp ), $this ) );

		} else {

			// Set tax total to sum of all tax rows
			$this->tax_total = WC_Tax::get_tax_total( $this->taxes );

			// VAT exemption done at this point - so all totals are correct before exemption
			if ( WC()->customer->get_is_vat_exempt() ) {
				$this->remove_taxes();
			}
		}

		do_action( 'woocommerce_after_calculate_totals', $this );
	}

	/**
	 * Remove taxes.
	 */
	public function remove_taxes() {
		$this->shipping_tax_total = $this->tax_total = 0;
		$this->subtotal           = $this->subtotal_ex_tax;

		foreach ( $this->cart_contents as $cart_item_key => $item ) {
			$this->cart_contents[ $cart_item_key ]['line_subtotal_tax'] = $this->cart_contents[ $cart_item_key ]['line_tax'] = 0;
			$this->cart_contents[ $cart_item_key ]['line_tax_data']     = array( 'total' => array(), 'subtotal' => array() );
		}

		// If true, zero rate is applied so '0' tax is displayed on the frontend rather than nothing.
		if ( apply_filters( 'woocommerce_cart_remove_taxes_apply_zero_rate', true ) ) {
			$this->taxes = $this->shipping_taxes = array( apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) => 0 );
		} else {
			$this->taxes = $this->shipping_taxes = array();
		}
	}



	public function get_subtotal() {
		return $this->_data['subtotal'];
	}

	public function get_total() {
		return apply_filters( 'woocommerce_cart_total', $this->_data['total'] );
	}

	public function get_taxes() {
		return apply_filters( 'woocommerce_cart_get_taxes', $this->_data['taxes'], $this );
	}


	public function set_subtotal( $value ) {
		$this->_data['subtotal'] = $value;
	}

	public function set_total( $value ) {
		$this->_data['total'] = $value;
	}

	public function set_taxes( $value ) {
		$this->_data['taxes'] = $value;
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
	 * Gets the cart contents total (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_total() {
		if ( ! $this->prices_include_tax ) {
			$cart_contents_total = wc_price( $this->cart_contents_total );
		} else {
			$cart_contents_total = wc_price( $this->cart_contents_total + $this->tax_total );
		}

		return apply_filters( 'woocommerce_cart_contents_total', $cart_contents_total );
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
