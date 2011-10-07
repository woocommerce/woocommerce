<?php
/**
 * Order
 * 
 * The WooCommerce order class handles order data.
 *
 * @class woocommerce_order
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_order {
	
	private $_data = array();
	
	public function __get($variable) {
		return isset($this->_data[$variable]) ? $this->_data[$variable] : null;
	}
	
	public function __set($variable, $value) {
		$this->_data[$variable] = $value;
	} 
	
	/** Get the order if ID is passed, otherwise the order is new and empty */
	function woocommerce_order( $id='' ) {
		if ($id>0) $this->get_order( $id );
	}
	
	/** Gets an order from the database */
	function get_order( $id = 0 ) {
		if (!$id) return false;
		if ($result = get_post( $id )) : 	 	  	 	
			$this->populate( $result );	 	 	 	 	 	
			return true;
		endif;
		return false;
	}
	
	/** Populates an order from the loaded post data */
	function populate( $result ) {
		global $woocommerce;
		
		// Standard post data
		$this->id = $result->ID; 
		$this->order_date = $result->post_date;
		$this->modified_date = $result->post_modified;	
		$this->customer_note = $result->post_excerpt;
		
		// Custom fields
		$this->items 				= (array) get_post_meta( $this->id, '_order_items', true );
		$this->user_id 				= (int) get_post_meta( $this->id, '_customer_user', true );
		$this->completed_date		= get_post_meta( $this->id, '_completed_date', true );
		
		if (!$this->completed_date) $this->completed_date = $this->modified_date;
		
		$order_custom_fields = get_post_custom( $this->id );
		
		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'order_key'				=> '',
			'billing_first_name'	=> '',
			'billing_last_name' 	=> '',
			'billing_company'		=> '',
			'billing_address_1'		=> '',
			'billing_address_2'		=> '',
			'billing_city'			=> '',
			'billing_postcode'		=> '',
			'billing_country'		=> '',
			'billing_state' 		=> '',
			'billing_email'			=> '',
			'billing_phone'			=> '',
			'shipping_first_name'	=> '',
			'shipping_last_name'	=> '',
			'shipping_company'		=> '',
			'shipping_address_1'	=> '',
			'shipping_address_2'	=> '',
			'shipping_city'			=> '',
			'shipping_postcode'		=> '',
			'shipping_country'		=> '',
			'shipping_state'		=> '',
			'shipping_method'		=> '',
			'payment_method'		=> '',
			'order_subtotal'		=> '',
			'order_discount'		=> '',
			'order_tax'				=> '',
			'order_shipping'		=> '',
			'order_shipping_tax'	=> '',
			'order_total'			=> ''
		);
		
		// Load the data from the custom fields
		foreach ($load_data as $key => $default) :
			if (isset($order_custom_fields[ '_' . $key ][0]) && $order_custom_fields[ '_' . $key ][0]!=='') :
				$this->$key = $order_custom_fields[ '_' . $key ][0];
			else :
				$this->$key = $default;
			endif;
		endforeach;
	
		// Formatted Addresses
		$formatted_address = array();
		$country = ($this->billing_country && isset($woocommerce->countries->countries[$this->billing_country])) ? $woocommerce->countries->countries[$this->billing_country] : $this->billing_country;
		$address =  array_map('trim', array(
			$this->billing_address_1,
			$this->billing_address_2,
			$this->billing_city,						
			$this->billing_state,
			$this->billing_postcode,
			$country
		));
		foreach ($address as $part) if (!empty($part)) $formatted_address[] = $part;
		$this->formatted_billing_address = implode(', ', $formatted_address);
		
		if ($this->shipping_address_1) :
			$formatted_address = array();
			$country = ($this->shipping_country && isset($woocommerce->countries->countries[$this->shipping_country])) ? $woocommerce->countries->countries[$this->shipping_country] : $this->shipping_country;
			$address = array_map('trim', array(
				$this->shipping_address_1,
				$this->shipping_address_2,
				$this->shipping_city,						
				$this->shipping_state,
				$this->shipping_postcode,
				$country
			));
			foreach ($address as $part) if (!empty($part)) $formatted_address[] = $part;
			$this->formatted_shipping_address = implode(', ', $formatted_address);
		endif;
		
		// Taxonomy data 
		$terms = wp_get_object_terms( $this->id, 'shop_order_status' );
		if (!is_wp_error($terms) && $terms) :
			$term = current($terms);
			$this->status = $term->slug; 
		else :
			$this->status = 'pending';
		endif;
			
	}
	
	/** Gets shipping and product tax */
	function get_total_tax() {
		return $this->order_tax + $this->order_shipping_tax;
	}
	
	/** Gets subtotal */
	function get_subtotal_to_display() {
		
		$subtotal = woocommerce_price($this->order_subtotal);
		
		if ($this->order_tax>0) :
			$subtotal .= __(' <small>(ex. tax)</small>', 'woothemes');
		endif;
		
		return $subtotal;
	}
	
	/** Gets shipping */
	function get_shipping_to_display() {
		
		if ($this->order_shipping > 0) :

			$shipping = woocommerce_price($this->order_shipping);
			if ($this->order_shipping_tax > 0) :
				$tax_text = __('(ex. tax) ', 'woothemes'); 
			else :
				$tax_text = '';
			endif;
			$shipping .= sprintf(__(' <small>%svia %s</small>', 'woothemes'), $tax_text, ucwords($this->shipping_method));

		else :
			$shipping = __('Free!', 'woothemes');
		endif;
		
		return $shipping;
	}
	
	/** Get a product (either product or variation) */
	function get_product_from_item( $item ) {
		
		if (isset($item['variation_id']) && $item['variation_id']>0) :
			$_product = &new woocommerce_product_variation( $item['variation_id'] );
		else :
			$_product = &new woocommerce_product( $item['id'] );
		endif;
		
		return $_product;

	}
	
	/** Output items for display in emails */
	function email_order_items_list( $show_download_links = false, $show_sku = false ) {
		
		$return = '';
		
		foreach($this->items as $item) : 
			
			$_product = $this->get_product_from_item( $item );

			$return .= $item['qty'] . ' x ' . apply_filters('woocommerce_order_product_title', $item['name'], $_product);
			
			if ($show_sku) :
				
				$return .= ' (#' . $_product->sku . ')';
				
			endif;
			
			$return .= ' - ' . strip_tags(woocommerce_price( $item['cost']*$item['qty'], array('ex_tax_label' => 1 )));
			
			if (isset($_product->variation_data)) :
				$return .= PHP_EOL . woocommerce_get_formatted_variation( $item['item_meta'], true );
			endif;
			
			if ($show_download_links) :
				
				if ($_product->exists) :
			
					if ($_product->is_type('downloadable')) :
						$return .= PHP_EOL . ' - ' . $this->get_downloadable_file_url( $item['id'] ) . '';
					endif;
		
				endif;	
					
			endif;
			
			$return .= PHP_EOL;
			
		endforeach;	
		
		return $return;	
	}
	
	/** Output items for display in html emails */
	function email_order_items_table( $show_download_links = false, $show_sku = false ) {

		$return = '';
		
		foreach($this->items as $item) : 
			
			$_product = $this->get_product_from_item( $item );
			
			$file = $sku = $variation = '';
			
			if ($show_sku) :
				$sku = ' (#' . $_product->sku . ')';
			endif;
			
			if (isset($item['item_meta'])) :
				$variation = '<br/>' . woocommerce_get_formatted_variation( $item['item_meta'], true );
			endif;
			
			if ($show_download_links) :
				
				if ($_product->exists) :
			
					if ($_product->is_type('downloadable')) :
						$file = '<br/>' . $this->get_downloadable_file_url( $item['id'] ) . '';
					endif;
		
				endif;	
					
			endif;
			
			$return .= '<tr>
				<td style="text-align:left;">' . apply_filters('woocommerce_order_product_title', $item['name'], $_product) . $sku . $file . $variation . '</td>
				<td style="text-align:left;">'.$item['qty'].'</td>
				<td style="text-align:left;">'.strip_tags(woocommerce_price( $item['cost']*$item['qty'], array('ex_tax_label' => 1 ))).'</td>
			</tr>';
			
		endforeach;	
		
		return $return;	
		
	}
	
	/**  Generates a URL so that a customer can checkout/pay for their (unpaid - pending) order via a link */
	function get_checkout_payment_url() {
		
		$payment_page = get_permalink(get_option('woocommerce_pay_page_id'));
		
		if (get_option('woocommerce_force_ssl_checkout')=='yes' || is_ssl()) $payment_page = str_replace('http:', 'https:', $payment_page);
	
		return add_query_arg('pay_for_order', 'true', add_query_arg('order', $this->order_key, add_query_arg('order_id', $this->id, $payment_page)));
	}
	
	
	/** Generates a URL so that a customer can cancel their (unpaid - pending) order */
	function get_cancel_order_url() {
		global $woocommerce;
		return $woocommerce->nonce_url( 'cancel_order', add_query_arg('cancel_order', 'true', add_query_arg('order', $this->order_key, add_query_arg('order_id', $this->id, trailingslashit( home_url() )))));
	}
	
	
	/** Gets a downloadable products file url */
	function get_downloadable_file_url( $item_id ) {
	 	
	 	$user_email = $this->billing_email;
				
		if ($this->user_id>0) :
			$user_info = get_userdata($this->user_id);
			if ($user_info->user_email) :
				$user_email = $user_info->user_email;
			endif;
		endif;
				
	 	return add_query_arg('download_file', $item_id, add_query_arg('order', $this->order_key, add_query_arg('email', $user_email, trailingslashit( home_url() ))));
	 }
	 
	/**
	 * Adds a note (comment) to the order
	 *
	 * @param   string	$note		Note to add
	 * @param   int		$is_customer_note	Is this a note for the customer?
	 */
	function add_order_note( $note, $is_customer_note = 0 ) {
		
		$comment_post_ID = $this->id;
		$comment_author = 'WooCommerce';
		$comment_author_email = 'woocommerce@' . str_replace('www.', '', str_replace('http://', '', site_url()));
		$comment_author_url = '';
		$comment_content = $note;
		$comment_type = '';
		$comment_parent = 0;
		
		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');
		
		$commentdata['comment_author_IP'] = preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] );
		$commentdata['comment_agent']     = substr($_SERVER['HTTP_USER_AGENT'], 0, 254);
	
		$commentdata['comment_date']     = current_time('mysql');
		$commentdata['comment_date_gmt'] = current_time('mysql', 1);
	
		$comment_id = wp_insert_comment( $commentdata );
		
		add_comment_meta($comment_id, 'is_customer_note', $is_customer_note);
		
		return $comment_id;
		
	}

	/**
	 * Adds a note (comment) to the order
	 *
	 * @param   string	$new_status		Status to change the order to
	 * @param   string	$note			Optional note to add
	 */
	function update_status( $new_status, $note = '' ) {
		
		if ($note) $note .= ' ';
	
		$new_status = get_term_by( 'slug', sanitize_title( $new_status ), 'shop_order_status');
		if ($new_status) :
		
			wp_set_object_terms($this->id, $new_status->slug, 'shop_order_status');
			
			if ( $this->status != $new_status->slug ) :
				// Status was changed
				do_action( 'woocommerce_order_status_'.$new_status->slug, $this->id );
				do_action( 'woocommerce_order_status_'.$this->status.'_to_'.$new_status->slug, $this->id );
				$this->add_order_note( $note . sprintf( __('Order status changed from %s to %s.', 'woothemes'), $this->status, $new_status->slug ) );
				clean_term_cache( '', 'shop_order_status' );
				
				// Date
				if ($new_status->slug=='completed') update_post_meta( $this->id, '_completed_date', current_time('mysql') );
			endif;
		
		endif;
		
	}
	
	/**
	 * Cancel the order and restore the cart (before payment)
	 *
	 * @param   string	$note	Optional note to add
	 */
	function cancel_order( $note = '' ) {
		
		unset($_SESSION['order_awaiting_payment']);
		
		$this->update_status('cancelled', $note);
		
	}

	/**
	 * When a payment is complete this function is called
	 *
	 * Most of the time this should mark an order as 'processing' so that admin can process/post the items
	 * If the cart contains only downloadable items then the order is 'complete' since the admin needs to take no action
	 * Stock levels are reduced at this point
	 */
	function payment_complete() {
		
		unset($_SESSION['order_awaiting_payment']);
		
		$downloadable_order = false;
		
		if (sizeof($this->items)>0) foreach ($this->items as $item) :
		
			if ($item['id']>0) :
				$_product = $this->get_product_from_item( $item );
				
				if ( $_product->exists && $_product->is_type('downloadable') ) :
					$downloadable_order = true;
					continue;
				endif;
				
			endif;
			
			$downloadable_order = false;
			break;
		
		endforeach;
		
		if ($downloadable_order) :
			$new_order_status = 'completed';
		else :
			$new_order_status = 'processing';
		endif;
		
		$new_order_status = apply_filters('woocommerce_payment_complete_order_status', $new_order_status, $this->id);
		
		$this->update_status($new_order_status);
		
		// Payment is complete so reduce stock levels
		$this->reduce_order_stock();
		
	}
	
	/**
	 * Reduce stock levels
	 */
	function reduce_order_stock() {
		
		// Reduce stock levels and do any other actions with products in the cart
		if (sizeof($this->items)>0) foreach ($this->items as $item) :
		
			if ($item['id']>0) :
				$_product = $this->get_product_from_item( $item );
				
				if ( $_product->exists && $_product->managing_stock() ) :
				
					$old_stock = $_product->stock;
					
					$new_quantity = $_product->reduce_stock( $item['qty'] );
					
					$this->add_order_note( sprintf( __('Item #%s stock reduced from %s to %s.', 'woothemes'), $item['id'], $old_stock, $new_quantity) );
						
					if ($new_quantity<0) :
						do_action('woocommerce_product_on_backorder_notification', $item['id'], $item['qty']);
					endif;
					
					// stock status notifications
					if (get_option('woocommerce_notify_no_stock_amount') && get_option('woocommerce_notify_no_stock_amount')>=$new_quantity) :
						do_action('woocommerce_no_stock_notification', $item['id']);
					elseif (get_option('woocommerce_notify_low_stock_amount') && get_option('woocommerce_notify_low_stock_amount')>=$new_quantity) :
						do_action('woocommerce_low_stock_notification', $item['id']);
					endif;
					
				endif;
				
			endif;
		 	
		endforeach;
		
		$this->add_order_note( __('Order item stock reduced successfully.', 'woothemes') );
			
	}
	
	/**
	 * List order notes (public) for the customer
	 */
	function get_customer_order_notes() {
		
		$notes = array();
		
		$args = array(
			'post_id' => $this->id,
			'approve' => 'approve',
			'type' => ''
		);
		
		remove_filter('comments_clauses', 'woocommerce_exclude_order_comments');
		
		$comments = get_comments( $args );
		
		foreach ($comments as $comment) :
			$is_customer_note = get_comment_meta($comment->comment_ID, 'is_customer_note', true);
			if ($is_customer_note) $notes[] = $comment;
		endforeach;
		
		add_filter('comments_clauses', 'woocommerce_exclude_order_comments');
		
		return (array) $notes;
		
	}

}