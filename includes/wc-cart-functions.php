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
 * Clears the cart session when called.
 */
function wc_empty_cart() {
	if ( ! isset( WC()->cart ) || '' === WC()->cart ) {
		WC()->cart = new WC_Cart();
	}
	WC()->cart->empty_cart( false );
}

/**
 * Load the persistent cart.
 *
 * @param string $user_login
 * @param WP_User $user
 * @deprecated 2.3
 */
function wc_load_persistent_cart( $user_login, $user ) {
	if ( ! $user || ! ( $saved_cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true ) ) ) {
		return;
	}

	if ( empty( WC()->session->cart ) || ! is_array( WC()->session->cart ) || 0 === sizeof( WC()->session->cart ) ) {
		WC()->session->cart = $saved_cart['cart'];
	}
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
 * @param int|array $products
 * @param bool $show_qty Should qty's be shown? Added in 2.6.0
 * @param bool $return Return message rather than add it.
 *
 * @return mixed|void
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
		$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( $return_to ), esc_html__( 'Continue shopping', 'woocommerce' ), esc_html( $added_text ) );
	} else {
		$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'View cart', 'woocommerce' ), esc_html( $added_text ) );
	}

	if ( has_filter( 'wc_add_to_cart_message' ) ) {
		wc_deprecated_function( 'The wc_add_to_cart_message filter', '3.0', 'wc_add_to_cart_message_html' );
		$message = apply_filters( 'wc_add_to_cart_message', $message, $product_id );
	}

	$message = apply_filters( 'wc_add_to_cart_message_html', $message, $products );

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

		if ( sizeof( $items ) === $key + 2 ) {
			$item_string .= ' ' . __( 'and', 'woocommerce' ) . ' ';
		} elseif ( sizeof( $items ) !== $key + 1 ) {
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

			if ( $order && $order->get_order_key() === $order_key ) {
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
 * Get the subtotal.
 *
 * @access public
 */
function wc_cart_totals_subtotal_html() {
	echo WC()->cart->get_cart_subtotal();
}

/**
 * Get shipping methods.
 *
 * @access public
 */
function wc_cart_totals_shipping_html() {
	$packages = WC()->shipping->get_packages();
	$first    = true;

	foreach ( $packages as $i => $package ) {
		$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
		$product_names = array();

		if ( sizeof( $packages ) > 1 ) {
			foreach ( $package['contents'] as $item_id => $values ) {
				$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
			}
			$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
		}

		wc_get_template( 'cart/cart-shipping.php', array(
			'package'                  => $package,
			'available_methods'        => $package['rates'],
			'show_package_details'     => sizeof( $packages ) > 1,
			'show_shipping_calculator' => is_cart() && $first,
			'package_details'          => implode( ', ', $product_names ),
			// @codingStandardsIgnoreStart
			'package_name'             => apply_filters( 'woocommerce_shipping_package_name', sprintf( _nx( 'Shipping', 'Shipping %d', ( $i + 1 ), 'shipping packages', 'woocommerce' ), ( $i + 1 ) ), $i, $package ),
			// @codingStandardsIgnoreEnd
			'index'                    => $i,
			'chosen_method'            => $chosen_method,
		) );

		$first = false;
	}
}

/**
 * Get taxes total.
 *
 * @access public
 */
function wc_cart_totals_taxes_total_html() {
	echo apply_filters( 'woocommerce_cart_totals_taxes_total_html', wc_price( WC()->cart->get_taxes_total() ) );
}

/**
 * Get a coupon label.
 *
 * @access public
 *
 * @param string $coupon
 * @param bool $echo or return
 *
 * @return string
 */
function wc_cart_totals_coupon_label( $coupon, $echo = true ) {
	if ( is_string( $coupon ) ) {
		$coupon = new WC_Coupon( $coupon );
	}

	$label = apply_filters( 'woocommerce_cart_totals_coupon_label', sprintf( esc_html__( 'Coupon: %s', 'woocommerce' ), $coupon->get_code() ), $coupon );

	if ( $echo ) {
		echo $label;
	} else {
		return $label;
	}
}

/**
 * Get coupon display HTML.
 *
 * @param string $coupon
 */
function wc_cart_totals_coupon_html( $coupon ) {
	if ( is_string( $coupon ) ) {
		$coupon = new WC_Coupon( $coupon );
	}

	$discount_amount_html = '';

	if ( $amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax ) ) {
		$discount_amount_html = '-' . wc_price( $amount );
	} elseif ( $coupon->get_free_shipping() ) {
		$discount_amount_html = __( 'Free shipping coupon', 'woocommerce' );
	}

	$discount_amount_html = apply_filters( 'woocommerce_coupon_discount_amount_html', $discount_amount_html, $coupon );
	$coupon_html          = $discount_amount_html . ' <a href="' . esc_url( add_query_arg( 'remove_coupon', urlencode( $coupon->get_code() ), defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ) . '" class="woocommerce-remove-coupon" data-coupon="' . esc_attr( $coupon->get_code() ) . '">' . __( '[Remove]', 'woocommerce' ) . '</a>';

	echo wp_kses( apply_filters( 'woocommerce_cart_totals_coupon_html', $coupon_html, $coupon, $discount_amount_html ), array_replace_recursive( wp_kses_allowed_html( 'post' ), array( 'a' => array( 'data-coupon' => true ) ) ) );
}

/**
 * Get order total html including inc tax if needed.
 *
 * @access public
 */
function wc_cart_totals_order_total_html() {
	$value = '<strong>' . WC()->cart->get_total() . '</strong> ';

	// If prices are tax inclusive, show taxes here
	if ( wc_tax_enabled() && WC()->cart->tax_display_cart == 'incl' ) {
		$tax_string_array = array();

		if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {
			foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
				$tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
			}
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
	$cart_totals_fee_html = ( 'excl' == WC()->cart->tax_display_cart ) ? wc_price( $fee->total ) : wc_price( $fee->total + $fee->tax );

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
			if ( $method->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
				$label .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}
		} else {
			$label .= ': ' . wc_price( $method->cost + $method->get_shipping_tax() );
			if ( $method->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
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
		// Fake it in PHP 5.2.
		if ( 2 === WC_DISCOUNT_ROUNDING_MODE && strstr( $value, '.' ) ) {
			$value    = (string) $value;
			$value    = explode( '.', $value );
			$value[1] = substr( $value[1], 0, $precision + 1 );
			$value    = implode( '.', $value );

			if ( substr( $value, -1 ) === '5' ) {
				$value = substr( $value, 0, -1 ) . '4';
			}
			$value = floatval( $value );
		}
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
 * Get chosen method for package from session.
 *
 * @since  3.2.0
 * @param  int   $key Key of package.
 * @param  array $package Package data array.
 * @return string|bool
 */
function wc_get_chosen_shipping_method_for_package( $key, $package ) {
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	$chosen_method  = isset( $chosen_methods[ $key ] ) ? $chosen_methods[ $key ] : false;
	$changed        = wc_shipping_methods_have_changed( $key, $package );

	// This is deprecated but here for BW compat. TODO: Remove in 4.0.0
	$method_counts  = WC()->session->get( 'shipping_method_counts' );

	if ( ! empty( $method_counts[ $key ] ) ) {
		$method_count = absint( $method_counts[ $key ] );
	} else {
		$method_count = 0;
	}

	// If not set, not available, or available methods have changed, set to the DEFAULT option.
	if ( ! $chosen_method || $changed || ! isset( $package['rates'][ $chosen_method ] ) || sizeof( $package['rates'] ) !== $method_count ) {
		$chosen_method          = wc_get_default_shipping_method_for_package( $key, $package, $chosen_method );
		$chosen_methods[ $key ] = $chosen_method;
		$method_counts[ $key ]  = sizeof( $package['rates'] );

		WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
		WC()->session->set( 'shipping_method_counts', $method_counts );

		do_action( 'woocommerce_shipping_method_chosen', $chosen_method );
	}
	return $chosen_method;
}

/**
 * Choose the default method for a package.
 *
 * @since  3.2.0
 * @param  int    $key Key of package.
 * @param  array  $package Package data array.
 * @param  string $chosen_method Cgosen method id.
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
 *
 * @since  3.2.0
 * @param  int   $key Key of package.
 * @param  array $package Package data array.
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
	return $new_rates !== $prev_rates;
}
