<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Orders controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\StoreApi\Utilities\Pagination;
use Automattic\WooCommerce\RestApi\V4\AbstractController;
use Automattic\WooCommerce\Internal\Utilities\Users;

/**
 * Orders Controller.
 */
class Controller extends AbstractController {
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
	 * Constructor.
	 */
	public function __construct() {
		$this->schema = new OrderSchema();
	}

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
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
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = array_merge(
			QueryUtils::get_query_schema(),
			array(
				'context'                 => $this->get_context_param(),
				'dp'                      => array(
					'default'           => wc_get_price_decimals(),
					'description'       => __( 'Number of decimal points to use in each resource.', 'woocommerce' ),
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'exclude_meta'            => array(
					'default'           => array(),
					'description'       => __( 'Ensure meta_data excludes specific keys.', 'woocommerce' ),
					'type'              => 'array',
					'items'             => array(
						'type' => 'string',
					),
					'sanitize_callback' => 'wp_parse_list',
				),
				'include_meta'            => array(
					'default'           => array(),
					'description'       => __( 'Limit meta_data to specific keys.', 'woocommerce' ),
					'type'              => 'array',
					'items'             => array(
						'type' => 'string',
					),
					'sanitize_callback' => 'wp_parse_list',
				),
				'order_item_display_meta' => array(
					'default'           => false,
					'description'       => __( 'Only show meta which is meant to be displayed for an order.', 'woocommerce' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
				),
			)
		);

		$params['context']['default'] = 'view';

		/**
		 * Filter the collection params for the orders controller.
		 *
		 * @param array $params The collection params.
		 */
		return apply_filters( $this->get_hook_prefix() . 'collection_params', $params, $this );
	}

	/**
	 * Add links to the response.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WC_Order         $order   Order object.
	 * @return \WP_REST_Response
	 */
	protected function get_item_links( \WC_Order $order ) {
		$links = array(
			'self'            => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $order->get_id() ) ),
			),
			'collection'      => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
			'email_templates' => array(
				'href'       => rest_url( sprintf( '/%s/%s/%d/actions/email_templates', $this->namespace, $this->rest_base, $order->get_id() ) ),
				'embeddable' => true,
			),
		);

		if ( $order->get_customer_id() ) {
			$links['customer'] = array(
				'href' => rest_url( sprintf( '/%s/customers/%d', $this->namespace, $order->get_customer_id() ) ),
			);
		}

		if ( $order->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $order->get_parent_id() ) ),
			);
		}

		return $links;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), rest_authorization_required_code() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  \WP_REST_Request $request The request object.
	 * @return \WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', $request['id'] ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), rest_authorization_required_code() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), rest_authorization_required_code() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  \WP_REST_Request $request The request object.
	 * @return \WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'edit', $request['id'] ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'woocommerce' ), rest_authorization_required_code() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'delete', $request['id'] ) ) {
			return $this->get_route_error_response( 'woocommerce_rest_cannot_delete', __( 'Sorry, you cannot delete this resource.', 'woocommerce' ), rest_authorization_required_code() );
		}
		return true;
	}

	/**
	 * Prepare a single order item for response.
	 *
	 * @param \WC_Order        $order Order object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function prepare_item_for_response( $order, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$data   = ResponseUtils::prepare_order_for_response( $order, $request, $fields );
		$data   = $this->add_additional_fields_to_object( $data, $request );
		$data   = $this->filter_response_by_context( $data, $request['context'] ?? 'view' );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->get_item_links( $order ) );

		/**
		 * Filter the data for a response.
		 *
		 * @param \WP_REST_Response $response The response object.
		 * @param \WC_Order          $order   Order object.
		 * @param \WP_REST_Request  $request  Request object.
		 */
		return rest_ensure_response( apply_filters( $this->get_hook_prefix() . 'item_response', $response, $order, $request ) );
	}

	/**
	 * Get a single item.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {
		$order = wc_get_order( (int) $request['id'] );

		if ( ! $order instanceof \WC_Order || $order->get_id() === 0 || 'shop_order_refund' === $order->get_type() ) {
			return new \WP_Error( $this->get_error_prefix() . 'invalid_id', __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $this->prepare_item_for_response( $order, $request );
	}

	/**
	 * Get collection of orders.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		/**
		 * Filter the query arguments for a request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$query_args    = apply_filters( $this->get_hook_prefix() . 'get_items_query', QueryUtils::prepare_query( $request ), $request );
		$query         = new \WC_Order_Query(
			array_merge(
				$query_args,
				array(
					'post_type' => $this->post_type,
					'paginate'  => true,
				)
			)
		);
		$query_results = $query->get_orders();
		$items         = array();

		foreach ( $query_results->orders as $order ) {
			if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', $order->get_id() ) ) {
				continue;
			}
			$data    = $this->prepare_item_for_response( $order, $request );
			$items[] = $this->prepare_response_for_collection( $data );
		}

		$pagination_util = new Pagination();
		$response        = $pagination_util->add_headers( rest_ensure_response( $items ), $request, $query_results->total, $query_results->max_num_pages );

		return $response;
	}

	/**
	 * Create a single item.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new \WP_Error( $this->get_error_prefix() . 'exists', sprintf( __( 'Cannot create existing %s.', 'woocommerce' ), $this->post_type ), array( 'status' => 400 ) );
		}

		try {
			$order = new \WC_Order();
			$order->set_created_via( ! empty( $request['created_via'] ) ? sanitize_text_field( wp_unslash( $request['created_via'] ) ) : 'rest-api' );
			$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );

			$this->update_object_from_request( $order, $request, true );
			$this->update_additional_fields_for_object( $order, $request );

			/**
			 * Fires after a single object is created via the REST API.
			 *
			 * @param WC_Data         $order    Inserted object.
			 * @param \WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating object, false when updating.
			 */
			do_action( $this->get_hook_prefix() . 'created', $order, $request );

			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $order, $request );
			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $order->get_id() ) ) );

			return $response;
		} catch ( \WC_Data_Exception $e ) {
			$data = $e->getErrorData();

			if ( $order->get_id() ) {
				try {
					$order->set_status( 'checkout-draft' );
					$order->save();
					// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( Exception $_ ) {
					// We don't want a failure in changing the order status
					// to throw on itself, but we don't have anything meaningful
					// to do with this failure either.
				}

				$data['new_draft_order_id'] = $order->get_id();
			}
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $data );
		} catch ( \WC_REST_Exception $e ) {
			$order->delete( true );
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Update a single item.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {
		$order = wc_get_order( (int) $request['id'] );

		if ( ! $order instanceof \WC_Order || $order->get_id() === 0 || 'shop_order_refund' === $order->get_type() ) {
			return new \WP_Error( $this->get_error_prefix() . 'invalid_id', __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		try {
			$this->update_object_from_request( $order, $request, false );
			$this->update_additional_fields_for_object( $order, $request );

			/**
			 * Fires after a single object is updated via the REST API.
			 *
			 * @param WC_Data         $order    Inserted object.
			 * @param \WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating object, false when updating.
			 */
			do_action( $this->get_hook_prefix() . 'updated', $order, $request );

			$request->set_param( 'context', 'edit' );
			return $this->prepare_item_for_response( $order, $request );
		} catch ( \WC_Data_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \WC_REST_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Delete a single item.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$order = wc_get_order( (int) $request['id'] );

		if ( ! $order instanceof \WC_Order || $order->get_id() === 0 || 'shop_order_refund' === $order->get_type() ) {
			return new \WP_Error( $this->get_error_prefix() . 'invalid_id', __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$force  = (bool) $request['force'];
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $order, $request );

		if ( $force ) {
			$result = $order->delete( true );
		} else {
			/**
			 * Filter whether an object is trashable.
			 *
			 * @param boolean $supports_trash Whether the object type support trashing.
			 * @param \WC_Order $order         The object being considered for trashing support.
			 */
			$supports_trash = apply_filters( $this->get_hook_prefix() . 'object_trashable', EMPTY_TRASH_DAYS > 0, $order );

			if ( ! $supports_trash ) {
				return $this->get_route_error_response( $this->get_error_prefix() . 'trash_not_supported', __( 'This object does not support trashing.', 'woocommerce' ), 501 );
			}

			if ( 'trash' === $order->get_status() ) {
				return $this->get_route_error_response( $this->get_error_prefix() . 'already_trashed', __( 'This object has already been trashed.', 'woocommerce' ), 410 );
			}

			$order->delete();
			$result = 'trash' === $order->get_status();
		}

		if ( ! $result ) {
			return $this->get_route_error_response( $this->get_error_prefix() . 'cannot_delete', __( 'This object cannot be deleted.', 'woocommerce' ), 500 );
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param \WC_Order         $order   The deleted or trashed object.
		 * @param \WP_REST_Response $response The response data.
		 * @param \WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( $this->get_hook_prefix() . 'deleted', $order, $response, $request );

		return $response;
	}

	/**
	 * Update current object from the request.
	 *
	 * @throws \WC_REST_Exception When fails to set any item.
	 * @throws \WC_Data_Exception When fails to set any item.
	 * @param \WC_Order        $order Order object.
	 * @param \WP_REST_Request $request Request object.
	 * @param bool            $creating True when creating object, false when updating.
	 * @return void
	 */
	protected function update_object_from_request( \WC_Order $order, \WP_REST_Request $request, bool $creating = false ) {
		// Get data that can be edited from schema.
		$ignore_keys = array( 'created_via', 'status', 'customer_id', 'set_paid' );
		$data_keys = array_diff(
			array_keys( array_filter( $this->schema->get_item_properties(), array( $this, 'filter_writable_props' ) ) ),
			$ignore_keys
		);

		// Make sure gateways are loaded so hooks from gateways fire on save/create.
		WC()->payment_gateways();

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( is_null( $value ) ) {
				continue;
			}

			if ( 'billing' === $key || 'shipping' === $key ) {
				DataUtils::update_address( $order, $key, (array) $value );
			} else if ( 'coupon_lines' === $key ) {
				DataUtils::update_line_items( $order, (array) $value, 'coupon' );
			} elseif ( 'line_items' === $key ) {
				DataUtils::update_line_items( $order, (array) $value, 'line_item' );
			} elseif ( 'shipping_lines' === $key ) {
				DataUtils::update_line_items( $order, (array) $value, 'shipping' );
			} elseif ( 'fee_lines' === $key ) {
				DataUtils::update_line_items( $order, (array) $value, 'fee' );
			} elseif ( 'meta_data' === $key ) {
				DataUtils::update_meta_data( $order, (array) $value );
			} elseif ( is_callable( array( $order, "set_{$key}" ) ) ) {
				$order->{"set_{$key}"}( $value );
			}
		}

		if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
			// The customer must exist, and in a multisite context must be visible to the current user.
			if ( is_wp_error( Users::get_user_in_current_site( $request['customer_id'] ) ) ) {
				throw new \WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'woocommerce' ), 400 );
			}

			// Make sure customer is part of blog.
			if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
				add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
			}

			$order->set_customer_id( (int) $request['customer_id'] );
		}

		// Save before calculating totals to ensure all line items are up to date.
		$order->save();

		// If items have changed, recalculate order totals.
		if ( isset( $request['billing'] ) || isset( $request['shipping'] ) || isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) ) {
			$order->calculate_totals( true );
		}

		if ( isset( $request['coupon_lines'] ) ) {
			$order->recalculate_coupons();
		}

		if ( ! empty( $request['status'] ) ) {
			$order->set_status( $request['status'], '', true );
			$order->save();
		}

		// Actions for after the order is saved.
		if ( true === $request['set_paid'] ) {
			if ( $creating || $order->needs_payment() ) {
				$order->payment_complete( $request['transaction_id'] );
			}
		}
	}
}
