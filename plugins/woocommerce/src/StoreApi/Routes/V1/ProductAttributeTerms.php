<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * ProductAttributeTerms class.
 */
class ProductAttributeTerms extends AbstractTermsRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product-attribute-terms';

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
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
				'allow_batch'         => [ 'v1' => true ],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get the query params for collections of attributes.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                      = parent::get_collection_params();
		$params['orderby']['enum'][] = 'menu_order';
		$params['orderby']['enum'][] = 'name_num';
		$params['orderby']['enum'][] = 'id';
		return $params;
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

	/**
	 * Prepare a single attribute term for response.
	 *
	 * Product attribute taxonomies (`pa_*`) are non-hierarchical, so the underlying
	 * `WP_Term::$parent` is always 0. For this endpoint we instead expose the parent
	 * attribute ID (the `attribute_id` route parameter) so consumers can tell which
	 * attribute a term belongs to without having to track it separately.
	 *
	 * @param mixed                                  $item Term object to format to schema.
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, \WP_REST_Request $request ) {
		if ( $item instanceof \WP_Term && isset( $request['attribute_id'] ) ) {
			$item         = clone $item;
			$item->parent = (int) $request['attribute_id'];
		}

		return parent::prepare_item_for_response( $item, $request );
	}
}
