<?php
/**
 * WooCommerce Functions
 *
 * Hooked-in functions for WooCommerce related events on the front-end.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     1.6.4
 */

/**
 * Handle IPN requests for the legacy paypal gateway by calling gateways manually if needed.
 *
 * @access public
 * @return void
 */
function woocommerce_legacy_paypal_ipn() {
	if ( ! empty( $_GET['paypalListener'] ) && $_GET['paypalListener'] == 'paypal_standard_IPN' ) {
		global $woocommerce;

		$woocommerce->payment_gateways();

		do_action( 'woocommerce_api_wc_gateway_paypal' );
	}
}

add_action( 'init', 'woocommerce_legacy_paypal_ipn' );

/**
 * Handle redirects before content is output - hooked into template_redirect so is_page works.
 *
 * @access public
 * @return void
 */
function woocommerce_template_redirect() {
	global $woocommerce, $wp_query;

	// When default permalinks are enabled, redirect shop page to post type archive url
	if ( ! empty( $_GET['page_id'] ) && get_option( 'permalink_structure' ) == "" && $_GET['page_id'] == woocommerce_get_page_id( 'shop' ) ) {
		wp_safe_redirect( get_post_type_archive_link('product') );
		exit;
	}

	// When on the checkout with an empty cart, redirect to cart page
	elseif ( is_page( woocommerce_get_page_id( 'checkout' ) ) && sizeof( $woocommerce->cart->get_cart() ) == 0 ) {
		wp_redirect( get_permalink( woocommerce_get_page_id( 'cart' ) ) );
		exit;
	}

	// When on pay page with no query string, redirect to checkout
	elseif ( is_page( woocommerce_get_page_id( 'pay' ) ) && ! isset( $_GET['order'] ) ) {
		wp_redirect( get_permalink( woocommerce_get_page_id( 'checkout' ) ) );
		exit;
	}

	// My account page redirects (logged out)
	elseif ( ! is_user_logged_in() && ( is_page( woocommerce_get_page_id( 'edit_address' ) ) || is_page( woocommerce_get_page_id( 'view_order' ) ) || is_page( woocommerce_get_page_id( 'change_password' ) ) ) ) {
		wp_redirect( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) );
		exit;
	}

	// Logout
	elseif ( is_page( woocommerce_get_page_id( 'logout' ) ) ) {
		wp_redirect( str_replace( '&amp;', '&', wp_logout_url( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ) ) );
		exit;
	}

	// Redirect to the product page if we have a single product
	elseif ( is_search() && is_post_type_archive( 'product' ) && apply_filters( 'woocommerce_redirect_single_search_result', true ) && $wp_query->post_count == 1 ) {
		$product = get_product( $wp_query->post );

		if ( $product->is_visible() ) {
			wp_safe_redirect( get_permalink( $product->id ), 302 );
			exit;
		}
	}

	// Force SSL
	elseif ( get_option('woocommerce_force_ssl_checkout') == 'yes' && ! is_ssl() ) {

		if ( is_checkout() || is_account_page() || apply_filters( 'woocommerce_force_ssl_checkout', false ) ) {
			if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
				wp_safe_redirect( preg_replace( '|^http://|', 'https://', $_SERVER['REQUEST_URI'] ) );
				exit;
			} else {
				wp_safe_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				exit;
			}
		}

	}

	// Break out of SSL if we leave the checkout/my accounts (anywhere but thanks)
	elseif ( get_option('woocommerce_force_ssl_checkout') == 'yes' && get_option('woocommerce_unforce_ssl_checkout') == 'yes' && is_ssl() && $_SERVER['REQUEST_URI'] && ! is_checkout() && ! is_page( woocommerce_get_page_id('thanks') ) && ! is_ajax() && ! is_account_page() && apply_filters( 'woocommerce_unforce_ssl_checkout', true ) ) {

		if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
			wp_safe_redirect( preg_replace( '|^https://|', 'http://', $_SERVER['REQUEST_URI'] ) );
			exit;
		} else {
			wp_safe_redirect( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			exit;
		}

	}

	// Buffer the checkout page
	elseif ( is_checkout() ) {
		ob_start();
	}

}

/**
 * woocommerce_nav_menu_items function.
 *
 * @access public
 * @param mixed $items
 * @param mixed $args
 * @return void
 */
function woocommerce_nav_menu_items( $items, $args ) {
	if ( ! is_user_logged_in() ) {

		$hide_pages   = array();
		$hide_pages[] = (int) woocommerce_get_page_id( 'change_password' );
		$hide_pages[] = (int) woocommerce_get_page_id( 'logout' );
		$hide_pages[] = (int) woocommerce_get_page_id( 'edit_address' );
		$hide_pages[] = (int) woocommerce_get_page_id( 'view_order' );
		$hide_pages   = apply_filters( 'woocommerce_logged_out_hidden_page_ids', $hide_pages );

		foreach ( $items as $key => $item ) {
			if ( ! empty( $item->object_id ) && ! empty( $item->object ) && in_array( $item->object_id, $hide_pages ) && $item->object == 'page' ) {
				unset( $items[ $key ] );
			}
		}
	}
    return $items;
}

/**
 * Fix active class in nav for shop page.
 *
 * @access public
 * @param array $menu_items
 * @param array $args
 * @return array
 */
function woocommerce_nav_menu_item_classes( $menu_items, $args ) {

	if ( ! is_woocommerce() ) return $menu_items;

	$shop_page 		= (int) woocommerce_get_page_id('shop');
	$page_for_posts = (int) get_option( 'page_for_posts' );

	foreach ( (array) $menu_items as $key => $menu_item ) {

		$classes = (array) $menu_item->classes;

		// Unset active class for blog page
		if ( $page_for_posts == $menu_item->object_id ) {
			$menu_items[$key]->current = false;

			if ( in_array( 'current_page_parent', $classes ) )
				unset( $classes[ array_search('current_page_parent', $classes) ] );

			if ( in_array( 'current-menu-item', $classes ) )
				unset( $classes[ array_search('current-menu-item', $classes) ] );

		// Set active state if this is the shop page link
		} elseif ( is_shop() && $shop_page == $menu_item->object_id ) {
			$menu_items[$key]->current = true;
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';

		// Set parent state if this is a product page
		} elseif ( is_singular( 'product' ) && $shop_page == $menu_item->object_id ) {
			$classes[] = 'current_page_parent';
		}

		$menu_items[$key]->classes = array_unique( $classes );

	}

	return $menu_items;
}


/**
 * Fix active class in wp_list_pages for shop page.
 *
 * https://github.com/woothemes/woocommerce/issues/177
 *
 * @author Jessor, Peter Sterling
 * @access public
 * @param string $pages
 * @return string
 */
function woocommerce_list_pages( $pages ){
    global $post;

    if (is_woocommerce()) {
        $pages = str_replace( 'current_page_parent', '', $pages); // remove current_page_parent class from any item
        $shop_page = 'page-item-' . woocommerce_get_page_id('shop'); // find shop_page_id through woocommerce options

        if (is_shop()) :
        	$pages = str_replace($shop_page, $shop_page . ' current_page_item', $pages); // add current_page_item class to shop page
    	else :
    		$pages = str_replace($shop_page, $shop_page . ' current_page_parent', $pages); // add current_page_parent class to shop page
    	endif;
    }
    return $pages;
}

/**
 * Remove from cart/update.
 *
 * @access public
 * @return void
 */
function woocommerce_update_cart_action() {
	global $woocommerce;

	// Remove from cart
	if ( isset($_GET['remove_item']) && $_GET['remove_item'] && $woocommerce->verify_nonce('cart', '_GET')) {

		$woocommerce->cart->set_quantity( $_GET['remove_item'], 0 );

		$woocommerce->add_message( __( 'Cart updated.', 'woocommerce' ) );

		$referer = ( wp_get_referer() ) ? wp_get_referer() : $woocommerce->cart->get_cart_url();
		wp_safe_redirect( $referer );
		exit;

	// Update Cart
	} elseif ( ( ! empty( $_POST['update_cart'] ) || ! empty( $_POST['proceed'] ) ) && $woocommerce->verify_nonce('cart')) {

		$cart_totals = isset( $_POST['cart'] ) ? $_POST['cart'] : '';

		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
			foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

				$_product = $values['data'];

				// Skip product if no updated quantity was posted
				if ( ! isset( $cart_totals[ $cart_item_key ]['qty'] ) )
					continue;

				// Sanitize
				$quantity = apply_filters( 'woocommerce_stock_amount_cart_item', apply_filters( 'woocommerce_stock_amount', preg_replace( "/[^0-9\.]/", "", $cart_totals[ $cart_item_key ]['qty'] ) ), $cart_item_key );

				if ( "" === $quantity || $quantity == $values['quantity'] )
					continue;

				// Update cart validation
	    		$passed_validation 	= apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $values, $quantity );

	    		// is_sold_individually
				if ( $_product->is_sold_individually() && $quantity > 1 ) {
					$woocommerce->add_error( sprintf( __( 'You can only have 1 %s in your cart.', 'woocommerce' ), $_product->get_title() ) );
					$passed_validation = false;
				}

	    		if ( $passed_validation )
		    		$woocommerce->cart->set_quantity( $cart_item_key, $quantity, false );

			}

			$woocommerce->cart->calculate_totals();
		}

		if ( ! empty( $_POST['proceed'] ) ) {
			wp_safe_redirect( $woocommerce->cart->get_checkout_url() );
			exit;
		} else {
			$woocommerce->add_message( __( 'Cart updated.', 'woocommerce' ) );

			$referer = ( wp_get_referer() ) ? wp_get_referer() : $woocommerce->cart->get_cart_url();
			$referer = remove_query_arg( 'remove_discounts', $referer );
			wp_safe_redirect( $referer );
			exit;
		}

	}
}


/**
 * Add to cart action
 *
 * Checks for a valid request, does validation (via hooks) and then redirects if valid.
 *
 * @access public
 * @param bool $url (default: false)
 * @return void
 */
function woocommerce_add_to_cart_action( $url = false ) {

	if ( empty( $_REQUEST['add-to-cart'] ) || ! is_numeric( $_REQUEST['add-to-cart'] ) )
		return;

	global $woocommerce;

	$product_id          = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
	$was_added_to_cart   = false;
	$added_to_cart       = array();
	$adding_to_cart      = get_product( $product_id );
	$add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->product_type, $adding_to_cart );

    // Variable product handling
    if ( 'variable' === $add_to_cart_handler ) {

    	$variation_id       = empty( $_REQUEST['variation_id'] ) ? '' : absint( $_REQUEST['variation_id'] );
    	$quantity           = empty( $_REQUEST['quantity'] ) ? 1 : apply_filters( 'woocommerce_stock_amount', $_REQUEST['quantity'] );
    	$all_variations_set = true;
    	$variations         = array();

		// Only allow integer variation ID - if its not set, redirect to the product page
		if ( empty( $variation_id ) ) {
			$woocommerce->add_error( __( 'Please choose product options&hellip;', 'woocommerce' ) );
			return;
		}

		$attributes = $adding_to_cart->get_attributes();
		$variation  = get_product( $variation_id );

		// Verify all attributes
		foreach ( $attributes as $attribute ) {
            if ( ! $attribute['is_variation'] )
            	continue;

            $taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );

            if ( ! empty( $_REQUEST[ $taxonomy ] ) ) {

                // Get value from post data
                // Don't use woocommerce_clean as it destroys sanitized characters
                $value = sanitize_title( trim( stripslashes( $_REQUEST[ $taxonomy ] ) ) );

                // Get valid value from variation
                $valid_value = $variation->variation_data[ $taxonomy ];

                // Allow if valid
                if ( $valid_value == '' || $valid_value == $value ) {
	                if ( $attribute['is_taxonomy'] )
	                	$variations[ esc_html( $attribute['name'] ) ] = $value;
	                else {
		                // For custom attributes, get the name from the slug
		                $options = array_map( 'trim', explode( '|', $attribute['value'] ) );
		                foreach ( $options as $option ) {
		                	if ( sanitize_title( $option ) == $value ) {
		                		$value = $option;
		                		break;
		                	}
		                }
		                 $variations[ esc_html( $attribute['name'] ) ] = $value;
	                }
	                continue;
	            }

			}

            $all_variations_set = false;
        }

        if ( $all_variations_set ) {
        	// Add to cart validation
        	$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations );

        	if ( $passed_validation ) {
				if ( $woocommerce->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations ) ) {
					woocommerce_add_to_cart_message( $product_id );
					$was_added_to_cart = true;
					$added_to_cart[] = $product_id;
				}
			}
        } else {
            $woocommerce->add_error( __( 'Please choose product options&hellip;', 'woocommerce' ) );
            return;
       }

    // Grouped Products
    } elseif ( 'grouped' === $add_to_cart_handler ) {

		if ( ! empty( $_REQUEST['quantity'] ) && is_array( $_REQUEST['quantity'] ) ) {

			$quantity_set = false;

			foreach ( $_REQUEST['quantity'] as $item => $quantity ) {
				if ( $quantity <= 0 )
					continue;

				$quantity_set = true;

				// Add to cart validation
				$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $item, $quantity );

				if ( $passed_validation ) {
					if ( $woocommerce->cart->add_to_cart( $item, $quantity ) ) {
						$was_added_to_cart = true;
						$added_to_cart[] = $item;
					}
				}
			}

			if ( $was_added_to_cart ) {
				woocommerce_add_to_cart_message( $added_to_cart );
			}

			if ( ! $was_added_to_cart && ! $quantity_set ) {
				$woocommerce->add_error( __( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce' ) );
				return;
			}

		} elseif ( $product_id ) {

			/* Link on product archives */
			$woocommerce->add_error( __( 'Please choose a product to add to your cart&hellip;', 'woocommerce' ) );
			return;

		}

	// Simple Products
    } else {

		$quantity 			= empty( $_REQUEST['quantity'] ) ? 1 : apply_filters( 'woocommerce_stock_amount', $_REQUEST['quantity'] );

		// Add to cart validation
		$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

		if ( $passed_validation ) {
    		// Add the product to the cart
    		if ( $woocommerce->cart->add_to_cart( $product_id, $quantity ) ) {
    			woocommerce_add_to_cart_message( $product_id );
    			$was_added_to_cart = true;
    			$added_to_cart[] = $product_id;
    		}
		}

    }

    // If we added the product to the cart we can now do a redirect, otherwise just continue loading the page to show errors
    if ( $was_added_to_cart ) {

		$url = apply_filters( 'add_to_cart_redirect', $url, $product_id );

		// If has custom URL redirect there
		if ( $url ) {
			wp_safe_redirect( $url );
			exit;
		}

		// Redirect to cart option
		elseif ( get_option('woocommerce_cart_redirect_after_add') == 'yes' && $woocommerce->error_count() == 0 ) {
			wp_safe_redirect( $woocommerce->cart->get_cart_url() );
			exit;
		}

		// Redirect to page without querystring args
		elseif ( wp_get_referer() ) {
			wp_safe_redirect( add_query_arg( 'added-to-cart', implode( ',', $added_to_cart ), remove_query_arg( array( 'add-to-cart', 'quantity', 'product_id' ), wp_get_referer() ) ) );
			exit;
		}

    }

}


/**
 * Add to cart messages.
 *
 * @access public
 * @return void
 */
function woocommerce_add_to_cart_message( $product_id ) {
	global $woocommerce;

	if ( is_array( $product_id ) ) {

		$titles = array();

		foreach ( $product_id as $id ) {
			$titles[] = get_the_title( $id );
		}

		$added_text = sprintf( __( 'Added &quot;%s&quot; to your cart.', 'woocommerce' ), join( __( '&quot; and &quot;', 'woocommerce' ), array_filter( array_merge( array( join( '&quot;, &quot;', array_slice( $titles, 0, -1 ) ) ), array_slice( $titles, -1 ) ) ) ) );

	} else {
		$added_text = sprintf( __( '&quot;%s&quot; was successfully added to your cart.', 'woocommerce' ), get_the_title( $product_id ) );
	}

	// Output success messages
	if ( get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) :

		$return_to 	= apply_filters( 'woocommerce_continue_shopping_redirect', wp_get_referer() ? wp_get_referer() : home_url() );

		$message 	= sprintf('<a href="%s" class="button">%s</a> %s', $return_to, __( 'Continue Shopping &rarr;', 'woocommerce' ), $added_text );

	else :

		$message 	= sprintf('<a href="%s" class="button">%s</a> %s', get_permalink( woocommerce_get_page_id( 'cart' ) ), __( 'View Cart &rarr;', 'woocommerce' ), $added_text );

	endif;

	$woocommerce->add_message( apply_filters('woocommerce_add_to_cart_message', $message) );
}


/**
 * Clear cart after payment.
 *
 * @access public
 * @return void
 */
function woocommerce_clear_cart_after_payment() {
	global $woocommerce;

	if ( is_page( woocommerce_get_page_id( 'thanks' ) ) ) {

		if ( isset( $_GET['order'] ) )
			$order_id = $_GET['order'];
		else
			$order_id = 0;

		if ( isset( $_GET['key'] ) )
			$order_key = $_GET['key'];
		else
			$order_key = '';

		if ( $order_id > 0 ) {
			$order = new WC_Order( $order_id );

			if ( $order->order_key == $order_key ) {
				$woocommerce->cart->empty_cart();
			}
		}

	}

	if ( $woocommerce->session->order_awaiting_payment > 0 ) {

		$order = new WC_Order( $woocommerce->session->order_awaiting_payment );

		if ( $order->id > 0 && $order->status !== 'pending' ) {
			$woocommerce->cart->empty_cart();
		}
	}
}


/**
 * Process the checkout form.
 *
 * @access public
 * @return void
 */
function woocommerce_checkout_action() {
	global $woocommerce;

	if ( isset( $_POST['woocommerce_checkout_place_order'] ) || isset( $_POST['woocommerce_checkout_update_totals'] ) ) {

		if ( sizeof( $woocommerce->cart->get_cart() ) == 0 ) {
			wp_redirect( get_permalink( woocommerce_get_page_id( 'cart' ) ) );
			exit;
		}

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
			define( 'WOOCOMMERCE_CHECKOUT', true );

		$woocommerce_checkout = $woocommerce->checkout();
		$woocommerce_checkout->process_checkout();
	}
}


/**
 * Process the pay form.
 *
 * @access public
 * @return void
 */
function woocommerce_pay_action() {
	global $woocommerce;

	if ( isset( $_POST['woocommerce_pay'] ) && $woocommerce->verify_nonce( 'pay' ) ) {

		ob_start();

		// Pay for existing order
		$order_key 	= urldecode( $_GET['order'] );
		$order_id 	= absint( $_GET['order_id'] );
		$order 		= new WC_Order( $order_id );

		if ( $order->id == $order_id && $order->order_key == $order_key && in_array( $order->status, array( 'pending', 'failed' ) ) ) {

			// Set customer location to order location
			if ( $order->billing_country )
				$woocommerce->customer->set_country( $order->billing_country );
			if ( $order->billing_state )
				$woocommerce->customer->set_state( $order->billing_state );
			if ( $order->billing_postcode )
				$woocommerce->customer->set_postcode( $order->billing_postcode );
			if ( $order->billing_city )
				$woocommerce->customer->set_city( $order->billing_city );

			// Update payment method
			if ( $order->needs_payment() ) {
				$payment_method = woocommerce_clean( $_POST['payment_method'] );

				$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();

				// Update meta
				update_post_meta( $order_id, '_payment_method', $payment_method );

				if ( isset( $available_gateways[ $payment_method ] ) )
					$payment_method_title = $available_gateways[ $payment_method ]->get_title();

				update_post_meta( $order_id, '_payment_method_title', $payment_method_title);

				// Validate
				$available_gateways[ $payment_method ]->validate_fields();

				// Process
				if ( $woocommerce->error_count() == 0 ) {

					$result = $available_gateways[ $payment_method ]->process_payment( $order_id );

					// Redirect to success/confirmation/payment page
					if ( $result['result'] == 'success' ) {
						wp_redirect( $result['redirect'] );
						exit;
					}

				}

			} else {

				// No payment was required for order
				$order->payment_complete();
				wp_safe_redirect( get_permalink( woocommerce_get_page_id( 'thanks' ) ) );
				exit;

			}

		}

	}
}


/**
 * Process the login form.
 *
 * @access public
 * @return void
 */
function woocommerce_process_login() {
	global $woocommerce;

	if ( ! empty( $_POST['login'] ) ) {

		$woocommerce->verify_nonce( 'login' );

		try {
			$creds = array();

			if ( empty( $_POST['username'] ) )
				throw new Exception( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . __( 'Username is required.', 'woocommerce' ) );
			if ( empty( $_POST['password'] ) )
				throw new Exception( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . __( 'Password is required.', 'woocommerce' ) );

			if ( is_email( $_POST['username'] ) ) {
				$user = get_user_by( 'email', $_POST['username'] );

				if ( isset( $user->user_login ) )
					$creds['user_login'] 	= $user->user_login;
				else
					throw new Exception( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . __( 'A user could not be found with this email address.', 'woocommerce' ) );
			} else {
				$creds['user_login'] 	= $_POST['username'];
			}

			$creds['user_password'] = $_POST['password'];
			$creds['remember']      = true;
			$secure_cookie          = is_ssl() ? true : false;
			$user                   = wp_signon( $creds, $secure_cookie );

			if ( is_wp_error( $user ) ) {
				throw new Exception( $user->get_error_message() );
			} else {

				if ( ! empty( $_POST['redirect'] ) ) {
					$redirect = esc_url( $_POST['redirect'] );
				} elseif ( wp_get_referer() ) {
					$redirect = esc_url( wp_get_referer() );
				} else {
					$redirect = esc_url( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) );
				}

				wp_redirect( apply_filters( 'woocommerce_login_redirect', $redirect, $user ) );
				exit;
			}
		} catch (Exception $e) {
			$woocommerce->add_error( $e->getMessage() );
		}
	}
}


/**
 * Process the registration form.
 *
 * @access public
 * @return void
 */
function woocommerce_process_registration() {
	global $woocommerce, $current_user;

	if ( ! empty( $_POST['register'] ) ) {

		$woocommerce->verify_nonce( 'register' );

		// Get fields
		$user_email = isset( $_POST['email'] ) ? trim( $_POST['email'] ) : '';
		$password   = isset( $_POST['password'] ) ? trim( $_POST['password'] ) : '';
		$password2  = isset( $_POST['password2'] ) ? trim( $_POST['password2'] ) : '';
		$user_email = apply_filters( 'user_registration_email', $user_email );

		if ( get_option( 'woocommerce_registration_email_for_username' ) == 'no' ) {

			$username 				= isset( $_POST['username'] ) ? trim( $_POST['username'] ) : '';
			$sanitized_user_login 	= sanitize_user( $username );

			// Check the username
			if ( $sanitized_user_login == '' ) {
				$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Please enter a username.', 'woocommerce' ) );
			} elseif ( ! validate_username( $username ) ) {
				$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'woocommerce' ) );
				$sanitized_user_login = '';
			} elseif ( username_exists( $sanitized_user_login ) ) {
				$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'This username is already registered, please choose another one.', 'woocommerce' ) );
			}

		} else {

			$username 				= $user_email;
			$sanitized_user_login 	= sanitize_user( $username );

		}

		// Check the e-mail address
		if ( $user_email == '' ) {
			$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Please type your e-mail address.', 'woocommerce' ) );
		} elseif ( ! is_email( $user_email ) ) {
			$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'The email address isn&#8217;t correct.', 'woocommerce' ) );
			$user_email = '';
		} elseif ( email_exists( $user_email ) ) {
			$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'This email is already registered, please choose another one.', 'woocommerce' ) );
		}

		// Password
		if ( ! $password ) $woocommerce->add_error( __( 'Password is required.', 'woocommerce' ) );
		if ( ! $password2 ) $woocommerce->add_error( __( 'Re-enter your password.', 'woocommerce' ) );
		if ( $password != $password2 ) $woocommerce->add_error( __( 'Passwords do not match.', 'woocommerce' ) );

		// Spam trap
		if ( ! empty( $_POST['email_2'] ) )
			$woocommerce->add_error( __( 'Anti-spam field was filled in.', 'woocommerce' ) );

		// More error checking
		$reg_errors = new WP_Error();
		do_action( 'register_post', $sanitized_user_login, $user_email, $reg_errors );
		$reg_errors = apply_filters( 'registration_errors', $reg_errors, $sanitized_user_login, $user_email );

		if ( $reg_errors->get_error_code() ) {
			$woocommerce->add_error( $reg_errors->get_error_message() );
			return;
		}

		if ( $woocommerce->error_count() == 0 ) {

            $new_customer_data = array(
            	'user_login' => $sanitized_user_login,
            	'user_pass'  => $password,
            	'user_email' => $user_email,
            	'role'       => 'customer'
            );

            $user_id = wp_insert_user( apply_filters( 'woocommerce_new_customer_data', $new_customer_data ) );

            if ( is_wp_error($user_id) ) {
            	$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
                return;
            }

            // Get user
            $current_user = get_user_by( 'id', $user_id );

            // Action
            do_action( 'woocommerce_created_customer', $user_id );

			// send the user a confirmation and their login details
			$mailer = $woocommerce->mailer();
			$mailer->customer_new_account( $user_id, $password );

            // set the WP login cookie
            $secure_cookie = is_ssl() ? true : false;
            wp_set_auth_cookie($user_id, true, $secure_cookie);

            // Redirect
			if ( wp_get_referer() ) {
				$redirect = esc_url( wp_get_referer() );
			} else {
				$redirect = esc_url( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) );
			}

			wp_redirect( apply_filters( 'woocommerce_registration_redirect', $redirect ) );
			exit;
		}

	}
}


/**
 * Place a previous order again.
 *
 * @access public
 * @return void
 */
function woocommerce_order_again() {
	global $woocommerce;

	// Nothing to do
	if ( ! isset( $_GET['order_again'] ) || ! is_user_logged_in() || get_option('woocommerce_allow_customers_to_reorder') == 'no' ) return;

	// Nonce security check
	if ( ! $woocommerce->verify_nonce( 'order_again', '_GET' ) ) return;

	// Clear current cart
	$woocommerce->cart->empty_cart();

	// Load the previous order - Stop if the order does not exist
	$order = new WC_Order( (int) $_GET['order_again'] );

	if ( empty( $order->id ) ) return;

	if ( $order->status!='completed' ) return;

	// Make sure the previous order belongs to the current customer
	if ( $order->user_id != get_current_user_id() ) return;

	// Copy products from the order to the cart
	foreach ( $order->get_items() as $item ) {
		// Load all product info including variation data
		$product_id   = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $item['product_id'] );
		$quantity     = (int) $item['qty'];
		$variation_id = (int) $item['variation_id'];
		$variations   = array();
		$cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );

		foreach ( $item['item_meta'] as $meta_name => $meta_value ) {
			if ( taxonomy_is_product_attribute( $meta_name ) )
				$variations[ $meta_name ] = $meta_value[0];
		}

		// Add to cart validation
		if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations, $cart_item_data ) ) continue;

		$woocommerce->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations, $cart_item_data );
	}

	do_action( 'woocommerce_ordered_again', $order->id );

	// Redirect to cart
	$woocommerce->add_message( __( 'The cart has been filled with the items from your previous order.', 'woocommerce' ) );
	wp_safe_redirect( $woocommerce->cart->get_cart_url() );
	exit;
}


/**
 * Cancel a pending order.
 *
 * @access public
 * @return void
 */
function woocommerce_cancel_order() {

	global $woocommerce;

	if ( isset($_GET['cancel_order']) && isset($_GET['order']) && isset($_GET['order_id']) ) :

		$order_key = urldecode( $_GET['order'] );
		$order_id = (int) $_GET['order_id'];

		$order = new WC_Order( $order_id );

		if ( $order->id == $order_id && $order->order_key == $order_key && in_array( $order->status, array( 'pending', 'failed' ) ) && $woocommerce->verify_nonce( 'cancel_order', '_GET' ) ) :

			// Cancel the order + restore stock
			$order->cancel_order( __('Order cancelled by customer.', 'woocommerce' ) );

			// Message
			$woocommerce->add_message( __( 'Your order was cancelled.', 'woocommerce' ) );

			do_action( 'woocommerce_cancelled_order', $order->id );

		elseif ( $order->status != 'pending' ) :

			$woocommerce->add_error( __( 'Your order is no longer pending and could not be cancelled. Please contact us if you need assistance.', 'woocommerce' ) );

		else :

			$woocommerce->add_error( __( 'Invalid order.', 'woocommerce' ) );

		endif;

		wp_safe_redirect( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) );
		exit;

	endif;
}


/**
 * Download a file - hook into init function.
 *
 * @access public
 * @return void
 */
function woocommerce_download_product() {

	if ( isset( $_GET['download_file'] ) && isset( $_GET['order'] ) && isset( $_GET['email'] ) ) {

		global $wpdb, $is_IE;

		$product_id           = (int) urldecode($_GET['download_file']);
		$order_key            = urldecode( $_GET['order'] );
		$email                = sanitize_email( str_replace( ' ', '+', urldecode( $_GET['email'] ) ) );
		$download_id          = isset( $_GET['key'] ) ? preg_replace( '/\s+/', ' ', urldecode( $_GET['key'] ) ) : '';
		$_product             = get_product( $product_id );
		$file_download_method = apply_filters( 'woocommerce_file_download_method', get_option( 'woocommerce_file_download_method' ), $product_id );

		if ( ! is_email( $email) )
			wp_die( __( 'Invalid email address.', 'woocommerce' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

		$query = "
			SELECT order_id,downloads_remaining,user_id,download_count,access_expires,download_id
			FROM " . $wpdb->prefix . "woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s";
		$args = array(
			$email,
			$order_key,
			$product_id
		);

		if ( $download_id ) {
			// backwards compatibility for existing download URLs
			$query .= " AND download_id = %s";
			$args[] = $download_id;
		}

		$download_result = $wpdb->get_row( $wpdb->prepare( $query, $args ) );

		if ( ! $download_result )
			wp_die( __( 'Invalid download.', 'woocommerce' ) . ' <a href="'.home_url().'">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

		$download_id 			= $download_result->download_id;
		$order_id 				= $download_result->order_id;
		$downloads_remaining 	= $download_result->downloads_remaining;
		$download_count 		= $download_result->download_count;
		$user_id 				= $download_result->user_id;
		$access_expires 		= $download_result->access_expires;

		if ( $user_id && get_option( 'woocommerce_downloads_require_login' ) == 'yes' ) {

			if ( ! is_user_logged_in() )
				wp_die( __( 'You must be logged in to download files.', 'woocommerce' ) . ' <a href="' . wp_login_url( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ) . '">' . __( 'Login &rarr;', 'woocommerce' ) . '</a>' );

			elseif ( $user_id != get_current_user_id() )
				wp_die( __( 'This is not your download link.', 'woocommerce' ) );

		}

		if ( ! get_post( $product_id ) )
			wp_die( __( 'Product no longer exists.', 'woocommerce' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

		if ( $order_id ) {
			$order = new WC_Order( $order_id );

			if ( ! $order->is_download_permitted() || $order->post_status != 'publish' )
				wp_die( __( 'Invalid order.', 'woocommerce' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );
		}

		if ( $downloads_remaining == '0' )
			wp_die( __( 'Sorry, you have reached your download limit for this file', 'woocommerce' ) . ' <a href="'.home_url().'">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

		if ( $access_expires > 0 && strtotime( $access_expires) < current_time( 'timestamp' ) )
			wp_die( __( 'Sorry, this download has expired', 'woocommerce' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

		if ( $downloads_remaining > 0 ) {
			$wpdb->update( $wpdb->prefix . "woocommerce_downloadable_product_permissions", array(
				'downloads_remaining' => $downloads_remaining - 1,
			), array(
				'user_email' 	=> $email,
				'order_key' 	=> $order_key,
				'product_id' 	=> $product_id,
				'download_id' 	=> $download_id
			), array( '%d' ), array( '%s', '%s', '%d', '%s' ) );
		}

		// Count the download
		$wpdb->update( $wpdb->prefix . "woocommerce_downloadable_product_permissions", array(
			'download_count' => $download_count + 1,
		), array(
			'user_email' 	=> $email,
			'order_key' 	=> $order_key,
			'product_id' 	=> $product_id,
			'download_id' 	=> $download_id
		), array( '%d' ), array( '%s', '%s', '%d', '%s' ) );

		// Trigger action
		do_action( 'woocommerce_download_product', $email, $order_key, $product_id, $user_id, $download_id, $order_id );

		// Get the download URL and try to replace the url with a path
		$file_path = $_product->get_file_download_path( $download_id );

		if ( ! $file_path )
			wp_die( __( 'No file defined', 'woocommerce' ) . ' <a href="'.home_url().'">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

		// Redirect to the file...
		if ( $file_download_method == "redirect" ) {
			header( 'Location: ' . $file_path );
			exit;
		}

		// ...or serve it
		if ( ! is_multisite() ) {

			/*
			 * Download file may be either http or https.
			 * site_url() depends on whether the page containing the download (ie; My Account) is served via SSL because WC
			 * modifies site_url() via a filter to force_ssl.
			 * So blindly doing a str_replace is incorrect because it will fail when schemes are mismatched. This code
			 * handles the various permutations.
			 */
			$scheme = parse_url( $file_path, PHP_URL_SCHEME );

			if ( $scheme ) {
				$site_url = set_url_scheme( site_url( '' ), $scheme );
			} else {
				$site_url = is_ssl() ? str_replace( 'https:', 'http:', site_url() ) : site_url();
			}

			$file_path   = str_replace( trailingslashit( $site_url ), ABSPATH, $file_path );

		} else {

			$network_url = is_ssl() ? str_replace( 'https:', 'http:', network_admin_url() ) : network_admin_url();
			$upload_dir  = wp_upload_dir();

			// Try to replace network url
			$file_path   = str_replace( trailingslashit( $network_url ), ABSPATH, $file_path );

			// Now try to replace upload URL
			$file_path   = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_path );
		}

		// See if its local or remote
		if ( strstr( $file_path, 'http:' ) || strstr( $file_path, 'https:' ) || strstr( $file_path, 'ftp:' ) ) {
			$remote_file = true;
		} else {
			$remote_file = false;

			// Remove Query String
			if ( strstr( $file_path, '?' ) ) {
				$exploded_file_path = explode( '?', $file_path );
				$file_path = current( $exploded_file_path );
			}

			$file_path   = realpath( $file_path );
		}

		$file_extension  = strtolower( substr( strrchr( $file_path, "." ), 1 ) );
		$ctype           = "application/force-download";

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $file_extension, $mimes ) ) {
				$ctype = $type;
				break;
			}
		}

		// Start setting headers
		if ( ! ini_get('safe_mode') )
			@set_time_limit(0);

		if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() )
			@set_magic_quotes_runtime(0);

		if( function_exists( 'apache_setenv' ) )
			@apache_setenv( 'no-gzip', 1 );

		@session_write_close();
		@ini_set( 'zlib.output_compression', 'Off' );
		@ob_end_clean();

		if ( ob_get_level() )
			@ob_end_clean(); // Zip corruption fix

		if ( $is_IE && is_ssl() ) {
			// IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
			header( 'Cache-Control: private' );
		} else {
			nocache_headers();
		}

		$file_name = basename( $file_path );

		if ( strstr( $file_name, '?' ) ) {
			$exploded_file_name = explode( '?', $file_name );
			$file_name = current( $exploded_file_name );
		}

		header( "Robots: none" );
		header( "Content-Type: " . $ctype );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"" . $file_name . "\";" );
		header( "Content-Transfer-Encoding: binary" );

        if ( $size = @filesize( $file_path ) )
        	header( "Content-Length: " . $size );

		if ( $file_download_method == 'xsendfile' ) {

			// Path fix - kudos to Jason Judge
         	if ( getcwd() )
         		$file_path = trim( preg_replace( '`^' . getcwd() . '`' , '', $file_path ), '/' );

            header( "Content-Disposition: attachment; filename=\"" . $file_name . "\";" );

            if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {

            	header("X-Sendfile: $file_path");
            	exit;

            } elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {

            	header( "X-Lighttpd-Sendfile: $file_path" );
            	exit;

            } elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {

            	header( "X-Accel-Redirect: /$file_path" );
            	exit;

            }
        }

        if ( $remote_file )
        	@woocommerce_readfile_chunked( $file_path ) or header( 'Location: ' . $file_path );
        else
        	@woocommerce_readfile_chunked( $file_path ) or wp_die( __( 'File not found', 'woocommerce' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

        exit;
	}
}

/**
 * readfile_chunked
 *
 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
 *
 * @access   public
 * @param    string    file
 * @param    boolean    return bytes of file
 * @return   void
 */
function woocommerce_readfile_chunked( $file, $retbytes = true ) {

	$chunksize = 1 * ( 1024 * 1024 );
	$buffer = '';
	$cnt = 0;

	$handle = fopen( $file, 'r' );
	if ( $handle === FALSE )
		return FALSE;

	while ( ! feof( $handle ) ) {
		$buffer = fread( $handle, $chunksize );
		echo $buffer;
		ob_flush();
		flush();

		if ( $retbytes )
			$cnt += strlen( $buffer );
	}

	$status = fclose( $handle );

	if ( $retbytes && $status )
		return $cnt;

	return $status;
}

/**
 * ecommerce tracking with piwik.
 *
 * @access public
 * @param int $order_id
 * @return void
 */
function woocommerce_ecommerce_tracking_piwik( $order_id ) {
	global $woocommerce;

	// Don't track admin
	if ( current_user_can('manage_options') )
		return;

	// Call the Piwik ecommerce function if WP-Piwik is configured to add tracking codes to the page
	$wp_piwik_global_settings = get_option( 'wp-piwik_global-settings' );

	// Return if Piwik settings are not here, or if global is not set
	if ( ! isset( $wp_piwik_global_settings['add_tracking_code'] ) || ! $wp_piwik_global_settings['add_tracking_code'] )
		return;
	if ( ! isset( $GLOBALS['wp_piwik'] ) )
		return;

	// Get the order and get tracking code
	$order = new WC_Order( $order_id );
	ob_start();
	?>
	try {
		// Add order items
		<?php if ( $order->get_items() ) foreach( $order->get_items() as $item ) : $_product = $order->get_product_from_item( $item ); ?>

			piwikTracker.addEcommerceItem(
				"<?php echo esc_js( $_product->get_sku() ); ?>",			// (required) SKU: Product unique identifier
				"<?php echo esc_js( $item['name'] ); ?>",					// (optional) Product name
				"<?php
					if ( isset( $_product->variation_data ) )
						echo esc_js( woocommerce_get_formatted_variation( $_product->variation_data, true ) );
				?>",	// (optional) Product category. You can also specify an array of up to 5 categories eg. ["Books", "New releases", "Biography"]
				<?php echo esc_js( $order->get_item_total( $item ) ); ?>,	// (recommended) Product price
				<?php echo esc_js( $item['qty'] ); ?> 						// (optional, default to 1) Product quantity
			);

		<?php endforeach; ?>

		// Track order
		piwikTracker.trackEcommerceOrder(
			"<?php echo esc_js( $order->get_order_number() ); ?>",	// (required) Unique Order ID
			<?php echo esc_js( $order->get_total() ); ?>,			// (required) Order Revenue grand total (includes tax, shipping, and subtracted discount)
			false,													// (optional) Order sub total (excludes shipping)
			<?php echo esc_js( $order->get_total_tax() ); ?>,		// (optional) Tax amount
			<?php echo esc_js( $order->get_shipping() ); ?>,		// (optional) Shipping amount
			false 													// (optional) Discount offered (set to false for unspecified parameter)
		);
	} catch( err ) {}
	<?php
	$code = ob_get_clean();
	$woocommerce->add_inline_js( $code );
}


/**
 * Products RSS Feed.
 *
 * @access public
 * @return void
 */
function woocommerce_products_rss_feed() {
	// Product RSS
	if ( is_post_type_archive( 'product' ) || is_singular( 'product' ) ) {

		$feed = get_post_type_archive_feed_link( 'product' );

		echo '<link rel="alternate" type="application/rss+xml"  title="' . __( 'New products', 'woocommerce' ) . '" href="' . esc_attr( $feed ) . '" />';

	} elseif ( is_tax( 'product_cat' ) ) {

		$term = get_term_by('slug', esc_attr( get_query_var('product_cat') ), 'product_cat');

		$feed = add_query_arg('product_cat', $term->slug, get_post_type_archive_feed_link( 'product' ));

		echo '<link rel="alternate" type="application/rss+xml"  title="' . sprintf(__( 'New products added to %s', 'woocommerce' ), urlencode($term->name)) . '" href="' . esc_attr( $feed ) . '" />';

	} elseif ( is_tax( 'product_tag' ) ) {

		$term = get_term_by('slug', esc_attr( get_query_var('product_tag') ), 'product_tag');

		$feed = add_query_arg('product_tag', $term->slug, get_post_type_archive_feed_link( 'product' ));

		echo '<link rel="alternate" type="application/rss+xml"  title="' . sprintf(__( 'New products tagged %s', 'woocommerce' ), urlencode($term->name)) . '" href="' . esc_attr( $feed ) . '" />';

	}
}


/**
 * Rating field for comments.
 *
 * @access public
 * @param mixed $comment_id
 * @return void
 */
function woocommerce_add_comment_rating( $comment_id ) {
	if ( isset( $_POST['rating'] ) ) {

		if ( ! $_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0 )
			return;

		add_comment_meta( $comment_id, 'rating', (int) esc_attr( $_POST['rating'] ), true );

		woocommerce_clear_comment_rating_transients( $comment_id );
	}
}


/**
 * Validate the comment ratings.
 *
 * @access public
 * @param array $comment_data
 * @return array
 */
function woocommerce_check_comment_rating( $comment_data ) {
	global $woocommerce;

	// If posting a comment (not trackback etc) and not logged in
	if ( isset( $_POST['rating'] ) && ! $woocommerce->verify_nonce('comment_rating') )
		wp_die( __( 'You have taken too long. Please go back and refresh the page.', 'woocommerce' ) );

	elseif ( isset( $_POST['rating'] ) && empty( $_POST['rating'] ) && $comment_data['comment_type'] == '' && get_option('woocommerce_review_rating_required') == 'yes' ) {
		wp_die( __( 'Please rate the product.', 'woocommerce' ) );
		exit;
	}
	return $comment_data;
}


/**
 * Finds an Order ID based on an order key.
 *
 * @access public
 * @param string $order_key An order key has generated by
 * @return int The ID of an order, or 0 if the order could not be found
 */
function woocommerce_get_order_id_by_order_key( $order_key ) {
	global $wpdb;

	// Faster than get_posts()
	$order_id = $wpdb->get_var( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_order_key' AND meta_value = '{$order_key}'" );

	return $order_id;
}

/**
 * Track product views
 *
 * @access public
 * @package 	WooCommerce/Widgets
 * @return void
 */
function woocommerce_track_product_view() {
	if ( ! is_singular( 'product' ) )
		return;

	global $post, $product;

	if ( empty( $_COOKIE['woocommerce_recently_viewed'] ) )
		$viewed_products = array();
	else
		$viewed_products = (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] );

	if ( ! in_array( $post->ID, $viewed_products ) )
		$viewed_products[] = $post->ID;

	if ( sizeof( $viewed_products ) > 15 )
		array_shift( $viewed_products );

	// Store for session only
	setcookie( "woocommerce_recently_viewed", implode( '|', $viewed_products ), 0, COOKIEPATH, COOKIE_DOMAIN, false, true );
}

add_action( 'template_redirect', 'woocommerce_track_product_view', 20 );

/**
 * Layered Nav Init
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @return void
 */
function woocommerce_layered_nav_init( ) {

	if ( is_active_widget( false, false, 'woocommerce_layered_nav', true ) && ! is_admin() ) {

		global $_chosen_attributes, $woocommerce, $_attributes_array;

		$_chosen_attributes = $_attributes_array = array();

		$attribute_taxonomies = $woocommerce->get_attribute_taxonomies();
		if ( $attribute_taxonomies ) {
			foreach ( $attribute_taxonomies as $tax ) {

		    	$attribute = sanitize_title( $tax->attribute_name );
		    	$taxonomy = $woocommerce->attribute_taxonomy_name( $attribute );

				// create an array of product attribute taxonomies
				$_attributes_array[] = $taxonomy;

		    	$name = 'filter_' . $attribute;
		    	$query_type_name = 'query_type_' . $attribute;

		    	if ( ! empty( $_GET[ $name ] ) && taxonomy_exists( $taxonomy ) ) {

		    		$_chosen_attributes[ $taxonomy ]['terms'] = explode( ',', $_GET[ $name ] );

		    		if ( empty( $_GET[ $query_type_name ] ) || ! in_array( strtolower( $_GET[ $query_type_name ] ), array( 'and', 'or' ) ) )
		    			$_chosen_attributes[ $taxonomy ]['query_type'] = apply_filters( 'woocommerce_layered_nav_default_query_type', 'and' );
		    		else
		    			$_chosen_attributes[ $taxonomy ]['query_type'] = strtolower( $_GET[ $query_type_name ] );

				}
			}
	    }

	    add_filter('loop_shop_post_in', 'woocommerce_layered_nav_query');
    }
}

add_action( 'init', 'woocommerce_layered_nav_init', 1 );


/**
 * Layered Nav post filter
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @param array $filtered_posts
 * @return array
 */
function woocommerce_layered_nav_query( $filtered_posts ) {
	global $_chosen_attributes, $woocommerce, $wp_query;

	if ( sizeof( $_chosen_attributes ) > 0 ) {

		$matched_products = array();
		$filtered_attribute = false;

		foreach ( $_chosen_attributes as $attribute => $data ) {

			$matched_products_from_attribute = array();
			$filtered = false;

			if ( sizeof( $data['terms'] ) > 0 ) {
				foreach ( $data['terms'] as $value ) {

					$posts = get_posts(
						array(
							'post_type' 	=> 'product',
							'numberposts' 	=> -1,
							'post_status' 	=> 'publish',
							'fields' 		=> 'ids',
							'no_found_rows' => true,
							'tax_query' => array(
								array(
									'taxonomy' 	=> $attribute,
									'terms' 	=> $value,
									'field' 	=> 'id'
								)
							)
						)
					);

					// AND or OR
					if ( $data['query_type'] == 'or' ) {

						if ( ! is_wp_error( $posts ) && ( sizeof( $matched_products_from_attribute ) > 0 || $filtered ) )
							$matched_products_from_attribute = array_merge($posts, $matched_products_from_attribute);
						elseif ( ! is_wp_error( $posts ) )
							$matched_products_from_attribute = $posts;

					} else {

						if ( ! is_wp_error( $posts ) && ( sizeof( $matched_products_from_attribute ) > 0 || $filtered ) )
							$matched_products_from_attribute = array_intersect($posts, $matched_products_from_attribute);
						elseif ( ! is_wp_error( $posts ) )
							$matched_products_from_attribute = $posts;
					}

					$filtered = true;

				}
			}

			if ( sizeof( $matched_products ) > 0 || $filtered_attribute )
				$matched_products = array_intersect( $matched_products_from_attribute, $matched_products );
			else
				$matched_products = $matched_products_from_attribute;

			$filtered_attribute = true;

		}

		if ( $filtered ) {

			$woocommerce->query->layered_nav_post__in = $matched_products;
			$woocommerce->query->layered_nav_post__in[] = 0;

			if ( sizeof( $filtered_posts ) == 0 ) {
				$filtered_posts = $matched_products;
				$filtered_posts[] = 0;
			} else {
				$filtered_posts = array_intersect( $filtered_posts, $matched_products );
				$filtered_posts[] = 0;
			}

		}
	}

	return (array) $filtered_posts;
}

/**
 * Price filter Init
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @return void
 */
function woocommerce_price_filter_init() {
	global $woocommerce;

	if ( is_active_widget( false, false, 'price_filter', true ) && ! is_admin() ) {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wc-price-slider', $woocommerce->plugin_url() . '/assets/js/frontend/price-slider' . $suffix . '.js', array( 'jquery-ui-slider' ), '1.6', true );

		add_filter( 'loop_shop_post_in', 'woocommerce_price_filter' );
	}
}

add_action( 'init', 'woocommerce_price_filter_init' );


/**
 * Price Filter post filter
 *
 * @package 	WooCommerce/Widgets
 * @access public
 * @param array $filtered_posts
 * @return array
 */
function woocommerce_price_filter($filtered_posts) {
    global $wpdb;

    if ( isset( $_GET['max_price'] ) && isset( $_GET['min_price'] ) ) {

        $matched_products = array();
        $min 	= floatval( $_GET['min_price'] );
        $max 	= floatval( $_GET['max_price'] );

        $matched_products_query = $wpdb->get_results( $wpdb->prepare("
        	SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
			INNER JOIN $wpdb->postmeta ON ID = post_id
			WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = %s AND meta_value BETWEEN %d AND %d
		", '_price', $min, $max ), OBJECT_K );

        if ( $matched_products_query ) {
            foreach ( $matched_products_query as $product ) {
                if ( $product->post_type == 'product' )
                    $matched_products[] = $product->ID;
                if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) )
                    $matched_products[] = $product->post_parent;
            }
        }

        // Filter the id's
        if ( sizeof( $filtered_posts ) == 0) {
            $filtered_posts = $matched_products;
            $filtered_posts[] = 0;
        } else {
            $filtered_posts = array_intersect( $filtered_posts, $matched_products );
            $filtered_posts[] = 0;
        }

    }

    return (array) $filtered_posts;
}

/**
 * Save the password and redirect back to the my account page.
 *
 * @access public
 */
function woocommerce_save_password() {
	global $woocommerce;

	if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
		return;

	if ( empty( $_POST[ 'action' ] ) || ( 'change_password' !== $_POST[ 'action' ] ) )
		return;

	$woocommerce->verify_nonce( 'change_password' );

	$update = true;
	$errors = new WP_Error();
	$user   = new stdClass();

	$user->ID = (int) get_current_user_id();

	if ( $user->ID <= 0 )
		return;

	$_POST = array_map( 'woocommerce_clean', $_POST );

	$pass1           = ! empty( $_POST[ 'password_1' ] ) ? $_POST[ 'password_1' ] : '';
	$pass2           = ! empty( $_POST[ 'password_2' ] ) ? $_POST[ 'password_2' ] : '';
	$user->user_pass = $pass1;

	if ( empty( $pass1 ) || empty( $pass2 ) )
		$woocommerce->add_error( __( 'Please enter your password.', 'woocommerce' ) );

	if ( $pass1 !== $pass2 )
		$woocommerce->add_error( __( 'Passwords do not match.', 'woocommerce' ) );

	// Allow plugins to return their own errors.
	do_action_ref_array( 'user_profile_update_errors', array ( &$errors, $update, &$user ) );

	if ( $errors->get_error_messages() )
		foreach( $errors->get_error_messages() as $error )
			$woocommerce->add_error( $error );

	if ( $woocommerce->error_count() == 0 ) {

		wp_update_user( $user ) ;

		$woocommerce->add_message( __( 'Password changed successfully.', 'woocommerce' ) );

		do_action( 'woocommerce_customer_change_password', $user->ID );

		wp_safe_redirect( get_permalink( woocommerce_get_page_id('myaccount') ) );
		exit;
	}
}

add_action( 'template_redirect', 'woocommerce_save_password' );

/**
 * Save and and update a billing or shipping address if the
 * form was submitted through the user account page.
 *
 * @access public
 */
function woocommerce_save_address() {
	global $woocommerce;

	if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) )
		return;

	if ( empty( $_POST[ 'action' ] ) || ( 'edit_address' !== $_POST[ 'action' ] ) )
		return;

	$woocommerce->verify_nonce( 'edit_address' );

	$validation = $woocommerce->validation();

	$user_id = get_current_user_id();

	if ( $user_id <= 0 ) return;

	$load_address = ( isset( $_GET[ 'address' ] ) ) ? esc_attr( $_GET[ 'address' ] ) : '';
	$load_address = ( $load_address == 'billing' || $load_address == 'shipping' ) ? $load_address : '';

	$address = $woocommerce->countries->get_address_fields( esc_attr($_POST[ $load_address . '_country' ]), $load_address . '_' );

	foreach ($address as $key => $field) :

		if (!isset($field['type'])) $field['type'] = 'text';

		// Get Value
		switch ($field['type']) :
			case "checkbox" :
				$_POST[$key] = isset($_POST[$key]) ? 1 : 0;
			break;
			default :
				$_POST[$key] = isset($_POST[$key]) ? woocommerce_clean($_POST[$key]) : '';
			break;
		endswitch;

		// Hook to allow modification of value
		$_POST[$key] = apply_filters('woocommerce_process_myaccount_field_' . $key, $_POST[$key]);

		// Validation: Required fields
		if ( isset($field['required']) && $field['required'] && empty($_POST[$key]) ) $woocommerce->add_error( $field['label'] . ' ' . __( 'is a required field.', 'woocommerce' ) );

		// Postcode
		if ($key=='billing_postcode' || $key=='shipping_postcode') :
			if ( ! $validation->is_postcode( $_POST[$key], $_POST[ $load_address . '_country' ] ) ) :
				$woocommerce->add_error( __( 'Please enter a valid postcode/ZIP.', 'woocommerce' ) );
			else :
				$_POST[$key] = $validation->format_postcode( $_POST[$key], $_POST[ $load_address . '_country' ] );
			endif;
		endif;

	endforeach;

	if ( $woocommerce->error_count() == 0 ) {

		foreach ($address as $key => $field) :
			update_user_meta( $user_id, $key, $_POST[$key] );
		endforeach;

		$woocommerce->add_message( __( 'Address changed successfully.', 'woocommerce' ) );

		do_action( 'woocommerce_customer_save_address', $user_id );

		wp_safe_redirect( get_permalink( woocommerce_get_page_id('myaccount') ) );
		exit;
	}
}

add_action( 'template_redirect', 'woocommerce_save_address' );

/**
 * Wrapper for wp_get_post_terms which supports ordering by parent
 * @return array of terms
 */
function wc_get_product_terms( $product_id, $taxonomy, $args = array() ) {
	if ( $args['orderby'] == 'parent' ) {
		unset( $args['orderby'] );
		$orderby_parent = true;
	}

	$terms = wp_get_post_terms( $product_id, $taxonomy, $args );

	if ( ! empty( $orderby_parent ) )
		usort( $terms, '_wc_get_product_terms_parent_usort_callback' );

	return $terms;
}

/**
 * Sort by parent
 * @return array
 */
function _wc_get_product_terms_parent_usort_callback( $a, $b ) {
	if( $a->parent === $b->parent )
		return 0;
	return ( $a->parent < $b->parent ) ? 1 : -1;
}