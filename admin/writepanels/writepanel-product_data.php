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

require_once('writepanel-product-type-variable.php');

/**
 * Product data box
 * 
 * Displays the product data box, tabbed, with several panels covering price, stock etc
 */
function woocommerce_product_data_box() {
	global $post, $wpdb, $thepostid, $woocommerce;
	add_action('admin_footer', 'woocommerce_meta_scripts');
	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
	
	$thepostid = $post->ID;
	
	$product_custom_fields = get_post_custom( $thepostid );
	?>
	<div class="panel-wrap product_data">
	
		<ul class="product_data_tabs tabs" style="display:none;">
			
			<li class="active show_if_simple show_if_variable show_if_external"><a href="#general_product_data"><?php _e('General', 'woothemes'); ?></a></li>
			
			<li class="tax_tab show_if_simple show_if_variable"><a href="#tax_product_data"><?php _e('Tax', 'woothemes'); ?></a></li>
			
			<?php if (get_option('woocommerce_manage_stock')=='yes') : ?><li class="inventory_tab show_if_simple show_if_variable show_if_grouped"><a href="#inventory_product_data"><?php _e('Inventory', 'woothemes'); ?></a></li><?php endif; ?>
			
			<li class="upsells_and_crosssells_tab"><a href="#upsells_and_crosssells_product_data"><?php _e('Up-sells &amp; Cross-sells', 'woothemes'); ?></a></li>
			
			<li class="attributes_tab"><a href="#woocommerce_attributes"><?php _e('Attributes', 'woothemes'); ?></a></li>
			
			<li class="grouping_tab show_if_simple"><a href="#grouping_product_data"><?php _e('Grouping', 'woothemes'); ?></a></li>
			
			<li class="downloads_tab show_if_downloadable"><a href="#downloadable_product_data"><?php _e('Downloads', 'woothemes'); ?></a></li>
			
			<?php do_action('woocommerce_product_write_panel_tabs'); ?>

		</ul>
		<div id="general_product_data" class="panel woocommerce_options_panel"><?php
						
			echo '<div class="options_group">';
			
				// SKU
				if( get_option('woocommerce_enable_sku', true) !== 'no' ) :
					woocommerce_wp_text_input( array( 'id' => 'sku', 'label' => __('SKU', 'woothemes'), 'placeholder' => $post->ID ) );
				else:
					echo '<input type="hidden" name="sku" value="'.get_post_meta($thepostid, 'sku', true).'" />';
				endif;
				
				do_action('woocommerce_product_options_sku');
			
			echo '</div>';
			
			echo '<div class="options_group show_if_external">';
				// External URL
				woocommerce_wp_text_input( array( 'id' => 'product_url', 'label' => __('Product URL', 'woothemes'), 'placeholder' => 'http://', 'description' => __('Enter the external URL to the product.', 'woothemes') ) );
			
			echo '</div>';
				
			echo '<div class="options_group pricing show_if_simple show_if_external">';
			
				// Price
				woocommerce_wp_text_input( array( 'id' => 'regular_price', 'label' => __('Regular Price', 'woothemes') . ' ('.get_woocommerce_currency_symbol().')' ) );
				
				// Special Price
				woocommerce_wp_text_input( array( 'id' => 'sale_price', 'label' => __('Sale Price', 'woothemes') . ' ('.get_woocommerce_currency_symbol().')' ) );
						
				// Special Price date range
				$field = array( 'id' => 'sale_price_dates', 'label' => __('Sale Price Dates', 'woothemes') );
				
				$sale_price_dates_from = get_post_meta($thepostid, 'sale_price_dates_from', true);
				$sale_price_dates_to = get_post_meta($thepostid, 'sale_price_dates_to', true);
				
				echo '	<p class="form-field sale_price_dates_fields">
							<label for="'.$field['id'].'_from">'.$field['label'].'</label>
							<input type="text" class="short" name="'.$field['id'].'_from" id="'.$field['id'].'_from" value="';
				if ($sale_price_dates_from) echo date('Y-m-d', $sale_price_dates_from);
				echo '" placeholder="' . __('From&hellip;', 'woothemes') . '" maxlength="10" />
							<input type="text" class="short" name="'.$field['id'].'_to" id="'.$field['id'].'_to" value="';
				if ($sale_price_dates_to) echo date('Y-m-d', $sale_price_dates_to);
				echo '" placeholder="' . __('To&hellip;', 'woothemes') . '" maxlength="10" />
							<span class="description">' . __('Date format', 'woothemes') . ': <code>YYYY-MM-DD</code></span>
						</p>';
						
				do_action('woocommerce_product_options_pricing');
					
			echo '</div>';
			
			echo '<div class="options_group">';
			
				// Weight
				if( get_option('woocommerce_enable_weight', true) !== 'no' ) :
					woocommerce_wp_text_input( array( 'id' => 'weight', 'label' => __('Weight', 'woothemes') . ' ('.get_option('woocommerce_weight_unit').')', 'placeholder' => '0.00' ) );
				else:
					echo '<input type="hidden" name="weight" value="'.get_post_meta($thepostid, 'weight', true).'" />';
				endif;
				
				// Size fields
				if( get_option('woocommerce_enable_dimensions', true) !== 'no' ) :
					?><p class="form-field dimensions_field">
						<label for"product_length"><?php echo __('Dimensions', 'woothemes') . ' ('.get_option('woocommerce_dimension_unit').')'; ?></label>
						<input id="product_length" placeholder="<?php _e('Length', 'woothemes'); ?>" class="input-text sized" size="6" type="text" name="length" value="<?php echo get_post_meta( $thepostid, 'length', true ); ?>" />
						<input placeholder="<?php _e('Width', 'woothemes'); ?>" class="input-text sized" size="6" type="text" name="width" value="<?php echo get_post_meta( $thepostid, 'width', true ); ?>" />
						<input placeholder="<?php _e('Height', 'woothemes'); ?>" class="input-text sized" size="6" type="text" name="height" value="<?php echo get_post_meta( $thepostid, 'height', true ); ?>" />
					</p><?php
				else:
					echo '<input type="hidden" name="length" value="'.get_post_meta($thepostid, 'length', true).'" />';
					echo '<input type="hidden" name="width" value="'.get_post_meta($thepostid, 'width', true).'" />';
					echo '<input type="hidden" name="height" value="'.get_post_meta($thepostid, 'height', true).'" />';
				endif;
				
				do_action('woocommerce_product_options_dimensions');
			
			echo '</div>';
			
			do_action('woocommerce_product_options_general_product_data');

			?>
		</div>
		<div id="tax_product_data" class="panel woocommerce_options_panel">
			
			<?php 
		
			// Tax
			woocommerce_wp_select( array( 'id' => 'tax_status', 'label' => __('Tax Status', 'woothemes'), 'options' => array(
				'taxable' => __('Taxable', 'woothemes'),
				'shipping' => __('Shipping only', 'woothemes'),
				'none' => __('None', 'woothemes')			
			) ) );
			
			$_tax = new woocommerce_tax();
			$tax_classes = $_tax->get_tax_classes();
			$classes_options = array();
			$classes_options[''] = __('Standard', 'woothemes');
    		if ($tax_classes) foreach ($tax_classes as $class) :
    			$classes_options[sanitize_title($class)] = $class;
    		endforeach;

			woocommerce_wp_select( array( 'id' => 'tax_class', 'label' => __('Tax Class', 'woothemes'), 'options' => $classes_options ) );
			
			do_action('woocommerce_product_options_tax');
			?>
		</div>
		<?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
		<div id="inventory_product_data" class="panel woocommerce_options_panel">
			
			<?php
			// manage stock
			woocommerce_wp_checkbox( array( 'id' => 'manage_stock', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __('Manage stock?', 'woothemes') ) );
			
			// Stock status
			woocommerce_wp_select( array( 'id' => 'stock_status', 'label' => __('Stock status', 'woothemes'), 'options' => array(
				'instock' => __('In stock', 'woothemes'),
				'outofstock' => __('Out of stock', 'woothemes')
			) ) );
			
			do_action('woocommerce_product_options_stock');
			
			echo '<div class="stock_fields show_if_simple show_if_variable">';
			
			// Stock
			woocommerce_wp_text_input( array( 'id' => 'stock', 'label' => __('Stock Qty', 'woothemes') ) );

			// Backorders?
			woocommerce_wp_select( array( 'id' => 'backorders', 'label' => __('Allow Backorders?', 'woothemes'), 'options' => array(
				'no' => __('Do not allow', 'woothemes'),
				'notify' => __('Allow, but notify customer', 'woothemes'),
				'yes' => __('Allow', 'woothemes')
			) ) );
			
			do_action('woocommerce_product_options_stock_fields');
			
			echo '</div>';
			?>			
			
		</div>
		<?php endif; ?>
		<div id="woocommerce_attributes" class="panel">
		
			<div class="woocommerce_attributes_wrapper">
				<table cellpadding="0" cellspacing="0" class="woocommerce_attributes">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th width="180"><?php _e('Attribute Name', 'woothemes'); ?></th>
							<th><?php _e('Value(s)', 'woothemes'); ?>&nbsp;<a class="tips" tip="<?php _e('Add multiple attributes for text attributes by pipe (|) separating values.', 'woothemes'); ?>" href="#">[?]</a></th>
							<th class="center" width="1%"><?php _e('Visible?', 'woothemes'); ?>&nbsp;<a class="tips" tip="<?php _e('Enable this to show the attribute on the product page.', 'woothemes'); ?>" href="#">[?]</a></th>
							<th class="center enable_variation show_if_variable" width="1%"><?php _e('Variation?', 'woothemes'); ?>&nbsp;<a class="tips" tip="<?php _e('Enable to use this attribute for variations.', 'woothemes'); ?>" href="#">[?]</a></th>
							<th class="center" width="1%"><?php _e('Remove', 'woothemes'); ?></th>
						</tr>
					</thead>
					<tbody id="attributes_list">	
						<?php
							$attribute_taxonomies = $woocommerce->get_attribute_taxonomies();	// Array of defined attribute taxonomies
							$attributes = maybe_unserialize( get_post_meta($thepostid, 'product_attributes', true) );	// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
														
							$i = -1;
							
							// Taxonomies
							if ( $attribute_taxonomies ) :
						    	foreach ($attribute_taxonomies as $tax) : $i++;
						    		
						    		// Get name of taxonomy we're now outputting (pa_xxx)
						    		$attribute_taxonomy_name = $woocommerce->attribute_taxonomy_name($tax->attribute_name);
						    		
						    		// Ensure it exists
						    		if (!taxonomy_exists($attribute_taxonomy_name)) continue;		    	
						    		
						    		// Get product data values for current taxonomy - this contains ordering and visibility data	
						    		if (isset($attributes[$attribute_taxonomy_name])) $attribute = $attributes[$attribute_taxonomy_name];
						    		
						    		$position = (isset($attribute['position'])) ? $attribute['position'] : 0;
						    		
						    		// Get terms of this taxonomy associated with current product
						    		$post_terms = wp_get_post_terms( $thepostid, $attribute_taxonomy_name );
						    		
						    		// Any set?
						    		$has_terms = (is_wp_error($post_terms) || !$post_terms || sizeof($post_terms)==0) ? 0 : 1;
						    		
						    		?><tr class="taxonomy <?php echo $attribute_taxonomy_name; ?>" rel="<?php echo $position; ?>" <?php if (!$has_terms) echo 'style="display:none"'; ?>>
						    			<td class="handle"></td>
										<td class="name">
											<?php echo ($tax->attribute_label) ? $tax->attribute_label : $tax->attribute_name; ?> 
											<input type="hidden" name="attribute_names[<?php echo $i; ?>]" value="<?php echo esc_attr( $attribute_taxonomy_name ); ?>" />
											<input type="hidden" name="attribute_position[<?php echo $i; ?>]" class="attribute_position" value="<?php echo esc_attr( $position ); ?>" />
											<input type="hidden" name="attribute_is_taxonomy[<?php echo $i; ?>]" value="1" />
										</td>
										<td class="values">
										<?php if ($tax->attribute_type=="select") : ?>
											<select multiple="multiple" class="multiselect" name="attribute_values[<?php echo $i; ?>][]">
												<?php
					        					$all_terms = get_terms( $attribute_taxonomy_name, 'orderby=name&hide_empty=0' );
				        						if ($all_terms) :
					        						foreach ($all_terms as $term) :
					        							$has_term = ( has_term( $term->slug, $attribute_taxonomy_name, $thepostid ) ) ? 1 : 0;
					        							echo '<option value="'.$term->slug.'" '.selected($has_term, 1, false).'>'.$term->name.'</option>';
													endforeach;
												endif;
												?>			
											</select>
										<?php elseif ($tax->attribute_type=="text") : ?>
											<input type="text" name="attribute_values[<?php echo $i; ?>]" value="<?php 
												
												// Text attributes should list terms pipe separated
												if ($post_terms) :
													$values = array();
													foreach ($post_terms as $term) :
														$values[] = $term->name;
													endforeach;
													echo implode('|', $values);
												endif;
												
											?>" placeholder="<?php _e('Pipe separate terms', 'woothemes'); ?>" />
										<?php endif; ?>
										</td>
										<td class="center"><input type="checkbox" <?php if (isset($attribute['is_visible'])) checked($attribute['is_visible'], 1); ?> name="attribute_visibility[<?php echo $i; ?>]" value="1" /></td>
										<td class="center enable_variation show_if_variable"><input type="checkbox" <?php if (isset($attribute['is_variation'])) checked($attribute['is_variation'], 1); ?> name="attribute_variation[<?php echo $i; ?>]" value="1" /></td>
										<td class="center"><button type="button" class="hide_row button">&times;</button></td>
									</tr><?php
						    	endforeach;
						    endif;
							
							// Custom Attributes
							if ($attributes && sizeof($attributes)>0) foreach ($attributes as $attribute) : 
								if ($attribute['is_taxonomy']) continue;
								
								$i++; 

					    		$position = (isset($attribute['position'])) ? $attribute['position'] : 0;
								
								?><tr rel="<?php if (isset($attribute['position'])) echo $attribute['position']; else echo '0'; ?>">
									<td class="handle"></td>
									<td>
										<input type="text" name="attribute_names[<?php echo $i; ?>]" value="<?php echo esc_attr( $attribute['name'] ); ?>" />
										<input type="hidden" name="attribute_position[<?php echo $i; ?>]" class="attribute_position" value="<?php echo esc_attr( $position ); ?>" />
										<input type="hidden" name="attribute_is_taxonomy[<?php echo $i; ?>]" value="0" />
									</td>
									<td><input type="text" name="attribute_values[<?php echo $i; ?>]" value="<?php echo esc_attr( $attribute['value'] ); ?>" /></td>
									<td class="center"><input type="checkbox" <?php checked($attribute['is_visible'], 1); ?> name="attribute_visibility[<?php echo $i; ?>]" value="1" /></td>
									<td class="center enable_variation show_if_variable"><input type="checkbox" <?php checked($attribute['is_variation'], 1); ?> name="attribute_variation[<?php echo $i; ?>]" value="1" /></td>
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
				    		$attribute_taxonomy_name = $woocommerce->attribute_taxonomy_name($tax->attribute_name);
				    		$label = ($tax->attribute_label) ? $tax->attribute_label : $tax->attribute_name;
				    		echo '<option value="'.$attribute_taxonomy_name.'">'.$label.'</option>';
				    	endforeach;
				    endif;
				?>
			</select>
			<div class="clear"></div>
		</div>	
		<div id="upsells_and_crosssells_product_data" class="panel woocommerce_options_panel">
			<div class="multi_select_products_wrapper"><h4><?php _e('Products', 'woothemes'); ?></h4>
				<ul class="multi_select_products multi_select_products_source">
					<li class="product_search"><input type="search" rel="upsell_ids" name="product_search" id="product_search" placeholder="<?php _e('Search for product', 'woothemes'); ?>" /><div class="clear"></div></li>
				</ul>
			</div>
			<div class="multi_select_products_wrapper multi_select_products_wrapper-alt">
				
				<h4><?php _e('Up-Sells', 'woothemes'); ?></h4>
				<?php _e('Up-sells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woothemes'); ?>
				<ul class="multi_select_products multi_select_products_target_upsell">
					<?php
						$upsell_ids = get_post_meta($thepostid, 'upsell_ids', true);
						if (!$upsell_ids) $upsell_ids = array(0);
						woocommerce_product_selection_list_remove($upsell_ids, 'upsell_ids');
					?>
				</ul>
				
				<h4><?php _e('Cross-Sells', 'woothemes'); ?></h4>
				<?php _e('Cross-sells are products which you promote in the cart, based on the current product.', 'woothemes'); ?>
				<ul class="multi_select_products multi_select_products_target_crosssell">
					<?php
					$crosssell_ids = get_post_meta($thepostid, 'crosssell_ids', true);
					if (!$crosssell_ids) $crosssell_ids = array(0);
					woocommerce_product_selection_list_remove($crosssell_ids, 'crosssell_ids');
					?>
				</ul>
				
			</div>
			<div class="clear"></div>
					
		</div>
		<div id="grouping_product_data" class="panel woocommerce_options_panel">
			<?php
			echo '<div class="options_group grouping">';
			
				// List Grouped products
				$post_parents = array();
				$post_parents[''] = __('Choose a grouped product&hellip;', 'woothemes');
	
				$posts_in = array_unique((array) get_objects_in_term( get_term_by( 'slug', 'grouped', 'product_type' )->term_id, 'product_type' ));
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
						
						$post_parents[$product->ID] = $product->post_title;
				
					endforeach; endif; 
				endif;
				
				woocommerce_wp_select( array( 'id' => 'parent_id', 'label' => __('Grouping', 'woothemes'), 'value' => $post->post_parent, 'options' => $post_parents ) );
				
				// Ordering
				woocommerce_wp_text_input( array( 'id' => 'menu_order', 'label' => _x('Sort Order', 'ordering', 'woothemes'), 'value' => $post->menu_order ) );
				
				do_action('woocommerce_product_options_grouping');
			
			echo '</div>';
			?>
		</div>
		<div id="downloadable_product_data" class="panel woocommerce_options_panel">
			<?php
	
				// File URL
				$file_path = get_post_meta($post->ID, 'file_path', true);
				$field = array( 'id' => 'file_path', 'label' => __('File path', 'woothemes') );
				echo '<p class="form-field"><label for="'.$field['id'].'">'.$field['label'].':</label>
					<input type="text" class="short file_path" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$file_path.'" placeholder="'.__('File path/URL', 'woothemes').'" />
					<input type="button"  class="upload_file_button button" value="'.__('Upload a file', 'woothemes').'" />
				</p>';
					
				// Download Limit
				$download_limit = get_post_meta($post->ID, 'download_limit', true);
				$field = array( 'id' => 'download_limit', 'label' => __('Download Limit', 'woothemes') );
				echo '<p class="form-field">
					<label for="'.$field['id'].'">'.$field['label'].':</label>
					<input type="text" class="short" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$download_limit.'" placeholder="'.__('Unlimited', 'woothemes').'" /> <span class="description">' . __('Leave blank for unlimited re-downloads.', 'woothemes') . '</span></p>';
	
			?>
		</div>
		
		<?php do_action('woocommerce_product_write_panels'); ?>
		
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
	global $wpdb, $woocommerce;

	$woocommerce_errors = array();
		
	// Update post meta
	update_post_meta( $post_id, 'regular_price', stripslashes( $_POST['regular_price'] ) );
	update_post_meta( $post_id, 'sale_price', stripslashes( $_POST['sale_price'] ) );
	update_post_meta( $post_id, 'weight', stripslashes( $_POST['weight'] ) );
	update_post_meta( $post_id, 'length', stripslashes( $_POST['length'] ) );
	update_post_meta( $post_id, 'width', stripslashes( $_POST['width'] ) );
	update_post_meta( $post_id, 'height', stripslashes( $_POST['height'] ) );
	update_post_meta( $post_id, 'tax_status', stripslashes( $_POST['tax_status'] ) );
	update_post_meta( $post_id, 'tax_class', stripslashes( $_POST['tax_class'] ) );
	update_post_meta( $post_id, 'stock_status', stripslashes( $_POST['stock_status'] ) );
	update_post_meta( $post_id, 'visibility', stripslashes( $_POST['visibility'] ) );
	if (isset($_POST['featured'])) update_post_meta( $post_id, 'featured', 'yes' ); else update_post_meta( $post_id, 'featured', 'no' );
		
	// Unique SKU 
	$sku = get_post_meta($post_id, 'sku', true);
	$new_sku = stripslashes( $_POST['sku'] );
	if ($new_sku!==$sku) :
		if ($new_sku && !empty($new_sku)) :
			if (
				$wpdb->get_var($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE meta_key='sku' AND meta_value='%s';", $new_sku)) || 
				$wpdb->get_var($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID='%s' AND ID!='%s' AND post_type='product';", $new_sku, $post_id))
				) :
				$woocommerce_errors[] = __('Product SKU must be unique.', 'woothemes');
			else :
				update_post_meta( $post_id, 'sku', $new_sku );
			endif;
		else :
			update_post_meta( $post_id, 'sku', '' );
		endif;
	endif;
		
	// Save Attributes
	$attributes = array();
	
	if (isset($_POST['attribute_names'])) :
		 $attribute_names = $_POST['attribute_names'];
		 $attribute_values = $_POST['attribute_values'];
		 if (isset($_POST['attribute_visibility'])) $attribute_visibility = $_POST['attribute_visibility'];
		 if (isset($_POST['attribute_variation'])) $attribute_variation = $_POST['attribute_variation'];
		 $attribute_is_taxonomy = $_POST['attribute_is_taxonomy'];
		 $attribute_position = $_POST['attribute_position'];

		 for ($i=0; $i<sizeof($attribute_names); $i++) :
		 	if (!($attribute_names[$i])) continue;
		 	
		 	$is_visible = (isset($attribute_visibility[$i])) ? 1 : 0;
		 	$is_variation = (isset($attribute_variation[$i])) ? 1 : 0;
		 	
		 	$is_taxonomy = ($attribute_is_taxonomy[$i]) ? 1 : 0;
		 	
		 	if ($is_taxonomy) :
		 		// Format values
		 		if (is_array($attribute_values[$i])) :
			 		$values = array_map('htmlspecialchars', array_map('stripslashes', $attribute_values[$i]));
			 	else :
			 		$values = htmlspecialchars(stripslashes($attribute_values[$i]));
			 		// Text based, separate by pipe
			 		$values = explode('|', $values);
			 		$values = array_map('trim', $values);
			 	endif;
			 	
			 	// Remove empty items in the array
			 	$values = array_filter( $values );
		 	
		 		// Update post terms
		 		if (taxonomy_exists( $attribute_names[$i] )) :
		 			wp_set_object_terms( $post_id, $values, $attribute_names[$i] );
		 		endif;

		 		if ($values) :
			 		// Add attribute to array, but don't set values
			 		$attributes[ sanitize_title( $attribute_names[$i] ) ] = array(
				 		'name' 			=> htmlspecialchars(stripslashes($attribute_names[$i])), 
				 		'value' 		=> '',
				 		'position' 		=> $attribute_position[$i],
				 		'is_visible' 	=> $is_visible,
				 		'is_variation' 	=> $is_variation,
				 		'is_taxonomy' 	=> $is_taxonomy
				 	);
			 	endif;
		 	else :
		 		// Format values
		 		$values = htmlspecialchars(stripslashes($attribute_values[$i]));
		 		// Text based, separate by pipe
		 		$values = explode('|', $values);
		 		$values = array_map('trim', $values);
		 		$values = implode('|', $values);
		 		
		 		// Custom attribute - Add attribute to array and set the values
			 	$attributes[ sanitize_title( $attribute_names[$i] ) ] = array(
			 		'name' 			=> htmlspecialchars(stripslashes($attribute_names[$i])), 
			 		'value' 		=> $values,
			 		'position' 		=> $attribute_position[$i],
			 		'is_visible' 	=> $is_visible,
			 		'is_variation' 	=> $is_variation,
			 		'is_taxonomy' 	=> $is_taxonomy
			 	);
		 	endif;
		 	
		 endfor; 
	endif;	
	
	if (!function_exists('attributes_cmp')) {
		function attributes_cmp($a, $b) {
		    if ($a['position'] == $b['position']) return 0;
		    return ($a['position'] < $b['position']) ? -1 : 1;
		}
	}
	uasort($attributes, 'attributes_cmp');
	
	update_post_meta( $post_id, 'product_attributes', $attributes );

	// Product type + Downloadable/Virtual
	$product_type = sanitize_title( stripslashes( $_POST['product-type'] ) );
	$is_downloadable = (isset($_POST['downloadable'])) ? 'yes' : 'no';
	$is_virtual = (isset($_POST['virtual'])) ? 'yes' : 'no';
	
	if( !$product_type ) $product_type = 'simple';
	
	wp_set_object_terms($post_id, $product_type, 'product_type');
	update_post_meta( $post_id, 'downloadable', $is_downloadable );
	update_post_meta( $post_id, 'virtual', $is_virtual );

	// Sales and prices
	if ($product_type=='simple' || $product_type=='external') :
		
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
		if ($_POST['sale_price'] && $date_to == '' && $date_from == '') :
			update_post_meta( $post_id, 'price', stripslashes($_POST['sale_price']) );
		else :
			update_post_meta( $post_id, 'price', stripslashes($_POST['regular_price']) );
		endif;	

		if ($date_from && strtotime($date_from) < strtotime('NOW')) :
			update_post_meta( $post_id, 'price', stripslashes($_POST['sale_price']) );
		endif;
		
		if ($date_to && strtotime($date_to) < strtotime('NOW')) :
			update_post_meta( $post_id, 'price', stripslashes($_POST['regular_price']) );
			update_post_meta( $post_id, 'sale_price_dates_from', '');
			update_post_meta( $post_id, 'sale_price_dates_to', '');
		endif;
		
	else :
		
		update_post_meta( $post_id, 'regular_price', '' );
		update_post_meta( $post_id, 'sale_price', '' );
		update_post_meta( $post_id, 'sale_price_dates_from', '' );	
		update_post_meta( $post_id, 'sale_price_dates_to', '' );
		update_post_meta( $post_id, 'price', '' );
		
	endif;
	
	// Update parent if grouped so price sorting works and stays in sync with the cheapest child
	if ($post->post_parent>0 || $product_type=='grouped') :
		$post_parent = ($post->post_parent>0) ? $post->post_parent : $post_id;
		
		$children_by_price = get_posts( array(
			'post_parent' 	=> $post_parent,
			'orderby' 	=> 'meta_value_num',
			'order'		=> 'asc',
			'meta_key'	=> 'price',
			'posts_per_page' => 1,
			'post_type' 	=> 'product',
			'fields' 		=> 'ids'
		));
		if ($children_by_price) :
			foreach ($children_by_price as $child) :
				$child_price = get_post_meta($child, 'price', true);
				update_post_meta( $post_parent, 'price', $child_price );
			endforeach;
		endif;
	endif;
	
	// Stock Data
	if (get_option('woocommerce_manage_stock')=='yes') :
		// Manage Stock Checkbox
		if ($product_type!=='grouped' && isset($_POST['manage_stock']) && $_POST['manage_stock']) :

			update_post_meta( $post_id, 'stock', $_POST['stock'] );
			update_post_meta( $post_id, 'manage_stock', 'yes' );
			update_post_meta( $post_id, 'backorders', stripslashes( $_POST['backorders'] ) );
			
			if ($product_type!=='variable' && $_POST['backorders']=='no' && $_POST['stock']<1) :
				update_post_meta( $post_id, 'stock_status', 'outofstock' );
			endif;
			
		elseif ($product_type!=='external') :
			
			update_post_meta( $post_id, 'stock', '0' );
			update_post_meta( $post_id, 'manage_stock', 'no' );
			update_post_meta( $post_id, 'backorders', 'no' );
		
		else :
		
			update_post_meta( $post_id, 'stock_status', 'instock' );
			update_post_meta( $post_id, 'stock', '0' );
			update_post_meta( $post_id, 'manage_stock', 'no' );
			update_post_meta( $post_id, 'backorders', 'no' );
				
		endif;
	endif;
	
	// Upsells
	if (isset($_POST['upsell_ids'])) :
		$upsells = array();
		$ids = $_POST['upsell_ids'];
		foreach ($ids as $id) :
			if ($id && $id>0) $upsells[] = $id;
		endforeach;
		update_post_meta( $post_id, 'upsell_ids', $upsells );
	else :
		delete_post_meta( $post_id, 'upsell_ids' );
	endif;
	
	// Cross sells
	if (isset($_POST['crosssell_ids'])) :
		$crosssells = array();
		$ids = $_POST['crosssell_ids'];
		foreach ($ids as $id) :
			if ($id && $id>0) $crosssells[] = $id;
		endforeach;
		update_post_meta( $post_id, 'crosssell_ids', $crosssells );
	else :
		delete_post_meta( $post_id, 'crosssell_ids' );
	endif;
	
	// Downloadable options
	if ($is_downloadable=='yes') :
		
		if (isset($_POST['file_path']) && $_POST['file_path']) update_post_meta( $post_id, 'file_path', esc_attr($_POST['file_path']) );
		if (isset($_POST['download_limit'])) update_post_meta( $post_id, 'download_limit', esc_attr($_POST['download_limit']) );
		
	endif;
	
	// Product url
	if ($product_type=='external') :
		
		if (isset($_POST['product_url']) && $_POST['product_url']) update_post_meta( $post_id, 'product_url', esc_attr($_POST['product_url']) );
		
	endif;
			
	// Do action for product type
	do_action( 'woocommerce_process_product_meta_' . $product_type, $post_id );
	
	// Clear cache/transients
	$woocommerce->clear_product_transients();
		
	// Save errors
	update_option('woocommerce_errors', $woocommerce_errors);
}

/**
* Outputs product list in selection boxes
**/
function woocommerce_product_selection_list_remove( $posts_to_display, $name ) {
	global $thepostid;
	
	$args = array(
		'post_type'	=> 'product',
		'post_status'     => 'publish',
		'numberposts' => -1,
		'orderby' => 'title',
		'order' => 'asc',
		'include' => $posts_to_display,
	);
	$related_posts = get_posts($args);
	$loop = 0;
	if ($related_posts) : foreach ($related_posts as $related_post) :
		
		if ($related_post->ID==$thepostid) continue;
		
		$SKU = get_post_meta($related_post->ID, 'sku', true);
		
		?><li rel="<?php echo $related_post->ID; ?>"><button type="button" name="Remove" class="button remove" title="Remove">&times;</button><strong><?php echo $related_post->post_title; ?></strong> &ndash; #<?php echo $related_post->ID; ?> <?php if (isset($SKU) && $SKU) echo 'SKU: '.$SKU; ?><input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $related_post->ID ); ?>" /></li><?php 

	endforeach; endif;
}

/**
* Procuct type panel
**/
function woocommerce_product_type_box() {
	
	global $post, $thepostid;
	
	$thepostid = $post->ID;

	echo '<div class="woocommerce_options_panel">';
	
	// Product Type
	if ($terms = wp_get_object_terms( $thepostid, 'product_type' )) $product_type = current($terms)->slug; else $product_type = 'simple';
	
	woocommerce_wp_select( array( 'id' => 'product-type', 'label' => __('Product Type', 'woothemes'), 'value' => $product_type, 'options' => apply_filters('product_type_selector', array(
		'simple' => __('Simple product', 'woothemes'),
		'grouped' => __('Grouped product', 'woothemes'),
		'external' => __('External/Affiliate product', 'woothemes')
	), $product_type) ) );
	
	// Visibility
	woocommerce_wp_select( array( 'id' => 'visibility', 'label' => __('Product visibility', 'woothemes'), 'options' => array(
		'visible' => __('Catalog &amp; Search', 'woothemes'),
		'catalog' => __('Catalog', 'woothemes'),
		'search' => __('Search', 'woothemes'),
		'hidden' => __('Hidden', 'woothemes')
	) ) );
	
	woocommerce_wp_checkbox( array( 'id' => 'virtual', 'wrapper_class' => 'show_if_simple', 'label' => __('Virtual', 'woothemes'), 'description' => __('Enable this option if a product is not shipped or there is no shipping cost', 'woothemes') ) );
	
	woocommerce_wp_checkbox( array( 'id' => 'downloadable', 'wrapper_class' => 'show_if_simple', 'label' => __('Downloadable', 'woothemes'), 'description' => __('Enable this option if access is given to a downloadable file upon purchase of a product', 'woothemes') ) );
	
	// Featured
	woocommerce_wp_checkbox( array( 'id' => 'featured', 'label' => __('Featured', 'woothemes'), 'description' => __('Enable this option to feature this product', 'woothemes') ) );
	
	echo '</div>';
			
}

/**
 * Change label for insert buttons
 */
add_filter( 'gettext', 'woocommerce_change_insert_into_post', null, 2 );

function woocommerce_change_insert_into_post( $translation, $original ) {
    if( !isset( $_REQUEST['from'] ) ) return $translation;

    if( $_REQUEST['from'] == 'wc01' && $original == 'Insert into Post' ) return __('Insert into URL field', 'woothemes' );

    return $translation;
}