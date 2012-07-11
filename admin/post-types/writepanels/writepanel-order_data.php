<?php
/**
 * Order Data
 * 
 * Functions for displaying the order data meta box
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */

/**
 * Order data meta box
 * 
 * Displays the meta box
 */
function woocommerce_order_data_meta_box($post) {
	
	global $post, $wpdb, $thepostid, $order_status, $woocommerce;
	
	$thepostid = $post->ID;
	
	$order = new WC_Order( $thepostid );
	
	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
	
	// Custom user
	$customer_user = (int) get_post_meta($post->ID, '_customer_user', true);
	
	// Order status
	$order_status = wp_get_post_terms($post->ID, 'shop_order_status');
	if ($order_status) :
		$order_status = current($order_status);
		$order_status = $order_status->slug;
	else :
		$order_status = 'pending';
	endif;
	
	if (!isset($post->post_title) || empty($post->post_title)) :
		$order_title = 'Order';
	else :
		$order_title = $post->post_title;
	endif;
	?>
	<style type="text/css">
		#titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
	</style>
	<div class="panel-wrap woocommerce">
		<input name="post_title" type="hidden" value="<?php echo esc_attr( $order_title ); ?>" />
		<input name="post_status" type="hidden" value="publish" />
		<div id="order_data" class="panel">
		
			<div class="order_data_left">
				
				<h2><?php _e('Order Details', 'woocommerce'); ?> &mdash; <?php echo $order->get_order_number(); ?></h2>
				
				<p class="form-field"><label for="order_status"><?php _e('Order status:', 'woocommerce') ?></label>
				<select id="order_status" name="order_status" class="chosen_select">
					<?php
						$statuses = (array) get_terms('shop_order_status', array('hide_empty' => 0, 'orderby' => 'id'));
						foreach ($statuses as $status) :
							echo '<option value="'.$status->slug.'" ';
							if ($status->slug==$order_status) echo 'selected="selected"';
							echo '>'.__($status->name, 'woocommerce').'</option>';
						endforeach;
					?>
				</select></p>
				
				<p class="form-field last"><label for="order_date"><?php _e('Order Date:', 'woocommerce') ?></label>
					<input type="text" class="date-picker-field" name="order_date" id="order_date" maxlength="10" value="<?php echo date('Y-m-d', strtotime( $post->post_date ) ); ?>" /> @ <input type="text" class="hour" placeholder="<?php _e('h', 'woocommerce') ?>" name="order_date_hour" id="order_date_hour" maxlength="2" size="2" value="<?php echo date('H', strtotime( $post->post_date ) ); ?>" />:<input type="text" class="minute" placeholder="<?php _e('m', 'woocommerce') ?>" name="order_date_minute" id="order_date_minute" maxlength="2" size="2" value="<?php echo date('i', strtotime( $post->post_date ) ); ?>" />
				</p>
	
				<p class="form-field form-field-wide"><label for="customer_user"><?php _e('Customer:', 'woocommerce') ?></label>
				<select id="customer_user" name="customer_user" class="chosen_select">
					<option value=""><?php _e('Guest', 'woocommerce') ?></option>
					<?php
						$users = new WP_User_Query( array( 'orderby' => 'display_name' ) );
						$users = $users->get_results();
						if ($users) foreach ( $users as $user ) :
							echo '<option value="'.$user->ID.'" '; selected($customer_user, $user->ID); echo '>' . $user->display_name . ' ('.$user->user_email.')</option>';
						endforeach;
					?>
				</select></p>
				
				<p class="form-field form-field-wide"><label for="excerpt"><?php _e('Customer Note:', 'woocommerce') ?></label>
				<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php _e('Customer\'s notes about the order', 'woocommerce'); ?>"><?php echo $post->post_excerpt; ?></textarea></p>

				<?php do_action( 'woocommerce_admin_order_data_after_order_details', $order ); ?>

			</div>
			<div class="order_data_right">
				<div class="order_data">
					<h2><?php _e('Billing Details', 'woocommerce'); ?> <a class="edit_address" href="#">(<?php _e('Edit', 'woocommerce') ;?>)</a></h2>
					<?php
						$billing_data = apply_filters('woocommerce_admin_billing_fields', array(
							'first_name' => array( 
								'label' => __('First Name', 'woocommerce'), 
								'show'	=> false
								),
							'last_name' => array( 
								'label' => __('Last Name', 'woocommerce'), 
								'show'	=> false
								),
							'company' => array( 
								'label' => __('Company', 'woocommerce'), 
								'show'	=> false
								),
							'address_1' => array( 
								'label' => __('Address 1', 'woocommerce'), 
								'show'	=> false
								),
							'address_2' => array( 
								'label' => __('Address 2', 'woocommerce'),
								'show'	=> false 
								),
							'city' => array( 
								'label' => __('City', 'woocommerce'), 
								'show'	=> false
								),
							'postcode' => array( 
								'label' => __('Postcode', 'woocommerce'), 
								'show'	=> false
								),
							'country' => array( 
								'label' => __('Country', 'woocommerce'), 
								'show'	=> false,
								'type'	=> 'select',
								'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + $woocommerce->countries->get_allowed_countries()
								),
							'state' => array( 
								'label' => __('State/County', 'woocommerce'), 
								'show'	=> false
								),
							'email' => array( 
								'label' => __('Email', 'woocommerce'), 
								),
							'phone' => array( 
								'label' => __('Phone', 'woocommerce'), 
								),
							));
						
						// Display values
						echo '<div class="address">';
						
							if ($order->get_formatted_billing_address()) echo '<p><strong>'.__('Address', 'woocommerce').':</strong><br/> ' .$order->get_formatted_billing_address().'</p>'; else echo '<p class="none_set"><strong>'.__('Address', 'woocommerce').':</strong> ' . __('No billing address set.', 'woocommerce') . '</p>';
							
							foreach ( $billing_data as $key => $field ) : if (isset($field['show']) && !$field['show']) continue;
								$field_name = 'billing_'.$key;
								if ( $order->$field_name ) echo '<p><strong>'.$field['label'].':</strong> '.$order->$field_name.'</p>';
							endforeach;
						
						echo '</div>';

						// Display form
						echo '<div class="edit_address"><p><button class="button load_customer_billing">'.__('Load customer billing address', 'woocommerce').'</button></p>';
						
						foreach ( $billing_data as $key => $field ) :
							if (!isset($field['type'])) $field['type'] = 'text';
							switch ($field['type']) {
								case "select" :
									woocommerce_wp_select( array( 'id' => '_billing_' . $key, 'label' => $field['label'], 'options' => $field['options'] ) );
								break;
								default :
									woocommerce_wp_text_input( array( 'id' => '_billing_' . $key, 'label' => $field['label'] ) );
								break;
							}
						endforeach;
						
						echo '</div>';

						do_action( 'woocommerce_admin_order_data_after_billing_address', $order );
					?>
				</div>
				<div class="order_data order_data_alt">
					
					<h2><?php _e('Shipping Details', 'woocommerce'); ?> <a class="edit_address" href="#">(<?php _e('Edit', 'woocommerce') ;?>)</a></h2>
					<?php
						$shipping_data = apply_filters('woocommerce_admin_shipping_fields', array(
							'first_name' => array( 
								'label' => __('First Name', 'woocommerce'), 
								'show'	=> false
								),
							'last_name' => array( 
								'label' => __('Last Name', 'woocommerce'), 
								'show'	=> false
								),
							'company' => array( 
								'label' => __('Company', 'woocommerce'), 
								'show'	=> false
								),
							'address_1' => array( 
								'label' => __('Address 1', 'woocommerce'), 
								'show'	=> false
								),
							'address_2' => array( 
								'label' => __('Address 2', 'woocommerce'),
								'show'	=> false 
								),
							'city' => array( 
								'label' => __('City', 'woocommerce'), 
								'show'	=> false
								),
							'postcode' => array( 
								'label' => __('Postcode', 'woocommerce'), 
								'show'	=> false
								),
							'country' => array( 
								'label' => __('Country', 'woocommerce'), 
								'show'	=> false,
								'type'	=> 'select',
								'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + $woocommerce->countries->get_allowed_countries()
								),
							'state' => array( 
								'label' => __('State/County', 'woocommerce'), 
								'show'	=> false
								),
							));
						
						// Display values
						echo '<div class="address">';
						
							if ($order->get_formatted_shipping_address()) echo '<p><strong>'.__('Address', 'woocommerce').':</strong><br/> ' .$order->get_formatted_shipping_address().'</p>'; else echo '<p class="none_set"><strong>'.__('Address', 'woocommerce').':</strong> ' . __('No shipping address set.', 'woocommerce') . '</p>';
							
							if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) : if (isset($field['show']) && !$field['show']) continue;
								$field_name = 'shipping_'.$key;
								if ( $order->$field_name ) echo '<p><strong>'.$field['label'].':</strong> '.$order->$field_name.'</p>';
							endforeach;
						
						echo '</div>';

						// Display form
						echo '<div class="edit_address"><p><button class="button load_customer_shipping">'.__('Load customer shipping address', 'woocommerce').'</button></p>';
						
						if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) :
							if (!isset($field['type'])) $field['type'] = 'text';
							switch ($field['type']) {
								case "select" :
									woocommerce_wp_select( array( 'id' => '_shipping_' . $key, 'label' => $field['label'], 'options' => $field['options'] ) );
								break;
								default :
									woocommerce_wp_text_input( array( 'id' => '_shipping_' . $key, 'label' => $field['label'] ) );
								break;
							}
						endforeach;
						
						echo '</div>';
						
						do_action( 'woocommerce_admin_order_data_after_shipping_address', $order );
					?>
				</div>
			</div>
			<div class="clear"></div>

		</div>
	</div>
	<?php
}

/**
 * Order items meta box
 * 
 * Displays the order items meta box - for showing individual items in the order
 */
function woocommerce_order_items_meta_box($post) {
	global $woocommerce;
	
	$order_items 	= (array) maybe_unserialize( get_post_meta($post->ID, '_order_items', true) );
	?>
	<div class="woocommerce_order_items_wrapper">
		<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
			<thead>
				<tr>
					<th class="thumb" width="1%"><?php _e('Item', 'woocommerce'); ?></th>
					<th class="sku"><?php _e('SKU', 'woocommerce'); ?></th>
					<th class="name"><?php _e('Name', 'woocommerce'); ?></th>
					<?php do_action('woocommerce_admin_order_item_headers'); ?>
					
					<th class="tax_class"><?php _e('Tax Class', 'woocommerce'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Tax class for the line item', 'woocommerce'); ?>." href="#">[?]</a></th>

					<th class="quantity"><?php _e('Qty', 'woocommerce'); ?></th>
					
					<th class="line_subtotal"><?php _e('Line&nbsp;Subtotal', 'woocommerce'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Line cost and line tax before pre-tax discounts', 'woocommerce'); ?>" href="#">[?]</a></th>
					
					<th class="line_total"><?php _e('Line&nbsp;Total', 'woocommerce'); ?>&nbsp;<a class="tips" data-tip="<?php _e('Line cost and line tax after pre-tax discounts', 'woocommerce'); ?>" href="#">[?]</a></th>

				</tr>
			</thead>
			<tbody id="order_items_list">	
				
				<?php $loop = 0; if (sizeof($order_items)>0 && isset($order_items[0]['id'])) foreach ($order_items as $item) : 
				
					if (isset($item['variation_id']) && $item['variation_id'] > 0) :
						$_product = new WC_Product_Variation( $item['variation_id'] );
					else :
						$_product = new WC_Product( $item['id'] );
					endif;

					// Totals - Backwards Compatibility
					if (!isset($item['line_total']) && isset($item['taxrate']) && isset($item['cost'])) :
						$item['line_tax'] = number_format(($item['cost'] * $item['qty'])*($item['taxrate']/100), 2, '.', '');
						$item['line_total'] = ($item['cost'] * $item['qty']);
						$item['line_subtotal_tax'] = $item['line_tax'];
						$item['line_subtotal'] = $item['line_total'];
					endif;
					?>
					<tr class="item" rel="<?php echo $loop; ?>">
						<td class="thumb">
							<a href="<?php echo esc_url( admin_url('post.php?post='. $_product->id .'&action=edit') ); ?>" class="tips" data-tip="<?php
								echo '<strong>'.__('Product ID:', 'woocommerce').'</strong> '. $item['id'];
								echo '<br/><strong>'.__('Variation ID:', 'woocommerce').'</strong> '; if ($item['variation_id']) echo $item['variation_id']; else echo '-';
								echo '<br/><strong>'.__('Product SKU:', 'woocommerce').'</strong> '; if ($_product->sku) echo $_product->sku; else echo '-';
							?>"><?php echo $_product->get_image(); ?></a>
						</td>
						<td class="sku" width="1%">
							<?php if ($_product->sku) echo $_product->sku; else echo '-'; ?>
							<input type="hidden" class="item_id" name="item_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $item['id'] ); ?>" />
							<input type="hidden" name="item_name[<?php echo $loop; ?>]" value="<?php echo esc_attr( $item['name'] ); ?>" />
							<input type="hidden" name="item_variation[<?php echo $loop; ?>]" value="<?php echo esc_attr( $item['variation_id'] ); ?>" />
						</td>
						<td class="name">
						
							<div class="row-actions">
								<span class="trash"><a class="remove_row" href="#"><?php _e('Delete item', 'woocommerce'); ?></a> | </span>
								<span class="view"><a href="<?php echo esc_url( admin_url('post.php?post='. $_product->id .'&action=edit') ); ?>"><?php _e('View product', 'woocommerce'); ?></a>
							</div>
							
							<?php echo $item['name']; ?>
							<?php
								if (isset($_product->variation_data)) echo '<br/>' . woocommerce_get_formatted_variation( $_product->variation_data, true );
							?>
							<table class="meta" cellspacing="0">
								<tfoot>
									<tr>
										<td colspan="4"><button class="add_meta button"><?php _e('Add&nbsp;meta', 'woocommerce'); ?></button></td>
									</tr>
								</tfoot>
								<tbody class="meta_items">
								<?php
									if (isset($item['item_meta']) && is_array($item['item_meta']) && sizeof($item['item_meta'])>0) :
										foreach ($item['item_meta'] as $key => $meta) :
										
											// Backwards compatibility
											if (is_array($meta) && isset($meta['meta_name'])) :
												$meta_name = $meta['meta_name'];
												$meta_value = $meta['meta_value'];
											else :
												$meta_name = $key;
												$meta_value = $meta;
											endif;
	
											echo '<tr><td><input type="text" name="meta_name['.$loop.'][]" value="'.esc_attr( $meta_name ).'" /></td><td><input type="text" name="meta_value['.$loop.'][]" value="'.esc_attr( $meta_value ).'" /></td><td width="1%"><button class="remove_meta button">&times;</button></td></tr>';
										endforeach;
									endif;
								?>
								</tbody>
							</table>
						</td>

						<?php do_action('woocommerce_admin_order_item_values', $_product, $item); ?>

						<td class="tax_class" width="1%">
							<select class="tax_class" name="item_tax_class[<?php echo $loop; ?>]" title="<?php _e('Tax class', 'woocommerce'); ?>">
								<?php 
								$item_value = (isset($item['tax_class'])) ? sanitize_title($item['tax_class']) : '';
								$tax_classes = array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
								$classes_options = array();
								$classes_options[''] = __('Standard', 'woocommerce');
								if ($tax_classes) foreach ($tax_classes as $class) :
									$classes_options[sanitize_title($class)] = $class;
								endforeach;
								foreach ($classes_options as $value => $name) echo '<option value="'. $value .'" '.selected( $value, $item_value, false ).'>'. $name .'</option>';
								?>
							</select>
						</td>
						
						<td class="quantity" width="1%">
							<input type="text" name="item_quantity[<?php echo $loop; ?>]" placeholder="0" value="<?php echo esc_attr( $item['qty'] ); ?>" size="2" class="quantity" />
						</td>
						
						<td class="line_subtotal" width="1%">
							<label><?php _e('Cost', 'woocommerce'); ?>: <input type="text" name="line_subtotal[<?php echo $loop; ?>]" placeholder="0.00" value="<?php if (isset($item['line_subtotal'])) echo esc_attr( $item['line_subtotal'] ); ?>" class="line_subtotal" /></label>
							
							<label><?php _e('Tax', 'woocommerce'); ?>: <input type="text" name="line_subtotal_tax[<?php echo $loop; ?>]" placeholder="0.00" value="<?php if (isset($item['line_subtotal_tax'])) echo esc_attr( $item['line_subtotal_tax'] ); ?>" class="line_subtotal_tax" /></label>
						</td>
						
						<td class="line_total" width="1%">
							<label><?php _e('Cost', 'woocommerce'); ?>: <input type="text" name="line_total[<?php echo $loop; ?>]" placeholder="0.00" value="<?php if (isset($item['line_total'])) echo esc_attr( $item['line_total'] ); ?>" class="line_total" /></label>
							
							<label><?php _e('Tax', 'woocommerce'); ?>: <input type="text" name="line_tax[<?php echo $loop; ?>]" placeholder="0.00" value="<?php if (isset($item['line_tax'])) echo esc_attr( $item['line_tax'] ); ?>" class="line_tax" /></label>
						</td>
						
					</tr>
				<?php $loop++; endforeach; ?>
			</tbody>
		</table>
	</div>
	
	<p class="buttons">
		<select id="add_item_id" name="add_item_id[]" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>" style="width: 400px"></select>
		
		<button type="button" class="button add_shop_order_item"><?php _e('Add item(s)', 'woocommerce'); ?></button>
	</p>
	<p class="buttons buttons-alt">
		<button type="button" class="button calc_line_taxes"><?php _e('Calc line tax &uarr;', 'woocommerce'); ?></button>
		<button type="button" class="button calc_totals"><?php _e('Calc totals &rarr;', 'woocommerce'); ?></button>
	</p>	
	<div class="clear"></div>
	<?php
	
}

/**
 * Order actions meta box
 * 
 * Displays the order actions meta box - buttons for managing order stock and sending the customer an invoice.
 */
function woocommerce_order_actions_meta_box($post) {
	?>
	<ul class="order_actions">
		<li><input type="submit" class="button button-primary tips" name="save" value="<?php _e('Save Order', 'woocommerce'); ?>" data-tip="<?php _e('Save/update the order', 'woocommerce'); ?>" /></li>

		<li><input type="submit" class="button tips" name="reduce_stock" value="<?php _e('Reduce stock', 'woocommerce'); ?>" data-tip="<?php _e('Reduces stock for each item in the order; useful after manually creating an order or manually marking an order as paid.', 'woocommerce'); ?>" /></li>
		
		<li><input type="submit" class="button tips" name="restore_stock" value="<?php _e('Restore stock', 'woocommerce'); ?>" data-tip="<?php _e('Restores stock for each item in the order; useful after refunding or canceling the entire order.', 'woocommerce'); ?>" /></li>
		
		<li><input type="submit" class="button tips" name="invoice" value="<?php _e('Email invoice', 'woocommerce'); ?>" data-tip="<?php _e('Email the order to the customer. Unpaid orders will include a payment link.', 'woocommerce'); ?>" /></li>
		
		<?php do_action('woocommerce_order_actions', $post->ID); ?>
		
		<li class="wide">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) ) {
			if ( !EMPTY_TRASH_DAYS )
				$delete_text = __('Delete Permanently', 'woocommerce');
			else
				$delete_text = __('Move to Trash', 'woocommerce');
			?>
		<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link($post->ID) ); ?>"><?php echo $delete_text; ?></a><?php
		} ?>
		</li>
	</ul>
	<?php
}

/**
 * Order totals meta box
 * 
 * Displays the order totals meta box
 */
function woocommerce_order_totals_meta_box($post) {
	global $woocommerce;
	
	$data = get_post_custom( $post->ID );
	?>
	<div class="totals_group">
		<h4><?php _e('Discounts', 'woocommerce'); ?></h4>
		<ul class="totals">
			
			<li class="left">
				<label><?php _e('Cart Discount:', 'woocommerce'); ?></label>
				<input type="text" id="_cart_discount" name="_cart_discount" placeholder="0.00" value="<?php 
				if (isset($data['_cart_discount'][0])) echo $data['_cart_discount'][0];
				?>" class="calculated" />
			</li>
			
			<li class="right">
				<label><?php _e('Order Discount:', 'woocommerce'); ?></label>
				<input type="text" id="_order_discount" name="_order_discount" placeholder="0.00" value="<?php 
				if (isset($data['_order_discount'][0])) echo $data['_order_discount'][0];
				?>" />
			</li>
	
		</ul>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e('Shipping', 'woocommerce'); ?></h4>
		<ul class="totals">
			
			<li class="left">
				<label><?php _e('Cost ex. tax:', 'woocommerce'); ?></label>
				<input type="text" id="_order_shipping" name="_order_shipping" placeholder="0.00 <?php _e('(ex. tax)', 'woocommerce'); ?>" value="<?php if (isset($data['_order_shipping'][0])) echo $data['_order_shipping'][0];
				?>" class="first" />
			</li>
			
			<li class="right">
				<label><?php _e('Method:', 'woocommerce'); ?></label>
				<input type="text" name="_shipping_method" id="_shipping_method" value="<?php 
				if (isset($data['_shipping_method'][0])) echo $data['_shipping_method'][0];
				?>" placeholder="<?php _e('Shipping method&hellip;', 'woocommerce'); ?>" />
			</li>
	
		</ul>
		<?php do_action( 'woocommerce_admin_order_totals_after_shipping', $post->ID ) ?>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e('Tax Rows', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e('These rows contain taxes for this order. This allows you to display multiple or compound taxes rather than a single total.', 'woocommerce'); ?>" href="#">[?]</a></h4>
		<div id="tax_rows">
			<?php 
				$loop = 0;
				$taxes = (isset($data['_order_taxes'][0])) ? maybe_unserialize($data['_order_taxes'][0]) : '';
				if (is_array($taxes) && sizeof($taxes)>0) :
					foreach ($taxes as $tax) :
						?>
						<div class="tax_row">
							<p class="first">
								<label><?php _e('Tax Label:', 'woocommerce'); ?></label>
								<input type="text" name="_order_taxes_label[<?php echo $loop; ?>]" placeholder="<?php echo $woocommerce->countries->tax_or_vat(); ?>" value="<?php echo $tax['label']; ?>" />
							</p>
							<p class="last">
								<label><?php _e('Compound:', 'woocommerce'); ?>
								<input type="checkbox" name="_order_taxes_compound[<?php echo $loop; ?>]" <?php checked($tax['compound'], 1); ?> /></label>
							</p>
							<p class="first">
								<label><?php _e('Cart Tax:', 'woocommerce'); ?></label>
								<input type="text" name="_order_taxes_cart[<?php echo $loop; ?>]" placeholder="0.00" value="<?php echo $tax['cart_tax']; ?>" />
							</p>
							<p class="last">
								<label><?php _e('Shipping Tax:', 'woocommerce'); ?></label>
								<input type="text" name="_order_taxes_shipping[<?php echo $loop; ?>]" placeholder="0.00" value="<?php echo $tax['shipping_tax']; ?>" />
							</p>
							<a href="#" class="delete_tax_row">&times;</a>
							<div class="clear"></div>
						</div>
						<?php
						$loop++;
					endforeach;
				endif;
			?>
		</div>
		<h4><a href="#" class="add_tax_row"><?php _e('+ Add tax row', 'woocommerce'); ?></a></h4>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e('Tax Totals', 'woocommerce'); ?></h4>
		<ul class="totals">
			
			<li class="left">
				<label><?php _e('Cart Tax:', 'woocommerce'); ?></label>
				<input type="text" id="_order_tax" name="_order_tax" placeholder="0.00" value="<?php 
				if (isset($data['_order_tax'][0])) echo $data['_order_tax'][0];
				?>" class="calculated" />
			</li>
			
			<li class="right">
				<label><?php _e('Shipping Tax:', 'woocommerce'); ?></label>
				<input type="text" id="_order_shipping_tax" name="_order_shipping_tax" placeholder="0.00" value="<?php 
				if (isset($data['_order_shipping_tax'][0])) echo $data['_order_shipping_tax'][0];
				?>" />
			</li>
	
		</ul>
		<div class="clear"></div>
	</div>
	<div class="totals_group">
		<h4><?php _e('Total', 'woocommerce'); ?></h4>
		<ul class="totals">

			<li class="left">
				<label><?php _e('Order Total:', 'woocommerce'); ?></label>
				<input type="text" id="_order_total" name="_order_total" placeholder="0.00" value="<?php 
				if (isset($data['_order_total'][0])) echo $data['_order_total'][0];
				?>" class="calculated" />
			</li>
			
			<li class="right">
				<label><?php _e('Payment Method:', 'woocommerce'); ?></label>
				<input type="text" name="_payment_method" id="_payment_method" value="<?php 
					if (isset($data['_payment_method'][0])) echo $data['_payment_method'][0];
				?>" class="first" placeholder="<?php _e('Payment method&hellip;', 'woocommerce'); ?>" />
			</li>
	
		</ul>
		<div class="clear"></div>
	</div>
	<?php
}

/**
 * Order Data Save
 * 
 * Function for processing and storing all order data.
 */
add_action('woocommerce_process_shop_order_meta', 'woocommerce_process_shop_order_meta', 1, 2);

function woocommerce_process_shop_order_meta( $post_id, $post ) {
	global $wpdb, $woocommerce, $woocommerce_errors;
	
	// Add key
		add_post_meta( $post_id, '_order_key', uniqid('order_'), true );

	// Update post data
		update_post_meta( $post_id, '_billing_first_name', stripslashes( $_POST['_billing_first_name'] ));
		update_post_meta( $post_id, '_billing_last_name', stripslashes( $_POST['_billing_last_name'] ));
		update_post_meta( $post_id, '_billing_company', stripslashes( $_POST['_billing_company'] ));
		update_post_meta( $post_id, '_billing_address_1', stripslashes( $_POST['_billing_address_1'] ));
		update_post_meta( $post_id, '_billing_address_2', stripslashes( $_POST['_billing_address_2'] ));
		update_post_meta( $post_id, '_billing_city', stripslashes( $_POST['_billing_city'] ));
		update_post_meta( $post_id, '_billing_postcode', stripslashes( $_POST['_billing_postcode'] ));
		update_post_meta( $post_id, '_billing_country', stripslashes( $_POST['_billing_country'] ));
		update_post_meta( $post_id, '_billing_state', stripslashes( $_POST['_billing_state'] ));
		update_post_meta( $post_id, '_billing_email', stripslashes( $_POST['_billing_email'] ));
		update_post_meta( $post_id, '_billing_phone', stripslashes( $_POST['_billing_phone'] ));
		update_post_meta( $post_id, '_shipping_first_name', stripslashes( $_POST['_shipping_first_name'] ));
		update_post_meta( $post_id, '_shipping_last_name', stripslashes( $_POST['_shipping_last_name'] ));
		update_post_meta( $post_id, '_shipping_company', stripslashes( $_POST['_shipping_company'] ));
		update_post_meta( $post_id, '_shipping_address_1', stripslashes( $_POST['_shipping_address_1'] ));
		update_post_meta( $post_id, '_shipping_address_2', stripslashes( $_POST['_shipping_address_2'] ));
		update_post_meta( $post_id, '_shipping_city', stripslashes( $_POST['_shipping_city'] ));
		update_post_meta( $post_id, '_shipping_postcode', stripslashes( $_POST['_shipping_postcode'] ));
		update_post_meta( $post_id, '_shipping_country', stripslashes( $_POST['_shipping_country'] ));
		update_post_meta( $post_id, '_shipping_state', stripslashes( $_POST['_shipping_state'] ));
		update_post_meta( $post_id, '_order_shipping', stripslashes( $_POST['_order_shipping'] ));
		update_post_meta( $post_id, '_cart_discount', stripslashes( $_POST['_cart_discount'] ));
		update_post_meta( $post_id, '_order_discount', stripslashes( $_POST['_order_discount'] ));
		update_post_meta( $post_id, '_order_total', stripslashes( $_POST['_order_total'] ));
		update_post_meta( $post_id, '_customer_user', (int) $_POST['customer_user'] );
		update_post_meta( $post_id, '_order_tax', stripslashes( $_POST['_order_tax'] ));
		update_post_meta( $post_id, '_order_shipping_tax', stripslashes( $_POST['_order_shipping_tax'] ));
		
		// Shipping method handling
		if ( get_post_meta( $post_id, '_shipping_method', true ) !== stripslashes( $_POST['_shipping_method'] ) ) {
			update_post_meta( $post_id, '_shipping_method', stripslashes( $_POST['_shipping_method'] ));
			update_post_meta( $post_id, '_shipping_method_title', stripslashes( $_POST['_shipping_method'] ));
		}
		
		// Payment method handling
		if ( get_post_meta( $post_id, '_payment_method', true ) !== stripslashes( $_POST['_payment_method'] ) ) {
			update_post_meta( $post_id, '_payment_method', stripslashes( $_POST['_payment_method'] ));
			update_post_meta( $post_id, '_payment_method_title', stripslashes( $_POST['_payment_method'] ));
		}
	
	// Update date
		if ( empty( $_POST['order_date'] ) ) {
			$date = current_time('timestamp');
		} else {
			$date = strtotime( $_POST['order_date'] . ' ' . (int) $_POST['order_date_hour'] . ':' . (int) $_POST['order_date_minute'] . ':00' );
		}
		
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_date = %s WHERE ID = %s", date('Y-m-d H:i:s', $date), $post_id ) );
	
	// Tax rows
		$order_taxes = array();
		
		if (isset($_POST['_order_taxes_label'])) :
			
			$order_taxes_label		= $_POST['_order_taxes_label'];
			$order_taxes_compound 	= isset($_POST['_order_taxes_compound']) ? $_POST['_order_taxes_compound'] : array();
			$order_taxes_cart 		= $_POST['_order_taxes_cart'];
			$order_taxes_shipping 	= $_POST['_order_taxes_shipping'];
			$order_taxes_label_count = sizeof( $order_taxes_label );
			
			for ($i=0; $i<$order_taxes_label_count; $i++) :
				
				// Add to array if the tax amount is set
				if (!$order_taxes_cart[$i] && !$order_taxes_shipping[$i]) continue;
				
				if (!$order_taxes_label[$i]) $order_taxes_label[$i] = $woocommerce->countries->tax_or_vat();
				
				if (isset($order_taxes_compound[$i])) $is_compound = 1; else $is_compound = 0;
				
				$order_taxes[] = array(
					'label' 		=> esc_attr($order_taxes_label[$i]),
					'compound' 		=> $is_compound,
					'cart_tax' 		=> esc_attr($order_taxes_cart[$i]),
					'shipping_tax' 	=> esc_attr($order_taxes_shipping[$i])
				);
				
			endfor;
			
		endif;
		
		update_post_meta( $post_id, '_order_taxes', $order_taxes );
		
	// Order items
		$order_items = array();
	
		if (isset($_POST['item_id'])) :
			$item_id			= $_POST['item_id'];
			$item_variation	= $_POST['item_variation'];
			$item_name 		= $_POST['item_name'];
			$item_quantity 	= $_POST['item_quantity'];
			
			$line_subtotal		= $_POST['line_subtotal'];
			$line_subtotal_tax	= $_POST['line_subtotal_tax'];
			
			$line_total 		= $_POST['line_total'];
			$line_tax		 	= $_POST['line_tax'];
			
			$item_meta_names 	= (isset($_POST['meta_name'])) ? $_POST['meta_name'] : '';
			$item_meta_values 	= (isset($_POST['meta_value'])) ? $_POST['meta_value'] : '';
			
			$item_tax_class	= $_POST['item_tax_class'];
			
			$item_id_count = sizeof( $item_id );
			
			for ($i=0; $i<$item_id_count; $i++) :
				
				if (!isset($item_id[$i]) || !$item_id[$i]) continue;
				if (!isset($item_name[$i])) continue;
				if (!isset($item_quantity[$i]) || $item_quantity[$i] < 1) continue;
				if (!isset($line_total[$i])) continue;
				if (!isset($line_tax[$i])) continue;
				
				// Meta
				$item_meta 		= new order_item_meta();
				
				if (isset($item_meta_names[$i]) && isset($item_meta_values[$i])) :
			 	$meta_names 	= $item_meta_names[$i];
			 	$meta_values 	= $item_meta_values[$i];
			 	$meta_names_count = sizeof( $meta_names );
			 	
			 	for ($ii=0; $ii<$meta_names_count; $ii++) :
			 		$meta_name 		= esc_attr( $meta_names[$ii] );
			 		$meta_value 	= esc_attr( $meta_values[$ii] );
			 		if ($meta_name && $meta_value) :
			 			$item_meta->add( $meta_name, $meta_value );
			 		endif;
			 	endfor;
				endif;
				
				// Add to array	 	
				$order_items[] = apply_filters('update_order_item', array(
					'id' 				=> htmlspecialchars(stripslashes($item_id[$i])),
					'variation_id' 		=> (int) $item_variation[$i],
					'name' 				=> htmlspecialchars(stripslashes($item_name[$i])),
					'qty' 				=> (int) $item_quantity[$i],
					'line_total' 		=> rtrim(rtrim(number_format(woocommerce_clean($line_total[$i]), 4, '.', ''), '0'), '.'),
					'line_tax'			=> rtrim(rtrim(number_format(woocommerce_clean($line_tax[$i]), 4, '.', ''), '0'), '.'),
					'line_subtotal'		=> rtrim(rtrim(number_format(woocommerce_clean($line_subtotal[$i]), 4, '.', ''), '0'), '.'),
					'line_subtotal_tax' => rtrim(rtrim(number_format(woocommerce_clean($line_subtotal_tax[$i]), 4, '.', ''), '0'), '.'),
					'item_meta'			=> $item_meta->meta,
					'tax_class'			=> woocommerce_clean($item_tax_class[$i])
				));
			 	
			 endfor; 
		endif;	
	
		update_post_meta( $post_id, '_order_items', $order_items );
		
	// Order data saved, now get it so we can manipulate status
		$order = new WC_Order( $post_id );
		
	// Order status
		$order->update_status( $_POST['order_status'] );
	
	// Handle button actions
	
		if (isset($_POST['reduce_stock']) && $_POST['reduce_stock'] && sizeof($order_items)>0) :
			
			$order->add_order_note( __('Manually reducing stock.', 'woocommerce') );
			
			foreach ($order_items as $order_item) :
						
				$_product = $order->get_product_from_item( $order_item );
				
				if ( $_product->exists() ) :
				
				 	if ( $_product->managing_stock() ) :
						
						$old_stock = $_product->stock;
						
						$new_quantity = $_product->reduce_stock( $order_item['qty'] );
						
						$order->add_order_note( sprintf( __('Item #%s stock reduced from %s to %s.', 'woocommerce'), $order_item['id'], $old_stock, $new_quantity) );
						
						$order->send_stock_notifications( $_product, $new_quantity, $order_item['qty'] );
						
					endif;
				
				else :
					
					$order->add_order_note( sprintf( __('Item %s %s not found, skipping.', 'woocommerce'), $order_item['id'], $order_item['name'] ) );
					
				endif;
			 	
			endforeach;
			
			$order->add_order_note( __('Manual stock reduction complete.', 'woocommerce') );
			
			do_action( 'woocommerce_reduce_order_stock', $order );
			
		elseif (isset($_POST['restore_stock']) && $_POST['restore_stock'] && sizeof($order_items)>0) :
			
			$order->add_order_note( __('Manually restoring stock.', 'woocommerce') );
			
			foreach ($order_items as $order_item) :
						
				$_product = $order->get_product_from_item( $order_item );
				
				if ( $_product->exists() ) :
				
				 	if ($_product->managing_stock()) :
						
						$old_stock = $_product->stock;
						
						$new_quantity = $_product->increase_stock( $order_item['qty'] );
						
						$order->add_order_note( sprintf( __('Item #%s stock increased from %s to %s.', 'woocommerce'), $order_item['id'], $old_stock, $new_quantity) );
						
					endif;
				
				else :
					
					$order->add_order_note( sprintf( __('Item %s %s not found, skipping.', 'woocommerce'), $order_item['id'], $order_item['name'] ) );
					
				endif;
			 	
			endforeach;
			
			$order->add_order_note( __('Manual stock restore complete.', 'woocommerce') );
			
			do_action( 'woocommerce_restore_order_stock', $order );
		
		elseif (isset($_POST['invoice']) && $_POST['invoice']) :
			
			do_action( 'woocommerce_before_send_customer_invoice', $order );
			
			$mailer = $woocommerce->mailer();
			$mailer->customer_invoice( $order );
			
			do_action( 'woocommerce_after__customer_invoice', $order );
			
		endif;
	
	delete_transient( 'woocommerce_processing_order_count' );
}