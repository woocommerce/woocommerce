<?php
/**
 * Order Data Save
 * 
 * Function for processing and storing all order data.
 *
 * @author 		Jigowatt
 * @category 	Admin Write Panels
 * @package 	JigoShop
 */
 
add_action('jigoshop_process_shop_order_meta', 'jigoshop_process_shop_order_meta', 1, 2);

function jigoshop_process_shop_order_meta( $post_id, $post ) {
	global $wpdb;
	
	$jigoshop_errors = array();
	
	$order = &new jigoshop_order($post_id);
	
	// Get old data + attributes
		$data = (array) maybe_unserialize( get_post_meta($post_id, 'order_data', true) );
	
	// Add/Replace data to array
		$data['billing_first_name'] 	= stripslashes( $_POST['billing_first_name'] );
		$data['billing_last_name'] 		= stripslashes( $_POST['billing_last_name'] );
		$data['billing_company'] 		= stripslashes( $_POST['billing_company'] );
		$data['billing_address_1'] 		= stripslashes( $_POST['billing_address_1'] );
		$data['billing_address_2']		= stripslashes( $_POST['billing_address_2'] );
		$data['billing_city']			= stripslashes( $_POST['billing_city'] );
		$data['billing_postcode'] 		= stripslashes( $_POST['billing_postcode'] );
		$data['billing_country']		= stripslashes( $_POST['billing_country'] );
		$data['billing_state'] 			= stripslashes( $_POST['billing_state'] );
		$data['billing_email']			= stripslashes( $_POST['billing_email'] );
		$data['billing_phone'] 			= stripslashes( $_POST['billing_phone'] );
		$data['shipping_first_name']	= stripslashes( $_POST['shipping_first_name'] );
		$data['shipping_last_name'] 	= stripslashes( $_POST['shipping_last_name'] );
		$data['shipping_company'] 		= stripslashes( $_POST['shipping_company'] );
		$data['shipping_address_1'] 	= stripslashes( $_POST['shipping_address_1'] );
		$data['shipping_address_2'] 	= stripslashes( $_POST['shipping_address_2'] );
		$data['shipping_city'] 			= stripslashes( $_POST['shipping_city'] );
		$data['shipping_postcode'] 		= stripslashes( $_POST['shipping_postcode'] );
		$data['shipping_country'] 		= stripslashes( $_POST['shipping_country'] );
		$data['shipping_state'] 		= stripslashes( $_POST['shipping_state'] );
		$data['shipping_method']		= stripslashes( $_POST['shipping_method'] );
		$data['payment_method'] 		= stripslashes( $_POST['payment_method'] );
		$data['order_subtotal'] 		= stripslashes( $_POST['order_subtotal'] );
		$data['order_shipping']			= stripslashes( $_POST['order_shipping'] );
		$data['order_discount'] 		= stripslashes( $_POST['order_discount'] );
		$data['order_tax'] 				= stripslashes( $_POST['order_tax'] );
		$data['order_shipping_tax'] 	= stripslashes( $_POST['order_shipping_tax'] );
		$data['order_total'] 			= stripslashes( $_POST['order_total'] );
	
	// Customer
		update_post_meta( $post_id, 'customer_user', (int) $_POST['customer_user'] );
	
	// Order status
		$order->update_status( $_POST['order_status'] );
	
	// Order items
		$order_items = array();
	
		if (isset($_POST['item_id'])) :
			 $item_id		= $_POST['item_id'];
			 $item_variation= $_POST['item_variation'];
			 $item_name 	= $_POST['item_name'];
			 $item_quantity = $_POST['item_quantity'];
			 $item_cost 	= $_POST['item_cost'];
			 $item_tax_rate = $_POST['item_tax_rate'];
	
			 for ($i=0; $i<sizeof($item_id); $i++) :
			 	
			 	if (!isset($item_id[$i])) continue;
			 	if (!isset($item_name[$i])) continue;
			 	if (!isset($item_quantity[$i])) continue;
			 	if (!isset($item_cost[$i])) continue;
			 	if (!isset($item_tax_rate[$i])) continue;
			 	
			 	$order_items[] = apply_filters('update_order_item', array(
			 		'id' 			=> htmlspecialchars(stripslashes($item_id[$i])),
			 		'variation_id' 	=> (int) $item_variation[$i],
			 		'name' 			=> htmlspecialchars(stripslashes($item_name[$i])),
			 		'qty' 			=> (int) $item_quantity[$i],
			 		'cost' 			=> number_format(jigowatt_clean($item_cost[$i]), 2),
			 		'taxrate' 		=> number_format(jigowatt_clean($item_tax_rate[$i]), 4)
			 	));
			 	
			 endfor; 
		endif;	
	
	// Save
		update_post_meta( $post_id, 'order_data', $data );
		update_post_meta( $post_id, 'order_items', $order_items );
	
	
	// Handle button actions
	
		if (isset($_POST['reduce_stock']) && $_POST['reduce_stock'] && sizeof($order_items)>0) :
			
			$order->add_order_note( __('Manually reducing stock.', 'jigoshop') );
			
			foreach ($order_items as $order_item) :
						
				$_product = $order->get_product_from_item( $order_item );
				
				if ($_product->exists) :
				
				 	if ($_product->managing_stock()) :
						
						$old_stock = $_product->stock;
						
						$new_quantity = $_product->reduce_stock( $order_item['qty'] );
						
						$order->add_order_note( sprintf( __('Item #%s stock reduced from %s to %s.', 'jigoshop'), $order_item['id'], $old_stock, $new_quantity) );
							
						if ($new_quantity<0) :
							do_action('jigoshop_product_on_backorder_notification', $order_item['id'], $values['quantity']);
						endif;
						
						// stock status notifications
						if (get_option('jigoshop_notify_no_stock_amount') && get_option('jigoshop_notify_no_stock_amount')>=$new_quantity) :
							do_action('jigoshop_no_stock_notification', $order_item['id']);
						elseif (get_option('jigoshop_notify_low_stock_amount') && get_option('jigoshop_notify_low_stock_amount')>=$new_quantity) :
							do_action('jigoshop_low_stock_notification', $order_item['id']);
						endif;
						
					endif;
				
				else :
					
					$order->add_order_note( sprintf( __('Item %s %s not found, skipping.', 'jigoshop'), $order_item['id'], $order_item['name'] ) );
					
				endif;
			 	
			endforeach;
			
			$order->add_order_note( __('Manual stock reduction complete.', 'jigoshop') );
			
		elseif (isset($_POST['restore_stock']) && $_POST['restore_stock'] && sizeof($order_items)>0) :
		
			$order->add_order_note( __('Manually restoring stock.', 'jigoshop') );
			
			foreach ($order_items as $order_item) :
						
				$_product = $order->get_product_from_item( $order_item );
				
				if ($_product->exists) :
				
				 	if ($_product->managing_stock()) :
						
						$old_stock = $_product->stock;
						
						$new_quantity = $_product->increase_stock( $order_item['qty'] );
						
						$order->add_order_note( sprintf( __('Item #%s stock increased from %s to %s.', 'jigoshop'), $order_item['id'], $old_stock, $new_quantity) );
						
					endif;
				
				else :
					
					$order->add_order_note( sprintf( __('Item %s %s not found, skipping.', 'jigoshop'), $order_item['id'], $order_item['name'] ) );
					
				endif;
			 	
			endforeach;
			
			$order->add_order_note( __('Manual stock restore complete.', 'jigoshop') );
		
		elseif (isset($_POST['invoice']) && $_POST['invoice']) :
			
			// Mail link to customer
			jigoshop_pay_for_order_customer_notification( $order->id );
			
		endif;
	
	// Error Handling
		if (sizeof($jigoshop_errors)>0) update_option('jigoshop_errors', $jigoshop_errors);
}