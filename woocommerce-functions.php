<?php
/**
 * WooCommerce Functions
 * 
 * Hooked-in functions for WooCommerce related events on the front-end.
 *
 * @package		WooCommerce
 * @category	Actions
 * @author		WooThemes
 */

/**
 * When default permalinks are enabled, redirect shop page to post type archive url
 **/
function woocommerce_shop_page_archive_redirect() {
	if ( isset($_GET['page_id']) && get_option( 'permalink_structure' )=="" && $_GET['page_id'] == woocommerce_get_page_id('shop') ) :
		wp_safe_redirect( get_post_type_archive_link('product') );
		exit;
	endif;
}

/**
 * Fix active class in nav for shop page
 **/
function woocommerce_nav_menu_item_classes( $menu_items, $args ) {
	
	if (!is_woocommerce()) return $menu_items;
	
	$shop_page 		= (int) woocommerce_get_page_id('shop');
	$page_for_posts = (int) get_option( 'page_for_posts' );

	foreach ( (array) $menu_items as $key => $menu_item ) :

		$classes = (array) $menu_item->classes;

		// Unset active class for blog page
		if ( $page_for_posts == $menu_item->object_id ) :
			$menu_items[$key]->current = false;
			unset( $classes[ array_search('current_page_parent', $classes) ] );
			unset( $classes[ array_search('current-menu-item', $classes) ] );

		// Set active state if this is the shop page link
		elseif ( is_shop() && $shop_page == $menu_item->object_id ) :
			$menu_items[$key]->current = true;
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';
		
		endif;

		$menu_items[$key]->classes = array_unique( $classes );
	
	endforeach;

	return $menu_items;
}

/**
 * Detect frontpage shop and fix pagination on static front page
 **/
function woocommerce_front_page_archive_paging_fix() {
		
	if ( is_front_page() && is_page( woocommerce_get_page_id('shop') )) :
		
		if (get_query_var('paged')) :
			$paged = get_query_var('paged'); 
		else :
			$paged = (get_query_var('page')) ? get_query_var('page') : 1;
		endif;
		
		global $wp_query;
		
		$wp_query->query( array( 'page_id' => woocommerce_get_page_id('shop'), 'is_paged' => true, 'paged' => $paged ) );
		
		define('SHOP_IS_ON_FRONT', true);
		
	endif;
}

/**
 * Front page archive/shop template applied to main loop
 */
function woocommerce_front_page_archive( $query ) {
		
	global $paged, $woocommerce, $wp_the_query, $wp_query;
	
	// Only apply to front_page
	if ( defined('SHOP_IS_ON_FRONT') && $query === $wp_the_query ) :
			
		if (get_query_var('paged')) :
			$paged = get_query_var('paged'); 
		else :
			$paged = (get_query_var('page')) ? get_query_var('page') : 1;
		endif;

		// Filter the query
		add_filter( 'parse_query', array( &$woocommerce->query, 'product_query') );
		
		// Query the products
		$wp_query->query( array( 'page_id' => '', 'p' => '', 'post_type' => 'product', 'paged' => $paged ) );
		
		// get products in view (for use by widgets)
		$woocommerce->query->get_products_in_view();
		
		// Remove the query manipulation
		remove_filter( 'parse_query', array( &$woocommerce->query, 'product_query') ); 
		remove_action( 'loop_start', 'woocommerce_front_page_archive', 1);
	
	endif;
}

/**
 * Fix active class in wp_list_pages for shop page
 *
 * Suggested by jessor - https://github.com/woothemes/woocommerce/issues/177
 * Amended Dec '11, by Peter Sterling - http://www.sterling-adventures.co.uk/
 **/
function woocommerce_list_pages($pages){
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
 * Add logout link to my account menu
 **/
function woocommerce_nav_menu_items( $items, $args ) {
	if ( get_option('woocommerce_menu_logout_link')=='yes' && strstr($items, get_permalink(woocommerce_get_page_id('myaccount'))) && is_user_logged_in() ) :
		$items .= '<li><a href="'. wp_logout_url(home_url()) .'">'.__('Logout', 'woocommerce').'</a></li>';
	endif;
	
    return $items;
}

/**
 * Update catalog ordering if posted
 */
function woocommerce_update_catalog_ordering() {
	if (isset($_POST['catalog_orderby']) && $_POST['catalog_orderby'] != '') $_SESSION['orderby'] = $_POST['catalog_orderby'];
}

/**
 * Increase coupon usage count
 */
function woocommerce_increase_coupon_counts() {
	global $woocommerce;
	if ($applied_coupons = $woocommerce->cart->get_applied_coupons()) foreach ($applied_coupons as $code) :
		$coupon = &new woocommerce_coupon( $code );
		$coupon->inc_usage_count();
	endforeach;
}

/**
 * Remove from cart/update
 **/
function woocommerce_update_cart_action() {
	global $woocommerce;
	
	// Remove from cart
	if ( isset($_GET['remove_item']) && $_GET['remove_item'] && $woocommerce->verify_nonce('cart', '_GET')) :
	
		$woocommerce->cart->set_quantity( $_GET['remove_item'], 0 );
		
		$woocommerce->add_message( __('Cart updated.', 'woocommerce') );
		
		if ( wp_get_referer() ) :
			wp_safe_redirect( wp_get_referer() );
			exit;
		endif;
	
	// Update Cart
	elseif (isset($_POST['update_cart']) && $_POST['update_cart']  && $woocommerce->verify_nonce('cart')) :
		
		$cart_totals = $_POST['cart'];
		
		if (sizeof($woocommerce->cart->get_cart())>0) : 
			foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) :
				
				if (isset($cart_totals[$cart_item_key]['qty'])) $woocommerce->cart->set_quantity( $cart_item_key, $cart_totals[$cart_item_key]['qty'] );
				
			endforeach;
		endif;
		
		$woocommerce->add_message( __('Cart updated.', 'woocommerce') );
		
	endif;
}

/**
 * Add to cart
 **/
function woocommerce_add_to_cart_action( $url = false ) {
	
	global $woocommerce;

	if (empty($_GET['add-to-cart']) || !$woocommerce->verify_nonce('add_to_cart', '_GET')) return;
    
	if (is_numeric($_GET['add-to-cart'])) :
		
		//single product
		$quantity = (isset($_POST['quantity'])) ? (int) $_POST['quantity'] : 1;
		
		// Add to the cart
		if ($woocommerce->cart->add_to_cart($_GET['add-to-cart'], $quantity)) :
			woocommerce_add_to_cart_message();
		endif;
	
	elseif ($_GET['add-to-cart']=='variation') :
		
		// Variation add to cart
		if (empty($_POST['variation_id']) || !is_numeric($_POST['variation_id'])) :
            
            $woocommerce->add_error( __('Please choose product options&hellip;', 'woocommerce') );
            wp_safe_redirect(apply_filters('woocommerce_add_to_cart_product_id', get_permalink($_GET['product'])));
            exit;
            
       else :
			
			$product_id 	= (int) apply_filters('woocommerce_add_to_cart_product_id', $_GET['product']);
			$variation_id 	= (int) $_POST['variation_id'];
			$quantity 		= (isset($_POST['quantity'])) ? (int) $_POST['quantity'] : 1;
			
            $attributes = (array) maybe_unserialize(get_post_meta($product_id, '_product_attributes', true));
            $variations = array();
            $all_variations_set = true;
            
            foreach ($attributes as $attribute) :

                if ( !$attribute['is_variation'] ) continue;

                $taxonomy = 'attribute_' . sanitize_title($attribute['name']);
                if (!empty($_POST[$taxonomy])) :
                    // Get value from post data
                    $value = esc_attr(stripslashes($_POST[$taxonomy]));

                    // Use name so it looks nicer in the cart widget/order page etc - instead of a sanitized string
                    $variations[esc_attr($attribute['name'])] = $value;
				else :
                    $all_variations_set = false;
                endif;
            endforeach;

            if ($all_variations_set && $variation_id > 0) :
                
                // Add to cart
				if ($woocommerce->cart->add_to_cart($product_id, $quantity, $variation_id, $variations)) :
					woocommerce_add_to_cart_message();
				endif;

            else :
                $woocommerce->add_error( __('Please choose product options&hellip;', 'woocommerce') );
                wp_redirect(get_permalink($_GET['product']));
                exit;
            endif;

		endif; 
	
	elseif ($_GET['add-to-cart']=='group') :
		
		// Group add to cart
		if (isset($_POST['quantity']) && is_array($_POST['quantity'])) :
			
			$total_quantity = 0;
			
			foreach ($_POST['quantity'] as $item => $quantity) :
				if ($quantity>0) :
					
					if ($woocommerce->cart->add_to_cart($item, $quantity)) :
						woocommerce_add_to_cart_message();
					endif;
					
					$total_quantity = $total_quantity + $quantity;
				endif;
			endforeach;
			
			if ($total_quantity==0) :
				$woocommerce->add_error( __('Please choose a quantity&hellip;', 'woocommerce') );
			endif;
		
		elseif ($_GET['product']) :
			
			/* Link on product pages */
			$woocommerce->add_error( __('Please choose a product&hellip;', 'woocommerce') );
			wp_redirect( get_permalink( $_GET['product'] ) );
			exit;
		
		endif; 
		
	endif;
	
	$url = apply_filters('add_to_cart_redirect', $url);
	
	// If has custom URL redirect there
	if ( $url ) {
		wp_safe_redirect( $url );
		exit;
	}
	
	// Redirect to cart option
	elseif (get_option('woocommerce_cart_redirect_after_add')=='yes' && $woocommerce->error_count() == 0) {
		wp_safe_redirect( $woocommerce->cart->get_cart_url() );
		exit;
	}
	
	// Otherwise redirect to where they came
	elseif ( wp_get_referer() ) {
		wp_safe_redirect( wp_get_referer() );
		exit;
	}
	
	// If all else fails redirect to root
	else {
		wp_safe_redirect(home_url());
		exit;
	}	
}

/**
 * Add to cart messages
 **/
function woocommerce_add_to_cart_message() {
	global $woocommerce;
	
	// Output success messages
	if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
		
		$return_to = (wp_get_referer()) ? wp_get_referer() : home_url();

		$woocommerce->add_message( sprintf('<a href="%s" class="button">%s</a> %s', $return_to, __('Continue Shopping &rarr;', 'woocommerce'), __('Product successfully added to your cart.', 'woocommerce') ));
	
	else :
		$woocommerce->add_message( sprintf('<a href="%s" class="button">%s</a> %s', $return_to, __('View Cart &rarr;', 'woocommerce'), __('Product successfully added to your cart.', 'woocommerce') ));
	endif;
}

/**
 * Clear cart after payment
 **/
function woocommerce_clear_cart_after_payment() {
	global $woocommerce;
	
	if (is_page(woocommerce_get_page_id('thanks'))) :
	
		if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
		if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';
		if ($order_id > 0) :
			$order = &new woocommerce_order( $order_id );
			if ($order->order_key == $order_key) :
			
				$woocommerce->cart->empty_cart();
				
				unset($_SESSION['order_awaiting_payment']);
				
			endif;
		endif;
		
	endif;
	
	if (isset($_SESSION['order_awaiting_payment']) && $_SESSION['order_awaiting_payment'] > 0) :
		
		$order = &new woocommerce_order($_SESSION['order_awaiting_payment']);
		
		if ($order->id > 0 && $order->status!=='pending') :
			
			$woocommerce->cart->empty_cart();
			
			unset($_SESSION['order_awaiting_payment']);
			
		endif;
		
	endif;
}

/**
 * Process the login form
 **/
function woocommerce_process_login() {
	
	global $woocommerce;
	
	if (isset($_POST['login']) && $_POST['login']) :
	
		$woocommerce->verify_nonce('login');

		if ( !isset($_POST['username']) || empty($_POST['username']) ) $woocommerce->add_error( __('Username is required.', 'woocommerce') );
		if ( !isset($_POST['password']) || empty($_POST['password']) ) $woocommerce->add_error( __('Password is required.', 'woocommerce') );
		
		if ($woocommerce->error_count()==0) :
			
			$creds = array();
			$creds['user_login'] = $_POST['username'];
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
 * Process the coupon form on the checkout and cart
 **/
function woocommerce_process_coupon_form() {
	global $woocommerce;
	
	if (isset($_POST['coupon_code']) && $_POST['coupon_code']) :
	
		$coupon_code = stripslashes(trim($_POST['coupon_code']));
		$woocommerce->cart->add_discount($coupon_code);
		
		if ( wp_get_referer() ) :
			wp_safe_redirect( remove_query_arg('remove_discounts', wp_get_referer()) );
			exit;
		endif;
	
	endif;	
}

/**
 * Process the registration form
 **/
function woocommerce_process_registration() {
	
	global $woocommerce;
	
	if (isset($_POST['register']) && $_POST['register']) :
	
		$woocommerce->verify_nonce('register');
		
		// Get fields
		$sanitized_user_login 	= (isset($_POST['username'])) ? sanitize_user(trim($_POST['username'])) : '';
		$user_email 		= (isset($_POST['email'])) ? esc_attr(trim($_POST['email'])) : '';
		$password	= (isset($_POST['password'])) ? esc_attr(trim($_POST['password'])) : '';
		$password2 	= (isset($_POST['password2'])) ? esc_attr(trim($_POST['password2'])) : '';
		
		$user_email = apply_filters( 'user_registration_email', $user_email );
		
		// Check the username
		if ( $sanitized_user_login == '' ) {
			$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __( 'Please enter a username.', 'woocommerce' ) );
		} elseif ( ! validate_username( $_POST['username'] ) ) {
			$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'woocommerce' ) );
			$sanitized_user_login = '';
		} elseif ( username_exists( $sanitized_user_login ) ) {
			$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __( 'This username is already registered, please choose another one.', 'woocommerce' ) );
		}
	
		// Check the e-mail address
		if ( $user_email == '' ) {
			$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __( 'Please type your e-mail address.', 'woocommerce' ) );
		} elseif ( ! is_email( $user_email ) ) {
			$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __( 'The email address isn&#8217;t correct.', 'woocommerce' ) );
			$user_email = '';
		} elseif ( email_exists( $user_email ) ) {
			$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __( 'This email is already registered, please choose another one.', 'woocommerce' ) );
		}
	
		// Password
		if ( !$password ) $woocommerce->add_error( __('Password is required.', 'woocommerce') );
		if ( !$password2 ) $woocommerce->add_error( __('Re-enter your password.', 'woocommerce') );
		if ( $password != $password2 ) $woocommerce->add_error( __('Passwords do not match.', 'woocommerce') );
		
		// Spam trap
		if (isset($_POST['email_2']) && $_POST['email_2']) $woocommerce->add_error( __('Anti-spam field was filled in.', 'woocommerce') );
		
		if ($woocommerce->error_count()==0) :
			
			$reg_errors = new WP_Error();
			do_action('register_post', $sanitized_user_login, $user_email, $reg_errors);
			$reg_errors = apply_filters( 'registration_errors', $reg_errors, $sanitized_user_login, $user_email );
	
            // if there are no errors, let's create the user account
			if ( !$reg_errors->get_error_code() ) :

                $user_id 	= wp_create_user( $sanitized_user_login, $password, $user_email );
                
                if ( !$user_id ) {
                	$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __('Couldn&#8217;t register you... please contact us if you continue to have problems.', 'woocommerce') );
                    return;
                }

                // Change role
                wp_update_user( array ('ID' => $user_id, 'role' => 'customer') ) ;
				
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
 * Cancel a pending order
 **/
function woocommerce_cancel_order() {
	
	global $woocommerce;
	
	if ( isset($_GET['cancel_order']) && isset($_GET['order']) && isset($_GET['order_id']) ) :
		
		$order_key = urldecode( $_GET['order'] );
		$order_id = (int) $_GET['order_id'];
		
		$order = &new woocommerce_order( $order_id );

		if ($order->id == $order_id && $order->order_key == $order_key && in_array($order->status, array('pending', 'failed')) && $woocommerce->verify_nonce('cancel_order', '_GET')) :
			
			// Cancel the order + restore stock
			$order->cancel_order( __('Order cancelled by customer.', 'woocommerce') );
			
			// Message
			$woocommerce->add_message( __('Your order was cancelled.', 'woocommerce') );
		
		elseif ($order->status!='pending') :
			
			$woocommerce->add_error( __('Your order is no longer pending and could not be cancelled. Please contact us if you need assistance.', 'woocommerce') );
			
		else :
		
			$woocommerce->add_error( __('Invalid order.', 'woocommerce') );
			
		endif;
		
		wp_safe_redirect($woocommerce->cart->get_cart_url());
		exit;
		
	endif;
}

/**
 * Download a file - hook into init function
 **/
function woocommerce_download_product() {
	
	if ( isset($_GET['download_file']) && isset($_GET['order']) && isset($_GET['email']) ) :
	
		global $wpdb;
		
		$download_file = (int) urldecode($_GET['download_file']);
		$order_key = urldecode( $_GET['order'] );
		$email = urldecode( $_GET['email'] );
		
		if (!is_email($email)) :
			wp_die( __('Invalid email address.', 'woocommerce') . ' <a href="'.home_url().'">' . __('Go to homepage &rarr;', 'woocommerce') . '</a>' );
		endif;
		
		$download_result = $wpdb->get_row( $wpdb->prepare("
			SELECT order_id, downloads_remaining 
			FROM ".$wpdb->prefix."woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s
		;", $email, $order_key, $download_file ) );
		
		if (!$download_result) :
			wp_die( __('Invalid download.', 'woocommerce') . ' <a href="'.home_url().'">' . __('Go to homepage &rarr;', 'woocommerce') . '</a>' );
			exit;
		endif;
		
		$order_id = $download_result->order_id;
		$downloads_remaining = $download_result->downloads_remaining;
		
		if ($order_id) :
			$order = &new woocommerce_order( $order_id );
			if ($order->status!='completed' && $order->status!='processing' && $order->status!='publish') :
				wp_die( __('Invalid order.', 'woocommerce') . ' <a href="'.home_url().'">' . __('Go to homepage &rarr;', 'woocommerce') . '</a>' );
				exit;
			endif;
		endif;
		
		if ($downloads_remaining=='0') :
			wp_die( __('Sorry, you have reached your download limit for this file', 'woocommerce') . ' <a href="'.home_url().'">' . __('Go to homepage &rarr;', 'woocommerce') . '</a>' );
		else :
			
			if ($downloads_remaining>0) :
				$wpdb->update( $wpdb->prefix . "woocommerce_downloadable_product_permissions", array( 
					'downloads_remaining' => $downloads_remaining - 1, 
				), array( 
					'user_email' => $email,
					'order_key' => $order_key,
					'product_id' => $download_file 
				), array( '%d' ), array( '%s', '%s', '%d' ) );
			endif;
			
			// Get the downloads URL and try to replace the url with a path
			$file_path = get_post_meta($download_file, '_file_path', true);	
			
			if (!$file_path) exit;
			
			$file_download_method = apply_filters('woocommerce_file_download_method', get_option('woocommerce_file_download_method'), $download_file);
			
			if ($file_download_method=='redirect') :
				
				header('Location: '.$file_path);
				exit;
				
			endif;
			
			// Get URLS with https
			$site_url = site_url();
			$network_url = network_admin_url();
			if (is_ssl()) :
				$site_url = str_replace('https:', 'http:', $site_url);
				$network_url = str_replace('https:', 'http:', $network_url);
			endif;
			
			if (!is_multisite()) :	
				$file_path = str_replace(trailingslashit($site_url), ABSPATH, $file_path);
			else :
				$upload_dir = wp_upload_dir();
				
				// Try to replace network url
				$file_path = str_replace(trailingslashit($network_url), ABSPATH, $file_path);
				
				// Now try to replace upload URL
				$file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_path);
			endif;
			
			// See if its local or remote
			if (strstr($file_path, 'http:') || strstr($file_path, 'https:') || strstr($file_path, 'ftp:')) :
				$remote_file = true;
			else :
				$remote_file = false;
				$file_path = realpath($file_path);
			endif;
			
			// Download the file
			$file_extension = strtolower(substr(strrchr($file_path,"."),1));
			
			$ctype = "application/force-download";
			
			foreach (get_allowed_mime_types() as $mime => $type) :
				$mimes = explode('|', $mime);
				if (in_array($file_extension, $mimes)) :
					$ctype = $type;
					break;
				endif;
			endforeach;
            
			if ($file_download_method=='xsendfile') :
             	
             	if (getcwd()) :
             		// Path fix - kudos to Jason Judge
             		$file_path = trim(preg_replace( '`^' . getcwd() . '`' , '', $file_path ), '/');
             	endif;
             	
	            header("Content-Disposition: attachment; filename=\"".basename($file_path)."\";");
	            
	            if (function_exists('apache_get_modules') && in_array( 'mod_xsendfile', apache_get_modules()) ) :
	            	
	            	header("X-Sendfile: $file_path");
	            	exit;
	            	
	            elseif (stristr(getenv('SERVER_SOFTWARE'), 'lighttpd') ) :
	            
	            	header("X-Lighttpd-Sendfile: $file_path");
	            	exit;
	            
	            elseif (stristr(getenv('SERVER_SOFTWARE'), 'nginx') || stristr(getenv('SERVER_SOFTWARE'), 'cherokee')) :
	            
	            	header("X-Accel-Redirect: $file_path");
	            	exit;
	            
	            endif;
	            
	        endif;

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
			if ( ! function_exists('readfile_chunked')) {
			    function readfile_chunked($file, $retbytes=TRUE) {
			    
					$chunksize = 1 * (1024 * 1024);
					$buffer = '';
					$cnt = 0;
					
					$handle = fopen($file, 'r');
					if ($handle === FALSE) return FALSE;
							
					while (!feof($handle)) :
					   $buffer = fread($handle, $chunksize);
					   echo $buffer;
					   ob_flush();
					   flush();
					
					   if ($retbytes) $cnt += strlen($buffer);
					endwhile;
					
					$status = fclose($handle);
					
					if ($retbytes AND $status) return $cnt;
					
					return $status;
			    }
			}

            @session_write_close();
            if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
            @ini_set('zlib.output_compression', 'Off');
			@set_time_limit(0);
			@set_magic_quotes_runtime(0);
			@ob_end_clean();
			if (ob_get_level()) @ob_end_clean(); // Zip corruption fix
			
			header("Pragma: no-cache");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Robots: none");
			header("Content-Type: ".$ctype."");
			header("Content-Description: File Transfer");	
			header("Content-Disposition: attachment; filename=\"".basename($file_path)."\";");	
			header("Content-Transfer-Encoding: binary");
							
            if ($size = @filesize($file_path)) header("Content-Length: ".$size);

            // Serve it
            if ($remote_file) :
            	
            	@readfile_chunked("$file_path") or header('Location: '.$file_path);
            	
            else :
            	
            	@readfile_chunked("$file_path") or wp_die( __('File not found', 'woocommerce') . ' <a href="'.home_url().'">' . __('Go to homepage &rarr;', 'woocommerce') . '</a>' );
			
            endif;
            
            exit;
			
		endif;
		
	endif;
}

/**
 * Google Analytics standard tracking
 **/
function woocommerce_google_tracking() {
	global $woocommerce;
	
	if (!get_option('woocommerce_ga_standard_tracking_enabled')) return;
	if (is_admin()) return; // Don't track admin
	
	$tracking_id = get_option('woocommerce_ga_id');
	
	if (!$tracking_id) return;
	
	$loggedin 	= (is_user_logged_in()) ? 'yes' : 'no';
	if (is_user_logged_in()) :
		$user_id 		= get_current_user_id();
		$current_user 	= get_user_by('id', $user_id);
		$username 		= $current_user->user_login;
	else :
		$user_id 		= '';
		$username 		= __('Guest', 'woocommerce');
	endif;
	?>
	<script type="text/javascript">
	
		var _gaq = _gaq || [];
		_gaq.push(
			['_setAccount', '<?php echo $tracking_id; ?>'],
			['_setCustomVar', 1, 'logged-in', '<?php echo $loggedin; ?>', 1],
			['_setCustomVar', 2, 'user-id', '<?php echo $user_id; ?>', 1],
			['_setCustomVar', 3, 'username', '<?php echo $username; ?>', 1],
			['_trackPageview']
		);
		
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();

	</script>
	<?php
}
			
/**
 * Google Analytics eCommerce tracking
 **/
function woocommerce_ecommerce_tracking( $order_id ) {
	global $woocommerce;
	
	if (!get_option('woocommerce_ga_ecommerce_tracking_enabled')) return;
	if (current_user_can('manage_options')) return; // Don't track admin
	
	$tracking_id = get_option('woocommerce_ga_id');
	
	if (!$tracking_id) return;
	
	// Doing eCommerce tracking so unhook standard tracking from the footer
	remove_action('wp_footer', 'woocommerce_google_tracking');
	
	// Get the order and output tracking code
	$order = &new woocommerce_order($order_id);
	
	$loggedin 	= (is_user_logged_in()) ? 'yes' : 'no';
	if (is_user_logged_in()) :
		$user_id 		= get_current_user_id();
		$current_user 	= get_user_by('id', $user_id);
		$username 		= $current_user->user_login;
	else :
		$user_id 		= '';
		$username 		= __('Guest', 'woocommerce');
	endif;
	?>
	<script type="text/javascript">
		var _gaq = _gaq || [];
		
		_gaq.push(
			['_setAccount', '<?php echo $tracking_id; ?>'],
			['_setCustomVar', 1, 'logged-in', '<?php echo $loggedin; ?>', 1],
			['_setCustomVar', 2, 'user-id', '<?php echo $user_id; ?>', 1],
			['_setCustomVar', 3, 'username', '<?php echo $username; ?>', 1],
			['_trackPageview']
		);
		
		_gaq.push(['_addTrans',
			'<?php echo $order_id; ?>',           		// order ID - required
			'<?php bloginfo('name'); ?>',  				// affiliation or store name
			'<?php echo $order->order_total; ?>',   	// total - required
			'<?php echo $order->get_total_tax(); ?>',   // tax
			'<?php echo $order->get_shipping(); ?>',	// shipping
			'<?php echo $order->billing_city; ?>',      // city
			'<?php echo $order->billing_state; ?>',     // state or province
			'<?php echo $order->billing_country; ?>'    // country
		]);
		
		// Order items
		<?php if ($order->items) foreach($order->items as $item) : $_product = $order->get_product_from_item( $item ); ?>
			_gaq.push(['_addItem',
				'<?php echo $order_id; ?>',           	// order ID - required
				'<?php echo $_product->sku; ?>',      	// SKU/code - required
				'<?php echo $item['name']; ?>',        	// product name
				'<?php if (isset($_product->variation_data)) echo woocommerce_get_formatted_variation( $_product->variation_data, true ); ?>',   // category or variation
				'<?php echo ($item['line_cost']/$item['qty']); ?>',         // unit price - required
				'<?php echo $item['qty']; ?>'           // quantity - required
			]);
		<?php endforeach; ?>
		
		_gaq.push(['_trackTrans']); 					// submits transaction to the Analytics servers
		
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
	<?php
} 

/**
 * ecommerce tracking with piwik
 */
function woocommerce_ecommerce_tracking_piwik( $order_id ) {
	global $woocommerce;
	
	if (is_admin()) return; // Don't track admin
	
	// Call the Piwik ecommerce function if WP-Piwik is configured to add tracking codes to the page
	$wp_piwik_global_settings = get_option('wp-piwik_global-settings');
	
	if (!isset($wp_piwik_global_settings['add_tracking_code']) || !$wp_piwik_global_settings['add_tracking_code']) return;
	
	// Remove WP-Piwik from wp_footer and run it here instead, to get Piwik 
	// loaded *before* we do our ecommerce tracking calls
	remove_action('wp_footer', array($GLOBALS['wp_piwik'],'footer'));
	$GLOBALS['wp_piwik']->footer();
	
	// Get the order and output tracking code
	$order = &new woocommerce_order($order_id);
	?>
	<script type="text/javascript">
	try {
		// Add order items
		<?php if ($order->items) foreach($order->items as $item) : $_product = $order->get_product_from_item( $item ); ?>
			piwikTracker.addEcommerceItem(
				"<?php echo $_product->sku; ?>",	// (required) SKU: Product unique identifier
				"<?php echo $item['name']; ?>",		// (optional) Product name
				"<?php if (isset($_product->variation_data)) echo woocommerce_get_formatted_variation( $_product->variation_data, true ); ?>",	// (optional) Product category. You can also specify an array of up to 5 categories eg. ["Books", "New releases", "Biography"]
				<?php echo ($item['line_cost']/$item['qty']); ?>,		// (recommended) Product price
				<?php echo $item['qty']; ?> 		// (optional, default to 1) Product quantity
			);
		<?php endforeach; ?>
		// Track order
		piwikTracker.trackEcommerceOrder(
			"<?php echo $order_id; ?>",		// (required) Unique Order ID
			<?php echo $order->order_total; ?>,	// (required) Order Revenue grand total (includes tax, shipping, and subtracted discount)
			false,					// (optional) Order sub total (excludes shipping)
			<?php echo $order->get_total_tax(); ?>,	// (optional) Tax amount
			<?php echo $order->get_shipping(); ?>,	// (optional) Shipping amount
			false 					// (optional) Discount offered (set to false for unspecified parameter)
		);
	} catch( err ) {}
	</script>
	<?php
} 

/* Products RSS Feed */
function woocommerce_products_rss_feed() {
	// Product RSS
	if ( is_post_type_archive( 'product' ) || is_singular( 'product' ) ) :
		
		$feed = get_post_type_archive_feed_link( 'product' );

		echo '<link rel="alternate" type="application/rss+xml"  title="' . __('New products', 'woocommerce') . '" href="' . $feed . '" />';
	
	elseif ( is_tax( 'product_cat' ) ) :
		
		$term = get_term_by('slug', get_query_var('product_cat'), 'product_cat');
		
		$feed = add_query_arg('product_cat', $term->slug, get_post_type_archive_feed_link( 'product' ));
		
		echo '<link rel="alternate" type="application/rss+xml"  title="' . sprintf(__('New products added to %s', 'woocommerce'), urlencode($term->name)) . '" href="' . $feed . '" />';
		
	elseif ( is_tax( 'product_tag' ) ) :
		
		$term = get_term_by('slug', get_query_var('product_tag'), 'product_tag');
		
		$feed = add_query_arg('product_tag', $term->slug, get_post_type_archive_feed_link( 'product' ));
		
		echo '<link rel="alternate" type="application/rss+xml"  title="' . sprintf(__('New products tagged %s', 'woocommerce'), urlencode($term->name)) . '" href="' . $feed . '" />';
		
	endif;
}

/**
 * Rating field for comments
 **/
function woocommerce_add_comment_rating($comment_id) {
	if ( isset($_POST['rating']) ) :
		global $post;
		if (!$_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0) $_POST['rating'] = 5; 
		add_comment_meta( $comment_id, 'rating', esc_attr($_POST['rating']), true );
		delete_transient( esc_attr($post->ID) . '_woocommerce_average_rating' );
	endif;
}

function woocommerce_check_comment_rating($comment_data) {
	global $woocommerce;
	
	// If posting a comment (not trackback etc) and not logged in
	if ( isset($_POST['rating']) && !$woocommerce->verify_nonce('comment_rating') )
		wp_die( __('You have taken too long. Please go back and refresh the page.', 'woocommerce') );
		
	elseif ( isset($_POST['rating']) && empty($_POST['rating']) && $comment_data['comment_type']== '' ) {
		wp_die( __('Please rate the product.', 'woocommerce') );
		exit;
	}
	return $comment_data;
}
