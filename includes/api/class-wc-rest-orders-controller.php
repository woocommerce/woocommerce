<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Orders_Controller extends WC_REST_Posts_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'orders';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';

	/**
	 * Initialize orders actions.
	 */
	public function __construct() {
		add_filter( "woocommerce_rest_{$this->post_type}_query", array( $this, 'query_args' ), 10, 2 );
	}

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
					),
					'reassign' => array(),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch_items' ),
				'permission_callback' => array( $this, 'batch_items_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );
	}

	/**
	 * Prepare a single order output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		global $wpdb;

		$order = wc_get_order( $post );
		$dp    = $request['dp'];

		$data = array(
			'id'                   => $order->id,
			'parent_id'            => $post->post_parent,
			'status'               => $order->get_status(),
			'order_key'            => $order->order_key,
			'number'               => $order->get_order_number(),
			'currency'             => $order->get_order_currency(),
			'version'              => $order->order_version,
			'prices_include_tax'   => $order->prices_include_tax,
			'date_created'         => wc_rest_prepare_date_response( $post->post_date_gmt ),
			'date_modified'        => wc_rest_prepare_date_response( $post->post_modified_gmt ),
			'customer_id'          => $order->get_user_id(),
			'discount_total'       => wc_format_decimal( $order->get_total_discount(), $dp ),
			'discount_tax'         => wc_format_decimal( $order->cart_discount_tax, $dp ),
			'shipping_total'       => wc_format_decimal( $order->get_total_shipping(), $dp ),
			'shipping_tax'         => wc_format_decimal( $order->get_shipping_tax(), $dp ),
			'cart_tax'             => wc_format_decimal( $order->get_cart_tax(), $dp ),
			'total'                => wc_format_decimal( $order->get_total(), $dp ),
			'total_tax'            => wc_format_decimal( $order->get_total_tax(), $dp ),
			'billing'              => array(),
			'shipping'             => array(),
			'payment_method'       => $order->payment_method,
			'payment_method_title' => $order->payment_method_title,
			'transaction_id'       => $order->get_transaction_id(),
			'customer_ip_address'  => $order->customer_ip_address,
			'customer_user_agent'  => $order->customer_user_agent,
			'created_via'          => $order->created_via,
			'customer_note'        => $order->customer_note,
			'date_completed'       => wc_rest_prepare_date_response( $order->completed_date ),
			'date_paid'            => $order->paid_date,
			'cart_hash'            => $order->cart_hash,
			'line_items'           => array(),
			'tax_lines'            => array(),
			'shipping_lines'       => array(),
			'fee_lines'            => array(),
			'coupon_lines'         => array(),
			'refunds'              => array(),
		);

		// Add addresses.
		$data['billing']  = $order->get_address( 'billing' );
		$data['shipping'] = $order->get_address( 'shipping' );

		// Add line items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$product      = $order->get_product_from_item( $item );
			$product_id   = 0;
			$variation_id = 0;
			$product_sku  = null;

			// Check if the product exists.
			if ( is_object( $product ) ) {
				$product_id   = $product->id;
				$variation_id = $product->variation_id;
				$product_sku  = $product->get_sku();
			}

			$meta = new WC_Order_Item_Meta( $item, $product );

			$item_meta = array();

			$hideprefix = 'true' === $request['all_item_meta'] ? null : '_';

			foreach ( $meta->get_formatted( $hideprefix ) as $meta_key => $formatted_meta ) {
				$item_meta[] = array(
					'key'   => $formatted_meta['key'],
					'label' => $formatted_meta['label'],
					'value' => $formatted_meta['value'],
				);
			}

			$line_item = array(
				'id'           => $item_id,
				'name'         => $item['name'],
				'sku'          => $product_sku,
				'product_id'   => (int) $product_id,
				'variation_id' => (int) $variation_id,
				'quantity'     => wc_stock_amount( $item['qty'] ),
				'tax_class'    => ! empty( $item['tax_class'] ) ? $item['tax_class'] : '',
				'price'        => wc_format_decimal( $order->get_item_total( $item, false, false ), $dp ),
				'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item, false, false ), $dp ),
				'subtotal_tax' => wc_format_decimal( $item['line_subtotal_tax'], $dp ),
				'total'        => wc_format_decimal( $order->get_line_total( $item, false, false ), $dp ),
				'total_tax'    => wc_format_decimal( $item['line_tax'], $dp ),
				'taxes'        => array(),
				'meta'         => $item_meta,
			);

			$item_line_taxes = maybe_unserialize( $item['line_tax_data'] );
			if ( isset( $item_line_taxes['total'] ) ) {
				$line_tax = array();

				foreach ( $item_line_taxes['total'] as $tax_rate_id => $tax ) {
					$line_tax[ $tax_rate_id ] = array(
						'id'       => $tax_rate_id,
						'total'    => $tax,
						'subtotal' => '',
					);
				}

				foreach ( $item_line_taxes['subtotal'] as $tax_rate_id => $tax ) {
					$line_tax[ $tax_rate_id ]['subtotal'] = $tax;
				}

				$line_item['taxes'] = array_values( $line_tax );
			}

			$data['line_items'][] = $line_item;
		}

		// Add taxes.
		foreach ( $order->get_items( 'tax' ) as $key => $tax ) {
			$tax_line = array(
				'id'                 => $key,
				'rate_code'          => $tax['name'],
				'rate_id'            => $tax['rate_id'],
				'label'              => isset( $tax['label'] ) ? $tax['label'] : $tax['name'],
				'compound'           => (bool) $tax['compound'],
				'tax_total'          => wc_format_decimal( $tax['tax_amount'], $dp ),
				'shipping_tax_total' => wc_format_decimal( $tax['shipping_tax_amount'], $dp ),
			);

			$data['tax_lines'][] = $tax_line;
		}

		// Add shipping.
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
			$shipping_line = array(
				'id'           => $shipping_item_id,
				'method_title' => $shipping_item['name'],
				'method_id'    => $shipping_item['method_id'],
				'total'        => wc_format_decimal( $shipping_item['cost'], $dp ),
				'total_tax'    => wc_format_decimal( '', $dp ),
				'taxes'        => array(),
			);

			$shipping_taxes = maybe_unserialize( $shipping_item['taxes'] );

			if ( ! empty( $shipping_taxes ) ) {
				$shipping_line['total_tax'] = wc_format_decimal( array_sum( $shipping_taxes ), $dp );

				foreach ( $shipping_taxes as $tax_rate_id => $tax ) {
					$shipping_line['taxes'][] = array(
						'id'       => $tax_rate_id,
						'total'    => $tax,
					);
				}
			}

			$data['shipping_lines'][] = $shipping_line;
		}

		// Add fees.
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
			$fee_line = array(
				'id'         => $fee_item_id,
				'name'       => $fee_item['name'],
				'tax_class'  => ! empty( $fee_item['tax_class'] ) ? $fee_item['tax_class'] : '',
				'tax_status' => 'taxable',
				'total'      => wc_format_decimal( $order->get_line_total( $fee_item ), $dp ),
				'total_tax'  => wc_format_decimal( $order->get_line_tax( $fee_item ), $dp ),
				'taxes'      => array(),
			);

			$fee_line_taxes = maybe_unserialize( $fee_item['line_tax_data'] );
			if ( isset( $fee_line_taxes['total'] ) ) {
				$fee_tax = array();

				foreach ( $fee_line_taxes['total'] as $tax_rate_id => $tax ) {
					$fee_tax[ $tax_rate_id ] = array(
						'id'       => $tax_rate_id,
						'total'    => $tax,
						'subtotal' => '',
					);
				}

				if ( isset( $fee_line_taxes['subtotal'] ) ) {
					foreach ( $fee_line_taxes['subtotal'] as $tax_rate_id => $tax ) {
						$fee_tax[ $tax_rate_id ]['subtotal'] = $tax;
					}
				}

				$fee_line['taxes'] = array_values( $fee_tax );
			}

			$data['fee_lines'][] = $fee_line;
		}

		// Add coupons.
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
			$coupon_line = array(
				'id'           => $coupon_item_id,
				'code'         => $coupon_item['name'],
				'discount'     => wc_format_decimal( $coupon_item['discount_amount'], $dp ),
				'discount_tax' => wc_format_decimal( $coupon_item['discount_amount_tax'], $dp ),
			);

			$data['coupon_lines'][] = $coupon_line;
		}

		// Add refunds.
		foreach ( $order->get_refunds() as $refund ) {
			$data['refunds'][] = array(
				'id'     => $refund->id,
				'refund' => $refund->get_refund_reason() ? $refund->get_refund_reason() : '',
				'total'  => '-' . wc_format_decimal( $refund->get_refund_amount(), $dp ),
			);
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $order ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param WP_Post            $post       Post object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Order $order Order object.
	 * @return array Links for the given order.
	 */
	protected function prepare_links( $order ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $order->id ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		if ( 0 !== (int) $order->get_user_id() ) {
			$links['customer'] = array(
				'href' => rest_url( sprintf( '/%s/customers/%d', $this->namespace, $order->get_user_id() ) ),
			);
		}

		if ( 0 !== (int) $order->post->post_parent ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $order->post->post_parent ) ),
			);
		}

		return $links;
	}

	/**
	 * Query args.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function query_args( $args, $request ) {
		global $wpdb;

		// Set post_status.
		if ( 'any' !== $request['status'] ) {
			$args['post_status'] = 'wc-' . $request['status'];
		} else {
			$args['post_status'] = 'any';
		}

		if ( ! empty( $request['customer'] ) ) {
			if ( ! empty( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}

			$args['meta_query'][] = array(
				'key'   => '_customer_user',
				'value' => $request['customer'],
				'type'  => 'NUMERIC',
			);
		}

		// Search by product.
		if ( ! empty( $request['product'] ) ) {
			$order_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT order_id
				FROM {$wpdb->prefix}woocommerce_order_items
				WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d )
				AND order_item_type = 'line_item'
			 ", $request['product'] ) );

			// Force WP_Query return empty if don't found any order.
			$order_ids = ! empty( $order_ids ) ? $order_ids : array( 0 );

			$args['post__in'] = $order_ids;
		}

		// Search.
		if ( ! empty( $args['s'] ) ) {
			$order_ids = wc_order_search( $args['s'] );

			if ( ! empty( $order_ids ) ) {
				unset( $args['s'] );
				$args['post__in'] =  array_merge( $order_ids, array( 0 ) );
			}
		}

		return $args;
	}

	/**
	 * Prepare a single order for create.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|stdClass $data Object.
	 */
	protected function prepare_item_for_database( $request ) {
		$data = new stdClass;

		// Set default order args.
		$data->status        = $request['status'];
		$data->customer_id   = $request['customer_id'];
		$data->customer_note = $request['customer_note'];

		/**
		 * Filter the query_vars used in `get_items` for the constructed query.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for insertion.
		 *
		 * @param stdClass        $data    An object representing a single item prepared
		 *                                 for inserting the database.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}", $data, $request );
	}

	/**
	 * Create base WC Order object.
	 *
	 * @param array $data
	 * @return WC_Order
	 */
	protected function create_base_order( $data ) {
		return wc_create_order( $data );
	}

	/**
	 * Create order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return int|WP_Error
	 */
	protected function create_order( $request ) {
		wc_transaction_query( 'start' );

		try {
			// Make sure customer exists.
			if ( 0 !== $request['customer_id'] && false === get_user_by( 'id', $request['customer_id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id',__( 'Customer ID is invalid.', 'woocommerce' ), 400 );
			}

			$data = $this->prepare_item_for_database( $request );
			if ( is_wp_error( $data ) ) {
				return $data;
			}

			$data->created_via = 'rest-api';

			$order = $this->create_base_order( (array) $data );

			if ( is_wp_error( $order ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_create_order', sprintf( __( 'Cannot create order: %s.', 'woocommerce' ), implode( ', ', $order->get_error_messages() ) ), 400 );
			}

			// Set addresses.
			if ( is_array( $request['billing'] ) ) {
				$this->update_address( $order, $request['billing'], 'billing' );
			}
			if ( is_array( $request['shipping'] ) ) {
				$this->update_address( $order, $request['shipping'], 'shipping' );
			}

			// Set currency.
			update_post_meta( $order->id, '_order_currency', $request['currency'] );

			// Set lines.
			$lines = array(
				'line_item' => 'line_items',
				'shipping'  => 'shipping_lines',
				'fee'       => 'fee_lines',
				'coupon'    => 'coupon_lines',
			);

			foreach ( $lines as $line_type => $line ) {
				if ( is_array( $request[ $line ] ) ) {
					foreach ( $request[ $line ] as $item ) {
						$set_item = 'set_' . $line_type;
						$this->$set_item( $order, $item, 'create' );
					}
				}
			}

			// Calculate totals and set them.
			$order->calculate_totals();

			// Set payment method.
			if ( ! empty( $request['payment_method'] ) ) {
				update_post_meta( $order->id, '_payment_method', $request['payment_method'] );
			}
			if ( ! empty( $request['payment_method_title'] ) ) {
				update_post_meta( $order->id, '_payment_method_title', $request['payment_method_title'] );
			}
			if ( true === $request['set_paid'] ) {
				$order->payment_complete( $request['transaction_id'] );
			}

			// Set meta data.
			if ( ! empty( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
				$this->update_meta_data( $order->id, $request['meta_data'] );
			}

			wc_transaction_query( 'commit' );

			return $order->id;
		} catch ( WC_REST_Exception $e ) {
			wc_transaction_query( 'rollback' );

			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Update address.
	 *
	 * @param WC_Order $order
	 * @param array $posted
	 * @param string $type
	 */
	protected function update_address( $order, $posted, $type = 'billing' ) {
		$fields = $order->get_address( $type );

		foreach ( array_keys( $fields ) as $field ) {
			if ( isset( $posted[ $field ] ) ) {
				$fields[ $field ] = $posted[ $field ];
			}
		}

		// Set address.
		$order->set_address( $fields, $type );

		// Update user meta.
		if ( $order->get_user_id() ) {
			foreach ( $fields as $key => $value ) {
				update_user_meta( $order->get_user_id(), $type . '_' . $key, $value );
			}
		}
	}

	/**
	 * Create or update a line item.
	 *
	 * @param WC_Order $order Order data.
	 * @param array $item Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function set_line_item( $order, $item, $action = 'create' ) {
		$creating  = 'create' === $action;
		$item_args = array();

		// Product is always required.
		if ( empty( $item['product_id'] ) && empty( $item['sku'] ) && empty( $item['variation_id'] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_required_product_reference', __( 'Product ID or SKU is required.', 'woocommerce' ), 400 );
		}

		if ( ! empty( $item['sku'] ) ) {
			$product_id = (int) wc_get_product_id_by_sku( $item['sku'] );
		} elseif ( ! empty( $item['product_id'] ) && empty( $item['variation_id'] ) ) {
			$product_id = (int) $item['product_id'];
		} elseif ( ! empty( $item['variation_id'] ) ) {
			$product_id = (int) $item['variation_id'];
		}

		// When updating, ensure product ID provided matches.
		if ( 'update' === $action && ! empty( $item['id'] ) ) {
			$item_product_id   = (int) wc_get_order_item_meta( $item['id'], '_product_id' );
			$item_variation_id = (int) wc_get_order_item_meta( $item['id'], '_variation_id' );

			if ( $product_id !== $item_product_id && $product_id !== $item_variation_id ) {
				throw new WC_REST_Exception( 'woocommerce_rest_required_product_reference', __( 'Product ID or variation ID provided does not match this line item.', 'woocommerce' ), 400 );
			}
		}

		$product = wc_get_product( $product_id );

		// Must be a valid WC_Product.
		if ( ! is_object( $product ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_product', __( 'Product is invalid.', 'woocommerce' ), 400 );
		}

		// Quantity must be positive float.
		if ( isset( $item['quantity'] ) && 0 >= floatval( $item['quantity'] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_product_quantity', __( 'Product quantity must be a positive float.', 'woocommerce' ), 400 );
		}

		// Quantity is required when creating.
		if ( $creating && ! isset( $item['quantity'] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_product_quantity', __( 'Product quantity is required.', 'woocommerce' ), 400 );
		}

		// Get variation attributes.
		if ( method_exists( $product, 'get_variation_attributes' ) ) {
			$item_args['variation'] = $product->get_variation_attributes();
		}

		// Quantity.
		if ( isset( $item['quantity'] ) ) {
			$item_args['qty'] = $item['quantity'];
		}

		// Total.
		if ( isset( $item['total'] ) ) {
			$item_args['totals']['total'] = floatval( $item['total'] );
		}

		// Total tax.
		if ( isset( $item['total_tax'] ) ) {
			$item_args['totals']['tax'] = floatval( $item['total_tax'] );
		}

		// Subtotal.
		if ( isset( $item['subtotal'] ) ) {
			$item_args['totals']['subtotal'] = floatval( $item['subtotal'] );
		}

		// Subtotal tax.
		if ( isset( $item['subtotal_tax'] ) ) {
			$item_args['totals']['subtotal_tax'] = floatval( $item['subtotal_tax'] );
		}

		if ( $creating ) {
			$item_id = $order->add_product( $product, $item_args['qty'], $item_args );
			if ( ! $item_id ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_create_line_item', __( 'Cannot create line item, try again.', 'woocommerce' ), 500 );
			}
		} else {
			$item_id = $order->update_product( $item['id'], $product, $item_args );
			if ( ! $item_id ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_update_line_item', __( 'Cannot update line item, try again.', 'woocommerce' ), 500 );
			}
		}
	}

	/**
	 * Create or update an order shipping method.
	 *
	 * @param WC_Order $order Order data.
	 * @param array $shipping Item data.
	 * @param string $action 'create' to add shipping or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function set_shipping( $order, $shipping, $action ) {
		// Total must be a positive float.
		if ( ! empty( $shipping['total'] ) && 0 > floatval( $shipping['total'] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_shipping_total', __( 'Shipping total must be a positive amount.', 'woocommerce' ), 400 );
		}

		if ( 'create' === $action ) {
			// Method ID is required.
			if ( empty( $shipping['method_id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_shipping_item', __( 'Shipping method ID is required.', 'woocommerce' ), 400 );
			}

			$rate = new WC_Shipping_Rate( $shipping['method_id'], isset( $shipping['method_title'] ) ? $shipping['method_title'] : '', isset( $shipping['total'] ) ? floatval( $shipping['total'] ) : 0, array(), $shipping['method_id'] );

			$shipping_id = $order->add_shipping( $rate );

			if ( ! $shipping_id ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_create_shipping', __( 'Cannot create shipping method, try again.', 'woocommerce' ), 500 );
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
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_update_shipping', __( 'Cannot update shipping method, try again.', 'woocommerce' ), 500 );
			}
		}
	}

	/**
	 * Create or update an order fee.
	 *
	 * @param WC_Order $order Order data.
	 * @param array $fee Item data.
	 * @param string $action 'create' to add fee or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function set_fee( $order, $fee, $action ) {
		if ( 'create' === $action ) {

			// Fee name is required.
			if ( empty( $fee['name'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_fee_item', __( 'Fee name is required.', 'woocommerce' ), 400 );
			}

			$fee_data            = new stdClass();
			$fee_data->id        = sanitize_title( $fee['name'] );
			$fee_data->name      = $fee['name'];
			$fee_data->amount    = isset( $fee['total'] ) ? floatval( $fee['total'] ) : 0;
			$fee_data->taxable   = false;
			$fee_data->tax       = 0;
			$fee_data->tax_data  = array();
			$fee_data->tax_class = '';

			// If taxable, tax class and total are required.
			if ( isset( $fee['tax_status'] ) && 'taxable' === $fee['tax_status'] ) {

				if ( ! isset( $fee['tax_class'] ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_fee_item', __( 'Fee tax class is required when fee is taxable.', 'woocommerce' ), 400 );
				}

				$fee_data->taxable   = true;
				$fee_data->tax_class = $fee['tax_class'];

				if ( isset( $fee['total_tax'] ) ) {
					$fee_data->tax = isset( $fee['total_tax'] ) ? wc_format_refund_total( $fee['total_tax'] ) : 0;
				}
			}

			$fee_id = $order->add_fee( $fee_data );

			if ( ! $fee_id ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_create_fee', __( 'Cannot create fee, try again.', 'woocommerce' ), 500 );
			}

		} else {
			$fee_args = array();

			if ( isset( $fee['name'] ) ) {
				$fee_args['name'] = $fee['name'];
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
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_update_fee', __( 'Cannot update fee, try again.', 'woocommerce' ), 500 );
			}
		}
	}

	/**
	 * Create or update an order coupon.
	 *
	 * @param WC_Order $order Order data.
	 * @param array $coupon Item data.
	 * @param string $action 'create' to add coupon or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function set_coupon( $order, $coupon, $action ) {
		// Coupon discount must be positive float.
		if ( isset( $coupon['discount'] ) && 0 > floatval( $coupon['discount'] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon_total', __( 'Coupon discount must be a positive amount.', 'woocommerce' ), 400 );
		}

		if ( 'create' === $action ) {
			// Coupon code is required.
			if ( empty( $coupon['code'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon_coupon', __( 'Coupon code is required.', 'woocommerce' ), 400 );
			}

			$coupon_id = $order->add_coupon( $coupon['code'], floatval( $coupon['discount'] ) );

			if ( ! $coupon_id ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_create_order_coupon', __( 'Cannot create coupon, try again.', 'woocommerce' ), 500 );
			}

		} else {
			$coupon_args = array();

			if ( isset( $coupon['code'] ) ) {
				$coupon_args['code'] = $coupon['code'];
			}

			if ( isset( $coupon['discount'] ) ) {
				$coupon_args['discount_amount'] = floatval( $coupon['discount'] );
			}

			$coupon_id = $order->update_coupon( $coupon['id'], $coupon_args );

			if ( ! $coupon_id ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_update_order_coupon', __( 'Cannot update coupon, try again.', 'woocommerce' ), 500 );
			}
		}
	}

	/**
	 * Helper method to add/update meta data, with two restrictions:
	 *
	 * 1) Only non-protected meta (no leading underscore) can be set
	 * 2) Meta values must be scalar (int, string, bool)
	 *
	 * @param int $order_id Order ID.
	 * @param array $meta_data Meta data in array( 'meta_key' => 'meta_value' ) format.
	 */
	protected function update_meta_data( $order_id, $meta_data ) {
		foreach ( $meta_data as $meta_key => $meta_value ) {
			if ( is_string( $meta_key ) && ! is_protected_meta( $meta_key ) && is_scalar( $meta_value ) ) {
				update_post_meta( $order_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'woocommerce' ), $this->post_type ), array( 'status' => 400 ) );
		}

		$order_id = $this->create_order( $request );
		if ( is_wp_error( $order_id ) ) {
			return $order_id;
		}

		// Clear transients.
		wc_delete_shop_order_transients( $order_id );

		$post = get_post( $order_id );
		$this->update_additional_fields_for_object( $post, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param object          $post      Inserted object (not a WP_Post object).
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->post_type}", $post, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $post->ID ) ) );

		return $response;
	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @param WC_Order $order order
	 * @param string $item_type
	 * @param array $item item provided in the request body
	 * @param string $action either 'create' or 'update'
	 * @throws WC_REST_Exception If item ID is not associated with order
	 */
	protected function set_item( $order, $item_type, $item, $action ) {
		global $wpdb;

		$set_method = 'set_' . $item_type;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$result = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d AND order_id = %d",
				absint( $item['id'] ),
				absint( $order->id )
			) );

			if ( is_null( $result ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_item_id', __( 'Order item ID provided is not associated with order.', 'woocommerce' ), 400 );
			}
		}

		$this->$set_method( $order, $item, $action );
	}

	/**
	 * Helper method to check if the resource ID associated with the provided item is null.
	 * Items can be deleted by setting the resource ID to null.
	 *
	 * @param array $item Item provided in the request body.
	 * @return bool True if the item resource ID is null, false otherwise.
	 */
	protected function item_is_null( $item ) {
		$keys = array( 'product_id', 'method_id', 'title', 'code' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $item ) && is_null( $item[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @param WP_Post $post Post data.
	 * @return int|WP_Error
	 */
	protected function update_order( $request, $post ) {
		try {
			$update_totals = false;
			$order         = wc_get_order( $post );
			$order_args    = array( 'order_id' => $order->id );

			// Customer note.
			if ( isset( $request['customer_note'] ) ) {
				$order_args['customer_note'] = $request['customer_note'];
			}

			// Customer ID.
			if ( isset( $request['customer_id'] ) && $request['customer_id'] != $order->get_user_id() ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'woocommerce' ), 400 );
				}

				update_post_meta( $order->id, '_customer_user', $request['customer_id'] );
			}

			// Update addresses.
			if ( is_array( $request['billing'] ) ) {
				$this->update_address( $order, $request['billing'], 'billing' );
			}
			if ( is_array( $request['shipping'] ) ) {
				$this->update_address( $order, $request['shipping'], 'shipping' );
			}

			$lines = array(
				'line_item' => 'line_items',
				'shipping'  => 'shipping_lines',
				'fee'       => 'fee_lines',
				'coupon'    => 'coupon_lines',
			);

			foreach ( $lines as $line_type => $line ) {
				if ( isset( $request[ $line ] ) && is_array( $request[ $line ] ) ) {
					$update_totals = true;
					foreach ( $request[ $line ] as $item ) {
						// Item ID is always required.
						if ( ! array_key_exists( 'id', $item ) ) {
							$item['id'] = null;
						}

						// Create item.
						if ( is_null( $item['id'] ) ) {
							$this->set_item( $order, $line_type, $item, 'create' );
						} elseif ( $this->item_is_null( $item ) ) {
							// Delete item.
							wc_delete_order_item( $item['id'] );
						} else {
							// Update item.
							$this->set_item( $order, $line_type, $item, 'update' );
						}
					}
				}
			}

			// Set payment method.
			if ( ! empty( $request['payment_method'] ) ) {
				update_post_meta( $order->id, '_payment_method', $request['payment_method'] );
			}
			if ( ! empty( $request['payment_method_title'] ) ) {
				update_post_meta( $order->id, '_payment_method_title', $request['payment_method'] );
			}
			if ( $order->needs_payment() && isset( $request['set_paid'] ) && true === $request['set_paid'] ) {
				$order->payment_complete( ! empty( $request['transaction_id'] ) ? $request['transaction_id'] : '' );
			}

			// Set order currency.
			if ( isset( $request['currency'] ) ) {
				update_post_meta( $order->id, '_order_currency', $request['currency'] );
			}

			// If items have changed, recalculate order totals.
			if ( $update_totals ) {
				$order->calculate_totals();
			}

			// Update meta data.
			if ( ! empty( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
				$this->update_meta_data( $order->id, $request['meta_data'] );
			}

			// Update the order post to set customer note/modified date.
			wc_update_order( $order_args );

			// Order status.
			if ( ! empty( $request['status'] ) ) {
				$order->update_status( $request['status'], isset( $request['status_note'] ) ? $request['status_note'] : '' );
			}

			return $order->id;
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Update a single order.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'ID is invalid.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$order_id = $this->update_order( $request, $post );
		if ( is_wp_error( $order_id ) ) {
			return $order_id;
		}

		// Clear transients.
		wc_delete_shop_order_transients( $order_id );

		$post = get_post( $order_id );
		$this->update_additional_fields_for_object( $post, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param object          $post      Inserted object (not a WP_Post object).
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->post_type}", $post, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Get order statuses.
	 *
	 * @return array
	 */
	protected function get_order_statuses() {
		$order_statuses = array();

		foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
			$order_statuses[] = str_replace( 'wc-', '', $status );
		}

		return $order_statuses;
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id' => array(
					'description' => __( 'Parent order ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Order status.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'pending',
					'enum'        => $this->get_order_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'order_key' => array(
					'description' => __( 'Order key.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'number' => array(
					'description' => __( 'Order number.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'currency' => array(
					'description' => __( 'Currency the order was created with, in ISO format.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => get_woocommerce_currency(),
					'enum'        => array_keys( get_woocommerce_currencies() ),
					'context'     => array( 'view', 'edit' ),
				),
				'version' => array(
					'description' => __( 'Version of WooCommerce when the order was made.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'prices_include_tax' => array(
					'description' => __( 'Shows if the prices included tax during checkout.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the order was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the order was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_id' => array(
					'description' => __( 'User ID who owns the order. 0 for guests.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'discount_total' => array(
					'description' => __( 'Total discount amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_tax' => array(
					'description' => __( 'Total discount tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_total' => array(
					'description' => __( 'Total shipping amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_tax' => array(
					'description' => __( 'Total shipping tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cart_tax' => array(
					'description' => __( 'Sum of line item taxes only.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total' => array(
					'description' => __( 'Grand total.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_tax' => array(
					'description' => __( 'Sum of all taxes.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing' => array(
					'description' => __( 'Billing address.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address line 1.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address line 2.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email' => array(
							'description' => __( 'Email address.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone' => array(
							'description' => __( 'Phone number.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping' => array(
					'description' => __( 'Shipping address.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address line 1.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address line 2.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'payment_method' => array(
					'description' => __( 'Payment method ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'payment_method_title' => array(
					'description' => __( 'Payment method title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'set_paid' => array(
					'description' => __( 'Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'edit' ),
				),
				'transaction_id' => array(
					'description' => __( 'Unique transaction ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'customer_ip_address' => array(
					'description' => __( "Customer's IP address.", 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_user_agent' => array(
					'description' => __( 'User agent of the customer.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created_via' => array(
					'description' => __( 'Shows where the order was created.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_note' => array(
					'description' => __( 'Note left by customer during checkout.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_completed' => array(
					'description' => __( "The date the order was completed, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_paid' => array(
					'description' => __( "The date the order has been paid, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cart_hash' => array(
					'description' => __( 'MD5 hash of cart items to ensure orders are not modified.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'line_items' => array(
					'description' => __( 'Line items data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name' => array(
							'description' => __( 'Product name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'sku' => array(
							'description' => __( 'Product SKU.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'product_id' => array(
							'description' => __( 'Product ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'variation_id' => array(
							'description' => __( 'Variation ID, if applicable.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'quantity' => array(
							'description' => __( 'Quantity ordered.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_class' => array(
							'description' => __( 'Tax class of product.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price' => array(
							'description' => __( 'Product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'subtotal' => array(
							'description' => __( 'Line subtotal (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'subtotal_tax' => array(
							'description' => __( 'Line subtotal tax (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total' => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax' => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'taxes' => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'id' => array(
									'description' => __( 'Tax rate ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'total' => array(
									'description' => __( 'Tax total.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'subtotal' => array(
									'description' => __( 'Tax subtotal.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
						'meta' => array(
							'description' => __( 'Line item meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'key' => array(
									'description' => __( 'Meta key.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'label' => array(
									'description' => __( 'Meta label.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'value' => array(
									'description' => __( 'Meta value.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
				'tax_lines' => array(
					'description' => __( 'Tax lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'rate_code' => array(
							'description' => __( 'Tax rate code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'rate_id' => array(
							'description' => __( 'Tax rate ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'label' => array(
							'description' => __( 'Tax rate label.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'compound' => array(
							'description' => __( 'Show if is a compound tax rate.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'tax_total' => array(
							'description' => __( 'Tax total (not including shipping taxes).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'shipping_tax_total' => array(
							'description' => __( 'Shipping tax total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'shipping_lines' => array(
					'description' => __( 'Shipping lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'method_title' => array(
							'description' => __( 'Shipping method name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'method_id' => array(
							'description' => __( 'Shipping method ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total' => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax' => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'taxes' => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'id' => array(
									'description' => __( 'Tax rate ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'total' => array(
									'description' => __( 'Tax total.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
				'fee_lines' => array(
					'description' => __( 'Fee lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name' => array(
							'description' => __( 'Fee name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_class' => array(
							'description' => __( 'Tax class of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_status' => array(
							'description' => __( 'Tax status of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total' => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax' => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'taxes' => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'id' => array(
									'description' => __( 'Tax rate ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'total' => array(
									'description' => __( 'Tax total.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'subtotal' => array(
									'description' => __( 'Tax subtotal.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
				'coupon_lines' => array(
					'description' => __( 'Coupons line data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'code' => array(
							'description' => __( 'Coupon code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'discount' => array(
							'description' => __( 'Discount total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'discount_tax' => array(
							'description' => __( 'Discount total tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'refunds' => array(
					'description' => __( 'List of refunds.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id' => array(
							'description' => __( 'Refund ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'reason' => array(
							'description' => __( 'Refund reason.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'total' => array(
							'description' => __( 'Refund total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any' ), $this->get_order_statuses() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['customer'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific customer.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific product.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['dp'] = array(
			'default'           => 2,
			'description'       => __( 'Number of decimal points to use in each resource.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
