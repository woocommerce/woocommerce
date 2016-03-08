<?php

/**
 * Manage Orders.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Order extends WC_CLI_Command {

	/**
	 * Create an order.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new order.
	 *
	 * [--porcelain]
	 * : Outputs just the new order id.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * Required fields:
	 *
	 * * customer_id
	 *
	 * Optional fields:
	 *
	 * * status
	 * * note
	 * * currency
	 * * order_meta
	 *
	 * Payment detail fields:
	 *
	 * * payment_details.method_id
	 * * payment_details.method_title
	 * * payment_details.paid
	 *
	 * Billing address fields:
	 *
	 * * billing_address.first_name
	 * * billing_address.last_name
	 * * billing_address.company
	 * * billing_address.address_1
	 * * billing_address.address_2
	 * * billing_address.city
	 * * billing_address.state
	 * * billing_address.postcode
	 * * billing_address.country
	 * * billing_address.email
	 * * billing_address.phone
	 *
	 * Shipping address fields:
	 *
	 * * shipping_address.first_name
	 * * shipping_address.last_name
	 * * shipping_address.company
	 * * shipping_address.address_1
	 * * shipping_address.address_2
	 * * shipping_address.city
	 * * shipping_address.state
	 * * shipping_address.postcode
	 * * shipping_address.country
	 *
	 * Line item fields (numeric array, started with index zero):
	 *
	 * * line_items.0.product_id
	 * * line_items.0.quantity
	 * * line_items.0.variations.pa_color
	 *
	 * For second line item: line_items.1.product_id and so on.
	 *
	 * Shipping line fields (numeric array, started with index zero):
	 *
	 * * shipping_lines.0.method_id
	 * * shipping_lines.0.method_title
	 * * shipping_lines.0.total
	 *
	 * For second shipping item: shipping_lines.1.method_id and so on.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc order create --customer_id=1 --status=pending ...
	 *
	 * @since  2.5.0
	 */
	public function create( $__, $assoc_args ) {
		global $wpdb;

		wc_transaction_query( 'start' );

		try {
			$porcelain = isset( $assoc_args['porcelain'] );
			unset( $assoc_args['porcelain'] );

			$data = apply_filters( 'woocommerce_cli_create_order_data', $this->unflatten_array( $assoc_args ) );

			// default order args, note that status is checked for validity in wc_create_order()
			$default_order_args = array(
				'status'        => isset( $data['status'] ) ? $data['status'] : '',
				'customer_note' => isset( $data['note'] ) ? $data['note'] : null,
			);

			if ( empty( $data['customer_id'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_missing_customer_id', __( 'Missing customer_id field', 'woocommerce' ) );
			}

			// make sure customer exists
			if ( false === get_user_by( 'id', $data['customer_id'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_customer_id', __( 'Customer ID is invalid', 'woocommerce' ) );
			}
			$default_order_args['customer_id'] = $data['customer_id'];

			// create the pending order
			$order = $this->create_base_order( $default_order_args, $data );

			if ( is_wp_error( $order ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_cannot_create_order', sprintf( __( 'Cannot create order: %s', 'woocommerce' ), implode( ', ', $order->get_error_messages() ) ) );
			}

			// billing/shipping addresses
			$this->set_order_addresses( $order, $data );

			$lines = array(
				'line_item' => 'line_items',
				'shipping'  => 'shipping_lines',
				'fee'       => 'fee_lines',
				'coupon'    => 'coupon_lines',
			);

			foreach ( $lines as $line_type => $line ) {
				if ( isset( $data[ $line ] ) && is_array( $data[ $line ] ) ) {
					$set_item = "set_{$line_type}";
					foreach ( $data[ $line ] as $item ) {
						$this->$set_item( $order, $item, 'create' );
					}
				}
			}

			// calculate totals and set them
			$order->calculate_totals();

			// payment method (and payment_complete() if `paid` == true)
			if ( isset( $data['payment_details'] ) && is_array( $data['payment_details'] ) ) {
				// method ID & title are required
				if ( empty( $data['payment_details']['method_id'] ) || empty( $data['payment_details']['method_title'] ) ) {
					throw new WC_CLI_Exception( 'woocommerce_invalid_payment_details', __( 'Payment method ID and title are required', 'woocommerce' ) );
				}

				update_post_meta( $order->id, '_payment_method', $data['payment_details']['method_id'] );
				update_post_meta( $order->id, '_payment_method_title', $data['payment_details']['method_title'] );

				// Mark as paid if set.
				if ( isset( $data['payment_details']['paid'] ) && $this->is_true( $data['payment_details']['paid'] ) ) {
					$order->payment_complete( isset( $data['payment_details']['transaction_id'] ) ? $data['payment_details']['transaction_id'] : '' );
				}
			}

			// Set order currency.
			if ( isset( $data['currency'] ) ) {
				if ( ! array_key_exists( $data['currency'], get_woocommerce_currencies() ) ) {
					throw new WC_CLI_Exception( 'woocommerce_invalid_order_currency', __( 'Provided order currency is invalid', 'woocommerce') );
				}

				update_post_meta( $order->id, '_order_currency', $data['currency'] );
			}

			// Set order meta.
			if ( isset( $data['order_meta'] ) && is_array( $data['order_meta'] ) ) {
				$this->set_order_meta( $order->id, $data['order_meta'] );
			}

			wc_delete_shop_order_transients( $order->id );

			do_action( 'woocommerce_cli_create_order', $order->id, $data );

			wc_transaction_query( 'commit' );

			if ( $porcelain ) {
				WP_CLI::line( $order->id );
			} else {
				WP_CLI::success( "Created order {$order->id}." );
			}
		} catch ( WC_CLI_Exception $e ) {
			wc_transaction_query( 'rollback' );

			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Delete one or more orders.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The order ID to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc order delete 123
	 *
	 * @since 2.5.0
	 */
	public function delete( $args, $assoc_args ) {
		$exit_code = 0;
		foreach ( $args as $id ) {
			$order = wc_get_order( $id );
			if ( ! $order ) {
				WP_CLI::warning( "Invalid order ID $id" );
				continue;
			}

			wc_delete_shop_order_transients( $id );
			do_action( 'woocommerce_cli_delete_order', $id );
			$r = wp_delete_post( $id, true );

			if ( $r ) {
				WP_CLI::success( "Deleted order $id." );
			} else {
				$exit_code += 1;
				WP_CLI::warning( "Failed deleting order $id." );
			}
		}
		exit( $exit_code ? 1 : 0 );
	}

	/**
	 * Get an order.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Order ID.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole order fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the order's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default:
	 *
	 * * id
	 * * order_number
	 * * customer_id
	 * * total
	 * * status
	 * * created_at
	 *
	 * For more fields, see: wp wc order list --help
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc order get 123 --fields=id,title,sku
	 *
	 * @since 2.5.0
	 */
	public function get( $args, $assoc_args ) {
		try {
			$order = wc_get_order( $args[0] );
			if ( ! $order ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_order', sprintf( __( 'Invalid order "%s"', 'woocommerce' ), $args[0] ) );
			}
			$order_data = $this->get_order_data( $order );

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $order_data );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * List orders.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter orders based on order property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each order.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific order fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each order:
	 *
	 * * id
	 * * order_number
	 * * customer_id
	 * * total
	 * * status
	 * * created_at
	 *
	 * These fields are optionally available:
	 *
	 * * updated_at
	 * * completed_at
	 * * currency
	 * * subtotal
	 * * total_line_items_quantity
	 * * total_tax
	 * * total_shipping
	 * * cart_tax
	 * * shipping_tax
	 * * total_discount
	 * * shipping_methods
	 * * note
	 * * customer_ip
	 * * customer_user_agent
	 * * view_order_url
	 *
	 * Payment detail fields:
	 *
	 * * payment_details.method_id
	 * * payment_details.method_title
	 * * payment_details.paid
	 *
	 * Billing address fields:
	 *
	 * * billing_address.first_name
	 * * billing_address.last_name
	 * * billing_address.company
	 * * billing_address.address_1
	 * * billing_address.address_2
	 * * billing_address.city
	 * * billing_address.state
	 * * billing_address.postcode
	 * * billing_address.country
	 * * billing_address.email
	 * * billing_address.phone
	 *
	 * Shipping address fields:
	 *
	 * * shipping_address.first_name
	 * * shipping_address.last_name
	 * * shipping_address.company
	 * * shipping_address.address_1
	 * * shipping_address.address_2
	 * * shipping_address.city
	 * * shipping_address.state
	 * * shipping_address.postcode
	 * * shipping_address.country
	 *
	 * Line item fields (numeric array, started with index zero):
	 *
	 * * line_items.0.product_id
	 * * line_items.0.quantity
	 * * line_items.0.variations.pa_color
	 *
	 * For second line item: line_items.1.product_id and so on.
	 *
	 * Shipping line fields (numeric array, started with index zero):
	 *
	 * * shipping_lines.0.method_id
	 * * shipping_lines.0.method_title
	 * * shipping_lines.0.total
	 *
	 * For second shipping item: shipping_lines.1.method_id and so on.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc order list
	 *
	 * @subcommand list
	 * @since      2.5.0
	 */
	public function list_( $args, $assoc_args ) {
		$query_args = $this->merge_wp_query_args( $this->get_list_query_args( $assoc_args ), $assoc_args );
		$formatter  = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query = new WP_Query( $query_args );
			echo implode( ' ', $query->posts );
		} else {
			$query = new WP_Query( $query_args );
			$items = $this->format_posts_to_items( $query->posts );
			$formatter->display_items( $items );
		}
	}

	/**
	 * Update an order.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Product ID
	 *
	 * [--<field>=<value>]
	 * : One or more fields to update.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * For available fields, see: wp wc order create --help
	 *
	 * ## EXAMPLES
	 *
	 *    wp wc order update 123 --status=completed
	 *
	 * @todo  gedex
	 * @since 2.5.0
	 */
	public function update( $args, $assoc_args ) {
		try {
			$id            = $args[0];
			$data          = apply_filters( 'woocommerce_cli_update_order_data', $this->unflatten_array( $assoc_args ) );
			$update_totals = false;
			$order         = wc_get_order( $id );

			if ( empty( $order ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_order_id', __( 'Order ID is invalid', 'woocommerce' ) );
			}

			$order_args = array( 'order_id' => $order->id );

			// customer note
			if ( isset( $data['note'] ) ) {
				$order_args['customer_note'] = $data['note'];
			}

			// order status
			if ( ! empty( $data['status'] ) ) {

				$order->update_status( $data['status'], isset( $data['status_note'] ) ? $data['status_note'] : '' );
			}

			// customer ID
			if ( isset( $data['customer_id'] ) && $data['customer_id'] != $order->get_user_id() ) {

				// make sure customer exists
				if ( false === get_user_by( 'id', $data['customer_id'] ) ) {
					throw new WC_CLI_Exception( 'woocommerce_cli_invalid_customer_id', __( 'Customer ID is invalid', 'woocommerce' ) );
				}

				update_post_meta( $order->id, '_customer_user', $data['customer_id'] );
			}

			// billing/shipping address
			$this->set_order_addresses( $order, $data );

			$lines = array(
				'line_item' => 'line_items',
				'shipping'  => 'shipping_lines',
				'fee'       => 'fee_lines',
				'coupon'    => 'coupon_lines',
			);

			foreach ( $lines as $line_type => $line ) {

				if ( isset( $data[ $line ] ) && is_array( $data[ $line ] ) ) {

					$update_totals = true;

					foreach ( $data[ $line ] as $item ) {

						// item ID is always required
						if ( ! array_key_exists( 'id', $item ) ) {
							throw new WC_CLI_Exception( 'woocommerce_invalid_item_id', __( 'Order item ID is required', 'woocommerce' ) );
						}

						// create item
						if ( is_null( $item['id'] ) ) {

							$this->set_item( $order, $line_type, $item, 'create' );

						} elseif ( $this->item_is_null( $item ) ) {

							// delete item
							wc_delete_order_item( $item['id'] );

						} else {

							// update item
							$this->set_item( $order, $line_type, $item, 'update' );
						}
					}
				}
			}

			// payment method (and payment_complete() if `paid` == true and order needs payment)
			if ( isset( $data['payment_details'] ) && is_array( $data['payment_details'] ) ) {

				// method ID
				if ( isset( $data['payment_details']['method_id'] ) ) {
					update_post_meta( $order->id, '_payment_method', $data['payment_details']['method_id'] );
				}

				// method title
				if ( isset( $data['payment_details']['method_title'] ) ) {
					update_post_meta( $order->id, '_payment_method_title', $data['payment_details']['method_title'] );
				}

				// mark as paid if set
				if ( $order->needs_payment() && isset( $data['payment_details']['paid'] ) && $this->is_true( $data['payment_details']['paid'] ) ) {
					$order->payment_complete( isset( $data['payment_details']['transaction_id'] ) ? $data['payment_details']['transaction_id'] : '' );
				}
			}

			// set order currency
			if ( isset( $data['currency'] ) ) {

				if ( ! array_key_exists( $data['currency'], get_woocommerce_currencies() ) ) {
					throw new WC_CLI_Exception( 'woocommerce_invalid_order_currency', __( 'Provided order currency is invalid', 'woocommerce' ) );
				}

				update_post_meta( $order->id, '_order_currency', $data['currency'] );
			}

			// set order number
			if ( isset( $data['order_number'] ) ) {

				update_post_meta( $order->id, '_order_number', $data['order_number'] );
			}

			// if items have changed, recalculate order totals
			if ( $update_totals ) {
				$order->calculate_totals();
			}

			// update order meta
			if ( isset( $data['order_meta'] ) && is_array( $data['order_meta'] ) ) {
				$this->set_order_meta( $order->id, $data['order_meta'] );
			}

			// update the order post to set customer note/modified date
			wc_update_order( $order_args );

			wc_delete_shop_order_transients( $order->id );

			do_action( 'woocommerce_cli_update_order', $order->id, $data );

			WP_CLI::success( "Updated order {$order->id}." );

		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Get query args for list subcommand.
	 *
	 * @since  2.5.0
	 * @param  array $args Args from command line
	 * @return array
	 */
	protected function get_list_query_args( $args ) {
		$query_args = array(
			'post_type'      => 'shop_order',
			'post_status'    => array_keys( wc_get_order_statuses() ),
			'posts_per_page' => -1,
		);

		if ( ! empty( $args['status'] ) ) {
			$statuses                  = 'wc-' . str_replace( ',', ',wc-', $args['status'] );
			$statuses                  = explode( ',', $statuses );
			$query_args['post_status'] = $statuses;
		}

		if ( ! empty( $args['customer_id'] ) ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_customer_user',
					'value' => (int) $args['customer_id'],
					'compare' => '='
				)
			);
		}

		return $query_args;
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,order_number,customer_id,total,status,created_at';
	}

	/**
	 * Format posts from WP_Query result to items in which each item contain
	 * common properties of item.
	 *
	 * @since  2.5.0
	 * @param  array $posts Array of post
	 * @return array Items
	 */
	protected function format_posts_to_items( $posts ) {
		$items = array();
		foreach ( $posts as $post ) {
			$order = wc_get_order( $post->ID );
			if ( ! $order ) {
				continue;
			}
			$items[] = $this->get_order_data( $order );
		}

		return $items;
	}

	/**
	 * Get the order data for the given ID.
	 *
	 * @since  2.5.0
	 * @param  WC_Order $order The order instance
	 * @return array
	 */
	protected function get_order_data( $order ) {
		$order_post = get_post( $order->id );
		$dp         = wc_get_price_decimals();
		$order_data = array(
			'id'                        => $order->id,
			'order_number'              => $order->get_order_number(),
			'created_at'                => $this->format_datetime( $order_post->post_date_gmt ),
			'updated_at'                => $this->format_datetime( $order_post->post_modified_gmt ),
			'completed_at'              => $this->format_datetime( $order->completed_date, true ),
			'status'                    => $order->get_status(),
			'currency'                  => $order->get_order_currency(),
			'total'                     => wc_format_decimal( $order->get_total(), $dp ),
			'subtotal'                  => wc_format_decimal( $order->get_subtotal(), $dp ),
			'total_line_items_quantity' => $order->get_item_count(),
			'total_tax'                 => wc_format_decimal( $order->get_total_tax(), $dp ),
			'total_shipping'            => wc_format_decimal( $order->get_total_shipping(), $dp ),
			'cart_tax'                  => wc_format_decimal( $order->get_cart_tax(), $dp ),
			'shipping_tax'              => wc_format_decimal( $order->get_shipping_tax(), $dp ),
			'total_discount'            => wc_format_decimal( $order->get_total_discount(), $dp ),
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
			'customer_id'               => $order->get_user_id(),
			'view_order_url'            => $order->get_view_order_url(),
			'line_items'                => array(),
			'shipping_lines'            => array(),
			'tax_lines'                 => array(),
			'fee_lines'                 => array(),
			'coupon_lines'              => array(),
		);

		// add line items
		foreach ( $order->get_items() as $item_id => $item ) {

			$product     = $order->get_product_from_item( $item );
			$product_id  = null;
			$product_sku = null;

			// Check if the product exists.
			if ( is_object( $product ) ) {
				$product_id  = ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id;
				$product_sku = $product->get_sku();
			}

			$meta = new WC_Order_Item_Meta( $item, $product );
			$item_meta = array();
			foreach ( $meta->get_formatted( null ) as $meta_key => $formatted_meta ) {
				$item_meta[] = array(
					'key' => $meta_key,
					'label' => $formatted_meta['label'],
					'value' => $formatted_meta['value'],
				);
			}

			$order_data['line_items'][] = array(
				'id'           => $item_id,
				'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item, false, false ), $dp ),
				'subtotal_tax' => wc_format_decimal( $item['line_subtotal_tax'], $dp ),
				'total'        => wc_format_decimal( $order->get_line_total( $item, false, false ), $dp ),
				'total_tax'    => wc_format_decimal( $item['line_tax'], $dp ),
				'price'        => wc_format_decimal( $order->get_item_total( $item, false, false ), $dp ),
				'quantity'     => wc_stock_amount( $item['qty'] ),
				'tax_class'    => ( ! empty( $item['tax_class'] ) ) ? $item['tax_class'] : null,
				'name'         => $item['name'],
				'product_id'   => $product_id,
				'sku'          => $product_sku,
				'meta'         => $item_meta,
			);
		}

		// Add shipping.
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
			$order_data['shipping_lines'][] = array(
				'id'           => $shipping_item_id,
				'method_id'    => $shipping_item['method_id'],
				'method_title' => $shipping_item['name'],
				'total'        => wc_format_decimal( $shipping_item['cost'], $dp ),
			);
		}

		// Add taxes.
		foreach ( $order->get_tax_totals() as $tax_code => $tax ) {
			$order_data['tax_lines'][] = array(
				'id'       => $tax->id,
				'rate_id'  => $tax->rate_id,
				'code'     => $tax_code,
				'title'    => $tax->label,
				'total'    => wc_format_decimal( $tax->amount, $dp ),
				'compound' => (bool) $tax->is_compound,
			);
		}

		// Add fees.
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
			$order_data['fee_lines'][] = array(
				'id'        => $fee_item_id,
				'title'     => $fee_item['name'],
				'tax_class' => ( ! empty( $fee_item['tax_class'] ) ) ? $fee_item['tax_class'] : null,
				'total'     => wc_format_decimal( $order->get_line_total( $fee_item ), $dp ),
				'total_tax' => wc_format_decimal( $order->get_line_tax( $fee_item ), $dp ),
			);
		}

		// Add coupons.
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
			$order_data['coupon_lines'][] = array(
				'id'     => $coupon_item_id,
				'code'   => $coupon_item['name'],
				'amount' => wc_format_decimal( $coupon_item['discount_amount'], $dp ),
			);
		}

		$order_data = apply_filters( 'woocommerce_cli_order_data', $order_data );

		return $this->flatten_array( $order_data );
	}

	/**
	 * Creates new WC_Order.
	 *
	 * @since  2.5.0
	 * @param  $args array
	 * @return WC_Order
	 */
	protected function create_base_order( $args ) {
		return wc_create_order( $args );
	}

	/**
	 * Helper method to set/update the billing & shipping addresses for an order.
	 *
	 * @since 2.5.0
	 * @param WC_Order $order
	 * @param array $data
	 */
	protected function set_order_addresses( $order, $data ) {
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

		// billing address.
		if ( isset( $data['billing_address'] ) && is_array( $data['billing_address'] ) ) {
			foreach ( $address_fields as $field ) {
				if ( isset( $data['billing_address'][ $field ] ) ) {
					$billing_address[ $field ] = wc_clean( $data['billing_address'][ $field ] );
				}
			}

			unset( $address_fields['email'] );
			unset( $address_fields['phone'] );
		}

		// shipping address.
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
			foreach ( $billing_address as $key => $value ) {
				update_user_meta( $order->get_user_id(), 'billing_' . $key, $value );
			}
			foreach ( $shipping_address as $key => $value ) {
				update_user_meta( $order->get_user_id(), 'shipping_' . $key, $value );
			}
		}
	}

	/**
	 * Helper method to add/update order meta, with two restrictions:
	 *
	 * 1) Only non-protected meta (no leading underscore) can be set
	 * 2) Meta values must be scalar (int, string, bool)
	 *
	 * @since 2.5.0
	 * @param int $order_id valid order ID
	 * @param array $order_meta order meta in array( 'meta_key' => 'meta_value' ) format
	 */
	protected function set_order_meta( $order_id, $order_meta ) {

		foreach ( $order_meta as $meta_key => $meta_value ) {

			if ( is_string( $meta_key) && ! is_protected_meta( $meta_key ) && is_scalar( $meta_value ) ) {
				update_post_meta( $order_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Wrapper method to create/update order items
	 *
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @since  2.5.0
	 * @param  WC_Order $order order
	 * @param  string $item_type
	 * @param  array $item item provided in the request body
	 * @param  string $action either 'create' or 'update'
	 * @throws WC_CLI_Exception if item ID is not associated with order
	 */
	protected function set_item( $order, $item_type, $item, $action ) {
		global $wpdb;

		$set_method = "set_{$item_type}";

		// verify provided line item ID is associated with order
		if ( 'update' === $action ) {
			$result = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d AND order_id = %d",
				absint( $item['id'] ),
				absint( $order->id )
			) );

			if ( is_null( $result ) ) {
				throw new WC_CLI_Exception( 'woocommerce_invalid_item_id', __( 'Order item ID provided is not associated with order', 'woocommerce' ) );
			}
		}

		$this->$set_method( $order, $item, $action );
	}

	/**
	 * Create or update a line item
	 *
	 * @since  2.5.0
	 * @param  WC_Order $order
	 * @param  array $item line item data
	 * @param  string $action 'create' to add line item or 'update' to update it
	 * @throws WC_CLI_Exception invalid data, server error
	 */
	protected function set_line_item( $order, $item, $action ) {

		$creating = ( 'create' === $action );

		// product is always required
		if ( ! isset( $item['product_id'] ) && ! isset( $item['sku'] ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_id', __( 'Product ID or SKU is required', 'woocommerce' ) );
		}

		// when updating, ensure product ID provided matches
		if ( 'update' === $action ) {

			$item_product_id   = wc_get_order_item_meta( $item['id'], '_product_id' );
			$item_variation_id = wc_get_order_item_meta( $item['id'], '_variation_id' );

			if ( $item['product_id'] != $item_product_id && $item['product_id'] != $item_variation_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_id', __( 'Product ID provided does not match this line item', 'woocommerce' ) );
			}
		}

		if ( isset( $item['product_id'] ) ) {
			$product_id = $item['product_id'];
		} elseif ( isset( $item['sku'] ) ) {
			$product_id = wc_get_product_id_by_sku( $item['sku'] );
		}

		// variations must each have a key & value
		$variation_id = 0;
		if ( isset( $item['variations'] ) && is_array( $item['variations'] ) ) {
			foreach ( $item['variations'] as $key => $value ) {
				if ( ! $key || ! $value ) {
					throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_variation', __( 'The product variation is invalid', 'woocommerce' ) );
				}
			}
			$item_args['variation'] = $item['variations'];
			$variation_id = $this->get_variation_id( wc_get_product( $product_id ), $item_args['variation'] );
		}

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		// must be a valid WC_Product
		if ( ! is_object( $product ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product', __( 'Product is invalid', 'woocommerce' ) );
		}

		// quantity must be positive float
		if ( isset( $item['quantity'] ) && floatval( $item['quantity'] ) <= 0 ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_quantity', __( 'Product quantity must be a positive float', 'woocommerce' ) );
		}

		// quantity is required when creating
		if ( $creating && ! isset( $item['quantity'] ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_quantity', __( 'Product quantity is required', 'woocommerce' ) );
		}

		$item_args = array();

		// quantity
		if ( isset( $item['quantity'] ) ) {
			$item_args['qty'] = $item['quantity'];
		}

		// total
		if ( isset( $item['total'] ) ) {
			$item_args['totals']['total'] = floatval( $item['total'] );
		}

		// total tax
		if ( isset( $item['total_tax'] ) ) {
			$item_args['totals']['tax'] = floatval( $item['total_tax'] );
		}

		// subtotal
		if ( isset( $item['subtotal'] ) ) {
			$item_args['totals']['subtotal'] = floatval( $item['subtotal'] );
		}

		// subtotal tax
		if ( isset( $item['subtotal_tax'] ) ) {
			$item_args['totals']['subtotal_tax'] = floatval( $item['subtotal_tax'] );
		}

		if ( $creating ) {

			$item_id = $order->add_product( $product, $item_args['qty'], $item_args );

			if ( ! $item_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_create_line_item', __( 'Cannot create line item, try again', 'woocommerce' ) );
			}

		} else {

			$item_id = $order->update_product( $item['id'], $product, $item_args );

			if ( ! $item_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_update_line_item', __( 'Cannot update line item, try again', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Given a product ID & variations, find the correct variation ID to use for
	 * calculation. We can't just trust input from the CLI to pass a variation_id
	 * manually, otherwise you could pass the cheapest variation ID but provide
	 * other information so we have to look up the variation ID.
	 *
	 * @since  2.5.0
	 * @param  WC_Product $product Product instance
	 * @return int                 Returns an ID if a valid variation was found for this product
	 */
	protected function get_variation_id( $product, $variations = array() ) {
		$variation_id = null;
		$variations_normalized = array();

		if ( $product->is_type( 'variable' ) && $product->has_child() ) {
			if ( isset( $variations ) && is_array( $variations ) ) {
				// start by normalizing the passed variations
				foreach ( $variations as $key => $value ) {
					$key = str_replace( 'attribute_', '', str_replace( 'pa_', '', $key ) ); // from get_attributes in class-wc-api-products.php
					$variations_normalized[ $key ] = strtolower( $value );
				}
				// now search through each product child and see if our passed variations match anything
				foreach ( $product->get_children() as $variation ) {
					$meta = array();
					foreach ( get_post_meta( $variation ) as $key => $value ) {
						$value = $value[0];
						$key = str_replace( 'attribute_', '', str_replace( 'pa_', '', $key ) );
						$meta[ $key ] = strtolower( $value );
					}
					// if the variation array is a part of the $meta array, we found our match
					if ( $this->array_contains( $variations_normalized, $meta ) ) {
						$variation_id = $variation;
						break;
					}
				}
			}
		}

		return $variation_id;
	}

	/**
	 * Utility function to see if the meta array contains data from variations.
	 *
	 * @since  2.5.0
	 * @return bool  Returns true if meta array contains data from variations
	 */
	protected function array_contains( $needles, $haystack ) {
		foreach ( $needles as $key => $value ) {
			if ( $haystack[ $key ] !== $value ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Create or update an order shipping method
	 *
	 * @since  2.5.0
	 * @param  \WC_Order $order
	 * @param  array $shipping item data
	 * @param  string $action 'create' to add shipping or 'update' to update it
	 * @throws WC_CLI_Exception invalid data, server error
	 */
	protected function set_shipping( $order, $shipping, $action ) {

		// total must be a positive float
		if ( isset( $shipping['total'] ) && floatval( $shipping['total'] ) < 0 ) {
			throw new WC_CLI_Exception( 'woocommerce_invalid_shipping_total', __( 'Shipping total must be a positive amount', 'woocommerce' ) );
		}

		if ( 'create' === $action ) {

			// method ID is required
			if ( ! isset( $shipping['method_id'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_invalid_shipping_item', __( 'Shipping method ID is required', 'woocommerce' ) );
			}

			$rate = new WC_Shipping_Rate( $shipping['method_id'], isset( $shipping['method_title'] ) ? $shipping['method_title'] : '', isset( $shipping['total'] ) ? floatval( $shipping['total'] ) : 0, array(), $shipping['method_id'] );

			$shipping_id = $order->add_shipping( $rate );

			if ( ! $shipping_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_create_shipping', __( 'Cannot create shipping method, try again', 'woocommerce' ) );
			}

		} else {

			$shipping_args = array();

			if ( isset( $shipping['method_id'] ) ) {
				$shipping_args['method_id'] = $shipping['method_id'];
			}

			if ( isset( $shipping['method_title'] ) ) {
				$shipping_args['method_title'] = $shipping['method_title'];
			}

			if ( isset( $shipping['total'] ) ) {
				$shipping_args['cost'] = floatval( $shipping['total'] );
			}

			$shipping_id = $order->update_shipping( $shipping['id'], $shipping_args );

			if ( ! $shipping_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_update_shipping', __( 'Cannot update shipping method, try again', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Create or update an order fee.
	 *
	 * @since  2.5.0
	 * @param  \WC_Order $order
	 * @param  array $fee item data
	 * @param  string $action 'create' to add fee or 'update' to update it
	 * @throws WC_CLI_Exception invalid data, server error
	 */
	protected function set_fee( $order, $fee, $action ) {

		if ( 'create' === $action ) {

			// fee title is required
			if ( ! isset( $fee['title'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_invalid_fee_item', __( 'Fee title is required', 'woocommerce' ) );
			}

			$order_fee            = new stdClass();
			$order_fee->id        = sanitize_title( $fee['title'] );
			$order_fee->name      = $fee['title'];
			$order_fee->amount    = isset( $fee['total'] ) ? floatval( $fee['total'] ) : 0;
			$order_fee->taxable   = false;
			$order_fee->tax       = 0;
			$order_fee->tax_data  = array();
			$order_fee->tax_class = '';

			// if taxable, tax class and total are required
			if ( isset( $fee['taxable'] ) && $fee['taxable'] ) {

				if ( ! isset( $fee['tax_class'] ) ) {
					throw new WC_CLI_Exception( 'woocommerce_invalid_fee_item', __( 'Fee tax class is required when fee is taxable', 'woocommerce' ) );
				}

				$order_fee->taxable   = true;
				$order_fee->tax_class = $fee['tax_class'];

				if ( isset( $fee['total_tax'] ) ) {
					$order_fee->tax = isset( $fee['total_tax'] ) ? wc_format_refund_total( $fee['total_tax'] ) : 0;
				}

				if ( isset( $fee['tax_data'] ) ) {
					$order_fee->tax      = wc_format_refund_total( array_sum( $fee['tax_data'] ) );
					$order_fee->tax_data = array_map( 'wc_format_refund_total', $fee['tax_data'] );
				}
			}

			$fee_id = $order->add_fee( $order_fee );

			if ( ! $fee_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_create_fee', __( 'Cannot create fee, try again', 'woocommerce' ) );
			}

		} else {

			$fee_args = array();

			if ( isset( $fee['title'] ) ) {
				$fee_args['name'] = $fee['title'];
			}

			if ( isset( $fee['tax_class'] ) ) {
				$fee_args['tax_class'] = $fee['tax_class'];
			}

			if ( isset( $fee['total'] ) ) {
				$fee_args['line_total'] = floatval( $fee['total'] );
			}

			if ( isset( $fee['total_tax'] ) ) {
				$fee_args['line_tax'] = floatval( $fee['total_tax'] );
			}

			$fee_id = $order->update_fee( $fee['id'], $fee_args );

			if ( ! $fee_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_update_fee', __( 'Cannot update fee, try again', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Create or update an order coupon
	 *
	 * @since  2.5.0
	 * @param  \WC_Order $order
	 * @param  array $coupon item data
	 * @param  string $action 'create' to add coupon or 'update' to update it
	 * @throws WC_CLI_Exception invalid data, server error
	 */
	protected function set_coupon( $order, $coupon, $action ) {

		// coupon amount must be positive float.
		if ( isset( $coupon['amount'] ) && floatval( $coupon['amount'] ) < 0 ) {
			throw new WC_CLI_Exception( 'woocommerce_invalid_coupon_total', __( 'Coupon discount total must be a positive amount', 'woocommerce' ) );
		}

		if ( 'create' === $action ) {

			// coupon code is required
			if ( empty( $coupon['code'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_invalid_coupon_coupon', __( 'Coupon code is required', 'woocommerce' ) );
			}

			$coupon_id = $order->add_coupon( $coupon['code'], isset( $coupon['amount'] ) ? floatval( $coupon['amount'] ) : 0 );

			if ( ! $coupon_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_create_order_coupon', __( 'Cannot create coupon, try again', 'woocommerce' ) );
			}

		} else {

			$coupon_args = array();

			if ( isset( $coupon['code'] ) ) {
				$coupon_args['code'] = $coupon['code'];
			}

			if ( isset( $coupon['amount'] ) ) {
				$coupon_args['discount_amount'] = floatval( $coupon['amount'] );
			}

			$coupon_id = $order->update_coupon( $coupon['id'], $coupon_args );

			if ( ! $coupon_id ) {
				throw new WC_CLI_Exception( 'woocommerce_cannot_update_order_coupon', __( 'Cannot update coupon, try again', 'woocommerce' ) );
			}
		}
	}
}
