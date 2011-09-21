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
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].':</label>
				<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$file_path.'" placeholder="'.__('File path/URL', 'woothemes').'" />
				<input type="button"  class="upload_file_button button" value="'.__('Upload a file', 'woothemes').'" />
			</p>';
				
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
 * Product Type Javascript
 * 
 * Javascript for the downloadable product type
 */
function downloadable_product_write_panel_js() {
	global $post;
	?>
	jQuery(function(){

		window.send_to_editor_default = window.send_to_editor;

		jQuery('.upload_file_button').live('click', function(){
			
			var post_id = <?php echo $post->ID; ?>;
			
			formfield = jQuery('#file_path').attr('name');
			
			window.send_to_editor = window.send_to_download_url;
			
			tb_show('', 'media-upload.php?post_id=' + post_id + '&amp;type=file&amp;from=wc01&amp;TB_iframe=true');
			return false;
		});

		window.send_to_download_url = function(html) {
			
			file_url = jQuery(html).attr('href');
			if (file_url) {
				jQuery('#file_path').val(file_url);
			}
			tb_remove();
			window.send_to_editor = window.send_to_editor_default;
			
		}
		
	});
	<?php
}
add_action('woocommerce_product_write_panel_js', 'downloadable_product_write_panel_js');

add_filter( 'gettext', 'woocommerce_change_insert_into_post', null, 2 );

function woocommerce_change_insert_into_post( $translation, $original ) {
    if( !isset( $_REQUEST['from'] ) ) return $translation;

    if( $_REQUEST['from'] == 'wc01' && $original == 'Insert into Post' ) return __('Insert into URL field', 'woothemes' );

    return $translation;
}


/**
 * Product Type selector
 * 
 * Adds this product type to the product type selector in the product options meta box
 */
function downloadable_product_type_selector( $types, $product_type ) {
	$types['downloadable'] = __('Downloadable', 'woothemes');
	return $types;
}
add_filter('product_type_selector', 'downloadable_product_type_selector', 1, 2);

/**
 * Process meta
 * 
 * Processes this product types options when a post is saved
 */
function process_product_meta_downloadable( $post_id ) {
	
	if (isset($_POST['file_path']) && $_POST['file_path']) update_post_meta( $post_id, 'file_path', $_POST['file_path'] );
	if (isset($_POST['download_limit'])) update_post_meta( $post_id, 'download_limit', $_POST['download_limit'] );

}
add_action('woocommerce_process_product_meta_downloadable', 'process_product_meta_downloadable');