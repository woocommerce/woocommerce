<?php
/**
 * WooCommerce Actions
 * 
 * Actions/functions/hooks for WooCommerce related events.
 *
 *		- AJAX update order review on checkout
 *		- AJAX add to cart
 *		- AJAX add to cart fragments
 *		- Increase coupon usage count
 *		- Get variation
 *		- Add order item
 *		- When default permalinks are enabled, redirect shop page to post type archive url
 *		- Add to Cart
 *		- Clear cart
 *		- Restore an order via a link
 *		- Cancel a pending order
 *		- Download a file
 *		- Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 *
 * @package		WooCommerce
 * @category	Emails
 * @author		WooThemes
 */


/**
 * AJAX get order review on checkout
 */
add_action('wp_ajax_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');
add_action('wp_ajax_nopriv_woocommerce_update_order_review', 'woocommerce_ajax_update_order_review');

function woocommerce_ajax_update_order_review() {
	
	check_ajax_referer( 'update-order-review', 'security' );
	
	if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);
	
	if (sizeof(woocommerce_cart::$cart_contents)==0) :
		echo '<p class="error">'.__('Sorry, your session has expired.', 'woothemes').' <a href="'.home_url().'">'.__('Return to homepage &rarr;', 'woothemes').'</a></p>';
		die();
	endif;
	
	if (isset($_POST['shipping_method'])) $_SESSION['_chosen_method_id'] = $_POST['shipping_method'];
	
	if (isset($_POST['country'])) woocommerce_customer::set_country( $_POST['country'] );
	if (isset($_POST['state'])) woocommerce_customer::set_state( $_POST['state'] );
	if (isset($_POST['postcode'])) woocommerce_customer::set_postcode( $_POST['postcode'] );
	
	if (isset($_POST['s_country'])) woocommerce_customer::set_shipping_country( $_POST['s_country'] );
	if (isset($_POST['s_state'])) woocommerce_customer::set_shipping_state( $_POST['s_state'] );
	if (isset($_POST['s_postcode'])) woocommerce_customer::set_shipping_postcode( $_POST['s_postcode'] );
	
	woocommerce_cart::calculate_totals();
	
	do_action('woocommerce_checkout_order_review');
	
	die();
}

/**
 * AJAX add to cart
 */
add_action('wp_ajax_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart', 'woocommerce_ajax_add_to_cart');

function woocommerce_ajax_add_to_cart() {
	
	check_ajax_referer( 'add-to-cart', 'security' );
	
	$product_id = (int) $_POST['product_id'];

	woocommerce_cart::add_to_cart($product_id, 1);
	
	// Return html fragments
	$fragments = apply_filters('add_to_cart_fragments', array());
	
	echo json_encode( $fragments );
	
	die();
}


/**
 * Increase coupon usage count
 */
add_action('woocommerce_new_order', 'woocommerce_increase_coupon_counts');

function woocommerce_increase_coupon_counts() {
	if (woocommerce_cart::$applied_coupons) foreach (woocommerce_cart::$applied_coupons as $code) :
		woocommerce_coupons::inc_usage_count($code);
	endforeach;
}


/**
 * Get variation price etc when using frontend form
 */
add_action('wp_ajax_woocommerce_get_variation', 'display_variation_data');
add_action('wp_ajax_nopriv_woocommerce_get_variation', 'display_variation_data');

function display_variation_data() {
	
	check_ajax_referer( 'get-variation', 'security' );
	
	// get variation terms
	$variation_query 	= array();
	$variation_data 	= array();
	parse_str( $_POST['variation_data'], $variation_data );
	
	$variation_id = woocommerce_find_variation( $variation_data );
	
	if (!$variation_id) die();
	
	$_product = &new woocommerce_product_variation($variation_id);
	
	$availability = $_product->get_availability();
	
	if ($availability['availability']) :
		$availability_html = '<p class="stock '.$availability['class'].'">'. $availability['availability'].'</p>';
	else :
		$availability_html = '';
	endif;
	
	if (has_post_thumbnail($variation_id)) :
		$attachment_id = get_post_thumbnail_id( $variation_id );
		$large_thumbnail_size = apply_filters('single_product_large_thumbnail_size', 'shop_large');
		$image = current(wp_get_attachment_image_src( $attachment_id, $large_thumbnail_size));
		$image_link = current(wp_get_attachment_image_src( $attachment_id, 'full'));
	else :
		$image = '';
		$image_link = '';
	endif;
	
	$data = array(
		'price_html' 		=> '<span class="price">'.$_product->get_price_html() .'</span>',
		'availability_html' => $availability_html,
		'image_src'			=> $image,
		'image_link'		=> $image_link
	);
	
	echo json_encode( $data );

	// Quit out
	die();
}

/**
 * Add order item via ajax
 */
add_action('wp_ajax_woocommerce_add_order_item', 'woocommerce_add_order_item');

function woocommerce_add_order_item() {
	
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
			AND $wpdb->postmeta.meta_value = '".$item_to_add."'
			LIMIT 1
		"));
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
			?>" src="<?php echo woocommerce::plugin_url(); ?>/assets/images/tip.png" />
		</td>
		<td class="name">
			<a href="<?php echo admin_url('post.php?post='. $_product->id .'&action=edit'); ?>"><?php echo $_product->get_title(); ?></a>
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
		<td class="cost"><input type="text" name="item_cost[<?php echo $index; ?>]" placeholder="<?php _e('Cost per unit ex. tax e.g. 2.99', 'woothemes'); ?>" value="<?php echo $_product->get_price_excluding_tax(); ?>" /></td>
		<td class="tax"><input type="text" name="item_tax_rate[<?php echo $index; ?>]" placeholder="<?php _e('Tax Rate e.g. 20.0000', 'woothemes'); ?>" value="<?php echo $_product->get_tax_base_rate(); ?>" /></td>
		<td class="center">
			<input type="hidden" name="item_id[<?php echo $index; ?>]" value="<?php echo $_product->id; ?>" />
			<input type="hidden" name="item_name[<?php echo $index; ?>]" value="<?php echo $_product->get_title(); ?>" />
			<button type="button" class="remove_row button">&times;</button>
		</td>
	</tr>
	<?php
	
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
			'posts_per_page' => 20,
			'post__in' => array(0, $search)
		);
		
	else :
	
		$args = array(
			'post_type'	=> 'product',
			'post_status' => 'publish',
			'posts_per_page' => 20,
			's' => $search
		);
	
	endif;
	
	$posts = get_posts( $args );
	
	if ($posts) : foreach ($posts as $post) : 
		
		$SKU = get_post_meta($post->ID, 'sku', true);
		
		?>
		<li rel="<?php echo $post->ID; ?>"><button type="button" name="Add" class="button add" title="Add">&rarr;</button><strong><?php echo $post->post_title; ?></strong> &ndash; #<?php echo $post->ID; ?> <?php if (isset($SKU) && $SKU) echo 'SKU: '.$SKU; ?><input type="hidden" name="<?php echo $name; ?>[]" value="0" /></li>
		<?php
						
	endforeach; else : 
	
		?><li><?php _e('No products found', 'woocommerce'); ?></li><?php 
		
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

	// Remove from cart
	if ( isset($_GET['remove_item']) && is_numeric($_GET['remove_item'])  && woocommerce::verify_nonce('cart', '_GET')) :
	
		woocommerce_cart::set_quantity( $_GET['remove_item'], 0 );
		
		// Re-calc price
		//woocommerce_cart::calculate_totals();
			
		woocommerce::add_message( __('Cart updated.', 'woothemes') );
		
		if ( isset($_SERVER['HTTP_REFERER'])) :
			wp_safe_redirect($_SERVER['HTTP_REFERER']);
			exit;
		endif;
	
	// Update Cart
	elseif (isset($_POST['update_cart']) && $_POST['update_cart']  && woocommerce::verify_nonce('cart')) :
		
		$cart_totals = $_POST['cart'];
		
		if (sizeof(woocommerce_cart::$cart_contents)>0) : 
			foreach (woocommerce_cart::$cart_contents as $cart_item_key => $values) :
				
				if (isset($cart_totals[$cart_item_key]['qty'])) woocommerce_cart::set_quantity( $cart_item_key, $cart_totals[$cart_item_key]['qty'] );
				
			endforeach;
		endif;
		
		woocommerce::add_message( __('Cart updated.', 'woothemes') );
		
	endif;

}

/**
 * Add to cart
 **/
add_action( 'init', 'woocommerce_add_to_cart_action' );

function woocommerce_add_to_cart_action( $url = false ) {
	
	if (isset($_GET['add-to-cart']) && $_GET['add-to-cart']) :
	
		if ( !woocommerce::verify_nonce('add_to_cart', '_GET') ) :

		elseif (is_numeric($_GET['add-to-cart'])) :
		
			$quantity = 1;
			if (isset($_POST['quantity'])) $quantity = $_POST['quantity'];
			woocommerce_cart::add_to_cart($_GET['add-to-cart'], $quantity);
			
			if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
				woocommerce::add_message( __('Product successfully added to your basket.', 'woothemes') );
			else :
				woocommerce::add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your basket.', 'woothemes'), woocommerce_cart::get_cart_url()) );
			endif;
		
		elseif ($_GET['add-to-cart']=='variation') :
			
			// Variation add to cart
			if (isset($_POST['quantity']) && $_POST['quantity']) :
				
				$product_id = (int) $_GET['product'];
				$quantity 	= ($_POST['quantity']) ? $_POST['quantity'] : 1;
				$attributes = (array) maybe_unserialize( get_post_meta($product_id, 'product_attributes', true) );
				$variations = array();
				$all_variations_set = true;
				$variation_id = 0;
				
				foreach ($attributes as $attribute) :
								
					if ( $attribute['variation']!=='yes' ) continue;
					
					if (isset($_POST[ 'tax_' . sanitize_title($attribute['name']) ]) && $_POST[ 'tax_' . sanitize_title($attribute['name']) ]) :
						$variations['tax_' .sanitize_title($attribute['name'])] = $_POST[ 'tax_' . sanitize_title($attribute['name']) ];
					else :
						$all_variations_set = false;
					endif;

				endforeach;
				
				if (!$all_variations_set) :
					woocommerce::add_error( __('Please choose product options&hellip;', 'woothemes') );
				else :
					// Find matching variation
					$variation_id = woocommerce_find_variation( $variations );
					
					if ($variation_id>0) :
						// Add to cart
						woocommerce_cart::add_to_cart($product_id, $quantity, $variations, $variation_id);
						
						if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
							woocommerce::add_message( __('Product successfully added to your basket.', 'woothemes') );
						else :
							woocommerce::add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your basket.', 'woothemes'), woocommerce_cart::get_cart_url()) );
						endif;
			
					else :
						woocommerce::add_error( __('Sorry, this variation is not available.', 'woothemes') );
					endif;
				endif;
			
			elseif ($_GET['product']) :
				
				/* Link on product pages */
				woocommerce::add_error( __('Please choose product options&hellip;', 'woothemes') );
				wp_redirect( get_permalink( $_GET['product'] ) );
				exit;
			
			endif; 
		
		elseif ($_GET['add-to-cart']=='group') :
			
			// Group add to cart
			if (isset($_POST['quantity']) && is_array($_POST['quantity'])) :
				
				$total_quantity = 0;
				
				foreach ($_POST['quantity'] as $item => $quantity) :
					if ($quantity>0) :
						woocommerce_cart::add_to_cart($item, $quantity);
						
						if (get_option('woocommerce_cart_redirect_after_add')=='yes') :
							woocommerce::add_message( __('Product successfully added to your basket.', 'woothemes') );
						else :
							woocommerce::add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your basket.', 'woothemes'), woocommerce_cart::get_cart_url()) );
						endif;
						
						$total_quantity = $total_quantity + $quantity;
					endif;
				endforeach;
				
				if ($total_quantity==0) :
					woocommerce::add_error( __('Please choose a quantity&hellip;', 'woothemes') );
				endif;
			
			elseif ($_GET['product']) :
				
				/* Link on product pages */
				woocommerce::add_error( __('Please choose a product&hellip;', 'woothemes') );
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
		elseif (get_option('woocommerce_cart_redirect_after_add')=='yes' && woocommerce::error_count() == 0) {
			wp_safe_redirect( woocommerce_cart::get_cart_url() );
			exit;
		}
		
		// Otherwise redirect to where they came
		elseif ( isset($_SERVER['HTTP_REFERER'])) {
			wp_safe_redirect($_SERVER['HTTP_REFERER']);
			exit;
		}
		
		// If all else fails redirect to root
		else {
			wp_safe_redirect('/');
			exit;
		}
	endif;
	
}

/**
 * Clear cart
 **/
add_action( 'wp_header', 'woocommerce_clear_cart_on_return' );

function woocommerce_clear_cart_on_return() {

	if (is_page(get_option('woocommerce_thanks_page_id'))) :
	
		if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
		if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';
		if ($order_id > 0) :
			$order = &new woocommerce_order( $order_id );
			if ($order->order_key == $order_key) :
				woocommerce_cart::empty_cart();
			endif;
		endif;
		
	endif;

}

/**
 * Clear the cart after payment - order will be processing or complete
 **/
add_action( 'init', 'woocommerce_clear_cart_after_payment' );

function woocommerce_clear_cart_after_payment( $url = false ) {
	
	if (isset($_SESSION['order_awaiting_payment']) && $_SESSION['order_awaiting_payment'] > 0) :
		
		$order = &new woocommerce_order($_SESSION['order_awaiting_payment']);
		
		if ($order->id > 0 && ($order->status=='completed' || $order->status=='processing')) :
			
			woocommerce_cart::empty_cart();
			
			unset($_SESSION['order_awaiting_payment']);
			
		endif;
		
	endif;
	
}


/**
 * Process the login form
 **/
add_action('init', 'woocommerce_process_login');
 
function woocommerce_process_login() {
	
	if (isset($_POST['login']) && $_POST['login']) :
	
		woocommerce::verify_nonce('login');

		if ( !isset($_POST['username']) || empty($_POST['username']) ) woocommerce::add_error( __('Username is required.', 'woothemes') );
		if ( !isset($_POST['password']) || empty($_POST['password']) ) woocommerce::add_error( __('Password is required.', 'woothemes') );
		
		if (woocommerce::error_count()==0) :
			
			$creds = array();
			$creds['user_login'] = $_POST['username'];
			$creds['user_password'] = $_POST['password'];
			$creds['remember'] = true;
			$secure_cookie = is_ssl() ? true : false;
			$user = wp_signon( $creds, $secure_cookie );
			if ( is_wp_error($user) ) :
				woocommerce::add_error( $user->get_error_message() );
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
	include_once(woocommerce::plugin_path() . '/classes/checkout.class.php');

	woocommerce_checkout::instance()->process_checkout();
	
	die(0);
}


/**
 * Cancel a pending order - hook into init function
 **/
add_action('init', 'woocommerce_cancel_order');

function woocommerce_cancel_order() {
	
	if ( isset($_GET['cancel_order']) && isset($_GET['order']) && isset($_GET['order_id']) ) :
		
		$order_key = urldecode( $_GET['order'] );
		$order_id = (int) $_GET['order_id'];
		
		$order = &new woocommerce_order( $order_id );

		if ($order->id == $order_id && $order->order_key == $order_key && $order->status=='pending' && woocommerce::verify_nonce('cancel_order', '_GET')) :
			
			// Cancel the order + restore stock
			$order->cancel_order( __('Order cancelled by customer.', 'woothemes') );
			
			// Message
			woocommerce::add_message( __('Your order was cancelled.', 'woothemes') );
		
		elseif ($order->status!='pending') :
			
			woocommerce::add_error( __('Your order is no longer pending and could not be cancelled. Please contact us if you need assistance.', 'woothemes') );
			
		else :
		
			woocommerce::add_error( __('Invalid order.', 'woothemes') );
			
		endif;
		
		wp_safe_redirect(woocommerce_cart::get_cart_url());
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
			WHERE user_email = '$email'
			AND order_key = '$order'
			AND product_id = '$download_file'
		;") );
		
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
            @ini_set('zlib.output_compression', 'Off');
			@set_time_limit(0);
			@session_start();					
			@session_cache_limiter('none');		
			@set_magic_quotes_runtime(0);
			@ob_end_clean();
			@session_write_close();
			
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
            	
            	@readfile("$file_path") or header('Location: '.$file_path);
            	
            else :
            	
            	if (!file_exists($file_path)) wp_die( sprintf(__('File not found. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
            	 
            	@readfile("$file_path") or wp_die( sprintf(__('File not found. <a href="%s">Go to homepage &rarr;</a>', 'woothemes'), home_url()) );
			
            endif;
            
            exit;
			
		endif;
		
	endif;
}


/**
 * Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 **/
add_action('order_status_completed', 'woocommerce_downloadable_product_permissions');

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