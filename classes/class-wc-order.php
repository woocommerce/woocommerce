<?php
/**
 * Order
 *
 * The WooCommerce order class handles order data.
 *
 * @class 		WC_Order
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Order {

	/** @public int Order (post) ID */
	public $id;

	/** @public string Order status. */
	public $status;

	/** @public string Order date (placed). */
	public $order_date;

	/** @public string Order date (paid). */
	public $modified_date;

	/** @public string Note added by the customer. */
	public $customer_note;

	/** @public array Order (post) meta/custom fields. */
	public $order_custom_fields;

	/** @public string Order unique key. */
	public $order_key;

	/** @public string */
	public $billing_first_name;

	/** @public string */
	public $billing_last_name;

	/** @public string */
	public $billing_company;

	/** @public string */
	public $billing_address_1;

	/** @public string */
	public $billing_address_2;

	/** @public string */
	public $billing_city;

	/** @public string */
	public $billing_postcode;

	/** @public string */
	public $billing_country;

	/** @public string */
	public $billing_state;

	/** @public string */
	public $billing_email;

	/** @public string */
	public $billing_phone;

	/** @public string */
	public $shipping_first_name;

	/** @public string */
	public $shipping_last_name;

	/** @public string */
	public $shipping_company;

	/** @public string */
	public $shipping_address_1;

	/** @public string */
	public $shipping_address_2;

	/** @public string */
	public $shipping_city;

	/** @public string */
	public $shipping_postcode;

	/** @public string */
	public $shipping_country;

	/** @public string */
	public $shipping_state;

	/** @public string Method id of the shipping used */
	public $shipping_method;

	/** @public string Shipping method title */
	public $shipping_method_title;

	/** @public string Method id of the payment used */
	public $payment_method;

	/** @public string Payment method title */
	public $payment_method_title;

	/** @public string After tax discount total */
	public $order_discount;

	/** @public string Before tax discount total */
	public $cart_discount;

	/** @public string Tax for the items total */
	public $order_tax;

	/** @public string Shipping cost */
	public $order_shipping;

	/** @public string Shipping tax */
	public $order_shipping_tax;

	/** @public string Grand total */
	public $order_total;

	/** @public array Taxes array (tax rows) */
	public $taxes;

	/** @public int User ID */
	public $customer_user;

	/** @public int User ID */
	public $user_id;

	/** @public string */
	public $completed_date;

	/** @public string */
	public $billing_address;

	/** @public string */
	public $formatted_billing_address;

	/** @public string */
	public $shipping_address;

	/** @public string */
	public $formatted_shipping_address;

	/** @public string */
	public $post_status;

	/**
	 * Get the order if ID is passed, otherwise the order is new and empty.
	 *
	 * @access public
	 * @param string $id (default: '')
	 * @return void
	 */
	public function __construct( $id = '' ) {
		$this->prices_include_tax = get_option('woocommerce_prices_include_tax') == 'yes' ? true : false;
		$this->tax_display_cart   = get_option( 'woocommerce_tax_display_cart' );

		$this->display_totals_ex_tax = $this->tax_display_cart == 'excl' ? true : false;
		$this->display_cart_ex_tax   = $this->tax_display_cart == 'excl' ? true : false;

		if ( $id > 0 )
			$this->get_order( $id );
	}


	/**
	 * Gets an order from the database.
	 *
	 * @access public
	 * @param int $id (default: 0)
	 * @return bool
	 */
	public function get_order( $id = 0 ) {
		if ( ! $id )
			return false;
		if ( $result = get_post( $id ) ) {
			$this->populate( $result );
			return true;
		}
		return false;
	}


	/**
	 * Populates an order from the loaded post data.
	 *
	 * @access public
	 * @param mixed $result
	 * @return void
	 */
	public function populate( $result ) {
		// Standard post data
		$this->id = $result->ID;
		$this->order_date = $result->post_date;
		$this->modified_date = $result->post_modified;
		$this->customer_note = $result->post_excerpt;
		$this->post_status = $result->post_status;
		$this->order_custom_fields = get_post_meta( $this->id );

		// Define the data we're going to load: Key => Default value
		$load_data = apply_filters( 'woocommerce_load_order_data', array(
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
		) );

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) {
			if ( isset( $this->order_custom_fields[ '_' . $key ][0] ) && $this->order_custom_fields[ '_' . $key ][0] !== '' ) {
				$this->$key = $this->order_custom_fields[ '_' . $key ][0];
			} else {
				$this->$key = $default;
			}
		}

		// Aliases
		$this->user_id = (int) $this->customer_user;

		// Get status
		$terms = wp_get_object_terms( $this->id, 'shop_order_status', array('fields' => 'slugs') );
		$this->status = (isset($terms[0])) ? $terms[0] : 'pending';
	}


	/**
	 * Check if an order key is valid.
	 *
	 * @access public
	 * @param mixed $key
	 * @return bool
	 */
	public function key_is_valid( $key ) {
		if ( $key == $this->order_key ) return true;
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
	public function get_order_number() {
		return apply_filters( 'woocommerce_order_number', _x( '#', 'hash before order number', 'woocommerce' ) . $this->id, $this );
	}

	/**
	 * Get a formatted billing address for the order.
	 *
	 * @access public
	 * @return string
	 */
	public function get_formatted_billing_address() {
		if ( ! $this->formatted_billing_address ) {
			global $woocommerce;

			// Formatted Addresses
			$address = apply_filters( 'woocommerce_order_formatted_billing_address', array(
				'first_name'	=> $this->billing_first_name,
				'last_name'		=> $this->billing_last_name,
				'company'		=> $this->billing_company,
				'address_1'		=> $this->billing_address_1,
				'address_2'		=> $this->billing_address_2,
				'city'			=> $this->billing_city,
				'state'			=> $this->billing_state,
				'postcode'		=> $this->billing_postcode,
				'country'		=> $this->billing_country
			), $this );

			$this->formatted_billing_address = $woocommerce->countries->get_formatted_address( $address );
		}
		return $this->formatted_billing_address;
	}

	/**
	 * Get the billing address in an array.
	 *
	 * @access public
	 * @return array
	 */
	public function get_billing_address() {
		if ( ! $this->billing_address ) {
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
		}
		return $this->billing_address;
	}

	/**
	 * Get a formatted shipping address for the order.
	 *
	 * @access public
	 * @return void
	 */
	public function get_formatted_shipping_address() {
		if ( ! $this->formatted_shipping_address ) {
			if ( $this->shipping_address_1 ) {
				global $woocommerce;

				// Formatted Addresses
				$address = apply_filters( 'woocommerce_order_formatted_shipping_address', array(
					'first_name' 	=> $this->shipping_first_name,
					'last_name'		=> $this->shipping_last_name,
					'company'		=> $this->shipping_company,
					'address_1'		=> $this->shipping_address_1,
					'address_2'		=> $this->shipping_address_2,
					'city'			=> $this->shipping_city,
					'state'			=> $this->shipping_state,
					'postcode'		=> $this->shipping_postcode,
					'country'		=> $this->shipping_country
				), $this );

				$this->formatted_shipping_address = $woocommerce->countries->get_formatted_address( $address );
			}
		}
		return $this->formatted_shipping_address;
	}


	/**
	 * Get the shipping address in an array.
	 *
	 * @access public
	 * @return array
	 */
	public function get_shipping_address() {
		if ( ! $this->shipping_address ) {
			if ( $this->shipping_address_1 ) {
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
			}
		}
		return $this->shipping_address;
	}

	/**
	 * Return an array of items/products within this order.
	 *
	 * @access public
	 * @param string $type Types of line items to get (array or string)
	 * @return void
	 */
	public function get_items( $type = '' ) {
		global $wpdb, $woocommerce;

		if ( empty( $type ) )
			$type = array( 'line_item' );

		if ( ! is_array( $type ) )
			$type = array( $type );

		$type = array_map( 'esc_attr', $type );

		$line_items = $wpdb->get_results( $wpdb->prepare( "
			SELECT 		order_item_id, order_item_name, order_item_type
			FROM 		{$wpdb->prefix}woocommerce_order_items
			WHERE 		order_id = %d
			AND 		order_item_type IN ( '" . implode( "','", $type ) . "' )
			ORDER BY 	order_item_id
		", $this->id ) );

		$items = array();

		foreach ( $line_items as $item ) {

			// Place line item into array to return
			$items[ $item->order_item_id ]['name'] = $item->order_item_name;
			$items[ $item->order_item_id ]['type'] = $item->order_item_type;
			$items[ $item->order_item_id ]['item_meta'] = $this->get_item_meta( $item->order_item_id );

			// Put meta into item array
			foreach( $items[ $item->order_item_id ]['item_meta'] as $name => $value ) {
				$key = substr( $name, 0, 1 ) == '_' ? substr( $name, 1 ) : $name;
				$items[ $item->order_item_id ][ $key ] = $value[0];
			}
		}

		return apply_filters( 'woocommerce_order_get_items', $items, $this );
	}

	/**
	 * Gets order total - formatted for display.
	 *
	 * @access public
	 * @return string
	 */
	public function get_item_count( $type = '' ) {
		global $wpdb, $woocommerce;

		if ( empty( $type ) )
			$type = array( 'line_item' );

		if ( ! is_array( $type ) )
			$type = array( $type );

		$items = $this->get_items( $type );

		$count = 0;

		foreach ( $items as $item ) {
			if ( ! empty( $item['qty'] ) )
				$count += $item['qty'];
			else
				$count ++;
		}

		return apply_filters( 'woocommerce_get_item_count', $count, $type, $this );
	}

	/**
	 * Return an array of fees within this order.
	 *
	 * @access public
	 * @return array
	 */
	public function get_fees() {
		return $this->get_items( 'fee' );
	}

	/**
	 * Return an array of taxes within this order.
	 *
	 * @access public
	 * @return void
	 */
	public function get_taxes() {
		return $this->get_items( 'tax' );
	}

	/**
	 * Get taxes, merged by code, formatted ready for output.
	 *
	 * @access public
	 * @return void
	 */
	public function get_tax_totals() {
		$taxes      = $this->get_items( 'tax' );
		$tax_totals = array();

		foreach ( $taxes as $key => $tax ) {

			$code = $tax[ 'name' ];

			if ( ! isset( $tax_totals[ $code ] ) ) {
				$tax_totals[ $code ] = new stdClass();
				$tax_totals[ $code ]->amount = 0;
			}

			$tax_totals[ $code ]->is_compound       = $tax[ 'compound' ];
			$tax_totals[ $code ]->label             = isset( $tax[ 'label' ] ) ? $tax[ 'label' ] : $tax[ 'name' ];
			$tax_totals[ $code ]->amount           += $tax[ 'tax_amount' ] + $tax[ 'shipping_tax_amount' ];
			$tax_totals[ $code ]->formatted_amount  = woocommerce_price( $tax_totals[ $code ]->amount );
		}

		return apply_filters( 'woocommerce_order_tax_totals', $tax_totals, $this );
	}

	/**
	 * has_meta function for order items.
	 *
	 * @access public
	 * @return array of meta data
	 */
	public function has_meta( $order_item_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value, meta_id, order_item_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d
			ORDER BY meta_key,meta_id", absint( $order_item_id ) ), ARRAY_A );
	}

	/**
	 * Get order item meta.
	 *
	 * @access public
	 * @param mixed $item_id
	 * @param string $key (default: '')
	 * @param bool $single (default: false)
	 * @return void
	 */
	public function get_item_meta( $order_item_id, $key = '', $single = false ) {
		return get_metadata( 'order_item', $order_item_id, $key, $single );
	}


	/** Total Getters *******************************************************/

	/**
	 * Gets shipping and product tax.
	 *
	 * @access public
	 * @return float
	 */
	public function get_total_tax() {
		return apply_filters( 'woocommerce_order_amount_total_tax', number_format( (double) $this->order_tax + (double) $this->order_shipping_tax, 2, '.', '' ) );
	}


	/**
	 * Gets the total (product) discount amount - these are applied before tax.
	 *
	 * @access public
	 * @return float
	 */
	public function get_cart_discount() {
		return apply_filters( 'woocommerce_order_amount_cart_discount', number_format( (double) $this->cart_discount, 2, '.', '' ) );
	}


	/**
	 * Gets the total (product) discount amount - these are applied before tax.
	 *
	 * @access public
	 * @return float
	 */
	public function get_order_discount() {
		return apply_filters( 'woocommerce_order_amount_order_discount', number_format( (double) $this->order_discount, 2, '.', '' ) );
	}


	/**
	 * Gets the total discount amount - both kinds
	 *
	 * @access public
	 * @return float
	 */
	public function get_total_discount() {
		if ( $this->order_discount || $this->cart_discount )
			return apply_filters( 'woocommerce_order_amount_total_discount', number_format( (double) $this->order_discount + (double) $this->cart_discount, 2, '.', '' ) );
	}


	/**
	 * Gets shipping total.
	 *
	 * @access public
	 * @return float
	 */
	public function get_shipping() {
		return apply_filters( 'woocommerce_order_amount_shipping', number_format( (double) $this->order_shipping, 2, '.', '' ) );
	}


	/**
	 * Gets shipping tax amount.
	 *
	 * @access public
	 * @return float
	 */
	public function get_shipping_tax() {
		return apply_filters( 'woocommerce_order_amount_shipping_tax', number_format( (double) $this->order_shipping_tax, 2, '.', '' ) );
	}


	/**
	 * Gets order total.
	 *
	 * @access public
	 * @return float
	 */
	public function get_total() {
		return apply_filters( 'woocommerce_order_amount_total', number_format( (double) $this->order_total, 2, '.', '' ) );
	}


	/**
	 * get_order_total function. Alias for get_total()
	 *
	 * @access public
	 * @return void
	 */
	public function get_order_total() {
		return $this->get_total();
	}

	/**
	 * Gets shipping method title.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_method() {
		return apply_filters( 'woocommerce_order_shipping_method', ucwords( $this->shipping_method_title ) );
	}


	/**
	 * Get item subtotal - this is the cost before discount.
	 *
	 * @access public
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @param bool $round (default: true)
	 * @return float
	 */
	public function get_item_subtotal( $item, $inc_tax = false, $round = true ) {
		if ( $inc_tax )
			$price = ( $item['line_subtotal'] + $item['line_subtotal_tax'] / $item['qty'] );
		else
			$price = ( $item['line_subtotal'] / $item['qty'] );
		return apply_filters( 'woocommerce_order_amount_item_subtotal', ($round) ? number_format( $price, 2, '.', '') : $price );
	}


	/**
	 * Get line subtotal - this is the cost before discount.
	 *
	 * @access public
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @param bool $round (default: true)
	 * @return float
	 */
	public function get_line_subtotal( $item, $inc_tax = false, $round = true ) {
		if ( $inc_tax )
			$price = $item['line_subtotal'] + $item['line_subtotal_tax'];
		else
			$price = $item['line_subtotal'];
		return apply_filters( 'woocommerce_order_amount_line_subtotal', ($round) ? number_format( $price, 2, '.', '') : $price );
	}


	/**
	 * Calculate item cost - useful for gateways.
	 *
	 * @access public
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @param bool $round (default: true)
	 * @return float
	 */
	public function get_item_total( $item, $inc_tax = false, $round = true ) {
		if ( $inc_tax )
			$price = ( ( $item['line_total'] + $item['line_tax'] ) / $item['qty'] );
		else
			$price = $item['line_total'] / $item['qty'];
		return apply_filters( 'woocommerce_order_amount_item_total', ($round) ? number_format( $price, 2, '.', '') : $price );
	}


	/**
	 * Calculate item tax - useful for gateways.
	 *
	 * @access public
	 * @param mixed $item
	 * @param bool $round (default: true)
	 * @return float
	 */
	public function get_item_tax( $item, $round = true ) {
		$price = $item['line_tax'] / $item['qty'];
		return apply_filters( 'woocommerce_order_amount_item_tax', ($round) ? number_format( $price, 2, '.', '') : $price );
	}


	/**
	 * Calculate line total - useful for gateways.
	 *
	 * @access public
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @return float
	 */
	public function get_line_total( $item, $inc_tax = false ) {
		if ( $inc_tax )
			return apply_filters( 'woocommerce_order_amount_line_total', number_format( $item['line_total'] + $item['line_tax'] , 2, '.', '') );
		else
			return apply_filters( 'woocommerce_order_amount_line_total', number_format( $item['line_total'] , 2, '.', '') );
	}


	/**
	 * Calculate line tax - useful for gateways.
	 *
	 * @access public
	 * @param mixed $item
	 * @return float
	 */
	public function get_line_tax( $item ) {
		return apply_filters( 'woocommerce_order_amount_line_tax', number_format( $item['line_tax'], 2, '.', '') );
	}

	/** End Total Getters *******************************************************/

	/**
	 * Gets line subtotal - formatted for display.
	 *
	 * @access public
	 * @param mixed $item
	 * @return string
	 */
	public function get_formatted_line_subtotal( $item, $tax_display = '' ) {

		if ( ! $tax_display )
			$tax_display = $this->tax_display_cart;

		$subtotal = 0;

		if (!isset($item['line_subtotal']) || !isset($item['line_subtotal_tax'])) return;

		if ( $tax_display == 'excl' ) {
			if ( $this->prices_include_tax ) $ex_tax_label = 1; else $ex_tax_label = 0;
			$subtotal = woocommerce_price( $this->get_line_subtotal( $item ), array( 'ex_tax_label' => $ex_tax_label ) );
		} else {
			$subtotal = woocommerce_price( $this->get_line_subtotal( $item, true ) );
		}

		return apply_filters( 'woocommerce_order_formatted_line_subtotal', $subtotal, $item, $this );
	}


	/**
	 * Gets order total - formatted for display.
	 *
	 * @access public
	 * @return string
	 */
	public function get_formatted_order_total() {

		$formatted_total = woocommerce_price( $this->order_total );

		return apply_filters( 'woocommerce_get_formatted_order_total', $formatted_total, $this );
	}


	/**
	 * Gets subtotal - subtotal is shown before discounts, but with localised taxes.
	 *
	 * @access public
	 * @param bool $compound (default: false)
	 * @return string
	 */
	public function get_subtotal_to_display( $compound = false, $tax_display = '' ) {
		global $woocommerce;

		if ( ! $tax_display )
			$tax_display = $this->tax_display_cart;

		$subtotal = 0;

		if ( ! $compound ) {

			foreach ( $this->get_items() as $item ) {

				if ( ! isset( $item['line_subtotal'] ) || ! isset( $item['line_subtotal_tax'] ) ) return;

				$subtotal += $this->get_line_subtotal( $item );

				if ( $tax_display == 'incl' ) {
					$subtotal += $item['line_subtotal_tax'];
				}

			}

			$subtotal = woocommerce_price( $subtotal );

			if ( $tax_display == 'excl' && $this->prices_include_tax )
				$subtotal .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';

		} else {

			if ( $tax_display == 'incl' )
				return;

			foreach ( $this->get_items() as $item ) {

				$subtotal += $item['line_subtotal'];

			}

			// Add Shipping Costs
			$subtotal += $this->get_shipping();

			// Remove non-compound taxes
			foreach ( $this->get_taxes() as $tax ) {

				if ( ! empty( $tax['compound'] ) ) continue;

				$subtotal = $subtotal + $tax['tax_amount'] + $tax['shipping_tax_amount'];

			}

			// Remove discounts
			$subtotal = $subtotal - $this->get_cart_discount();

			$subtotal = woocommerce_price( $subtotal );

		}

		return apply_filters( 'woocommerce_order_subtotal_to_display', $subtotal, $compound, $this );
	}


	/**
	 * Gets shipping (formatted).
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_to_display( $tax_display = '' ) {
		global $woocommerce;

		if ( ! $tax_display )
			$tax_display = $this->tax_display_cart;

		if ( $this->order_shipping > 0 ) {

			$tax_text = '';

			if ( $tax_display == 'excl' ) {

				// Show shipping excluding tax
				$shipping = woocommerce_price( $this->order_shipping );

				if ( $this->order_shipping_tax > 0 && $this->prices_include_tax )
					$tax_text = $woocommerce->countries->ex_tax_or_vat() . ' ';

			} else {

				// Show shipping including tax
				$shipping = woocommerce_price( $this->order_shipping + $this->order_shipping_tax );

				if ( $this->order_shipping_tax > 0 && ! $this->prices_include_tax )
					$tax_text = $woocommerce->countries->inc_tax_or_vat() . ' ';

			}

			$shipping .= sprintf( __( '&nbsp;<small>%svia %s</small>', 'woocommerce' ), $tax_text, $this->get_shipping_method() );

		} elseif ( $this->get_shipping_method() ) {
			$shipping = $this->get_shipping_method();
		} else {
			$shipping = __( 'Free!', 'woocommerce' );
		}

		return apply_filters( 'woocommerce_order_shipping_to_display', $shipping, $this );
	}


	/**
	 * Get cart discount (formatted).
	 *
	 * @access public
	 * @return string.
	 */
	public function get_cart_discount_to_display() {
		return apply_filters( 'woocommerce_order_cart_discount_to_display', woocommerce_price( $this->get_cart_discount() ), $this );
	}


	/**
	 * Get cart discount (formatted).
	 *
	 * @access public
	 * @return string
	 */
	public function get_order_discount_to_display() {
		return apply_filters( 'woocommerce_order_discount_to_display', woocommerce_price( $this->get_order_discount() ), $this );
	}


	/**
	 * Get a product (either product or variation).
	 *
	 * @access public
	 * @param mixed $item
	 * @return WC_Product
	 */
	public function get_product_from_item( $item ) {
		$_product = get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
                return apply_filters( 'woocommerce_get_product_from_item', $_product, $item, $this );
	}


	/**
	 * Get totals for display on pages and in emails.
	 *
	 * @access public
	 * @return array
	 */
	public function get_order_item_totals( $tax_display = '' ) {
		global $woocommerce;

		if ( ! $tax_display )
			$tax_display = $this->tax_display_cart;

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

		if ( $fees = $this->get_fees() )
			foreach( $fees as $id => $fee ) {

				if ( $tax_display == 'excl' ) {

					$total_rows[ 'fee_' . $id ] = array(
						'label' => $fee['name'],
						'value'	=> woocommerce_price( $fee['line_total'] )
					);

				} else {

					$total_rows[ 'fee_' . $id ] = array(
						'label' => $fee['name'],
						'value'	=> woocommerce_price( $fee['line_total'] + $fee['line_tax'] )
					);

				}
			}

		// Tax for tax exclusive prices
		if ( $tax_display == 'excl' ) {
			foreach ( $this->get_tax_totals() as $code => $tax ) {
				$total_rows[ sanitize_title( $code ) ] = array(
					'label' => $tax->label . ':',
					'value'	=> $tax->formatted_amount
				);
			}
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

		// Tax for inclusive prices
		if ( $tax_display == 'incl' ) {

			$tax_string_array = array();

			foreach ( $this->get_tax_totals() as $code => $tax ) {
				$tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
			}

			if ( ! empty( $tax_string_array ) )
				$total_rows['order_total']['value'] .= ' ' . sprintf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) );
		}

		return apply_filters( 'woocommerce_get_order_item_totals', $total_rows, $this );
	}


	/**
	 * Output items for display in html emails.
	 *
	 * @access public
	 * @param bool $show_download_links (default: false)
	 * @param bool $show_sku (default: false)
	 * @param bool $show_purchase_note (default: false)
	 * @param bool $show_image (default: false)
	 * @param array $image_size (default: array( 32, 32 )
	 * @param bool plain text
	 * @return string
	 */
	public function email_order_items_table( $show_download_links = false, $show_sku = false, $show_purchase_note = false, $show_image = false, $image_size = array( 32, 32 ), $plain_text = false ) {

		ob_start();

		$template = $plain_text ? 'emails/plain/email-order-items.php' : 'emails/email-order-items.php';

		woocommerce_get_template( $template, array(
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

	/**
	 * Checks if product download is permitted
	 *
	 * @access public
	 * @return bool
	 */
	public function is_download_permitted() {
		return apply_filters( 'woocommerce_order_is_download_permitted', $this->status == 'completed' || ( get_option( 'woocommerce_downloads_grant_access_after_payment' ) == 'yes' && $this->status == 'processing' ), $this );
	}

	/**
	 * Returns true if the order contains a downloadable product.
	 *
	 * @access public
	 * @return bool
	 */
	public function has_downloadable_item() {
		$has_downloadable_item = false;

		foreach($this->get_items() as $item) :

			$_product = $this->get_product_from_item( $item );

			if ($_product->exists() && $_product->is_downloadable()) :
				$has_downloadable_item = true;
			endif;

		endforeach;

		return $has_downloadable_item;
	}


	/**
	 * Generates a URL so that a customer can checkout/pay for their (unpaid - pending) order via a link.
	 *
	 * @access public
	 * @return string
	 */
	public function get_checkout_payment_url() {

		$payment_page = get_permalink(woocommerce_get_page_id('pay'));

		if (get_option('woocommerce_force_ssl_checkout')=='yes' || is_ssl()) $payment_page = str_replace('http:', 'https:', $payment_page);

		return apply_filters('woocommerce_get_checkout_payment_url', add_query_arg('pay_for_order', 'true', add_query_arg('order', $this->order_key, add_query_arg('order_id', $this->id, $payment_page))));
	}


	/**
	 * Generates a URL so that a customer can cancel their (unpaid - pending) order.
	 *
	 * @access public
	 * @return string
	 */
	public function get_cancel_order_url() {
		global $woocommerce;
		return apply_filters('woocommerce_get_cancel_order_url', $woocommerce->nonce_url( 'cancel_order', add_query_arg('cancel_order', 'true', add_query_arg('order', $this->order_key, add_query_arg('order_id', $this->id, trailingslashit( home_url() ))))));
	}


	/**
	 * Gets any downloadable product file urls.
	 *
	 * @access public
	 * @param int $product_id product identifier
	 * @param int $variation_id variation identifier, or null
	 * @param array $item the item
	 * @return array available downloadable file urls
	 */
	public function get_downloadable_file_urls( $product_id, $variation_id, $item ) {
		global $wpdb;

	 	$download_file = $variation_id > 0 ? $variation_id : $product_id;
		$_product = get_product( $download_file );

	 	$user_email = $this->billing_email;

		$results = $wpdb->get_results( $wpdb->prepare("
			SELECT download_id
			FROM " . $wpdb->prefix . "woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s
		", $user_email, $this->order_key, $download_file ) );

		$file_urls = array();
		foreach ( $results as $result ) {
			if ( $_product->has_file( $result->download_id ) ) {

				$file_urls[ $_product->get_file_download_path( $result->download_id ) ] = add_query_arg( array( 'download_file' => $download_file, 'order' => $this->order_key, 'email' => $user_email, 'key' => $result->download_id ), trailingslashit( home_url() ) );

			}
		}

		return apply_filters( 'woocommerce_get_downloadable_file_urls', $file_urls, $product_id, $variation_id, $item );
	}

	/**
	 * Adds a note (comment) to the order
	 *
	 * @access public
	 * @param string $note Note to add
	 * @param int $is_customer_note (default: 0) Is this a note for the customer?
	 * @return id Comment ID
	 */
	public function add_order_note( $note, $is_customer_note = 0 ) {

		$is_customer_note = intval( $is_customer_note );

		if ( isset( $_SERVER['HTTP_HOST'] ) )
			$comment_author_email 	= sanitize_email( strtolower( __( 'WooCommerce', 'woocommerce' ) ) . '@' . str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) );
		else
			$comment_author_email 	= sanitize_email( strtolower( __( 'WooCommerce', 'woocommerce' ) ) . '@noreply.com' );

		$comment_post_ID 		= $this->id;
		$comment_author 		= __( 'WooCommerce', 'woocommerce' );
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
	 * Updates status of order
	 *
	 * @access public
	 * @param string $new_status_slug Status to change the order to
	 * @param string $note (default: '') Optional note to add
	 * @return void
	 */
	public function update_status( $new_status_slug, $note = '' ) {

		if ( $note )
			$note .= ' ';

		$old_status = get_term_by( 'slug', sanitize_title( $this->status ), 'shop_order_status' );
		$new_status = get_term_by( 'slug', sanitize_title( $new_status_slug ), 'shop_order_status' );

		if ( $new_status ) {

			wp_set_object_terms( $this->id, array( $new_status->slug ), 'shop_order_status', false );

			if ( $this->status != $new_status->slug ) {

				// Status was changed
				do_action( 'woocommerce_order_status_' . $new_status->slug, $this->id );
				do_action( 'woocommerce_order_status_' . $this->status . '_to_' . $new_status->slug, $this->id );
				do_action( 'woocommerce_order_status_changed', $this->id, $this->status, $new_status->slug );

				if ( $old_status )
					$this->add_order_note( $note . sprintf( __( 'Order status changed from %s to %s.', 'woocommerce' ), __( $old_status->name, 'woocommerce' ), __( $new_status->name, 'woocommerce' ) ) );

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

				// Update last modified
  				wp_update_post( array( 'ID' => $this->id ) );

				$this->status = $new_status->slug;
			}

		}

		delete_transient( 'woocommerce_processing_order_count' );
	}


	/**
	 * Cancel the order and restore the cart (before payment)
	 *
	 * @access public
	 * @param string $note (default: '') Optional note to add
	 * @return void
	 */
	public function cancel_order( $note = '' ) {
		global $woocommerce;

		unset( $woocommerce->session->order_awaiting_payment );

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
	 *
	 * @access public
	 * @return void
	 */
	public function payment_complete() {
		global $woocommerce;

		if ( ! empty( $woocommerce->session->order_awaiting_payment ) )
			unset( $woocommerce->session->order_awaiting_payment );

		if ( $this->id && ( $this->status == 'on-hold' || $this->status == 'pending' || $this->status == 'failed' ) ) {

			$order_needs_processing = true;

			if ( sizeof( $this->get_items() ) > 0 ) {

				foreach( $this->get_items() as $item ) {

					if ( $item['product_id'] > 0 ) {

						$_product = $this->get_product_from_item( $item );

						if ( ( $_product->is_downloadable() && $_product->is_virtual() ) || ! apply_filters( 'woocommerce_order_item_needs_processing', true, $_product, $this->id ) ) {
							$order_needs_processing = false;
							continue;
						}

					}
					$order_needs_processing = true;
					break;
				}
			}

			$new_order_status = $order_needs_processing ? 'processing' : 'completed';

			$new_order_status = apply_filters( 'woocommerce_payment_complete_order_status', $new_order_status, $this->id );

			$this->update_status( $new_order_status );

			add_post_meta( $this->id, '_paid_date', current_time('mysql'), true );

			$this_order = array(
				'ID' => $this->id,
				'post_date' => current_time( 'mysql', 0 ),
				'post_date_gmt' => current_time( 'mysql', 1 )
			);
			wp_update_post( $this_order );

			if ( apply_filters( 'woocommerce_payment_complete_reduce_order_stock', true, $this->id ) )
				$this->reduce_order_stock(); // Payment is complete so reduce stock levels

			do_action( 'woocommerce_payment_complete', $this->id );
		}
	}


	/**
	 * Record sales
	 *
	 * @access public
	 * @return void
	 */
	public function record_product_sales() {

		if ( get_post_meta( $this->id, '_recorded_sales', true ) == 'yes' )
			return;

		if ( sizeof( $this->get_items() ) > 0 ) {
			foreach ( $this->get_items() as $item ) {
				if ( $item['product_id'] > 0 ) {
					$sales = (int) get_post_meta( $item['product_id'], 'total_sales', true );
					$sales += (int) $item['qty'];
					if ( $sales )
						update_post_meta( $item['product_id'], 'total_sales', $sales );
				}
			}
		}

		update_post_meta( $this->id, '_recorded_sales', 'yes' );
	}


	/**
	 * Get coupon codes only.
	 *
	 * @access public
	 * @return array
	 */
	public function get_used_coupons() {

		$codes   = array();
		$coupons = $this->get_items( 'coupon' );

		foreach ( $coupons as $item_id => $item ) {
			$codes[] = trim( $item['name'] );
		}

		return $codes;
	}


	/**
	 * Increase applied coupon counts
	 *
	 * @access public
	 * @return void
	 */
	public function increase_coupon_usage_counts() {
		global $woocommerce;

		if ( get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) == 'yes' )
			return;

		if ( sizeof( $this->get_used_coupons() ) > 0 ) {
			foreach ( $this->get_used_coupons() as $code ) {
				if ( ! $code )
					continue;

				$coupon = new WC_Coupon( $code );
				$coupon->inc_usage_count();
			}
		}

		update_post_meta( $this->id, '_recorded_coupon_usage_counts', 'yes' );
	}


	/**
	 * Decrease applied coupon counts
	 *
	 * @access public
	 * @return void
	 */
	public function decrease_coupon_usage_counts() {
		global $woocommerce;

		if ( get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) != 'yes' )
			return;

		if ( sizeof( $this->get_used_coupons() ) > 0 ) {
			foreach ( $this->get_used_coupons() as $code ) {
				if ( ! $code )
					continue;

				$coupon = new WC_Coupon( $code );
				$coupon->dcr_usage_count();
			}
		}

		delete_post_meta( $this->id, '_recorded_coupon_usage_counts' );
	}


	/**
	 * Reduce stock levels
	 *
	 * @access public
	 * @return void
	 */
	public function reduce_order_stock() {

		if ( get_option('woocommerce_manage_stock') == 'yes' && sizeof( $this->get_items() ) > 0 ) {

			// Reduce stock levels and do any other actions with products in the cart
			foreach ( $this->get_items() as $item ) {

				if ($item['product_id']>0) {
					$_product = $this->get_product_from_item( $item );

					if ( $_product && $_product->exists() && $_product->managing_stock() ) {

						$old_stock = $_product->stock;

						$qty = apply_filters( 'woocommerce_order_item_quantity', $item['qty'], $this, $item );

						$new_quantity = $_product->reduce_stock( $qty );

						$this->add_order_note( sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $item['product_id'], $old_stock, $new_quantity) );

						$this->send_stock_notifications( $_product, $new_quantity, $item['qty'] );

					}

				}

			}

			do_action( 'woocommerce_reduce_order_stock', $this );

			$this->add_order_note( __( 'Order item stock reduced successfully.', 'woocommerce' ) );
		}

	}


	/**
	 * send_stock_notifications function.
	 *
	 * @access public
	 * @param object $product
	 * @param int $new_stock
	 * @param int $qty_ordered
	 * @return void
	 */
	public function send_stock_notifications( $product, $new_stock, $qty_ordered ) {

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
	 *
	 * @access public
	 * @return array
	 */
	public function get_customer_order_notes() {

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
