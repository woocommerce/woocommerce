<?php
/**
 * ACTIONS
 *
 * Various hooks Jigoshop uses to do stuff. index:
 *
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
 **/


/**
 * Get variation
 *
 * Get variation price etc when using frontend form
 *
 * @since 		1.0
 */
add_action('wp_ajax_jigoshop_get_variation', 'display_variation_data');
add_action('wp_ajax_nopriv_jigoshop_get_variation', 'display_variation_data');

function display_variation_data() {
	
	check_ajax_referer( 'get-variation', 'security' );
	
	// get variation terms
	$variation_query 	= array();
	$variation_data 	= array();
	parse_str( $_POST['variation_data'], $variation_data );
	
	$variation_id = jigoshop_find_variation( $variation_data );
	
	if (!$variation_id) die();
	
	$_product = &new jigoshop_product_variation($variation_id);
	
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
		'price_html' 		=> '<span class="price">'.$_product->get_price_html().'</span>',
		'availability_html' => $availability_html,
		'image_src'			=> $image,
		'image_link'		=> $image_link
	);
	
	echo json_encode( $data );

	// Quit out
	die();
}

/**
 * Add order item
 *
 * Add order item via ajax
 *
 * @since 		1.0
 */
add_action('wp_ajax_jigoshop_add_order_item', 'jigoshop_add_order_item');

function jigoshop_add_order_item() {
	
	check_ajax_referer( 'add-order-item', 'security' );
	
	global $wpdb;
	
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
			WHERE $wpdb->postmeta.meta_key = 'SKU'
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
		$_product = &new jigoshop_product( $post->ID );
	else :
		$_product = &new jigoshop_product_variation( $post->ID );
	endif;
	
	$loop = 0;
	?>
	<tr class="item">
		<td class="product-id">#<?php echo $_product->id; ?></td>
		<td class="variation-id"><?php if (isset($_product->variation_id)) echo $_product->variation_id; else echo '-'; ?></td>
		<td class="product-sku"><?php if ($_product->sku) echo $_product->sku; ?></td>
		<td class="name"><a href="<?php echo admin_url('post.php?post='. $_product->id .'&action=edit'); ?>"><?php echo $_product->get_title(); ?></a></td>
		<td class="variation"><?php
			if (isset($_product->variation_data)) :
				echo jigoshop_get_formatted_variation( $_product->variation_data, true );
			else :
				echo '-';
			endif;
		?></td>
		<td>
			<table class="meta" cellspacing="0">
				<tfoot>
					<tr>
						<td colspan="3"><button class="add_meta button"><?php _e('Add meta', 'jigoshop'); ?></button></td>
					</tr>
				</tfoot>
				<tbody></tbody>
			</table>
		</td>
		<?php do_action('jigoshop_admin_order_item_values', $_product); ?>
		<td class="quantity"><input type="text" name="item_quantity[]" placeholder="<?php _e('Quantity e.g. 2', 'jigoshop'); ?>" value="1" /></td>
		<td class="cost"><input type="text" name="item_cost[]" placeholder="<?php _e('Cost per unit ex. tax e.g. 2.99', 'jigoshop'); ?>" value="<?php echo $_product->get_price(); ?>" /></td>
		<td class="tax"><input type="text" name="item_tax_rate[]" placeholder="<?php _e('Tax Rate e.g. 20.0000', 'jigoshop'); ?>" value="<?php echo $_product->get_tax_base_rate(); ?>" /></td>
		<td class="center">
			<input type="hidden" name="item_id[]" value="<?php echo $_product->id; ?>" />
			<input type="hidden" name="item_name[]" value="<?php echo $_product->get_title(); ?>" />
			<button type="button" class="remove_row button">&times;</button>
		</td>
	</tr>
	<?php
	
	// Quit out
	die();
	
}


/**
 * When default permalinks are enabled, redirect shop page to post type archive url
 **/
if (get_option( 'permalink_structure' )=="") add_action( 'init', 'jigoshop_shop_page_archive_redirect' );

function jigoshop_shop_page_archive_redirect() {
	
	if ( isset($_GET['page_id']) && $_GET['page_id'] == get_option('jigoshop_shop_page_id') ) :
		wp_safe_redirect( get_post_type_archive_link('product') );
		exit;
	endif;
	
}

/**
 * Remove from cart/update
 **/
add_action( 'init', 'jigoshop_update_cart_action' );

function jigoshop_update_cart_action() {

	// Remove from cart
	if ( isset($_GET['remove_item']) && is_numeric($_GET['remove_item'])  && jigoshop::verify_nonce('cart', '_GET')) :
	
		jigoshop_cart::set_quantity( $_GET['remove_item'], 0 );
		
		// Re-calc price
		//jigoshop_cart::calculate_totals();
			
		jigoshop::add_message( __('Cart updated.', 'jigoshop') );
		
		if ( isset($_SERVER['HTTP_REFERER'])) :
			wp_safe_redirect($_SERVER['HTTP_REFERER']);
			exit;
		endif;
	
	// Update Cart
	elseif (isset($_POST['update_cart']) && $_POST['update_cart']  && jigoshop::verify_nonce('cart')) :
		
		$cart_totals = $_POST['cart'];
		
		if (sizeof(jigoshop_cart::$cart_contents)>0) : 
			foreach (jigoshop_cart::$cart_contents as $cart_item_key => $values) :
				
				if (isset($cart_totals[$cart_item_key]['qty'])) jigoshop_cart::set_quantity( $cart_item_key, $cart_totals[$cart_item_key]['qty'] );
				
			endforeach;
		endif;
		
		jigoshop::add_message( __('Cart updated.', 'jigoshop') );
		
	endif;

}

/**
 * Add to cart
 **/
add_action( 'init', 'jigoshop_add_to_cart_action' );

function jigoshop_add_to_cart_action( $url = false ) {
	
	if (isset($_GET['add-to-cart']) && $_GET['add-to-cart']) :
	
		if ( !jigoshop::verify_nonce('add_to_cart', '_GET') ) :

		elseif (is_numeric($_GET['add-to-cart'])) :
		
			$quantity = 1;
			if (isset($_POST['quantity'])) $quantity = $_POST['quantity'];
			jigoshop_cart::add_to_cart($_GET['add-to-cart'], $quantity);
			
			jigoshop::add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your basket.', 'jigoshop'), jigoshop_cart::get_cart_url()) );
		
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
					jigoshop::add_error( __('Please choose product options&hellip;', 'jigoshop') );
				else :
					// Find matching variation
					$variation_id = jigoshop_find_variation( $variations );
					
					if ($variation_id>0) :
						// Add to cart
						jigoshop_cart::add_to_cart($product_id, $quantity, $variations, $variation_id);
						jigoshop::add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your basket.', 'jigoshop'), jigoshop_cart::get_cart_url()) );
					else :
						jigoshop::add_error( __('Sorry, this variation is not available.', 'jigoshop') );
					endif;
				endif;
			
			elseif ($_GET['product']) :
				
				/* Link on product pages */
				jigoshop::add_error( __('Please choose product options&hellip;', 'jigoshop') );
				wp_redirect( get_permalink( $_GET['product'] ) );
				exit;
			
			endif; 
		
		elseif ($_GET['add-to-cart']=='group') :
			
			// Group add to cart
			if (isset($_POST['quantity']) && is_array($_POST['quantity'])) :
				
				$total_quantity = 0;
				
				foreach ($_POST['quantity'] as $item => $quantity) :
					if ($quantity>0) :
						jigoshop_cart::add_to_cart($item, $quantity);
						jigoshop::add_message( sprintf(__('<a href="%s" class="button">View Cart &rarr;</a> Product successfully added to your basket.', 'jigoshop'), jigoshop_cart::get_cart_url()) );
						$total_quantity = $total_quantity + $quantity;
					endif;
				endforeach;
				
				if ($total_quantity==0) :
					jigoshop::add_error( __('Please choose a quantity&hellip;', 'jigoshop') );
				endif;
			
			elseif ($_GET['product']) :
				
				/* Link on product pages */
				jigoshop::add_error( __('Please choose a product&hellip;', 'jigoshop') );
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
		
		// Otherwise redirect to where they came
		else if ( isset($_SERVER['HTTP_REFERER'])) {
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
add_action( 'wp_header', 'jigoshop_clear_cart_on_return' );

function jigoshop_clear_cart_on_return() {

	if (is_page(get_option('jigoshop_thanks_page_id'))) :
	
		if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
		if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';
		if ($order_id > 0) :
			$order = &new jigoshop_order( $order_id );
			if ($order->order_key == $order_key) :
				jigoshop_cart::empty_cart();
			endif;
		endif;
		
	endif;

}

/**
 * Clear the cart after payment - order will be processing or complete
 **/
add_action( 'init', 'jigoshop_clear_cart_after_payment' );

function jigoshop_clear_cart_after_payment( $url = false ) {
	
	if (isset($_SESSION['order_awaiting_payment']) && $_SESSION['order_awaiting_payment'] > 0) :
		
		$order = &new jigoshop_order($_SESSION['order_awaiting_payment']);
		
		if ($order->id > 0 && ($order->status=='completed' || $order->status=='processing')) :
			
			jigoshop_cart::empty_cart();
			
			unset($_SESSION['order_awaiting_payment']);
			
		endif;
		
	endif;
	
}


/**
 * Process the login form
 **/
add_action('init', 'jigoshop_process_login');
 
function jigoshop_process_login() {
	
	if (isset($_POST['login']) && $_POST['login']) :
	
		jigoshop::verify_nonce('login');

		if ( !isset($_POST['username']) || empty($_POST['username']) ) jigoshop::add_error( __('Username is required.', 'jigoshop') );
		if ( !isset($_POST['password']) || empty($_POST['password']) ) jigoshop::add_error( __('Password is required.', 'jigoshop') );
		
		if (jigoshop::error_count()==0) :
			
			$creds = array();
			$creds['user_login'] = $_POST['username'];
			$creds['user_password'] = $_POST['password'];
			$creds['remember'] = true;
			$secure_cookie = is_ssl() ? true : false;
			$user = wp_signon( $creds, $secure_cookie );
			if ( is_wp_error($user) ) :
				jigoshop::add_error( $user->get_error_message() );
			else :
				if ( isset($_SERVER['HTTP_REFERER'])) {
					wp_safe_redirect($_SERVER['HTTP_REFERER']);
					exit;
				}
				wp_redirect(get_permalink(get_option('jigoshop_myaccount_page_id')));
				exit;
			endif;
			
		endif;
	
	endif;	
}

/**
 * Process ajax checkout form
 */
add_action('wp_ajax_jigoshop-checkout', 'jigoshop_process_checkout');
add_action('wp_ajax_nopriv_jigoshop-checkout', 'jigoshop_process_checkout');

function jigoshop_process_checkout () {
	include_once jigoshop::plugin_path() . '/classes/jigoshop_checkout.class.php';

	jigoshop_checkout::instance()->process_checkout();
	
	die(0);
}


/**
 * Cancel a pending order - hook into init function
 **/
add_action('init', 'jigoshop_cancel_order');

function jigoshop_cancel_order() {
	
	if ( isset($_GET['cancel_order']) && isset($_GET['order']) && isset($_GET['order_id']) ) :
		
		$order_key = urldecode( $_GET['order'] );
		$order_id = (int) $_GET['order_id'];
		
		$order = &new jigoshop_order( $order_id );

		if ($order->id == $order_id && $order->order_key == $order_key && $order->status=='pending' && jigoshop::verify_nonce('cancel_order', '_GET')) :
			
			// Cancel the order + restore stock
			$order->cancel_order( __('Order cancelled by customer.', 'jigoshop') );
			
			// Message
			jigoshop::add_message( __('Your order was cancelled.', 'jigoshop') );
		
		elseif ($order->status!='pending') :
			
			jigoshop::add_error( __('Your order is no longer pending and could not be cancelled. Please contact us if you need assistance.', 'jigoshop') );
			
		else :
		
			jigoshop::add_error( __('Invalid order.', 'jigoshop') );
			
		endif;
		
		wp_safe_redirect(jigoshop_cart::get_cart_url());
		exit;
		
	endif;
}


/**
 * Download a file - hook into init function
 **/
add_action('init', 'jigoshop_download_product');

function jigoshop_download_product() {
	
	if ( isset($_GET['download_file']) && isset($_GET['order']) && isset($_GET['email']) ) :
		
		global $wpdb;
		
		$download_file = (int) urldecode($_GET['download_file']);
		$order = urldecode( $_GET['order'] );
		$email = urldecode( $_GET['email'] );
		
		if (!is_email($email)) wp_safe_redirect( home_url() );
		
		$downloads_remaining = $wpdb->get_var( $wpdb->prepare("
			SELECT downloads_remaining 
			FROM ".$wpdb->prefix."jigoshop_downloadable_product_permissions
			WHERE user_email = '$email'
			AND order_key = '$order'
			AND product_id = '$download_file'
		;") );
		
		if ($downloads_remaining=='0') :
			wp_die( sprintf(__('Sorry, you have reached your download limit for this file. <a href="%s">Go to homepage &rarr;</a>', 'jigoshop'), home_url()) );
		else :
			
			if ($downloads_remaining>0) :
				$wpdb->update( $wpdb->prefix . "jigoshop_downloadable_product_permissions", array( 
					'downloads_remaining' => $downloads_remaining - 1, 
				), array( 
					'user_email' => $email,
					'order_key' => $order,
					'product_id' => $download_file 
				), array( '%d' ), array( '%s', '%s', '%d' ) );
			endif;
		
			// Download the file
			$file_path = ABSPATH . get_post_meta($download_file, 'file_path', true);			
			
            $file_path = realpath($file_path);

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

            if (!file_exists($file_path)) wp_die( sprintf(__('File not found. <a href="%s">Go to homepage &rarr;</a>', 'jigoshop'), home_url()) );
			
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
							
            header("Content-Length: ".@filesize($file_path));
            @readfile("$file_path") or wp_die( sprintf(__('File not found. <a href="%s">Go to homepage &rarr;</a>', 'jigoshop'), home_url()) );
			exit;
			
		endif;
		
	endif;
}


/**
 * Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 **/
add_action('order_status_completed', 'jigoshop_downloadable_product_permissions');

function jigoshop_downloadable_product_permissions( $order_id ) {
	
	global $wpdb;
	
	$order = &new jigoshop_order( $order_id );
	
	if (sizeof($order->items)>0) foreach ($order->items as $item) :
	
		if ($item['id']>0) :
			$_product = &new jigoshop_product( $item['id'] );
			
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
				$wpdb->insert( $wpdb->prefix . 'jigoshop_downloadable_product_permissions', array( 
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