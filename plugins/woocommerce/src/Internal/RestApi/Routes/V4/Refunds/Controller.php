<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Refunds controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\StoreApi\Utilities\Pagination;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundSchema;
use Automattic\WooCommerce\Utilities\MetaDataUtil;
use Automattic\WooCommerce\Utilities\NumberUtil;
use WP_Http;
use WP_Error;
use WC_Order;
use WC_Order_Refund;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Refunds Controller.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'refunds';

	/**
	 * Post type used for orders.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order_refund';

	/**
	 * Schema class for this route.
	 *
	 * @var RefundSchema
	 */
	protected $item_schema;

	/**
	 * Schema class for preview responses.
	 *
	 * @var RefundPreviewSchema
	 */
	protected $preview_schema;

	/**
	 * Collection query class.
	 *
	 * @var CollectionQuery
	 */
	protected $collection_query;

	/**
	 * Data utils class.
	 *
	 * @var DataUtils
	 */
	protected $data_utils;

	/**
	 * Initialize the controller.
	 *
	 * @internal
	 *
	 * @param RefundSchema        $item_schema Refund schema class.
	 * @param RefundPreviewSchema $preview_schema Preview schema class.
	 * @param CollectionQuery     $collection_query Collection query class.
	 * @param DataUtils           $data_utils Data utils class.
	 */
	final public function init( RefundSchema $item_schema, RefundPreviewSchema $preview_schema, CollectionQuery $collection_query, DataUtils $data_utils ) {
		$this->item_schema      = $item_schema;
		$this->preview_schema   = $preview_schema;
		$this->collection_query = $collection_query;
		$this->data_utils       = $data_utils;
	}

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Get the collection args schema.
	 *
	 * @return array
	 */
	protected function get_query_schema(): array {
		return $this->collection_query->get_query_schema();
	}

	/**
	 * List of args for endpoints. These may alter how data is returned or formatted. Extended by routes.
	 *
	 * @return array
	 */
	protected function get_endpoint_args(): array {
		return array(
			'num_decimals' => array(
				'default'           => wc_get_price_decimals(),
				'description'       => __( 'Number of decimal points to use in each resource.', 'woocommerce' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args'   => $this->get_endpoint_args(),
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
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
						array(
							'api_refund'  => array(
								'description'       => __( 'When true, the payment gateway API is used to perform the refund. If the payment gateway does not support refunds, the refund will fail.', 'woocommerce' ),
								'type'              => 'boolean',
								'context'           => array( 'edit' ),
								'default'           => false,
								'sanitize_callback' => 'rest_sanitize_boolean',
							),
							'api_restock' => array(
								'description'       => __( 'When true, refunded items are restocked.', 'woocommerce' ),
								'type'              => 'boolean',
								'context'           => array( 'edit' ),
								'default'           => false,
								'sanitize_callback' => 'rest_sanitize_boolean',
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preview',
			array(
				// permission_callback below intentionally uses the create-refund capability:
				// preview is read-only but logically part of the refund-creation flow, so it
				// requires the same capability. This prevents read-only-API clients from
				// probing refund state on orders they cannot act on.
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'preview_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'order_id'   => array(
							'description'       => __( 'The ID of the order to preview a refund for.', 'woocommerce' ),
							'type'              => 'integer',
							'required'          => true,
							'minimum'           => 1,
							'validate_callback' => 'rest_validate_request_arg',
						),
						'line_items' => array(
							'description'       => __( 'Line items to include in the refund preview.', 'woocommerce' ),
							'type'              => 'array',
							'required'          => true,
							'minItems'          => 1,
							'validate_callback' => 'rest_validate_request_arg',
							'items'             => array(
								'type'                 => 'object',
								'required'             => array( 'line_item_id' ),
								'additionalProperties' => false,
								'properties'           => array(
									'line_item_id' => array(
										'description' => __( 'ID of the original order line item.', 'woocommerce' ),
										'type'        => 'integer',
										'minimum'     => 1,
									),
									'quantity'     => array(
										'description' => __( 'Quantity to refund. Required when refund_total is omitted.', 'woocommerce' ),
										'type'        => 'integer',
										'minimum'     => 1,
									),
									'refund_total' => array(
										// No `minimum` here on purpose: validate_preview_line_items() owns
										// the sign rule and returns the actionable `invalid_refund_total`
										// code. A refund_total must be non-zero and match the line's sign —
										// negative is valid for a discount/credit line, positive for a normal
										// line; zero and wrong-sign values are rejected. A schema `minimum`
										// would wrongly forbid the negative form, and a generic
										// `rest_invalid_param` is less useful to clients.
										'description' => __( 'Tax-inclusive amount to refund for this line item. Must be non-zero and match the line\'s sign (negative for discount or credit lines, positive otherwise). Required when quantity is omitted.', 'woocommerce' ),
										'type'        => array( 'number', 'null' ),
									),
								),
							),
						),
					),
				),
				'schema' => array( $this, 'get_public_preview_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				'args'   => array_merge(
					$this->get_endpoint_args(),
					array(
						'id' => array(
							'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
							'type'        => 'integer',
						),
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
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param mixed            $item WordPress representation of the item.
	 * @param WP_REST_Request  $request Request object.
	 * @param WP_REST_Response $response Response object.
	 * @return array
	 */
	protected function prepare_links( $item, WP_REST_Request $request, WP_REST_Response $response ): array {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $item->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
			'up'         => array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $item->get_parent_id() ) ),
			),
		);

		if ( $item->get_refunded_by() ) {
			$links['refunded_by'] = array(
				'href'       => rest_url( sprintf( '/wp/v2/users/%d', $item->get_refunded_by() ) ),
				'embeddable' => true,
			);
		}

		return $links;
	}

	/**
	 * Prepare a single order object for response.
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $refund, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $refund, $request, $this->get_fields_for_response( $request ) );
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$refund = wc_get_order( (int) $request['id'] );

		if ( ! $this->is_valid_refund_for_request( $refund ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		return $this->prepare_item_for_response( $refund, $request );
	}

	/**
	 * Get collection of refunds.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args = $this->collection_query->get_query_args( $request );
		$results    = $this->collection_query->get_query_results(
			array_merge(
				$query_args,
				array(
					'post_type'   => $this->post_type,
					'post_status' => array_keys( wc_get_order_statuses() ),
				)
			),
			$request
		);
		$items      = array();

		foreach ( $results['results'] as $result ) {
			$items[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $result, $request ) );
		}

		$pagination_util = new Pagination();
		$response        = $pagination_util->add_headers( rest_ensure_response( $items ), $request, $results['total'], $results['pages'] );

		return $response;
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return $this->get_route_error_by_code( self::RESOURCE_EXISTS );
		}

		$order = wc_get_order( $request['order_id'] );

		// wc_get_order can return a WC_Order_Refund for refund IDs — reject those
		// here since refunds are not refundable themselves.
		if ( ! $order instanceof \WC_Order ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		// Fill in refund_total for any line items that omit it. The simplified
		// request form sends only {line_item_id, quantity}; the backend derives
		// the tax-inclusive total from the order's unit price × quantity.
		// Scoped try: compute_line_item_refund_total throws InvalidArgumentException
		// on quantity < 1, but fill_missing_refund_totals pre-checks that condition,
		// so this branch is defensive against a future invariant break only.
		try {
			$line_items = $this->data_utils->fill_missing_refund_totals( $request['line_items'] ?? array(), $order );
		} catch ( \InvalidArgumentException $e ) {
			wc_get_logger()->error(
				sprintf(
					'Refund creation invariant violation on order %d (%s): %s',
					$order->get_id(),
					get_class( $e ),
					$e->getMessage()
				),
				array( 'source' => 'wc-v4-refunds' )
			);
			return $this->get_route_error_response(
				'invalid_refund_request',
				__( 'The refund could not be created due to an unexpected error.', 'woocommerce' ),
				WP_Http::INTERNAL_SERVER_ERROR
			);
		}

		// Mirror the augmented array back onto the request so the 'created' hook
		// and any other downstream readers of $request['line_items'] see
		// normalised data with refund_total populated.
		$request->set_param( 'line_items', $line_items );

		try {
			// Validate request line_items before proceeding against the order being refunded.
			$validation_error = $this->data_utils->validate_line_items( $line_items, $order );

			if ( is_wp_error( $validation_error ) ) {
				// Preserve any status carried on the WP_Error so create and preview
				// return the same HTTP code for the same invalid input (e.g. 422 for
				// over-refund / non-refundable order). Falls back to 400 otherwise.
				$error_data = $validation_error->get_error_data();
				$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : WP_Http::BAD_REQUEST;
				return $this->get_route_error_response_from_object( $validation_error, $status );
			}

			// Convert line items to internal format. refund_total is tax-inclusive when no
			// explicit refund_tax is supplied (auto-computed values, or client values) — the
			// converter splits the tax portion out via the line's stored total/tax ratio
			// (DataUtils::split_inclusive_by_stored_ratio(), the same method the preview uses).
			// When the client supplies an explicit refund_tax breakdown, refund_total is the
			// tax-exclusive subtotal and the tax is added on top (core Woo semantics). Either
			// way calculate_refund_amount sums refund_total + refund_tax to the gross line
			// amount, so mixing the two forms across line items is well-defined.
			$line_item_data   = $this->data_utils->convert_line_items_to_internal_format( $line_items, $order );
			$calculated_total = ! empty( $line_items ) ? $this->data_utils->calculate_refund_amount( $line_items ) : 0;
			$refund_amount    = ! empty( $request['amount'] ) ? $request['amount'] : $calculated_total;

			if ( 0 > $refund_amount || ! $refund_amount ) {
				return $this->get_route_error_response( 'invalid_refund_amount', __( 'Refund total must be greater than zero.', 'woocommerce' ) );
			}

			// Prevent under-refunding: amount cannot be less than calculated line items total.
			// Over-refunding is allowed for goodwill/compensation scenarios.
			if ( ! empty( $request['amount'] ) && $calculated_total > 0 && NumberUtil::round( (float) $refund_amount, wc_get_price_decimals() ) < NumberUtil::round( $calculated_total, wc_get_price_decimals() ) ) {
				return $this->get_route_error_response(
					'invalid_refund_amount',
					sprintf(
						/* translators: %1$s: refund amount, %2$s: calculated total from line items */
						__( 'Refund amount (%1$s) cannot be less than the total of line items (%2$s).', 'woocommerce' ),
						wc_format_decimal( $refund_amount, wc_get_price_decimals() ),
						wc_format_decimal( $calculated_total, wc_get_price_decimals() )
					)
				);
			}

			// Over-refunding line items is allowed (goodwill), but the amount can never
			// exceed the order's remaining refundable amount. Reject up-front with a clear
			// 422 rather than relying on wc_create_refund's generic failure, mirroring the
			// preview endpoint's preview_exceeds_max_refundable guard.
			$remaining_refundable = (float) $order->get_remaining_refund_amount();
			if ( NumberUtil::round( (float) $refund_amount, wc_get_price_decimals() ) > NumberUtil::round( $remaining_refundable, wc_get_price_decimals() ) ) {
				return $this->get_route_error_response(
					'refund_exceeds_remaining',
					sprintf(
						/* translators: %1$s: requested refund amount, %2$s: remaining refundable amount */
						__( 'Refund amount (%1$s) exceeds the remaining refundable amount (%2$s).', 'woocommerce' ),
						wc_format_decimal( $refund_amount, wc_get_price_decimals() ),
						wc_format_decimal( $remaining_refundable, wc_get_price_decimals() )
					),
					WP_Http::UNPROCESSABLE_ENTITY
				);
			}

			$refund = wc_create_refund(
				array(
					'order_id'       => $order->get_id(),
					'amount'         => $refund_amount,
					'reason'         => $request['reason'],
					'line_items'     => $line_item_data,
					'refund_payment' => $request['api_refund'],
					'restock_items'  => $request['api_restock'],
				)
			);

			if ( ! $refund ) {
				return $this->get_route_error_response( 'cannot_create_refund', __( 'Cannot create order refund.', 'woocommerce' ) );
			}

			if ( is_wp_error( $refund ) ) {
				return $this->get_route_error_response( 'cannot_create_refund', $refund->get_error_message() );
			}

			if ( ! empty( $request['meta_data'] ) ) {
				MetaDataUtil::update( $request['meta_data'], $refund );
				$refund->save_meta_data();
			}

			$this->update_additional_fields_for_object( $refund, $request );

			/**
			 * Fires after a single object is created via the REST API.
			 *
			 * @param WC_Order_Refund         $refund    Inserted object.
			 * @param WP_REST_Request $request   Request object.
			 * @since 10.2.0
			 */
			do_action( $this->get_hook_prefix() . 'created', $refund, $request );

			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $refund, $request );
			$response->set_status( WP_Http::CREATED );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $refund->get_id() ) ) );

			return $response;
		} catch ( \WC_Data_Exception $e ) {
			return $this->get_route_error_response( $e->getErrorCode(), $e->getMessage() );
		} catch ( \WC_REST_Exception $e ) {
			return $this->get_route_error_response( $e->getErrorCode(), $e->getMessage() );
		}
	}

	/**
	 * Preview a refund without creating it.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 10.9.0
	 */
	public function preview_item( $request ) {
		$order = wc_get_order( $request['order_id'] );

		// wc_get_order returns WC_Order|WC_Order_Refund|false; only a WC_Order
		// (shop_order) is previewable here — refunds and missing IDs are rejected.
		if ( ! $order instanceof WC_Order ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		// Round caller-supplied refund_total values once, up front, so validation and
		// the computed preview use the same precision the create flow stores. Reused
		// for both validate and build below.
		$line_items = $this->data_utils->normalize_refund_totals( $request['line_items'] );

		$validation_error = $this->data_utils->validate_preview_line_items( $line_items, $order );

		if ( is_wp_error( $validation_error ) ) {
			$error_data = $validation_error->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : WP_Http::BAD_REQUEST;
			return $this->get_route_error_response_from_object( $validation_error, $status );
		}

		try {
			$preview = $this->data_utils->build_refund_preview( $order, $line_items );
		} catch ( \InvalidArgumentException $e ) {
			// validate_preview_line_items above should have caught any bad input.
			// If build_refund_preview still throws InvalidArgumentException, treat
			// it as a server-side invariant violation, log for observability, and
			// return a generic message (do not leak internal IDs to clients).
			wc_get_logger()->error(
				sprintf( 'Refund preview invariant violation on order %d: %s', $order->get_id(), $e->getMessage() ),
				array( 'source' => 'wc-v4-refunds' )
			);
			return $this->get_route_error_response(
				'invalid_preview_request',
				__( 'The refund preview could not be generated due to an unexpected error.', 'woocommerce' ),
				WP_Http::INTERNAL_SERVER_ERROR
			);
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Refund preview unexpected error on order %d: %s', $order->get_id(), $e->getMessage() ),
				array( 'source' => 'wc-v4-refunds' )
			);
			return $this->get_route_error_response(
				'unexpected_preview_error',
				__( 'An unexpected error occurred while generating the refund preview.', 'woocommerce' ),
				WP_Http::INTERNAL_SERVER_ERROR
			);
		}

		// Reject a non-positive aggregate total up front, mirroring create_item()'s
		// `0 > $refund_amount || ! $refund_amount` guard. A refund of only a negative
		// discount line, or a product plus discount that nets to zero, would otherwise
		// preview successfully and then fail at create with 'invalid_refund_amount'.
		if ( (float) $preview['total'] <= 0 ) {
			return $this->get_route_error_response(
				'invalid_refund_amount',
				__( 'Refund total must be greater than zero.', 'woocommerce' )
			);
		}

		// Final guard: even when per-line validation passes, the aggregate
		// preview total can still exceed the order's remaining refundable
		// amount (e.g. an amount-only partial refund applied previously).
		// Reject up-front so the eventual create call doesn't fail with the
		// generic 'cannot_create_refund' error from wc_create_refund.
		// `total` is already tax-inclusive; compare directly against max_refundable.
		$preview_total_with_tax = abs( (float) $preview['total'] );
		if ( $preview_total_with_tax > (float) $preview['max_refundable'] ) {
			return $this->get_route_error_response(
				'preview_exceeds_max_refundable',
				sprintf(
					/* translators: 1: requested preview total including tax, 2: remaining refundable */
					__( 'Requested refund preview (%1$s) exceeds the remaining refundable amount (%2$s).', 'woocommerce' ),
					wc_format_decimal( $preview_total_with_tax, wc_get_price_decimals() ),
					$preview['max_refundable']
				),
				WP_Http::UNPROCESSABLE_ENTITY
			);
		}

		return rest_ensure_response( $preview );
	}

	/**
	 * Get the public schema for the preview endpoint.
	 *
	 * @since 10.9.0
	 *
	 * @return array
	 */
	public function get_public_preview_schema(): array {
		return $this->preview_schema->get_item_schema();
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$refund = wc_get_order( (int) $request['id'] );

		if ( ! $this->is_valid_refund_for_request( $refund ) ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		$request->set_param( 'context', 'edit' );

		$response = new WP_REST_Response( null, WP_Http::NO_CONTENT );
		$result   = $refund->delete( true );

		if ( ! $result ) {
			return $this->get_route_error_by_code( self::CANNOT_DELETE );
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param WC_Order_Refund  $refund   The deleted object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 * @since 10.2.0
		 */
		do_action( $this->get_hook_prefix() . 'deleted', $refund, $response, $request );

		return $response;
	}

	/**
	 * Check if an order is valid.
	 *
	 * @param WC_Order_Refund $refund The refund object.
	 * @return bool True if the refund is valid, false otherwise.
	 */
	protected function is_valid_refund_for_request( $refund ): bool {
		return $refund instanceof WC_Order_Refund && $refund->get_id() !== 0 && 'shop_order_refund' === $refund->get_type();
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', $request['id'] ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
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
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'delete', $request['id'] ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}
}
