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
	
	global $post, $wpdb, $thepostid, $order_status;
	
	$thepostid = $post->ID;
	
	$order = &new woocommerce_order( $thepostid );
	
	add_action('admin_footer', 'woocommerce_meta_scripts');
	
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
				
				<h2><?php _e('Order Details', 'woothemes'); ?></h2>
				
				<p class="form-field"><label for="order_status"><?php _e('Order status:', 'woothemes') ?></label>
				<select id="order_status" name="order_status" class="chosen_select">
					<?php
						$statuses = (array) get_terms('shop_order_status', array('hide_empty' => 0, 'orderby' => 'id'));
						foreach ($statuses as $status) :
							echo '<option value="'.$status->slug.'" ';
							if ($status->slug==$order_status) echo 'selected="selected"';
							echo '>'.__($status->name, 'woothemes').'</option>';
						endforeach;
					?>
				</select></p>
	
				<p class="form-field form-field-wide"><label for="customer_user"><?php _e('Customer:', 'woothemes') ?></label>
				<select id="customer_user" name="customer_user" class="chosen_select">
					<option value=""><?php _e('Guest', 'woothemes') ?></option>
					<?php
						$users = new WP_User_Query( array( 'orderby' => 'display_name' ) );
						$users = $users->get_results();
						if ($users) foreach ( $users as $user ) :
							echo '<option value="'.$user->ID.'" '; selected($customer_user, $user->ID); echo '>' . $user->display_name . ' ('.$user->user_email.')</option>';
						endforeach;
					?>
				</select></p>
				
				<p class="form-field form-field-wide"><label for="excerpt"><?php _e('Customer Note:', 'woothemes') ?></label>
				<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php _e('Customer\'s notes about the order', 'woothemes'); ?>"><?php echo $post->post_excerpt; ?></textarea></p>
			
			</div>
			<div class="order_data_right">
				<div class="order_data">
					
					<h2><?php _e('Billing Address', 'woothemes'); ?> <a class="edit_address" href="#">(<?php _e('Edit', 'woothemes') ;?>)</a></h2>
					<div class="address">
						<?php
						if ($order->formatted_billing_address) echo wpautop($order->formatted_billing_address); else echo '<p class="none_set">' . __('No billing address set.', 'woothemes') . '</p>';
						?>
					</div>
					<div class="edit_address">
						<p>
							<button class="button load_customer_billing"><?php _e('Load customer billing address', 'woothemes'); ?></button>
						</p>
						<?php
						woocommerce_wp_text_input( array( 'id' => '_billing_first_name', 'label' => __('First Name', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_last_name', 'label' => __('Last Name', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_company', 'label' => __('Company', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_address_1', 'label' => __('Address 1', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_address_2', 'label' => __('Address 2', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_city', 'label' => __('City', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_postcode', 'label' => __('Postcode', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_country', 'label' => __('Country', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_state', 'label' => __('State/County', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_email', 'label' => __('Email Address', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_billing_phone', 'label' => __('Tel', 'woothemes') ) );
						?>
					</div>
					
				</div>
				<div class="order_data">
					
					<h2><?php _e('Shipping Address', 'woothemes'); ?> <a class="edit_address" href="#">(<?php _e('Edit', 'woothemes') ;?>)</a></h2>
					<div class="address">
						<?php
						if ($order->formatted_shipping_address) echo wpautop($order->formatted_shipping_address); else echo '<p class="none_set">' . __('No shipping address set.', 'woothemes') . '</p>';
						?>
					</div>
					<div class="edit_address">
						<p>
							<button class="button load_customer_shipping"><?php _e('Load customer shipping address', 'woothemes'); ?></button>
							<button class="button billing-same-as-shipping"><?php _e('Copy from billing information', 'woothemes'); ?></button>
						</p>
						<?php
						woocommerce_wp_text_input( array( 'id' => '_shipping_first_name', 'label' => __('First Name', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_last_name', 'label' => __('Last Name', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_company', 'label' => __('Company', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_address_1', 'label' => __('Address 1', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_address_2', 'label' => __('Address 2', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_city', 'label' => __('City', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_postcode', 'label' => __('Postcode', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_country', 'label' => __('Country', 'woothemes') ) );
						woocommerce_wp_text_input( array( 'id' => '_shipping_state', 'label' => __('State/County', 'woothemes') ) );
						?>
					</div>
				
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
					<th class="product-id" width="1%"><?php _e('ID', 'woothemes'); ?></th>
					<th class="sku"><?php _e('SKU', 'woothemes'); ?></th>
					<th class="name"><?php _e('Name', 'woothemes'); ?></th>
					<th class="meta" width="1%"><?php _e('Item&nbsp;Meta', 'woothemes'); ?></th>
					<?php do_action('woocommerce_admin_order_item_headers'); ?>
					<th class="quantity"><?php _e('Quantity', 'woothemes'); ?></th>
					<th class="cost"><?php _e('Base&nbsp;Cost', 'woothemes'); ?>&nbsp;<a class="tips" tip="<?php _e('Cost before discounts', 'woothemes'); ?> <?php echo $woocommerce->countries->ex_tax_or_vat(); ?>. <?php _e('Up to 4 decimals are allowed for precision.', 'woothemes'); ?>" href="#">[?]</a></th>
					<th class="cost"><?php _e('Cost', 'woothemes'); ?>&nbsp;<a class="tips" tip="<?php _e('Final cost after discount', 'woothemes'); ?> <?php echo $woocommerce->countries->ex_tax_or_vat(); ?>. <?php _e('Up to 4 decimals are allowed for precision.', 'woothemes'); ?>" href="#">[?]</a></th>
					<th class="tax"><?php _e('Tax Rate', 'woothemes'); ?></th>
					<th class="center" width="1%"><?php _e('Remove', 'woothemes'); ?></th>
				</tr>
			</thead>
			<tbody id="order_items_list">	
				
				<?php $loop = 0; if (sizeof($order_items)>0 && isset($order_items[0]['id'])) foreach ($order_items as $item) : 
				
					if (isset($item['variation_id']) && $item['variation_id'] > 0) :
						$_product = &new woocommerce_product_variation( $item['variation_id'] );
					else :
						$_product = &new woocommerce_product( $item['id'] );
					endif;

					?>
					<tr class="item" rel="<?php echo $loop; ?>">
						<td class="product-id">
							<img class="tips" tip="<?php
								echo '<strong>'.__('Product ID:', 'woothemes').'</strong> '. $item['id'];
								echo '<br/><strong>'.__('Variation ID:', 'woothemes').'</strong> '; if ($item['variation_id']) echo $item['variation_id']; else echo '-';
								echo '<br/><strong>'.__('Product SKU:', 'woothemes').'</strong> '; if ($_product->sku) echo $_product->sku; else echo '-';
							?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/tip.png" />
						</td>
						<td class="sku"><?php if ($_product->sku) echo $_product->sku; else echo '-'; ?></td>
						<td class="name">
							<a href="<?php echo esc_url( admin_url('post.php?post='. $_product->id .'&action=edit') ); ?>"><?php echo $item['name']; ?></a>
							<?php
								if (isset($_product->variation_data)) echo '<br/>' . woocommerce_get_formatted_variation( $_product->variation_data, true );
							?>
						</td>
						<td>
							<table class="meta" cellspacing="0">
								<tfoot>
									<tr>
										<td colspan="4"><button class="add_meta button"><?php _e('Add&nbsp;meta', 'woothemes'); ?></button></td>
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
	
											echo '<tr><td><input type="text" name="meta_name['.$loop.'][]" value="'.$meta_name.'" /></td><td><input type="text" name="meta_value['.$loop.'][]" value="'.esc_attr( $meta_value ).'" /></td><td><button class="remove_meta button">&times;</button></td></tr>';
										endforeach;
									endif;
								?>
								</tbody>
							</table>
						</td>
						<?php do_action('woocommerce_admin_order_item_values', $_product, $item); ?>
						
						<td class="quantity">
								<input type="text" name="item_quantity[<?php echo $loop; ?>]" placeholder="<?php _e('0', 'woothemes'); ?>" value="<?php echo esc_attr( $item['qty'] ); ?>" />
						</td>
						
						<td class="cost">
								<input type="text" name="base_item_cost[<?php echo $loop; ?>]" placeholder="<?php _e('0.00', 'woothemes'); ?>" value="<?php if (isset($item['base_cost'])) echo esc_attr( $item['base_cost'] ); else echo esc_attr( $item['cost'] ); ?>" />
						</td>
						
						<td class="cost">
								<input type="text" name="item_cost[<?php echo $loop; ?>]" placeholder="<?php _e('0.00', 'woothemes'); ?>" value="<?php echo esc_attr( $item['cost'] ); ?>" />
						</td>
						
						<td class="tax">
								<input type="text" name="item_tax_rate[<?php echo $loop; ?>]" placeholder="<?php _e('0.0000', 'woothemes'); ?>" value="<?php echo esc_attr( $item['taxrate'] ); ?>" />
						</td>
						
						<td class="center">
							<button type="button" class="remove_row button">&times;</button>
							<input type="hidden" name="item_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $item['id'] ); ?>" />
							<input type="hidden" name="item_name[<?php echo $loop; ?>]" value="<?php echo esc_attr( $item['name'] ); ?>" />
							<input type="hidden" name="item_variation[<?php echo $loop; ?>]" value="<?php echo esc_attr( $item['variation_id'] ); ?>" />
						</td>
						
					</tr>
				<?php $loop++; endforeach; ?>
			</tbody>
		</table>
	</div>
	
	<p class="buttons">
		<select name="add_item_id" class="add_item_id chosen_select_nostd" data-placeholder="<?php _e('Choose an item&hellip;', 'woothemes') ?>">
			<?php
				echo '<option value=""></option>';
				
				$args = array(
					'post_type' 		=> 'product',
					'posts_per_page' 	=> -1,
					'post_status'		=> 'publish',
					'post_parent'		=> 0,
					'order'				=> 'ASC',
					'orderby'			=> 'title'
				);
				$products = get_posts( $args );
				
				if ($products) foreach ($products as $product) :
					
					$sku = get_post_meta($product->ID, 'sku', true);
					
					if ($sku) $sku = ' SKU: '.$sku;
					
					echo '<option value="'.$product->ID.'">'.$product->post_title.$sku.' (#'.$product->ID.''.$sku.')</option>';
					
					$args_get_children = array(
						'post_type' => array( 'product_variation', 'product' ),
						'posts_per_page' 	=> -1,
						'order'				=> 'ASC',
						'orderby'			=> 'title',
						'post_parent'		=> $product->ID
					);	
						
					if ( $children_products =& get_children( $args_get_children ) ) :
		
						foreach ($children_products as $child) :
							
							echo '<option value="'.$child->ID.'">&nbsp;&nbsp;&mdash;&nbsp;'.$child->post_title.'</option>';
							
						endforeach;
						
					endif;
					
				endforeach;
			?>
		</select>
		
		<button type="button" class="button button-primary add_shop_order_item"><?php _e('Add item', 'woothemes'); ?></button>
	</p>
	<p class="buttons buttons-alt">
		<button type="button" class="button button calc_totals"><?php _e('Calculate totals', 'woothemes'); ?></button>
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
		<li><input type="submit" class="button button-primary tips" name="save" value="<?php _e('Save Order', 'woothemes'); ?>" tip="<?php _e('Save/update the order', 'woothemes'); ?>" /></li>

		<li><input type="submit" class="button tips" name="reduce_stock" value="<?php _e('Reduce stock', 'woothemes'); ?>" tip="<?php _e('Reduces stock for each item in the order; useful after manually creating an order or manually marking an order as paid.', 'woothemes'); ?>" /></li>
		
		<li><input type="submit" class="button tips" name="restore_stock" value="<?php _e('Restore stock', 'woothemes'); ?>" tip="<?php _e('Restores stock for each item in the order; useful after refunding or canceling the entire order.', 'woothemes'); ?>" /></li>
		
		<li><input type="submit" class="button tips" name="invoice" value="<?php _e('Email invoice', 'woothemes'); ?>" tip="<?php _e('Email the order to the customer. Unpaid orders will include a payment link.', 'woothemes'); ?>" /></li>
		
		<?php do_action('woocommerce_order_actions', $post->ID); ?>
		
		<li class="wide">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) ) {
			if ( !EMPTY_TRASH_DAYS )
				$delete_text = __('Delete Permanently', 'woothemes');
			else
				$delete_text = __('Move to Trash', 'woothemes');
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
	
	$data = get_post_custom( $post->ID );
	?>
	<ul class="totals">
		<li>
			<label><?php _e('Subtotal:', 'woothemes'); ?></label>
			<input type="text" id="_order_subtotal" name="_order_subtotal" placeholder="0.00 <?php _e('(ex. tax)', 'woothemes'); ?>" value="<?php 
			if (isset($data['_order_subtotal'][0])) echo $data['_order_subtotal'][0]; 
			?>" class="first" />
		</li>
		
		<li>
			<label><?php _e('Shipping:', 'woothemes'); ?></label>
			<input type="text" id="_order_shipping" name="_order_shipping" placeholder="0.00 <?php _e('(ex. tax)', 'woothemes'); ?>" value="<?php 
			if (isset($data['_order_shipping'][0])) echo $data['_order_shipping'][0];
			?>" class="first" /> <input type="text" name="_shipping_method" id="_shipping_method" value="<?php 
			if (isset($data['_shipping_method'][0])) echo $data['_shipping_method'][0];
			?>" class="last" placeholder="<?php _e('Shipping method...', 'woothemes'); ?>" />
		</li>
		
		<li class="left">
			<label><?php _e('Shipping tax:', 'woothemes'); ?></label>
			<input type="text" id="_order_shipping_tax" name="_order_shipping_tax" placeholder="0.00" value="<?php 
			if (isset($data['_order_shipping_tax'][0])) echo $data['_order_shipping_tax'][0];
			?>" class="first" />
		</li>
		
		<li class="right">
			<label><?php _e('Tax:', 'woothemes'); ?></label>
			<input type="text" id="_order_tax" name="_order_tax" placeholder="0.00" value="<?php 
			if (isset($data['_order_tax'][0])) echo $data['_order_tax'][0];
			?>" class="first" />
		</li>
		
		<li class="left">
			<label><?php _e('Cart Discount:', 'woothemes'); ?></label>
			<input type="text" id="_cart_discount" name="_cart_discount" placeholder="0.00" value="<?php 
			if (isset($data['_cart_discount'][0])) echo $data['_cart_discount'][0];
			?>" />
		</li>
		
		<li class="right">
			<label><?php _e('Order Discount:', 'woothemes'); ?></label>
			<input type="text" id="_order_discount" name="_order_discount" placeholder="0.00" value="<?php 
			if (isset($data['_order_discount'][0])) echo $data['_order_discount'][0];
			?>" />
		</li>
		
		<li>
			<label><?php _e('Order Total:', 'woothemes'); ?></label>
			<input type="text" id="_order_total" name="_order_total" placeholder="0.00" value="<?php 
			if (isset($data['_order_total'][0])) echo $data['_order_total'][0];
			?>" class="first" /> <input type="text" name="_payment_method" id="_payment_method" value="<?php 
				if (isset($data['_payment_method'][0])) echo $data['_payment_method'][0];
			?>" class="last" placeholder="<?php _e('Payment method...', 'woothemes'); ?>" />
		</li>	
	</ul>
	<div class="clear"></div>
	<?php
}

/**
 * Order Data Save
 * 
 * Function for processing and storing all order data.
 */
add_action('woocommerce_process_shop_order_meta', 'woocommerce_process_shop_order_meta', 1, 2);

function woocommerce_process_shop_order_meta( $post_id, $post ) {
	global $wpdb;
	
	$woocommerce_errors = array();
	
	// Add key
		add_post_meta( $post_id, '_order_key', uniqid('order_') );

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
		update_post_meta( $post_id, '_shipping_method', stripslashes( $_POST['_shipping_method'] ));
		update_post_meta( $post_id, '_payment_method', stripslashes( $_POST['_payment_method'] ));
		update_post_meta( $post_id, '_order_subtotal', stripslashes( $_POST['_order_subtotal'] ));
		update_post_meta( $post_id, '_order_shipping', stripslashes( $_POST['_order_shipping'] ));
		update_post_meta( $post_id, '_cart_discount', stripslashes( $_POST['_cart_discount'] ));
		update_post_meta( $post_id, '_order_discount', stripslashes( $_POST['_order_discount'] ));
		update_post_meta( $post_id, '_order_tax', stripslashes( $_POST['_order_tax'] ));
		update_post_meta( $post_id, '_order_shipping_tax', stripslashes( $_POST['_order_shipping_tax'] ));
		update_post_meta( $post_id, '_order_total', stripslashes( $_POST['_order_total'] ));
		update_post_meta( $post_id, '_customer_user', (int) $_POST['customer_user'] );
	
	// Order items
		$order_items = array();
	
		if (isset($_POST['item_id'])) :
			 $item_id			= $_POST['item_id'];
			 $item_variation	= $_POST['item_variation'];
			 $item_name 		= $_POST['item_name'];
			 $item_quantity 	= $_POST['item_quantity'];
			 $item_cost 		= $_POST['item_cost'];
			 $base_item_cost	= $_POST['base_item_cost'];
			 $item_tax_rate 	= $_POST['item_tax_rate'];
			 $item_meta_names 	= $_POST['meta_name'];
			 $item_meta_values 	= $_POST['meta_value'];
	
			 for ($i=0; $i<sizeof($item_id); $i++) :
			 	
			 	if (!isset($item_id[$i]) || !$item_id[$i]) continue;
			 	if (!isset($item_name[$i])) continue;
			 	if (!isset($item_quantity[$i]) || $item_quantity[$i] < 1) continue;
			 	if (!isset($item_cost[$i])) continue;
			 	if (!isset($item_tax_rate[$i])) continue;
			 	
			 	// Meta
			 	$item_meta 		= &new order_item_meta();
			 	$meta_names 	= $item_meta_names[$i];
			 	$meta_values 	= $item_meta_values[$i];
			 	
			 	for ($ii=0; $ii<sizeof($meta_names); $ii++) :
			 		$meta_name 		= esc_attr( $meta_names[$ii] );
			 		$meta_value 	= esc_attr( $meta_values[$ii] );
			 		if ($meta_name && $meta_value) :
			 			$item_meta->add( $meta_name, $meta_value );
			 		endif;
			 	endfor;
			 	
			 	// Add to array	 	
			 	$order_items[] = apply_filters('update_order_item', array(
			 		'id' 			=> htmlspecialchars(stripslashes($item_id[$i])),
			 		'variation_id' 	=> (int) $item_variation[$i],
			 		'name' 			=> htmlspecialchars(stripslashes($item_name[$i])),
			 		'qty' 			=> (int) $item_quantity[$i],
			 		'cost' 			=> rtrim(rtrim(number_format(woocommerce_clean($item_cost[$i]), 4, '.', ''), '0'), '.'),
			 		'base_cost'		=> rtrim(rtrim(number_format(woocommerce_clean($base_item_cost[$i]), 4, '.', ''), '0'), '.'),
			 		'taxrate' 		=> rtrim(rtrim(number_format(woocommerce_clean($item_tax_rate[$i]), 4, '.', ''), '0'), '.'),
			 		'item_meta'		=> $item_meta->meta
			 	));
			 	
			 endfor; 
		endif;	
	
		update_post_meta( $post_id, '_order_items', $order_items );

	// Give a password - not used, but can protect the content/comments from theme functions
		if ($post->post_password=='') :
			$order_post = array();
			$order_post['ID'] = $post_id;
			$order_post['post_password'] = uniqid('order_');
			wp_update_post( $order_post );
		endif;
		
	// Order data saved, now get it so we can manipulate status
		$order = &new woocommerce_order( $post_id );
		
	// Order status
		$order->update_status( $_POST['order_status'] );
	
	// Handle button actions
	
		if (isset($_POST['reduce_stock']) && $_POST['reduce_stock'] && sizeof($order_items)>0) :
			
			$order->add_order_note( __('Manually reducing stock.', 'woothemes') );
			
			foreach ($order_items as $order_item) :
						
				$_product = $order->get_product_from_item( $order_item );
				
				if ($_product->exists) :
				
				 	if ($_product->managing_stock()) :
						
						$old_stock = $_product->stock;
						
						$new_quantity = $_product->reduce_stock( $order_item['qty'] );
						
						$order->add_order_note( sprintf( __('Item #%s stock reduced from %s to %s.', 'woothemes'), $order_item['id'], $old_stock, $new_quantity) );
							
						if ($new_quantity<0) :
							do_action('woocommerce_product_on_backorder_notification', $order_item['id'], $values['quantity']);
						endif;
						
						// stock status notifications
						if (get_option('woocommerce_notify_no_stock_amount') && get_option('woocommerce_notify_no_stock_amount')>=$new_quantity) :
							do_action('woocommerce_no_stock_notification', $order_item['id']);
						elseif (get_option('woocommerce_notify_low_stock_amount') && get_option('woocommerce_notify_low_stock_amount')>=$new_quantity) :
							do_action('woocommerce_low_stock_notification', $order_item['id']);
						endif;
						
					endif;
				
				else :
					
					$order->add_order_note( sprintf( __('Item %s %s not found, skipping.', 'woothemes'), $order_item['id'], $order_item['name'] ) );
					
				endif;
			 	
			endforeach;
			
			$order->add_order_note( __('Manual stock reduction complete.', 'woothemes') );
			
		elseif (isset($_POST['restore_stock']) && $_POST['restore_stock'] && sizeof($order_items)>0) :
		
			$order->add_order_note( __('Manually restoring stock.', 'woothemes') );
			
			foreach ($order_items as $order_item) :
						
				$_product = $order->get_product_from_item( $order_item );
				
				if ($_product->exists) :
				
				 	if ($_product->managing_stock()) :
						
						$old_stock = $_product->stock;
						
						$new_quantity = $_product->increase_stock( $order_item['qty'] );
						
						$order->add_order_note( sprintf( __('Item #%s stock increased from %s to %s.', 'woothemes'), $order_item['id'], $old_stock, $new_quantity) );
						
					endif;
				
				else :
					
					$order->add_order_note( sprintf( __('Item %s %s not found, skipping.', 'woothemes'), $order_item['id'], $order_item['name'] ) );
					
				endif;
			 	
			endforeach;
			
			$order->add_order_note( __('Manual stock restore complete.', 'woothemes') );
		
		elseif (isset($_POST['invoice']) && $_POST['invoice']) :
			
			// Mail link to customer
			woocommerce_pay_for_order_customer_notification( $order );
			
		endif;
	
	// Error Handling
		if (sizeof($woocommerce_errors)>0) update_option('woocommerce_errors', $woocommerce_errors);
}