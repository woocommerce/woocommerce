<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * ProductsById class.
 */
class ProductsById extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'products-by-id';

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
		return '/products/(?P<id>[\d]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			'args'   => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
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
	 * Get a single product by ID.
	 *
	 * Single product lookups bypass catalog visibility and stock filtering,
	 * allowing product pages to display any published product regardless of
	 * visibility settings. Only post status and password protection are enforced.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$product_id = (int) $request['id'];
		$post       = get_post( $product_id );

		// Check post exists and is published.
		if ( ! $post || 'publish' !== $post->post_status ) {
			throw new RouteException( 'woocommerce_rest_product_invalid_id', esc_html__( 'Invalid product ID.', 'woocommerce' ), 404 );
		}

		// Exclude password-protected products.
		if ( ! empty( $post->post_password ) ) {
			throw new RouteException( 'woocommerce_rest_product_invalid_id', esc_html__( 'Invalid product ID.', 'woocommerce' ), 404 );
		}

		$object = wc_get_product( $product_id );

		if ( ! $object || 0 === $object->get_id() ) {
			throw new RouteException( 'woocommerce_rest_product_invalid_id', esc_html__( 'Invalid product ID.', 'woocommerce' ), 404 );
		}

		return rest_ensure_response( $this->schema->get_item_response( $object ) );
	}
}
