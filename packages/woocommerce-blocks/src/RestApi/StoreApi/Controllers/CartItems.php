<?php
/**
 * Cart controller.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WP_Error as RestError;
use \WP_REST_Server as RestServer;
use \WP_REST_Controller as RestContoller;
use \WP_REST_Response as RestResponse;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas\CartItemSchema;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\CartController;

/**
 * Cart API.
 *
 * @since 2.5.0
 */
class CartItems extends RestContoller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/store';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cart/items';

	/**
	 * Schema class instance.
	 *
	 * @var object
	 */
	protected $schema;

	/**
	 * Setup API class.
	 */
	public function __construct() {
		$this->schema = new CartItemSchema();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'  => RestServer::READABLE,
					'callback' => [ $this, 'get_items' ],
					'args'     => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				[
					'methods'  => RestServer::CREATABLE,
					'callback' => array( $this, 'create_item' ),
					'args'     => $this->get_endpoint_args_for_item_schema( RestServer::CREATABLE ),
				],
				[
					'methods'  => RestServer::DELETABLE,
					'callback' => [ $this, 'delete_items' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<key>[\w-]{32})',
			[
				'args'   => [
					'key' => [
						'description' => __( 'Unique identifier for the item within the cart.', 'woocommerce' ),
						'type'        => 'string',
					],
				],
				[
					'methods'  => RestServer::READABLE,
					'callback' => [ $this, 'get_item' ],
					'args'     => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				[
					'methods'  => RestServer::EDITABLE,
					'callback' => array( $this, 'update_item' ),
					'args'     => $this->get_endpoint_args_for_item_schema( RestServer::EDITABLE ),
				],
				[
					'methods'  => RestServer::DELETABLE,
					'callback' => [ $this, 'delete_item' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Get a collection of cart items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$controller = new CartController();
		$cart_items = $controller->get_cart_items();
		$items      = [];

		foreach ( $cart_items as $cart_item ) {
			$data    = $this->prepare_item_for_response( $cart_item, $request );
			$items[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $items );

		return $response;
	}

	/**
	 * Get a single cart items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {
		$controller = new CartController();
		$cart_item  = $controller->get_cart_item( $request['key'] );

		if ( ! $cart_item ) {
			return new RestError( 'woocommerce_rest_cart_invalid_key', __( 'Cart item does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_item_for_response( $cart_item, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		// Do not allow key to be specified during creation.
		if ( ! empty( $request['key'] ) ) {
			return new RestError( 'woocommerce_rest_cart_item_exists', __( 'Cannot create an existing cart item.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$controller = new CartController();
		$result     = $controller->add_to_cart(
			[
				'id'        => $request['id'],
				'quantity'  => $request['quantity'],
				'variation' => $request['variation'],
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = rest_ensure_response( $this->prepare_item_for_response( $controller->get_cart_item( $result ), $request ) );
		$response->set_status( 201 );
		return $response;
	}

	/**
	 * Update a single cart item.
	 *
	 * @param \WP_Rest_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$controller = new CartController();
		$cart       = $controller->get_cart_instance();
		$cart_item  = $controller->get_cart_item( $request['key'] );

		if ( ! $cart_item ) {
			return new RestError( 'woocommerce_rest_cart_invalid_key', __( 'Cart item does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( isset( $request['quantity'] ) ) {
			$cart->set_quantity( $request['key'], $request['quantity'] );
		}

		return rest_ensure_response( $this->prepare_item_for_response( $controller->get_cart_item( $request['key'] ), $request ) );
	}

	/**
	 * Delete a single cart item.
	 *
	 * @param \WP_Rest_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$controller = new CartController();
		$cart       = $controller->get_cart_instance();
		$cart_item  = $controller->get_cart_item( $request['key'] );

		if ( ! $cart_item ) {
			return new RestError( 'woocommerce_rest_cart_invalid_key', __( 'Cart item does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$cart->remove_cart_item( $request['key'] );

		return new RestResponse( null, 204 );
	}

	/**
	 * Deletes all items in the cart.
	 *
	 * @param \WP_Rest_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function delete_items( $request ) {
		$controller = new CartController();
		$controller->empty_cart();

		return new RestResponse( [], 200 );
	}

	/**
	 * Cart item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return $this->schema->get_item_schema();
	}

	/**
	 * Prepares a single item output for response.
	 *
	 * @param array            $cart_item    Cart item array.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $cart_item, $request ) {
		$data = $this->schema->get_item_response( $cart_item );

		return rest_ensure_response( $data );
	}
}
