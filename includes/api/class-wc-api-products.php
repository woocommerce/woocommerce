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

class WC_API_Products extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/products';

	/**
	 * Register the routes for this class
	 *
	 * GET /products
	 * GET /products/count
	 * GET|PUT|DELETE /products/<id>
	 * GET /products/<id>/reviews
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET /products
		$routes[ $this->base ] = array(
			array( array( $this, 'get_products' ),     WC_API_Server::READABLE ),
		);

		# GET /products/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'get_products_count' ), WC_API_Server::READABLE ),
		);

		# GET|PUT|DELETE /products/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_product' ),  WC_API_Server::READABLE ),
			array( array( $this, 'edit_product' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_product' ), WC_API_Server::DELETABLE ),
		);

		# GET /products/<id>/reviews
		$routes[ $this->base . '/(?P<id>\d+)/reviews' ] = array(
			array( array( $this, 'get_product_reviews' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all products
	 *
	 * @since 2.1
	 * @param string $fields
	 * @param string $type
	 * @param array $filter
	 * @return array
	 */
	public function get_products( $fields = null, $type = null, $filter = array() ) {

		if ( ! empty( $type ) )
			$filter['type'] = $type;

		$query = $this->query_products( $filter );

		$products = array();

		foreach( $query->posts as $product_id ) {

			if ( ! $this->is_readable( $product_id ) )
				continue;

			$products[] = $this->get_product( $product_id, $fields );
		}

		$this->server->query_navigation_headers( $query );

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
	public function get_product( $id, $fields = null ) {

		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) )
			return $id;

		$product = get_product( $id );

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
	 * @param array $filter
	 * @return array
	 */
	public function get_products_count( $type = null, $filter = array() ) {

		if ( ! empty( $type ) )
			$filter['type'] = $type;

		// TODO: permissions?

		$query = $this->query_products( $filter );

		return array( 'count' => $query->found_posts );
	}

	/**
	 * Edit a product
	 *
	 * @since 2.1
	 * @param int $id the product ID
	 * @param array $data
	 * @return array
	 */
	public function edit_product( $id, $data ) {

		$id = $this->validate_request( $id, 'product', 'edit' );

		if ( is_wp_error( $id ) )
			return $id;

		// TODO: implement

		return $this->get_product( $id );
	}

	/**
	 * Delete a product
	 *
	 * @since 2.1
	 * @param int $id the product ID
	 * @param bool $force true to permanently delete order, false to move to trash
	 * @return array
	 */
	public function delete_product( $id, $force = false ) {

		$id = $this->validate_request( $id, 'product', 'delete' );

		if ( is_wp_error( $id ) )
			return $id;

		return $this->delete( $id, 'product', ( 'true' === $force ) );
	}

	/**
	 * Get the reviews for a product
	 * @param $id
	 * @return mixed
	 */
	public function get_product_reviews( $id ) {

		$id = $this->validate_request( $id, 'product', 'read' );

		if ( is_wp_error( $id ) )
			return $id;

		// TODO: implement

		return array();
	}

	/**
	 * Helper method to get product post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	private function query_products( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'post_parent' => 0,
			'meta_query' => array(),
		);

		if ( ! empty( $args['type'] ) ) {

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $args['type'],
				),
			);

			unset( $args['type'] );
		}

		// TODO: some param to show hidden products, but hide by default
		$query_args['meta_query'][] = WC()->query->visibility_meta_query();

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}

}
