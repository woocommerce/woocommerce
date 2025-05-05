<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * ProductBrandsById class.
 */
class ProductBrandsById extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product-brands-by-id';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'product-brand';

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
		return '/products/brands/(?P<identifier>[\w-]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			'args'   => array(
				'identifier' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
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
	 * Get a single item.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		if ( isset( $request['identifier'] ) && is_numeric( $request['identifier'] ) ) {
			$object = get_term( (int) $request['identifier'], 'product_brand' );
		} else {
			$object = get_term_by( 'slug', $request['identifier'], 'product_brand' );
		}

		if ( ! $object ) {
			if ( isset( $request['identifier'] ) && is_numeric( $request['identifier'] ) ) {
				throw new RouteException( 'woocommerce_rest_brand_invalid_id', esc_html__( 'Invalid brand ID.', 'woocommerce' ), 404 );
			} else {
				throw new RouteException( 'woocommerce_rest_brand_invalid_slug', esc_html__( 'Invalid brand slug.', 'woocommerce' ), 404 );
			}
		}

		$data = $this->prepare_item_for_response( $object, $request );
		return rest_ensure_response( $data );
	}
}
