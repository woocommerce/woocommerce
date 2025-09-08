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

use Automattic\WooCommerce\RestApi\V4\AbstractController;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;
use Automattic\WooCommerce\Internal\Utilities\Users;

/**
 * Orders Controller.
 */
class Controller extends AbstractController {
	use CogsAwareTrait;

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
	 * Stores the request.
	 *
	 * @var array
	 */
	protected $request = array();

	/**
	 * Schema instance.
	 *
	 * @var Schema
	 */
	protected $schema;

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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'type'       => 'object',
			'title'      => $this->schema::IDENTIFIER,
			'properties' => $this->schema->get_item_properties(),
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
		return wc_rest_check_post_permissions( $this->post_type, 'read' ) ? true : new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  \WP_REST_Request $request The request object.
	 * @return \WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		return Utils::check_permissions( $request, 'read' ) ? true : new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		return wc_rest_check_post_permissions( $this->post_type, 'create' ) ? true : new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  \WP_REST_Request $request The request object.
	 * @return \WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		return Utils::check_permissions( $request, 'edit' ) ? true : new \WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		return Utils::check_permissions( $request, 'delete' ) ? true : new \WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you cannot delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
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

		$cogs_is_enabled = $this->cogs_is_enabled();

		if ( isset( $data['line_items'] ) ) {
			foreach ( $data['line_items'] as &$line_item_data ) {
				if ( isset( $line_item_data['cogs_value'] ) ) {
					if ( $cogs_is_enabled ) {
						$line_item_data['cost_of_goods_sold']['value'] = $line_item_data['cogs_value'];
					}
					unset( $line_item_data['cogs_value'] );
				}
			}
		}

		if ( $cogs_is_enabled ) {
			$data['cost_of_goods_sold']['total_value'] = $order->get_cogs_total_value();
		}

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

		$page      = (int) $query_args['paged'];
		$max_pages = (int) $query_results->max_num_pages;

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', $query_results->total );
		$response->header( 'X-WP-TotalPages', $max_pages );

		$request_params = $request->get_query_params();
		$base_path      = rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) );
		$base           = add_query_arg( urlencode_deep( $request_params ), $base_path );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

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

			$this->update_order_from_request( $order, $request, true );
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
			$this->update_order_from_request( $order, $request, false );
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

		// If we're forcing, then delete permanently.
		if ( $force ) {
			$order->delete( true );
			$result = 0 === $order->get_id();
		} else {
			/**
			 * Filter whether an object is trashable.
			 *
			 * Return false to disable trash support for the object.
			 *
			 * @param boolean $supports_trash Whether the object type support trashing.
			 * @param \WC_Order $order         The object being considered for trashing support.
			 */
			$supports_trash = apply_filters( $this->get_hook_prefix() . 'object_trashable', EMPTY_TRASH_DAYS > 0 && is_callable( array( $order, 'get_status' ) ), $order );

			// If we don't support trashing for this type, error out.
			if ( ! $supports_trash ) {
				/* translators: %s: post type */
				return new \WP_Error( 'woocommerce_rest_trash_not_supported', sprintf( __( 'The %s does not support trashing.', 'woocommerce' ), $this->post_type ), array( 'status' => 501 ) );
			}

			// Otherwise, only trash if we haven't already.
			if ( is_callable( array( $order, 'get_status' ) ) ) {
				if ( 'trash' === $order->get_status() ) {
					/* translators: %s: post type */
					return new \WP_Error( 'woocommerce_rest_already_trashed', sprintf( __( 'The %s has already been deleted.', 'woocommerce' ), $this->post_type ), array( 'status' => 410 ) );
				}

				$order->delete();
				$result = 'trash' === $order->get_status();
			}
		}

		if ( ! $result ) {
			/* translators: %s: post type */
			return new \WP_Error( 'woocommerce_rest_cannot_delete', sprintf( __( 'The %s cannot be deleted.', 'woocommerce' ), $this->post_type ), array( 'status' => 500 ) );
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
	 * Update an order from a request.
	 *
	 * @throws \WC_REST_Exception When fails to set any item.
	 * @throws \WC_Data_Exception When fails to set any item.
	 * @param \WC_Order        $order Order object.
	 * @param \WP_REST_Request $request Request object.
	 * @param bool            $creating True when creating object, false when updating.
	 * @return void
	 */
	protected function update_order_from_request( \WC_Order $order, \WP_REST_Request $request, bool $creating = false ) {
		// Get data that can be edited from schema.
		$ignore_keys = array( 'created_via', 'status', 'customer_id', 'set_paid' );
		$data_keys = array_diff(
			array_keys( array_filter( $this->schema->get_item_properties(), array( $this, 'filter_writable_props' ) ) ),
			$ignore_keys
		);

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( is_null( $value ) ) {
				continue;
			}

			switch ( $key ) {
				case 'billing':
				case 'shipping':
					if ( ! is_array( $value ) ) {
						break;
					}
					DataUtils::update_address( $order, $key, $value );
					break;
				case 'coupon_lines':
				case 'line_items':
				case 'shipping_lines':
				case 'fee_lines':
					if ( ! is_array( $value ) ) {
						break;
					}
					if ( $key === 'coupon_lines' ) {
						$item_type = 'coupon';
					} elseif ( $key === 'shipping_lines' ) {
						$item_type = 'shipping';
					} elseif ( $key === 'fee_lines' ) {
						$item_type = 'fee';
					} else {
						$item_type = 'line_item';
					}
					$existing_items = $order->get_items( $item_type );
					$processed_item_ids = array();
					foreach ( $value as $line_item_data ) {
						if ( ! is_array( $line_item_data ) ) {
							break;
						}
						if ( DataUtils::item_is_null_or_zero( $line_item_data ) && $line_item_data['id'] ) {
							if ( $line_item_data['id'] ) {
								DataUtils::remove_item_from_order( $order, $key, (int) $line_item_data['id'] );
							}
						} else {
							DataUtils::update_line_item( $order, $key, $line_item_data );
						}
						$processed_item_ids[] = $line_item_data['id'] ?? null;
					}
					// Remove any pre-existing items that were not posted.
					foreach ( $existing_items as $existing_item ) {
						if ( ! in_array( $existing_item->get_id(), $processed_item_ids, true ) ) {
							DataUtils::remove_item_from_order( $order, $key, $existing_item->get_id() );
						}
					}
					break;
				case 'meta_data':
					if ( ! is_array( $value ) ) {
						break;
					}
					foreach ( $value as $meta ) {
						$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
					}
					break;
				default:
					if ( is_callable( array( $order, "set_{$key}" ) ) ) {
						$order->{"set_{$key}"}( $value );
					}
					break;
			}
		}

		// Make sure gateways are loaded so hooks from gateways fire on save/create.
		WC()->payment_gateways();

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
