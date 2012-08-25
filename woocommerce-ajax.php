<?php
/**
 * WooCommerce Ajax Handlers
 *
 * Handles AJAX requests via wp_ajax hook (both admin and front-end events)
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions/AJAX
 * @version     1.6.4
 */

/** Frontend AJAX events **************************************************/

/**
 * Process ajax login
 *
 * @access public
 * @return void
 */
function woocommerce_sidebar_login_ajax_process() {

	check_ajax_referer( 'woocommerce-sidebar-login-action', 'security' );

	// Get post data
	$creds = array();
	$creds['user_login'] 	= esc_attr($_REQUEST['user_login']);
	$creds['user_password'] = esc_attr($_REQUEST['user_password']);
	$creds['remember'] 		= 'forever';
	$redirect_to 			= esc_attr($_REQUEST['redirect_to']);

	// Check for Secure Cookie
	$secure_cookie = '';

	// If the user wants ssl but the session is not ssl, force a secure cookie.
	if ( ! force_ssl_admin() ) {
		$user_name = sanitize_user( $creds['user_login']  );
		if ( $user = get_user_by('login',  $user_name ) ) {
			if ( get_user_option( 'use_ssl', $user->ID ) ) {
				$secure_cookie = true;
				force_ssl_admin(true);
			}
		}
	}

	if ( force_ssl_admin() ) $secure_cookie = true;
	if ( $secure_cookie=='' && force_ssl_login() ) $secure_cookie = false;

	// Login
	$user = wp_signon( $creds, $secure_cookie );

	// Redirect filter
	if ( $secure_cookie && strstr($redirect_to, 'wp-admin') ) $redirect_to = str_replace('http:', 'https:', $redirect_to);

	// Result
	$result = array();

	if ( !is_wp_error($user) ) :
		$result['success'] = 1;
		$result['redirect'] = $redirect_to;
	else :
		$result['success'] = 0;
		if ( $user->errors ) {
			foreach ($user->errors as $error) {
				$result['error'] = $error[0];
				break;
			}
		} else {
			$result['error'] = __('Please enter your username and password to login.', 'woocommerce');
		}
	endif;

	header('content-type: application/json; charset=utf-8');

	echo $_GET['callback'] . '(' . json_encode( $result ) . ')';

	die();
}

add_action('wp_ajax_nopriv_woocommerce_sidebar_login_process', 'woocommerce_sidebar_login_ajax_process');


/**
 * AJAX apply coupon on checkout page
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_apply_coupon() {
	global $woocommerce;

	check_ajax_referer( 'apply-coupon', 'security' );

	if ( ! empty( $_POST['coupon_code'] ) ) {
		$woocommerce->cart->add_discount( stripslashes( trim( $_POST['coupon_code'] ) ) );
	} else {
		$woocommerce->add_error( __('Please enter a coupon code.', 'woocommerce') );
	}

	$woocommerce->show_messages();

	die();
}

add_action('wp_ajax_woocommerce_apply_coupon', 'woocommerce_ajax_apply_coupon');
add_action('wp_ajax_nopriv_woocommerce_apply_coupon', 'woocommerce_ajax_apply_coupon');


/**
 * AJAX update shipping method on cart page
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_update_shipping_method() {
	global $woocommerce;

	check_ajax_referer( 'update-shipping-method', 'security' );

	if ( ! defined('WOOCOMMERCE_CART') ) define( 'WOOCOMMERCE_CART', true );

	if ( isset( $_POST['shipping_method'] ) ) $_SESSION['_chosen_shipping_method'] = $_POST['shipping_method'];

	$woocommerce->cart->calculate_totals();

	woocommerce_cart_totals();

	die();
}

add_action('wp_ajax_woocommerce_update_shipping_method', 'woocommerce_ajax_update_shipping_method');
add_action('wp_ajax_nopriv_woocommerce_update_shipping_method', 'woocommerce_ajax_update_shipping_method');


/**
 * AJAX update order review on checkout
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_update_order_review() {
	global $woocommerce;

	check_ajax_referer( 'update-order-review', 'security' );

	if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);

	if (sizeof($woocommerce->cart->get_cart())==0) :
		echo '<div class="woocommerce_error">'.__('Sorry, your session has expired.', 'woocommerce').' <a href="'.home_url().'">'.__('Return to homepage &rarr;', 'woocommerce').'</a></div>';
		die();
	endif;

	do_action('woocommerce_checkout_update_order_review', $_POST['post_data']);

	if (isset($_POST['shipping_method'])) $_SESSION['_chosen_shipping_method'] = $_POST['shipping_method'];
	if (isset($_POST['payment_method'])) $_SESSION['_chosen_payment_method'] = $_POST['payment_method'];
	if (isset($_POST['country'])) $woocommerce->customer->set_country( $_POST['country'] );
	if (isset($_POST['state'])) $woocommerce->customer->set_state( $_POST['state'] );
	if (isset($_POST['postcode'])) $woocommerce->customer->set_postcode( $_POST['postcode'] );
	if (isset($_POST['s_country'])) $woocommerce->customer->set_shipping_country( $_POST['s_country'] );
	if (isset($_POST['s_state'])) $woocommerce->customer->set_shipping_state( $_POST['s_state'] );
	if (isset($_POST['s_postcode'])) $woocommerce->customer->set_shipping_postcode( $_POST['s_postcode'] );

	$woocommerce->cart->calculate_totals();

	do_action('woocommerce_checkout_order_review'); // Display review order table

	die();
}

add_action('wp_ajax_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');
add_action('wp_ajax_nopriv_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');


/**
 * AJAX add to cart
 *
 * @access public
 * @return void
 */
function woocommerce_ajax_add_to_cart() {

	global $woocommerce;

	check_ajax_referer( 'add-to-cart', 'security' );

	$product_id = (int) apply_filters('woocommerce_add_to_cart_product_id', $_POST['product_id']);

	$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, 1);

	if ($passed_validation && $woocommerce->cart->add_to_cart($product_id, 1)) :
		// Return html fragments
		$data = apply_filters('add_to_cart_fragments', array());
		do_action( 'woocommerce_ajax_added_to_cart', $product_id);
	else :
		// If there was an error adding to the cart, redirect to the product page to show any errors
		$data = array(
			'error' => true,
			'product_url' => get_permalink( $product_id )
		);
		$woocommerce->set_messages();
	endif;

	echo json_encode( $data );

	die();
}

add_action('wp_ajax_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');


/**
 * Process ajax checkout form
 *
 * @access public
 * @return void
 */
function woocommerce_process_checkout() {
	global $woocommerce;

	if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);

	$woocommerce_checkout = $woocommerce->checkout();
	$woocommerce_checkout->process_checkout();

	die(0);
}

add_action('wp_ajax_woocommerce-checkout', 'woocommerce_process_checkout');
add_action('wp_ajax_nopriv_woocommerce-checkout', 'woocommerce_process_checkout');



/** Admin AJAX events *****************************************************/

/**
 * Feature a product from admin
 *
 * @access public
 * @return void
 */
function woocommerce_feature_product() {

	if ( ! is_admin() ) die;

	if ( ! current_user_can('edit_posts') ) wp_die( __('You do not have sufficient permissions to access this page.', 'woocommerce') );

	if ( ! check_admin_referer('woocommerce-feature-product')) wp_die( __('You have taken too long. Please go back and retry.', 'woocommerce') );

	$post_id = isset( $_GET['product_id'] ) && (int) $_GET['product_id'] ? (int) $_GET['product_id'] : '';

	if (!$post_id) die;

	$post = get_post($post_id);

	if ( ! $post || $post->post_type !== 'product' ) die;

	$featured = get_post_meta( $post->ID, '_featured', true );

	if ( $featured == 'yes' )
		update_post_meta($post->ID, '_featured', 'no');
	else
		update_post_meta($post->ID, '_featured', 'yes');

	wp_safe_redirect( remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), wp_get_referer() ) );
}

add_action('wp_ajax_woocommerce-feature-product', 'woocommerce_feature_product');


/**
 * Mark an order as complete
 *
 * @access public
 * @return void
 */
function woocommerce_mark_order_complete() {

	if ( !is_admin() ) die;
	if ( !current_user_can('edit_posts') ) wp_die( __('You do not have sufficient permissions to access this page.', 'woocommerce') );
	if ( !check_admin_referer('woocommerce-mark-order-complete')) wp_die( __('You have taken too long. Please go back and retry.', 'woocommerce') );
	$order_id = isset($_GET['order_id']) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
	if (!$order_id) die;

	$order = new WC_Order( $order_id );
	$order->update_status( 'completed' );

	wp_safe_redirect( wp_get_referer() );

}
add_action('wp_ajax_woocommerce-mark-order-complete', 'woocommerce_mark_order_complete');


/**
 * Mark an order as processing
 *
 * @access public
 * @return void
 */
function woocommerce_mark_order_processing() {

	if ( !is_admin() ) die;
	if ( !current_user_can('edit_posts') ) wp_die( __('You do not have sufficient permissions to access this page.', 'woocommerce') );
	if ( !check_admin_referer('woocommerce-mark-order-processing')) wp_die( __('You have taken too long. Please go back and retry.', 'woocommerce') );
	$order_id = isset($_GET['order_id']) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
	if (!$order_id) die;

	$order = new WC_Order( $order_id );
	$order->update_status( 'processing' );

	wp_safe_redirect( wp_get_referer() );

}
add_action('wp_ajax_woocommerce-mark-order-processing', 'woocommerce_mark_order_processing');


/**
 * Add a new attribute via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_add_new_attribute() {

	check_ajax_referer( 'add-attribute', 'security' );

	$taxonomy = esc_attr( $_POST['taxonomy'] );
	$term = stripslashes( $_POST['term'] );

	if ( taxonomy_exists( $taxonomy ) ) {

		$result = wp_insert_term( $term, $taxonomy );

		if ( is_wp_error($result) ) {
   			echo json_encode(array(
				'error'			=> $result->get_error_message()
			));
   		} else {
	   		echo json_encode(array(
				'term_id'		=> $result['term_id'],
				'name'			=> $term,
				'slug'  		=> sanitize_title( $term ),
			));
		}
	}

	die();
}

add_action('wp_ajax_woocommerce_add_new_attribute', 'woocommerce_add_new_attribute');


/**
 * Delete variation via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_remove_variation() {

	check_ajax_referer( 'delete-variation', 'security' );
	$variation_id = intval( $_POST['variation_id'] );
	$variation = get_post($variation_id);
	if ( $variation && $variation->post_type == "product_variation" )
		wp_delete_post( $variation_id );
	die();
}

add_action('wp_ajax_woocommerce_remove_variation', 'woocommerce_remove_variation');


/**
 * Delete variations via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_remove_variations() {

	check_ajax_referer( 'delete-variations', 'security' );
	$variation_ids = (array) $_POST['variation_ids'];
	foreach ( $variation_ids as $variation_id ) {
		$variation = get_post($variation_id);
		if ( $variation && $variation->post_type == "product_variation" )
			wp_delete_post( $variation_id );
	}
	die();
}

add_action('wp_ajax_woocommerce_remove_variations', 'woocommerce_remove_variations');


/**
 * Add variation via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_add_variation() {

	check_ajax_referer( 'add-variation', 'security' );

	$post_id = intval( $_POST['post_id'] );

	$variation = array(
		'post_title' => 'Product #' . $post_id . ' Variation',
		'post_content' => '',
		'post_status' => 'publish',
		'post_author' => get_current_user_id(),
		'post_parent' => $post_id,
		'post_type' => 'product_variation'
	);
	$variation_id = wp_insert_post( $variation );

	echo $variation_id;

	die();
}

add_action('wp_ajax_woocommerce_add_variation', 'woocommerce_add_variation');


/**
 * Link all variations via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_link_all_variations() {
	global $woocommerce;
	
	check_ajax_referer( 'link-variations', 'security' );

	@set_time_limit(0);

	$post_id = intval( $_POST['post_id'] );

	if (!$post_id) die();

	$variations = array();

	$_product = new WC_Product( $post_id );

	// Put variation attributes into an array
	foreach ($_product->get_attributes() as $attribute) :

		if ( !$attribute['is_variation'] ) continue;

		$attribute_field_name = 'attribute_' . sanitize_title($attribute['name']);

		if ($attribute['is_taxonomy']) :
			$post_terms = wp_get_post_terms( $post_id, $attribute['name'] );
			$options = array();
			foreach ($post_terms as $term) :
				$options[] = $term->slug;
			endforeach;
		else :
			$options = explode('|', $attribute['value']);
		endif;

		$options = array_map('trim', $options);

		$variations[$attribute_field_name] = $options;

	endforeach;

	// Quit out if none were found
	if (sizeof($variations)==0) die();

	// Get existing variations so we don't create duplicated
    $available_variations = array();

    foreach($_product->get_children() as $child_id) {
    	$child = $_product->get_child( $child_id );
        if ($child instanceof WC_Product_Variation) {
            $available_variations[] = $child->get_variation_attributes();
        }
    }

	// Created posts will all have the following data
	$variation_post_data = array(
		'post_title' => 'Product #' . $post_id . ' Variation',
		'post_content' => '',
		'post_status' => 'publish',
		'post_author' => get_current_user_id(),
		'post_parent' => $post_id,
		'post_type' => 'product_variation'
	);

	// Now find all combinations and create posts
	if (!function_exists('array_cartesian')) {
		function array_cartesian($input) {
		    $result = array();

		    while (list($key, $values) = each($input)) {
		        // If a sub-array is empty, it doesn't affect the cartesian product
		        if (empty($values)) {
		            continue;
		        }

		        // Special case: seeding the product array with the values from the first sub-array
		        if (empty($result)) {
		            foreach($values as $value) {
		                $result[] = array($key => $value);
		            }
		        }
		        else {
		            // Second and subsequent input sub-arrays work like this:
		            //   1. In each existing array inside $product, add an item with
		            //      key == $key and value == first item in input sub-array
		            //   2. Then, for each remaining item in current input sub-array,
		            //      add a copy of each existing array inside $product with
		            //      key == $key and value == first item in current input sub-array

		            // Store all items to be added to $product here; adding them on the spot
		            // inside the foreach will result in an infinite loop
		            $append = array();
		            foreach($result as &$product) {
		                // Do step 1 above. array_shift is not the most efficient, but it
		                // allows us to iterate over the rest of the items with a simple
		                // foreach, making the code short and familiar.
		                $product[$key] = array_shift($values);

		                // $product is by reference (that's why the key we added above
		                // will appear in the end result), so make a copy of it here
		                $copy = $product;

		                // Do step 2 above.
		                foreach($values as $item) {
		                    $copy[$key] = $item;
		                    $append[] = $copy;
		                }

		                // Undo the side effecst of array_shift
		                array_unshift($values, $product[$key]);
		            }

		            // Out of the foreach, we can add to $results now
		            $result = array_merge($result, $append);
		        }
		    }

		    return $result;
		}
	}

	$variation_ids = array();
	$added = 0;
	$possible_variations = array_cartesian( $variations );

	foreach ($possible_variations as $variation) :

		// Check if variation already exists
		if (in_array($variation, $available_variations)) continue;

		$variation_id = wp_insert_post( $variation_post_data );

		$variation_ids[] = $variation_id;

		foreach ($variation as $key => $value) :

			update_post_meta( $variation_id, $key, $value );

		endforeach;

		$added++;

		// Max 100
		if ($added>49) break;

	endforeach;
	
	$woocommerce->clear_product_transients( $post_id );

	echo $added;

	die();
}

add_action( 'wp_ajax_woocommerce_link_all_variations', 'woocommerce_link_all_variations' );


/**
 * Delete download permissions via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_revoke_access_to_download() {

	check_ajax_referer( 'revoke-access', 'security' );

	global $wpdb;

	$product_id = intval( $_POST['product_id'] );
	$order_id 	= intval( $_POST['order_id'] );

	$wpdb->query("
		DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
		WHERE order_id = $order_id
		AND product_id = $product_id
	");

	die();
}

add_action('wp_ajax_woocommerce_revoke_access_to_download', 'woocommerce_revoke_access_to_download');


/**
 * Grant download permissions via ajax function
 *
 * @access public
 * @return void
 */
function woocommerce_grant_access_to_download() {

	check_ajax_referer( 'grant-access', 'security' );

	global $wpdb;

	$product_id = intval( $_POST['product_id'] );
	$order_id 	= intval( $_POST['order_id'] );

	$order = new WC_Order( $order_id );

	$user_email = $order->billing_email;

	$limit = trim(get_post_meta($product_id, '_download_limit', true));
	$expiry = trim(get_post_meta($product_id, '_download_expiry', true));

    $limit = (empty($limit)) ? '' : (int) $limit;

    // Default value is NULL in the table schema
	$expiry = (empty($expiry)) ? null : (int) $expiry;

	if ($expiry) $expiry = date_i18n( "Y-m-d", strtotime( 'NOW + ' . $expiry . ' DAY' ) );

	$wpdb->hide_errors();

    $data = array(
        'product_id' 			=> $product_id,
        'user_id' 				=> (int) $order->user_id,
        'user_email' 			=> $user_email,
        'order_id' 				=> $order->id,
        'order_key' 			=> $order->order_key,
        'downloads_remaining' 	=> $limit,
        'access_granted'		=> current_time('mysql'),
        'download_count'		=> 0
    );

    $format = array(
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%d'
    );

    if ( ! is_null($expiry)) {
        $data['access_expires'] = $expiry;
        $format[] = '%s';
    }

    // Downloadable product - give access to the customer
    $success = $wpdb->insert( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
        $data,
        $format
    );

	if ($success) {
		echo json_encode(array(
			'success'		=> 1,
			'download_id'  	=> $product_id,
			'title'			=> get_the_title($product_id),
			'expires'		=> is_null($expiry) ? '' : $expiry,
			'remaining'		=> $limit
		));
	}

	die();
}

add_action('wp_ajax_woocommerce_grant_access_to_download', 'woocommerce_grant_access_to_download');


/**
 * Get customer details via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_get_customer_details() {

	global $woocommerce;

	check_ajax_referer( 'get-customer-details', 'security' );

	$user_id = (int) trim(stripslashes($_POST['user_id']));
	$type_to_load = esc_attr(trim(stripslashes($_POST['type_to_load'])));

	$customer_data = array(
		$type_to_load . '_first_name' => get_user_meta( $user_id, $type_to_load . '_first_name', true ),
		$type_to_load . '_last_name' => get_user_meta( $user_id, $type_to_load . '_last_name', true ),
		$type_to_load . '_company' => get_user_meta( $user_id, $type_to_load . '_company', true ),
		$type_to_load . '_address_1' => get_user_meta( $user_id, $type_to_load . '_address_1', true ),
		$type_to_load . '_address_2' => get_user_meta( $user_id, $type_to_load . '_address_2', true ),
		$type_to_load . '_city' => get_user_meta( $user_id, $type_to_load . '_city', true ),
		$type_to_load . '_postcode' => get_user_meta( $user_id, $type_to_load . '_postcode', true ),
		$type_to_load . '_country' => get_user_meta( $user_id, $type_to_load . '_country', true ),
		$type_to_load . '_state' => get_user_meta( $user_id, $type_to_load . '_state', true ),
		$type_to_load . '_email' => get_user_meta( $user_id, $type_to_load . '_email', true ),
		$type_to_load . '_phone' => get_user_meta( $user_id, $type_to_load . '_phone', true ),
	);

	echo json_encode($customer_data);

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_get_customer_details', 'woocommerce_get_customer_details');


/**
 * Add order item via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_add_order_item() {
	global $woocommerce, $wpdb;

	check_ajax_referer( 'add-order-item', 'security' );

	$index = trim(stripslashes($_POST['index']));
	$item_to_add = trim(stripslashes($_POST['item_to_add']));

	$post = '';

	// Find the item
	if (is_numeric($item_to_add)) :
		$post = get_post( $item_to_add );
	endif;

	if (!$post || ($post->post_type!=='product' && $post->post_type!=='product_variation')) :
		$post_id = $wpdb->get_var($wpdb->prepare("
			SELECT post_id
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
			WHERE $wpdb->postmeta.meta_key = '_sku'
			AND $wpdb->posts.post_status = 'publish'
			AND $wpdb->posts.post_type = 'shop_product'
			AND $wpdb->postmeta.meta_value = %s
			LIMIT 1
		"), $item_to_add );
		$post = get_post( $post_id );
	endif;

	if (!$post || ($post->post_type!=='product' && $post->post_type!=='product_variation')) :
		die();
	endif;

	if ($post->post_type=="product") :
		$_product = new WC_Product( $post->ID );
	else :
		$_product = new WC_Product_Variation( $post->ID );
	endif;
	?>
	<tr class="item" rel="<?php echo $index; ?>">
		<td class="thumb">
			<a href="<?php echo esc_url( admin_url('post.php?post='. $_product->id .'&action=edit') ); ?>" class="tips" data-tip="<?php
				echo '<strong>'.__('Product ID:', 'woocommerce').'</strong> '. $_product->id;
				echo '<br/><strong>'.__('Variation ID:', 'woocommerce').'</strong> '; if (isset($_product->variation_id) && $_product->variation_id) echo $_product->variation_id; else echo '-';
				echo '<br/><strong>'.__('Product SKU:', 'woocommerce').'</strong> '; if ($_product->sku) echo $_product->sku; else echo '-';
			?>"><?php echo $_product->get_image(); ?></a>
		</td>
		<td class="sku" width="1%">
			<?php if ($_product->sku) echo $_product->sku; else echo '-'; ?>
			<input type="hidden" class="item_id" name="item_id[<?php echo $index; ?>]" value="<?php echo esc_attr( $_product->id ); ?>" />
			<input type="hidden" name="item_name[<?php echo $index; ?>]" value="<?php echo esc_attr( $_product->get_title() ); ?>" />
			<input type="hidden" name="item_variation[<?php echo $index; ?>]" value="<?php if (isset($_product->variation_id)) echo $_product->variation_id; ?>" />
		</td>
		<td class="name">

			<div class="row-actions">
				<span class="trash"><a class="remove_row" href="#"><?php _e('Delete item', 'woocommerce'); ?></a> | </span>
				<span class="view"><a href="<?php echo esc_url( admin_url('post.php?post='. $_product->id .'&action=edit') ); ?>"><?php _e('View product', 'woocommerce'); ?></a>
			</div>

			<?php echo $_product->get_title(); ?>
			<?php if (isset($_product->variation_data)) echo '<br/>' . woocommerce_get_formatted_variation( $_product->variation_data, true ); ?>
			<table class="meta" cellspacing="0">
				<tfoot>
					<tr>
						<td colspan="3"><button class="add_meta button"><?php _e('Add&nbsp;meta', 'woocommerce'); ?></button></td>
					</tr>
				</tfoot>
				<tbody class="meta_items"></tbody>
			</table>
		</td>

		<?php do_action('woocommerce_admin_order_item_values', $_product); ?>

		<td class="tax_class" width="1%">
			<select class="tax_class" name="item_tax_class[<?php echo $loop; ?>]">
				<?php
				$tax_classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
				$classes_options = array();
				$classes_options[''] = __('Standard', 'woocommerce');
				if ($tax_classes) foreach ($tax_classes as $class) :
					$classes_options[sanitize_title($class)] = $class;
				endforeach;
				foreach ($classes_options as $value => $name) echo '<option value="'. $value .'" '.selected( $value, $_product->get_tax_status(), false ).'>'. $name .'</option>';
				?>
			</select>
		</td>

		<td class="quantity" width="1%">
			<input type="text" name="item_quantity[<?php echo $index; ?>]" placeholder="0" value="1" size="2" class="quantity" />
		</td>

		<td class="line_subtotal" width="1%">
			<label><?php _e('Cost', 'woocommerce'); ?>: <input type="text" name="line_subtotal[<?php echo $index; ?>]" placeholder="0.00" value="<?php echo esc_attr( number_format( (double) $_product->get_price_excluding_tax(), 2, '.', '' ) ); ?>" class="line_subtotal" /></label>

			<label><?php _e('Tax', 'woocommerce'); ?>: <input type="text" name="line_subtotal_tax[<?php echo $index; ?>]" placeholder="0.00" class="line_subtotal_tax" /></label>
		</td>

		<td class="line_total" width="1%">
			<label><?php _e('Cost', 'woocommerce'); ?>: <input type="text" name="line_total[<?php echo $index; ?>]" placeholder="0.00" value="<?php echo esc_attr( number_format( (double) $_product->get_price_excluding_tax(), 2, '.', '' ) ); ?>" class="line_total" /></label>

			<label><?php _e('Tax', 'woocommerce'); ?>: <input type="text" name="line_tax[<?php echo $index; ?>]" placeholder="0.00" class="line_tax" /></label>
		</td>

	</tr>
	<?php

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_add_order_item', 'woocommerce_add_order_item');


/**
 * Calc line tax
 *
 * @access public
 * @return void
 */
function woocommerce_calc_line_taxes() {
	global $woocommerce;

	check_ajax_referer( 'calc-totals', 'security' );

	$tax = new WC_Tax();

	$base_tax_amount = 0;
	$line_tax_amount = 0;

	$country 		= strtoupper( esc_attr( $_POST['country'] ) );
	$state 			= strtoupper( esc_attr( $_POST['state'] ) );
	$postcode 		= strtoupper( esc_attr( $_POST['postcode'] ) );

	$line_subtotal 	= esc_attr( $_POST['line_subtotal'] );
	$line_total 	= esc_attr( $_POST['line_total'] );

	$item_id		= esc_attr( $_POST['item_id'] );
	$tax_class 		= esc_attr( $_POST['tax_class'] );

	if ( ! $item_id ) return;

	// Get product details
	$_product			= new WC_Product( $item_id );
	$item_tax_status 	= $_product->get_tax_status();

	if ( $item_tax_status == 'taxable' ) {

		$tax_rates			= $tax->find_rates( $country, $state, $postcode, $tax_class );

		$line_subtotal_tax_amount	= rtrim( rtrim( number_format( array_sum( $tax->calc_tax( $line_subtotal, $tax_rates, false ) ), 4, '.', '' ), '0' ), '.' );
		$line_tax_amount			= rtrim( rtrim( number_format( array_sum( $tax->calc_tax( $line_total, $tax_rates, false ) ), 4, '.', '' ), '0' ), '.' );

	}

	if ( $line_subtotal_tax_amount < 0 ) $line_subtotal_tax_amount = 0;
	if ( $line_tax_amount < 0 ) $line_tax_amount = 0;

	echo json_encode( array(
		'line_subtotal_tax' => $line_subtotal_tax_amount,
		'line_tax' => $line_tax_amount
	) );

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_calc_line_taxes', 'woocommerce_calc_line_taxes');


/**
 * Add order note via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_add_order_note() {

	global $woocommerce;

	check_ajax_referer( 'add-order-note', 'security' );

	$post_id 	= (int) $_POST['post_id'];
	$note		= wp_kses( trim( stripslashes( $_POST['note'] ) ), array( 'a' => array( 'href' => array(), 'title' => array() ), 'br' => array(), 'em' => array(), 'strong' => array() ) );
	$note_type	= $_POST['note_type'];

	$is_customer_note = $note_type == 'customer' ? 1 : 0;

	if ( $post_id > 0 ) {
		$order = new WC_Order( $post_id );
		$comment_id = $order->add_order_note( $note, $is_customer_note );

		echo '<li rel="'.$comment_id.'" class="note ';
		if ($is_customer_note) echo 'customer-note';
		echo '"><div class="note_content">';
		echo wpautop( wptexturize( $note ) );
		echo '</div><p class="meta"><a href="#" class="delete_note">'.__('Delete note', 'woocommerce').'</a></p>';
		echo '</li>';

	}

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_add_order_note', 'woocommerce_add_order_note');


/**
 * Delete order note via ajax
 *
 * @access public
 * @return void
 */
function woocommerce_delete_order_note() {

	global $woocommerce;

	check_ajax_referer( 'delete-order-note', 'security' );

	$note_id 	= (int) $_POST['note_id'];

	if ($note_id>0) :
		wp_delete_comment( $note_id );
	endif;

	// Quit out
	die();
}

add_action('wp_ajax_woocommerce_delete_order_note', 'woocommerce_delete_order_note');


/**
 * Search for products and return json
 *
 * @access public
 * @param string $x (default: '')
 * @param string $post_types (default: array('product'))
 * @return void
 */
function woocommerce_json_search_products( $x = '', $post_types = array('product') ) {

	check_ajax_referer( 'search-products', 'security' );

	$term = (string) urldecode(stripslashes(strip_tags($_GET['term'])));

	if (empty($term)) die();

	if ( is_numeric( $term ) ) {

		$args = array(
			'post_type'			=> $post_types,
			'post_status'	 	=> 'publish',
			'posts_per_page' 	=> -1,
			'post__in' 			=> array(0, $term),
			'fields'			=> 'ids'
		);

		$args2 = array(
			'post_type'			=> $post_types,
			'post_status'	 	=> 'publish',
			'posts_per_page' 	=> -1,
			'post_parent' 		=> $term,
			'fields'			=> 'ids'
		);

		$args3 = array(
			'post_type'			=> $post_types,
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> -1,
			'meta_query' 		=> array(
				array(
				'key' 	=> '_sku',
				'value' => $term,
				'compare' => 'LIKE'
				)
			),
			'fields'			=> 'ids'
		);

		$posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ));

	} else {

		$args = array(
			'post_type'			=> $post_types,
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> -1,
			's' 				=> $term,
			'fields'			=> 'ids'
		);

		$args2 = array(
			'post_type'			=> $post_types,
			'post_status' 		=> 'publish',
			'posts_per_page' 	=> -1,
			'meta_query' 		=> array(
				array(
				'key' 	=> '_sku',
				'value' => $term,
				'compare' => 'LIKE'
				)
			),
			'fields'			=> 'ids'
		);

		$posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 ) ));

	}

	$found_products = array();

	if ($posts) foreach ($posts as $post) {

		$SKU = get_post_meta($post, '_sku', true);

		if (isset($SKU) && $SKU) $SKU = ' (SKU: ' . $SKU . ')';

		$found_products[$post] = get_the_title( $post ) . ' &ndash; #' . $post . $SKU;

	}

	echo json_encode( $found_products );

	die();
}

add_action('wp_ajax_woocommerce_json_search_products', 'woocommerce_json_search_products');

/**
 * Search for product variations and return json
 *
 * @access public
 * @return void
 * @see woocommerce_json_search_products()
 */
function woocommerce_json_search_products_and_variations() {
	woocommerce_json_search_products( '', array('product', 'product_variation') );
}

add_action('wp_ajax_woocommerce_json_search_products_and_variations', 'woocommerce_json_search_products_and_variations');


/**
 * Search for customers and return json
 *
 * @access public
 * @return void
 */
function woocommerce_json_search_customers() {

	check_ajax_referer( 'search-customers', 'security' );

	$term = urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );

	if ( empty( $term ) )
		die();

	$default = isset( $_GET['default'] ) ? $_GET['default'] : __('Guest', 'woocommerce');

	$found_customers = array( '' => $default );

	$customers_query = new WP_User_Query( array(
		'fields'			=> 'all',
		'orderby'			=> 'display_name',
		'search'			=> '*' . $term . '*',
		'search_columns'	=> array( 'ID', 'user_login', 'user_email', 'user_nicename' )
	) );

	$customers = $customers_query->get_results();

	if ( $customers ) {
		foreach ( $customers as $customer ) {
			$found_customers[ $customer->ID ] = $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . $customer->user_email . ')';
		}
	}

	echo json_encode( $found_customers );
	die();
}

add_action('wp_ajax_woocommerce_json_search_customers', 'woocommerce_json_search_customers');


/**
 * Search for products for upsells/crosssells
 *
 * @access public
 * @return void
 */
function woocommerce_upsell_crosssell_search_products() {

	check_ajax_referer( 'search-products', 'security' );

	$search = (string) urldecode(stripslashes(strip_tags($_POST['search'])));
	$name = (string) urldecode(stripslashes(strip_tags($_POST['name'])));

	if (empty($search)) die();

	if (is_numeric($search)) :

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => 15,
			'post__in' => array(0, $search)
		);

	else :

		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => 15,
			's' => $search
		);

	endif;

	$posts = apply_filters('woocommerce_upsell_crosssell_search_products', get_posts( $args ));

	if ($posts) : foreach ($posts as $post) :

		$SKU = get_post_meta($post->ID, '_sku', true);

		?>
		<li rel="<?php echo $post->ID; ?>"><button type="button" name="Add" class="button add_crosssell" title="Add"><?php _e('Cross-sell', 'woocommerce'); ?> &rarr;</button><button type="button" name="Add" class="button add_upsell" title="Add"><?php _e('Up-sell', 'woocommerce'); ?> &rarr;</button><strong><?php echo $post->post_title; ?></strong> &ndash; #<?php echo $post->ID; ?> <?php if (isset($SKU) && $SKU) echo 'SKU: '.$SKU; ?><input type="hidden" class="product_id" value="0" /></li>
		<?php

	endforeach; else :

		?><li><?php _e('No products found', 'woocommerce'); ?></li><?php

	endif;

	die();
}

add_action('wp_ajax_woocommerce_upsell_crosssell_search_products', 'woocommerce_upsell_crosssell_search_products');


/**
 * Ajax request handling for categories ordering
 *
 * @access public
 * @return void
 */
function woocommerce_term_ordering() {
	global $wpdb;

	$id = (int) $_POST['id'];
	$next_id  = isset($_POST['nextid']) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
	$taxonomy = isset($_POST['thetaxonomy']) ? esc_attr( $_POST['thetaxonomy'] ) : null;
	$term = get_term_by('id', $id, $taxonomy);

	if ( !$id || !$term || !$taxonomy ) die(0);

	woocommerce_order_terms( $term, $next_id, $taxonomy );

	$children = get_terms($taxonomy, "child_of=$id&menu_order=ASC&hide_empty=0");

	if ( $term && sizeof($children) ) {
		echo 'children';
		die;
	}
}

add_action('wp_ajax_woocommerce-term-ordering', 'woocommerce_term_ordering');


/**
 * Ajax request handling for product ordering
 *
 * Based on Simple Page Ordering by 10up (http://wordpress.org/extend/plugins/simple-page-ordering/)
 *
 * @access public
 * @return void
 */
function woocommerce_product_ordering() {
	global $wpdb;

	// check permissions again and make sure we have what we need
	if ( ! current_user_can('edit_others_pages') || empty( $_POST['id'] ) || ( ! isset( $_POST['previd'] ) && ! isset( $_POST['nextid'] ) ) )
		die(-1);

	// real post?
	if ( ! $post = get_post( $_POST['id'] ) )
		die(-1);

	$previd = isset( $_POST['previd'] ) ? $_POST['previd'] : false;
	$nextid = isset( $_POST['nextid'] ) ? $_POST['nextid'] : false;
	$new_pos = array(); // store new positions for ajax

	$siblings = $wpdb->get_results("
		SELECT ID, menu_order FROM {$wpdb->posts} AS posts
		WHERE 	posts.post_type 	= 'product'
		AND 	posts.post_status 	IN ( 'publish', 'pending', 'draft', 'future', 'private' )
		AND 	posts.ID			NOT IN ( {$post->ID} )
		ORDER BY posts.menu_order ASC, posts.post_title ASC
	");

	$menu_order = 0;

	foreach( $siblings as $sibling ) {

		// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
		if ( $nextid == $sibling->ID ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'menu_order' => $menu_order
				),
				array( 'ID' => $post->ID ),
				array( '%d' ),
				array( '%d' )
			);
			$new_pos[ $post->ID ] = $menu_order;
			$menu_order++;
		}

		// if repositioned post has been set, and new items are already in the right order, we can stop
		if ( isset( $new_pos[ $post->ID ] ) && $sibling->menu_order >= $menu_order )
			break;

		// set the menu order of the current sibling and increment the menu order
		$wpdb->update(
			$wpdb->posts,
			array(
				'menu_order' => $menu_order
			),
			array( 'ID' => $sibling->ID ),
			array( '%d' ),
			array( '%d' )
		);
		$new_pos[ $sibling->ID ] = $menu_order;
		$menu_order++;

		if ( ! $nextid && $previd == $sibling->ID ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'menu_order' => $menu_order
				),
				array( 'ID' => $post->ID ),
				array( '%d' ),
				array( '%d' )
			);
			$new_pos[$post->ID] = $menu_order;
			$menu_order++;
		}

	}

	die( json_encode( $new_pos ) );
}

add_action( 'wp_ajax_woocommerce_product_ordering', 'woocommerce_product_ordering' );