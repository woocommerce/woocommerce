<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( WC_ABSPATH . 'includes/legacy/class-wc-legacy-cart.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-item-totals.php' );

/**
 * WooCommerce cart
 *
 * Main cart class handles the session and retrieves totals from the various items inside the cart.
 *
 * @class 		WC_Cart
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart extends WC_Legacy_Cart {

	/**
	 * Session data.
	 * @var array
	 */
	protected $session_data = array(
		'cart_contents_total'         => 0,
		'total'                       => 0,
		'subtotal'                    => 0,
		'subtotal_ex_tax'             => 0,
		'tax_total'                   => 0,
		'taxes'                       => array(),
		'shipping_taxes'              => array(),
		'discount_cart'               => 0,
		'discount_cart_tax'           => 0,
		'shipping_total'              => 0,
		'shipping_tax_total'          => 0,
		'coupon_discount_amounts'     => array(),
		'coupon_discount_tax_amounts' => array(),
		'fee_total'                   => 0,
		'fees'                        => array(),
	);

	/**
	 * An array of fees.
	 * @var array
	 */
	public $fees = array();

	/**
	 * Constructor for the cart class. Loads options and hooks in the init method.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 ); // Set cookies
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 ); // Set cookies before shutdown and ob flushing
		add_action( 'woocommerce_add_to_cart', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_applied_coupon', array( $this, 'calculate_totals' ), 20, 0 );

		$this->items     = new WC_Cart_Items;
		$this->fees      = new WC_Cart_Fees;
		$this->discounts = new WC_Cart_Coupons;
	}

	/**
	 * Will set cart cookies if needed, once, during WP hook.
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( ! $this->is_empty() ) {
				$this->set_cart_cookies( true );
			} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
				$this->set_cart_cookies( false );
			}
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
		} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
			wc_setcookie( 'woocommerce_items_in_cart', 0, time() - HOUR_IN_SECONDS );
			wc_setcookie( 'woocommerce_cart_hash', '', time() - HOUR_IN_SECONDS );
		}
		do_action( 'woocommerce_set_cart_cookies', $set );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Cart Session Handling */
	/*-----------------------------------------------------------------------------------*/

		/**
		 * Get the cart data from the PHP session and store it in class variables.
		 */
		public function get_cart_from_session() {
			// Load cart session data from session
			foreach ( $this->session_data as $key => $default ) {
				$this->$key = WC()->session->get( $key, $default );
			}

			$update_cart_session         = false;
			$this->removed_cart_contents = array_filter( WC()->session->get( 'removed_cart_contents', array() ) );
			$this->applied_coupons       = array_filter( WC()->session->get( 'applied_coupons', array() ) );

			/**
			 * Load the cart object. This defaults to the persistent cart if null.
			 */
			$cart = WC()->session->get( 'cart', null );

			if ( is_null( $cart ) && ( $saved_cart = get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', true ) ) ) {
				$cart                = $saved_cart['cart'];
				$update_cart_session = true;
			} elseif ( is_null( $cart ) ) {
				$cart = array();
			}

			if ( is_array( $cart ) ) {
				// Prime meta cache to reduce future queries
				update_meta_cache( 'post', wp_list_pluck( $cart, 'product_id' ) );

				foreach ( $cart as $key => $values ) {
					$_product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );

					if ( ! empty( $_product ) && $_product->exists() && $values['quantity'] > 0 ) {

						if ( ! $_product->is_purchasable() ) {

							// Flag to indicate the stored cart should be update
							$update_cart_session = true;
							wc_add_notice( sprintf( __( '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.', 'woocommerce' ), $_product->get_title() ), 'error' );
							do_action( 'woocommerce_remove_cart_item_from_session', $key, $values );

						} else {

							// Put session data into array. Run through filter so other plugins can load their own session data
							$session_data = array_merge( $values, array( 'data' => $_product ) );
							$this->cart_contents[ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $key );

						}
					}
				}
			}

			// Trigger action
			do_action( 'woocommerce_cart_loaded_from_session', $this );

			if ( $update_cart_session ) {
				WC()->session->cart = $this->get_cart_for_session();
			}

			// Queue re-calc if subtotal is not set
			if ( ( ! $this->subtotal && ! $this->is_empty() ) || $update_cart_session ) {
				$this->calculate_totals();
			}
		}

		/**
		 * Sets the php session data for the cart and coupons.
		 */
		public function set_session() {
			// Set cart and coupon session data
			$cart_session = $this->get_cart_for_session();

			WC()->session->set( 'cart', $cart_session );
			WC()->session->set( 'applied_coupons', $this->applied_coupons );
			WC()->session->set( 'coupon_discount_amounts', $this->coupon_discount_amounts );
			WC()->session->set( 'coupon_discount_tax_amounts', $this->coupon_discount_tax_amounts );
			WC()->session->set( 'removed_cart_contents', $this->removed_cart_contents );

			foreach ( $this->session_data as $key => $default ) {
				WC()->session->set( $key, $this->$key );
			}

			if ( get_current_user_id() ) {
				$this->persistent_cart_update();
			}

			do_action( 'woocommerce_cart_updated' );
		}

		/**
		 * Empties the cart and optionally the persistent cart too.
		 *
		 * @param bool $clear_persistent_cart (default: true)
		 */
		public function empty_cart( $clear_persistent_cart = true ) {
			$this->cart_contents = array();
			$this->reset( true );

			unset( WC()->session->order_awaiting_payment, WC()->session->applied_coupons, WC()->session->coupon_discount_amounts, WC()->session->coupon_discount_tax_amounts, WC()->session->cart );

			if ( $clear_persistent_cart && get_current_user_id() ) {
				$this->persistent_cart_destroy();
			}

			do_action( 'woocommerce_cart_emptied' );
		}

	/*-----------------------------------------------------------------------------------*/
	/* Persistent cart handling */
	/*-----------------------------------------------------------------------------------*/

		/**
		 * Save the persistent cart when the cart is updated.
		 */
		public function persistent_cart_update() {
			update_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', array(
				'cart' => WC()->session->get( 'cart' ),
			) );
		}

		/**
		 * Delete the persistent cart permanently.
		 */
		public function persistent_cart_destroy() {
			delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart' );
		}

	/*-----------------------------------------------------------------------------------*/
	/* Cart Data Functions */
	/*-----------------------------------------------------------------------------------*/

		/**
		 * Get number of items in the cart.
		 * @return int
		 */
		public function get_cart_contents_count() {
			return apply_filters( 'woocommerce_cart_contents_count', array_sum( wp_list_pluck( $this->get_cart(), 'quantity' ) ) );
		}

		/**
		 * Get weight of items in the cart.
		 * @since 2.5.0
		 * @return int
		 */
		public function get_cart_contents_weight() {
			$weight = 0;

			foreach ( $this->get_cart() as $cart_item_key => $values ) {
				$weight += $values['data']->get_weight() * $values['quantity'];
			}

			return apply_filters( 'woocommerce_cart_contents_weight', $weight );
		}

		/**
		* Checks if the cart is empty.
		*
		* @return bool
		*/
		public function is_empty() {
			return 0 === sizeof( $this->get_cart() );
		}

		/**
		 * Get cart items quantities - merged so we can do accurate stock checks on items across multiple lines.
		 *
		 * @return array
		 */
		public function get_cart_item_quantities() {
			$quantities = array();

			foreach ( $this->get_cart() as $cart_item_key => $values ) {
				$_product = $values['data'];

				if ( $_product->is_type( 'variation' ) && true === $_product->managing_stock() ) {
					// Variation has stock levels defined so its handled individually
					$quantities[ $values['variation_id'] ] = isset( $quantities[ $values['variation_id'] ] ) ? $quantities[ $values['variation_id'] ] + $values['quantity'] : $values['quantity'];
				} else {
					$quantities[ $values['product_id'] ] = isset( $quantities[ $values['product_id'] ] ) ? $quantities[ $values['product_id'] ] + $values['quantity'] : $values['quantity'];
				}
			}

			return $quantities;
		}

		/**
		 * Looks through cart items and checks the posts are not trashed or deleted.
		 *
		 * @return bool|WP_Error
		 */
		public function check_cart_item_validity() {
			$return = true;

			foreach ( $this->get_cart() as $cart_item_key => $values ) {
				$_product = $values['data'];

				if ( ! $_product || ! $_product->exists() || 'trash' === $_product->post->post_status ) {
					$this->set_quantity( $cart_item_key, 0 );
					$return = new WP_Error( 'invalid', __( 'An item which is no longer available was removed from your cart.', 'woocommerce' ) );
				}
			}

			return $return;
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
				$this->get_cart_from_session();
			}
			return array_filter( (array) $this->cart_contents );
		}

		/**
		 * Returns the contents of the cart in an array without the 'data' element.
		 *
		 * @return array contents of the cart
		 */
		public function get_cart_for_session() {
			$cart_session = array();

			if ( $this->get_cart() ) {
				foreach ( $this->get_cart() as $key => $values ) {
					$cart_session[ $key ] = $values;
					unset( $cart_session[ $key ]['data'] ); // Unset product object
				}
			}

			return $cart_session;
		}

		/**
		 * Returns a specific item in the cart.
		 *
		 * @param string $item_key Cart item key.
		 * @return array Item data
		 */
		public function get_cart_item( $item_key ) {
			return isset( $this->cart_contents[ $item_key ] ) ? $this->cart_contents[ $item_key ] : array();
		}

		/**
		 * Returns the cart and shipping taxes, merged.
		 *
		 * @return array merged taxes
		 */
		public function get_taxes() {
			$taxes = array();

			foreach ( array_keys( $this->taxes + $this->shipping_taxes ) as $key ) {
				$taxes[ $key ] = ( isset( $this->shipping_taxes[ $key ] ) ? $this->shipping_taxes[ $key ] : 0 ) + ( isset( $this->taxes[ $key ] ) ? $this->taxes[ $key ] : 0 );
			}

			return apply_filters( 'woocommerce_cart_get_taxes', $taxes, $this );
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
		 * Get all tax classes for items in the cart.
		 * @return array
		 */
		public function get_cart_item_tax_classes() {
			$found_tax_classes = array();

			foreach ( WC()->cart->get_cart() as $item ) {
				$found_tax_classes[] = $item['data']->get_tax_class();
			}

			return array_unique( $found_tax_classes );
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
			return wc_format_decimal( 'incl' === $this->tax_display_cart ? $this->subtotal : $this->subtotal_ex_tax );
		}

	/*-----------------------------------------------------------------------------------*/
	/* Cart Calculation Functions */
	/*-----------------------------------------------------------------------------------*/

		/**
		 * Reset cart totals to the defaults. Useful before running calculations.
		 *
		 * @param  bool  	$unset_session If true, the session data will be forced unset.
		 * @access private
		 */
		private function reset( $unset_session = false ) {
			foreach ( $this->session_data as $key => $default ) {
				$this->$key = $default;
				if ( $unset_session ) {
					unset( WC()->session->$key );
				}
			}
			do_action( 'woocommerce_cart_reset', $this, $unset_session );
		}

		/**
		 * Calculate totals for the items in the cart.
		 */
		public function calculate_totals() {
			$this->reset();
			$this->coupons = $this->get_coupons();

			do_action( 'woocommerce_before_calculate_totals', $this );

			if ( $this->is_empty() ) {
				$this->set_session();
				return;
			}

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

			$this->set_session();
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

		/**
		 * Looks at the totals to see if payment is actually required.
		 *
		 * @return bool
		 */
		public function needs_payment() {
			return apply_filters( 'woocommerce_cart_needs_payment', $this->total > 0, $this );
		}

	/*-----------------------------------------------------------------------------------*/
	/* Get Formatted Totals */
	/*-----------------------------------------------------------------------------------*/

		/**
		 * Gets the order total (after calculation).
		 *
		 * @return string formatted price
		 */
		public function get_total() {
			return apply_filters( 'woocommerce_cart_total', wc_price( $this->total ) );
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
		 * Gets the sub total (after calculation).
		 *
		 * @param bool $compound whether to include compound taxes
		 * @return string formatted price
		 */
		public function get_cart_subtotal( $compound = false ) {

			// If the cart has compound tax, we want to show the subtotal as
			// cart + shipping + non-compound taxes (after discount)
			if ( $compound ) {

				$cart_subtotal = wc_price( $this->cart_contents_total + $this->shipping_total + $this->get_taxes_total( false, false ) );

			// Otherwise we show cart items totals only (before discount)
			} else {

				// Display varies depending on settings
				if ( $this->tax_display_cart == 'excl' ) {

					$cart_subtotal = wc_price( $this->subtotal_ex_tax );

					if ( $this->tax_total > 0 && $this->prices_include_tax ) {
						$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				} else {

					$cart_subtotal = wc_price( $this->subtotal );

					if ( $this->tax_total > 0 && ! $this->prices_include_tax ) {
						$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				}
			}

			return apply_filters( 'woocommerce_cart_subtotal', $cart_subtotal, $compound, $this );
		}

		/**
		 * Get the product row price per item.
		 *
		 * @param WC_Product $_product
		 * @return string formatted price
		 */
		public function get_product_price( $_product ) {
			if ( $this->tax_display_cart == 'excl' ) {
				$product_price = $_product->get_price_excluding_tax();
			} else {
				$product_price = $_product->get_price_including_tax();
			}

			return apply_filters( 'woocommerce_cart_product_price', wc_price( $product_price ), $_product );
		}

		/**
		 * Get the product row subtotal.
		 *
		 * Gets the tax etc to avoid rounding issues.
		 *
		 * When on the checkout (review order), this will get the subtotal based on the customer's tax rate rather than the base rate.
		 *
		 * @param WC_Product $_product
		 * @param int $quantity
		 * @return string formatted price
		 */
		public function get_product_subtotal( $_product, $quantity ) {
			$price 			= $_product->get_price();
			$taxable 		= $_product->is_taxable();

			if ( $taxable ) {
				if ( $this->tax_display_cart == 'excl' ) {

					$row_price        = $_product->get_price_excluding_tax( $quantity );
					$product_subtotal = wc_price( $row_price );

					if ( $this->prices_include_tax && $this->tax_total > 0 ) {
						$product_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				} else {

					$row_price        = $_product->get_price_including_tax( $quantity );
					$product_subtotal = wc_price( $row_price );

					if ( ! $this->prices_include_tax && $this->tax_total > 0 ) {
						$product_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				}
			} else {
				$row_price        = $price * $quantity;
				$product_subtotal = wc_price( $row_price );
			}

			return apply_filters( 'woocommerce_cart_product_subtotal', $product_subtotal, $_product, $quantity, $this );
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
		 * Gets the total discount amount - both kinds.
		 *
		 * @return mixed formatted price or false if there are none
		 */
		public function get_total_discount() {
			if ( $this->get_cart_discount_total() ) {
				$total_discount = wc_price( $this->get_cart_discount_total() );
			} else {
				$total_discount = false;
			}
			return apply_filters( 'woocommerce_cart_total_discount', $total_discount, $this );
		}
}
