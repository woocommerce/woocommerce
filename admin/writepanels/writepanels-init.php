<?php
/**
 * WooCommerce Write Panels
 * 
 * Sets up the write panels used by products and orders (custom post types)
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */

require_once('writepanel-product_data.php');
require_once('writepanel-product-types.php');
require_once('writepanel-order_data.php');

/**
 * Init the meta boxes
 * 
 * Inits the write panels for both products and orders. Also removes unused default write panels.
 */
add_action( 'add_meta_boxes', 'woocommerce_meta_boxes' );

function woocommerce_meta_boxes() {
	add_meta_box( 'woocommerce-product-data', __('Product Data', 'woothemes'), 'woocommerce_product_data_box', 'product', 'normal', 'high' );
	add_meta_box( 'woocommerce-product-type-options', __('Product Type Options', 'woothemes'), 'woocommerce_product_type_options_box', 'product', 'normal', 'high' );
	
	add_meta_box( 'woocommerce-order-data', __('Order Data', 'woothemes'), 'woocommerce_order_data_meta_box', 'shop_order', 'normal', 'high' );
	add_meta_box( 'woocommerce-order-items', __('Order Items <small>&ndash; Note: if you edit quantities or remove items from the order you will need to manually change the item\'s stock levels.</small>', 'woothemes'), 'woocommerce_order_items_meta_box', 'shop_order', 'normal', 'high');
	add_meta_box( 'woocommerce-order-totals', __('Order Totals', 'woothemes'), 'woocommerce_order_totals_meta_box', 'shop_order', 'side', 'default');
	
	add_meta_box( 'woocommerce-order-actions', __('Order Actions', 'woothemes'), 'woocommerce_order_actions_meta_box', 'shop_order', 'side', 'default');
	
	remove_meta_box( 'commentstatusdiv', 'shop_order' , 'normal' );
	remove_meta_box( 'slugdiv', 'shop_order' , 'normal' );
}

/**
 * Save meta boxes
 * 
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 */
add_action( 'save_post', 'woocommerce_meta_boxes_save', 1, 2 );

function woocommerce_meta_boxes_save( $post_id, $post ) {
	global $wpdb;
	
	if ( !$_POST ) return $post_id;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	if ( !isset($_POST['woocommerce_meta_nonce']) || (isset($_POST['woocommerce_meta_nonce']) && !wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ))) return $post_id;
	if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
	if ( $post->post_type != 'product' && $post->post_type != 'shop_order' ) return $post_id;
	
	do_action( 'woocommerce_process_'.$post->post_type.'_meta', $post_id, $post );
}

/**
 * Product data
 * 
 * Forces certain product data based on the product's type, e.g. grouped products cannot have a parent.
 */
add_filter('wp_insert_post_data', 'woocommerce_product_data');

function woocommerce_product_data( $data ) {
	global $post;
	if ($data['post_type']=='product' && isset($_POST['product-type'])) {
		$product_type = stripslashes( $_POST['product-type'] );
		switch($product_type) :
			case "grouped" :
			case "variable" :
				$data['post_parent'] = 0;
			break;
		endswitch;
	}
	return $data;
}

/**
 * Order data
 * 
 * Forces the order posts to have a title in a certain format (containing the date)
 */
add_filter('wp_insert_post_data', 'woocommerce_order_data');

function woocommerce_order_data( $data ) {
	global $post;
	if ($data['post_type']=='shop_order' && isset($data['post_date'])) {
		
		$order_title = 'Order';
		if ($data['post_date']) $order_title.= ' &ndash; '.date('F j, Y @ h:i A', strtotime($data['post_date']));
		
		$data['post_title'] = $order_title;
	}
	return $data;
}


/**
 * Save errors
 * 
 * Stores error messages in a variable so they can be displayed on the edit post screen after saving.
 */
add_action( 'admin_notices', 'woocommerce_meta_boxes_save_errors' );

function woocommerce_meta_boxes_save_errors() {
	$woocommerce_errors = maybe_unserialize(get_option('woocommerce_errors'));
    if ($woocommerce_errors && sizeof($woocommerce_errors)>0) :
    	echo '<div id="woocommerce_errors" class="error fade">';
    	foreach ($woocommerce_errors as $error) :
    		echo '<p>'.$error.'</p>';
    	endforeach;
    	echo '</div>';
    	update_option('woocommerce_errors', '');
    endif; 
}

/**
 * Enqueue scripts
 * 
 * Enqueue JavaScript used by the meta panels.
 */
function woocommerce_write_panel_scripts() {
	
	$post_type = woocommerce_get_current_post_type();
	
	if( $post_type !== 'product' && $post_type !== 'shop_order' ) return;
	
	wp_register_script('woocommerce-date', woocommerce::plugin_url() . '/assets/js/date.js');
	wp_register_script('woocommerce-datepicker', woocommerce::plugin_url() . '/assets/js/datepicker.js', array('jquery', 'woocommerce-date'));
	
	wp_enqueue_script('woocommerce-datepicker');
	
	wp_register_script('woocommerce-writepanel', woocommerce::plugin_url() . '/assets/js/write-panels.js', array('jquery'));
	wp_enqueue_script('woocommerce-writepanel');
	
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_style('thickbox');
	
	$params = array( 
		'remove_item_notice' 			=>  __("Remove this item? If you have previously reduced this item's stock, or this order was submitted by a customer, will need to manually restore the item's stock.", 'woothemes'),
		'cart_total' 					=> __("Calc totals based on order items, discount amount, and shipping?", 'woothemes'),
		'copy_billing' 					=> __("Copy billing information to shipping information? This will remove any currently entered shipping information.", 'woothemes'),
		'prices_include_tax' 			=> get_option('woocommerce_prices_include_tax'),
		'ID' 							=>  __('ID', 'woothemes'),
		'item_name' 					=> __('Item Name', 'woothemes'),
		'quantity' 						=> __('Quantity e.g. 2', 'woothemes'),
		'cost_unit' 					=> __('Cost per unit e.g. 2.99', 'woothemes'),
		'tax_rate' 						=> __('Tax Rate e.g. 20.0000', 'woothemes'),
		'meta_name'						=> __('Meta Name', 'woothemes'),
		'meta_value'					=> __('Meta Value', 'woothemes'),
		'plugin_url' 					=> woocommerce::plugin_url(),
		'ajax_url' 						=> admin_url('admin-ajax.php'),
		'add_order_item_nonce' 			=> wp_create_nonce("add-order-item"),
		'upsell_crosssell_search_products_nonce' => wp_create_nonce("search-products")
	 );
				 
	wp_localize_script( 'woocommerce-writepanel', 'params', $params );
	
	
}
add_action('admin_print_scripts-post.php', 'woocommerce_write_panel_scripts');
add_action('admin_print_scripts-post-new.php', 'woocommerce_write_panel_scripts');

/**
 * Meta scripts
 * 
 * Outputs JavaScript used by the meta panels.
 */
function woocommerce_meta_scripts() {
	?>
	<script type="text/javascript">
		jQuery(function(){
			<?php do_action('product_write_panel_js'); ?>
		});
	</script>
	<?php
}