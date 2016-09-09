<?php
/**
 * WooCommerce Cart Functions
 *
 * Functions for cart specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Prevent password protected products being added to the cart.
 *
 * @param  bool $passed
 * @param  int $product_id
 * @return bool
 */
function wc_protected_product_add_to_cart( $passed, $product_id ) {
	if ( post_password_required( $product_id ) ) {
		$passed = false;
		wc_add_notice( __( 'This product is protected and cannot be purchased.', 'woocommerce' ), 'error' );
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'wc_protected_product_add_to_cart', 10, 2 );

/**
 * Add a product to the cart.
 *
 * Validates the product can be added, then adds it if valid.
 *
 * @since 2.7.0
 * @param int $product_or_variation_id contains the id of the product to add to the cart.
 * @param int $quantity contains the quantity of the item to add
 * @param array $data extra cart item data we want to pass into the item, including any data about the variation if applicable.
 * @return string|bool $cart_item_key that was added or false if validation failed.
 */
function wc_add_to_cart( $product_or_variation_id = 0, $quantity = 1, $data = array() ) {
	try {
		$product = wc_get_product( $product_or_variation_id );

		if ( empty( $product ) || ! $product->is_purchasable() ) {
			throw new Exception( __( 'Sorry, this product cannot be purchased.', 'woocommerce' ) );
		}

		$quantity = max( 0, apply_filters( 'woocommerce_add_to_cart_quantity', $quantity, $product, $data ) );
		$data     = (array) apply_filters( 'woocommerce_add_to_cart_data', $data, $product );

		wc_add_to_cart_validate_stock( $product, $quantity );

		$cart_item_key = WC()->cart->add_item( array(
			'product'  => $product,
			'quantity' => $quantity,
			'data'     => $data,
		) );

		do_action(
			'woocommerce_add_to_cart',
			$cart_item_key,
			$product->get_id(),
			$quantity,
			is_callable( array( $product, 'get_variation_id' ) ) ? $product->get_variation_id() : 0,
			! empty( $data['variation'] ) ? $data['variation'] : array(),
			$data
		);

		return $cart_item_key;
	} catch ( Exception $e ) {
		wc_add_notice( $e->getMessage(), 'error' );
		return false;
	}
}

/**
 * Check product has enough stock to be added to cart.
 * @since 2.7.0
 * @param WC_Product $product
 * @param int $adding_quantity
 * @throws Exception
 */
function wc_add_to_cart_validate_stock( $product, $adding_quantity = 0 ) {
	$products_qty_in_cart  = wc_cart_item_quantities();
	$managing_stock        = $product->managing_stock();
	$check_variation_stock = $product->is_type( 'variation' ) && true === $managing_stock;
	$check_id              = $check_variation_stock ? $product->variation_id : $product->id;
	$in_cart_qty           = isset( $products_qty_in_cart[ $check_id ] ) ? $products_qty_in_cart[ $check_id ] : 0;

	if ( ! $product->is_in_stock() ) {
		throw new Exception( sprintf( __( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ), $product->get_title() ) );
	}

	if ( $product->is_sold_individually() && $in_cart_qty && $adding_quantity ) {
		throw new Exception( sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', wc_get_cart_url(), __( 'View Cart', 'woocommerce' ), sprintf( __( 'You cannot add another &quot;%s&quot; to your cart.', 'woocommerce' ), $product->get_title() ) ) );
	}

	if ( ! $product->has_enough_stock( $adding_quantity ) ) {
		throw new Exception( sprintf(__( 'You cannot add that amount of &quot;%s&quot; to the cart because there is not enough stock (%s remaining).', 'woocommerce' ), $product->get_title(), $product->get_stock_quantity() ) );
	}

	/**
	 * Check stock based on all items in the cart.
	 */
	if ( ! $product->has_enough_stock( $in_cart_qty + $adding_quantity ) ) {
		throw new Exception( '<a href="' . esc_url( wc_get_cart_url() ) . '" class="button wc-forward">' . __( 'View Cart', 'woocommerce' ) . '</a> ' . sprintf( __( 'You cannot add that amount to the cart &mdash; we have %s in stock and you already have %s in your cart.', 'woocommerce' ), $product->get_stock_quantity(), $check_qty ) );
	}

	/**
	 * Finally consider any held stock, from pending orders.
	 */
	if ( ! $product->has_enough_stock( $in_cart_qty + $adding_quantity + wc_get_held_stock_count( $product ) ) ) {
		throw new Exception( '<a href="' . esc_url( wc_get_cart_url() ) . '" class="button wc-forward">' . __( 'View Cart', 'woocommerce' ) . '</a> ' . sprintf(__( 'Sorry, we do not have enough "%s" in stock to fulfill your order right now. Please try again in %d minutes or edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce' ), $product->get_title(), get_option( 'woocommerce_hold_stock_minutes' ) ) );
	}
}

/**
 * Get cart items quantities - merged so we can do accurate stock checks on items across multiple lines.
 * @since 2.7.0
 * @return array
 */
function wc_cart_item_quantities() {
	$quantities = array();

	foreach ( WC()->cart->get_cart() as $cart_item_key => $item ) {
		$id                = $item->get_product_id();
		$quantities[ $id ] = isset( $quantities[ $id ] ) ? $quantities[ $id ] + $item->get_quantity() : $item->get_quantity();
	}

	return $quantities;
}

/**
 * Clears the cart session when called.
 */
function wc_empty_cart() {
	if ( ! isset( WC()->cart ) || '' === WC()->cart ) {
		WC()->cart = new WC_Cart();
	}
	WC()->cart->empty_cart();
}

/**
 * Retrieves unvalidated referer from '_wp_http_referer' or HTTP referer.
 *
 * Do not use for redirects, use {@see wp_get_referer()} instead.
 *
 * @since 2.6.1
 * @return string|false Referer URL on success, false on failure.
 */
function wc_get_raw_referer() {
	if ( function_exists( 'wp_get_raw_referer' ) ) {
		return wp_get_raw_referer();
	}

	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		return wp_unslash( $_REQUEST['_wp_http_referer'] );
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		return wp_unslash( $_SERVER['HTTP_REFERER'] );
	}

	return false;
}

/**
 * Add to cart messages.
 *
 * @access public
 * @param int|array $products
 * @param bool $show_qty Should qty's be shown? Added in 2.6.0
 * @param bool $return Return message rather than add it.
 */
function wc_add_to_cart_message( $products, $show_qty = false, $return = false ) {
	$titles = array();
	$count  = 0;

	if ( ! is_array( $products ) ) {
		$products = array( $products => 1 );
		$show_qty = false;
	}

	if ( ! $show_qty ) {
		$products = array_fill_keys( array_keys( $products ), 1 );
	}

	foreach ( $products as $product_id => $qty ) {
		$titles[] = ( $qty > 1 ? absint( $qty ) . ' &times; ' : '' ) . sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), strip_tags( get_the_title( $product_id ) ) );
		$count += $qty;
	}

	$titles     = array_filter( $titles );
	$added_text = sprintf( _n( '%s has been added to your cart.', '%s have been added to your cart.', $count, 'woocommerce' ), wc_format_list_of_items( $titles ) );

	// Output success messages
	if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
		$return_to = apply_filters( 'woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect( wc_get_raw_referer(), false ) : wc_get_page_permalink( 'shop' ) );
		$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( $return_to ), esc_html__( 'Continue Shopping', 'woocommerce' ), esc_html( $added_text ) );
	} else {
		$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'View Cart', 'woocommerce' ), esc_html( $added_text ) );
	}

	$message = apply_filters( 'wc_add_to_cart_message', $message, $product_id );

	if ( $return ) {
		return $message;
	} else {
		wc_add_notice( $message );
	}
}

/**
 * Comma separate a list of item names, and replace final comma with 'and'
 * @param  array $items
 * @return string
 */
function wc_format_list_of_items( $items ) {
	$item_string = '';

	foreach ( $items as $key => $item ) {
		$item_string .= $item;

		if ( $key + 2 === sizeof( $items ) ) {
			$item_string .= ' ' . __( 'and', 'woocommerce' ) . ' ';
		} elseif ( $key + 1 !== sizeof( $items ) ) {
			$item_string .= ', ';
		}
	}

	return $item_string;
}

/**
 * Clear cart after payment.
 *
 * @access public
 */
function wc_clear_cart_after_payment() {
	global $wp;

	if ( ! empty( $wp->query_vars['order-received'] ) ) {

		$order_id  = absint( $wp->query_vars['order-received'] );
		$order_key = isset( $_GET['key'] ) ? wc_clean( $_GET['key'] ) : '';

		if ( $order_id > 0 ) {
			$order = wc_get_order( $order_id );

			if ( $order->get_order_key() === $order_key ) {
				WC()->cart->empty_cart();
			}
		}
	}

	if ( WC()->session->order_awaiting_payment > 0 ) {
		$order = wc_get_order( WC()->session->order_awaiting_payment );

		if ( $order && $order->get_id() > 0 ) {
			// If the order has not failed, or is not pending, the order must have gone through
			if ( ! $order->has_status( array( 'failed', 'pending', 'cancelled' ) ) ) {
				WC()->cart->empty_cart();
			}
		}
	}
}
add_action( 'get_header', 'wc_clear_cart_after_payment' );

/**
 * Get shipping methods.
 *
 * @access public
 */
function wc_cart_totals_shipping_html() {
	$packages = WC()->shipping->get_packages();

	foreach ( $packages as $key => $package ) {
		$chosen_method = wc_get_chosen_shipping_method_for_package( $key, $package );
		$product_names = array();
		$package_name  = apply_filters( 'woocommerce_shipping_package_name', sprintf( _n( 'Shipping', 'Shipping %d', ( $key + 1 ), 'woocommerce' ), ( $key + 1 ) ), $key, $package );

		if ( sizeof( $packages ) > 1 ) {
			foreach ( $package['contents'] as $item_id => $values ) {
				$product_names[] = $values['data']->get_title() . ' &times;' . $values['quantity'];
			}
		}

		if ( 'yes' === get_option( 'woocommerce_shipping_debug_mode', 'no' ) && WC()->shipping->get_shipping_zone() ) {
			$package_name .= ' - ' . WC()->shipping->get_shipping_zone()->get_zone_name();
		}

		wc_get_template( 'cart/cart-shipping.php', array(
			'package'              => $package,
			'available_methods'    => $package['rates'],
			'show_package_details' => sizeof( $packages ) > 1,
			'package_details'      => implode( ', ', $product_names ),
			'package_name'         => $package_name,
			'index'                => $key,
			'chosen_method'        => $chosen_method,
		) );
	}
}

/**
 * Get taxes total. Used when not showing itemized taxes.
 */
function wc_cart_totals_taxes_total_html() {
	echo apply_filters( 'woocommerce_cart_totals_taxes_total_html', wc_price( WC()->cart->get_taxes_total() ) );
}

/**
 * Get a coupon label.
 *
 * @access public
 * @param string $coupon
 * @param bool $echo or return
 */
function wc_cart_totals_coupon_label( $coupon, $echo = true ) {
	if ( is_string( $coupon ) ) {
		$coupon = new WC_Coupon( $coupon );
	}

	$label = apply_filters( 'woocommerce_cart_totals_coupon_label', esc_html( __( 'Coupon:', 'woocommerce' ) . ' ' . $coupon->get_code() ), $coupon );

	if ( $echo ) {
		echo $label;
	} else {
		return $label;
	}
}

/**
 * Get a coupon value.
 *
 * @access public
 * @param string $coupon
 */
function wc_cart_totals_coupon_html( $coupon ) {
	if ( is_string( $coupon ) ) {
		$coupon = new WC_Coupon( $coupon );
	}

	$value  = array();

	if ( $amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax ) ) {
		$discount_html = '-' . wc_price( $amount );
	} else {
		$discount_html = '';
	}

	$value[] = apply_filters( 'woocommerce_coupon_discount_amount_html', $discount_html, $coupon );

	if ( $coupon->get_free_shipping() ) {
		$value[] = __( 'Free shipping coupon', 'woocommerce' );
	}

	// get rid of empty array elements
	$value = array_filter( $value );
	$value = implode( ', ', $value ) . ' <a href="' . esc_url( add_query_arg( 'remove_coupon', urlencode( $coupon->get_code() ), defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ) . '" class="woocommerce-remove-coupon" data-coupon="' . esc_attr( $coupon->get_code() ) . '">' . __( '[Remove]', 'woocommerce' ) . '</a>';

	echo apply_filters( 'woocommerce_cart_totals_coupon_html', $value, $coupon );
}

/**
 * Get order total html including inc tax if needed.
 */
function wc_cart_totals_order_total_html() {
	$value = '<strong>' . WC()->cart->get_total() . '</strong> ';

	// If prices are tax inclusive, show taxes here
	if ( wc_tax_enabled() && WC()->cart->tax_display_cart == 'incl' ) {
		$tax_string_array = array();

		if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {
			foreach ( WC()->cart->get_tax_totals() as $code => $tax )
				$tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
		} else {
			$tax_string_array[] = sprintf( '%s %s', wc_price( WC()->cart->get_taxes_total( true, true ) ), WC()->countries->tax_or_vat() );
		}

		if ( ! empty( $tax_string_array ) ) {
			$taxable_address = WC()->customer->get_taxable_address();
			$estimated_text  = WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()
				? sprintf( ' ' . __( 'estimated for %s', 'woocommerce' ), WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] )
				: '';
			$value .= '<small class="includes_tax">' . sprintf( __( '(includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) . $estimated_text ) . '</small>';
		}
	}

	echo apply_filters( 'woocommerce_cart_totals_order_total_html', $value );
}

/**
 * Get the fee value.
 *
 * @param object $fee
 */
function wc_cart_totals_fee_html( $fee ) {
	$cart_totals_fee_html = ( 'excl' == WC()->cart->tax_display_cart ) ? wc_price( $fee->amount ) : wc_price( $fee->amount + $fee->tax );

	echo apply_filters( 'woocommerce_cart_totals_fee_html', $cart_totals_fee_html, $fee );
}

/**
 * Get a shipping methods full label including price.
 * @param  WC_Shipping_Rate $method
 * @return string
 */
function wc_cart_totals_shipping_method_label( $method ) {
	$label = $method->get_label();

	if ( $method->cost > 0 ) {
		if ( WC()->cart->tax_display_cart == 'excl' ) {
			$label .= ': ' . wc_price( $method->cost );
			if ( $method->get_shipping_tax() > 0 && WC()->cart->prices_include_tax ) {
				$label .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}
		} else {
			$label .= ': ' . wc_price( $method->cost + $method->get_shipping_tax() );
			if ( $method->get_shipping_tax() > 0 && ! WC()->cart->prices_include_tax ) {
				$label .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
			}
		}
	}

	return apply_filters( 'woocommerce_cart_shipping_method_full_label', $label, $method );
}

/**
 * Round discount.
 *
 * @param  float $value
 * @param  int $precision
 * @return float
 */
function wc_cart_round_discount( $value, $precision ) {
	if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
		return round( $value, $precision, WC_DISCOUNT_ROUNDING_MODE );
	} else {
		return round( $value, $precision );
	}
}

/**
 * Gets chosen shipping method IDs from chosen_shipping_methods session, without instance IDs.
 * @since  2.6.2
 * @return string[]
 */
function wc_get_chosen_shipping_method_ids() {
	$method_ids     = array();
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );
	foreach ( $chosen_methods as $chosen_method ) {
		$chosen_method = explode( ':', $chosen_method );
		$method_ids[]  = current( $chosen_method );
	}
	return $method_ids;
}

/**
 * Get cart cross sells.
 * @since 2.7.0
 * @return array
 */
function wc_get_cart_cross_sells() {
	$cross_sells = array();
	$in_cart     = array();
	if ( ! WC()->cart->is_empty() ) {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			if ( $values['quantity'] > 0 ) {
				$cross_sells = array_merge( $values['data']->get_cross_sells(), $cross_sells );
				$in_cart[] = $values['product_id'];
			}
		}
	}
	$cross_sells = array_diff( $cross_sells, $in_cart );
	return $cross_sells;
}

/**
 * See if we're displaying tax in the cart in a certain way.
 * @since 2.7.0
 * @return boolean
 */
function wc_cart_prices_include_tax() {
	return 'incl' === get_option( 'woocommerce_tax_display_cart', 'excl' );
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
 * @since 2.7.0
 * @return string
 */
function wc_cart_subtotal_to_display() {
	return wc_format_decimal( wc_cart_prices_include_tax() ? WC()->cart->subtotal : WC()->cart->subtotal_ex_tax );
}

/**
 * Gets the sub total (after calculation).
 *
 * @param bool $compound whether to include compound taxes
 * @return string formatted price
 */
function wc_cart_subtotal_html( $compound = false, $echo = true ) {
	// If the cart has compound tax, we want to show the subtotal as
	// cart + shipping + non-compound taxes (after discount)
	if ( $compound ) {
		$cart_subtotal = WC()->cart->cart_contents_total + WC()->cart->shipping_total + WC()->cart->get_taxes_total( false, false );
		$suffix = '';

	// Otherwise we show cart items totals only (before discount)
	} else {
		$cart_subtotal = wc_cart_subtotal_to_display();

		if ( wc_cart_prices_include_tax() ) {
			$suffix = wc_cart_prices_include_tax() ? '' : '<small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
		} else {
			$suffix = wc_cart_prices_include_tax() ? '<small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>' : '';
		}
	}

	$html = apply_filters( 'woocommerce_cart_subtotal', wc_price( $cart_subtotal ) . ' ' . $suffix, $compound, WC()->cart );

	if ( $echo ) {
		echo $html;
	} else {
		return $html;
	}
}

/**
 * Get the product row price per item.
 * @since 2.7.0
 * @param WC_Product $product
 * @param int $quantity to show
 * @return string formatted price
 */
function wc_cart_product_price_html( $product, $quantity = 1 ) {
	if ( wc_cart_prices_include_tax() ) {
		$price  = $product->get_price_including_tax( $quantity );
		$suffix = wc_cart_prices_include_tax() ? '' : '<small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
	} else {
		$price = $product->get_price_excluding_tax( $quantity );
		$suffix = wc_cart_prices_include_tax() ? '<small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>' : '';
	}
	return apply_filters( 'woocommerce_cart_product_price_html', wc_price( $product->is_taxable() ? $price . ' ' . $suffix : $price ), $product, $quantity );
}

/**
 * Should we show shipping in the cart?
 * @since 2.7.0
 * @return bool
 */
function wc_cart_show_shipping() {
	if ( ! wc_shipping_enabled() || WC()->cart->is_empty() ) {
		return false;
	}

	if ( 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ) && ! WC()->customer->has_calculated_shipping() && ! WC()->customer->has_shipping_address() ) {
		return false;
	}

	return apply_filters( 'woocommerce_cart_ready_to_calc_shipping', true );
}

/**
 * Load the persistent cart.
 */
function wc_load_persistent_cart() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$cart = WC()->session->get( 'cart', null );

	if ( is_null( $cart ) && ( $saved_cart = get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', true ) ) ) {
		$cart          = array();
		$cart['items'] = $saved_cart;
		WC()->session->set( 'cart', $cart );
	}
}
add_action( 'wp_loaded', 'wc_load_persistent_cart', 5 );

/**
 * Update the persistent cart.
 */
function wc_update_persistent_cart() {
	update_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', WC()->cart->get_cart_for_session() );
}
add_action( 'woocommerce_cart_updated', 'wc_update_persistent_cart' );

/**
 * Delete the persistent cart.
 */
function wc_delete_persistent_cart() {
	delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart' );
}
add_action( 'woocommerce_cart_emptied', 'wc_delete_persistent_cart' );

/**
 * Get chosen method for package from session.
 * @param  int $key
 * @param  array $package
 * @return string|bool
 */
function wc_get_chosen_shipping_method_for_package( $key, $package ) {
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	$chosen_method  = isset( $chosen_methods[ $key ] ) ? $chosen_methods[ $key ] : false;
	$changed        = wc_shipping_methods_have_changed( $key, $package );

	// If not set, not available, or available methods have changed, set to the DEFAULT option
	if ( ! $chosen_method || $changed || ! isset( $package['rates'][ $chosen_method ] ) ) {
		$chosen_method          = wc_get_default_shipping_method_for_package( $key, $package, $chosen_method );
		$chosen_methods[ $key ] = $chosen_method;
		WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
		do_action( 'woocommerce_shipping_method_chosen', $chosen_method );
	}

	return $chosen_method;
}

/**
 * Choose the default method for a package.
 * @param  string $key
 * @param  array $package
 * @return string
 */
function wc_get_default_shipping_method_for_package( $key, $package, $chosen_method ) {
	$rate_keys = array_keys( $package['rates'] );
	$default   = current( $rate_keys );
	$coupons   = WC()->cart->get_coupons();

	foreach ( $coupons as $coupon ) {
		if ( $coupon->get_free_shipping() ) {
			foreach ( $rate_keys as $rate_key ) {
				if ( 0 === stripos( $rate_key, 'free_shipping' ) ) {
					$default = $rate_key;
					break;
				}
			}
			break;
		}
	}

	return apply_filters( 'woocommerce_shipping_chosen_method', $default, $package['rates'], $chosen_method );
}

/**
 * See if the methods have changed since the last request.
 * @param  int $key
 * @param  array $package
 * @return bool
 */
function wc_shipping_methods_have_changed( $key, $package ) {
	// Lookup previous methods from session.
	$previous_shipping_methods = WC()->session->get( 'previous_shipping_methods' );

	// Get new and old rates.
	$new_rates  = array_keys( $package['rates'] );
	$prev_rates = isset( $previous_shipping_methods[ $key ] ) ? $previous_shipping_methods[ $key ] : false;

	// Update session.
	$previous_shipping_methods[ $key ] = $new_rates;
	WC()->session->set( 'previous_shipping_methods', $previous_shipping_methods );

	return $new_rates != $prev_rates;
}
