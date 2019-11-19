<?php
/**
 * Products collection data controller. Get's aggregate data from a collection of products.
 *
 * Supports the same parameters as /products, but returns a different response.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Controller as RestContoller;
use \WP_REST_Server as RestServer;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\ProductQueryFilters;

/**
 * ProductCollectionData API.
 *
 * @since 2.5.0
 */
class ProductCollectionData extends RestContoller {
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
	protected $rest_base = 'products/collection-data';

	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'  => RestServer::READABLE,
					'callback' => array( $this, 'get_items' ),
					'args'     => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Return the schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_collection_data',
			'type'       => 'object',
			'properties' => [
				'min_price'        => array(
					'description' => __( 'Min price found in collection of products.', 'woo-gutenberg-products-block' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'max_price'        => array(
					'description' => __( 'Max price found in collection of products.', 'woo-gutenberg-products-block' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'attribute_counts' => array(
					'description' => __( 'Returns number of products within attribute terms, indexed by term ID.', 'woo-gutenberg-products-block' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'term'  => array(
								'description' => __( 'Term ID', 'woo-gutenberg-products-block' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'count' => array(
								'description' => __( 'Number of products.', 'woo-gutenberg-products-block' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'rating_counts'    => array(
					'description' => __( 'Returns number of products with each average rating.', 'woo-gutenberg-products-block' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'rating' => array(
								'description' => __( 'Average rating', 'woo-gutenberg-products-block' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'count'  => array(
								'description' => __( 'Number of products.', 'woo-gutenberg-products-block' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
			],
		];
	}

	/**
	 * Get a collection of posts and add the post title filter option to \WP_Query.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return RestError|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$return  = [
			'min_price'        => null,
			'max_price'        => null,
			'attribute_counts' => null,
			'rating_counts'    => null,
		];
		$filters = new ProductQueryFilters();

		if ( ! empty( $request['calculate_price_range'] ) ) {
			$price_results = $filters->get_filtered_price( $request );

			$return['min_price'] = $price_results->min_price;
			$return['max_price'] = $price_results->max_price;
		}

		if ( ! empty( $request['calculate_attribute_counts'] ) ) {
			$return['attribute_counts'] = [];
			$counts                     = $filters->get_attribute_counts( $request, $request['calculate_attribute_counts'] );

			foreach ( $counts as $key => $value ) {
				$return['attribute_counts'][] = [
					'term'  => $key,
					'count' => $value,
				];
			}
		}

		if ( ! empty( $request['calculate_rating_counts'] ) ) {
			$return['rating_counts'] = [];
			$counts                  = $filters->get_rating_counts( $request );

			foreach ( $counts as $key => $value ) {
				$return['rating_counts'][] = [
					'rating' => $key,
					'count'  => $value,
				];
			}
		}

		return rest_ensure_response( $return );
	}

	/**
	 * Get the query params for collections of products.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = ( new Products() )->get_collection_params();

		$params['calculate_price_range'] = array(
			'description' => __( 'If true, calculates the minimum and maximum product prices for the collection.', 'woo-gutenberg-products-block' ),
			'type'        => 'boolean',
			'default'     => false,
		);

		$params['calculate_attribute_counts'] = array(
			'description' => __( 'If requested, calculates attribute term counts for products in the collection.', 'woo-gutenberg-products-block' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
			'default'     => array(),
		);

		$params['calculate_rating_counts'] = array(
			'description' => __( 'If true, calculates rating counts for products in the collection.', 'woo-gutenberg-products-block' ),
			'type'        => 'boolean',
			'default'     => false,
		);

		return $params;
	}
}
