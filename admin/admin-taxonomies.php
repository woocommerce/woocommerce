<?php
/**
 * Functions used for taxonomies in admin 
 *
 * These functions control admin interface bits like category ordering.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

/**
 * Category thumbnails
 */
add_action('product_cat_add_form_fields', 'woocommerce_add_category_thumbnail_field');
add_action('product_cat_edit_form_fields', 'woocommerce_edit_category_thumbnail_field', 10,2);

function woocommerce_add_category_thumbnail_field() {
	global $woocommerce;
	?>
	<div class="form-field">
		<label><?php _e('Thumbnail', 'woothemes'); ?></label>
		<div id="product_cat_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo $woocommerce->plugin_url().'/assets/images/placeholder.png' ?>" width="60px" height="60px" /></div>
		<div style="line-height:60px;">
			<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" />
			<button type="submit" class="upload_image_button button"><?php _e('Upload/Add image', 'woothemes'); ?></button>
			<button type="submit" class="remove_image_button button"><?php _e('Remove image', 'woothemes'); ?></button>
		</div>
		<script type="text/javascript">
			
				window.send_to_termmeta = function(html) {
					
					jQuery('body').append('<div id="temp_image">' + html + '</div>');
					
					var img = jQuery('#temp_image').find('img');
					
					imgurl 		= img.attr('src');
					imgclass 	= img.attr('class');
					imgid		= parseInt(imgclass.replace(/\D/g, ''), 10);
					
					jQuery('#product_cat_thumbnail_id').val(imgid);
					jQuery('#product_cat_thumbnail img').attr('src', imgurl);
					jQuery('#temp_image').remove();
					
					tb_remove();
				}
				
				jQuery('.upload_image_button').live('click', function(){
					var post_id = 0;
					
					window.send_to_editor = window.send_to_termmeta;
					
					tb_show('', 'media-upload.php?post_id=' + post_id + '&amp;type=image&amp;TB_iframe=true');
					return false;
				});
				
				jQuery('.remove_image_button').live('click', function(){
					jQuery('#product_cat_thumbnail img').attr('src', '<?php echo $woocommerce->plugin_url().'/assets/images/placeholder.png'; ?>');
					jQuery('#product_cat_thumbnail_id').val('');
					return false;
				});
			
		</script>
		<div class="clear"></div>
	</div>
	<?php
}

function woocommerce_edit_category_thumbnail_field( $term, $taxonomy ) {
	global $woocommerce;
	
	$image 			= '';
	$thumbnail_id 	= get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true );
	if ($thumbnail_id) :
		$image = wp_get_attachment_url( $thumbnail_id );
	else :
		$image = $woocommerce->plugin_url().'/assets/images/placeholder.png';
	endif;
	?>
	<tr class="form-field">
		<th scope="row" valign="top"><label><?php _e('Thumbnail', 'woothemes'); ?></label></th>
		<td>
			<div id="product_cat_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo $image; ?>" width="60px" height="60px" /></div>
			<div style="line-height:60px;">
				<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" value="<?php echo $thumbnail_id; ?>" />
				<button type="submit" class="upload_image_button button"><?php _e('Upload/Add image', 'woothemes'); ?></button>
				<button type="submit" class="remove_image_button button"><?php _e('Remove image', 'woothemes'); ?></button>
			</div>
			<script type="text/javascript">
				
				window.send_to_termmeta = function(html) {
					
					jQuery('body').append('<div id="temp_image">' + html + '</div>');
					
					var img = jQuery('#temp_image').find('img');
					
					imgurl 		= img.attr('src');
					imgclass 	= img.attr('class');
					imgid		= parseInt(imgclass.replace(/\D/g, ''), 10);
					
					jQuery('#product_cat_thumbnail_id').val(imgid);
					jQuery('#product_cat_thumbnail img').attr('src', imgurl);
					jQuery('#temp_image').remove();
					
					tb_remove();
				}
				
				jQuery('.upload_image_button').live('click', function(){
					var post_id = 0;
					
					window.send_to_editor = window.send_to_termmeta;
					
					tb_show('', 'media-upload.php?post_id=' + post_id + '&amp;type=image&amp;TB_iframe=true');
					return false;
				});
				
				jQuery('.remove_image_button').live('click', function(){
					jQuery('#product_cat_thumbnail img').attr('src', '<?php echo $woocommerce->plugin_url().'/assets/images/placeholder.png'; ?>');
					jQuery('#product_cat_thumbnail_id').val('');
					return false;
				});
				
			</script>
			<div class="clear"></div>
		</td>
	</tr>
	<?php
}

add_action('created_term', 'woocommerce_category_thumbnail_field_save', 10,3);
add_action('edit_term', 'woocommerce_category_thumbnail_field_save', 10,3);

function woocommerce_category_thumbnail_field_save( $term_id, $tt_id, $taxonomy ) {
	if (isset($_POST['product_cat_thumbnail_id'])) {
		update_woocommerce_term_meta($term_id, 'thumbnail_id', $_POST['product_cat_thumbnail_id']);
    }
}


/**
 * Category/Term ordering
 */

/**
 * Reorder on term insertion
 */
add_action("create_term", 'woocommerce_create_term', 5, 3);

function woocommerce_create_term( $term_id, $tt_id, $taxonomy ) {
	
	if (!$taxonomy=='product_cat' && !strstr($taxonomy, 'pa_')) return;
	
	$next_id = null;
	
	$term = get_term($term_id, $taxonomy);
	
	// gets the sibling terms
	$siblings = get_terms($taxonomy, "parent={$term->parent}&menu_order=ASC&hide_empty=0");
	
	foreach ($siblings as $sibling) {
		if( $sibling->term_id == $term_id ) continue;
		$next_id =  $sibling->term_id; // first sibling term of the hierarchy level
		break;
	}

	// reorder
	woocommerce_order_terms( $term, $next_id, $taxonomy );
}

/**
 * Delete terms metas on deletion
 */
add_action("delete_product_term", 'woocommerce_delete_term', 5, 3);

function woocommerce_delete_term( $term_id, $tt_id, $taxonomy ) {
	
	$term_id = (int) $term_id;
	
	if(!$term_id) return;
	
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->woocommerce_termmeta} WHERE `woocommerce_term_id` = " . $term_id);
	
}

/**
 * Description for product_cat page
 */
add_action('product_cat_pre_add_form', 'woocommerce_product_cat_description');

function woocommerce_product_cat_description() {

	echo wpautop(__('Product categories for your store can be managed here. To change the order of categories on the front-end you can drag and drop to sort them. To see more categories listed click the "screen options" link at the top of the page.', 'woothemes'));

}


/**
 * Description for shipping class page
 */
add_action('product_shipping_class_pre_add_form', 'woocommerce_shipping_class_description');

function woocommerce_shipping_class_description() {

	echo wpautop(__('Shipping classes can be used to group products of similar type. These groups can then be used by certain shipping methods to provide different rates to different products.', 'woothemes'));

}


/**
 * Fix for per_page option
 * Trac: http://core.trac.wordpress.org/ticket/19465
 */
add_filter('edit_posts_per_page', 'woocommerce_fix_edit_posts_per_page', 1, 2);

function woocommerce_fix_edit_posts_per_page( $per_page, $post_type ) {
	
	if ($post_type!=='product') return $per_page;
	
	$screen = get_current_screen();
	
	if (strstr($screen->id, '-')) {
	
		$option = 'edit_' . str_replace('edit-', '', $screen->id) . '_per_page';
		
		if (isset($_POST['wp_screen_options']['option']) && $_POST['wp_screen_options']['option'] == $option ) :
			
			update_user_meta( get_current_user_id(), $option, $_POST['wp_screen_options']['value'] );
			
			wp_redirect( remove_query_arg( array('pagenum', 'apage', 'paged'), wp_get_referer() ) );
			exit;

		endif;
		
		$user_per_page = (int) get_user_meta( get_current_user_id(), $option, true );
		
		if ($user_per_page) $per_page = $user_per_page;
		
	}
	
	return $per_page;
	
}

