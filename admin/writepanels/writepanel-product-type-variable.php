<?php
/**
 * Variable Product Type
 * 
 * Functions specific to variable products (for the write panels)
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */
 
/**
 * Product Options
 * 
 * Product Options for the variable product type
 */
function variable_product_type_options() {
	global $post;
	
	$attributes = maybe_unserialize( get_post_meta($post->ID, 'product_attributes', true) );
	if (!isset($attributes)) $attributes = array();
	?>
	<div id="variable_product_options" class="panel">
		
		<div class="woocommerce_configurations">
			<?php
			$args = array(
				'post_type'	=> 'product_variation',
				'post_status' => array('private', 'publish'),
				'numberposts' => -1,
				'orderby' => 'id',
				'order' => 'asc',
				'post_parent' => $post->ID
			);
			$variations = get_posts($args);
			$loop = 0;
			if ($variations) foreach ($variations as $variation) : 
			
				$variation_data = get_post_custom( $variation->ID );
				$image = '';
				if (isset($variation_data['_thumbnail_id'][0])) :
					$image = wp_get_attachment_url( $variation_data['_thumbnail_id'][0] );
				endif;
				
				if (!$image) $image = woocommerce::plugin_url().'/assets/images/placeholder.png';
				?>
				<div class="woocommerce_configuration">
					<p>
						<button type="button" class="remove_variation button" rel="<?php echo $variation->ID; ?>"><?php _e('Remove', 'woothemes'); ?></button>
						<strong>#<?php echo $variation->ID; ?> &mdash; <?php _e('Variation:', 'woothemes'); ?></strong>
						<?php
							foreach ($attributes as $attribute) :
								
								if ( $attribute['variation']!=='yes' ) continue;
								
								$options = $attribute['value'];
								$value = get_post_meta( $variation->ID, 'tax_' . sanitize_title($attribute['name']), true );
								
								if (!is_array($options)) $options = explode(',', $options);
								
								echo '<select name="tax_' . sanitize_title($attribute['name']).'['.$loop.']"><option value="">'.__('Any ', 'woothemes').$attribute['name'].'&hellip;</option>';
								
								foreach($options as $option) :
									$option = trim($option);
									echo '<option ';
									selected($value, $option);
									echo ' value="'.$option.'">'.ucfirst($option).'</option>';
								endforeach;	
									
								echo '</select>';
	
							endforeach;
						?>
						<input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo $variation->ID; ?>" />
					</p>
					<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes">
						<tbody>	
							<tr>
								<td class="upload_image"><img src="<?php echo $image ?>" width="60px" height="60px" /><input type="hidden" name="upload_image_id[<?php echo $loop; ?>]" class="upload_image_id" value="<?php if (isset($variation_data['_thumbnail_id'][0])) echo $variation_data['_thumbnail_id'][0]; ?>" /><input type="button" rel="<?php echo $variation->ID; ?>" class="upload_image_button button" value="<?php _e('Product Image', 'woothemes'); ?>" /></td>
								<td><label><?php _e('SKU:', 'woothemes'); ?></label><input type="text" size="5" name="variable_sku[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['SKU'][0])) echo $variation_data['SKU'][0]; ?>" /></td>
								<td><label><?php _e('Weight', 'woothemes').' ('.get_option('woocommerce_weight_unit').'):'; ?></label><input type="text" size="5" name="variable_weight[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['weight'][0])) echo $variation_data['weight'][0]; ?>" /></td>
								<td><label><?php _e('Stock Qty:', 'woothemes'); ?></label><input type="text" size="5" name="variable_stock[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['stock'][0])) echo $variation_data['stock'][0]; ?>" /></td>
								<td><label><?php _e('Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_price[<?php echo $loop; ?>]" placeholder="<?php _e('e.g. 29.99', 'woothemes'); ?>" value="<?php if (isset($variation_data['price'][0])) echo $variation_data['price'][0]; ?>" /></td>
								<td><label><?php _e('Sale Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_sale_price[<?php echo $loop; ?>]" placeholder="<?php _e('e.g. 29.99', 'woothemes'); ?>" value="<?php if (isset($variation_data['sale_price'][0])) echo $variation_data['sale_price'][0]; ?>" /></td>
								<td><label><?php _e('Enabled', 'woothemes'); ?></label><input type="checkbox" class="checkbox" name="variable_enabled[<?php echo $loop; ?>]" <?php checked($variation->post_status, 'publish'); ?> /></td>
							</tr>		
						</tbody>
					</table>
				</div>
			<?php $loop++; endforeach; ?>
		</div>
		<p class="description"><?php _e('Add (optional) pricing/inventory for product variations. You must save your product attributes in the "Product Data" panel to make them available for selection.', 'woothemes'); ?></p>

		<button type="button" class="button button-primary add_configuration"><?php _e('Add Configuration', 'woothemes'); ?></button>
		
		<div class="clear"></div>
	</div>
	<?php
}
add_action('woocommerce_product_type_options_box', 'variable_product_type_options');

 
/**
 * Product Type Javascript
 * 
 * Javascript for the variable product type
 */
function variable_product_write_panel_js() {
	global $post;
	
	$attributes = maybe_unserialize( get_post_meta($post->ID, 'product_attributes', true) );
	if (!isset($attributes)) $attributes = array();
	?>
	jQuery(function(){
		
		jQuery('button.add_configuration').live('click', function(){
		
			jQuery('.woocommerce_configurations').block({ message: null, overlayCSS: { background: '#fff url(<?php echo woocommerce::plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
					
			var data = {
				action: 'woocommerce_add_variation',
				post_id: <?php echo $post->ID; ?>,
				security: '<?php echo wp_create_nonce("add-variation"); ?>'
			};

			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
				
				var variation_id = parseInt(response);
				
				var loop = jQuery('.woocommerce_configuration').size();
				
				jQuery('.woocommerce_configurations').append('<div class="woocommerce_configuration">\
					<p>\
						<button type="button" class="remove_variation button"><?php _e('Remove', 'woothemes'); ?></button>\
						<strong><?php _e('Variation:', 'woothemes'); ?></strong>\
						<?php
							if ($attributes) foreach ($attributes as $attribute) :
								
								if ( $attribute['variation']!=='yes' ) continue;
								
								$options = $attribute['value'];
								if (!is_array($options)) $options = explode(',', $options);
								
								echo '<select name="tax_' . sanitize_title($attribute['name']).'[\' + loop + \']"><option value="">'.__('Any ', 'woothemes').$attribute['name'].'&hellip;</option>';
								
								foreach($options as $option) :
									echo '<option value="'.trim($option).'">'.ucfirst(trim($option)).'</option>';
								endforeach;	
									
								echo '</select>';
	
							endforeach;
					?><input type="hidden" name="variable_post_id[' + loop + ']" value="' + variation_id + '" /></p>\
					<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes">\
						<tbody>\
							<tr>\
								<td class="upload_image"><img src="<?php echo woocommerce::plugin_url().'/assets/images/placeholder.png' ?>" width="60px" height="60px" /><input type="hidden" name="upload_image_id[' + loop + ']" class="upload_image_id" /><input type="button" class="upload_image_button button" rel="" value="<?php _e('Product Image', 'woothemes'); ?>" /></td>\
								<td><label><?php _e('SKU:', 'woothemes'); ?></label><input type="text" size="5" name="variable_sku[' + loop + ']" /></td>\
								<td><label><?php _e('Weight', 'woothemes').' ('.get_option('woocommerce_weight_unit').'):'; ?></label><input type="text" size="5" name="variable_weight[' + loop + ']" /></td>\
								<td><label><?php _e('Stock Qty:', 'woothemes'); ?></label><input type="text" size="5" name="variable_stock[' + loop + ']" /></td>\
								<td><label><?php _e('Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_price[' + loop + ']" placeholder="<?php _e('e.g. 29.99', 'woothemes'); ?>" /></td>\
								<td><label><?php _e('Sale Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_sale_price[' + loop + ']" placeholder="<?php _e('e.g. 29.99', 'woothemes'); ?>" /></td>\
								<td><label><?php _e('Enabled', 'woothemes'); ?></label><input type="checkbox" class="checkbox" name="variable_enabled[' + loop + ']" checked="checked" /></td>\
							</tr>\
						</tbody>\
					</table>\
				</div>');
				
				jQuery('.woocommerce_configurations').unblock();

			});

			return false;
		
		});
		
		jQuery('button.remove_variation').live('click', function(){
			var answer = confirm('<?php _e('Are you sure you want to remove this variation?', 'woothemes'); ?>');
			if (answer){
				
				var el = jQuery(this).parent().parent();
				
				var variation = jQuery(this).attr('rel');
				
				if (variation>0) {
				
					jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(<?php echo woocommerce::plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
					
					var data = {
						action: 'woocommerce_remove_variation',
						variation_id: variation,
						security: '<?php echo wp_create_nonce("delete-variation"); ?>'
					};
	
					jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
						// Success
						jQuery(el).fadeOut('300', function(){
							jQuery(el).remove();
						});
					});
					
				} else {
					jQuery(el).fadeOut('300', function(){
						jQuery(el).remove();
					});
				}
				
			}
			return false;
		});
		
		var current_field_wrapper;
		
		window.send_to_editor_default = window.send_to_editor;

		jQuery('.upload_image_button').live('click', function(){
			
			var post_id = jQuery(this).attr('rel');
			
			var parent = jQuery(this).parent();
			
			current_field_wrapper = parent;
			
			window.send_to_editor = window.send_to_cproduct;
			
			formfield = jQuery('.upload_image_id', parent).attr('name');
			tb_show('', 'media-upload.php?post_id=' + post_id + '&amp;type=image&amp;TB_iframe=true');
			return false;
		});

		window.send_to_cproduct = function(html) {
			
			imgurl = jQuery('img', html).attr('src');
			imgclass = jQuery('img', html).attr('class');
			imgid = parseInt(imgclass.replace(/\D/g, ''), 10);
			
			jQuery('.upload_image_id', current_field_wrapper).val(imgid);

			jQuery('img', current_field_wrapper).attr('src', imgurl);
			tb_remove();
			window.send_to_editor = window.send_to_editor_default;
			
		}

	});
	<?php
	
}
add_action('product_write_panel_js', 'variable_product_write_panel_js');


/**
 * Delete variation via ajax function
 */
add_action('wp_ajax_woocommerce_remove_variation', 'woocommerce_remove_variation');

function woocommerce_remove_variation() {
	
	check_ajax_referer( 'delete-variation', 'security' );
	$variation_id = intval( $_POST['variation_id'] );
	wp_delete_post( $variation_id );
	die();
	
}


/**
 * Add variation via ajax function
 */
add_action('wp_ajax_woocommerce_add_variation', 'woocommerce_add_variation');

function woocommerce_add_variation() {
	
	check_ajax_referer( 'add-variation', 'security' );
	
	$post_id = intval( $_POST['post_id'] );

	$variation = array(
		'post_title' => 'Product #' . $post_id . ' Variation',
		'post_content' => '',
		'post_status' => 'publish',
		'post_author' => get_current_user_id(),
		'post_parent' => $post_id,
		'post_type' => 'product_variation'
	);
	$variation_id = wp_insert_post( $variation );
	
	echo $variation_id;
	
	die();
	
}



/**
 * Product Type selector
 * 
 * Adds this product type to the product type selector in the product options meta box
 */
function variable_product_type_selector( $product_type ) {
	
	echo '<option value="variable" '; if ($product_type=='variable') echo 'selected="selected"'; echo '>'.__('Variable', 'woothemes').'</option>';

}
add_action('product_type_selector', 'variable_product_type_selector');

/**
 * Process meta
 * 
 * Processes this product types options when a post is saved
 */
function process_product_meta_variable( $data, $post_id ) {
	
	if (isset($_POST['variable_sku'])) :
		
		$variable_post_id 	= $_POST['variable_post_id'];
		$variable_sku 		= $_POST['variable_sku'];
		$variable_weight	= $_POST['variable_weight'];
		$variable_stock 	= $_POST['variable_stock'];
		$variable_price 	= $_POST['variable_price'];
		$variable_sale_price= $_POST['variable_sale_price'];
		$upload_image_id		= $_POST['upload_image_id'];
		if (isset($_POST['variable_enabled'])) $variable_enabled = $_POST['variable_enabled'];
		
		$attributes = maybe_unserialize( get_post_meta($post_id, 'product_attributes', true) );
		if (!isset($attributes)) $attributes = array();
		
		for ($i=0; $i<sizeof($variable_sku); $i++) :
			
			$variation_id = (int) $variable_post_id[$i];

			// Enabled or disabled
			if (isset($variable_enabled[$i])) $post_status = 'publish'; else $post_status = 'private';
			
			// Generate a useful post title
			$title = array();
			
			foreach ($attributes as $attribute) :
				if ( $attribute['variation']=='yes' ) :
					$value = trim($_POST[ 'tax_' . sanitize_title($attribute['name']) ][$i]);
					if ($value) $title[] = ucfirst($attribute['name']).': '.$value;
				endif;
			endforeach;
			
			$sku_string = '#'.$variation_id;
			if ($variable_sku[$i]) $sku_string .= ' SKU: ' . $variable_sku[$i];
			
			// Update or Add post
			if (!$variation_id) :
				
				$variation = array(
					'post_title' => '#' . $post_id . ' Variation ('.$sku_string.') - ' . implode(', ', $title),
					'post_content' => '',
					'post_status' => $post_status,
					'post_author' => get_current_user_id(),
					'post_parent' => $post_id,
					'post_type' => 'product_variation'
				);
				$variation_id = wp_insert_post( $variation );

			else :
				
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status, 'post_title' => '#' . $post_id . ' Variation ('.$sku_string.') - ' . implode(', ', $title) ), array( 'ID' => $variation_id ) );
			
			endif;

			// Update post meta
			update_post_meta( $variation_id, 'SKU', $variable_sku[$i] );
			update_post_meta( $variation_id, 'price', $variable_price[$i] );
			update_post_meta( $variation_id, 'sale_price', $variable_sale_price[$i] );
			update_post_meta( $variation_id, 'weight', $variable_weight[$i] );
			update_post_meta( $variation_id, 'stock', $variable_stock[$i] );
			update_post_meta( $variation_id, '_thumbnail_id', $upload_image_id[$i] );
			
			// Update taxonomies
			foreach ($attributes as $attribute) :
							
				if ( $attribute['variation']=='yes' ) :
				
					$value = trim($_POST[ 'tax_' . sanitize_title($attribute['name']) ][$i]);
					
					update_post_meta( $variation_id, 'tax_' . sanitize_title($attribute['name']), $value );
				
				endif;

			endforeach;
		 	
		 endfor; 
	endif;

}
add_action('process_product_meta_variable', 'process_product_meta_variable', 1, 2);