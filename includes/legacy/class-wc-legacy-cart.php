'<?php
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
	 * Handle unset props.
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		_doing_it_wrong( $key, 'Cart properties should not be accessed directly.', '2.7' );

		switch ( $key ) {
			case 'subtotal' :
				return $this->totals->get_items_subtotal( true );
			case 'subtotal_ex_tax' :
				return $this->totals->get_items_subtotal( false );
			case 'taxes' :
				return $this->totals->get_tax_data();
			case 'cart_contents_total' :
				return $this->totals->get_items_total( false );
			case 'discount_cart' :
				return $this->totals->get_discount_total();
			case 'discount_cart_tax' :
				return $this->totals->get_discount_total_tax();
			case 'cart_contents' :
				return $this->get_cart();
			case 'removed_cart_contents' :
				return $this->items->get_removed_items();
			case 'tax_total' :
				return $this->totals->get_tax_total();
			case 'shipping_tax_total':
				return $this->totals->get_shipping_tax_total();
			case 'taxes' :
				return wc_list_pluck( $this->totals->get_taxes(), 'get_tax_total' );
			case 'shipping_taxes' :
				return wc_list_pluck( $this->totals->get_taxes(), 'get_shipping_tax_total' );
			case 'total' :
				return $this->totals->get_total();
			case 'coupon_discount_amounts' :
				return wp_list_pluck( $this->totals->get_coupons(), 'total' );
			case 'coupon_discount_tax_amounts' :
				return wp_list_pluck( $this->totals->get_coupons(), 'total_tax' );
			case 'applied_coupons' :
				return array_keys( $this->get_coupons() );
			case 'coupons' :
				return $this->get_coupons();
			case 'shipping_total' :
				return WC()->shipping->shipping_total;
			case 'shipping_taxes' :
				return WC()->shipping->shipping_taxes;
			case 'cart_session_data' :
				return array(
					'cart_contents_total'         => $this->cart_contents_total,
					'total'                       => $this->total,
					'subtotal'                    => $this->subtotal,
					'subtotal_ex_tax'             => $this->subtotal_ex_tax,
					'tax_total'                   => $this->tax_total,
					'taxes'                       => $this->taxes,
					'shipping_taxes'              => $this->shipping_taxes,
					'discount_cart'               => $this->discount_cart,
					'discount_cart_tax'           => $this->discount_cart_tax,
					'shipping_total'              => $this->shipping_total,
					'shipping_tax_total'          => $this->shipping_tax_total,
					'coupon_discount_amounts'     => $this->coupon_discount_amounts,
					'coupon_discount_tax_amounts' => $this->coupon_discount_tax_amounts,
					'fee_total'                   => $this->fee_total,
					'fees'                        => $this->fees,
				);
			case 'coupon_applied_count' :
				return wp_list_pluck( $this->totals->get_coupons(), 'count' );
			case 'fee_total' :
				return $this->totals->get_fees_total( false );
			case 'shipping_total' :
				return $this->totals->get_shipping_total( false );
			case 'shipping_tax_total' :
				return $this->totals->get_shipping_tax_total();
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
		}
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function add_discount( $coupon_code ) {
		_deprecated_function( 'WC_Cart::add_discount', '2.7', 'WC_Cart::add_coupon' );
		return $this->add_coupon( $coupon_code );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function has_discount( $coupon_code = '' ) {
		_deprecated_function( 'WC_Cart::has_discount', '2.7', 'WC_Cart::has_coupon' );
		return $this->has_coupon( $coupon_code );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function get_applied_coupons() {
		_deprecated_function( 'WC_Cart::get_applied_coupons', '2.7', 'WC_Cart::get_coupons' );
		return array_keys( $this->get_coupons() );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function check_cart_coupons() {
		_deprecated_function( 'WC_Cart::check_cart_coupons', '2.7', 'WC_Cart_Coupons::check_coupons' );
		$this->coupons->check_coupons();
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function check_customer_coupons( $posted ) {
		_deprecated_function( 'WC_Cart::check_customer_coupons', '2.7', 'WC_Cart_Coupons::check_customer_restriction/check_customer_limits' );
		$this->coupons->check_customer_restriction( $posted );
		$this->coupons->check_customer_limits( $posted );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function show_shipping() {
		_deprecated_function( 'WC_Cart::show_shipping', '2.7', 'wc_cart_show_shipping' );
		return wc_cart_show_shipping();
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function calculate_fees() {
		_deprecated_function( 'WC_Cart::calculate_fees', '2.7', 'WC_Cart_Fees::calculate_fees' );
		$this->fees->calculate_fees();
	}

	/**
	 * Add a product to the cart.
	 * @deprecated 2.7.0
	 * @param int $product_id contains the id of the product to add to the cart
	 * @param int $quantity contains the quantity of the item to add
	 * @param int $variation_id
	 * @param array $variation attribute values
	 * @param array $cart_item_data extra cart item data we want to pass into the item
	 * @return string|bool $cart_item_key
	 */
	public function add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		_deprecated_function( 'WC_Cart::add_to_cart', '2.7', 'wc_add_to_cart' );

		// Map legacy args to new.
		if ( $variation_id ) {
			$product_id = $variation_id;
		}

		if ( $variation ) {
			$cart_item_data['variation'] = $variation;
		}

		return wc_add_to_cart( $product_id, $quantity, $cart_item_data );
	}

	/**
	 * Get cart items quantities - merged so we can do accurate stock checks on items across multiple lines.
	 * @return array
	 */
	public function get_cart_item_quantities() {
		_deprecated_function( 'WC_Cart::get_cart_item_quantities', '2.7', 'wc_cart_item_quantities' );
		return wc_cart_item_quantities();
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function check_cart_items() {
		_deprecated_function( 'WC_Cart::check_cart_items', '2.7', 'WC_Cart_Items::check_items' );
		$this->items->check_items();
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function check_cart_item_stock() {
		_doing_it_wrong( 'check_cart_item_stock', 'Should not be called directly.', '2.7' );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function persistent_cart_update() {
		_doing_it_wrong( 'persistent_cart_destroy', 'Should not be called directly.', '2.7' );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function persistent_cart_destroy() {
		_doing_it_wrong( 'persistent_cart_destroy', 'Should not be called directly.', '2.7' );
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
	 * @deprecated 2.7.0
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
		return wc_cart_product_price_html( $product );
	}

	/**
	 * Gets the sub total (after calculation).
	 * @deprecated 2.7.0
	 * @param bool $compound whether to include compound taxes
	 * @return string formatted price
	 */
	public function get_cart_subtotal( $compound = false ) {
		_deprecated_function( 'get_cart_subtotal', '2.7', 'wc_cart_subtotal_html' );
		return wc_cart_subtotal_html( false, false );
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
		return wc_cart_product_price_html( $product, $quantity );
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

	/**
	 * @deprecated 2.7.0
	 */
	public function get_cross_sells() {
		_deprecated_function( 'WC_Cart::get_cross_sells', '2.7', 'wc_get_cart_cross_sells' );
		wc_get_cart_cross_sells();
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function get_remove_url( $cart_item_key ) {
		_deprecated_function( 'WC_Cart::get_remove_url', '2.7', 'wc_get_cart_remove_url' );
		return wc_get_cart_remove_url( $cart_item_key );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function get_undo_url( $cart_item_key ) {
		_deprecated_function( 'WC_Cart::get_undo_url', '2.7', 'wc_get_cart_undo_url' );
		return wc_get_cart_undo_url( $cart_item_key );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function get_discounted_price( $values, $price, $add_totals = false ) {
		_deprecated_function( 'WC_Cart::get_undo_url', '2.7' );
		return $price;
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function get_item_data( $cart_item, $flat = false ) {
		_deprecated_function( 'WC_Cart::get_item_data', '2.7', 'wc_display_item_data' );
		return wc_display_item_data( $cart_item, $flat = false  );
	}

	/**
	 * @deprecated 2.7.0 Unused method.
	 */
	public function get_total_ex_tax() {
		_deprecated_function( 'WC_Cart::get_total_ex_tax', '2.7' );
		return apply_filters( 'woocommerce_cart_total_ex_tax', wc_price( min( 0, $this->total - $this->tax_total - $this->shipping_tax_total ) ) );
	}

	/**
	 * @deprecated 2.7.0 Unused method.
	 */
	public function init() {
		_deprecated_function( 'WC_Cart::init', '2.7' );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function get_cart_discount_total() {
		_deprecated_function( 'WC_Cart::get_cart_discount_total', '2.7', 'WC_Cart_Totals::get_discount_total' );
		return $this->totals->get_discount_total();
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function get_cart_discount_tax_total() {
		_deprecated_function( 'WC_Cart::get_cart_discount_tax_total', '2.7', 'WC_Cart_Totals::get_discount_total_tax' );
		return $this->totals->get_discount_total_tax();
	}

	/**
	 * @deprecated 2.7.0 Taxes are just not calculated when not needed, so no need to remove them.
	 */
	public function remove_taxes() {
		_deprecated_function( 'WC_Cart::remove_taxes', '2.7' );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function find_product_in_cart( $cart_id = false ) {
		_deprecated_function( 'WC_Cart::find_product_in_cart', '2.7', 'WC_Cart::get_item_by_key' );
		if ( $cart_id !== false ) {
			if ( is_array( $this->cart_contents ) && isset( $this->cart_contents[ $cart_id ] ) ) {
				return $cart_id;
			}
		}
		return '';
	}

	/**
	 * Generate a unique ID for the cart item being added.
	 * @deprecated 2.7.0
	 * @param int $product_id - id of the product the key is being generated for
	 * @param int $variation_id of the product the key is being generated for
	 * @param array $variation data for the cart item
	 * @param array $cart_item_data other cart item data passed which affects this items uniqueness in the cart
	 * @return string cart item key
	 */
	public function generate_cart_id( $product_id, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		_deprecated_function( 'WC_Cart::generate_cart_id', '2.7', 'WC_Cart_Items::generate_key' );
		return apply_filters( 'woocommerce_cart_id', md5( json_encode( array( $product_id, $variation_id, $variation, $cart_item_data ) ) ), $product_id, $variation_id, $variation, $cart_item_data );
	}

	/**
	 * @deprecated 2.7.0
	 */
	public function check_cart_item_validity() {
		_deprecated_function( 'WC_Cart::check_cart_item_validity', '2.7', 'WC_Cart_Items::check_items' );
		$this->items->check_items();
	}

	/**
	 * @deprecated 2.7.0 Unused
	 */
	public function get_cart_shipping_total() {
		_deprecated_function( 'WC_Cart::get_cart_shipping_total', '2.7' );
		if ( isset( $this->shipping_total ) ) {
			if ( $this->shipping_total > 0 ) {

				// Display varies depending on settings
				if ( $this->tax_display_cart == 'excl' ) {

					$return = wc_price( $this->shipping_total );

					if ( $this->shipping_tax_total > 0 && wc_prices_include_tax() ) {
						$return .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}

					return $return;

				} else {

					$return = wc_price( $this->shipping_total + $this->shipping_tax_total );

					if ( $this->shipping_tax_total > 0 && ! wc_prices_include_tax() ) {
						$return .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}

					return $return;

				}
			} else {
				return __( 'Free!', 'woocommerce' );
			}
		}
		return '';
	}

	/**
	 * @deprecated 2.7.0 Unused
	 */
	public function get_tax_amount( $tax_rate_id ) {
		_deprecated_function( 'WC_Cart::get_tax_amount', '2.7', 'Obtain from WC_Cart::get_taxes' );
		$taxes = $this->totals->get_taxes();
		return isset( $taxes[ $tax_rate_id ] ) ? $taxes[ $tax_rate_id ]->get_tax_total() : 0;
	}

	/**
	 * @deprecated 2.7.0 Unused
	 */
	public function get_shipping_tax_amount( $tax_rate_id ) {
		_deprecated_function( 'WC_Cart::get_shipping_tax_amount', '2.7', 'Obtain from WC_Cart::get_taxes' );
		$taxes = $this->totals->get_taxes();
		return isset( $taxes[ $tax_rate_id ] ) ? $taxes[ $tax_rate_id ]->get_shipping_tax_total() : 0;
	}

	/**
	 * @deprecated 2.7.0 Unused
	 */
	public function get_cart_total() {
		_deprecated_function( 'WC_Cart::get_cart_total', '2.7' );
		if ( ! wc_prices_include_tax() ) {
			$cart_contents_total = wc_price( $this->cart_contents_total );
		} else {
			$cart_contents_total = wc_price( $this->cart_contents_total + $this->tax_total );
		}
		return apply_filters( 'woocommerce_cart_contents_total', $cart_contents_total );
	}

	/**
	 * @deprecated 2.7.0 Unused
	 */
	public function get_cart_tax() {
		_deprecated_function( 'WC_Cart::get_cart_tax', '2.7' );
		$cart_total_tax = wc_round_tax_total( $this->tax_total + $this->shipping_tax_total );
		return apply_filters( 'woocommerce_get_cart_tax', $cart_total_tax ? wc_price( $cart_total_tax ) : '' );
	}
}
