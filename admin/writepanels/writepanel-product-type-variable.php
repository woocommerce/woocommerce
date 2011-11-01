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
	global $post, $woocommerce;
	
	$attributes = (array) maybe_unserialize( get_post_meta($post->ID, 'product_attributes', true) );
	
	// See if any are set
	$variation_attribute_found = false;
	if ($attributes) foreach($attributes as $attribute){
		if (isset($attribute['is_variation'])) :
			$variation_attribute_found = true;
			break;
		endif;
	}
	?>
	<div id="variable_product_options" class="panel">
		<p class="bulk_edit"><strong><?php _e('Bulk edit:', 'woothemes'); ?></strong> <a class="button set_all_prices" href="#"><?php _e('Set all prices', 'woothemes'); ?></a> <a class="button set_all_sale_prices" href="#"><?php _e('Set all sale prices', 'woothemes'); ?></a> <a class="button set_all_stock" href="#"><?php _e('Set all stock', 'woothemes'); ?></a></p>

		<div class="woocommerce_variations">
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
				
				if (!$image) $image = $woocommerce->plugin_url().'/assets/images/placeholder.png';
				?>
				<div class="woocommerce_variation">
					<p>
						<button type="button" class="remove_variation button" rel="<?php echo $variation->ID; ?>"><?php _e('Remove', 'woothemes'); ?></button>
						<strong>#<?php echo $variation->ID; ?> &mdash; <?php _e('Variation:', 'woothemes'); ?></strong>
						<?php
							foreach ($attributes as $attribute) :
								
								// Only deal with attributes that are variations
								if ( !$attribute['is_variation'] ) continue;

								// Get current value for variation (if set)
								$variation_selected_value = get_post_meta( $variation->ID, 'attribute_' . sanitize_title($attribute['name']), true );
								
								// Name will be something like attribute_pa_color
								echo '<select name="attribute_' . sanitize_title($attribute['name']).'['.$loop.']"><option value="">'.__('Any', 'woothemes') . ' ' . $woocommerce->attribute_label($attribute['name']).'&hellip;</option>';
								
								// Get terms for attribute taxonomy or value if its a custom attribute
								if ($attribute['is_taxonomy']) :
									$post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );
									foreach ($post_terms as $term) :
										echo '<option '.selected($variation_selected_value, $term->slug, false).' value="'.$term->slug.'">'.$term->name.'</option>';
									endforeach;
								else :
									$options = explode('|', $attribute['value']);
									foreach ($options as $option) :
										echo '<option '.selected($variation_selected_value, $option, false).' value="'.$option.'">'.ucfirst($option).'</option>';
									endforeach;
								endif;
									
								echo '</select>';
	
							endforeach;
						?>
						<input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $variation->ID ); ?>" />
					</p>
					<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes">
						<tbody>	
							<tr>
								<td class="upload_image"><img src="<?php echo $image ?>" width="60px" height="60px" /><input type="hidden" name="upload_image_id[<?php echo $loop; ?>]" class="upload_image_id" value="<?php if (isset($variation_data['_thumbnail_id'][0])) echo $variation_data['_thumbnail_id'][0]; ?>" /><input type="button" rel="<?php echo $variation->ID; ?>" class="upload_image_button button" value="<?php _e('Product Image', 'woothemes'); ?>" /></td>
								
								<td><label><?php _e('SKU:', 'woothemes'); ?> <a class="tips" tip="<?php _e('Enter a SKU for this variation or leave blank to use the parent product\'s SKU.', 'woothemes'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_sku[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['sku'][0])) echo $variation_data['sku'][0]; ?>" placeholder="<?php if ($sku = get_post_meta($post->ID, 'sku', true)) echo $sku; else echo $post->ID; ?>" /></td>
								
								<td><label><?php _e('Weight', 'woothemes').' ('.get_option('woocommerce_weight_unit').'):'; ?> <a class="tips" tip="<?php _e('Enter a weight for this variation or leave blank to use the parent product\'s weight.', 'woothemes'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_weight[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['weight'][0])) echo $variation_data['weight'][0]; ?>" placeholder="<?php if ($value = get_post_meta($post->ID, 'weight', true)) echo $value; else echo '0.00'; ?>" /></td>
								
								<td><label><?php _e('Stock Qty:', 'woothemes'); ?> <a class="tips" tip="<?php _e('Enter a quantity to manage stock for this variation, or leave blank to use the variable product\'s stock options.', 'woothemes'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_stock[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['stock'][0])) echo $variation_data['stock'][0]; ?>" /></td>
								
								<td><label><?php _e('Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_price[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['price'][0])) echo $variation_data['price'][0]; ?>" /></td>
								
								<td><label><?php _e('Sale Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_sale_price[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['sale_price'][0])) echo $variation_data['sale_price'][0]; ?>" /></td>
								
								<td><label><?php _e('Enabled', 'woothemes'); ?></label><input type="checkbox" class="checkbox" name="variable_enabled[<?php echo $loop; ?>]" <?php checked($variation->post_status, 'publish'); ?> /></td>
							</tr>		
						</tbody>
					</table>
				</div>
			<?php $loop++; endforeach; ?>
		</div>
		
		<?php
			if ($variation_attribute_found) :
				?>
				<p class="default_variation">
					<strong><?php _e('Default variation selections:', 'woothemes'); ?></strong>
					<?php
						$default_attributes = (array) maybe_unserialize(get_post_meta( $post->ID, '_default_attributes', true ));
						foreach ($attributes as $attribute) :
							
							// Only deal with attributes that are variations
							if ( !$attribute['is_variation'] ) continue;

							// Get current value for variation (if set)
							$variation_selected_value = (isset($default_attributes[sanitize_title($attribute['name'])])) ? $default_attributes[sanitize_title($attribute['name'])] : '';
							
							// Name will be something like attribute_pa_color
							echo '<select name="default_attribute_' . sanitize_title($attribute['name']).'"><option value="">'.__('No default', 'woothemes') . ' ' . $woocommerce->attribute_label($attribute['name']).'&hellip;</option>';
							
							// Get terms for attribute taxonomy or value if its a custom attribute
							if ($attribute['is_taxonomy']) :
								$post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );
								foreach ($post_terms as $term) :
									echo '<option '.selected($variation_selected_value, $term->slug, false).' value="'.$term->slug.'">'.$term->name.'</option>';
								endforeach;
							else :
								$options = explode('|', $attribute['value']);
								foreach ($options as $option) :
									echo '<option '.selected($variation_selected_value, $option, false).' value="'.$option.'">'.ucfirst($option).'</option>';
								endforeach;
							endif;
								
							echo '</select>';

						endforeach;
					?>
				</p>
				<?php
			endif;
		?>

		<button type="button" class="button button-primary add_variation" <?php disabled($variation_attribute_found, false); ?>><?php _e('Add Variation', 'woothemes'); ?></button>
		<button type="button" class="button link_all_variations" <?php disabled($variation_attribute_found, false); ?>><?php _e('Link all variations', 'woothemes'); ?></button>
		
		<p class="description"><?php _e('Add (optional) information for product variations. You must save your product attributes in the "Product Data" panel to make them selectable.', 'woothemes'); ?></p>
		
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
	global $post, $woocommerce;
	
	$attributes = (array) maybe_unserialize( get_post_meta($post->ID, 'product_attributes', true) );
	?>
	jQuery(function(){
	
		<?php if (!$attributes || (is_array($attributes) && sizeof($attributes)==0)) : ?>
			
			jQuery('button.link_all_variations, button.add_variation').live('click', function(){
				
				alert('<?php _e('You must add some attributes via the "Product Data" panel and save before adding a new variation.', 'woothemes'); ?>');
				
				return false;
				
			});
			
		<?php else : ?>
		
		jQuery('button.add_variation').live('click', function(){
		
			jQuery('.woocommerce_variations').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
					
			var data = {
				action: 'woocommerce_add_variation',
				post_id: <?php echo $post->ID; ?>,
				security: '<?php echo wp_create_nonce("add-variation"); ?>'
			};

			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
				
				var variation_id = parseInt(response);
				
				var loop = jQuery('.woocommerce_variation').size();
				
				jQuery('.woocommerce_variations').append('<div class="woocommerce_variation">\
					<p>\
						<button type="button" class="remove_variation button" rel="' + variation_id + '"><?php _e('Remove', 'woothemes'); ?></button>\
						<strong>#' + variation_id + ' &mdash; <?php _e('Variation:', 'woothemes'); ?></strong>\
						<?php
							if ($attributes) foreach ($attributes as $attribute) :
								
								if ( !isset($attribute['is_variation']) || !$attribute['is_variation'] ) continue;
								
								echo '<select name="attribute_' . sanitize_title($attribute['name']).'[\' + loop + \']"><option value="">'.__('Any', 'woothemes') . ' ' .$woocommerce->attribute_label($attribute['name']).'&hellip;</option>';
								
								// Get terms for attribute taxonomy or value if its a custom attribute
								if ($attribute['is_taxonomy']) :
									$post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );
									foreach ($post_terms as $term) :
										echo '<option value="'.$term->slug.'">'.$term->name.'</option>';
									endforeach;
								else :
									$options = explode('|', $attribute['value']);
									foreach ($options as $option) :
										echo '<option value="'.$option.'">'.ucfirst($option).'</option>';
									endforeach;
								endif;
									
								echo '</select>';
	
							endforeach;
					?><input type="hidden" name="variable_post_id[' + loop + ']" value="' + variation_id + '" /></p>\
					<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes">\
						<tbody>\
							<tr>\
								<td class="upload_image"><img src="<?php echo $woocommerce->plugin_url().'/assets/images/placeholder.png' ?>" width="60px" height="60px" /><input type="hidden" name="upload_image_id[' + loop + ']" class="upload_image_id" /><input type="button" class="upload_image_button button" rel="" value="<?php _e('Product Image', 'woothemes'); ?>" /></td>\
								\
								<td><label><?php _e('SKU:', 'woothemes'); ?> <a class="tips" tip="<?php _e('Enter a SKU for this variation or leave blank to use the parent product\\\'s SKU.', 'woothemes'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_sku[' + loop + ']" placeholder="<?php if ($sku = get_post_meta($post->ID, 'sku', true)) echo $sku; else echo $post->ID; ?>" /></td>\
								\
								<td><label><?php _e('Weight', 'woothemes').' ('.get_option('woocommerce_weight_unit').'):'; ?> <a class="tips" tip="<?php _e('Enter a weight for this variation or leave blank to use the parent product\\\'s weight.', 'woothemes'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_weight[' + loop + ']" placeholder="<?php if ($value = get_post_meta($post->ID, 'weight', true)) echo $value; else echo '0.00'; ?>" /></td>\
								\
								<td><label><?php _e('Stock Qty:', 'woothemes'); ?> <a class="tips" tip="<?php _e('Enter a quantity to manage stock for this variation, or leave blank to use the parent product\\\'s stock options.', 'woothemes'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_stock[' + loop + ']" /></td>\
								\
								<td><label><?php _e('Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_price[' + loop + ']" /></td>\
								\
								<td><label><?php _e('Sale Price:', 'woothemes'); ?></label><input type="text" size="5" name="variable_sale_price[' + loop + ']" /></td>\
								<td><label><?php _e('Enabled', 'woothemes'); ?></label><input type="checkbox" class="checkbox" name="variable_enabled[' + loop + ']" checked="checked" /></td>\
							</tr>\
						</tbody>\
					</table>\
				</div>');
				
				jQuery(".tips").easyTooltip();
				jQuery('.woocommerce_variations').unblock();

			});

			return false;
		
		});

		jQuery('button.link_all_variations').live('click', function(){
			
			var answer = confirm('<?php _e('Are you sure you want to link all variations? This will create a new variation for each and every possible combination of variation attributes.', 'woothemes'); ?>');
			
			if (answer) {
				
				jQuery('.woocommerce_variations').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
				
				var data = {
					action: 'woocommerce_link_all_variations',
					post_id: <?php echo $post->ID; ?>,
					security: '<?php echo wp_create_nonce("link-variations"); ?>'
				};
	
				jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
					
					jQuery('.woocommerce_variations').unblock();
					
					if (response==1) {				
						jQuery('.woocommerce_variations').load( window.location + ' .woocommerce_variations > *' );
					}
	
				});
			}
			return false;
		});
		
		jQuery('button.remove_variation').live('click', function(){
			var answer = confirm('<?php _e('Are you sure you want to remove this variation?', 'woothemes'); ?>');
			if (answer){
				
				var el = jQuery(this).parent().parent();
				
				var variation = jQuery(this).attr('rel');
				
				if (variation>0) {
				
					jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
					
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
		
		jQuery('a.set_all_prices').click(function(){
			var value = prompt("<?php _e('Enter a price', 'woothemes'); ?>");
			jQuery('input[name^="variable_price"]').val( value );
			return false;
		});
		
		jQuery('a.set_all_sale_prices').click(function(){
			var value = prompt("<?php _e('Enter a price', 'woothemes'); ?>");
			jQuery('input[name^="variable_sale_price"]').val( value );
			return false;
		});
		
		jQuery('a.set_all_stock').click(function(){
			var value = prompt("<?php _e('Enter stock quantity', 'woothemes'); ?>");
			jQuery('input[name^="variable_stock"]').val( value );
			return false;
		});
		
		<?php endif; ?>
		
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
			
			var img = jQuery(html).find('img');
			
			imgurl = jQuery(img).attr('src');
			imgclass = jQuery(img).attr('class');

			imgid = parseInt(imgclass.replace(/\D/g, ''), 10);
			
			jQuery('.upload_image_id', current_field_wrapper).val(imgid);

			jQuery('img', current_field_wrapper).attr('src', imgurl);
			tb_remove();
			window.send_to_editor = window.send_to_editor_default;
			
		}

	});
	<?php
	
}
add_action('woocommerce_product_write_panel_js', 'variable_product_write_panel_js');


/**
 * Delete variation via ajax function
 */
add_action('wp_ajax_woocommerce_remove_variation', 'woocommerce_remove_variation');

function woocommerce_remove_variation() {
	
	check_ajax_referer( 'delete-variation', 'security' );
	$variation_id = intval( $_POST['variation_id'] );
	$variation = get_post($variation_id);
	if ($variation && $variation->post_type=="product_variation") wp_delete_post( $variation_id );
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
 * Link all variations via ajax function
 */
add_action('wp_ajax_woocommerce_link_all_variations', 'woocommerce_link_all_variations');

function woocommerce_link_all_variations() {
	
	check_ajax_referer( 'link-variations', 'security' );
	
	$post_id = intval( $_POST['post_id'] );
	
	if (!$post_id) die();
	
	$variations = array();
	
	$_product = &new woocommerce_product( $post_id );
		
	// Put variation attributes into an array
	foreach ($_product->attributes as $attribute) :
								
		if ( !$attribute['is_variation'] ) continue;
		
		$attribute_field_name = 'attribute_' . sanitize_title($attribute['name']);
		
		if ($attribute['is_taxonomy']) :
			$post_terms = wp_get_post_terms( $post_id, $attribute['name'] );
			$options = array();
			foreach ($post_terms as $term) :
				$options[] = $term->slug;
			endforeach;
		else :
			$options = explode('|', $attribute['value']);
		endif;
		
		$options = array_map('trim', $options);
		
		$variations[$attribute_field_name] = $options;
		
	endforeach;
	
	// Quit out if none were found
	if (sizeof($variations)==0) die();
	
	// Get existing variations so we don't create duplicated
    $available_variations = array();
    
    foreach($_product->get_children() as $child) {
        $variation = $child->product;
        if($variation instanceof woocommerce_product_variation) {
            $available_variations[] = $variation->get_variation_attributes();
        }
    }
	
	// Created posts will all have the following data
	$variation_post_data = array(
		'post_title' => 'Product #' . $post_id . ' Variation',
		'post_content' => '',
		'post_status' => 'publish',
		'post_author' => get_current_user_id(),
		'post_parent' => $post_id,
		'post_type' => 'product_variation'
	);
		
	// Now find all combinations and create posts
	if (!function_exists('array_cartesian')) {
		function array_cartesian($input) {
		    $result = array();
		 
		    while (list($key, $values) = each($input)) {
		        // If a sub-array is empty, it doesn't affect the cartesian product
		        if (empty($values)) {
		            continue;
		        }
		 
		        // Special case: seeding the product array with the values from the first sub-array
		        if (empty($result)) {
		            foreach($values as $value) {
		                $result[] = array($key => $value);
		            }
		        }
		        else {
		            // Second and subsequent input sub-arrays work like this:
		            //   1. In each existing array inside $product, add an item with
		            //      key == $key and value == first item in input sub-array
		            //   2. Then, for each remaining item in current input sub-array,
		            //      add a copy of each existing array inside $product with
		            //      key == $key and value == first item in current input sub-array
		 
		            // Store all items to be added to $product here; adding them on the spot
		            // inside the foreach will result in an infinite loop
		            $append = array();
		            foreach($result as &$product) {
		                // Do step 1 above. array_shift is not the most efficient, but it
		                // allows us to iterate over the rest of the items with a simple
		                // foreach, making the code short and familiar.
		                $product[$key] = array_shift($values);
		 
		                // $product is by reference (that's why the key we added above
		                // will appear in the end result), so make a copy of it here
		                $copy = $product;
		 
		                // Do step 2 above.
		                foreach($values as $item) {
		                    $copy[$key] = $item;
		                    $append[] = $copy;
		                }
		 
		                // Undo the side effecst of array_shift
		                array_unshift($values, $product[$key]);
		            }
		 
		            // Out of the foreach, we can add to $results now
		            $result = array_merge($result, $append);
		        }
		    }
		    
		    return $result;
		}
	}
	
	$variation_ids = array();
	$possible_variations = array_cartesian( $variations );
	
	foreach ($possible_variations as $variation) :
		
		// Check if variation already exists
		if (in_array($variation, $available_variations)) continue;
		
		$variation_id = wp_insert_post( $variation_post_data );
		
		$variation_ids[] = $variation_id;
		
		foreach ($variation as $key => $value) :
			
			update_post_meta( $variation_id, $key, $value );
			
		endforeach;
		
	endforeach;
	
	echo 1;
		
	die();
	
}


/**
 * Product Type selector
 * 
 * Adds this product type to the product type selector in the product options meta box
 */
function variable_product_type_selector( $types, $product_type ) {
	$types['variable'] = __('Variable', 'woothemes');
	return $types;
}
add_filter('product_type_selector', 'variable_product_type_selector', 1, 2);

/**
 * Process meta
 * 
 * Processes this product types options when a post is saved
 */
function process_product_meta_variable( $post_id ) {
	global $woocommerce; 
	
	if (isset($_POST['variable_sku'])) :
		
		$variable_post_id 	= $_POST['variable_post_id'];
		$variable_sku 		= $_POST['variable_sku'];
		$variable_weight	= $_POST['variable_weight'];
		$variable_stock 	= $_POST['variable_stock'];
		$variable_price 	= $_POST['variable_price'];
		$variable_sale_price= $_POST['variable_sale_price'];
		$upload_image_id		= $_POST['upload_image_id'];
		if (isset($_POST['variable_enabled'])) $variable_enabled = $_POST['variable_enabled'];
		
		$attributes = (array) maybe_unserialize( get_post_meta($post_id, 'product_attributes', true) );
		
		for ($i=0; $i<sizeof($variable_sku); $i++) :
			
			$variation_id = (int) $variable_post_id[$i];

			// Enabled or disabled
			if (isset($variable_enabled[$i])) $post_status = 'publish'; else $post_status = 'private';
			
			// Generate a useful post title
			$title = array();
			
			foreach ($attributes as $attribute) :
				if ( $attribute['is_variation'] ) :
					$value = esc_attr(trim($_POST[ 'attribute_' . sanitize_title($attribute['name']) ][$i]));
					if ($value) :
						$title[] = $woocommerce->attribute_label($attribute['name']).': '.$value;
					endif;
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
			update_post_meta( $variation_id, 'sku', $variable_sku[$i] );
			update_post_meta( $variation_id, 'price', $variable_price[$i] );
			update_post_meta( $variation_id, 'sale_price', $variable_sale_price[$i] );
			update_post_meta( $variation_id, 'weight', $variable_weight[$i] );
			update_post_meta( $variation_id, 'stock', $variable_stock[$i] );
			update_post_meta( $variation_id, '_thumbnail_id', $upload_image_id[$i] );
			
			// Remove old taxnomies attributes so data is kept up to date
			$variation_custom_fields = get_post_custom( $variation_id );
			
			foreach ($variation_custom_fields as $name => $value) :
				if (!strstr($name, 'attribute_')) continue;
				delete_post_meta( $variation_id, $name );
			endforeach;
		
			// Update taxonomies
			foreach ($attributes as $attribute) :
							
				if ( $attribute['is_variation'] ) :
				
					$value = esc_attr(trim($_POST[ 'attribute_' . sanitize_title($attribute['name']) ][$i]));
					
					update_post_meta( $variation_id, 'attribute_' . sanitize_title($attribute['name']), $value );
				
				endif;

			endforeach;
		 	
		 endfor; 
		 
	endif;
	
	// Update parent if variable so price sorting works and stays in sync with the cheapest child
	$post_parent = $post_id;
	
	$children = get_posts( array(
		'post_parent' 	=> $post_parent,
		'posts_per_page'=> -1,
		'post_type' 	=> 'product_variation',
		'fields' 		=> 'ids'
	));
	$lowest_price = '';
	$highest_price = '';
	if ($children) :
		foreach ($children as $child) :
			$child_price = get_post_meta($child, 'price', true);
			$child_sale_price = get_post_meta($child, 'sale_price', true);
			
			// Low price
			if (!is_numeric($lowest_price) || $child_price<$lowest_price) $lowest_price = $child_price;
			if (!empty($child_sale_price) && $child_sale_price<$lowest_price) $lowest_price = $child_sale_price;
			
			// High price
			if (!empty($child_sale_price)) :
				if ($child_sale_price>$highest_price) :
					$highest_price = $child_sale_price;
				endif;	
			else :
				if ($child_price>$highest_price) :
					$highest_price = $child_price;
				endif;
			endif;
			
		endforeach;
	endif;
	
	update_post_meta( $post_parent, 'price', $lowest_price );
	update_post_meta( $post_parent, 'min_variation_price', $lowest_price );
	update_post_meta( $post_parent, 'max_variation_price', $highest_price );
	
	// Update default attribute options setting
	$default_attributes = array();
	
	foreach ($attributes as $attribute) :
		if ( $attribute['is_variation'] ) :
			$value = esc_attr(trim($_POST[ 'default_attribute_' . sanitize_title($attribute['name']) ]));
			if ($value) :
				$default_attributes[sanitize_title($attribute['name'])] = $value;
			endif;
		endif;
	endforeach;
	
	update_post_meta( $post_parent, '_default_attributes', $default_attributes );

}
add_action('woocommerce_process_product_meta_variable', 'process_product_meta_variable');