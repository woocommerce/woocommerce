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

use Automattic\WooCommerce\Internal\RestApiParameterUtil;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\StoreApi\Utilities\Pagination;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundSchema;
use WP_Http;
use WP_Error;
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
	 * @var OrderSchema
	 */
	protected $item_schema;

	/**
	 * Collection query class.
	 *
	 * @var CollectionQuery
	 */
	protected $collection_query;

	/**
	 * Initialize the controller.
	 *
	 * @param RefundSchema    $item_schema Refund schema class.
	 * @param CollectionQuery $collection_query Collection query class.
	 * @internal
	 */
	final public function init( RefundSchema $item_schema, CollectionQuery $collection_query ) {
		$this->item_schema      = $item_schema;
		$this->collection_query = $collection_query;
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
								'description' => __( 'When true, the payment gateway API is used to perform the refund. If the payment gateway does not support refunds, the refund will fail.', 'woocommerce' ),
								'type'        => 'boolean',
								'context'     => array( 'edit' ),
								'default'     => false,
							),
							'api_restock' => array(
								'description' => __( 'When true, refunded items are restocked.', 'woocommerce' ),
								'type'        => 'boolean',
								'context'     => array( 'edit' ),
								'default'     => false,
							),
							'line_items'  => array(
								'description' => __( 'Line items to refund. This can include products, fees, and shipping lines, combined into a single array.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'edit' ),
								'default'     => array(),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'           => array(
											'description' => __( 'Item ID.', 'woocommerce' ),
											'type'        => 'integer',
										),
										'quantity'     => array(
											'description' => __( 'Quantity to refund for this item.', 'woocommerce' ),
											'type'        => 'integer',
											'minimum'     => 0,
										),
										'refund_total' => array(
											'description' => __( 'Amount to refund for this item.', 'woocommerce' ),
											'type'        => 'number',
											'minimum'     => 0,
										),
										'refund_tax'   => array(
											'description' => __( 'Taxes to refund for this item.', 'woocommerce' ),
											'type'        => 'array',
											'default'     => array(),
											'items'       => array(
												'type' => 'object',
												'properties' => array(
													'id' => array(
														'description' => __( 'Tax ID.', 'woocommerce' ),
														'type'        => 'integer',
													),
													'refund_total' => array(
														'description' => __( 'Amount to refund for this tax.', 'woocommerce' ),
														'type'        => 'number',
														'minimum'     => 0,
													),
												),
											),
										),
									),
								),
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
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

		try {
			RestApiParameterUtil::adjust_create_refund_request_parameters( $request );

			$order = wc_get_order( $request['order_id'] );

			if ( ! $order ) {
				return $this->get_route_error_by_code( self::INVALID_ID );
			}

			if ( 0 > $request['amount'] || ! $request['amount'] ) {
				return $this->get_route_error_response( 'invalid_refund_amount', __( 'Refund total must be greater than zero.', 'woocommerce' ) );
			}

			// Validate line items.
			foreach ( $request['line_items'] as $line_item_id => $line_item ) {
				if ( ! $line_item_id ) {
					return $this->get_route_error_response( 'invalid_line_item', __( 'Line item ID is required.', 'woocommerce' ) );
				}

				$item = $order->get_item( $line_item_id );

				// Validate item exists and belongs to the order.
				if ( ! $item || $item->get_order_id() !== $order->get_id() ) {
					return $this->get_route_error_response( 'invalid_line_item', __( 'Line item not found.', 'woocommerce' ) );
				}

				// Validate item quantity is not greater than the item quantity.
				if ( $item->get_quantity() < $line_item['qty'] ) {
					return $this->get_route_error_response( 'invalid_line_item', __( 'Line item quantity cannot be greater than the item quantity.', 'woocommerce' ) );
				}

				// Validate refund total is not greater than the item total.
				if ( $item->get_total() < $line_item['refund_total'] ) {
					return $this->get_route_error_response(
						'invalid_refund_amount',
						sprintf(
							/* translators: %s: tax total */
							__( 'Refund total cannot be greater than the line item tax total (%s).', 'woocommerce' ),
							$item->get_total()
						)
					);
				}

				if ( isset( $line_item['refund_tax'] ) && ( $item_taxes = $item->get_taxes() ) ) {
					$allowed_tax_ids = array_keys( $item_taxes['total'] ?? array() );

					foreach ( $line_item['refund_tax'] as $tax_id => $refund_total ) {
						if ( ! in_array( $tax_id, $allowed_tax_ids, true ) ) {
							return $this->get_route_error_response(
								'invalid_line_item',
								sprintf(
									/* translators: %s: tax IDs */
									__( 'Line item tax not found. Must be: %s.', 'woocommerce' ),
									implode( ', ', $allowed_tax_ids )
								)
							);
						}

						if ( $item_taxes['total'][ $tax_id ] < $refund_total ) {
							return $this->get_route_error_response(
								'invalid_refund_amount',
								sprintf(
									/* translators: %s: tax total */
									__( 'Refund tax total cannot be greater than the line item tax total (%s).', 'woocommerce' ),
									$item_taxes['total'][ $tax_id ]
								)
							);
						}
					}
				}
			}

			$refund = wc_create_refund(
				array(
					'order_id'       => $order->get_id(),
					'amount'         => $request['amount'],
					'reason'         => $request['reason'],
					'line_items'     => $request['line_items'],
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

			if ( ! empty( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
				foreach ( $request['meta_data'] as $meta ) {
					$refund->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
				}
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

		$response = new WP_REST_Response( null, 204 );
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
