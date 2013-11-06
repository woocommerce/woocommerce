<?php
/**
 * WooCommerce API Products Class
 *
 * Handles requests to the /products endpoint
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_Products extends WC_API_Base {

	/** @var string $base the route base */
	protected $base = '/products';

	/**
	 * Register the routes for this class
	 *
	 * GET|POST /products
	 * GET /products/count
	 * GET|PUT|DELETE /products/<id>
	 * GET /products/<id>/reviews
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function registerRoutes( $routes ) {

		# GET|POST /products
		$routes[ $this->base ] = array(
			array( array( $this, 'getProducts' ),     WC_API_Server::READABLE ),
			array( array( $this, 'createProduct' ),   WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /products/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'getProductsCount' ), WC_API_Server::READABLE ),
		);

		# GET|PUT|DELETE /products/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'getProduct' ),  WC_API_Server::READABLE ),
			array( array( $this, 'editProduct' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'deleteProduct' ), WC_API_Server::DELETABLE ),
		);

		# GET /products/<id>/reviews
		$routes[ $this->base . '/(?P<id>\d+)/reviews' ] = array(
			array( array( $this, 'getProductReviews' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all products
	 *
	 * @since 2.1
	 * @param array $fields
	 * @param string $type
	 * @param string $created_at_min
	 * @param string $created_at_max
	 * @param string $q search terms
	 * @param int $limit coupons per response
	 * @param int $offset
	 * @return array
	 */
	public function getProducts( $fields = null, $type = null, $created_at_min = null, $created_at_max = null, $q = null, $limit = null, $offset = null ) {

		$request_args = array(
			'type'           => $type,
			'created_at_min' => $created_at_min,
			'created_at_max' => $created_at_max,
			'q'              => $q,
			'limit'          => $limit,
			'offset'         => $offset,
		);

		$query = $this->queryProducts( $request_args );

		$products = array();

		foreach( $query->posts as $product_id ) {

			$products[] = $this->getProduct( $product_id, $fields );
		}

		return array( 'products' => $products );
	}

	/**
	 * Get the product for the given ID
	 *
	 * @since 2.1
	 * @param int $id the product ID
	 * @param string $fields
	 * @return array
	 */
	public function getProduct( $id, $fields = null ) {

		$id = absint( $id );

		if ( empty( $id ) )
			return new WP_Error( 'woocommerce_api_invalid_id', __( 'Invalid product ID', 'woocommerce' ), array( 'status' => 404 ) );

		$product = get_product( $id );

		if ( 'product' !== $product->get_post_data()->post_type )
			return new WP_Error( 'woocommerce_api_invalid_product', __( 'Invalid product', 'woocommerce' ), array( 'status' => 404 ) );

		$product_data = array(
			'id' => $product->id
		);

		return apply_filters( 'woocommerce_api_product_response', $product_data, $product, $fields );
	}

	/**
	 * Get the total number of orders
	 *
	 * @since 2.1
	 * @param string $type
	 * @param string $created_at_min
	 * @param string $created_at_max
	 * @return array
	 */
	public function getProductsCount( $type = null, $created_at_min = null, $created_at_max = null ) {

		$request_args = array(
			'type'           => $type,
			'created_at_min' => $created_at_min,
			'created_at_max' => $created_at_max,
		);

		$query = $this->queryProducts( $request_args );

		return array( 'count' => $query->found_posts );
	}


	/**
	 * Create a product
	 *
	 * @since 2.1
	 * @param array $data
	 * @return array
	 */
	public function createProduct( $data ) {

		// TODO: implement - what's the minimum set of data required? woocommerce_create_product() would be nice

		return array();
	}

	/**
	 * Edit a product
	 *
	 * @since 2.1
	 * @param int $id the product ID
	 * @param array $data
	 * @return array
	 */
	public function editProduct( $id, $data ) {

		// TODO: implement

		return $this->getProduct( $id );
	}

	/**
	 * Delete a product
	 *
	 * @since 2.1
	 * @param int $id the product ID
	 * @param bool $force true to permanently delete order, false to move to trash
	 * @return array
	 */
	public function deleteProduct( $id, $force = false ) {

		return $this->deleteResource( $id, 'product', ( 'true' === $force ) );
	}

	/**
	 * Get the reviews for a product
	 * @param $id
	 * @return mixed
	 */
	public function getProductReviews( $id ) {

		return array();
	}

	/**
	 * Helper method to get product post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return array
	 */
	private function queryProducts( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'post_parent' => 0,
			'meta_query' => array(),
		);

		// TODO: some param to show hidden products, but hide by default
		$query_args['meta_query'][] = WC()->query->visibility_meta_query();

		$query_args = $this->mergeQueryArgs( $query_args, $args );

		// TODO: navigation/total count headers for pagination

		return new WP_Query( $query_args );
	}

}
