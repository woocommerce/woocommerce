<?php
/**
 * Admin taxonomy functions
 *
 * These functions control admin interface bits like category ordering.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Taxonomies
 * @version     1.6.4
 */

/**
 * Category thumbnail fields.
 *
 * @access public
 * @return void
 */
function woocommerce_add_category_thumbnail_field() {
	global $woocommerce;
	?>
	<div class="form-field">
		<label><?php _e('Thumbnail', 'woocommerce'); ?></label>
		<div id="product_cat_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo woocommerce_placeholder_img_src(); ?>" width="60px" height="60px" /></div>
		<div style="line-height:60px;">
			<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" />
			<button type="submit" class="upload_image_button button"><?php _e('Upload/Add image', 'woocommerce'); ?></button>
			<button type="submit" class="remove_image_button button"><?php _e('Remove image', 'woocommerce'); ?></button>
		</div>
		<script type="text/javascript">
			
			 // Only show the "remove image" button when needed
			 if ( ! jQuery('#product_cat_thumbnail_id').val() )
				 jQuery('.remove_image_button').hide();

			window.send_to_editor_default = window.send_to_editor;

			window.send_to_termmeta = function(html) {

				jQuery('body').append('<div id="temp_image">' + html + '</div>');

				var img = jQuery('#temp_image').find('img');

				imgurl 		= img.attr('src');
				imgclass 	= img.attr('class');
				imgid		= parseInt(imgclass.replace(/\D/g, ''), 10);

				jQuery('#product_cat_thumbnail_id').val(imgid);
				jQuery('#product_cat_thumbnail img').attr('src', imgurl);
				jQuery('.remove_image_button').show();
				jQuery('#temp_image').remove();

				tb_remove();

				window.send_to_editor = window.send_to_editor_default;
			}

			jQuery('.upload_image_button').live('click', function(){
				var post_id = 0;

				window.send_to_editor = window.send_to_termmeta;

				tb_show('', 'media-upload.php?post_id=' + post_id + '&amp;type=image&amp;TB_iframe=true');
				return false;
			});

			jQuery('.remove_image_button').live('click', function(){
				jQuery('#product_cat_thumbnail img').attr('src', '<?php echo woocommerce_placeholder_img_src(); ?>');
				jQuery('#product_cat_thumbnail_id').val('');
				jQuery('.remove_image_button').hide();
				return false;
			});

		</script>
		<div class="clear"></div>
	</div>
	<?php
}

add_action( 'product_cat_add_form_fields', 'woocommerce_add_category_thumbnail_field' );
add_action( 'product_cat_edit_form_fields', 'woocommerce_edit_category_thumbnail_field', 10,2 );


/**
 * Edit category thumbnail field.
 *
 * @access public
 * @param mixed $term Term (category) being edited
 * @param mixed $taxonomy Taxonomy of the term being edited
 * @return void
 */
function woocommerce_edit_category_thumbnail_field( $term, $taxonomy ) {
	global $woocommerce;

	$image 			= '';
	$thumbnail_id 	= get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true );
	if ($thumbnail_id) :
		$image = wp_get_attachment_url( $thumbnail_id );
	else :
		$image = woocommerce_placeholder_img_src();
	endif;
	?>
	<tr class="form-field">
		<th scope="row" valign="top"><label><?php _e('Thumbnail', 'woocommerce'); ?></label></th>
		<td>
			<div id="product_cat_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo $image; ?>" width="60px" height="60px" /></div>
			<div style="line-height:60px;">
				<input type="hidden" id="product_cat_thumbnail_id" name="product_cat_thumbnail_id" value="<?php echo $thumbnail_id; ?>" />
				<button type="submit" class="upload_image_button button"><?php _e('Upload/Add image', 'woocommerce'); ?></button>
				<button type="submit" class="remove_image_button button"><?php _e('Remove image', 'woocommerce'); ?></button>
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
					jQuery('#product_cat_thumbnail img').attr('src', '<?php echo woocommerce_placeholder_img_src(); ?>');
					jQuery('#product_cat_thumbnail_id').val('');
					return false;
				});

			</script>
			<div class="clear"></div>
		</td>
	</tr>
	<?php
}

/**
 * woocommerce_category_thumbnail_field_save function.
 *
 * @access public
 * @param mixed $term_id Term ID being saved
 * @param mixed $tt_id
 * @param mixed $taxonomy Taxonomy of the term being saved
 * @return void
 */
function woocommerce_category_thumbnail_field_save( $term_id, $tt_id, $taxonomy ) {
	if ( isset( $_POST['product_cat_thumbnail_id'] ) )
		update_woocommerce_term_meta( $term_id, 'thumbnail_id', $_POST['product_cat_thumbnail_id'] );
}

add_action( 'created_term', 'woocommerce_category_thumbnail_field_save', 10,3 );
add_action( 'edit_term', 'woocommerce_category_thumbnail_field_save', 10,3 );


/**
 * Description for product_cat page to aid users.
 *
 * @access public
 * @return void
 */
function woocommerce_product_cat_description() {

	echo wpautop( __( 'Product categories for your store can be managed here. To change the order of categories on the front-end you can drag and drop to sort them. To see more categories listed click the "screen options" link at the top of the page.', 'woocommerce' ) );

}

add_action( 'product_cat_pre_add_form', 'woocommerce_product_cat_description' );


/**
 * Description for shipping class page to aid users.
 *
 * @access public
 * @return void
 */
function woocommerce_shipping_class_description() {

	echo wpautop(__('Shipping classes can be used to group products of similar type. These groups can then be used by certain shipping methods to provide different rates to different products.', 'woocommerce'));

}

add_action( 'product_shipping_class_pre_add_form', 'woocommerce_shipping_class_description' );


/**
 * Fix for the per_page option
 *
 * Trac: http://core.trac.wordpress.org/ticket/19465
 *
 * @access public
 * @param mixed $per_page
 * @param mixed $post_type
 * @return void
 */
function woocommerce_fix_edit_posts_per_page( $per_page, $post_type ) {

	if ( $post_type !== 'product' )
		return $per_page;

	$screen = get_current_screen();

	if ( strstr( $screen->id, '-' ) ) {

		$option = 'edit_' . str_replace( 'edit-', '', $screen->id ) . '_per_page';

		if ( isset( $_POST['wp_screen_options']['option'] ) && $_POST['wp_screen_options']['option'] == $option ) {

			update_user_meta( get_current_user_id(), $option, $_POST['wp_screen_options']['value'] );

			wp_redirect( remove_query_arg( array('pagenum', 'apage', 'paged'), wp_get_referer() ) );
			exit;

		}

		$user_per_page = (int) get_user_meta( get_current_user_id(), $option, true );

		if ( $user_per_page )
			$per_page = $user_per_page;

	}

	return $per_page;
}

add_filter( 'edit_posts_per_page', 'woocommerce_fix_edit_posts_per_page', 1, 2 );


/**
 * Thumbnail column added to category admin.
 *
 * @access public
 * @param mixed $columns
 * @return void
 */
function woocommerce_product_cat_columns( $columns ) {
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['thumb'] = __('Image', 'woocommerce');

	unset( $columns['cb'] );

	return array_merge( $new_columns, $columns );
}

add_filter( 'manage_edit-product_cat_columns', 'woocommerce_product_cat_columns' );


/**
 * Thumbnail column value added to category admin.
 *
 * @access public
 * @param mixed $columns
 * @param mixed $column
 * @param mixed $id
 * @return void
 */
function woocommerce_product_cat_column( $columns, $column, $id ) {
	global $woocommerce;

	if ( $column == 'thumb' ) {

		$image 			= '';
		$thumbnail_id 	= get_woocommerce_term_meta( $id, 'thumbnail_id', true );

		if ($thumbnail_id)
			$image = wp_get_attachment_url( $thumbnail_id );
		else
			$image = woocommerce_placeholder_img_src();

		$columns .= '<img src="' . $image . '" alt="Thumbnail" class="wp-post-image" height="48" width="48" />';

	}

	return $columns;
}

add_filter( 'manage_product_cat_custom_column', 'woocommerce_product_cat_column', 10, 3 );


/**
 * Add a configure button column for the shipping classes page.
 *
 * @access public
 * @param mixed $columns
 * @return void
 */
function woocommerce_shipping_class_columns( $columns ) {
	$columns['configure'] = '&nbsp;';
	return $columns;
}

add_filter( 'manage_edit-product_shipping_class_columns', 'woocommerce_shipping_class_columns' );


/**
 * Add a configure button for the shipping classes page.
 *
 * @access public
 * @param mixed $columns
 * @param mixed $column
 * @param mixed $id
 * @return void
 */
function woocommerce_shipping_class_column( $columns, $column, $id ) {
	if ( $column == 'configure' )
		$columns .= '<a href="'. admin_url( 'edit-tags.php?action=edit&taxonomy=product_shipping_class&tag_ID='. $id .'&post_type=product' ) .'" class="button alignright">'.__('Configure shipping class', 'woocommerce').'</a>';

	return $columns;
}

add_filter( 'manage_product_shipping_class_custom_column', 'woocommerce_shipping_class_column', 10, 3 );