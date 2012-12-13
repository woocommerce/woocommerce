<?php
/**
 * Variable Product Type
 *
 * Functions specific to variable products (for the write panels).
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/WritePanels
 * @version     1.6.4
 */


/**
 * Display the variation tab in product data.
 *
 * @access public
 * @return void
 */
function variable_product_type_options_tab() {
	?>
	<li class="variations_tab show_if_variable variation_options"><a href="#variable_product_options" title="<?php _e('Variations for variable products are defined here.', 'woocommerce'); ?>"><?php _e('Variations', 'woocommerce'); ?></a></li>
	<?php
}

add_action('woocommerce_product_write_panel_tabs', 'variable_product_type_options_tab');


/**
 * Show the variable product options.
 *
 * @access public
 * @return void
 */
function variable_product_type_options() {
	global $post, $woocommerce;

	$attributes = (array) maybe_unserialize( get_post_meta($post->ID, '_product_attributes', true) );

	// See if any are set
	$variation_attribute_found = false;
	if ($attributes) foreach($attributes as $attribute){
		if (isset($attribute['is_variation'])) :
			$variation_attribute_found = true;
			break;
		endif;
	}

	// Get tax classes
	$tax_classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
	$tax_class_options = array();
	$tax_class_options['parent'] =__('Same as parent', 'woocommerce');
	$tax_class_options[''] = __('Standard', 'woocommerce');
	if ($tax_classes) foreach ( $tax_classes as $class )
		$tax_class_options[sanitize_title($class)] = $class;

	?>
	<div id="variable_product_options" class="panel wc-metaboxes-wrapper">

		<?php if (!$variation_attribute_found) : ?>

			<div id="message" class="inline woocommerce-message">
				<div class="squeezer">
					<h4><?php _e( 'Before adding variations, add and save some attributes on the <strong>Attributes</strong> tab.', 'woocommerce' ); ?></h4>

					<p class="submit"><a class="button-primary" href="http://www.woothemes.com/woocommerce-docs/user-guide/product-variations/" target="_blank"><?php _e('Learn more', 'woocommerce'); ?></a></p>
				</div>
			</div>

		<?php else : ?>

			<p class="toolbar">
				<a href="#" class="close_all"><?php _e('Close all', 'woocommerce'); ?></a><a href="#" class="expand_all"><?php _e('Expand all', 'woocommerce'); ?></a>
				<strong><?php _e('Bulk edit:', 'woocommerce'); ?></strong>
				<select id="field_to_edit">
					<option value="variable_price"><?php _e('Prices', 'woocommerce'); ?></option>
					<option value="variable_sale_price"><?php _e('Sale prices', 'woocommerce'); ?></option>
					<option value="variable_stock"><?php _e('Stock', 'woocommerce'); ?></option>
					<option value="variable_weight"><?php _e('Weight', 'woocommerce'); ?></option>
					<option value="variable_length"><?php _e('Length', 'woocommerce'); ?></option>
					<option value="variable_width"><?php _e('Width', 'woocommerce'); ?></option>
					<option value="variable_height"><?php _e('Height', 'woocommerce'); ?></option>
					<option value="variable_file_path"><?php _e('File Path', 'woocommerce'); ?></option>
					<option value="variable_download_limit"><?php _e('Download limit', 'woocommerce'); ?></option>
				</select>
				<a class="button bulk_edit plus"><?php _e('Edit', 'woocommerce'); ?></a>
				<a class="button toggle toggle_downloadable" href="#"><?php _e('Downloadable', 'woocommerce'); ?></a> <a class="button toggle toggle_virtual" href="#"><?php _e('Virtual', 'woocommerce'); ?></a> <a class="button toggle toggle_enabled" href="#"><?php _e('Enabled', 'woocommerce'); ?></a> <a href="#" class="button delete_variations"><?php _e('Delete all', 'woocommerce'); ?></a>
			</p>

			<div class="woocommerce_variations wc-metaboxes">
				<?php
				$args = array(
					'post_type'	=> 'product_variation',
					'post_status' => array('private', 'publish'),
					'numberposts' => -1,
					'orderby' => 'menu_order',
					'order' => 'asc',
					'post_parent' => $post->ID
				);
				$variations = get_posts($args);
				$loop = 0;
				if ($variations) foreach ($variations as $variation) :

					$variation_data = get_post_custom( $variation->ID );
					$variation_data['variation_post_id'] = $variation->ID;
					$image = '';
					if (isset($variation_data['_thumbnail_id'][0])) :
						$image_id = $variation_data['_thumbnail_id'][0];
						$image = wp_get_attachment_url( $variation_data['_thumbnail_id'][0] );
					else :
						$image_id = 0;
					endif;

					if (!$image) $image = woocommerce_placeholder_img_src();

					$classes = get_the_terms( $variation->ID, 'product_shipping_class' );
					if ( $classes && ! is_wp_error( $classes ) ) $current_shipping_class = current($classes)->term_id; else $current_shipping_class = '';

		    		$parent_tax_class = get_post_meta( $post->ID, '_tax_class', true );
					?>
					<div class="woocommerce_variation wc-metabox closed">
						<h3>
							<button type="button" class="remove_variation button" rel="<?php echo $variation->ID; ?>"><?php _e('Remove', 'woocommerce'); ?></button>
							<div class="handlediv" title="<?php _e('Click to toggle', 'woocommerce'); ?>"></div>
							<strong>#<?php echo $variation->ID; ?> &mdash; </strong>
							<?php
								foreach ($attributes as $attribute) :

									// Only deal with attributes that are variations
									if ( !$attribute['is_variation'] ) continue;

									// Get current value for variation (if set)
									$variation_selected_value = get_post_meta( $variation->ID, 'attribute_' . sanitize_title($attribute['name']), true );

									// Name will be something like attribute_pa_color
									echo '<select name="attribute_' . sanitize_title($attribute['name']).'['.$loop.']"><option value="">'.__('Any', 'woocommerce') . ' ' . $woocommerce->attribute_label($attribute['name']).'&hellip;</option>';

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
							<input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo $loop; ?>]" value="<?php echo $loop; ?>" />
						</h3>
						<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes wc-metabox-content">
							<tbody>
								<tr>
									<td class="sku" colspan="2">
										<?php if( get_option('woocommerce_enable_sku', true) !== 'no' ) : ?>
											<label><?php _e('SKU', 'woocommerce'); ?>: <a class="tips" data-tip="<?php _e('Enter a SKU for this variation or leave blank to use the parent product SKU.', 'woocommerce'); ?>" href="#">[?]</a></label>
											<input type="text" size="5" name="variable_sku[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_sku'][0])) echo $variation_data['_sku'][0]; ?>" placeholder="<?php if ($sku = get_post_meta($post->ID, '_sku', true)) echo $sku; ?>" />
										<?php else : ?>
											<input type="hidden" name="variable_sku[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_sku'][0])) echo $variation_data['_sku'][0]; ?>" />
										<?php endif; ?>
									</td>

									<td class="data" rowspan="2">
										<table cellspacing="0" cellpadding="0">
											<tr>
												<td><label><?php _e('Stock Qty:', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Enter a quantity to enable stock management for this variation, or leave blank to use the variable product stock options.', 'woocommerce'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_stock[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_stock'][0])) echo $variation_data['_stock'][0]; ?>" /></td>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td><label><?php _e('Price:', 'woocommerce'); ?></label><input type="text" size="5" name="variable_price[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_price'][0])) echo $variation_data['_price'][0]; ?>" /></td>

												<td><label><?php _e('Sale Price:', 'woocommerce'); ?></label><input type="text" size="5" name="variable_sale_price[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_sale_price'][0])) echo $variation_data['_sale_price'][0]; ?>" /></td>
											</tr>
											<?php if( get_option('woocommerce_enable_weight', true) !== 'no' || get_option('woocommerce_enable_dimensions', true) !== 'no' ) : ?>
											<tr>
												<?php if( get_option('woocommerce_enable_weight', true) !== 'no' ) : ?>
													<td class="hide_if_variation_virtual"><label><?php _e('Weight', 'woocommerce').' ('.get_option('woocommerce_weight_unit').'):'; ?> <a class="tips" data-tip="<?php _e('Enter a weight for this variation or leave blank to use the parent product weight.', 'woocommerce'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_weight[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_weight'][0])) echo $variation_data['_weight'][0]; ?>" placeholder="<?php if ($value = get_post_meta($post->ID, '_weight', true)) echo $value; else echo '0.00'; ?>" /></td>
												<?php else : ?>
													<td>&nbsp;</td>
												<?php endif; ?>
												<?php if( get_option('woocommerce_enable_dimensions', true) !== 'no' ) : ?>
													<td class="dimensions_field hide_if_variation_virtual">
														<label for"product_length"><?php echo __('Dimensions (L&times;W&times;H)', 'woocommerce'); ?></label>
														<input id="product_length" class="input-text" size="6" type="text" name="variable_length[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_length'][0])) echo $variation_data['_length'][0]; ?>" placeholder="<?php if ($value = get_post_meta($post->ID, '_length', true)) echo $value; else echo '0'; ?>" />
														<input class="input-text" size="6" type="text" name="variable_width[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_width'][0])) echo $variation_data['_width'][0]; ?>" placeholder="<?php if ($value = get_post_meta($post->ID, '_width', true)) echo $value; else echo '0'; ?>" />
														<input class="input-text last" size="6" type="text" name="variable_height[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_height'][0])) echo $variation_data['_height'][0]; ?>" placeholder="<?php if ($value = get_post_meta($post->ID, '_height', true)) echo $value; else echo '0'; ?>" />
													</td>
												<?php else : ?>
													<td>&nbsp;</td>
												<?php endif; ?>
											</tr>
											<?php endif; ?>

											<tr>
												<td><label><?php _e('Shipping class:', 'woocommerce'); ?></label> <?php
													$args = array(
														'taxonomy' 			=> 'product_shipping_class',
														'hide_empty'		=> 0,
														'show_option_none' 	=> __('Same as parent', 'woocommerce'),
														'name' 				=> 'variable_shipping_class['.$loop.']',
														'id'				=> '',
														'selected'			=> $current_shipping_class
													);
													wp_dropdown_categories( $args );
												?></td>
												<td><label><?php _e('Tax class:', 'woocommerce'); ?></label> <select name="variable_tax_class[<?php echo $loop; ?>]"><?php
													foreach ( $tax_class_options as $key => $tax_class ) {
														echo '<option value="' . $key . '" ';
														if ( isset( $variation_data['_tax_class'][0] ) ) selected($key, $variation_data['_tax_class'][0]);
														echo '>' . $tax_class . '</option>';
													}
												?></select></td>
											</tr>
											<tr class="show_if_variation_downloadable">
												<td>
													<div class="file_path_field">
													<label><?php _e('File path:', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Enter a File Path to make this variation a downloadable product, or leave blank.', 'woocommerce'); ?>" href="#">[?]</a></label><input type="text" size="5" class="file_path" name="variable_file_path[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_file_path'][0])) echo $variation_data['_file_path'][0]; ?>" placeholder="<?php _e('File path/URL', 'woocommerce'); ?>" /> <input type="button"  class="upload_file_button button" value="<?php _e('&uarr;', 'woocommerce'); ?>" title="<?php _e('Upload', 'woocommerce'); ?>" />
													</div>
												</td>
												<td>
													<div>
													<label><?php _e('Download Limit:', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Leave blank for unlimited re-downloads.', 'woocommerce'); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_download_limit[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['_download_limit'][0])) echo $variation_data['_download_limit'][0]; ?>" placeholder="<?php _e('Unlimited', 'woocommerce'); ?>" />
													</div>
												</td>
											</tr>
											<?php do_action( 'woocommerce_product_after_variable_attributes', $loop, $variation_data ); ?>
										</table>
									</td>
								</tr>
								<tr>
									<td class="upload_image">

										<a href="#" class="upload_image_button <?php if ($image_id>0) echo 'remove'; ?>" rel="<?php echo $variation->ID; ?>"><img src="<?php echo $image ?>" /><input type="hidden" name="upload_image_id[<?php echo $loop; ?>]" class="upload_image_id" value="<?php echo $image_id; ?>" /><span class="overlay"></span></a>

									</td>
									<td class="options">

										<label><input type="checkbox" class="checkbox" name="variable_enabled[<?php echo $loop; ?>]" <?php checked($variation->post_status, 'publish'); ?> /> <?php _e('Enabled', 'woocommerce'); ?></label>

										<label><input type="checkbox" class="checkbox variable_is_downloadable" name="variable_is_downloadable[<?php echo $loop; ?>]" <?php if (isset($variation_data['_downloadable'][0])) checked($variation_data['_downloadable'][0], 'yes'); ?> /> <?php _e('Downloadable', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Enable this option if access is given to a downloadable file upon purchase of a product', 'woocommerce'); ?>" href="#">[?]</a></label>

										<label><input type="checkbox" class="checkbox variable_is_virtual" name="variable_is_virtual[<?php echo $loop; ?>]" <?php if (isset($variation_data['_virtual'][0])) checked($variation_data['_virtual'][0], 'yes'); ?> /> <?php _e('Virtual', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('Enable this option if a product is not shipped or there is no shipping cost', 'woocommerce'); ?>" href="#">[?]</a></label>

										<?php do_action( 'woocommerce_variation_options', $loop, $variation_data ); ?>

									</td>
								</tr>
							</tbody>
						</table>
					</div>
				<?php $loop++; endforeach; ?>
			</div>

			<p class="toolbar">

				<button type="button" class="button button-primary add_variation" <?php disabled($variation_attribute_found, false); ?>><?php _e('Add Variation', 'woocommerce'); ?></button>

				<button type="button" class="button link_all_variations" <?php disabled($variation_attribute_found, false); ?>><?php _e('Link all variations', 'woocommerce'); ?></button>

				<strong><?php _e('Default selections:', 'woocommerce'); ?></strong>
				<?php
					$default_attributes = (array) maybe_unserialize(get_post_meta( $post->ID, '_default_attributes', true ));
					foreach ($attributes as $attribute) :

						// Only deal with attributes that are variations
						if ( !$attribute['is_variation'] ) continue;

						// Get current value for variation (if set)
						$variation_selected_value = (isset($default_attributes[sanitize_title($attribute['name'])])) ? $default_attributes[sanitize_title($attribute['name'])] : '';

						// Name will be something like attribute_pa_color
						echo '<select name="default_attribute_' . sanitize_title($attribute['name']).'"><option value="">'.__('No default', 'woocommerce') . ' ' . $woocommerce->attribute_label($attribute['name']).'&hellip;</option>';

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

		<?php endif; ?>

		<div class="clear"></div>
	</div>
	<?php
	/**
	 * Product Type Javascript
	 */
	ob_start();
	?>
	jQuery(function(){

		<?php if (!$attributes || (is_array($attributes) && sizeof($attributes)==0)) : ?>

			jQuery('#variable_product_options').on('click', 'button.link_all_variations, button.add_variation', function(){

				alert('<?php _e('You must add some attributes via the "Product Data" panel and save before adding a new variation.', 'woocommerce'); ?>');

				return false;

			});

		<?php else : ?>

		// Add a variation
		jQuery('#variable_product_options').on('click', 'button.add_variation', function(){

			jQuery('.woocommerce_variations').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action: 'woocommerce_add_variation',
				post_id: <?php echo $post->ID; ?>,
				security: '<?php echo wp_create_nonce("add-variation"); ?>'
			};

			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

				var variation_id = parseInt(response);

				var loop = jQuery('.woocommerce_variation').size();

				jQuery('.woocommerce_variations').append('<div class="woocommerce_variation wc-metabox">\
					<h3 class="handle">\
						<button type="button" class="remove_variation button" rel="' + variation_id + '"><?php echo esc_js( __('Remove', 'woocommerce') ); ?></button>\
						<div class="handlediv" title="<?php echo esc_js( __('Click to toggle', 'woocommerce') ); ?>"></div>\
						<strong>#' + variation_id + ' &mdash; </strong>\
						<?php
							if ($attributes) foreach ($attributes as $attribute) :

								if ( !isset($attribute['is_variation']) || !$attribute['is_variation'] ) continue;

								echo '<select name="attribute_' . sanitize_title($attribute['name']).'[\' + loop + \']"><option value="">' . esc_js( __('Any', 'woocommerce') ) . ' ' .$woocommerce->attribute_label($attribute['name']).'&hellip;</option>';

								// Get terms for attribute taxonomy or value if its a custom attribute
								if ($attribute['is_taxonomy']) :
									$post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );
									foreach ($post_terms as $term) :
										echo '<option value="'.$term->slug.'">'.esc_html($term->name).'</option>';
									endforeach;
								else :
									$options = explode('|', $attribute['value']);
									foreach ($options as $option) :
										echo '<option value="'.$option.'">'.ucfirst($option).'</option>';
									endforeach;
								endif;

								echo '</select>';

							endforeach;
					?><input type="hidden" name="variable_post_id[' + loop + ']" value="' + variation_id + '" /><input type="hidden" class="variation_menu_order" name="variation_menu_order[' + loop + ']" value="' + loop + '" /></h3>\
					<table cellpadding="0" cellspacing="0" class="woocommerce_variable_attributes wc-metabox-content">\
						<tbody>	\
							<tr>\
								<td class="sku" colspan="2">\
									<?php if( get_option('woocommerce_enable_sku', true) !== 'no' ) : ?>\
										<label><?php echo esc_js( __('SKU', 'woocommerce') ); ?>: <a class="tips" data-tip="<?php echo esc_js( __('Enter a SKU for this variation or leave blank to use the parent product SKU.', 'woocommerce') ); ?>" href="#">[?]</a></label>\
										<input type="text" size="5" name="variable_sku[' + loop + ']" placeholder="<?php if ($sku = get_post_meta($post->ID, '_sku', true)) echo $sku; ?>" />\
									<?php else : ?>\
										<input type="hidden" name="variable_sku[' + loop + ']" />\
									<?php endif; ?>\
								</td>\
								<td class="data" rowspan="2">\
									<table cellspacing="0">\
										<tr>\
											<td><label><?php echo esc_js( __('Stock Qty:', 'woocommerce') ); ?> <a class="tips" data-tip="<?php echo esc_js( __('Enter a quantity to enable stock management for this variation, or leave blank to use the variable product stock options.', 'woocommerce') ); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_stock[' + loop + ']" /></td>\
											<td>&nbsp;</td>\
										</tr>\
										<tr>\
											<td><label><?php echo esc_js( __('Price:', 'woocommerce') ); ?></label><input type="text" size="5" name="variable_price[' + loop + ']" /></td>\
											<td><label><?php echo esc_js( __('Sale Price:', 'woocommerce') ); ?></label><input type="text" size="5" name="variable_sale_price[' + loop + ']" /></td>\
										</tr>\
										<?php if( get_option('woocommerce_enable_weight', true) !== 'no' || get_option('woocommerce_enable_dimensions', true) !== 'no' ) : ?>\
										<tr>\
											<?php if( get_option('woocommerce_enable_weight', true) !== 'no' ) : ?>\
												<td class="hide_if_variation_virtual"><label><?php echo esc_js( __('Weight', 'woocommerce') ).' ('.get_option('woocommerce_weight_unit').'):'; ?> <a class="tips" data-tip="<?php echo esc_js( __('Enter a weight for this variation or leave blank to use the parent product weight.', 'woocommerce') ); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_weight[' + loop + ']" placeholder="<?php if ($value = get_post_meta($post->ID, '_weight', true)) echo $value; else echo '0.00'; ?>" /></td>\
											<?php else : ?>\
												<td>&nbsp;</td>\
											<?php endif; ?>\
											<?php if( get_option('woocommerce_enable_dimensions', true) !== 'no' ) : ?>\
												<td class="dimensions_field hide_if_variation_virtual">\
													<label for"product_length"><?php echo esc_js( __('Dimensions (L&times;W&times;H)', 'woocommerce') ); ?></label>\
													<input id="product_length" class="input-text" size="6" type="text" name="variable_length[' + loop + ']" placeholder="<?php if ($value = get_post_meta($post->ID, '_length', true)) echo $value; else echo '0'; ?>" />\
													<input class="input-text" size="6" type="text" name="variable_width[' + loop + ']" placeholder="<?php if ($value = get_post_meta($post->ID, '_width', true)) echo $value; else echo '0'; ?>" />\
													<input class="input-text last" size="6" type="text" name="variable_height[' + loop + ']" placeholder="<?php if ($value = get_post_meta($post->ID, '_height', true)) echo $value; else echo '0'; ?>" />\
												</td>\
											<?php else : ?>\
												<td>&nbsp;</td>\
											<?php endif; ?>\
										</tr>\
										<?php endif; ?>\
										<tr>\
											<td><label><?php echo esc_js( __('Shipping class:', 'woocommerce') ); ?></label> <?php
												$args = array(
													'taxonomy' 			=> 'product_shipping_class',
													'hide_empty'		=> 0,
													'show_option_none' 	=> __('Same as parent', 'woocommerce'),
													'name' 				=> 'variable_shipping_class[]',
													'id'				=> '',
													'echo'				=> 0
												);
												echo addslashes(str_replace('[]', "[' + loop + ']", str_replace("\n", '', wp_dropdown_categories( $args ))));
											?></td>\
											<td><label><?php echo esc_js( __('Tax class:', 'woocommerce') ); ?></label> <select name="variable_tax_class[' + loop + ']"><?php
												foreach ( $tax_class_options as $key => $tax_class ) {
													echo '<option value="' . $key . '" ';
													if ( isset( $variation_data['_tax_class'][0] ) ) echo addslashes(selected($key, $variation_data['_tax_class'][0], false));
													echo '>' . $tax_class . '</option>';
												}
											?></select></td>\
										</tr>\
										<tr class="show_if_variation_downloadable">\
											<td>\
												<div class="file_path_field"><label><?php echo esc_js( __('File path:', 'woocommerce') ); ?> <a class="tips" data-tip="<?php echo esc_js( __('Enter a File Path to make this variation a downloadable product, or leave blank.', 'woocommerce') ); ?>" href="#">[?]</a></label><input type="text" size="5" class="file_path" name="variable_file_path[' + loop + ']" placeholder="<?php echo esc_js( __('File path/URL', 'woocommerce') ); ?>" /> <input type="button"  class="upload_file_button button" value="<?php echo esc_js( __('&uarr;', 'woocommerce') ); ?>" title="<?php echo esc_js( __('Upload', 'woocommerce') ); ?>" /></div>\
											</td>\
											<td>\
												<div><label><?php echo esc_js( __('Download Limit:', 'woocommerce') ); ?> <a class="tips" data-tip="<?php echo esc_js( __('Leave blank for unlimited re-downloads.', 'woocommerce') ); ?>" href="#">[?]</a></label><input type="text" size="5" name="variable_download_limit[' + loop + ']" placeholder="<?php echo esc_js( __('Unlimited', 'woocommerce') ); ?>" /></div>\
											</td>\
										</tr>\
										<?php do_action( 'woocommerce_product_after_variable_attributes_js' ); ?>\
									</table>\
								</td>\
							</tr>\
							<tr>\
								<td class="upload_image"><a href="#" class="upload_image_button" rel="' + variation_id + '"><img src="<?php echo woocommerce_placeholder_img_src() ?>" /><input type="hidden" name="upload_image_id[' + loop + ']" class="upload_image_id" /><span class="overlay"></span></a></td>\
								<td class="options">\
									\
									<label><input type="checkbox" class="checkbox" name="variable_enabled[' + loop + ']" checked="checked" /> <?php echo esc_js( __('Enabled', 'woocommerce') ); ?></label>\
									\
									<label><input type="checkbox" class="checkbox variable_is_downloadable" name="variable_is_downloadable[' + loop + ']" /> <?php echo esc_js( __('Downloadable', 'woocommerce') ); ?> <a class="tips" data-tip="<?php echo esc_js( __('Enable this option if access is given to a downloadable file upon purchase of a product', 'woocommerce') ); ?>" href="#">[?]</a></label>\
									\
									<label><input type="checkbox" class="checkbox variable_is_virtual" name="variable_is_virtual[' + loop + ']" /> <?php echo esc_js( __('Virtual', 'woocommerce') ); ?> <a class="tips" data-tip="<?php echo esc_js( __('Enable this option if a product is not shipped or there is no shipping cost', 'woocommerce') ); ?>" href="#">[?]</a></label>\
									\
								</td>\
							</tr>\
						</tbody>\
					</table>\
				</div>');

				jQuery(".tips").tipTip({
			    	'attribute' : 'data-tip',
			    	'fadeIn' : 50,
			    	'fadeOut' : 50
			    });

			    jQuery('input.variable_is_downloadable, input.variable_is_virtual').change();

				jQuery('.woocommerce_variations').unblock();

			});

			return false;

		});

		jQuery('#variable_product_options').on('click', 'button.link_all_variations', function(){

			var answer = confirm('<?php _e('Are you sure you want to link all variations? This will create a new variation for each and every possible combination of variation attributes (max 50 per run).', 'woocommerce'); ?>');

			if (answer) {

				jQuery('#variable_product_options').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

				var data = {
					action: 'woocommerce_link_all_variations',
					post_id: <?php echo $post->ID; ?>,
					security: '<?php echo wp_create_nonce("link-variations"); ?>'
				};

				jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

					var count = parseInt( response );

					if (count>0) {
						jQuery('.woocommerce_variations').load( window.location + ' .woocommerce_variations > *' );
					}

					if (count==1) {
						alert( count + ' <?php _e("variation added", 'woocommerce'); ?>');
					} else if (count==0 || count>1) {
						alert( count + ' <?php _e("variations added", 'woocommerce'); ?>');
					} else {
						alert('<?php _e("No variations added", 'woocommerce'); ?>');
					}

					jQuery('#variable_product_options').unblock();

				});
			}
			return false;
		});

		jQuery('#variable_product_options').on('click', 'button.remove_variation', function(e){
			e.preventDefault();
			var answer = confirm('<?php _e('Are you sure you want to remove this variation?', 'woocommerce'); ?>');
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

		jQuery('#variable_product_options').on('click', 'a.delete_variations', function(){
			var answer = confirm('<?php _e('Are you sure you want to delete all variations? This cannot be undone.', 'woocommerce'); ?>');
			if (answer){

				var answer = confirm('<?php _e('Last warning, are you sure?', 'woocommerce'); ?>');

				if (answer) {

					var variation_ids = [];

					jQuery('.woocommerce_variations .woocommerce_variation').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

					jQuery('.woocommerce_variations .woocommerce_variation .remove_variation').each(function(){

						var variation = jQuery(this).attr('rel');
						if (variation>0) {
							variation_ids.push(variation);
						}
					});

					var data = {
						action: 'woocommerce_remove_variations',
						variation_ids: variation_ids,
						security: '<?php echo wp_create_nonce("delete-variations"); ?>'
					};

					jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
						jQuery('.woocommerce_variations .woocommerce_variation').fadeOut('300', function(){
							jQuery('.woocommerce_variations .woocommerce_variation').remove();
						});
					});

				}

			}
			return false;
		});

		jQuery('a.bulk_edit').click(function() {
			var field_to_edit = jQuery('select#field_to_edit').val();
			var value = prompt("<?php _e('Enter a value', 'woocommerce'); ?>");
			jQuery('input[name^="' + field_to_edit + '"]').val( value );
			return false;
		});

		jQuery('a.toggle_virtual').click(function(){
			var checkbox = jQuery('input[name^="variable_is_virtual"]');
       		checkbox.attr('checked', !checkbox.attr('checked'));
       		jQuery('input.variable_is_virtual').change();
			return false;
		});

		jQuery('a.toggle_downloadable').click(function(){
			var checkbox = jQuery('input[name^="variable_is_downloadable"]');
       		checkbox.attr('checked', !checkbox.attr('checked'));
       		jQuery('input.variable_is_downloadable').change();
			return false;
		});

		jQuery('a.toggle_enabled').click(function(){
			var checkbox = jQuery('input[name^="variable_enabled"]');
       		checkbox.attr('checked', !checkbox.attr('checked'));
			return false;
		});

		jQuery('#variable_product_options').on('change', 'input.variable_is_downloadable', function(){

			jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').hide();

			if (jQuery(this).is(':checked')) {
				jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').show();
			}

		});

		jQuery('#variable_product_options').on('change', 'input.variable_is_virtual', function(){

			jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').show();

			if (jQuery(this).is(':checked')) {
				jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').hide();
			}

		});

		jQuery('input.variable_is_downloadable, input.variable_is_virtual').change();

		// Ordering
		$('.woocommerce_variations').sortable({
			items:'.woocommerce_variation',
			cursor:'move',
			axis:'y',
			handle: 'h3',
			scrollSensitivity:40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-metabox-sortable-placeholder',
			start:function(event,ui){
				ui.item.css('background-color','#f6f6f6');
			},
			stop:function(event,ui){
				ui.item.removeAttr('style');
				variation_row_indexes();
			}
		});

		function variation_row_indexes() {
			$('.woocommerce_variations .woocommerce_variation').each(function(index, el){
				$('.variation_menu_order', el).val( parseInt( $(el).index('.woocommerce_variations .woocommerce_variation') ) );
			});
		};

		<?php endif; ?>

		var current_field_wrapper;

		window.send_to_editor_default = window.send_to_editor;

		jQuery('#variable_product_options').on('click', '.upload_image_button', function(){

			var post_id = jQuery(this).attr('rel');
			var parent = jQuery(this).parent();
			current_field_wrapper = parent;

			if (jQuery(this).is('.remove')) {

				jQuery('.upload_image_id', current_field_wrapper).val('');
				jQuery('img', current_field_wrapper).attr('src', '<?php echo woocommerce_placeholder_img_src(); ?>');
				jQuery(this).removeClass('remove');

			} else {

				window.send_to_editor = window.send_to_cproduct;
				formfield = jQuery('.upload_image_id', parent).attr('name');
				tb_show('', 'media-upload.php?post_id=' + post_id + '&amp;type=image&amp;TB_iframe=true');

			}

			return false;
		});

		window.send_to_cproduct = function(html) {

			jQuery('body').append('<div id="temp_image">' + html + '</div>');

			var img = jQuery('#temp_image').find('img');

			imgurl 		= img.attr('src');
			imgclass 	= img.attr('class');
			imgid		= parseInt(imgclass.replace(/\D/g, ''), 10);

			jQuery('.upload_image_id', current_field_wrapper).val(imgid);
			jQuery('.upload_image_button', current_field_wrapper).addClass('remove');

			jQuery('img', current_field_wrapper).attr('src', imgurl);
			tb_remove();
			jQuery('#temp_image').remove();

			window.send_to_editor = window.send_to_editor_default;

		}

	});
	<?php
	$javascript = ob_get_clean();
	$woocommerce->add_inline_js( $javascript );
}

add_action('woocommerce_product_write_panels', 'variable_product_type_options');


/**
 * Show the variable product selector in the dropdown in admin.
 *
 * @access public
 * @param mixed $types
 * @param mixed $product_type
 * @return array
 */
function variable_product_type_selector( $types, $product_type ) {
	$types['variable'] = __('Variable product', 'woocommerce');
	return $types;
}

add_filter('product_type_selector', 'variable_product_type_selector', 1, 2);


/**
 * Save the variable product options.
 *
 * @access public
 * @param mixed $post_id
 * @return void
 */
function process_product_meta_variable( $post_id ) {
	global $woocommerce, $wpdb;

	if (isset($_POST['variable_sku'])) {

		$variable_post_id 			= $_POST['variable_post_id'];
		$variable_sku 				= $_POST['variable_sku'];
		$variable_weight			= $_POST['variable_weight'];
		$variable_length			= $_POST['variable_length'];
		$variable_width				= $_POST['variable_width'];
		$variable_height			= $_POST['variable_height'];
		$variable_stock 			= $_POST['variable_stock'];
		$variable_price 			= $_POST['variable_price'];
		$variable_sale_price		= $_POST['variable_sale_price'];
		$upload_image_id			= $_POST['upload_image_id'];
		$variable_file_path 		= $_POST['variable_file_path'];
		$variable_download_limit 	= $_POST['variable_download_limit'];
		$variable_shipping_class 	= $_POST['variable_shipping_class'];
		$variable_tax_class			= $_POST['variable_tax_class'];
		$variable_menu_order 		= $_POST['variation_menu_order'];

		if (isset($_POST['variable_enabled']))
			$variable_enabled 			= $_POST['variable_enabled'];

		if (isset($_POST['variable_is_virtual']))
			$variable_is_virtual		= $_POST['variable_is_virtual'];

		if (isset($_POST['variable_is_downloadable']))
			$variable_is_downloadable 	= $_POST['variable_is_downloadable'];

		$attributes = (array) maybe_unserialize( get_post_meta($post_id, '_product_attributes', true) );

		$max_loop = max( array_keys( $_POST['variable_post_id'] ) );

		for ( $i=0; $i <= $max_loop; $i++ ) {

			if ( ! isset( $variable_post_id[$i] ) ) continue;

			$variation_id = (int) $variable_post_id[$i];

			// Virtal/Downloadable
			if (isset($variable_is_virtual[$i])) $is_virtual = 'yes'; else $is_virtual = 'no';
			if (isset($variable_is_downloadable[$i])) $is_downloadable = 'yes'; else $is_downloadable = 'no';

			// Enabled or disabled
			if (isset($variable_enabled[$i])) $post_status = 'publish'; else $post_status = 'private';

			// Generate a useful post title
			$variation_post_title = sprintf(__('Variation #%s of %s', 'woocommerce'), $variation_id, get_the_title($post_id));

			// Update or Add post
			if (!$variation_id) :

				$variation = array(
					'post_title' => $variation_post_title,
					'post_content' => '',
					'post_status' => $post_status,
					'post_author' => get_current_user_id(),
					'post_parent' => $post_id,
					'post_type' => 'product_variation',
					'menu_order' => $variable_menu_order[$i]
				);
				$variation_id = wp_insert_post( $variation );

			else :

				$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status, 'post_title' => $variation_post_title, 'menu_order' => $variable_menu_order[$i] ), array( 'ID' => $variation_id ) );

			endif;

			// Update post meta
			update_post_meta( $variation_id, '_sku', esc_html( $variable_sku[$i] ) );
			update_post_meta( $variation_id, '_price', $variable_price[$i] );
			update_post_meta( $variation_id, '_sale_price', $variable_sale_price[$i] );
			update_post_meta( $variation_id, '_weight', $variable_weight[$i] );

			update_post_meta( $variation_id, '_length', $variable_length[$i] );
			update_post_meta( $variation_id, '_width', $variable_width[$i] );
			update_post_meta( $variation_id, '_height', $variable_height[$i] );

			update_post_meta( $variation_id, '_stock', $variable_stock[$i] );
			update_post_meta( $variation_id, '_thumbnail_id', $upload_image_id[$i] );

			update_post_meta( $variation_id, '_virtual', $is_virtual );
			update_post_meta( $variation_id, '_downloadable', $is_downloadable );

			if ( $variable_tax_class[$i] !== 'parent' )
				update_post_meta( $variation_id, '_tax_class', $variable_tax_class[$i] );
			else
				delete_post_meta( $variation_id, '_tax_class' );

			if ($is_downloadable=='yes') :
				update_post_meta( $variation_id, '_download_limit', $variable_download_limit[$i] );
				update_post_meta( $variation_id, '_file_path', $variable_file_path[$i] );
			else :
				update_post_meta( $variation_id, '_download_limit', '' );
				update_post_meta( $variation_id, '_file_path', '' );
			endif;

			// Save shipping class
			$variable_shipping_class[$i] = $variable_shipping_class[$i] > 0 ? (int) $variable_shipping_class[$i] : '';
			wp_set_object_terms( $variation_id, $variable_shipping_class[$i], 'product_shipping_class');

			// Remove old taxonomies attributes so data is kept up to date
			if ( $variation_id ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND post_id = %d;", $variation_id ) );
				wp_cache_delete( $variation_id, 'post_meta');
			}

			// Update taxonomies
			foreach ($attributes as $attribute) :

				if ( $attribute['is_variation'] ) :

					$value = esc_attr(trim($_POST[ 'attribute_' . sanitize_title($attribute['name']) ][$i]));

					update_post_meta( $variation_id, 'attribute_' . sanitize_title($attribute['name']), $value );

				endif;

			endforeach;

		}

	}

	// Update parent if variable so price sorting works and stays in sync with the cheapest child
	$post_parent = $post_id;

	$children = get_posts( array(
		'post_parent' 	=> $post_parent,
		'posts_per_page'=> -1,
		'post_type' 	=> 'product_variation',
		'fields' 		=> 'ids',
		'post_status'	=> 'publish'
	));

	$lowest_price = $lowest_regular_price = $lowest_sale_price = $highest_price = $highest_regular_price = $highest_sale_price = '';

	if ($children) {
		foreach ($children as $child) {

			$child_price 		= get_post_meta($child, '_price', true);
			$child_sale_price 	= get_post_meta($child, '_sale_price', true);

			// Low price
			if (!is_numeric($lowest_regular_price) || $child_price < $lowest_regular_price) $lowest_regular_price = $child_price;
			if ($child_sale_price!=='' && (!is_numeric($lowest_sale_price) || $child_sale_price < $lowest_sale_price)) $lowest_sale_price = $child_sale_price;

			// High price
			if (!is_numeric($highest_regular_price) || $child_price > $highest_regular_price) $highest_regular_price = $child_price;
			if ($child_sale_price!=='' && (!is_numeric($highest_sale_price) || $child_sale_price > $highest_sale_price)) $highest_sale_price = $child_sale_price;
		}

    	$lowest_price = ($lowest_sale_price==='' || $lowest_regular_price < $lowest_sale_price) ? $lowest_regular_price : $lowest_sale_price;
		$highest_price = ($highest_sale_price==='' || $highest_regular_price > $highest_sale_price) ? $highest_regular_price : $highest_sale_price;
	}

	update_post_meta( $post_parent, '_price', $lowest_price );
	update_post_meta( $post_parent, '_min_variation_price', $lowest_price );
	update_post_meta( $post_parent, '_max_variation_price', $highest_price );
	update_post_meta( $post_parent, '_min_variation_regular_price', $lowest_regular_price );
	update_post_meta( $post_parent, '_max_variation_regular_price', $highest_regular_price );
	update_post_meta( $post_parent, '_min_variation_sale_price', $lowest_sale_price );
	update_post_meta( $post_parent, '_max_variation_sale_price', $highest_sale_price );

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