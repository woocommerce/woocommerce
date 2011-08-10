<?php
/**
 * Product Data
 * 
 * Function for displaying the product data meta boxes
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */
 
/**
 * Product data box
 * 
 * Displays the product data box, tabbed, with several panels covering price, stock etc
 */
function woocommerce_product_data_box() {
	global $post, $wpdb, $thepostid;
	add_action('admin_footer', 'woocommerce_meta_scripts');
	
	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
	
	$data = (array) maybe_unserialize( get_post_meta($post->ID, 'product_data', true) );
	$featured = (string) get_post_meta( $post->ID, 'featured', true );
	$visibility = (string) get_post_meta( $post->ID, 'visibility', true);
	
	if (!isset($data['weight'])) $data['weight'] = '';
	if (!isset($data['regular_price'])) $data['regular_price'] = '';
	if (!isset($data['sale_price'])) $data['sale_price'] = '';
	
	$thepostid = $post->ID;
	
	?>
	<div class="panel-wrap product_data">
	
		<ul class="product_data_tabs tabs" style="display:none;">
			<li class="active"><a href="#general_product_data"><?php _e('General', 'woothemes'); ?></a></li>
			<li class="pricing_tab"><a href="#pricing_product_data"><?php _e('Pricing', 'woothemes'); ?></a></li>
			<?php if (get_option('woocommerce_manage_stock')=='yes') : ?><li class="inventory_tab"><a href="#inventory_product_data"><?php _e('Inventory', 'woothemes'); ?></a></li><?php endif; ?>
			<li><a href="#woocommerce_attributes"><?php _e('Attributes', 'woothemes'); ?></a></li>
			
			<?php do_action('product_write_panel_tabs'); ?>

		</ul>
		<div id="general_product_data" class="panel woocommerce_options_panel"><?php
			
			// Product Type
			if ($terms = wp_get_object_terms( $thepostid, 'product_type' )) $product_type = current($terms)->slug; else $product_type = 'simple';
			$field = array( 'id' => 'product-type', 'label' => __('Product Type', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].' <em class="req" title="'.__('Required', 'woothemes') . '">*</em></label><select id="'.$field['id'].'" name="'.$field['id'].'">';

			echo '<option value="simple" '; if ($product_type=='simple') echo 'selected="selected"'; echo '>'.__('Simple', 'woothemes').'</option>';
			
			do_action('product_type_selector', $product_type);

			echo '</select></p>';
			
			// List Grouped products
			$posts_in = (array) get_objects_in_term( get_term_by( 'slug', 'grouped', 'product_type' )->term_id, 'product_type' );
			$posts_in = array_unique($posts_in);
			
			$field = array( 'id' => 'parent_id', 'label' => __('Parent post', 'woothemes') );
			echo '<p class="form-field parent_id_field"><label for="'.$field['id'].'">'.$field['label'].'</label><select id="'.$field['id'].'" name="'.$field['id'].'"><option value="">'.__('Choose a grouped product&hellip;', 'woothemes').'</option>';

			if (sizeof($posts_in)>0) :
				$args = array(
					'post_type'	=> 'product',
					'post_status' => 'publish',
					'numberposts' => -1,
					'orderby' => 'title',
					'order' => 'asc',
					'post_parent' => 0,
					'include' => $posts_in,
				);
				$grouped_products = get_posts($args);
				$loop = 0;
				if ($grouped_products) : foreach ($grouped_products as $product) :
					
					if ($product->ID==$post->ID) continue;
					
					echo '<option value="'.$product->ID.'" ';
					if ($post->post_parent==$product->ID) echo 'selected="selected"';
					echo '>'.$product->post_title.'</option>';
			
				endforeach; endif; 
			endif;

			echo '</select></p>';
			
			// Ordering
			$menu_order = $post->menu_order;
			$field = array( 'id' => 'menu_order', 'label' => _x('Post Order', 'ordering', 'woothemes') );
			echo '<p class="form-field menu_order_field">
				<label for="'.$field['id'].'">'.$field['label'].':</label>
				<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$menu_order.'" /></p>';
			
			
			// Summary
			echo '<p class="form-field"><label for="excerpt">' . __('Summary', 'woothemes') . ':</label>
			<textarea name="excerpt" id="excerpt" placeholder="' . __('Add a summary for your product &ndash; this is a quick description to encourage users to view the product.', 'woothemes') . '">'.esc_html( $post->post_excerpt ).'</textarea></p>';
			
			// SKU
			$field = array( 'id' => 'sku', 'label' => __('SKU', 'woothemes') );
			$SKU = get_post_meta($thepostid, 'SKU', true);
			
			if( get_option('woocommerce_enable_sku', true) !== 'no' ) :
				echo '<p class="form-field">
					<label for="'.$field['id'].'">'.$field['label'].':</label>
					<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$SKU.'" /> <span class="description">' . __('Leave blank to use product ID', 'woothemes') . '</span></p>';
			else:
				echo '<input type="hidden" name="'.$field['id'].'" value="'.$SKU.'" />';
			endif;
			
			// Weight
			$field = array( 'id' => 'weight', 'label' => __('Weight', 'woothemes') . ' ('.get_option('woocommerce_weight_unit').'):' );
			 
			if( get_option('woocommerce_enable_weight', true) !== 'no' ) :
				echo '<p class="form-field">
					<label for="'.$field['id'].'">'.$field['label'].'</label>
					<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$data[$field['id']].'" placeholder="0.00" /></p>';
			else:
				echo '<input type="hidden" name="'.$field['id'].'" value="'.$data[$field['id']].'" />';
			endif;
			
			// Featured
			$field = array( 'id' => 'featured', 'label' => __('Featured?', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].'</label><select name="'.$field['id'].'">';
			echo '<option value="no" '; if (isset($featured) && $featured=='no') echo 'selected="selected"'; echo '>' . __('No', 'woothemes') . '</option>';
			echo '<option value="yes" '; if (isset($featured) && $featured=='yes') echo 'selected="selected"'; echo '>' . __('Yes', 'woothemes') . '</option>';
			echo '</select></p>';
			
			// Visibility
			$field = array( 'id' => 'visibility', 'label' => __('Visibility', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].':</label><select name="'.$field['id'].'">';
			echo '<option value="visible" '; if (isset($visibility) && $visibility=='visible') echo 'selected="selected"'; echo '>' . __('Catalog &amp; Search', 'woothemes') . '</option>';
			echo '<option value="catalog" '; if (isset($visibility) && $visibility=='catalog') echo 'selected="selected"'; echo '>' . __('Catalog', 'woothemes') . '</option>';
			echo '<option value="search" '; if (isset($visibility) && $visibility=='search') echo 'selected="selected"'; echo '>' . __('Search', 'woothemes') . '</option>';
			echo '<option value="hidden" '; if (isset($visibility) && $visibility=='hidden') echo 'selected="selected"'; echo '>' . __('Hidden', 'woothemes') . '</option>';
			echo '</select></p>';
			?>
		</div>
		<div id="pricing_product_data" class="panel woocommerce_options_panel">
			
			<?php 
			// Price
			$field = array( 'id' => 'regular_price', 'label' => __('Regular Price', 'woothemes') . ' ('.get_woocommerce_currency_symbol().'):' );
			echo '	<p class="form-field">
						<label for="'.$field['id'].'">'.$field['label'].'</label>
						<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$data[$field['id']].'" placeholder="0.00" /></p>';
			
			// Special Price
			$field = array( 'id' => 'sale_price', 'label' => __('Sale Price', 'woothemes') . ' ('.get_woocommerce_currency_symbol().'):' );
			echo '	<p class="form-field">
						<label for="'.$field['id'].'">'.$field['label'].'</label>
						<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$data[$field['id']].'" placeholder="0.00" /></p>';
					
			// Special Price date range
			$field = array( 'id' => 'sale_price_dates', 'label' => __('Sale Price Dates', 'woothemes') );
			
			$sale_price_dates_from = get_post_meta($thepostid, 'sale_price_dates_from', true);
			$sale_price_dates_to = get_post_meta($thepostid, 'sale_price_dates_to', true);
			
			echo '	<p class="form-field">
						<label for="'.$field['id'].'_from">'.$field['label'].':</label>
						<input type="text" class="short date-pick" name="'.$field['id'].'_from" id="'.$field['id'].'_from" value="';
			if ($sale_price_dates_from) echo date('Y-m-d', $sale_price_dates_from);
			echo '" placeholder="' . __('From&hellip;', 'woothemes') . '" maxlength="10" />
						<input type="text" class="short date-pick" name="'.$field['id'].'_to" id="'.$field['id'].'_to" value="';
			if ($sale_price_dates_to) echo date('Y-m-d', $sale_price_dates_to);
			echo '" placeholder="' . __('To&hellip;', 'woothemes') . '" maxlength="10" />
						<span class="description">' . __('Date format', 'woothemes') . ': <code>YYYY-MM-DD</code></span>
					</p>';
					
			// Tax
			$_tax = new woocommerce_tax();
			
			$field = array( 'id' => 'tax_status', 'label' => __('Tax Status', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].':</label><select name="'.$field['id'].'">';
			echo '<option value="taxable" '; if (isset($data[$field['id']]) && $data[$field['id']]=='taxable') echo 'selected="selected"'; echo '>' . __('Taxable', 'woothemes') . '</option>';
			echo '<option value="shipping" '; if (isset($data[$field['id']]) && $data[$field['id']]=='shipping') echo 'selected="selected"'; echo '>' . __('Shipping only', 'woothemes') . '</option>';
			echo '<option value="none" '; if (isset($data[$field['id']]) && $data[$field['id']]=='none') echo 'selected="selected"'; echo '>' . __('None', 'woothemes') . '</option>';
			echo '</select></p>';
			
			$field = array( 'id' => 'tax_class', 'label' => __('Tax Class', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].':</label><select name="'.$field['id'].'">';
			echo '<option value="" '; if (isset($data[$field['id']]) && $data[$field['id']]=='') echo 'selected="selected"'; echo '>'.__('Standard', 'woothemes').'</option>';
			$tax_classes = $_tax->get_tax_classes();
    		if ($tax_classes) foreach ($tax_classes as $class) :
    			echo '<option value="'.sanitize_title($class).'" '; if (isset($data[$field['id']]) && $data[$field['id']]==sanitize_title($class)) echo 'selected="selected"'; echo '>'.$class.'</option>';
    		endforeach;
			echo '</select></p>';
			?>
			
		</div>
		<?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
		<div id="inventory_product_data" class="panel woocommerce_options_panel">
			
			<?php
			// manage stock
			$field = array( 'id' => 'manage_stock', 'label' => __('Manage stock?', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].' <em class="req" title="' .__('Required', 'woothemes') . '">*</em></label><input type="checkbox" class="checkbox" name="'.$field['id'].'" id="'.$field['id'].'"';
			if (isset($data[$field['id']]) && $data[$field['id']]=='yes') echo 'checked="checked"';
			echo ' /></p>';
			
			// Stock status
			$field = array( 'id' => 'stock_status', 'label' => 'Stock status:' );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].' <em class="req" title="'.__('Required', 'woothemes') . '">*</em></label><select name="'.$field['id'].'">';
			echo '<option value="instock" '; if (isset($data[$field['id']]) && $data[$field['id']]=='instock') echo 'selected="selected"'; echo '>In stock</option>';
			echo '<option value="outofstock" '; if (isset($data[$field['id']]) && $data[$field['id']]=='outofstock') echo 'selected="selected"'; echo '>Out of stock</option>';
			echo '</select></p>';
			
			echo '<div class="stock_fields">';
			
			// Stock
			$field = array( 'id' => 'stock', 'label' => __('Stock Qty', 'woothemes') );
			echo '	<p class="form-field">
						<label for="'.$field['id'].'">'.$field['label'].': <em class="req" title="'.__('Required', 'woothemes') . '">*</em></label>
						<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="';
			
			$stock = get_post_meta($post->ID, 'stock', true);
    		if (!$stock) $stock = 0;
    		echo $stock;
						
			echo '" />
					</p>';

			// Backorders?
			$field = array( 'id' => 'backorders', 'label' => __('Allow Backorders?', 'woothemes') );
			echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].' <em class="req" title="'.__('Required', 'woothemes') . '">*</em></label><select name="'.$field['id'].'">';
			echo '<option value="no" '; if (isset($data[$field['id']]) && $data[$field['id']]=='no') echo 'selected="selected"'; echo '>' . __('Do not allow', 'woothemes') . '</option>';
			echo '<option value="notify" '; if (isset($data[$field['id']]) && $data[$field['id']]=='notify') echo 'selected="selected"'; echo '>' . __('Allow, but notify customer', 'woothemes') . '</option>';
			echo '<option value="yes" '; if (isset($data[$field['id']]) && $data[$field['id']]=='yes') echo 'selected="selected"'; echo '>' . __('Allow', 'woothemes') . '</option>';
			echo '</select></p>';
			
			echo '</div>';
			?>			
			
		</div>
		<?php endif; ?>
		<div id="woocommerce_attributes" class="panel">
		
			<div class="woocommerce_attributes_wrapper">
				<table cellpadding="0" cellspacing="0" class="woocommerce_attributes">
					<thead>
						<tr>
							<th class="center" width="60"><?php _e('Order', 'woothemes'); ?></th>
							<th width="180"><?php _e('Name', 'woothemes'); ?></th>
							<th><?php _e('Value', 'woothemes'); ?></th>
							<th class="center" width="1%"><?php _e('Visible?', 'woothemes'); ?></th>
							<th class="center" width="1%"><?php _e('Variation?', 'woothemes'); ?></th>
							<th class="center" width="1%"><?php _e('Remove', 'woothemes'); ?></th>
						</tr>
					</thead>
					<tbody id="attributes_list">	
						<?php
							$attribute_taxonomies = woocommerce::$attribute_taxonomies;
							$attributes = maybe_unserialize( get_post_meta($post->ID, 'product_attributes', true) );

							$i = -1;
							
							// Taxonomies
							if ( $attribute_taxonomies ) :
						    	foreach ($attribute_taxonomies as $tax) : $i++;
						    							    	
						    		$attribute_nicename = strtolower(sanitize_title($tax->attribute_name));
						    		if (isset($attributes[$attribute_nicename])) $attribute = $attributes[$attribute_nicename];
						    		if (isset($attribute['visible']) && $attribute['visible']=='yes') $checked = 'checked="checked"'; else $checked = '';
						    		if (isset($attribute['variation']) && $attribute['variation']=='yes') $checked2 = 'checked="checked"'; else $checked2 = '';
						    		
						    		$values = wp_get_post_terms( $thepostid, 'product_attribute_'.strtolower(sanitize_title($tax->attribute_name)) );
						    		$value = array();
						    		if (!is_wp_error($values) && $values) :
						    			foreach ($values as $v) :
						    				$value[] = $v->slug;
						    			endforeach;
						    		endif;
						    		
						    		?><tr class="taxonomy <?php echo strtolower(sanitize_title($tax->attribute_name)); ?>" rel="<?php if (isset($attribute['position'])) echo $attribute['position']; else echo '0'; ?>" <?php if (!$value || sizeof($value)==0) echo 'style="display:none"'; ?>>
										<td class="center">
											<button type="button" class="move_up button">&uarr;</button><button type="button" class="move_down button">&darr;</button>
											<input type="hidden" name="attribute_position[<?php echo $i; ?>]" class="attribute_position" value="<?php if (isset($attribute['position'])) echo $attribute['position']; else echo '0'; ?>" />
										</td>
										<td class="name">
											<?php echo $tax->attribute_name; ?> 
											<input type="hidden" name="attribute_names[<?php echo $i; ?>]" value="<?php echo $tax->attribute_name; ?>" />
											<input type="hidden" name="attribute_is_taxonomy[<?php echo $i; ?>]" value="1" />
										</td>
										<td>
										<?php if ($tax->attribute_type=="select" || $tax->attribute_type=="multiselect") : ?>
											<select <?php if ($tax->attribute_type=="multiselect") echo 'multiple="multiple" class="multiselect" name="attribute_values['.$i.'][]"'; else echo 'name="attribute_values['.$i.']"'; ?>>
												<?php if ($tax->attribute_type=="select") : ?><option value=""><?php _e('Choose an option&hellip;', 'woothemes'); ?></option><?php endif; ?>
												<?php
												if (taxonomy_exists('product_attribute_'.strtolower(sanitize_title($tax->attribute_name)))) :
					        						$terms = get_terms( 'product_attribute_'.strtolower(sanitize_title($tax->attribute_name)), 'orderby=name&hide_empty=0' );
					        						if ($terms) :
						        						foreach ($terms as $term) :
						        							echo '<option value="'.$term->slug.'" ';
						        							if (in_array($term->slug, $value)) echo 'selected="selected"';
						        							echo '>'.$term->name.'</option>';
														endforeach;
													endif;
												endif;
												?>			
											</select>
										<?php elseif ($tax->attribute_type=="text") : ?>
											<input type="text" name="attribute_values[<?php echo $i; ?>]" value="<?php if (isset($attribute['value'])) echo $attribute['value']; ?>" placeholder="<?php _e('Comma separate terms', 'woothemes'); ?>" />
										<?php endif; ?>
										</td>
										<td class="center"><input type="checkbox" <?php echo $checked; ?> name="attribute_visibility[<?php echo $i; ?>]" value="1" /></td>
										<td class="center"><input type="checkbox" <?php echo $checked2; ?> name="attribute_variation[<?php echo $i; ?>]" value="1" /></td>
										<td class="center"><button type="button" class="hide_row button">&times;</button></td>
									</tr><?php
						    	endforeach;
						    endif;
							
							// Attributes
							if ($attributes && sizeof($attributes)>0) foreach ($attributes as $attribute) : 
								if (isset($attribute['is_taxonomy']) && $attribute['is_taxonomy']=='yes') continue;
								
								$i++; 
								
								if (isset($attribute['visible']) && $attribute['visible']=='yes') $checked = 'checked="checked"'; else $checked = '';
								if (isset($attribute['variation']) && $attribute['variation']=='yes') $checked2 = 'checked="checked"'; else $checked2 = '';
								
								?><tr rel="<?php if (isset($attribute['position'])) echo $attribute['position']; else echo '0'; ?>">
									<td class="center">
										<button type="button" class="move_up button">&uarr;</button><button type="button" class="move_down button">&darr;</button>
										<input type="hidden" name="attribute_position[<?php echo $i; ?>]" class="attribute_position" value="<?php if (isset($attribute['position'])) echo $attribute['position']; else echo '0'; ?>" />
									</td>
									<td>
										<input type="text" name="attribute_names[<?php echo $i; ?>]" value="<?php echo $attribute['name']; ?>" />
										<input type="hidden" name="attribute_is_taxonomy[<?php echo $i; ?>]" value="0" />
									</td>
									<td><input type="text" name="attribute_values[<?php echo $i; ?>]" value="<?php echo $attribute['value']; ?>" /></td>
									<td class="center"><input type="checkbox" <?php echo $checked; ?> name="attribute_visibility[<?php echo $i; ?>]" value="1" /></td>
									<td class="center"><input type="checkbox" <?php echo $checked2; ?> name="attribute_variation[<?php echo $i; ?>]" value="1" /></td>
									<td class="center"><button type="button" class="remove_row button">&times;</button></td>
								</tr><?php
							endforeach;
						?>			
					</tbody>
				</table>
			</div>
			<button type="button" class="button button-primary add_attribute"><?php _e('Add', 'woothemes'); ?></button>
			<select name="attribute_taxonomy" class="attribute_taxonomy">
				<option value=""><?php _e('Custom product attribute', 'woothemes'); ?></option>
				<?php
					if ( $attribute_taxonomies ) :
				    	foreach ($attribute_taxonomies as $tax) :
				    		echo '<option value="'.strtolower(sanitize_title($tax->attribute_name)).'">'.$tax->attribute_name.'</option>';
				    	endforeach;
				    endif;
				?>
			</select>
			<div class="clear"></div>
		</div>	
		
		<?php do_action('product_write_panels'); ?>
		
	</div>
	<?php
}


/**
 * Product Data Save
 * 
 * Function for processing and storing all product data.
 */
add_action('woocommerce_process_product_meta', 'woocommerce_process_product_meta', 1, 2);

function woocommerce_process_product_meta( $post_id, $post ) {
	global $wpdb;

	$woocommerce_errors = array();
	
	// Get old data + attributes
	$data = (array) maybe_unserialize( get_post_meta($post_id, 'product_data', true) );
	$attributes = (array) maybe_unserialize( get_post_meta($post_id, 'product_attributes', true) );
	
	// Add/Replace data to array
	$data['regular_price']			= stripslashes( $_POST['regular_price'] );
	$data['sale_price'] 			= stripslashes( $_POST['sale_price'] );
	$data['weight'] 				= stripslashes( $_POST['weight'] );
	$data['tax_status'] 			= stripslashes( $_POST['tax_status'] );
	$data['tax_class'] 				= stripslashes( $_POST['tax_class'] );
	if (isset($_POST['stock_status'])) $data['stock_status'] = stripslashes( $_POST['stock_status'] );

	// Attributes
	
		$attributes = array();
		
		//var_dump($_POST['attribute_values']);
		
		if (isset($_POST['attribute_names'])) :
			 $attribute_names = $_POST['attribute_names'];
			 $attribute_values = $_POST['attribute_values'];
			 if (isset($_POST['attribute_visibility'])) $attribute_visibility = $_POST['attribute_visibility'];
			 if (isset($_POST['attribute_variation'])) $attribute_variation = $_POST['attribute_variation'];
			 $attribute_is_taxonomy = $_POST['attribute_is_taxonomy'];
			 $attribute_position = $_POST['attribute_position'];
	
			 for ($i=0; $i<sizeof($attribute_names); $i++) :
			 	if (!($attribute_names[$i])) continue;
			 	if (isset($attribute_visibility[$i])) $visible = 'yes'; else $visible = 'no';
			 	if (isset($attribute_variation[$i])) $variation = 'yes'; else $variation = 'no';
			 	if ($attribute_is_taxonomy[$i]) $is_taxonomy = 'yes'; else $is_taxonomy = 'no';
			 	
			 	if (is_array($attribute_values[$i])) :
			 		$attribute_values[$i] = array_map('htmlspecialchars', array_map('stripslashes', $attribute_values[$i]));
			 	else :
			 		$attribute_values[$i] = trim(htmlspecialchars(stripslashes($attribute_values[$i])));
			 	endif;
			 	
			 	if (empty($attribute_values[$i]) || ( is_array($attribute_values[$i]) && sizeof($attribute_values[$i])==0) ) :
			 		if ($is_taxonomy=='yes' && taxonomy_exists('product_attribute_'.strtolower(sanitize_title($attribute_names[$i])))) :
			 			wp_set_object_terms( $post_id, 0, 'product_attribute_'.strtolower(sanitize_title($attribute_names[$i])) );
			 		endif;
			 		continue;
			 	endif;
			 	
			 	$attributes[ strtolower(sanitize_title( $attribute_names[$i] )) ] = array(
			 		'name' => htmlspecialchars(stripslashes($attribute_names[$i])), 
			 		'value' => $attribute_values[$i],
			 		'position' => $attribute_position[$i],
			 		'visible' => $visible,
			 		'variation' => $variation,
			 		'is_taxonomy' => $is_taxonomy
			 	);
			 	
			 	if ($is_taxonomy=='yes') :
			 		// Update post terms
			 		$tax = $attribute_names[$i];
			 		$value = $attribute_values[$i];
			 		
			 		if (taxonomy_exists('product_attribute_'.strtolower(sanitize_title($tax)))) :
			 			
			 			wp_set_object_terms( $post_id, $value, 'product_attribute_'.strtolower(sanitize_title($tax)) );
			 			
			 		endif;
			 		
			 	endif;
			 	
			 endfor; 
		endif;	
		
		if (!function_exists('attributes_cmp')) {
			function attributes_cmp($a, $b) {
			    if ($a['position'] == $b['position']) {
			        return 0;
			    }
			    return ($a['position'] < $b['position']) ? -1 : 1;
			}
		}
		uasort($attributes, 'attributes_cmp');
	
		// Product type
		$product_type = sanitize_title( stripslashes( $_POST['product-type'] ) );
		if( !$product_type ) $product_type = 'simple';
		
		wp_set_object_terms($post_id, $product_type, 'product_type');
		
		// visibility
		$visibility = stripslashes( $_POST['visibility'] );
		update_post_meta( $post_id, 'visibility', $visibility );
		
		// Featured
		$featured = stripslashes( $_POST['featured'] );
		update_post_meta( $post_id, 'featured', $featured );
		
		// Unique SKU 
		$SKU = get_post_meta($post_id, 'SKU', true);
		$new_sku = stripslashes( $_POST['sku'] );
		if ($new_sku!==$SKU) :
			if ($new_sku && !empty($new_sku)) :
				if ($wpdb->get_var("SELECT * FROM $wpdb->postmeta WHERE meta_key='SKU' AND meta_value='".$new_sku."';") || $wpdb->get_var("SELECT * FROM $wpdb->posts WHERE ID='".$new_sku."' AND ID!=".$post_id.";")) :
					$woocommerce_errors[] = __('Product SKU must be unique.', 'woothemes');
				else :
					update_post_meta( $post_id, 'SKU', $new_sku );
				endif;
			else :
				update_post_meta( $post_id, 'SKU', '' );
			endif;
		endif;
		
		// Sales and prices
		
		if ($product_type!=='grouped') :
			
			$date_from = (isset($_POST['sale_price_dates_from'])) ? $_POST['sale_price_dates_from'] : '';
			$date_to = (isset($_POST['sale_price_dates_to'])) ? $_POST['sale_price_dates_to'] : '';
			
			// Dates
			if ($date_from) :
				update_post_meta( $post_id, 'sale_price_dates_from', strtotime($date_from) );
			else :
				update_post_meta( $post_id, 'sale_price_dates_from', '' );	
			endif;
			
			if ($date_to) :
				update_post_meta( $post_id, 'sale_price_dates_to', strtotime($date_to) );
			else :
				update_post_meta( $post_id, 'sale_price_dates_to', '' );	
			endif;
			
			if ($date_to && !$date_from) :
				update_post_meta( $post_id, 'sale_price_dates_from', strtotime('NOW') );
			endif;
	
			// Update price if on sale
			if ($data['sale_price'] && $date_to == '' && $date_from == '') :
				update_post_meta( $post_id, 'price', $data['sale_price'] );
			else :
				update_post_meta( $post_id, 'price', $data['regular_price'] );
			endif;	
	
			if ($date_from && strtotime($date_from) < strtotime('NOW')) :
				update_post_meta( $post_id, 'price', $data['sale_price'] );
			endif;
			
			if ($date_to && strtotime($date_to) < strtotime('NOW')) :
				update_post_meta( $post_id, 'price', $data['regular_price'] );
				update_post_meta( $post_id, 'sale_price_dates_from', '');
				update_post_meta( $post_id, 'sale_price_dates_to', '');
			endif;
		
		else :
			
			$data['sale_price'] = '';
			$data['regular_price'] = '';
			update_post_meta( $post_id, 'sale_price_dates_from', '' );	
			update_post_meta( $post_id, 'sale_price_dates_to', '' );
			update_post_meta( $post_id, 'price', '' );

		endif;
		
		// Stock Data
		
		if (get_option('woocommerce_manage_stock')=='yes') :
			// Manage Stock Checkbox
			if ($product_type!=='grouped' && isset($_POST['manage_stock']) && $_POST['manage_stock']) :

				update_post_meta( $post_id, 'stock', $_POST['stock'] );
				$data['manage_stock'] = 'yes';
				$data['backorders'] = stripslashes( $_POST['backorders'] );
				
			else :
				
				update_post_meta( $post_id, 'stock', '0' );
				$data['manage_stock'] = 'no';
				$data['backorders'] = 'no';
				
			endif;
		endif;
		
		// Apply filters to data
		$data = apply_filters( 'process_product_meta', $data, $post_id );
		
		// Apply filter to data for product type
		$data = apply_filters( 'filter_product_meta_' . $product_type, $data, $post_id );
		
		// Do action for product type
		do_action( 'process_product_meta_' . $product_type, $data, $post_id );
		
	// Save
	update_post_meta( $post_id, 'product_attributes', $attributes );
	update_post_meta( $post_id, 'product_data', $data );
	update_option('woocommerce_errors', $woocommerce_errors);
}