<?php
/**
 * Coupon Data
 * 
 * Functions for displaying the coupon data meta box
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */

/**
 * Coupon data meta box
 * 
 * Displays the meta box
 */
function woocommerce_coupon_data_meta_box($post) {

	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
	
	?>
	<style type="text/css">
		#edit-slug-box { display:none }
	</style>
	<div id="coupon_options" class="panel woocommerce_options_panel">
		<?php

			// Type
			$value = get_post_meta($post->ID, 'discount_type', true);
			$field = array( 'id' => 'discount_type', 'label' => __('Discount type', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].':</label><select name="'.$field['id'].'" id="'.$field['id'].'">';
			
			$discount_types = apply_filters('woocommerce_coupon_discount_types', array(
    			'fixed_cart' 	=> __('Cart Discount', 'woothemes'),
    			'percent' 		=> __('Cart % Discount', 'woothemes'),
    			'fixed_product'	=> __('Product Discount', 'woothemes')
    		));
    		
    		foreach ($discount_types as $type => $label) :
    			echo '<option value="'.$type.'" ';
    			selected($type, $value);
    			echo '>'.$label.'</option>';
    		endforeach;
				
			echo '</select></p>';
				
			// Amount
			$value = get_post_meta($post->ID, 'coupon_amount', true);
			$field = array( 'id' => 'coupon_amount', 'label' => __('Coupon amount', 'woothemes') );
			echo '<p class="form-field">
				<label for="'.$field['id'].'">'.$field['label'].':</label>
				<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.esc_attr( $value ).'" /> <span class="description">' . __('Enter an amount e.g. 2.99 or an integer for percentages e.g. 20', 'woothemes') . '</span></p>';
				
			// Individual use
			$value = get_post_meta($post->ID, 'individual_use', true);
			$field = array( 'id' => 'individual_use', 'label' => __('Individual use', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].':</label>';
			echo '<input type="checkbox" class="checkbox" name="'.$field['id'].'" id="'.$field['id'].'" ';
			checked($value, 1);
			echo ' /> <span class="description">' . __('Check this box if the coupon cannot be used in conjuction with other coupons', 'woothemes');
			echo '</span></p>';
			
			// Product ids
			$value = get_post_meta($post->ID, 'product_ids', true);
			$field = array( 'id' => 'product_ids', 'label' => __('Product IDs', 'woothemes') );
			echo '<p class="form-field">
				<label for="'.$field['id'].'">'.$field['label'].':</label>
				<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.esc_attr( $value ).'" /> <span class="description">' . __('(optional) Comma separate product IDs which are required for this coupon to work', 'woothemes') . '</span></p>';
			
			// Usage limit
			$value = get_post_meta($post->ID, 'usage_limit', true);
			$field = array( 'id' => 'usage_limit', 'label' => __('Usage limit', 'woothemes') );
			echo '<p class="form-field">
				<label for="'.$field['id'].'">'.$field['label'].':</label>
				<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.esc_attr( $value ).'" /> <span class="description">' . __('(optional) How many times this coupon can be used before it is void', 'woothemes') . '</span></p>';
				
			// Expiry date
			$value = get_post_meta($post->ID, 'expiry_date', true);
			$field = array( 'id' => 'expiry_date', 'label' => __('Expiry date', 'woothemes') );
			echo '<p class="form-field">
				<label for="'.$field['id'].'">'.$field['label'].':</label>
				<input type="text" class="short date-picker" name="'.$field['id'].'" id="'.$field['id'].'" value="'.esc_attr( $value ).'" /> <span class="description">' . __('(optional) The date this coupon will expire, <code>YYYY-MM-DD</code>', 'woothemes') . '</span></p>';

		?>
	</div>
	<?php	
}

/**
 * Coupon data meta box
 * 
 * Displays the meta box
 */
add_filter('enter_title_here', 'woocommerce_coupon_enter_title_here', 1, 2);

function woocommerce_coupon_enter_title_here( $text, $post ) {
	if ($post->post_type=='shop_coupon') return __('Coupon code', 'woothemes');
	return $text;
}

/**
 * Coupon Data Save
 * 
 * Function for processing and storing all coupon data.
 */
add_action('woocommerce_process_shop_coupon_meta', 'woocommerce_process_shop_coupon_meta', 1, 2);

function woocommerce_process_shop_coupon_meta( $post_id, $post ) {
	global $wpdb;
	
	$woocommerce_errors = array();
	
	if (!$_POST['coupon_amount']) $woocommerce_errors[] = __('Coupon amount is required', 'woothemes');

	// Add/Replace data to array
		$type 			= strip_tags(stripslashes( $_POST['discount_type'] ));
		$amount 		= strip_tags(stripslashes( $_POST['coupon_amount'] ));
		$product_ids 	= strip_tags(stripslashes( $_POST['product_ids'] ));
		$usage_limit 	= (isset($_POST['usage_limit']) && $_POST['usage_limit']>0) ? (int) $_POST['usage_limit'] : '';
		$individual_use = isset($_POST['individual_use']) ? 1 : 0;
		$expiry_date 		= strip_tags(stripslashes( $_POST['expiry_date'] ));
	
	// Save
		update_post_meta( $post_id, 'discount_type', $type );
		update_post_meta( $post_id, 'coupon_amount', $amount );
		update_post_meta( $post_id, 'individual_use', $individual_use );
		update_post_meta( $post_id, 'product_ids', $product_ids );
		update_post_meta( $post_id, 'usage_limit', $usage_limit );
		update_post_meta( $post_id, 'expiry_date', $expiry_date );
	
	// Error Handling
		if (sizeof($woocommerce_errors)>0) update_option('woocommerce_errors', $woocommerce_errors);
}