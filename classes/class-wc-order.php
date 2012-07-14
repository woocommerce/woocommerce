<?php
/**
 * Order
 * 
 * The WooCommerce order class handles order data.
 *
 * @class 		WC_Order
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class WC_Order {
	
	var $id;
	var $status;
	var $order_date;
	var $modified_date;
	var $customer_note;
	var $order_custom_fields;
	var $order_key;
	var $billing_first_name;
	var $billing_last_name;
	var $billing_company;
	var $billing_address_1;
	var $billing_address_2;
	var $billing_city;
	var $billing_postcode;
	var $billing_country;
	var $billing_state;
	var $billing_email;
	var $billing_phone;
	var $shipping_first_name;
	var $shipping_last_name;
	var $shipping_company;
	var $shipping_address_1;
	var $shipping_address_2;
	var $shipping_city;
	var $shipping_postcode;
	var $shipping_country;
	var $shipping_state;
	var $shipping_method;
	var $shipping_method_title;
	var $payment_method;
	var $payment_method_title;
	var $order_discount;
	var $cart_discount;
	var $order_tax;
	var $order_shipping;
	var $order_shipping_tax;
	var $order_total;
	var $items;
	var $taxes;
	var $customer_user;
	var $user_id;
	var $completed_date;
	var $billing_address;
	var $formatted_billing_address;
	var $shipping_address;
	var $formatted_shipping_address;
	
	/** Get the order if ID is passed, otherwise the order is new and empty */
	function __construct( $id = '' ) {
		$this->prices_include_tax = (get_option('woocommerce_prices_include_tax')=='yes') ? true : false;
		$this->display_totals_ex_tax = (get_option('woocommerce_display_totals_excluding_tax')=='yes') ? true : false;
		$this->display_cart_ex_tax = (get_option('woocommerce_display_cart_prices_excluding_tax')=='yes') ? true : false;
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
		// Standard post data
		$this->id = $result->ID; 
		$this->order_date = $result->post_date;
		$this->modified_date = $result->post_modified;	
		$this->customer_note = $result->post_excerpt;
		$this->order_custom_fields = get_post_custom( $this->id );
		
		// Define the data we're going to load: Key => Default value
		$load_data = apply_filters('woocommerce_load_order_data', array(
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
			'shipping_method_title'	=> '',
			'payment_method'		=> '',
			'payment_method_title' 	=> '',
			'order_discount'		=> '',
			'cart_discount'			=> '',
			'order_tax'				=> '',
			'order_shipping'		=> '',
			'order_shipping_tax'	=> '',
			'order_total'			=> '',
			'customer_user'			=> '',
			'completed_date'		=> $this->modified_date
		));
		
		// Load the data from the custom fields
		foreach ($load_data as $key => $default) :
			if (isset($this->order_custom_fields[ '_' . $key ][0]) && $this->order_custom_fields[ '_' . $key ][0]!=='') :
				$this->$key = $this->order_custom_fields[ '_' . $key ][0];
			else :
				$this->$key = $default;
			endif;
		endforeach;
		
		// Aliases
		$this->user_id = (int) $this->customer_user;
		
		// Get status
		$terms = wp_get_object_terms( $this->id, 'shop_order_status', array('fields' => 'slugs') );
		$this->status = (isset($terms[0])) ? $terms[0] : 'pending';
	}
	
	function key_is_valid( $key ) {
		if ($key==$this->order_key) return true; 
		return false;
	}
	
	
	/**
	 * get_order_number function.
	 *
	 * Gets the order number for display (by default, order ID)
	 * 
	 * @access public
	 * @return string
	 */
	function get_order_number() {
		return apply_filters( 'woocommerce_order_number', _x( '#', 'hash before order number', 'woocommerce' ) . $this->id, $this );
	}
	
	function get_formatted_billing_address() {
		if (!$this->formatted_billing_address) :
			global $woocommerce;
			
			// Formatted Addresses
			$address = array(
				'first_name' 	=> $this->billing_first_name,
				'last_name'		=> $this->billing_last_name,
				'company'		=> $this->billing_company,
				'address_1'		=> $this->billing_address_1,
				'address_2'		=> $this->billing_address_2,
				'city'			=> $this->billing_city,		
				'state'			=> $this->billing_state,
				'postcode'		=> $this->billing_postcode,
				'country'		=> $this->billing_country
			);
	
			$this->formatted_billing_address = $woocommerce->countries->get_formatted_address( $address );
		endif;
		return $this->formatted_billing_address;
	}
	
	function get_billing_address() {
		if (!$this->billing_address) :
			// Formatted Addresses
			$address = array(
				'address_1'		=> $this->billing_address_1,
				'address_2'		=> $this->billing_address_2,
				'city'			=> $this->billing_city,		
				'state'			=> $this->billing_state,
				'postcode'		=> $this->billing_postcode,
				'country'		=> $this->billing_country
			);
			$joined_address = array();
			foreach ($address as $part) if (!empty($part)) $joined_address[] = $part;
			$this->billing_address = implode(', ', $joined_address);
		endif;
		return $this->billing_address;
	}
	
	function get_formatted_shipping_address() {
		if (!$this->formatted_shipping_address) :
			if ($this->shipping_address_1) :
				global $woocommerce;
				
				// Formatted Addresses
				$address = array(
					'first_name' 	=> $this->shipping_first_name,
					'last_name'		=> $this->shipping_last_name,
					'company'		=> $this->shipping_company,
					'address_1'		=> $this->shipping_address_1,
					'address_2'		=> $this->shipping_address_2,
					'city'			=> $this->shipping_city,		
					'state'			=> $this->shipping_state,
					'postcode'		=> $this->shipping_postcode,
					'country'		=> $this->shipping_country
				);
		
				$this->formatted_shipping_address = $woocommerce->countries->get_formatted_address( $address );
			endif;
		endif;
		return $this->formatted_shipping_address;
	}
	
	function get_shipping_address() {
		if (!$this->shipping_address) :
			if ($this->shipping_address_1) :
				// Formatted Addresses
				$address = array(
					'address_1'		=> $this->shipping_address_1,
					'address_2'		=> $this->shipping_address_2,
					'city'			=> $this->shipping_city,		
					'state'			=> $this->shipping_state,
					'postcode'		=> $this->shipping_postcode,
					'country'		=> $this->shipping_country
				);
				$joined_address = array();
				foreach ($address as $part) if (!empty($part)) $joined_address[] = $part;
				$this->shipping_address = implode(', ', $joined_address);
			endif;
		endif;
		return $this->shipping_address;
	}
	
	function get_items() {
		if (!$this->items) :
			$this->items = isset( $this->order_custom_fields['_order_items'][0] ) ? maybe_unserialize( $this->order_custom_fields['_order_items'][0] ) : array();
		endif;
		return $this->items;
	}
	
	function get_taxes() {
		if (!$this->taxes) :
			$this->taxes = isset( $this->order_custom_fields['_order_taxes'][0] ) ? maybe_unserialize( $this->order_custom_fields['_order_taxes'][0] ) : array();
		endif;
		return $this->taxes;
	}


	/** Total Getters *******************************************************/

	/** Gets shipping and product tax */
	function get_total_tax() {
		return apply_filters( 'woocommerce_order_amount_total_tax', $this->order_tax + $this->order_shipping_tax );
	}
	
	/**
	 * gets the total (product) discount amount - these are applied before tax
	 */
	function get_cart_discount() {
		return apply_filters( 'woocommerce_order_amount_cart_discount', $this->cart_discount ); 
	}
	
	/**
	 * gets the total (product) discount amount - these are applied before tax
	 */
	function get_order_discount() {
		return apply_filters( 'woocommerce_order_amount_order_discount', $this->order_discount ); 
	}
	
	/**
	 * gets the total discount amount - both kinds
	 */
	function get_total_discount() {
		if ($this->order_discount || $this->cart_discount) :
			return apply_filters( 'woocommerce_order_amount_total_discount', $this->order_discount + $this->cart_discount ); 
		endif;
	}
	
	/** Gets shipping */
	function get_shipping() {
		return apply_filters( 'woocommerce_order_amount_shipping', $this->order_shipping );
	}
	
	/** Gets shipping tax amount */
	function get_shipping_tax() {
		return apply_filters( 'woocommerce_order_amount_shipping_tax', $this->order_shipping_tax );
	}
	
	/** Gets shipping method title */
	function get_shipping_method() {
		return apply_filters( 'woocommerce_order_shipping_method', ucwords( $this->shipping_method_title ) );
	}
	
	/** Gets order total */
	function get_total() {
		return apply_filters( 'woocommerce_order_amount_total', $this->order_total );
	}
		
	/** Get item subtotal - this is the cost before discount */
	function get_item_subtotal( $item, $inc_tax = false, $round = true ) {
		if ($inc_tax) :
			$price = ( $item['line_subtotal'] + $item['line_subtotal_tax'] / $item['qty'] );
		else :
			$price = ( $item['line_subtotal'] / $item['qty'] );
		endif;
		return apply_filters( 'woocommerce_order_amount_item_subtotal', ($round) ? number_format( $price, 2, '.', '') : $price );
	}
	
	/** Get line subtotal - this is the cost before discount */
	function get_line_subtotal( $item, $inc_tax = false, $round = true ) {
		if ( $inc_tax ) :
			$price = $item['line_subtotal'] + $item['line_subtotal_tax'];
		else :
			$price = $item['line_subtotal'];
		endif;
		return apply_filters( 'woocommerce_order_amount_line_subtotal', ($round) ? number_format( $price, 2, '.', '') : $price );
	}
	
	/** Calculate item cost - useful for gateways */
	function get_item_total( $item, $inc_tax = false, $round = true ) {
		if ($inc_tax) :
			$price = ( ( $item['line_total'] + $item['line_tax'] ) / $item['qty'] );
		else :
			$price = $item['line_total'] / $item['qty'];
		endif;
		return apply_filters( 'woocommerce_order_amount_item_total', ($round) ? number_format( $price, 2, '.', '') : $price );
	}
	
	/** Calculate item tax - useful for gateways */
	function get_item_tax( $item, $round = true ) {
		$price = $item['line_tax'] / $item['qty'];
		return apply_filters( 'woocommerce_order_amount_item_tax', ($round) ? number_format( $price, 2, '.', '') : $price );
	}
	
	/** Calculate line total - useful for gateways */
	function get_line_total( $item, $inc_tax = false ) {
		if ($inc_tax) :
			return apply_filters( 'woocommerce_order_amount_line_total', number_format( $item['line_total'] + $item['line_tax'] , 2, '.', '') );
		else :
			return apply_filters( 'woocommerce_order_amount_line_total', number_format( $item['line_total'] , 2, '.', '') );
		endif;
	}
	
	/** Calculate line tax - useful for gateways */
	function get_line_tax( $item ) {
		return apply_filters( 'woocommerce_order_amount_line_tax', number_format( $item['line_tax'], 2, '.', '') );
	}
	
	/** Depreciated functions */
	
	function get_order_total() {
		return apply_filters( 'woocommerce_order_amount_total', $this->order_total );
	}
	
	function get_item_cost( $item, $inc_tax = false ) {
		_deprecated_function( __FUNCTION__, '1.4', 'get_item_total()' );
		return $this->get_item_total( $item, $inc_tax );
	}
	
	function get_row_cost( $item, $inc_tax = false ) {
		_deprecated_function( __FUNCTION__, '1.4', 'get_row_cost()' );
		return $this->get_line_total( $item, $inc_tax );
	}
	
	/** End Total Getters *******************************************************/

	
	/** Gets line subtotal - formatted for display */
	function get_formatted_line_subtotal( $item ) {
		$subtotal = 0;
		
		if (!isset($item['line_subtotal']) || !isset($item['line_subtotal_tax'])) return;
		
		if ( $this->display_cart_ex_tax || ! $this->prices_include_tax ) :	
			if ( $this->prices_include_tax ) $ex_tax_label = 1; else $ex_tax_label = 0;
			$subtotal = woocommerce_price( $this->get_line_subtotal( $item ), array('ex_tax_label' => $ex_tax_label ) );
		else :
			$subtotal = woocommerce_price( $this->get_line_subtotal( $item, true ) );
		endif;

		return apply_filters( 'woocommerce_order_formatted_line_subtotal', $subtotal, $item, $this );
	}
	
	/** Gets order total - formatted for display */
	function get_formatted_order_total() {

		$formatted_total = woocommerce_price( $this->order_total );

		return apply_filters( 'woocommerce_get_formatted_order_total', $formatted_total, $this );
	}
	
	/** Gets subtotal - subtotal is shown before discounts, but with localised taxes */
	function get_subtotal_to_display( $compound = false ) {
		global $woocommerce;
		
		$subtotal = 0;
		
		if ( ! $compound ) :

			foreach ($this->get_items() as $item) :
				
				if ( ! isset( $item['line_subtotal'] ) || ! isset( $item['line_subtotal_tax'] ) ) return;
				
				$subtotal += $this->get_line_subtotal( $item );
				
				if ( ! $this->display_cart_ex_tax ) :
					$subtotal += $item['line_subtotal_tax'];
				endif;

			endforeach;
					
			$subtotal = woocommerce_price( $subtotal );
			
			if ($this->display_cart_ex_tax && $this->prices_include_tax) :	
				$subtotal .= ' <small>'.$woocommerce->countries->ex_tax_or_vat().'</small>';
			endif;
		
		else :
			
			if ( $this->prices_include_tax ) return;
			
			foreach ($this->get_items() as $item) :
				
				$subtotal += $item['line_subtotal'];
			
			endforeach;
			
			// Add Shipping Costs
			$subtotal += $this->get_shipping();
		
			// Remove non-compound taxes
			foreach ( $this->get_taxes() as $tax ) :
				
				if (isset($tax['compound']) && $tax['compound']) continue;
				
				$subtotal = $subtotal + $tax['cart_tax'] + $tax['shipping_tax'];
			
			endforeach;
			
			// Remove discounts
			$subtotal = $subtotal - $this->get_cart_discount();
			
			$subtotal = woocommerce_price($subtotal);

		endif;
		
		return apply_filters( 'woocommerce_order_subtotal_to_display', $subtotal, $compound, $this );
	}

	/** Gets shipping (formatted) */
	function get_shipping_to_display() {
		global $woocommerce;
		
		if ( $this->order_shipping > 0 ) :
			
			$tax_text = '';
			
			if ($this->display_totals_ex_tax || !$this->prices_include_tax) :

				// Show shipping excluding tax
				$shipping = woocommerce_price($this->order_shipping);
				if ($this->order_shipping_tax > 0 && $this->prices_include_tax) :
					$tax_text = $woocommerce->countries->ex_tax_or_vat() . ' '; 
				endif;
			
			else :
			
				// Show shipping including tax
				$shipping = woocommerce_price($this->order_shipping + $this->order_shipping_tax);
				if ($this->order_shipping_tax > 0 && !$this->prices_include_tax) :
					$tax_text = $woocommerce->countries->inc_tax_or_vat() . ' '; 
				endif;
			
			endif;
			
			$shipping .= sprintf( __('&nbsp;<small>%svia %s</small>', 'woocommerce'), $tax_text, $this->get_shipping_method() );
			
		elseif ( $this->get_shipping_method() ) :
			$shipping = $this->get_shipping_method();
		else :
			$shipping = __('Free!', 'woocommerce');
		endif;
		
		return apply_filters( 'woocommerce_order_shipping_to_display', $shipping, $this );
	}

	/** Get cart discount (formatted)  */
	function get_cart_discount_to_display() {
		return apply_filters( 'woocommerce_order_cart_discount_to_display', woocommerce_price( $this->get_cart_discount() ), $this );
	}
	
	/** Get cart discount (formatted)  */
	function get_order_discount_to_display() {
		return apply_filters( 'woocommerce_order_discount_to_display', woocommerce_price( $this->get_order_discount() ), $this );
	}
	
	/** Get a product (either product or variation) */
	function get_product_from_item( $item ) {
		
		if (isset($item['variation_id']) && $item['variation_id']>0) :
			$_product = new WC_Product_Variation( $item['variation_id'] );
		else :
			$_product = new WC_Product( $item['id'] );
		endif;
		
		return $_product;

	}
	
	/** Get totals for display on pages and in emails */
	function get_order_item_totals() {
		global $woocommerce;
		
		$total_rows = array();
		
		if ( $subtotal = $this->get_subtotal_to_display() )
			$total_rows['cart_subtotal'] = array(
				'label' => __( 'Cart Subtotal:', 'woocommerce' ),
				'value'	=> $subtotal
			);
		
		if ( $this->get_cart_discount() > 0 ) 
			$total_rows['cart_discount'] = array(
				'label' => __( 'Cart Discount:', 'woocommerce' ),
				'value'	=> '-' . $this->get_cart_discount_to_display()
			);
		
		if ( $this->get_shipping_method() )
			$total_rows['shipping'] = array(
				'label' => __( 'Shipping:', 'woocommerce' ),
				'value'	=> $this->get_shipping_to_display()
			);
		
		if ( $this->get_total_tax() > 0 ) {
			
			if ( sizeof( $this->get_taxes() ) > 0 ) {
			
				$has_compound_tax = false;
				
				foreach ( $this->get_taxes() as $tax ) {
					if ( $tax[ 'compound' ] ) {
						$has_compound_tax = true;
						continue;
					}
					
					if ( ( $tax[ 'cart_tax' ] + $tax[ 'shipping_tax' ] ) == 0 )
						continue;
					
					$total_rows[ sanitize_title( $tax[ 'label' ] ) ] = array(
						'label' => $tax[ 'label' ],
						'value'	=> woocommerce_price( ( $tax[ 'cart_tax' ] + $tax[ 'shipping_tax' ] ) )
					);
				}
				
				if ( $has_compound_tax ) {
					if ( $subtotal = $this->get_subtotal_to_display( true ) ) {
						$total_rows['subtotal'] = array(
							'label' => __( 'Subtotal:', 'woocommerce' ),
							'value'	=> $subtotal
						);
					}
				}
				
				foreach ( $this->get_taxes() as $tax ) {
					if ( ! $tax[ 'compound' ] )
						continue;

					if ( ( $tax[ 'cart_tax' ] + $tax[ 'shipping_tax' ] ) == 0 )
						continue;
					
					$total_rows[ sanitize_title( $tax[ 'label' ] ) ] = array(
						'label' => $tax[ 'label' ],
						'value'	=> woocommerce_price( ( $tax[ 'cart_tax' ] + $tax[ 'shipping_tax' ] ) )
					);
				}
			} else {
				$total_rows['tax'] = array(
					'label' => $woocommerce->countries->tax_or_vat(),
					'value'	=> woocommerce_price( $this->get_total_tax() )
				);
			}

		} elseif ( get_option( 'woocommerce_display_cart_taxes_if_zero' ) == 'yes' ) {
			$total_rows['tax'] = array(
				'label' => $woocommerce->countries->tax_or_vat(),
				'value'	=> _x( 'N/A', 'Relating to tax', 'woocommerce' )
			);
		}
		
		if ( $this->get_order_discount() > 0 )
			$total_rows['order_discount'] = array(
				'label' => __( 'Order Discount:', 'woocommerce' ),
				'value'	=> '-' . $this->get_order_discount_to_display()
			);
		
		$total_rows['order_total'] = array(
			'label' => __( 'Order Total:', 'woocommerce' ),
			'value'	=> $this->get_formatted_order_total()
		);
		
		return apply_filters( 'woocommerce_get_order_item_totals', $total_rows, $this );
	}
	
	/** Output items for display in html emails */
	function email_order_items_table( $show_download_links = false, $show_sku = false, $show_purchase_note = false, $show_image = false, $image_size = array( 32, 32 ) ) {

		ob_start();
		
		woocommerce_get_template( 'emails/email-order-items.php', array( 
			'order'					=> $this,
			'items' 				=> $this->get_items(), 
			'show_download_links'	=> $show_download_links,
			'show_sku'				=> $show_sku,
			'show_purchase_note'	=> $show_purchase_note,
			'show_image' 			=> $show_image,
			'image_size'			=> $image_size
		) );
		
		$return = apply_filters( 'woocommerce_email_order_items_table', ob_get_clean() );

		return $return;	
		
	}
	
	/**  Returns true if the order contains a downloadable product */
	function has_downloadable_item() {
		$has_downloadable_item = false;
		
		foreach($this->get_items() as $item) : 
			
			$_product = $this->get_product_from_item( $item );

			if ($_product->exists() && $_product->is_downloadable()) :
				$has_downloadable_item = true;
			endif;
			
		endforeach;	
		
		return $has_downloadable_item;
	}
	
	/**  Generates a URL so that a customer can checkout/pay for their (unpaid - pending) order via a link */
	function get_checkout_payment_url() {
		
		$payment_page = get_permalink(woocommerce_get_page_id('pay'));
		
		if (get_option('woocommerce_force_ssl_checkout')=='yes' || is_ssl()) $payment_page = str_replace('http:', 'https:', $payment_page);
	
		return apply_filters('woocommerce_get_checkout_payment_url', add_query_arg('pay_for_order', 'true', add_query_arg('order', $this->order_key, add_query_arg('order_id', $this->id, $payment_page))));
	}
	
	
	/** Generates a URL so that a customer can cancel their (unpaid - pending) order */
	function get_cancel_order_url() {
		global $woocommerce;
		return apply_filters('woocommerce_get_cancel_order_url', $woocommerce->nonce_url( 'cancel_order', add_query_arg('cancel_order', 'true', add_query_arg('order', $this->order_key, add_query_arg('order_id', $this->id, trailingslashit( home_url() ))))));
	}
	
	
	/** Gets a downloadable products file url */
	function get_downloadable_file_url( $item_id, $variation_id ) {
	 	
	 	$download_id = ($variation_id>0) ? $variation_id : $item_id;
	 	
	 	$user_email = $this->billing_email;
				
	 	return add_query_arg('download_file', $download_id, add_query_arg('order', $this->order_key, add_query_arg('email', $user_email, trailingslashit( home_url() ))));
	 }
	 
	/**
	 * Adds a note (comment) to the order
	 *
	 * @param   string	$note		Note to add
	 * @param   int		$is_customer_note	Is this a note for the customer?
	 */
	function add_order_note( $note, $is_customer_note = 0 ) {
		
		$comment_post_ID 		= $this->id;
		$comment_author 		= 'WooCommerce';
		$comment_author_email 	= 'woocommerce@' . str_replace('www.', '', $_SERVER['HTTP_HOST']);
		$comment_author_url 	= '';
		$comment_content 		= $note;
		$comment_agent			= 'WooCommerce';
		$comment_type			= 'order_note';
		$comment_parent			= 0;
		$comment_approved 		= 1;
		$commentdata 			= compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' );
		
		$comment_id = wp_insert_comment( $commentdata );
		
		add_comment_meta( $comment_id, 'is_customer_note', $is_customer_note );
		
		if ($is_customer_note) do_action( 'woocommerce_new_customer_note', array( 'order_id' => $this->id, 'customer_note' => $note ) );
		
		return $comment_id;
		
	}

	/**
	 * Adds a note (comment) to the order
	 *
	 * @param   string	$new_status		Status to change the order to
	 * @param   string	$note			Optional note to add
	 */
	function update_status( $new_status_slug, $note = '' ) {
		
		if ($note) $note .= ' ';
		
		$old_status = get_term_by( 'slug', sanitize_title( $this->status ), 'shop_order_status');
		$new_status = get_term_by( 'slug', sanitize_title( $new_status_slug ), 'shop_order_status');
		
		if ($new_status) {
			
			wp_set_object_terms($this->id, array( $new_status->slug ), 'shop_order_status', false);
			
			if ( $this->status != $new_status->slug ) {
				
				// Status was changed
				do_action( 'woocommerce_order_status_' . $new_status->slug , $this->id );
				do_action( 'woocommerce_order_status_' . $this->status . '_to_' . $new_status->slug, $this->id );
				
				$this->add_order_note( $note . sprintf( __('Order status changed from %s to %s.', 'woocommerce'), __( $old_status->name, 'woocommerce' ), __( $new_status->name, 'woocommerce' ) ) );

				// Record the completed date of the order
				if ( $new_status->slug == 'completed' ) 
					update_post_meta( $this->id, '_completed_date', current_time('mysql') );
				
				if ( $new_status->slug == 'processing' || $new_status->slug == 'completed' || $new_status->slug == 'on-hold' ) {
					
					// Record the sales
					$this->record_product_sales();
					
					// Increase coupon usage counts
					$this->increase_coupon_usage_counts();
				}
				
				// If the order is cancelled, restore used coupons
				if ( $new_status->slug == 'cancelled' ) 
					$this->decrease_coupon_usage_counts();
				
			}

		}
		
		delete_transient( 'woocommerce_processing_order_count' );
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
	 * Sales are also recorded for products
	 * Finally, record the date of payment
	 */
	function payment_complete() {
		
		unset( $_SESSION['order_awaiting_payment'] );
		
		if ( $this->status == 'on-hold' || $this->status == 'pending' || $this->status == 'failed' ) {
		
			$downloadable_order = false;
			
			if ( sizeof( $this->get_items() ) > 0 ) {
				foreach( $this->get_items() as $item ) {
			
					if ( $item['id'] > 0 ) {
					
						$_product = $this->get_product_from_item( $item );
						
						if ( $_product->is_downloadable() && $_product->is_virtual() ) {
							$downloadable_order = true;
							continue;
						}
						
					}
					$downloadable_order = false;
					break;
				}
			}
			
			$new_order_status = ( $downloadable_order ) ? 'completed' : 'processing';
			
			$new_order_status = apply_filters('woocommerce_payment_complete_order_status', $new_order_status, $this->id);
			
			$this->update_status( $new_order_status );
			
			add_post_meta( $this->id, '_paid_date', current_time('mysql'), true );
			
			$this_order = array(
				'ID' => $this->id,
				'post_date' => current_time( 'mysql', 0 ),
				'post_date_gmt' => current_time( 'mysql', 1 )
			);
			wp_update_post( $this_order );

			$this->reduce_order_stock(); // Payment is complete so reduce stock levels
			
			do_action( 'woocommerce_payment_complete', $this->id );
		}
	}
	
	/**
	 * Record sales
	 */
	function record_product_sales() {
		
		if ( get_post_meta( $this->id, '_recorded_sales', true ) == 'yes' ) 
			return;
		
		if ( sizeof( $this->get_items() ) > 0 ) {
			foreach ( $this->get_items() as $item ) {
				if ( $item['id'] > 0 ) {
					$sales = (int) get_post_meta( $item['id'], 'total_sales', true );
					$sales += (int) $item['qty'];
					if ( $sales ) 
						update_post_meta( $item['id'], 'total_sales', $sales );
				}
			}
		}
		
		update_post_meta( $this->id, '_recorded_sales', 'yes' );
	}

	/**
	 * Increase applied coupon counts
	 */
	function get_used_coupons() {
		
		$coupons = get_post_meta( $this->id, 'coupons', true );
		
		return array_map( 'trim', explode( ',', $coupons ) );
	}
	
	/**
	 * Increase applied coupon counts
	 */
	function increase_coupon_usage_counts() {
		global $woocommerce;
		
		if ( get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) == 'yes' ) 
			return;
			
		if ( sizeof( $this->get_used_coupons() ) > 0 ) {
			foreach ( $this->get_used_coupons() as $code ) {
				if ( ! $code )
					continue;
					
				$coupon = $woocommerce->coupon( $code );
				$coupon->inc_usage_count();
			}
		}
		
		update_post_meta( $this->id, '_recorded_coupon_usage_counts', 'yes' );
	}
	
	/**
	 * Decrease applied coupon counts
	 */
	function decrease_coupon_usage_counts() {
		global $woocommerce;
		
		if ( get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) != 'yes' ) 
			return;
			
		if ( sizeof( $this->get_used_coupons() ) > 0 ) {
			foreach ( $this->get_used_coupons() as $code ) {
				if ( ! $code )
					continue;
					
				$coupon = $woocommerce->coupon( $code );
				$coupon->dcr_usage_count();
			}
		}
		
		delete_post_meta( $this->id, '_recorded_coupon_usage_counts' );
	}
		
	/**
	 * Reduce stock levels
	 */
	function reduce_order_stock() {
		
		if ( get_option('woocommerce_manage_stock') == 'yes' && sizeof( $this->get_items() ) > 0 ) {
		
			// Reduce stock levels and do any other actions with products in the cart
			foreach ( $this->get_items() as $item ) {
			
				if ($item['id']>0) {
					$_product = $this->get_product_from_item( $item );
					
					if ( $_product && $_product->exists() && $_product->managing_stock() ) {
					
						$old_stock = $_product->stock;
						
						$new_quantity = $_product->reduce_stock( $item['qty'] );
						
						$this->add_order_note( sprintf( __('Item #%s stock reduced from %s to %s.', 'woocommerce'), $item['id'], $old_stock, $new_quantity) );
						
						$this->send_stock_notifications( $_product, $new_quantity, $item['qty'] );
						
					}
					
				}
			 	
			}
			
			do_action( 'woocommerce_reduce_order_stock', $this );
			
			$this->add_order_note( __('Order item stock reduced successfully.', 'woocommerce') );
			
		}
			
	}
	
	/**
	 * send_stock_notifications function.
	 */
	function send_stock_notifications( $product, $new_stock, $qty_ordered ) {
		
		// Backorders
		if ( $new_stock < 0 )
			do_action( 'woocommerce_product_on_backorder', array( 'product' => $product, 'order_id' => $this->id, 'quantity' => $qty_ordered ) );
		
		// stock status notifications
		$notification_sent = false;
		
		if ( get_option( 'woocommerce_notify_no_stock' ) == 'yes' && get_option('woocommerce_notify_no_stock_amount') >= $new_stock ) {
			do_action( 'woocommerce_no_stock', $product );
			$notification_sent = true;
		}
		if ( ! $notification_sent && get_option( 'woocommerce_notify_low_stock' ) == 'yes' && get_option('woocommerce_notify_low_stock_amount') >= $new_stock ) {
			do_action( 'woocommerce_low_stock', $product );
			$notification_sent = true;
		}

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
			$comment->comment_content = make_clickable($comment->comment_content);
			if ($is_customer_note) 
				$notes[] = $comment;
		endforeach;
		
		add_filter('comments_clauses', 'woocommerce_exclude_order_comments');
		
		return (array) $notes;
		
	}

}


/**
 * Order Item Meta
 * 
 * A Simple class for managing order item meta so plugins add it in the correct format
 */
class order_item_meta {
	
	var $meta;
	
	/**
	 * Constructor
	 */
	function __construct( $item_meta = '' ) {
		$this->meta = array();
		
		if ($item_meta) $this->meta = $item_meta;
	}
	
	/**
	 * Load item meta
	 */
	function new_order_item( $item ) {
		if ($item) :
			do_action('woocommerce_order_item_meta', $this, $item);
		endif;
	}
	
	/**
	 * Add meta
	 */
	function add( $name, $value ) {
		$this->meta[] = array(
			'meta_name' 		=> $name, 
			'meta_value' 	=> $value
		);
	}
	
	/**
	 * Display meta in a formatted list
	 */
	function display( $flat = false, $return = false ) {
		global $woocommerce;
		
		if ( $this->meta && is_array( $this->meta ) ) :
			
			if ( ! $flat ) $output = '<dl class="variation">'; else $output = '';
			
			$meta_list = array();
			
			foreach ( $this->meta as $meta ) :
				
				$name 	= $meta['meta_name'];
				$value	= $meta['meta_value'];
				
				if ( ! $value ) continue;
				
				// If this is a term slug, get the term's nice name
	            if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $name ) ) ) ) :
	            	$term = get_term_by('slug', $value, esc_attr(str_replace('attribute_', '', $name)));
	            	if (!is_wp_error($term) && $term->name) :
	            		$value = $term->name;
	            	endif;
	            else :
	            	$value = ucfirst($value);
	            endif;
				
				if ($flat) :
					$meta_list[] = $woocommerce->attribute_label(str_replace('attribute_', '', $name)).': '.$value;
				else :
					$meta_list[] = '<dt>'.$woocommerce->attribute_label(str_replace('attribute_', '', $name)).':</dt><dd>'.$value.'</dd>';
				endif;
				
			endforeach;
			
			if ($flat) :
				$output .= implode(", \n", $meta_list);
			else :
				$output .= implode('', $meta_list);
			endif;
			
			if (!$flat) $output .= '</dl>';

			if ($return) return $output; else echo $output;
			
		endif;
	}
	
}

/** Depreciated */
class woocommerce_order extends WC_Order {
	public function __construct( $id = '' ) { 
		parent::__construct( $id ); 
	} 
}