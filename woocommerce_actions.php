<?php
/**
 * WooCommerce Actions
 * 
 * Actions/functions/hooks for WooCommerce related events.
 *
 *		- Clear cart on logout
 *		- Update catalog ordering if posted
 *		- Increase coupon usage count
 *		- When default permalinks are enabled, redirect shop page to post type archive url
 *		- Add to Cart
 *		- Clear cart
 *		- Restore an order via a link
 *		- Cancel a pending order
 *		- Download a file
 *		- Order Status completed - allow customer to access Downloadable product
 *		- Google Analytics standard tracking
 *		- Google Analytics eCommerce tracking
 *		- Products RSS Feed
 *
 * @package		WooCommerce
 * @category	Actions
 * @author		WooThemes
 */

/**
 * Clear cart on logout
 */
add_action('wp_logout', 'woocommerce_clear_cart_on_logout');

function woocommerce_clear_cart_on_logout() {
	
	if (get_option('woocommerce_clear_cart_on_logout')=='yes') :
		
		global $woocommerce;
		
		$woocommerce->cart->empty_cart();
		
	endif;
}

/**
 * Update catalog ordering if posted
 */
add_action('init', 'woocommerce_update_catalog_ordering');

function woocommerce_update_catalog_ordering() {
	if (isset($_POST['catalog_orderby']) && $_POST['catalog_orderby'] != '') $_SESSION['orderby'] = $_POST['catalog_orderby'];
}



/**
 * Increase coupon usage count
 */
add_action('woocommerce_new_order', 'woocommerce_increase_coupon_counts');

function woocommerce_increase_coupon_counts() {
	global $woocommerce;
	if ($applied_coupons = $woocommerce->cart->get_applied_coupons()) foreach ($applied_coupons as $code) :
		$coupon = &new woocommerce_coupon( $code );
		$coupon->inc_usage_count();
	endforeach;
}


/**
 * When default permalinks are enabled, redirect shop page to post type archive url
 **/
if (get_option( 'permalink_structure' )=="") add_action( 'init', 'woocommerce_shop_page_archive_redirect' );

function woocommerce_shop_page_archive_redirect() {
	
	if ( isset($_GET['page_id']) && $_GET['page_id'] == get_option('woocommerce_shop_page_id') ) :
		wp_safe_redirect( get_post_type_archive_link('product') );
		exit;
	endif;
	
}

/**
 * Remove from cart/update
 **/
add_action( 'init', 'woocommerce_update_cart_action' );

function woocommerce_update_cart_action() {
	
	global $woocommerce;
	
	// Remove from cart
	if ( isset($_GET['remove_item']) && $_GET['remove_item'] && $woocommerce->verify_nonce('cart', '_GET')) :
	
		$woocommerce->cart->set_quantity( $_GET['remove_item'], 0 );
		
		$woocommerce->add_message( __('Cart updated.', 'woothemes') );
		
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
		
		$woocommerce->add_message( __('Cart updated.', 'woothemes') );
		
	endif;

}

/**
 * Add to cart
 **/
add_action( 'init', 'woocommerce_add_to_cart_action' );

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
            
            $woocommerce->add_error( __('Please choose product options&hellip;', 'woothemes') );
            wp_safe_redirect(get_permalink($_GET['product']));
            exit;
            
       else :
			
			$product_id 	= (int) $_GET['product'];
			$variation_id 	= (int) $_POST['variation_id'];
			$quantity 		= (isset($_POST['quantity'])) ? (int) $_POST['quantity'] : 1;
			
            $attributes = (array) maybe_unserialize(get_post_meta($product_id, 'product_attributes', true));
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
                $woocommerce->add_error( __('Please choose product options&hellip;', 'woothemes') );
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
				$woocommerce->add_error( __('Please choose a quantity&hellip;', 'woothemes') );
			endif;
		
		elseif ($_GET['product']) :
			
			/* Link on product pages */
			$woocommerce->add_error( __('Please choose a product&hellip;', 'woothemes') );
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
add_action( 'init', 'woocommerce_add_to_cart_action' );

function woocommerce_add_to_cart_message() {
	global $woocommerce;
	
	// Output success messages
	if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
		
		$return_to = (wp_get_referer()) ? wp_get_referer() : home_url();

		$woocommerce->add_message( sprintf(__('<a href="%s" class="button">Continue Shopping &rarr;</a> Product successfully added to your cart.', 'woothemes'), $return_to ));
	
	else :
		$woocommerce->add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your cart.', 'woothemes'), $woocommerce->cart->get_cart_url()) );
	endif;
}


/**
 * Clear cart
 **/
add_action( 'wp', 'woocommerce_clear_cart_on_return' );

function woocommerce_clear_cart_on_return() {
	global $woocommerce;
	
	if (is_page(get_option('woocommerce_thanks_page_id'))) :
	
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

}

/**
 * Clear the cart after payment - order will be processing or complete
 **/
add_action( 'init', 'woocommerce_clear_cart_after_payment' );

function woocommerce_clear_cart_after_payment( $url = false ) {
	global $woocommerce;
	
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
add_action('init', 'woocommerce_process_login');
 
function woocommerce_process_login() {
	
	global $woocommerce;
	
	if (isset($_POST['login']) && $_POST['login']) :
	
		$woocommerce->verify_nonce('login');

		if ( !isset($_POST['username']) || empty($_POST['username']) ) $woocommerce->add_error( __('Username is required.', 'woothemes') );
		if ( !isset($_POST['password']) || empty($_POST['password']) ) $woocommerce->add_error( __('Password is required.', 'woothemes') );
		
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
				if ( wp_get_referer() ) :
					wp_safe_redirect( wp_get_referer() );
					exit;
				endif;
				wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
				exit;
			endif;
			
		endif;
	
	endif;	
}


/**
 * Process the registration form
 **/
add_action('init', 'woocommerce_process_registration');
 
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
			$woocommerce->add_error( __( '<strong>ERROR</strong>: Please enter a username.', 'woothemes' ) );
		} elseif ( ! validate_username( $_POST['username'] ) ) {
			$woocommerce->add_error( __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.', 'woothemes' ) );
			$sanitized_user_login = '';
		} elseif ( username_exists( $sanitized_user_login ) ) {
			$woocommerce->add_error( __( '<strong>ERROR</strong>: This username is already registered, please choose another one.', 'woothemes' ) );
		}
	
		// Check the e-mail address
		if ( $user_email == '' ) {
			$woocommerce->add_error( __( '<strong>ERROR</strong>: Please type your e-mail address.', 'woothemes' ) );
		} elseif ( ! is_email( $user_email ) ) {
			$woocommerce->add_error( __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.', 'woothemes' ) );
			$user_email = '';
		} elseif ( email_exists( $user_email ) ) {
			$woocommerce->add_error( __( '<strong>ERROR</strong>: This email is already registered, please choose another one.', 'woothemes' ) );
		}
	
		// Password
		if ( !$password ) $woocommerce->add_error( __('Password is required.', 'woothemes') );
		if ( !$password2 ) $woocommerce->add_error( __('Re-enter your password.', 'woothemes') );
		if ( $password != $password2 ) $woocommerce->add_error( __('Passwords do not match.', 'woothemes') );
		
		// Spam trap
		if (isset($_POST['email_2']) && $_POST['email_2']) $woocommerce->add_error( __('Anti-spam field was filled in.', 'woothemes') );
		
		if ($woocommerce->error_count()==0) :
			
			$reg_errors = new WP_Error();
			do_action('register_post', $sanitized_user_login, $user_email, $reg_errors);
			$reg_errors = apply_filters( 'registration_errors', $reg_errors, $sanitized_user_login, $user_email );
	
            // if there are no errors, let's create the user account
			if ( !$reg_errors->get_error_code() ) :

                $user_id 	= wp_create_user( $sanitized_user_login, $password, $user_email );
                
                if ( !$user_id ) {
                	$woocommerce->add_error( sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'woothemes'), get_option('admin_email')));
                    return;
                }

                // Change role
                wp_update_user( array ('ID' => $user_id, 'role' => 'customer') ) ;

                // send the user a confirmation and their login details
                woocommerce_customer_new_account( $user_id, $password );

                // set the WP login cookie
                $secure_cookie = is_ssl() ? true : false;
                wp_set_auth_cookie($user_id, true, $secure_cookie);
                
                // Redirect
                if ( wp_get_referer() ) :
					wp_safe_redirect( wp_get_referer() );
					exit;
				endif;
				wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
				exit;
			
			else :
				$woocommerce->add_error( $reg_errors->get_error_message() );
            	return;                 
			endif;
			
		endif;
	
	endif;	
}


/**
 * Cancel a pending order - hook into init function
 **/
add_action('init', 'woocommerce_cancel_order');

function woocommerce_cancel_order() {
	
	global $woocommerce;
	
	if ( isset($_GET['cancel_order']) && isset($_GET['order']) && isset($_GET['order_id']) ) :
		
		$order_key = urldecode( $_GET['order'] );
		$order_id = (int) $_GET['order_id'];
		
		$order = &new woocommerce_order( $order_id );

		if ($order->id == $order_id && $order->order_key == $order_key && in_array($order->status, array('pending', 'failed')) && $woocommerce->verify_nonce('cancel_order', '_GET')) :
			
			// Cancel the order + restore stock
			$order->cancel_order( __('Order cancelled by customer.', 'woothemes') );
			
			// Message
			$woocommerce->add_message( __('Your order was cancelled.', 'woothemes') );
		
		elseif ($order->status!='pending') :
			
			$woocommerce->add_error( __('Your order is no longer pending and could not be cancelled. Please contact us if you need assistance.', 'woothemes') );
			
		else :
		
			$woocommerce->add_error( __('Invalid order.', 'woothemes') );
			
		endif;
		
		wp_safe_redirect($woocommerce->cart->get_cart_url());
		exit;
		
	endif;
}


/**
 * Download a file - hook into init function
 **/
add_action('init', 'woocommerce_download_product');

function woocommerce_download_product() {
	
	if ( isset($_GET['download_file']) && isset($_GET['order']) && isset($_GET['email']) ) :
	
		global $wpdb;
		
		$download_file = (int) urldecode($_GET['download_file']);
		$order_key = urldecode( $_GET['order'] );
		$email = urldecode( $_GET['email'] );
		
		if (!is_email($email)) :
			wp_die( sprintf(__('Invalid email address. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
		endif;
		
		$download_result = $wpdb->get_row( $wpdb->prepare("
			SELECT order_id, downloads_remaining 
			FROM ".$wpdb->prefix."woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s
		;", $email, $order_key, $download_file ) );
		
		if (!$download_result) :
			wp_die( sprintf(__('Invalid download. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
			exit;
		endif;
		
		$order_id = $download_result->order_id;
		$downloads_remaining = $download_result->downloads_remaining;
		
		if ($order_id) :
			$order = &new woocommerce_order( $order_id );
			if ($order->status!='completed' && $order->status!='processing') :
				wp_die( sprintf(__('Invalid order. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
				exit;
			endif;
		endif;
		
		if ($downloads_remaining=='0') :
			wp_die( sprintf(__('Sorry, you have reached your download limit for this file. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
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
			$file_path = get_post_meta($download_file, 'file_path', true);	
			
			if (!$file_path) exit;
			
			$file_download_method = apply_filters('woocommerce_file_download_method', get_option('woocommerce_file_download_method'), $download_file);
			
			if ($file_download_method=='redirect') :
				
				header('Location: '.$file_path);
				exit;
				
			endif;
			
			if (!is_multisite()) :	
				$file_path = str_replace(trailingslashit(site_url()), ABSPATH, $file_path);
			else :
				$upload_dir = wp_upload_dir();
				
				// Try to replace network url
				$file_path = str_replace(trailingslashit(network_admin_url()), ABSPATH, $file_path);
				
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

            switch ($file_extension) :
                case "pdf": $ctype="application/pdf"; break;
                case "exe": $ctype="application/octet-stream"; break;
                case "zip": $ctype="application/zip"; break;
                case "doc": $ctype="application/msword"; break;
                case "xls": $ctype="application/vnd.ms-excel"; break;
                case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
                case "gif": $ctype="image/gif"; break;
                case "png": $ctype="image/png"; break;
                case "jpe": case "jpeg": case "jpg": $ctype="image/jpg"; break;
                default: $ctype="application/force-download";
            endswitch;
            
            if ($file_download_method=='xsendfile') :
            
				header("X-Accel-Redirect: $file_path");
				header("X-Sendfile: $file_path");
				header("Content-Type: $ctype");
				header("Content-Disposition: attachment; filename=\"".basename($file_path)."\";");
				exit;

            endif;
            
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
            	
            	@readfile_chunked("$file_path") or wp_die( sprintf(__('File not found. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
			
            endif;
            
            exit;
			
		endif;
		
	endif;
}


/**
 * Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 **/
add_action('woocommerce_order_status_completed', 'woocommerce_downloadable_product_permissions');

function woocommerce_downloadable_product_permissions( $order_id ) {
	
	global $wpdb;
	
	$order = &new woocommerce_order( $order_id );
	
	if (sizeof($order->items)>0) foreach ($order->items as $item) :
	
		if ($item['id']>0) :
			$_product = $order->get_product_from_item( $item );
			
			if ( $_product->exists && $_product->is_downloadable() ) :
			
				$download_id = ($item['variation_id']>0) ? $item['variation_id'] : $item['id'];
				
				$user_email = $order->billing_email;
				
				if ($order->user_id>0) :
					$user_info = get_userdata($order->user_id);
					if ($user_info->user_email) :
						$user_email = $user_info->user_email;
					endif;
				else :
					$order->user_id = 0;
				endif;
				
				$limit = trim(get_post_meta($download_id, 'download_limit', true));
				
				if (!empty($limit)) :
					$limit = (int) $limit;
				else :
					$limit = '';
				endif;
				
				// Downloadable product - give access to the customer
				$wpdb->insert( $wpdb->prefix . 'woocommerce_downloadable_product_permissions', array( 
					'product_id' => $download_id, 
					'user_id' => $order->user_id,
					'user_email' => $user_email,
					'order_id' => $order->id,
					'order_key' => $order->order_key,
					'downloads_remaining' => $limit
				), array( 
					'%s', 
					'%s', 
					'%s', 
					'%s', 
					'%s',
					'%s'
				) );	
				
			endif;
			
		endif;
	
	endforeach;
}


/**
 * Google Analytics standard tracking
 **/
add_action('wp_footer', 'woocommerce_google_tracking');

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
		$username 		= __('Guest', 'woothemes');
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
add_action('woocommerce_thankyou', 'woocommerce_ecommerce_tracking');

function woocommerce_ecommerce_tracking( $order_id ) {
	global $woocommerce;
	
	if (!get_option('woocommerce_ga_ecommerce_tracking_enabled')) return;
	if (is_admin()) return; // Don't track admin
	
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
		$username 		= __('Guest', 'woothemes');
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
			'<?php echo $order->order_tax; ?>',     	// tax
			'<?php echo $order->order_shipping; ?>',	// shipping
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
				'<?php echo $item['cost']; ?>',         // unit price - required
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

/* Products RSS Feed */
add_action( 'wp_head', 'woocommerce_products_rss_feed' );

function woocommerce_products_rss_feed() {
	
	// Product RSS
	if ( is_post_type_archive( 'product' ) || is_singular( 'product' ) ) :
		
		$feed = get_post_type_archive_feed_link( 'product' );

		echo '<link rel="alternate" type="application/rss+xml"  title="' . __('New products', 'woothemes') . '" href="' . $feed . '" />';
	
	elseif ( is_tax( 'product_cat' ) ) :
		
		$term = get_term_by('slug', get_query_var('product_cat'), 'product_cat');
		
		$feed = add_query_arg('product_cat', $term->slug, get_post_type_archive_feed_link( 'product' ));
		
		echo '<link rel="alternate" type="application/rss+xml"  title="' . sprintf(__('New products added to %s', 'woothemes'), urlencode($term->name)) . '" href="' . $feed . '" />';
		
	elseif ( is_tax( 'product_tag' ) ) :
		
		$term = get_term_by('slug', get_query_var('product_tag'), 'product_tag');
		
		$feed = add_query_arg('product_tag', $term->slug, get_post_type_archive_feed_link( 'product' ));
		
		echo '<link rel="alternate" type="application/rss+xml"  title="' . sprintf(__('New products tagged %s', 'woothemes'), urlencode($term->name)) . '" href="' . $feed . '" />';
		
	endif;

}
