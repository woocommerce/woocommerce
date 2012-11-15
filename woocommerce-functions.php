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
 * Handle redirects before content is output - hooked into template_redirect so is_page works.
 *
 * @access public
 * @return void
 */
function woocommerce_redirects() {
	global $woocommerce, $wp_query;

	// When default permalinks are enabled, redirect shop page to post type archive url
	if ( isset($_GET['page_id']) && $_GET['page_id'] > 0 && get_option( 'permalink_structure' )=="" && $_GET['page_id'] == woocommerce_get_page_id('shop') ) :
		wp_safe_redirect( get_post_type_archive_link('product') );
		exit;
	endif;

	// When on the checkout with an empty cart, redirect to cart page
	if (is_page(woocommerce_get_page_id('checkout')) && sizeof($woocommerce->cart->get_cart())==0) :
		wp_redirect(get_permalink(woocommerce_get_page_id('cart')));
		exit;
	endif;

	// When on pay page with no query string, redirect to checkout
	if (is_page(woocommerce_get_page_id('pay')) && !isset($_GET['order'])) :
		wp_redirect(get_permalink(woocommerce_get_page_id('checkout')));
		exit;
	endif;

	// My account page redirects (logged out)
	if (!is_user_logged_in() && ( is_page(woocommerce_get_page_id('edit_address')) || is_page(woocommerce_get_page_id('view_order')) || is_page(woocommerce_get_page_id('change_password')) )) :
		wp_redirect(get_permalink(woocommerce_get_page_id('myaccount')));
		exit;
	endif;

	// Redirect to the product page if we have a single product
	if (is_search() && is_post_type_archive('product') && get_option('woocommerce_redirect_on_single_search_result')=='yes') {
		if ($wp_query->post_count==1) {
			$product = new WC_Product($wp_query->post->ID);
			if ($product->is_visible()) wp_safe_redirect( get_permalink($product->id), 302 );
			exit;
		}
	}
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
			unset( $classes[ array_search('current_page_parent', $classes) ] );
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
 * Add logout link to my account menu.
 *
 * @access public
 * @param string $items
 * @param array $args
 * @return string
 */
function woocommerce_nav_menu_items( $items, $args ) {
	if ( get_option('woocommerce_menu_logout_link')=='yes' && strstr($items, get_permalink(woocommerce_get_page_id('myaccount'))) && is_user_logged_in() )
		$items .= '<li class="logout"><a href="'. wp_logout_url(home_url()) .'">'.__( 'Logout', 'woocommerce' ).'</a></li>';

    return $items;
}


/**
 * Update catalog ordering if posted.
 *
 * @access public
 * @return void
 */
function woocommerce_update_catalog_ordering() {
	global $woocommerce;
	
	if ( isset( $_REQUEST['sort'] ) && $_REQUEST['sort'] != '' )
		$woocommerce->session->orderby = esc_attr( $_REQUEST['sort'] );
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
				if ( ! isset( $cart_totals[$cart_item_key]['qty'] ) )
					continue;

				// Check the quantity input
				$original = $cart_totals[ $cart_item_key ]['qty'];
				$casted = (int) $cart_totals[ $cart_item_key ]['qty'];
				$casted = (string) $casted;
				if ( $original == $casted ) {
					$quantity = absint( $cart_totals[ $cart_item_key ]['qty'] );
				} else {
					$quantity = $woocommerce->cart->cart_contents[ $cart_item_key ]['quantity'];
				}

				// Update cart validation
	    		$passed_validation 	= apply_filters('woocommerce_update_cart_validation', true, $cart_item_key, $values, $quantity);

	    		// Check downloadable items
				if ( get_option('woocommerce_limit_downloadable_product_qty') == 'yes' ) {
					if ( $_product->is_downloadable() && $_product->is_virtual() && $quantity > 1 ) {
						$woocommerce->add_error( sprintf(__( 'You can only have 1 %s in your cart.', 'woocommerce' ), $_product->get_title()) );
						$passed_validation = false;
					}
				}

	    		if ( $passed_validation )
		    		$woocommerce->cart->set_quantity( $cart_item_key, $quantity );

			}
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
	
	$product_id			= apply_filters('woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
	$was_added_to_cart 	= false;
	$adding_to_cart 	= new WC_Product( $product_id );
    
    // Variable product handling
    if ( $adding_to_cart->is_type( 'variable' ) ) {
    
    	$variation_id 		= empty( $_REQUEST['variation_id'] ) ? '' : absint( $_REQUEST['variation_id'] );
    	$quantity 			= empty( $_REQUEST['quantity'] ) ? 1 : absint( $_REQUEST['quantity'] );
    	$all_variations_set = true;
    	$variations 		= array();

		// Only allow integer variation ID - if its not set, redirect to the product page
		if ( empty( $variation_id ) ) {
			$woocommerce->add_error( __( 'Please choose product options&hellip;', 'woocommerce' ) );
			wp_redirect( get_permalink( $product_id ) );
			exit;
		}
		
		$attributes = (array) maybe_unserialize( get_post_meta( $product_id, '_product_attributes', true ) );

		// Verify all attributes for the variable product were set
		foreach ( $attributes as $attribute ) {
            if ( ! $attribute['is_variation'] ) 
            	continue;

            $taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );
            if ( ! empty( $_REQUEST[$taxonomy] ) ) {
                // Get value from post data
                $value = esc_attr( stripslashes( $_REQUEST[ $taxonomy ] ) );

                // Use name so it looks nicer in the cart widget/order page etc - instead of a sanitized string
                $variations[ esc_attr( $attribute['name'] ) ] = $value;
			} else {
                $all_variations_set = false;
            }
        }

        if ( $all_variations_set ) {
        	// Add to cart validation
        	$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

        	if ( $passed_validation ) {
				if ( $woocommerce->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations ) ) {
					woocommerce_add_to_cart_message( $product_id );
					$was_added_to_cart = true;
				}
			}
        } else {
            $woocommerce->add_error( __( 'Please choose product options&hellip;', 'woocommerce' ) );
            wp_redirect( get_permalink( $product_id ) );
			exit;
       }

    // Grouped Products
    } elseif ( $adding_to_cart->is_type( 'grouped' ) ) {

		if ( ! empty( $_REQUEST['quantity'] ) && is_array( $_REQUEST['quantity'] ) ) {
		
			$quantity_set = false;
			$added_to_cart = array();
		
			foreach ( $_REQUEST['quantity'] as $item => $quantity ) {
				if ( $quantity < 1 ) 
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
				wp_redirect( get_permalink( $product_id ) );
				exit;
			}
		
		} elseif ( $product_id ) {
		
			/* Link on product archives */
			$woocommerce->add_error( __( 'Please choose a product to add to your cart&hellip;', 'woocommerce' ) );
			wp_redirect( get_permalink( $product_id ) );
			exit;
		
		}

	// Simple Products
    } else {

		$quantity 			= empty( $_REQUEST['quantity'] ) ? 1 : absint( $_REQUEST['quantity'] );

		// Add to cart validation
		$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

		if ( $passed_validation ) {
    		// Add the product to the cart
    		if ( $woocommerce->cart->add_to_cart( $product_id, $quantity ) ) {
    			woocommerce_add_to_cart_message( $product_id );
    			$was_added_to_cart = true;
    		}
		}

    }

    // If we added the product to the cart we can now do a redirect, otherwise just continue loading the page to show errors
    if ( $was_added_to_cart ) {

		$url = apply_filters( 'add_to_cart_redirect', $url );

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
			wp_safe_redirect( remove_query_arg( array( 'add-to-cart', 'quantity', 'product_id' ), wp_get_referer() ) );
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

		$added_text = sprintf( __( 'Added &quot;%s&quot; to your cart.', 'woocommerce' ), join( __('&quot; and &quot;'), array_filter( array_merge( array( join( '&quot;, &quot;', array_slice( $titles, 0, -1 ) ) ), array_slice( $titles, -1 ) ) ) ) );
		
	} else {
		$added_text = sprintf( __( '&quot;%s&quot; was successfully added to your cart.', 'woocommerce' ), get_the_title( $product_id ) );
	}

	// Output success messages
	if ( get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) :

		$return_to 	= wp_get_referer() ? wp_get_referer() : home_url();

		$message 	= sprintf('<a href="%s" class="button">%s</a> %s', $return_to, __( 'Continue Shopping &rarr;', 'woocommerce' ), $added_text );

	else :

		$message 	= sprintf('<a href="%s" class="button">%s</a> %s', get_permalink(woocommerce_get_page_id('cart')), __( 'View Cart &rarr;', 'woocommerce' ), $added_text );

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
			if ( $order->order_total > 0 ) {
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

	if (isset($_POST['login']) && $_POST['login']) :

		$woocommerce->verify_nonce('login');

		if ( !isset($_POST['username']) || empty($_POST['username']) ) $woocommerce->add_error( __( 'Username is required.', 'woocommerce' ) );
		if ( !isset($_POST['password']) || empty($_POST['password']) ) $woocommerce->add_error( __( 'Password is required.', 'woocommerce' ) );

		if ($woocommerce->error_count()==0) :

			$creds = array();
			$creds['user_login'] 	= $_POST['username'];
			$creds['user_password'] = $_POST['password'];
			$creds['remember'] = true;
			$secure_cookie = is_ssl() ? true : false;
			$user = wp_signon( $creds, $secure_cookie );
			if ( is_wp_error($user) ) :
				$woocommerce->add_error( $user->get_error_message() );
			else :

				if (isset($_POST['redirect']) && $_POST['redirect']) :
					wp_safe_redirect( esc_attr($_POST['redirect']) );
					exit;
				endif;

				if ( wp_get_referer() ) :
					wp_safe_redirect( wp_get_referer() );
					exit;
				endif;

				wp_redirect(get_permalink(woocommerce_get_page_id('myaccount')));
				exit;
			endif;

		endif;

	endif;
}


/**
 * Process the registration form.
 *
 * @access public
 * @return void
 */
function woocommerce_process_registration() {

	global $woocommerce;

	if (isset($_POST['register']) && $_POST['register']) :

		$woocommerce->verify_nonce('register');

		// Get fields
		$user_email 			= isset( $_POST['email'] ) ? trim( $_POST['email'] ) : '';
		$password				= isset( $_POST['password'] ) ? trim( $_POST['password'] ) : '';
		$password2 				= isset( $_POST['password2'] ) ? trim( $_POST['password2'] ) : '';
		$user_email 			= apply_filters( 'user_registration_email', $user_email );

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
		if ( !$password ) $woocommerce->add_error( __( 'Password is required.', 'woocommerce' ) );
		if ( !$password2 ) $woocommerce->add_error( __( 'Re-enter your password.', 'woocommerce' ) );
		if ( $password != $password2 ) $woocommerce->add_error( __( 'Passwords do not match.', 'woocommerce' ) );

		// Spam trap
		if (isset($_POST['email_2']) && $_POST['email_2']) $woocommerce->add_error( __( 'Anti-spam field was filled in.', 'woocommerce' ) );

		if ($woocommerce->error_count()==0) :

			$reg_errors = new WP_Error();
			do_action('register_post', $sanitized_user_login, $user_email, $reg_errors);
			$reg_errors = apply_filters( 'registration_errors', $reg_errors, $sanitized_user_login, $user_email );

            // if there are no errors, let's create the user account
			if ( !$reg_errors->get_error_code() ) :

                $user_id 	= wp_create_user( $sanitized_user_login, $password, $user_email );

                if ( !$user_id ) {
                	$woocommerce->add_error( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
                    return;
                }

                // Change role
                wp_update_user( array ('ID' => $user_id, 'role' => 'customer') ) ;
                
                // Action
	            do_action( 'woocommerce_created_customer', $user_id );

				// send the user a confirmation and their login details
				$mailer = $woocommerce->mailer();
				$mailer->customer_new_account( $user_id, $password );

                // set the WP login cookie
                $secure_cookie = is_ssl() ? true : false;
                wp_set_auth_cookie($user_id, true, $secure_cookie);

                // Redirect
                if ( wp_get_referer() ) :
					wp_safe_redirect( wp_get_referer() );
					exit;
				endif;
				wp_redirect(get_permalink(woocommerce_get_page_id('myaccount')));
				exit;

			else :
				$woocommerce->add_error( $reg_errors->get_error_message() );
            	return;
			endif;

		endif;

	endif;
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
		foreach ( $item['item_meta'] as $meta ) {
			if ( ! substr( $meta['meta_name'], 0, 3) === 'pa_' ) continue;
			$variations[$meta['meta_name']] = $meta['meta_value'];
		}

		// Add to cart validation
		if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity ) ) continue;

		$woocommerce->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations );
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

		if ($order->id == $order_id && $order->order_key == $order_key && in_array($order->status, array('pending', 'failed')) && $woocommerce->verify_nonce('cancel_order', '_GET')) :

			// Cancel the order + restore stock
			$order->cancel_order( __('Order cancelled by customer.', 'woocommerce' ) );

			// Message
			$woocommerce->add_message( __( 'Your order was cancelled.', 'woocommerce' ) );

			do_action( 'woocommerce_cancelled_order', $order->id );

		elseif ($order->status!='pending') :

			$woocommerce->add_error( __( 'Your order is no longer pending and could not be cancelled. Please contact us if you need assistance.', 'woocommerce' ) );

		else :

			$woocommerce->add_error( __( 'Invalid order.', 'woocommerce' ) );

		endif;

		wp_safe_redirect($woocommerce->cart->get_cart_url());
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

		global $wpdb;

		$product_id 	= (int) urldecode($_GET['download_file']);
		$order_key 		= urldecode( $_GET['order'] );
		$email 			= sanitize_email( str_replace( ' ', '+', urldecode( $_GET['email'] ) ) );
		$download_id 	= isset( $_GET['key'] ) ? urldecode( $_GET['key'] ) : '';  // backwards compatibility for existing download URLs
		$_product	 	= new WC_Product( $product_id );

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
			
			if ( ! $order->is_download_permitted() && $order->status != 'publish' )
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

		// Get the download URL and try to replace the url with a path
		$file_path = $_product->get_file_download_path( $download_id );

		if ( ! $file_path ) exit;

		$file_download_method = apply_filters( 'woocommerce_file_download_method', get_option( 'woocommerce_file_download_method' ), $product_id );
		
		// Redirect to download location
		if ( $file_download_method == 'redirect' ) {
			header( 'Location: ' . $file_path );
			exit;
		}

		// Get URLS with https
		$site_url = site_url();
		$network_url = network_admin_url();
		if ( is_ssl() ) {
			$site_url = str_replace( 'https:', 'http:', $site_url );
			$network_url = str_replace( 'https:', 'http:', $network_url );
		}

		if ( ! is_multisite() ) {
			$file_path = str_replace( trailingslashit( $site_url ), ABSPATH, $file_path );
		} else {
			$upload_dir = wp_upload_dir();

			// Try to replace network url
			$file_path = str_replace( trailingslashit( $network_url ), ABSPATH, $file_path );

			// Now try to replace upload URL
			$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_path );
		}

		// See if its local or remote
		if ( strstr( $file_path, 'http:' ) || strstr( $file_path, 'https:' ) || strstr( $file_path, 'ftp:' ) ) {
			$remote_file = true;
		} else {
			$remote_file = false;
			$file_path = realpath( $file_path );
		}

		// Download the file
		$file_extension = strtolower( substr( strrchr( $file_path, "." ), 1 ) );

		$ctype = "application/force-download";

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $file_extension, $mimes ) ) {
				$ctype = $type;
				break;
			}
		}

		if ( $file_download_method == 'xsendfile' ) {
			
			// Path fix - kudos to Jason Judge
         	if ( getcwd() )
         		$file_path = trim( preg_replace( '`^' . getcwd() . '`' , '', $file_path ), '/' );

            header( "Content-Disposition: attachment; filename=\"" . basename( $file_path ) . "\";" );

            if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {

            	header("X-Sendfile: $file_path");
            	exit;

            } elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {

            	header( "X-Lighttpd-Sendfile: $file_path" );
            	exit;

            } elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {

            	header( "X-Accel-Redirect: $file_path" );
            	exit;

            }
        }

        if ( ! function_exists('readfile_chunked')) {

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
		    function readfile_chunked( $file, $retbytes = true ) {

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
		}

        @session_write_close();
        if ( function_exists( 'apache_setenv' ) ) 
        	@apache_setenv( 'no-gzip', 1 );
        @ini_set( 'zlib.output_compression', 'Off' );
		@set_time_limit(0);
		@set_magic_quotes_runtime(0);
		@ob_end_clean();
		if ( ob_get_level() ) 
			@ob_end_clean(); // Zip corruption fix

		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Robots: none" );
		header( "Content-Type: " . $ctype );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"" . basename( $file_path ) . "\";" );
		header( "Content-Transfer-Encoding: binary" );

        if ( $size = @filesize( $file_path ) ) 
        	header( "Content-Length: " . $size );

        // Serve it
        if ( $remote_file ) {

        	@readfile_chunked( "$file_path" ) or header( 'Location: ' . $file_path );

        } else {

        	@readfile_chunked( "$file_path" ) or wp_die( __( 'File not found', 'woocommerce' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'woocommerce' ) . '</a>' );

        }

        exit;

	}
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

	if (is_admin()) return; // Don't track admin

	// Call the Piwik ecommerce function if WP-Piwik is configured to add tracking codes to the page
	$wp_piwik_global_settings = get_option('wp-piwik_global-settings');

	// Return if Piwik settings are not here, or if global is not set
	if ( ! isset( $wp_piwik_global_settings['add_tracking_code'] ) || ! $wp_piwik_global_settings['add_tracking_code'] ) return;
	if ( ! isset( $GLOBALS['wp_piwik'] ) ) return;

	// Remove WP-Piwik from wp_footer and run it here instead, to get Piwik
	// loaded *before* we do our ecommerce tracking calls
	remove_action('wp_footer', array($GLOBALS['wp_piwik'],'footer'));
	$GLOBALS['wp_piwik']->footer();

	// Get the order and output tracking code
	$order = new WC_Order($order_id);
	?>
	<script type="text/javascript">
	try {
		// Add order items
		<?php if ($order->get_items()) foreach($order->get_items() as $item) : $_product = $order->get_product_from_item( $item ); ?>
			piwikTracker.addEcommerceItem(
				"<?php echo esc_js( $_product->sku ); ?>",	// (required) SKU: Product unique identifier
				"<?php echo esc_js( $item['name'] ); ?>",		// (optional) Product name
				"<?php if (isset($_product->variation_data)) echo esc_js( woocommerce_get_formatted_variation( $_product->variation_data, true ) ); ?>",	// (optional) Product category. You can also specify an array of up to 5 categories eg. ["Books", "New releases", "Biography"]
				<?php echo esc_js( ( $item['line_cost'] / $item['qty'] ) ); ?>,		// (recommended) Product price
				<?php echo esc_js( $item['qty'] ); ?> 		// (optional, default to 1) Product quantity
			);
		<?php endforeach; ?>
		// Track order
		piwikTracker.trackEcommerceOrder(
			"<?php echo esc_js( $order_id ); ?>",		// (required) Unique Order ID
			<?php echo esc_js( $order->order_total ); ?>,	// (required) Order Revenue grand total (includes tax, shipping, and subtracted discount)
			false,					// (optional) Order sub total (excludes shipping)
			<?php echo esc_js( $order->get_total_tax() ); ?>,	// (optional) Tax amount
			<?php echo esc_js( $order->get_shipping() ); ?>,	// (optional) Shipping amount
			false 					// (optional) Discount offered (set to false for unspecified parameter)
		);
	} catch( err ) {}
	</script>
	<?php
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
function woocommerce_add_comment_rating($comment_id) {
	if ( isset($_POST['rating']) ) :
		global $post;
		if ( ! $_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0 ) return;
		add_comment_meta( $comment_id, 'rating', (int) esc_attr($_POST['rating']), true );
		delete_transient( 'wc_average_rating_' . esc_attr($post->ID) );
	endif;
}


/**
 * Validate the comment ratings.
 *
 * @access public
 * @param array $comment_data
 * @return array
 */
function woocommerce_check_comment_rating($comment_data) {
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
 * Change the count properties for all terms based on our manual counters
 *
 * @access public
 * @return array Contains all the terms with updated counts
 */
function wc_get_terms_count_filter( $terms, $taxonomies ) {
    if ( ! is_admin() && in_array( 'product_cat', $taxonomies ) ) {
    	$counted_ids = get_option( 'wc_prod_cat_counts' );

        foreach ( $terms as $term ) {
        	if ( isset( $counted_ids[ $term->term_id ] ) ) {
            	$term->count = count( $counted_ids[ $term->term_id ] );
            }
        }
    }

    return $terms;
}