<?php
/**
 * Product Attribute Terms route.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\TermQuery;

/**
 * ProductAttributeTerms class.
 */
class ProductAttributeTerms extends AbstractRoute {
	/**
	 * Get the namespace for this route.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return 'wc/store';
	}

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
					'description' => __( 'Unique identifier for the attribute.', 'woo-gutenberg-products-block' ),
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
		$attribute  = wc_get_attribute( $request['attribute_id'] );
		$term_query = new TermQuery();

		if ( ! $attribute || ! taxonomy_exists( $attribute->slug ) ) {
			throw new RouteException( 'woocommerce_rest_taxonomy_invalid', __( 'Attribute does not exist.', 'woo-gutenberg-products-block' ), 404 );
		}

		$term_request = [
			'taxonomy'   => $attribute->slug,
			'order'      => $request['order'],
			'orderby'    => $request['orderby'],
			'hide_empty' => $request['hide_empty'],
		];

		$objects = $term_query->get_objects( $term_request );
		$return  = [];

		foreach ( $objects as $object ) {
			$data     = $this->prepare_item_for_response( $object, $request );
			$return[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $return );
	}

	/**
	 * Get the query params for collections of attributes.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'default'           => 'name',
			'enum'              => array(
				'name',
				'slug',
				'count',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['hide_empty'] = array(
			'description' => __( 'Should empty terms be hidden?', 'woo-gutenberg-products-block' ),
			'type'        => 'boolean',
			'default'     => true,
		);

		return $params;
	}
}
