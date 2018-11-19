<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @package  WooCommerce/API
 * @since    2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_CRUD_Controller
 */
class WC_REST_Orders_V2_Controller extends WC_REST_Legacy_Orders_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

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
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Stores the request.
	 *
	 * @var array
	 */
	protected $request = array();

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/' . $this->rest_base, array(
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
			)
		);

		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
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
							'type'        => 'boolean',
							'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/batch', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
	}

	/**
	 * Get object. Return false if object is not of required type.
	 *
	 * @since  3.0.0
	 * @param  int $id Object ID.
	 * @return WC_Data|bool
	 */
	protected function get_object( $id ) {
		$order = wc_get_order( $id );
		// In case id is a refund's id (or it's not an order at all), don't expose it via /orders/ path.
		if ( ! $order || 'shop_order_refund' === $order->get_type() ) {
			return false;
		}

		return $order;
	}

	/**
	 * Expands an order item to get its data.
	 *
	 * @param WC_Order_item $item Order item data.
	 * @return array
	 */
	protected function get_order_item_data( $item ) {
		$data           = $item->get_data();
		$format_decimal = array( 'subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
			}
		}

		// Add SKU and PRICE to products.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$data['sku']   = $item->get_product() ? $item->get_product()->get_sku() : null;
			$data['price'] = $item->get_quantity() ? $item->get_total() / $item->get_quantity() : 0;
		}

		// Format taxes.
		if ( ! empty( $data['taxes']['total'] ) ) {
			$taxes = array();

			foreach ( $data['taxes']['total'] as $tax_rate_id => $tax ) {
				$taxes[] = array(
					'id'       => $tax_rate_id,
					'total'    => $tax,
					'subtotal' => isset( $data['taxes']['subtotal'][ $tax_rate_id ] ) ? $data['taxes']['subtotal'][ $tax_rate_id ] : '',
				);
			}
			$data['taxes'] = $taxes;
		} elseif ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array();
		}

		// Remove names for coupons, taxes and shipping.
		if ( isset( $data['code'] ) || isset( $data['rate_code'] ) || isset( $data['method_title'] ) ) {
			unset( $data['name'] );
		}

		// Remove props we don't want to expose.
		unset( $data['order_id'] );
		unset( $data['type'] );

		return $data;
	}

	/**
	 * Get formatted item data.
	 *
	 * @since  3.0.0
	 * @param  WC_Data $object WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {
		$data              = $object->get_data();
		$format_decimal    = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date       = array( 'date_created', 'date_modified', 'date_completed', 'date_paid' );
		$format_line_items = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$datetime              = $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		// Format the order status.
		$data['status'] = 'wc-' === substr( $data['status'], 0, 3 ) ? substr( $data['status'], 3 ) : $data['status'];

		// Format line items.
		foreach ( $format_line_items as $key ) {
			$data[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $data[ $key ] ) );
		}

		// Refunds.
		$data['refunds'] = array();
		foreach ( $object->get_refunds() as $refund ) {
			$data['refunds'][] = array(
				'id'     => $refund->get_id(),
				'reason' => $refund->get_reason() ? $refund->get_reason() : '',
				'total'  => '-' . wc_format_decimal( $refund->get_amount(), $this->request['dp'] ),
			);
		}

		return array(
			'id'                   => $object->get_id(),
			'parent_id'            => $data['parent_id'],
			'number'               => $data['number'],
			'order_key'            => $data['order_key'],
			'created_via'          => $data['created_via'],
			'version'              => $data['version'],
			'status'               => $data['status'],
			'currency'             => $data['currency'],
			'date_created'         => $data['date_created'],
			'date_created_gmt'     => $data['date_created_gmt'],
			'date_modified'        => $data['date_modified'],
			'date_modified_gmt'    => $data['date_modified_gmt'],
			'discount_total'       => $data['discount_total'],
			'discount_tax'         => $data['discount_tax'],
			'shipping_total'       => $data['shipping_total'],
			'shipping_tax'         => $data['shipping_tax'],
			'cart_tax'             => $data['cart_tax'],
			'total'                => $data['total'],
			'total_tax'            => $data['total_tax'],
			'prices_include_tax'   => $data['prices_include_tax'],
			'customer_id'          => $data['customer_id'],
			'customer_ip_address'  => $data['customer_ip_address'],
			'customer_user_agent'  => $data['customer_user_agent'],
			'customer_note'        => $data['customer_note'],
			'billing'              => $data['billing'],
			'shipping'             => $data['shipping'],
			'payment_method'       => $data['payment_method'],
			'payment_method_title' => $data['payment_method_title'],
			'transaction_id'       => $data['transaction_id'],
			'date_paid'            => $data['date_paid'],
			'date_paid_gmt'        => $data['date_paid_gmt'],
			'date_completed'       => $data['date_completed'],
			'date_completed_gmt'   => $data['date_completed_gmt'],
			'cart_hash'            => $data['cart_hash'],
			'meta_data'            => $data['meta_data'],
			'line_items'           => $data['line_items'],
			'tax_lines'            => $data['tax_lines'],
			'shipping_lines'       => $data['shipping_lines'],
			'fee_lines'            => $data['fee_lines'],
			'coupon_lines'         => $data['coupon_lines'],
			'refunds'              => $data['refunds'],
		);
	}

	/**
	 * Prepare a single order output for response.
	 *
	 * @since  3.0.0
	 * @param  WC_Data         $object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$this->request       = $request;
		$this->request['dp'] = is_null( $this->request['dp'] ) ? wc_get_price_decimals() : absint( $this->request['dp'] );
		$data                = $this->get_formatted_item_data( $object );
		$context             = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data                = $this->add_additional_fields_to_object( $data, $request );
		$data                = $this->filter_response_by_context( $data, $context );
		$response            = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		if ( 0 !== (int) $object->get_customer_id() ) {
			$links['customer'] = array(
				'href' => rest_url( sprintf( '/%s/customers/%d', $this->namespace, $object->get_customer_id() ) ),
			);
		}

		if ( 0 !== (int) $object->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $object->get_parent_id() ) ),
			);
		}

		return $links;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		global $wpdb;

		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		if ( in_array( $request['status'], $this->get_order_statuses(), true ) ) {
			$args['post_status'] = 'wc-' . $request['status'];
		} elseif ( 'any' === $request['status'] ) {
			$args['post_status'] = 'any';
		} else {
			$args['post_status'] = $request['status'];
		}

		if ( isset( $request['customer'] ) ) {
			if ( ! empty( $args['meta_query'] ) ) {
				$args['meta_query'] = array(); // WPCS: slow query ok.
			}

			$args['meta_query'][] = array(
				'key'   => '_customer_user',
				'value' => $request['customer'],
				'type'  => 'NUMERIC',
			);
		}

		// Search by product.
		if ( ! empty( $request['product'] ) ) {
			$order_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT order_id
				FROM {$wpdb->prefix}woocommerce_order_items
				WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d )
				AND order_item_type = 'line_item'
			 ", $request['product']
				)
			);

			// Force WP_Query return empty if don't found any order.
			$order_ids = ! empty( $order_ids ) ? $order_ids : array( 0 );

			$args['post__in'] = $order_ids;
		}

		// Search.
		if ( ! empty( $args['s'] ) ) {
			$order_ids = wc_order_search( $args['s'] );

			if ( ! empty( $order_ids ) ) {
				unset( $args['s'] );
				$args['post__in'] = array_merge( $order_ids, array( 0 ) );
			}
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for an order collection request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( 'woocommerce_rest_orders_prepare_object_query', $args, $request );

		return $args;
	}

	/**
	 * Only return writable props from schema.
	 *
	 * @param  array $schema Schema.
	 * @return bool
	 */
	protected function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
	}

	/**
	 * Prepare a single order for create or update.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order     = new WC_Order( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'status':
						// Status change should be done later so transitions have new data.
						break;
					case 'billing':
					case 'shipping':
						$this->update_address( $order, $value, $key );
						break;
					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
					case 'coupon_lines':
						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								if ( is_array( $item ) ) {
									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$order->remove_item( $item['id'] );
									} else {
										$this->set_item( $order, $key, $item );
									}
								}
							}
						}
						break;
					case 'meta_data':
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
							}
						}
						break;
					default:
						if ( is_callable( array( $order, "set_{$key}" ) ) ) {
							$order->{"set_{$key}"}( $value );
						}
						break;
				}
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $order    Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $order, $request, $creating );
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			// Make sure gateways are loaded so hooks from gateways fire on save/create.
			WC()->payment_gateways();

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'woocommerce' ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id_network', __( 'Customer ID does not belong to this site.', 'woocommerce' ), 400 );
				}
			}

			if ( $creating ) {
				$object->set_created_via( 'rest-api' );
				$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				$object->calculate_totals();
			} else {
				// If items have changed, recalculate order totals.
				if ( isset( $request['billing'] ) || isset( $request['shipping'] ) || isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) || isset( $request['coupon_lines'] ) ) {
					$object->calculate_totals( true );
				}
			}

			// Set status.
			if ( ! empty( $request['status'] ) ) {
				$object->set_status( $request['status'] );
			}

			$object->save();

			// Actions for after the order is saved.
			if ( true === $request['set_paid'] ) {
				if ( $creating || $object->needs_payment() ) {
					$object->payment_complete( $request['transaction_id'] );
				}
			}

			return $this->get_object( $object->get_id() );
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Update address.
	 *
	 * @param WC_Order $order  Order data.
	 * @param array    $posted Posted data.
	 * @param string   $type   Address type.
	 */
	protected function update_address( $order, $posted, $type = 'billing' ) {
		foreach ( $posted as $key => $value ) {
			if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
				$order->{"set_{$type}_{$key}"}( $value );
			}
		}
	}

	/**
	 * Gets the product ID from the SKU or posted ID.
	 *
	 * @throws WC_REST_Exception When SKU or ID is not valid.
	 * @param array $posted Request data.
	 * @return int
	 */
	protected function get_product_id( $posted ) {
		if ( ! empty( $posted['sku'] ) ) {
			$product_id = (int) wc_get_product_id_by_sku( $posted['sku'] );
		} elseif ( ! empty( $posted['product_id'] ) && empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['product_id'];
		} elseif ( ! empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['variation_id'];
		} else {
			throw new WC_REST_Exception( 'woocommerce_rest_required_product_reference', __( 'Product ID or SKU is required.', 'woocommerce' ), 400 );
		}
		return $product_id;
	}

	/**
	 * Maybe set an item prop if the value was posted.
	 *
	 * @param WC_Order_Item $item   Order item.
	 * @param string        $prop   Order property.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_prop( $item, $prop, $posted ) {
		if ( isset( $posted[ $prop ] ) ) {
			$item->{"set_$prop"}( $posted[ $prop ] );
		}
	}

	/**
	 * Maybe set item props if the values were posted.
	 *
	 * @param WC_Order_Item $item   Order item data.
	 * @param string[]      $props  Properties.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_props( $item, $props, $posted ) {
		foreach ( $props as $prop ) {
			$this->maybe_set_item_prop( $item, $prop, $posted );
		}
	}

	/**
	 * Maybe set item meta if posted.
	 *
	 * @param WC_Order_Item $item   Order item data.
	 * @param array         $posted Request data.
	 */
	protected function maybe_set_item_meta_data( $item, $posted ) {
		if ( ! empty( $posted['meta_data'] ) && is_array( $posted['meta_data'] ) ) {
			foreach ( $posted['meta_data'] as $meta ) {
				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$item->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}
	}

	/**
	 * Create or update a line item.
	 *
	 * @param array  $posted Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Product
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_items( $posted, $action = 'create', $item = null ) {
		$item    = is_null( $item ) ? new WC_Order_Item_Product( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
		$product = wc_get_product( $this->get_product_id( $posted ) );

		if ( $product !== $item->get_product() ) {
			$item->set_product( $product );

			if ( 'create' === $action ) {
				$quantity = isset( $posted['quantity'] ) ? $posted['quantity'] : 1;
				$total    = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
				$item->set_total( $total );
				$item->set_subtotal( $total );
			}
		}

		$this->maybe_set_item_props( $item, array( 'name', 'quantity', 'total', 'subtotal', 'tax_class' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order shipping method.
	 *
	 * @param array  $posted $shipping Item data.
	 * @param string $action 'create' to add shipping or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Shipping
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_lines( $posted, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Shipping( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['method_id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_shipping_item', __( 'Shipping method ID is required.', 'woocommerce' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'method_id', 'method_title', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order fee.
	 *
	 * @param array  $posted Item data.
	 * @param string $action 'create' to add fee or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Fee
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_fee_lines( $posted, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Fee( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['name'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_fee_item', __( 'Fee name is required.', 'woocommerce' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'name', 'tax_class', 'tax_status', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order coupon.
	 *
	 * @param array  $posted Item data.
	 * @param string $action 'create' to add coupon or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Coupon
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_coupon_lines( $posted, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Coupon( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['code'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon_coupon', __( 'Coupon code is required.', 'woocommerce' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'code', 'discount' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @param WC_Order $order order object.
	 * @param string   $item_type The item type.
	 * @param array    $posted item provided in the request body.
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 */
	protected function set_item( $order, $item_type, $posted ) {
		global $wpdb;

		if ( ! empty( $posted['id'] ) ) {
			$action = 'update';
		} else {
			$action = 'create';
		}

		$method = 'prepare_' . $item_type;
		$item   = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $order->get_item( absint( $posted['id'] ), false );

			if ( ! $item ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_item_id', __( 'Order item ID provided is not associated with order.', 'woocommerce' ), 400 );
			}
		}

		// Prepare item data.
		$item = $this->$method( $posted, $action, $item );

		do_action( 'woocommerce_rest_set_order_item', $item, $posted );

		// If creating the order, add the item to it.
		if ( 'create' === $action ) {
			$order->add_item( $item );
		} else {
			$item->save();
		}
	}

	/**
	 * Helper method to check if the resource ID associated with the provided item is null.
	 * Items can be deleted by setting the resource ID to null.
	 *
	 * @param array $item Item provided in the request body.
	 * @return bool True if the item resource ID is null, false otherwise.
	 */
	protected function item_is_null( $item ) {
		$keys = array( 'product_id', 'method_id', 'method_title', 'name', 'code' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $item ) && is_null( $item[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get order statuses without prefixes.
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
				'id'                   => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id'            => array(
					'description' => __( 'Parent order ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'number'               => array(
					'description' => __( 'Order number.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'order_key'            => array(
					'description' => __( 'Order key.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created_via'          => array(
					'description' => __( 'Shows where the order was created.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'version'              => array(
					'description' => __( 'Version of WooCommerce which last updated the order.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'               => array(
					'description' => __( 'Order status.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'pending',
					'enum'        => $this->get_order_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'currency'             => array(
					'description' => __( 'Currency the order was created with, in ISO format.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => get_woocommerce_currency(),
					'enum'        => array_keys( get_woocommerce_currencies() ),
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'         => array(
					'description' => __( "The date the order was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'     => array(
					'description' => __( 'The date the order was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'        => array(
					'description' => __( "The date the order was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'    => array(
					'description' => __( 'The date the order was last modified, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_total'       => array(
					'description' => __( 'Total discount amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_tax'         => array(
					'description' => __( 'Total discount tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_total'       => array(
					'description' => __( 'Total shipping amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_tax'         => array(
					'description' => __( 'Total shipping tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cart_tax'             => array(
					'description' => __( 'Sum of line item taxes only.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total'                => array(
					'description' => __( 'Grand total.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_tax'            => array(
					'description' => __( 'Sum of all taxes.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'prices_include_tax'   => array(
					'description' => __( 'True the prices included tax during checkout.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_id'          => array(
					'description' => __( 'User ID who owns the order. 0 for guests.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'customer_ip_address'  => array(
					'description' => __( "Customer's IP address.", 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_user_agent'  => array(
					'description' => __( 'User agent of the customer.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_note'        => array(
					'description' => __( 'Note left by customer during checkout.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'billing'              => array(
					'description' => __( 'Billing address.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => __( 'Address line 1', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => __( 'Address line 2', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email'      => array(
							'description' => __( 'Email address.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone'      => array(
							'description' => __( 'Phone number.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping'             => array(
					'description' => __( 'Shipping address.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name'  => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company'    => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1'  => array(
							'description' => __( 'Address line 1', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2'  => array(
							'description' => __( 'Address line 2', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city'       => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state'      => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode'   => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country'    => array(
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'payment_method'       => array(
					'description' => __( 'Payment method ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'payment_method_title' => array(
					'description' => __( 'Payment method title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'transaction_id'       => array(
					'description' => __( 'Unique transaction ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_paid'            => array(
					'description' => __( "The date the order was paid, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_paid_gmt'        => array(
					'description' => __( 'The date the order was paid, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_completed'       => array(
					'description' => __( "The date the order was completed, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_completed_gmt'   => array(
					'description' => __( 'The date the order was completed, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cart_hash'            => array(
					'description' => __( 'MD5 hash of cart items to ensure orders are not modified.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'meta_data'            => array(
					'description' => __( 'Meta data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'line_items'           => array(
					'description' => __( 'Line items data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'name'         => array(
								'description' => __( 'Product name.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'product_id'   => array(
								'description' => __( 'Product ID.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'variation_id' => array(
								'description' => __( 'Variation ID, if applicable.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'quantity'     => array(
								'description' => __( 'Quantity ordered.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'tax_class'    => array(
								'description' => __( 'Tax class of product.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'subtotal'     => array(
								'description' => __( 'Line subtotal (before discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'subtotal_tax' => array(
								'description' => __( 'Line subtotal tax (before discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'total'        => array(
								'description' => __( 'Line total (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total_tax'    => array(
								'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'taxes'        => array(
								'description' => __( 'Line taxes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'       => array(
											'description' => __( 'Tax rate ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
										),
										'total'    => array(
											'description' => __( 'Tax total.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'subtotal' => array(
											'description' => __( 'Tax subtotal.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
							'meta_data'    => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key'   => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'mixed',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
							'sku'          => array(
								'description' => __( 'Product SKU.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'price'        => array(
								'description' => __( 'Product price.', 'woocommerce' ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'tax_lines'            => array(
					'description' => __( 'Tax lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                 => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'rate_code'          => array(
								'description' => __( 'Tax rate code.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'rate_id'            => array(
								'description' => __( 'Tax rate ID.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'label'              => array(
								'description' => __( 'Tax rate label.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'compound'           => array(
								'description' => __( 'Show if is a compound tax rate.', 'woocommerce' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'tax_total'          => array(
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
							'meta_data'          => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key'   => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'mixed',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'shipping_lines'       => array(
					'description' => __( 'Shipping lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'method_title' => array(
								'description' => __( 'Shipping method name.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'method_id'    => array(
								'description' => __( 'Shipping method ID.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'instance_id'  => array(
								'description' => __( 'Shipping instance ID.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total'        => array(
								'description' => __( 'Line total (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total_tax'    => array(
								'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'taxes'        => array(
								'description' => __( 'Line taxes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
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
							'meta_data'    => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key'   => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'mixed',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'fee_lines'            => array(
					'description' => __( 'Fee lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'         => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'name'       => array(
								'description' => __( 'Fee name.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'tax_class'  => array(
								'description' => __( 'Tax class of fee.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'tax_status' => array(
								'description' => __( 'Tax status of fee.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'enum'        => array( 'taxable', 'none' ),
							),
							'total'      => array(
								'description' => __( 'Line total (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total_tax'  => array(
								'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'taxes'      => array(
								'description' => __( 'Line taxes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'       => array(
											'description' => __( 'Tax rate ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'total'    => array(
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
							'meta_data'  => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key'   => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'mixed',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'coupon_lines'         => array(
					'description' => __( 'Coupons line data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'code'         => array(
								'description' => __( 'Coupon code.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'discount'     => array(
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
							'meta_data'    => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key'   => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'mixed',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'refunds'              => array(
					'description' => __( 'List of refunds.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
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
							'total'  => array(
								'description' => __( 'Refund total.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'set_paid'             => array(
					'description' => __( 'Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'edit' ),
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

		$params['status']   = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'trash' ), $this->get_order_statuses() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['customer'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific customer.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product']  = array(
			'description'       => __( 'Limit result set to orders assigned a specific product.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['dp']       = array(
			'default'           => wc_get_price_decimals(),
			'description'       => __( 'Number of decimal points to use in each resource.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
