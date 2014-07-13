<?php
/**
 * WooCommerce API Orders Class
 *
 * Handles requests to the /orders endpoint
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_Orders extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/orders';

	/**
	 * Register the routes for this class
	 *
	 * GET|POST /orders
	 * GET /orders/count
	 * GET|PUT|DELETE /orders/<id>
	 * GET /orders/<id>/notes
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET|POST /orders
		$routes[ $this->base ] = array(
			array( array( $this, 'get_orders' ),     WC_API_Server::READABLE ),
			array( array( $this, 'create_order' ),   WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /orders/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'get_orders_count' ), WC_API_Server::READABLE ),
		);

		# GET|PUT|DELETE /orders/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_order' ),  WC_API_Server::READABLE ),
			array( array( $this, 'edit_order' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_order' ), WC_API_Server::DELETABLE ),
		);

		# GET /orders/<id>/notes
		$routes[ $this->base . '/(?P<id>\d+)/notes' ] = array(
			array( array( $this, 'get_order_notes' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all orders
	 *
	 * @since 2.1
	 * @param string $fields
	 * @param array $filter
	 * @param string $status
	 * @param int $page
	 * @return array
	 */
	public function get_orders( $fields = null, $filter = array(), $status = null, $page = 1 ) {

		if ( ! empty( $status ) )
			$filter['status'] = $status;

		$filter['page'] = $page;

		$query = $this->query_orders( $filter );

		$orders = array();

		foreach( $query->posts as $order_id ) {

			if ( ! $this->is_readable( $order_id ) )
				continue;

			$orders[] = current( $this->get_order( $order_id, $fields ) );
		}

		$this->server->add_pagination_headers( $query );

		return array( 'orders' => $orders );
	}


	/**
	 * Get the order for the given ID
	 *
	 * @since 2.1
	 * @param int $id the order ID
	 * @param array $fields
	 * @return array
	 */
	public function get_order( $id, $fields = null ) {

		// ensure order ID is valid & user has permission to read
		$id = $this->validate_request( $id, 'shop_order', 'read' );

		if ( is_wp_error( $id ) )
			return $id;

		$order = get_order( $id );

		$order_post = get_post( $id );

		$order_data = array(
			'id'                        => $order->id,
			'order_number'              => $order->get_order_number(),
			'created_at'                => $this->server->format_datetime( $order_post->post_date_gmt ),
			'updated_at'                => $this->server->format_datetime( $order_post->post_modified_gmt ),
			'completed_at'              => $this->server->format_datetime( $order->completed_date, true ),
			'status'                    => $order->get_status(),
			'currency'                  => $order->order_currency,
			'total'                     => wc_format_decimal( $order->get_total(), 2 ),
			'subtotal'                  => wc_format_decimal( $this->get_order_subtotal( $order ), 2 ),
			'total_line_items_quantity' => $order->get_item_count(),
			'total_tax'                 => wc_format_decimal( $order->get_total_tax(), 2 ),
			'total_shipping'            => wc_format_decimal( $order->get_total_shipping(), 2 ),
			'cart_tax'                  => wc_format_decimal( $order->get_cart_tax(), 2 ),
			'shipping_tax'              => wc_format_decimal( $order->get_shipping_tax(), 2 ),
			'total_discount'            => wc_format_decimal( $order->get_total_discount(), 2 ),
			'cart_discount'             => wc_format_decimal( $order->get_cart_discount(), 2 ),
			'order_discount'            => wc_format_decimal( $order->get_order_discount(), 2 ),
			'shipping_methods'          => $order->get_shipping_method(),
			'payment_details' => array(
				'method_id'    => $order->payment_method,
				'method_title' => $order->payment_method_title,
				'paid'         => isset( $order->paid_date ),
			),
			'billing_address' => array(
				'first_name' => $order->billing_first_name,
				'last_name'  => $order->billing_last_name,
				'company'    => $order->billing_company,
				'address_1'  => $order->billing_address_1,
				'address_2'  => $order->billing_address_2,
				'city'       => $order->billing_city,
				'state'      => $order->billing_state,
				'postcode'   => $order->billing_postcode,
				'country'    => $order->billing_country,
				'email'      => $order->billing_email,
				'phone'      => $order->billing_phone,
			),
			'shipping_address' => array(
				'first_name' => $order->shipping_first_name,
				'last_name'  => $order->shipping_last_name,
				'company'    => $order->shipping_company,
				'address_1'  => $order->shipping_address_1,
				'address_2'  => $order->shipping_address_2,
				'city'       => $order->shipping_city,
				'state'      => $order->shipping_state,
				'postcode'   => $order->shipping_postcode,
				'country'    => $order->shipping_country,
			),
			'note'                      => $order->customer_note,
			'customer_ip'               => $order->customer_ip_address,
			'customer_user_agent'       => $order->customer_user_agent,
			'customer_id'               => $order->customer_user,
			'view_order_url'            => $order->get_view_order_url(),
			'line_items'                => array(),
			'shipping_lines'            => array(),
			'tax_lines'                 => array(),
			'fee_lines'                 => array(),
			'coupon_lines'              => array(),
		);

		// add line items
		foreach( $order->get_items() as $item_id => $item ) {

			$product = $order->get_product_from_item( $item );

			$order_data['line_items'][] = array(
				'id'           => $item_id,
				'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item ), 2 ),
				'subtotal_tax' => wc_format_decimal( $item['line_subtotal_tax'], 2 ),
				'total'        => wc_format_decimal( $order->get_line_total( $item ), 2 ),
				'total_tax'    => wc_format_decimal( $order->get_line_tax( $item ), 2 ),
				'price'        => wc_format_decimal( $order->get_item_total( $item ), 2 ),
				'quantity'     => (int) $item['qty'],
				'tax_class'    => ( ! empty( $item['tax_class'] ) ) ? $item['tax_class'] : null,
				'name'         => $item['name'],
				'product_id'   => ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id,
				'sku'          => is_object( $product ) ? $product->get_sku() : null,
			);
		}

		// add shipping
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {

			$order_data['shipping_lines'][] = array(
				'id'           => $shipping_item_id,
				'method_id'    => $shipping_item['method_id'],
				'method_title' => $shipping_item['name'],
				'total'        => wc_format_decimal( $shipping_item['cost'], 2 ),
			);
		}

		// add taxes
		foreach ( $order->get_tax_totals() as $tax_code => $tax ) {

			$order_data['tax_lines'][] = array(
				'code'     => $tax_code,
				'title'    => $tax->label,
				'total'    => wc_format_decimal( $tax->amount, 2 ),
				'compound' => (bool) $tax->is_compound,
			);
		}

		// add fees
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {

			$order_data['fee_lines'][] = array(
				'id'        => $fee_item_id,
				'title'     => $fee_item['name'],
				'tax_class' => ( ! empty( $fee_item['tax_class'] ) ) ? $fee_item['tax_class'] : null,
				'total'     => wc_format_decimal( $order->get_line_total( $fee_item ), 2 ),
				'total_tax' => wc_format_decimal( $order->get_line_tax( $fee_item ), 2 ),
			);
		}

		// add coupons
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {

			$order_data['coupon_lines'][] = array(
				'id'     => $coupon_item_id,
				'code'   => $coupon_item['name'],
				'amount' => wc_format_decimal( $coupon_item['discount_amount'], 2 ),
			);
		}

		return array( 'order' => apply_filters( 'woocommerce_api_order_response', $order_data, $order, $fields, $this->server ) );
	}

	/**
	 * Get the total number of orders
	 *
	 * @since 2.1
	 * @param string $status
	 * @param array $filter
	 * @return array
	 */
	public function get_orders_count( $status = null, $filter = array() ) {

		if ( ! empty( $status ) )
			$filter['status'] = $status;

		$query = $this->query_orders( $filter );

		if ( ! current_user_can( 'read_private_shop_orders' ) )
			return new WP_Error( 'woocommerce_api_user_cannot_read_orders_count', __( 'You do not have permission to read the orders count', 'woocommerce' ), array( 'status' => 401 ) );

		return array( 'count' => (int) $query->found_posts );
	}

	/**
	 * Create an order
	 *
	 * @since 2.2
	 * @param array $data raw order data
	 * @return array
	 */
	public function create_order( $data ) {

		// permission check
		if ( ! current_user_can( 'publish_shop_orders' ) ) {
			return new WP_Error( 'woocommerce_api_user_cannot_create_order', __( 'You do not have permission to create orders', 'woocommerce' ), array( 'status' => 401 ) );
		}

		// default order args, note that status is checked for validity in wc_create_order()
		$default_order_args = array(
			'status'        => isset( $data['status'] ) ? $data['status'] : '',
			'customer_note' => isset( $data['customer_note'] ) ? $data['customer_note'] : null,
		);

		// if creating order for existing customer
		if ( ! empty( $data['customer_id'] ) ) {

			// make sure customer exists
			if ( false === get_user_by( 'id', $data['customer_id'] ) ) {
				return new WP_Error( 'woocommerce_api_invalid_customer_id', __( 'Customer ID is invalid', 'woocommerce' ), array( 'status' => 400 ) );
			}

			$default_order_args['customer_id'] = $data['customer_id'];
		}

		// create the pending order
		$order = wc_create_order( $default_order_args );

		if ( is_wp_error( $order ) ) {
			return new WP_Error( 'woocommerce_api_cannot_create_order', sprintf( __( 'Cannot create order: %s', 'woocommerce' ), implode( ', ', $order->get_error_messages() ) ), array( 'status' => 400 ) );
		}

		$address_fields = array(
			'first_name',
			'last_name',
			'company',
			'email',
			'phone',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
		);

		$billing_address = $shipping_address = array();

		// billing address
		if ( isset( $data['billing_address'] ) && is_array( $data['billing_address'] ) ) {

			foreach ( $address_fields as $field ) {

				if ( isset( $data['billing_address'][ $field ] ) ) {
					$billing_address[ $field ] = wc_clean( $data['billing_address'][ $field ] );
				}
			}

			unset( $address_fields['email'] );
			unset( $address_fields['phone'] );
		}

		// shipping address
		if ( isset( $data['shipping_address'] ) && is_array( $data['shipping_address'] ) ) {

			foreach ( $address_fields as $field ) {

				if ( isset( $data['shipping_address'][ $field ] ) ) {
					$shipping_address[ $field ] = wc_clean( $data['shipping_address'][ $field ] );
				}
			}
		}

		$order->set_address( $billing_address, 'billing' );
		$order->set_address( $shipping_address, 'shipping' );

		// update user meta
		if ( $order->get_user_id() ) {
				foreach( $billing_address as $key => $value ) {
					update_user_meta( $order->get_user_id(), 'billing_' . $key, $value );
				}
				foreach( $shipping_address as $key => $value ) {
					update_user_meta( $order->get_user_id(), 'shipping_' . $key, $value );
				}
		}

		// set line items
		if ( isset( $data['line_items'] ) && is_array( $data['line_items'] ) ) {

			foreach ( $data['line_items'] as $item ) {

				$product = get_product( $item['product_id'] );

				// must be a valid WC_Product
				if ( ! is_object( $product ) ) {
					return new WP_Error( 'woocommerce_api_invalid_product_id', __( 'Product ID is invalid', 'woocommerce' ), array( 'status' => 400 ) );
				}

				// quantity must be positive integer
				if ( ! isset( $item['quantity'] ) || 0 === absint( $item['quantity'] ) ) {
					return new WP_Error( 'woocommerce_api_invalid_product_quantity', __( 'Product quantity is required and must be a positive integer', 'woocommerce' ), array( 'status' => 400 ) );
				}

				$item_args = array();

				// variations must each have a key & value
				if ( isset( $item['variations'] ) && is_array( $item['variations'] ) ) {
					foreach ( $item['variations'] as $key => $value ) {
						if ( ! $key || ! $value ) {
							return new WP_Error( 'woocommerce_api_invalid_variation', __( 'The product variation is invalid', 'woocommerce' ), array( 'status' => 400 ) );
						}
					}
					$item_args['variation'] = $item['variations'];
				}

				// total
				if ( isset( $item['total'] ) ) {
					$item_args['totals']['line_total'] = $item['total'];
				}

				// total tax
				if ( isset( $item['total_tax'] ) ) {
					$item_args['totals']['line_tax'] = $item['total_tax'];
				}

				// subtotal
				if ( isset( $item['subtotal'] ) ) {
					$item_args['totals']['line_subtotal'] = $item['subtotal'];
				}

				// subtotal tax
				if ( isset( $item['subtotal_tax'] ) ) {
					$item_args['totals']['line_subtotal_tax'] = $item['subtotal_tax'];
				}

				$item_id = $order->add_product( $product, $item['quantity'], $item_args );

				if ( ! $item_id ) {
					return new WP_Error( 'woocommerce_cannot_create_line_item', __( 'Cannot create line item, try again', 'woocommerce' ), array( 'status' => 500 ) );
				}
			}
		}

		// set shipping
		if ( isset( $data['shipping_lines'] ) && is_array( $data['shipping_lines'] ) ) {

			foreach ( $data['shipping_lines'] as $shipping ) {

				// method ID and title are required
				if ( empty( $shipping['method_id'] ) || empty( $shipping['method_title'] ) ) {
					return new WP_Error( 'woocommerce_invalid_shipping_line_item', __( 'Shipping method ID and Title are required', 'woocommerce' ), array( 'status' => 400 ) );
				}

				// total must be a positive float
				if ( empty( $shipping['total'] ) || floatval( $shipping['total'] ) < 0 ) {
					return new WP_Error( 'woocommerce_invalid_shipping_line_total', __( 'Shipping total is required and must be a positive amount', 'woocommerce' ), array( 'status' => 400 ) );
				}

				$rate = new WC_Shipping_Rate( $shipping['method_id'], $shipping['method_title'], floatval( $shipping['total'] ), array(), $shipping['method_id'] );

				$item_id = $order->add_shipping( $rate );

				if ( ! $item_id ) {
					return new WP_Error( 'woocommerce_cannot_create_shipping_line_item', __( 'Cannot create shipping line item, try again', 'woocommerce' ), array( 'status' => 500 ) );
				}
			}
		}

		// set fees
		if ( isset( $data['fee_lines'] ) && is_array( $data['fee_lines'] ) ) {

			foreach ( $data['fee_lines'] as $fee ) {

				// fee title is required
				if ( empty( $fee['title'] ) ) {
					return new WP_Error( 'woocommerce_invalid_fee_line_item', __( 'Fee title is required', 'woocommerce' ), array( 'status' => 400 ) );
				}

				// fee amount is required and must be positive float
				if ( empty( $fee['total'] ) || floatval( $fee['total'] ) < 0 ) {
					return new WP_Error( 'woocommerce_invalid_fee_line_total', __( 'Fee total is required and must be a positive amount', 'woocommerce' ), array( 'status' => 400 ) );
				}

				$order_fee         = new stdClass();
				$order_fee->id     = sanitize_title( $fee['title'] );
				$order_fee->name   = $fee['title'];
				$order_fee->amount = floatval( $fee['total'] );

				// if taxable, tax class and total are required
				if ( isset( $fee['taxable'] ) && 'true' === $fee['taxable'] ) {

					if ( empty( $fee['tax_class'] ) ) {
						return new WP_Error( 'woocommerce_invalid_fee_line_item', __( 'Fee tax class is required when fee is taxable', 'woocommerce' ), array( 'status' => 400 ) );
					}

					if ( empty( $fee['total_tax'] ) || floatval( $fee['total_tax'] ) < 0 ) {
						return new WP_Error( 'woocommerce_invalid_fee_line_total', __( 'Fee tax total is required and must be a positive amount when fee is taxable', 'woocommerce' ), array( 'status' => 400 ) );
					}

					$order_fee->taxable   = true;
					$order_fee->tax_class = $fee['tax_class'];
					$order_fee->tax       = floatval( $fee['total_tax'] );
				}

				$item_id = $order->add_fee( $order_fee );

				if ( ! $item_id ) {
					return new WP_Error( 'woocommerce_cannot_create_fee_line_item', __( 'Cannot create fee line item, try again', 'woocommerce' ), array( 'status' => 500 ) );
				}
			}
		}

		// set coupons
		if ( isset( $data['coupon_lines'] ) && is_array( $data['coupon_lines'] ) ) {

			foreach ( $data['coupon_lines'] as $coupon ) {

				// coupon code is required
				if ( empty( $coupon['code'] ) ) {
					return new WP_Error( 'woocommerce_invalid_coupon_line_item', __( 'Coupon code is required', 'woocommerce' ), array( 'status' => 400 ) );
				}

				// coupon discount amount is required and must be positive float
				if ( empty( $coupon['amount'] ) || floatval( $coupon['amount'] ) < 0 ) {
					return new WP_Error( 'woocommerce_invalid_coupon_line_total', __( 'Coupon discount total is required and must be a positive amount', 'woocommerce' ), array( 'status' => 400 ) );
				}

				$item_id = $order->add_coupon( $coupon['code'], floatval( $coupon['amount'] ) );

				if ( ! $item_id ) {
					return new WP_Error( 'woocommerce_cannot_create_coupon_line_item', __( 'Cannot create coupon line item, try again', 'woocommerce' ), array( 'status' => 500 ) );
				}
			}
		}

		// calculate totals and set them
		$order->calculate_totals();

		// if taxes are provided, override the calculated totals and set them
		if ( isset( $data['tax_lines'] ) && is_array( $data['tax_lines'] ) ) {

			foreach ( $data['tax_lines'] as $tax ) {

				// tax id is required
				if ( empty( $tax['id'] ) ) {
					return new WP_Error( 'woocommerce_invalid_tax_line_item', __( 'Tax ID is required', 'woocommerce' ), array( 'status' => 400 ) );
				}

				// tax amount is required and must be a positive float
				if ( empty( $tax['total'] ) || floatval( $tax['total'] ) < 0 ) {
					return new WP_Error( 'woocommerce_invalid_tax_line_total', __( 'Tax total is required and must be a positive amount', 'woocommerce' ), array( 'status' => 400 ) );
				}

				// if shipping tax amount is provided, it must be a positive float
				if ( isset( $tax['shipping_total'] ) && floatval( $tax['shipping_total'] ) < 0 ) {
					return new WP_Error( 'woocommerce_invalid_tax_line_shipping_total', __( 'Tax shipping total must be a positive amount', 'woocommerce' ), array( 'status' => 400 ) );
				}

				$item_id = $order->add_tax( $tax['id'], floatval( $tax['total'] ), isset( $tax['shipping_total'] ) ? floatval( $tax['shipping_total'] ) : 0 );

				if ( ! $item_id ) {
					return new WP_Error( 'woocommerce_cannot_create_tax_line_item', __( 'Cannot create tax line item, try again', 'woocommerce' ), array( 'status' => 500 ) );
				}
			}
		}

		// payment method (and payment_complete() if `paid` == true)
		if ( isset( $data['payment_details'] ) && is_array( $data['payment_details'] ) ) {

			// method ID & title are required
			if ( empty( $data['payment_details']['method_id'] ) || empty( $data['payment_details']['method_title'] ) ) {
				return new WP_Error( 'woocommerce_invalid_payment_details', __( 'Payment method ID and title are required', 'woocommerce' ), array( 'status' => 400 ) );
			}

			update_post_meta( $order->id, '_payment_method', $data['payment_details']['method_id'] );
			update_post_meta( $order->id, '_payment_method_title', $data['payment_details']['method_title'] );

			// mark as paid if set
			if ( isset( $data['payment_details']['paid'] ) && 'true' === $data['payment_details']['paid'] ) {
				$order->payment_complete( isset( $data['payment_details']['transaction_id'] ) ? $data['payment_details']['transaction_id'] : '' );
			}
		}

		// set order currency
		if ( isset( $data['currency'] ) ) {

			if ( ! array_key_exists( $data['currency'], get_woocommerce_currencies() ) ) {
				return new WP_Error( 'woocommerce_invalid_order_currency', __( 'Provided order currency is invalid', 'woocommerce'), array( 'status' => 400 ) );
			}

			update_post_meta( $order->id, '_order_currency', $data['currency'] );
		}

		// TODO: should we clients to set order meta?

		$this->server->send_status( 201 );

		return $this->get_order( $order->id );
	}

	/**
	 * Edit an order
	 *
	 * @since 2.1
	 * @param int $id the order ID
	 * @param array $data
	 * @return array
	 */
	public function edit_order( $id, $data ) {

		// see wc_update_order()

		$id = $this->validate_request( $id, 'shop_order', 'edit' );

		if ( is_wp_error( $id ) )
			return $id;

		$order = get_order( $id );

		if ( ! empty( $data['status'] ) ) {

			$order->update_status( $data['status'], isset( $data['note'] ) ? $data['note'] : '' );
		}

		return $this->get_order( $id );
	}

	/**
	 * Delete an order
	 *
	 * @param int $id the order ID
	 * @param bool $force true to permanently delete order, false to move to trash
	 * @return array
	 */
	public function delete_order( $id, $force = false ) {

		$id = $this->validate_request( $id, 'shop_order', 'delete' );

		return $this->delete( $id, 'order',  ( 'true' === $force ) );
	}

	/**
	 * Get the admin order notes for an order
	 *
	 * @since 2.1
	 * @param int $id the order ID
	 * @param string $fields fields to include in response
	 * @return array
	 */
	public function get_order_notes( $id, $fields = null ) {

		// ensure ID is valid order ID
		$id = $this->validate_request( $id, 'shop_order', 'read' );

		if ( is_wp_error( $id ) )
			return $id;

		$args = array(
			'post_id' => $id,
			'approve' => 'approve',
			'type'    => 'order_note'
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$order_notes = array();

		foreach ( $notes as $note ) {

			$order_notes[] = array(
				'id'            => $note->comment_ID,
				'created_at'    => $this->server->format_datetime( $note->comment_date_gmt ),
				'note'          => $note->comment_content,
				'customer_note' => get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ? true : false,
			);
		}

		return array( 'order_notes' => apply_filters( 'woocommerce_api_order_notes_response', $order_notes, $id, $fields, $notes, $this->server ) );
	}

	/**
	 * Helper method to get order post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	private function query_orders( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order'
		);

		// add status argument
		if ( ! empty( $args['status'] ) ) {
			$statuses                  = explode( ',', $args['status'] );
			$query_args['post_status'] = $statuses;

			unset( $args['status'] );
		} else {
			$query_args['post_status'] = array_keys( wc_get_order_statuses() );
		}

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}

	/**
	 * Helper method to get the order subtotal
	 *
	 * @since 2.1
	 * @param WC_Order $order
	 * @return float
	 */
	private function get_order_subtotal( $order ) {

		$subtotal = 0;

		// subtotal
		foreach ( $order->get_items() as $item ) {

			$subtotal += ( isset( $item['line_subtotal'] ) ) ? $item['line_subtotal'] : 0;
		}

		return $subtotal;
	}

}
