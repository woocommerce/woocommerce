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
	
	$thepostid = $post->ID;
	
	$product_custom_fields = get_post_custom( $thepostid );
	?>
	<div class="panel-wrap product_data">
	
		<ul class="product_data_tabs tabs" style="display:none;">
			<li class="active"><a href="#general_product_data"><?php _e('General', 'woothemes'); ?></a></li>
			<li class="tax_tab"><a href="#tax_product_data"><?php _e('Tax', 'woothemes'); ?></a></li>
			<?php if (get_option('woocommerce_manage_stock')=='yes') : ?><li class="inventory_tab"><a href="#inventory_product_data"><?php _e('Inventory', 'woothemes'); ?></a></li><?php endif; ?>
			<li><a href="#woocommerce_attributes"><?php _e('Attributes', 'woothemes'); ?></a></li>
			<li><a href="#upsell_product_data" title="<?php _e('Up-sells are products which you recommend instead of the currently viewed product, for example, products that are more profitable or better quality or more expensive.', 'woothemes'); ?>"><?php _e('Up-sells', 'woothemes'); ?></a></li>
			<li><a href="#crosssell_product_data" title="<?php _e('Cross-sells are products which you promote in the cart, based on the current product.', 'woothemes'); ?>"><?php _e('Cross-sells', 'woothemes'); ?></a></li>
			<?php do_action('product_write_panel_tabs'); ?>

		</ul>
		<div id="general_product_data" class="panel woocommerce_options_panel"><?php
			
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
			
			echo '</div>';
			
			echo '<div class="options_group">';
			
				// SKU
				if( get_option('woocommerce_enable_sku', true) !== 'no' ) :
					woocommerce_wp_text_input( array( 'id' => 'sku', 'label' => __('SKU', 'woothemes'), 'placeholder' => $post->ID ) );
				else:
					echo '<input type="hidden" name="sku" value="'.get_post_meta($thepostid, 'sku', true).'" />';
				endif;
			
			echo '</div>';
						
			echo '<div class="options_group pricing">';
			
				// Price
				woocommerce_wp_text_input( array( 'id' => 'regular_price', 'label' => __('Regular Price', 'woothemes') . ' ('.get_woocommerce_currency_symbol().'):', 'placeholder' => '0.00' ) );
				
				// Special Price
				woocommerce_wp_text_input( array( 'id' => 'sale_price', 'label' => __('Sale Price', 'woothemes') . ' ('.get_woocommerce_currency_symbol().'):', 'placeholder' => '0.00' ) );
						
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
					
			echo '</div>';
			
			echo '<div class="options_group">';
			
				// Weight
				if( get_option('woocommerce_enable_weight', true) !== 'no' ) :
					woocommerce_wp_text_input( array( 'id' => 'weight', 'label' => __('Weight', 'woothemes') . ' ('.get_option('woocommerce_weight_unit').')', 'placeholder' => '0.00' ) );
				else:
					echo '<input type="hidden" name="weight" value="'.get_post_meta($thepostid, 'weight', true).'" />';
				endif;
			
			echo '</div>';

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

			?>
		</div>
		<?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
		<div id="inventory_product_data" class="panel woocommerce_options_panel">
			
			<?php
			// manage stock
			woocommerce_wp_checkbox( array( 'id' => 'manage_stock', 'label' => __('Manage stock?', 'woothemes') ) );
			
			// Stock status
			woocommerce_wp_select( array( 'id' => 'stock_status', 'label' => __('Stock status', 'woothemes'), 'options' => array(
				'instock' => __('In stock', 'woothemes'),
				'outofstock' => __('Out of stock', 'woothemes')
			) ) );
			
			echo '<div class="stock_fields">';
			
			// Stock
			woocommerce_wp_text_input( array( 'id' => 'stock', 'label' => __('Stock Qty', 'woothemes') ) );

			// Backorders?
			woocommerce_wp_select( array( 'id' => 'backorders', 'label' => __('Allow Backorders?', 'woothemes'), 'options' => array(
				'no' => __('Do not allow', 'woothemes'),
				'notify' => __('Allow, but notify customer', 'woothemes'),
				'yes' => __('Allow', 'woothemes')
			) ) );
			
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
							$attribute_taxonomies = woocommerce::get_attribute_taxonomies();
							$attributes = maybe_unserialize( get_post_meta($post->ID, 'product_attributes', true) );

							$i = -1;
							
							// Taxonomies
							if ( $attribute_taxonomies ) :
						    	foreach ($attribute_taxonomies as $tax) : $i++;
						    							    	
						    		$attribute_nicename = sanitize_title($tax->attribute_name);
						    		if (isset($attributes[$attribute_nicename])) $attribute = $attributes[$attribute_nicename];
						    		if (isset($attribute['visible']) && $attribute['visible']=='yes') $checked = 'checked="checked"'; else $checked = '';
						    		if (isset($attribute['variation']) && $attribute['variation']=='yes') $checked2 = 'checked="checked"'; else $checked2 = '';
						    		
						    		$values = wp_get_post_terms( $thepostid, woocommerce::attribute_name($tax->attribute_name) );
						    		$value = array();
						    		if (!is_wp_error($values) && $values) :
						    			foreach ($values as $v) :
						    				$value[] = $v->slug;
						    			endforeach;
						    		endif;
						    		
						    		?><tr class="taxonomy <?php echo sanitize_title($tax->attribute_name); ?>" rel="<?php if (isset($attribute['position'])) echo $attribute['position']; else echo '0'; ?>" <?php if (!$value || sizeof($value)==0) echo 'style="display:none"'; ?>>
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
												if (taxonomy_exists(woocommerce::attribute_name($tax->attribute_name))) :
					        						$terms = get_terms( woocommerce::attribute_name($tax->attribute_name), 'orderby=name&hide_empty=0' );
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
											<input type="text" name="attribute_values[<?php echo $i; ?>]" value="<?php 
											
												if (isset($attribute['value'])) :
													if (is_array($attribute['value'])) :
														echo implode(', ', $attribute['value']);
													else :
														echo $attribute['value']; 
													endif;
												endif;
												
											?>" placeholder="<?php _e('Comma separate terms', 'woothemes'); ?>" />
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
				    		echo '<option value="'.sanitize_title($tax->attribute_name).'">'.$tax->attribute_name.'</option>';
				    	endforeach;
				    endif;
				?>
			</select>
			<div class="clear"></div>
		</div>	
		<div id="upsell_product_data" class="panel woocommerce_options_panel">
				<div class="multi_select_products_wrapper"><h4><?php _e('Products', 'woothemes'); ?></h4>
				<ul class="multi_select_products multi_select_products_source">
					<li class="product_search"><input type="search" rel="upsell_ids" name="product_search" id="product_search" placeholder="<?php _e('Search for product', 'woothemes'); ?>" /><div class="clear"></div></li>
				</ul>
				</div>
				<div class="multi_select_products_wrapper multi_select_products_wrapper-alt"><h4><?php _e('Up-Sells', 'woothemes'); ?></h4><ul class="multi_select_products multi_select_products_target">
					<?php
					$upsell_ids = get_post_meta($thepostid, 'upsell_ids', true);
					if (!$upsell_ids) $upsell_ids = array(0);
					woocommerce_product_selection_list_remove($upsell_ids, 'upsell_ids');
					?>
				</ul></div>
				<div class="clear"></div>
						
			</div>
			<div id="crosssell_product_data" class="panel woocommerce_options_panel">
				<div class="multi_select_products_wrapper"><h4><?php _e('Products', 'woothemes'); ?></h4>
				
				<ul class="multi_select_products multi_select_products_source">
					<li class="product_search"><input type="search" rel="crosssell_ids" name="product_search" id="product_search" placeholder="<?php _e('Search for product', 'woothemes'); ?>" /><div class="clear"></div></li>
				</ul>
			</div>
			<div class="multi_select_products_wrapper multi_select_products_wrapper-alt"><h4><?php _e('Cross-Sells', 'woothemes'); ?></h4><ul class="multi_select_products multi_select_products_target">
					<?php
					$crosssell_ids = get_post_meta($thepostid, 'crosssell_ids', true);
					if (!$crosssell_ids) $crosssell_ids = array(0);
					woocommerce_product_selection_list_remove($crosssell_ids, 'crosssell_ids');
					?>
				</ul></div>
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
		
	// Update post meta
	update_post_meta( $post_id, 'regular_price', stripslashes( $_POST['regular_price'] ) );
	update_post_meta( $post_id, 'sale_price', stripslashes( $_POST['sale_price'] ) );
	update_post_meta( $post_id, 'weight', stripslashes( $_POST['weight'] ) );
	update_post_meta( $post_id, 'tax_status', stripslashes( $_POST['tax_status'] ) );
	update_post_meta( $post_id, 'tax_class', stripslashes( $_POST['tax_class'] ) );
	update_post_meta( $post_id, 'stock_status', stripslashes( $_POST['stock_status'] ) );
	update_post_meta( $post_id, 'visibility', stripslashes( $_POST['visibility'] ) );
	if ($_POST['featured']) update_post_meta( $post_id, 'featured', 'yes' ); else update_post_meta( $post_id, 'featured', 'no' );
		
	// Unique SKU 
	$sku = get_post_meta($post_id, 'sku', true);
	$new_sku = stripslashes( $_POST['sku'] );
	if ($new_sku!==$sku) :
		if ($new_sku && !empty($new_sku)) :
			if ($wpdb->get_var("SELECT * FROM $wpdb->postmeta WHERE meta_key='sku' AND meta_value='".$new_sku."';") || $wpdb->get_var("SELECT * FROM $wpdb->posts WHERE ID='".$new_sku."' AND ID!=".$post_id.";")) :
				$woocommerce_errors[] = __('Product SKU must be unique.', 'woothemes');
			else :
				delete_post_meta( $post_id, 'SKU' );
				update_post_meta( $post_id, 'sku', $new_sku );
			endif;
		else :
			delete_post_meta( $post_id, 'SKU' );
			update_post_meta( $post_id, 'sku', '' );
		endif;
	endif;
		
	// Attributes
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
		 	if (isset($attribute_visibility[$i])) $visible = 'yes'; else $visible = 'no';
		 	if (isset($attribute_variation[$i])) $variation = 'yes'; else $variation = 'no';
		 	if ($attribute_is_taxonomy[$i]) $is_taxonomy = 'yes'; else $is_taxonomy = 'no';
		 	
		 	if (is_array($attribute_values[$i])) :
		 		$attribute_values[$i] = array_map('htmlspecialchars', array_map('stripslashes', $attribute_values[$i]));
		 	else :
		 		$attribute_values[$i] = trim(htmlspecialchars(stripslashes($attribute_values[$i])));
		 	endif;
		 	
		 	if (empty($attribute_values[$i]) || ( is_array($attribute_values[$i]) && sizeof($attribute_values[$i])==0) ) :
		 		if ($is_taxonomy=='yes' && taxonomy_exists( woocommerce::attribute_name($attribute_names[$i])) ) :
		 			wp_set_object_terms( $post_id, 0, woocommerce::attribute_name($attribute_names[$i]) );
		 		endif;
		 		continue;
		 	endif;
		 	
		 	$attributes[ sanitize_title( $attribute_names[$i] ) ] = array(
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
		 		
		 		if (taxonomy_exists( woocommerce::attribute_name($tax) )) :
		 			
		 			wp_set_object_terms( $post_id, $value, woocommerce::attribute_name($tax) );
		 			
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
	
	update_post_meta( $post_id, 'product_attributes', $attributes );

	// Product type
	$product_type = sanitize_title( stripslashes( $_POST['product-type'] ) );
	if( !$product_type ) $product_type = 'simple';
	
	wp_set_object_terms($post_id, $product_type, 'product_type');

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
	
	// Update parent if grouped so price sorting works
	if ($post->post_parent || $product_type=='grouped') :
		if ($post->post_parent) :
			$parent_id = $post->post_parent; 
		else :
			$parent_id = $post_id;
		endif;
		
		woocommerce_grouped_price_sync( $parent_id );
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
			
		else :
			
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
	endif;
	
	// Cross sells
	
	if (isset($_POST['crosssell_ids'])) :
		$crosssells = array();
		$ids = $_POST['crosssell_ids'];
		foreach ($ids as $id) :
			if ($id && $id>0) $crosssells[] = $id;
		endforeach;
		update_post_meta( $post_id, 'crosssell_ids', $crosssells );
	endif;
		
	// Do action
	do_action( 'process_product_meta', $post_id );
	
	// Do action for product type
	do_action( 'process_product_meta_' . $product_type, $post_id );
		
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
		
		?><li rel="<?php echo $related_post->ID; ?>"><button type="button" name="Remove" class="button remove" title="Remove">X</button><strong><?php echo $related_post->post_title; ?></strong> &ndash; #<?php echo $related_post->ID; ?> <?php if (isset($SKU) && $SKU) echo 'SKU: '.$SKU; ?><input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $related_post->ID; ?>" /></li><?php 

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
		'simple' => __('Simple', 'woothemes')
	), $product_type) ) );
	
	// Visibility
	woocommerce_wp_select( array( 'id' => 'visibility', 'label' => __('Visibility', 'woothemes'), 'options' => array(
		'visible' => __('Catalog &amp; Search', 'woothemes'),
		'catalog' => __('Catalog', 'woothemes'),
		'search' => __('Search', 'woothemes'),
		'hidden' => __('Hidden', 'woothemes')
	) ) );
	
	// Featured
	woocommerce_wp_checkbox( array( 'id' => 'featured', 'label' => __('Featured?', 'woothemes') ) );
	
	echo '</div>';
			
}