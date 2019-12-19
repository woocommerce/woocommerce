<?php
/**
 * Cart Coupons controller.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WP_Error as RestError;
use \WP_REST_Server as RestServer;
use \WP_REST_Controller as RestController;
use \WP_REST_Response as RestResponse;
use \WP_REST_Request as RestRequest;
use \WC_REST_Exception as RestException;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas\CartCouponSchema;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\CartController;

/**
 * Cart Coupons API.
 */
class CartCoupons extends RestController {
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
	protected $rest_base = 'cart/coupons';

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
		$this->schema = new CartCouponSchema();
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
			'/' . $this->rest_base . '/(?P<code>[\w-]+)',
			[
				'args'   => [
					'code' => [
						'description' => __( 'Unique identifier for the coupon within the cart.', 'woo-gutenberg-products-block' ),
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
					'methods'  => RestServer::DELETABLE,
					'callback' => [ $this, 'delete_item' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Get a collection of cart coupons.
	 *
	 * @param RestRequest $request Full details about the request.
	 * @return RestError|RestResponse
	 */
	public function get_items( $request ) {
		$controller   = new CartController();
		$cart_coupons = $controller->get_cart_coupons();
		$items        = [];

		foreach ( $cart_coupons as $coupon_code ) {
			$response = $this->prepare_item_for_response( $coupon_code, $request );
			$response->add_links( $this->prepare_links( $coupon_code ) );

			$response = $this->prepare_response_for_collection( $response );
			$items[]  = $response;
		}

		$response = rest_ensure_response( $items );

		return $response;
	}

	/**
	 * Get a single cart coupon.
	 *
	 * @param RestRequest $request Full details about the request.
	 * @return RestError|RestResponse
	 */
	public function get_item( $request ) {
		$controller = new CartController();

		if ( ! $controller->has_coupon( $request['code'] ) ) {
			return new RestError( 'woocommerce_rest_cart_coupon_invalid_code', __( 'Coupon does not exist in the cart.', 'woo-gutenberg-products-block' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_item_for_response( $request['code'], $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Add a coupon to the cart and return the result.
	 *
	 * @param RestRequest $request Full data about the request.
	 * @return RestError|RestResponse Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( ! wc_coupons_enabled() ) {
			return new RestError( 'woocommerce_rest_cart_coupon_disabled', __( 'Coupons are disabled.', 'woo-gutenberg-products-block' ), array( 'status' => 404 ) );
		}

		$controller = new CartController();

		try {
			$controller->apply_coupon( $request['code'] );
		} catch ( RestException $e ) {
			return new RestError( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		$response = $this->get_item( $request );

		if ( $response instanceof RestError ) {
			return $response;
		}

		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Delete a single cart coupon.
	 *
	 * @param RestRequest $request Full data about the request.
	 * @return RestError|RestResponse Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$controller = new CartController();

		if ( ! $controller->has_coupon( $request['code'] ) ) {
			return new RestError( 'woocommerce_rest_cart_coupon_invalid_code', __( 'Coupon does not exist in the cart.', 'woo-gutenberg-products-block' ), array( 'status' => 404 ) );
		}

		$cart = $controller->get_cart_instance();
		$cart->remove_coupon( $request['code'] );

		return new RestResponse( null, 204 );
	}

	/**
	 * Deletes all coupons in the cart.
	 *
	 * @param RestRequest $request Full data about the request.
	 * @return RestError|RestResponse Response object on success, or WP_Error object on failure.
	 */
	public function delete_items( $request ) {
		$controller = new CartController();
		$cart       = $controller->get_cart_instance();
		$cart->remove_coupons();

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
	 * @param string      $coupon_code Coupon code.
	 * @param RestRequest $request Request object.
	 * @return RestResponse Response object.
	 */
	public function prepare_item_for_response( $coupon_code, $request ) {
		return rest_ensure_response( $this->schema->get_item_response( $coupon_code ) );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $coupon_code Coupon code.
	 * @return array
	 */
	protected function prepare_links( $coupon_code ) {
		$base  = $this->namespace . '/' . $this->rest_base;
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $coupon_code ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);
		return $links;
	}
}
