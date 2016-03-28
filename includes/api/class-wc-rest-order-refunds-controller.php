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

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Order_Refunds_Controller extends WC_REST_Posts_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	public $namespace = 'wc/v1';

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
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'email' => array(
						'required' => true,
					),
				) ),
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
	 * Check whether a given request has permission to read orders.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list order refunds.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access create orders.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an order.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access update an order.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access delete an order.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Query args.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function query_args( $args, $request ) {
		// Set post_status.
		$args['post_status'] = 'any';

		return $args;
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
					'description' => __( 'Reason for refund', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'line_items' => array(
					'description' => __( 'Line itens data.', 'woocommerce' ),
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
							'type'        => 'integer',
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
							'description' => __( 'Line total tax.', 'woocommerce' ),
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
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
