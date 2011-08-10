<?php
/**
 * Downloadable Product Type
 * 
 * Functions specific to downloadable products (for the write panels)
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */
  
/**
 * Product Options
 * 
 * Product Options for the downloadable product type
 */
function downloadable_product_type_options() {
	global $post;
	?>
	<div id="downloadable_product_options" class="panel woocommerce_options_panel">
		<?php

			// File URL
			$file_path = get_post_meta($post->ID, 'file_path', true);
			$field = array( 'id' => 'file_path', 'label' => __('File path', 'woothemes') );
			echo '<p class="form-field">
				<label for="'.$field['id'].'">'.$field['label'].':</label>
				<span style="float:left">'.ABSPATH.'</span><input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$file_path.'" placeholder="'.__('path to file on your server', 'woothemes').'" /></p>';
				
			// Download Limit
			$download_limit = get_post_meta($post->ID, 'download_limit', true);
			$field = array( 'id' => 'download_limit', 'label' => __('Download Limit', 'woothemes') );
			echo '<p class="form-field">
				<label for="'.$field['id'].'">'.$field['label'].':</label>
				<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$download_limit.'" /> <span class="description">' . __('Leave blank for unlimited re-downloads.', 'woothemes') . '</span></p>';

		?>
	</div>
	<?php
}
add_action('woocommerce_product_type_options_box', 'downloadable_product_type_options');

/**
 * Product Type selector
 * 
 * Adds this product type to the product type selector in the product options meta box
 */
function downloadable_product_type_selector( $product_type ) {
	
	echo '<option value="downloadable" '; if ($product_type=='downloadable') echo 'selected="selected"'; echo '>'.__('Downloadable', 'woothemes').'</option>';

}
add_action('product_type_selector', 'downloadable_product_type_selector');

/**
 * Process meta
 * 
 * Processes this product types options when a post is saved
 */
function filter_product_meta_downloadable( $data, $post_id ) {
	
	if (isset($_POST['file_path']) && $_POST['file_path']) update_post_meta( $post_id, 'file_path', $_POST['file_path'] );
	if (isset($_POST['download_limit'])) update_post_meta( $post_id, 'download_limit', $_POST['download_limit'] );
	
	return $data;

}
add_filter('filter_product_meta_downloadable', 'filter_product_meta_downloadable', 1, 2);