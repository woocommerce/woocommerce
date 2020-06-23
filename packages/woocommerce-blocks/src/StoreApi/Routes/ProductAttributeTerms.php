<?php
/**
 * Product Attribute Terms route.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

/**
 * ProductAttributeTerms class.
 */
class ProductAttributeTerms extends AbstractTermsRoute {
	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/products/attributes/(?P<attribute_id>[\d]+)/terms';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			'args'   => array(
				'attribute_id' => array(
					'description' => __( 'Unique identifier for the attribute.', 'woocommerce' ),
					'type'        => 'integer',
				),
			),
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_response' ],
				'args'     => $this->get_collection_params(),
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get a collection of attribute terms.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$attribute = wc_get_attribute( $request['attribute_id'] );

		if ( ! $attribute || ! taxonomy_exists( $attribute->slug ) ) {
			throw new RouteException( 'woocommerce_rest_taxonomy_invalid', __( 'Attribute does not exist.', 'woocommerce' ), 404 );
		}

		return $this->get_terms_response( $attribute->slug, $request );
	}
}
