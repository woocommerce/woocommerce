<?php
/**
 * REST API Order Refunds controller
 *
 * Handles requests to the /orders/<order_id>/refunds endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include( 'class-wc-rest-orders-controller.php' );

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Order_Refunds_Controller extends WC_REST_Orders_Controller {

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
	protected $rest_base = 'orders/(?P<order_id>[\d]+)/refunds';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order_refund';

	/**
	 * Stores the request.
	 * @var array
	 */
	protected $request = array();

	/**
	 * Order refunds actions.
	 */
	public function __construct() {
		add_filter( "woocommerce_rest_{$this->post_type}_trashable", '__return_false' );
		add_filter( "woocommerce_rest_{$this->post_type}_query", array( $this, 'query_args' ), 10, 2 );
	}

	/**
	 * Register the routes for order refunds.
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
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
					),
					'reassign' => array(),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Prepare a single order refund output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		$this->request = $request;
		$order         = wc_get_order( (int) $request['order_id'] );

		if ( ! $order ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), 404 );
		}

		$refund = wc_get_order( $post );

		if ( ! $refund || $refund->get_parent_id() !== $order->get_id() ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_refund_id', __( 'Invalid order refund ID.', 'woocommerce' ), 404 );
		}

		$data              = $refund->get_data();
		$format_decimal    = array( 'amount' );
		$format_date       = array( 'date_created' );
		$format_line_items = array( 'line_items' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$data[ $key ] = $data[ $key ] ? wc_rest_prepare_date_response( get_gmt_from_date( date( 'Y-m-d H:i:s', $data[ $key ] ) ) ) : false;
		}

		// Format line items.
		foreach ( $format_line_items as $key ) {
			$data[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $data[ $key ] ) );
		}

		// Unset unwanted data
		unset(
			$data['parent_id'], $data['status'], $data['currency'], $data['prices_include_tax'],
			$data['version'], $data['date_modified'], $data['discount_total'], $data['discount_tax'],
			$data['shipping_total'], $data['shipping_tax'], $data['cart_tax'], $data['cart_total'],
			$data['total'], $data['total_tax'], $data['tax_lines'], $data['shipping_lines'],
			$data['fee_lines'], $data['coupon_lines']
	 	);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $refund, $request ) );

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
	 * @param WC_Order_Refund $refund Comment object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given order refund.
	 */
	protected function prepare_links( $refund, $request ) {
		$order_id = $refund->get_parent_id();
		$base     = str_replace( '(?P<order_id>[\d]+)', $order_id, $this->rest_base );
		$links    = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $refund->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up' => array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $order_id ) ),
			),
		);

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
		$args['post_status']     = array_keys( wc_get_order_statuses() );
		$args['post_parent__in'] = array( absint( $request['order_id'] ) );
		return $args;
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
			return new WP_Error( "woocommerce_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'woocommerce' ), $this->post_type ), array( 'status' => 400 ) );
		}

		$order_data = get_post( (int) $request['order_id'] );

		if ( empty( $order_data ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_order', __( 'Order is invalid', 'woocommerce' ), 400 );
		}

		if ( 0 > $request['amount'] ) {
			return new WP_Error( 'woocommerce_rest_invalid_order_refund', __( 'Refund amount must be greater than zero.', 'woocommerce' ), 400 );
		}

		$api_refund = is_bool( $request['api_refund'] ) ? $request['api_refund'] : true;

		$data = array(
			'order_id'   => $order_data->ID,
			'amount'     => $request['amount'],
			'reason'     => empty( $request['reason'] ) ? null : $request['reason'],
			'line_items' => $request['line_items'],
		);

		// Create the refund.
		$refund = wc_create_refund( $data );

		if ( ! $refund ) {
			return new WP_Error( 'woocommerce_rest_cannot_create_order_refund', __( 'Cannot create order refund, please try again.', 'woocommerce' ), 500 );
		}

		// Refund via API.
		if ( $api_refund ) {
			if ( WC()->payment_gateways() ) {
				$payment_gateways = WC()->payment_gateways->payment_gateways();
			}

			$order = wc_get_order( $order_data );

			if ( isset( $payment_gateways[ $order->get_payment_method() ] ) && $payment_gateways[ $order->get_payment_method() ]->supports( 'refunds' ) ) {
				$result = $payment_gateways[ $order->get_payment_method() ]->process_refund( $order->get_id(), $refund->get_amount(), $refund->get_reason() );

				if ( ! $result || is_wp_error( $result ) ) {
					$refund->delete();

					$message = is_wp_error( $result ) ? $result : new WP_Error( 'woocommerce_rest_create_order_refund_api_failed', __( 'An error occurred while attempting to create the refund using the payment gateway API.', 'woocommerce' ), 500 );

					return $message;
				}
			}
		}

		$post = get_post( $refund->get_id() );
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
				'date_created' => array(
					'description' => __( "The date the order refund was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'amount' => array(
					'description' => __( 'Refund amount.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'reason' => array(
					'description' => __( 'Reason for refund.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'refunded_by' => array(
					'description' => __( 'User ID of user who created the refund.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'meta_data' => array(
					'description' => __( 'Order meta data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Meta ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key' => array(
								'description' => __( 'Meta key.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'line_items' => array(
					'description' => __( 'Line items data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
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
								'readonly'    => true,
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
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Tax rate ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
										),
										'total' => array(
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
							'meta_data' => array(
								'description' => __( 'Order item meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key' => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
							'sku' => array(
								'description' => __( 'Product SKU.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'price' => array(
								'description' => __( 'Product price.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'meta' => array(
								'description' => __( 'Order item meta data (formatted).', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
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
