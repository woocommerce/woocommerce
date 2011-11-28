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
require_once('writepanel-order_data.php');
require_once('writepanel-order_notes.php');
require_once('writepanel-coupon_data.php');

/**
 * Init the meta boxes
 * 
 * Inits the write panels for both products and orders. Also removes unused default write panels.
 */
add_action( 'add_meta_boxes', 'woocommerce_meta_boxes' );

function woocommerce_meta_boxes() {
	
	// Products
	add_meta_box( 'woocommerce-product-type', __('Product Type &amp; Visibility', 'woothemes'), 'woocommerce_product_type_box', 'product', 'normal', 'high' );
	add_meta_box( 'woocommerce-product-data', __('Product Data', 'woothemes'), 'woocommerce_product_data_box', 'product', 'normal', 'high' );
	
	// Orders
	add_meta_box( 'woocommerce-order-data', __('Order Data', 'woothemes'), 'woocommerce_order_data_meta_box', 'shop_order', 'normal', 'high' );
	add_meta_box( 'woocommerce-order-items', __('Order Items <small>&ndash; Note: if you edit quantities or remove items from the order you will need to manually change the item\'s stock levels.</small>', 'woothemes'), 'woocommerce_order_items_meta_box', 'shop_order', 'normal', 'high');
	add_meta_box( 'woocommerce-order-totals', __('Order Totals', 'woothemes'), 'woocommerce_order_totals_meta_box', 'shop_order', 'side', 'default');
	add_meta_box( 'woocommerce-order-notes', __('Order Notes', 'woothemes'), 'woocommerce_order_notes_meta_box', 'shop_order', 'side', 'default');
	add_meta_box( 'woocommerce-order-actions', __('Order Actions', 'woothemes'), 'woocommerce_order_actions_meta_box', 'shop_order', 'side', 'high');
	
	remove_meta_box( 'commentsdiv', 'shop_order' , 'normal' );
	remove_meta_box( 'woothemes-settings', 'shop_order' , 'normal' );
	remove_meta_box( 'commentstatusdiv', 'shop_order' , 'normal' );
	remove_meta_box( 'slugdiv', 'shop_order' , 'normal' );
	
	// Coupons
	add_meta_box( 'woocommerce-coupon-data', __('Coupon Data', 'woothemes'), 'woocommerce_coupon_data_meta_box', 'shop_coupon', 'normal', 'high');
	
	remove_meta_box( 'commentstatusdiv', 'shop_coupon' , 'normal' );
	remove_meta_box( 'slugdiv', 'shop_coupon' , 'normal' );
	
	//remove_meta_box('pageparentdiv', 'product_variation', 'side');
	//add_meta_box('product_variation-parent', __('Product', 'woothemes'), 'variations_product_meta_box', 'product_variation', 'side', 'default');
}

/**
 * Title boxes
 */
add_filter('enter_title_here', 'woocommerce_enter_title_here', 1, 2);

function woocommerce_enter_title_here( $text, $post ) {
	if ($post->post_type=='shop_coupon') return __('Coupon code', 'woothemes');
	if ($post->post_type=='product') return __('Product name', 'woothemes');
	return $text;
}

/**
 * Let variations have a product as the parent
 
function variations_product_meta_box($post) {
	$post_type_object = get_post_type_object($post->post_type);
	if ( $post_type_object->hierarchical ) {
			$pages = wp_dropdown_pages(array('post_type' => 'product', 'selected' => $post->post_parent, 'name' => 'parent_id', 'show_option_none' => __('(no parent)'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
		if ( ! empty($pages) ) {
			echo $pages;
		} // end empty pages check
	} // end hierarchical check.
}*/

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
	if ( $post->post_type != 'product' && $post->post_type != 'shop_order' && $post->post_type != 'shop_coupon' ) return $post_id;
	
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
 * Meta scripts
 * 
 * Outputs JavaScript used by the meta panels.
 */
function woocommerce_meta_scripts() {
	?>
	<script type="text/javascript">
		jQuery(function(){
			<?php do_action('woocommerce_product_write_panel_js'); ?>
		});
	</script>
	<?php
}

/**
 * Output write panel form elements
 */
function woocommerce_wp_text_input( $field ) {
	global $thepostid, $post;
	
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['placeholder'])) $field['placeholder'] = '';
	if (!isset($field['class'])) $field['class'] = 'short';
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	
	echo '<p class="form-field '.$field['id'].'_field"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="text" class="'.$field['class'].'" name="'.$field['id'].'" id="'.$field['id'].'" value="'.esc_attr( $field['value'] ).'" placeholder="'.$field['placeholder'].'" /> ';
	
	if (isset($field['description'])) echo '<span class="description">' .$field['description'] . '</span>';
		
	echo '</p>';
}

function woocommerce_wp_checkbox( $field ) {
	global $thepostid, $post;
	
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['class'])) $field['class'] = 'checkbox';
	if (!isset($field['wrapper_class'])) $field['wrapper_class'] = '';
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	
	echo '<p class="form-field '.$field['id'].'_field '.$field['wrapper_class'].'"><label for="'.$field['id'].'">'.$field['label'].'</label><input type="checkbox" class="'.$field['class'].'" name="'.$field['id'].'" id="'.$field['id'].'" ';
	
	checked($field['value'], 'yes');
	
	echo ' /> ';
	
	if (isset($field['description'])) echo '<span class="description">' .$field['description'] . '</span>';
		
	echo '</p>';
}

function woocommerce_wp_select( $field ) {
	global $thepostid, $post;
	
	if (!$thepostid) $thepostid = $post->ID;
	if (!isset($field['class'])) $field['class'] = 'select short';
	if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);
	
	echo '<p class="form-field '.$field['id'].'_field"><label for="'.$field['id'].'">'.$field['label'].'</label><select id="'.$field['id'].'" name="'.$field['id'].'" class="'.$field['class'].'">';
	
	foreach ($field['options'] as $key => $value) :
		
		echo '<option value="'.$key.'" ';
		selected($field['value'], $key);
		echo '>'.$value.'</option>';
		
	endforeach;
	
	echo '</select> ';
	
	if (isset($field['description'])) echo '<span class="description">' .$field['description'] . '</span>';
		
	echo '</p>';
}
