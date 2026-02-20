<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\ProductQuery;

/**
 * ProductsBySlug class.
 */
class ProductsBySlug extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'products-by-slug';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'product';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/products/(?P<slug>[\S]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			'args'   => array(
				'slug' => array(
					'description' => __( 'Slug of the resource.', 'woocommerce' ),
					'type'        => 'string',
				),
			),
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => array(
					'context' => $this->get_context_param(
						array(
							'default' => 'view',
						)
					),
				),
				'allow_batch'         => [ 'v1' => true ],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get a single item using the same query logic as the collection endpoint.
	 *
	 * This ensures that single product requests respect the same visibility
	 * and stock status filtering as the collection endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$slug = sanitize_title( $request['slug'] );

		$collection_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$collection_request->set_param( 'slug', $slug );
		$collection_request->set_param( 'per_page', 1 );

		$product_query = new ProductQuery();
		$query_results = $product_query->get_objects( $collection_request );

		if ( empty( $query_results['objects'] ) || ! $query_results['objects'][0] ) {
			throw new RouteException( 'woocommerce_rest_product_invalid_slug', esc_html__( 'Invalid product slug.', 'woocommerce' ), 404 );
		}

		$object = $query_results['objects'][0];

		return rest_ensure_response( $this->schema->get_item_response( $object ) );
	}
}
