<?php
/**
 * WooCommerce Actions
 * 
 * Actions/functions/hooks for WooCommerce related events.
 *
 *		- Update catalog ordering if posted
 *		- AJAX update shipping method on cart page
 *		- AJAX update order review on checkout
 *		- AJAX add to cart
 *		- AJAX add to cart fragments
 *		- Increase coupon usage count
 *		- Add order item
 *		- When default permalinks are enabled, redirect shop page to post type archive url
 *		- Add to Cart
 *		- Clear cart
 *		- Restore an order via a link
 *		- Cancel a pending order
 *		- Download a file
 *		- Order Status completed - allow customer to access Downloadable product
 *		- Google Analytics standard tracking
 *		- Google Analytics eCommerce tracking
 *
 * @package		WooCommerce
 * @category	Actions
 * @author		WooThemes
 */

/**
 * Update catalog ordering if posted
 */
add_action('init', 'woocommerce_update_catalog_ordering');

function woocommerce_update_catalog_ordering() {
	if (isset($_POST['catalog_orderby']) && $_POST['catalog_orderby'] != '') $_SESSION['orderby'] = $_POST['catalog_orderby'];
}

/**
 * AJAX update shipping method on cart page
 */
add_action('wp_ajax_woocommerce_update_shipping_method', 'woocommerce_ajax_update_shipping_method');
add_action('wp_ajax_nopriv_woocommerce_update_shipping_method', 'woocommerce_ajax_update_shipping_method');

function woocommerce_ajax_update_shipping_method() {
	global $woocommerce;
	
	check_ajax_referer( 'update-shipping-method', 'security' );
	
	if (isset($_POST['shipping_method'])) $_SESSION['_chosen_shipping_method'] = $_POST['shipping_method'];
	
	$woocommerce->cart->calculate_totals();
	
	woocommerce_cart_totals();
	
	die();
}


/**
 * AJAX update order review on checkout
 */
add_action('wp_ajax_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');
add_action('wp_ajax_nopriv_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');

function woocommerce_ajax_update_order_review() {
	global $woocommerce;
	
	check_ajax_referer( 'update-order-review', 'security' );
	
	if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);
	
	if (sizeof($woocommerce->cart->cart_contents)==0) :
		echo '<p class="error">'.__('Sorry, your session has expired.', 'woothemes').' <a href="'.home_url().'">'.__('Return to homepage &rarr;', 'woothemes').'</a></p>';
		die();
	endif;
	
	do_action('woocommerce_checkout_update_order_review', $_POST['post_data']);
	
	if (isset($_POST['shipping_method'])) $_SESSION['_chosen_shipping_method'] = $_POST['shipping_method'];
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

/**
 * AJAX add to cart
 */
add_action('wp_ajax_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');

function woocommerce_ajax_add_to_cart() {
	
	global $woocommerce;
	
	check_ajax_referer( 'add-to-cart', 'security' );
	
	$product_id = (int) $_POST['product_id'];

	if ($woocommerce->cart->add_to_cart($product_id, 1)) :
		// Return html fragments
		$data = apply_filters('add_to_cart_fragments', array());
	else :
		// Return error
		$data = array(
			'error' => $woocommerce->errors[0]
		);
		$woocommerce->clear_messages();
	endif;
	
	echo json_encode( $data );
	
	die();
}


/**
 * Increase coupon usage count
 */
add_action('woocommerce_new_order', 'woocommerce_increase_coupon_counts');

function woocommerce_increase_coupon_counts() {
	global $woocommerce;
	if ($woocommerce->cart->applied_coupons) foreach ($woocommerce->cart->applied_coupons as $code) :
		$coupon = &new woocommerce_coupon( $code );
		$coupon->inc_usage_count();
	endforeach;
}

/**
 * Add order item via ajax
 */
add_action('wp_ajax_woocommerce_add_order_item', 'woocommerce_add_order_item');

function woocommerce_add_order_item() {
	
	global $woocommerce;

	check_ajax_referer( 'add-order-item', 'security' );
	
	global $wpdb;
	
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
			WHERE $wpdb->postmeta.meta_key = 'sku'
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
		$_product = &new woocommerce_product( $post->ID );
	else :
		$_product = &new woocommerce_product_variation( $post->ID );
	endif;
	?>
	<tr class="item" rel="<?php echo $index; ?>">
		<td class="product-id">
			<img class="tips" tip="<?php
				echo '<strong>'.__('Product ID:', 'woothemes').'</strong> '. $_product->id;
				echo '<br/><strong>'.__('Variation ID:', 'woothemes').'</strong> '; if ($_product->variation_id) echo $_product->variation_id; else echo '-';
				echo '<br/><strong>'.__('Product SKU:', 'woothemes').'</strong> '; if ($_product->sku) echo $_product->sku; else echo '-';
			?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/tip.png" />
		</td>
		<td class="name">
			<a href="<?php echo esc_url( admin_url('post.php?post='. $_product->id .'&action=edit') ); ?>"><?php echo $_product->get_title(); ?></a>
			<?php
				if (isset($_product->variation_data)) :
					echo '<br/>' . woocommerce_get_formatted_variation( $_product->variation_data, true );
				endif;
			?>
		</td>
		<td>
			<table class="meta" cellspacing="0">
				<tfoot>
					<tr>
						<td colspan="3"><button class="add_meta button"><?php _e('Add&nbsp;meta', 'woothemes'); ?></button></td>
					</tr>
				</tfoot>
				<tbody class="meta_items"></tbody>
			</table>
		</td>
		<?php do_action('woocommerce_admin_order_item_values', $_product); ?>
		<td class="quantity"><input type="text" name="item_quantity[<?php echo $index; ?>]" placeholder="<?php _e('Quantity e.g. 2', 'woothemes'); ?>" value="1" /></td>
		<td class="cost"><input type="text" name="item_cost[<?php echo $index; ?>]" placeholder="<?php _e('Cost per unit ex. tax e.g. 2.99', 'woothemes'); ?>" value="<?php echo esc_attr( $_product->get_price_excluding_tax() ); ?>" /></td>
		<td class="tax"><input type="text" name="item_tax_rate[<?php echo $index; ?>]" placeholder="<?php _e('Tax Rate e.g. 20.0000', 'woothemes'); ?>" value="<?php echo esc_attr( $_product->get_tax_base_rate() ); ?>" /></td>
		<td class="center">
			<input type="hidden" name="item_id[<?php echo $index; ?>]" value="<?php echo esc_attr( $_product->id ); ?>" />
			<input type="hidden" name="item_name[<?php echo $index; ?>]" value="<?php echo esc_attr( $_product->get_title() ); ?>" />
			<input type="hidden" name="item_variation[<?php echo $index; ?>]" value="<?php if (isset($_product->variation_id)) echo $_product->variation_id; ?>" />
			<button type="button" class="remove_row button">&times;</button>
		</td>
	</tr>
	<?php
	
	// Quit out
	die();
	
}

/**
 * Add order note via ajax
 */
add_action('wp_ajax_woocommerce_add_order_note', 'woocommerce_add_order_note');

function woocommerce_add_order_note() {
	
	global $woocommerce;

	check_ajax_referer( 'add-order-note', 'security' );
	
	$post_id 	= (int) $_POST['post_id'];
	$note		= strip_tags(woocommerce_clean($_POST['note']));
	$note_type	= $_POST['note_type'];
	
	$is_customer_note = ($note_type=='customer') ? 1 : 0;
	
	if ($post_id>0) :
		$order = &new woocommerce_order( $post_id );
		$comment_id = $order->add_order_note( $note, $is_customer_note );
		
		echo '<li rel="'.$comment_id.'" class="note ';
		if ($is_customer_note) echo 'customer-note';
		echo '"><div class="note_content">';
		echo wpautop(wptexturize($note));
		echo '</div><p class="meta">'. sprintf(__('added %s ago', 'woothemes'), human_time_diff(strtotime('NOW'))) .' - <a href="#" class="delete_note">'.__('Delete note', 'woothemes').'</a></p>';
		echo '</li>';
		
	endif;
	
	// Quit out
	die();
}

/**
 * Delete order note via ajax
 */
add_action('wp_ajax_woocommerce_delete_order_note', 'woocommerce_delete_order_note');

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

/**
 * Search for products for upsells/crosssells
 */
add_action('wp_ajax_woocommerce_upsell_crosssell_search_products', 'woocommerce_upsell_crosssell_search_products');

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
	
	$posts = get_posts( $args );
	
	if ($posts) : foreach ($posts as $post) : 
		
		$SKU = get_post_meta($post->ID, 'sku', true);
		
		?>
		<li rel="<?php echo $post->ID; ?>"><button type="button" name="Add" class="button add" title="Add">&rarr;</button><strong><?php echo $post->post_title; ?></strong> &ndash; #<?php echo $post->ID; ?> <?php if (isset($SKU) && $SKU) echo 'SKU: '.$SKU; ?><input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="0" /></li>
		<?php
						
	endforeach; else : 
	
		?><li><?php _e('No products found', 'woothemes'); ?></li><?php 
		
	endif; 
	
	die();
	
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
	if ( isset($_GET['remove_item']) && is_numeric($_GET['remove_item'])  && $woocommerce->verify_nonce('cart', '_GET')) :
	
		$woocommerce->cart->set_quantity( $_GET['remove_item'], 0 );
		
		$woocommerce->add_message( __('Cart updated.', 'woothemes') );
		
		if ( isset($_SERVER['HTTP_REFERER'])) :
			wp_safe_redirect($_SERVER['HTTP_REFERER']);
			exit;
		endif;
	
	// Update Cart
	elseif (isset($_POST['update_cart']) && $_POST['update_cart']  && $woocommerce->verify_nonce('cart')) :
		
		$cart_totals = $_POST['cart'];
		
		if (sizeof($woocommerce->cart->cart_contents)>0) : 
			foreach ($woocommerce->cart->cart_contents as $cart_item_key => $values) :
				
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
		
			// Output success messages
			if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
				$woocommerce->add_message( __('Product successfully added to your cart.', 'woothemes') );
			else :
				$woocommerce->add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your cart.', 'woothemes'), $woocommerce->cart->get_cart_url()) );
			endif;
		
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
                    $variations[$taxonomy] = esc_attr(stripslashes($_POST[$taxonomy]));
				else :
                    $all_variations_set = false;
                endif;
            endforeach;

            if ($all_variations_set && $variation_id > 0) :
                
                // Add to cart
				if ($woocommerce->cart->add_to_cart($product_id, $quantity, $variations, $variation_id)) :
				
					if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
						$woocommerce->add_message( __('Product successfully added to your cart.', 'woothemes') );
					else :
						$woocommerce->add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your cart.', 'woothemes'), $woocommerce->cart->get_cart_url()) );
					endif;
				
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
					
						if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
							$woocommerce->add_message( __('Product successfully added to your cart.', 'woothemes') );
						else :
							$woocommerce->add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your cart.', 'woothemes'), $woocommerce->cart->get_cart_url()) );
						endif;
						
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
	elseif ( isset($_SERVER['HTTP_REFERER'])) {
		wp_safe_redirect($_SERVER['HTTP_REFERER']);
		exit;
	}
	
	// If all else fails redirect to root
	else {
		wp_safe_redirect(home_url());
		exit;
	}	
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
				if ( isset($_SERVER['HTTP_REFERER'])) {
					wp_safe_redirect($_SERVER['HTTP_REFERER']);
					exit;
				}
				wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
				exit;
			endif;
			
		endif;
	
	endif;	
}

/**
 * Process ajax checkout form
 */
add_action('wp_ajax_woocommerce-checkout', 'woocommerce_process_checkout');
add_action('wp_ajax_nopriv_woocommerce-checkout', 'woocommerce_process_checkout');

function woocommerce_process_checkout () {
	global $woocommerce, $woocommerce_checkout;
	
	include_once($woocommerce->plugin_path() . '/classes/checkout.class.php');
	
	$woocommerce_checkout = &new woocommerce_checkout();
	$woocommerce_checkout->process_checkout();
	
	die(0);
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
		$order = urldecode( $_GET['order'] );
		$email = urldecode( $_GET['email'] );
		
		if (!is_email($email)) wp_safe_redirect( home_url() );
		
		$downloads_remaining = $wpdb->get_var( $wpdb->prepare("
			SELECT downloads_remaining 
			FROM ".$wpdb->prefix."woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s
		;", $email, $order, $download_file ) );
		
		if ($downloads_remaining=='0') :
			wp_die( sprintf(__('Sorry, you have reached your download limit for this file. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
		else :
			
			if ($downloads_remaining>0) :
				$wpdb->update( $wpdb->prefix . "woocommerce_downloadable_product_permissions", array( 
					'downloads_remaining' => $downloads_remaining - 1, 
				), array( 
					'user_email' => $email,
					'order_key' => $order,
					'product_id' => $download_file 
				), array( '%d' ), array( '%s', '%s', '%d' ) );
			endif;
			
			// Get the downloads URL and try to replace the url with a path
			$file_path = get_post_meta($download_file, 'file_path', true);	
			
			$file_path = str_replace(trailingslashit(home_url()), ABSPATH, $file_path);
			
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

            // Headers
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
							
          	if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
			    // workaround for IE filename bug with multiple periods / multiple dots in filename
			    $iefilename = preg_replace('/\./', '%2e', basename($file_path), substr_count(basename($file_path), '.') - 1);
			    header("Content-Disposition: attachment; filename=\"".$iefilename."\";");
			} else {
			    header("Content-Disposition: attachment; filename=\"".basename($file_path)."\";");
			}

			header("Content-Transfer-Encoding: binary");
							
            if ($size = @filesize($file_path)) header("Content-Length: ".$size);

            // Serve it
            if ($remote_file) :
            	
            	@readfile_chunked("$file_path") or header('Location: '.$file_path);
            	
            else :
            	
            	if (!file_exists($file_path)) wp_die( sprintf(__('File not found. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
            	 
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
			$_product = &new woocommerce_product( $item['id'] );
			
			if ( $_product->exists && $_product->is_type('downloadable') ) :
				
				$user_email = $order->billing_email;
				
				if ($order->user_id>0) :
					$user_info = get_userdata($order->user_id);
					if ($user_info->user_email) :
						$user_email = $user_info->user_email;
					endif;
				else :
					$order->user_id = 0;
				endif;
				
				$limit = trim(get_post_meta($_product->id, 'download_limit', true));
				
				if (!empty($limit)) :
					$limit = (int) $limit;
				else :
					$limit = '';
				endif;
				
				// Downloadable product - give access to the customer
				$wpdb->insert( $wpdb->prefix . 'woocommerce_downloadable_product_permissions', array( 
					'product_id' => $_product->id, 
					'user_id' => $order->user_id,
					'user_email' => $user_email,
					'order_key' => $order->order_key,
					'downloads_remaining' => $limit
				), array( 
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
				'<?php echo woocommerce_get_formatted_variation( $_product->variation_data, true ); ?>',   // category or variation
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
