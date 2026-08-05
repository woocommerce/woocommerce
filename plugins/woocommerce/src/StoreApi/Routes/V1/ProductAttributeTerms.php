<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductAttributeTermSchema;

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
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = ProductAttributeTermSchema::IDENTIFIER;

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
		$params                          = parent::get_collection_params();
		$params['orderby']['enum'][]     = 'menu_order';
		$params['orderby']['enum'][]     = 'name_num';
		$params['orderby']['enum'][]     = 'id';
		$params['__experimental_visual'] = array(
			'description'       => __( 'If true, include experimental visual swatch data for wc-visual attribute terms.', 'woocommerce' ),
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}

	/**
	 * Prepare a single item for response.
	 *
	 * @param mixed            $item Item to format to schema.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, \WP_REST_Request $request ) {
		$response = parent::prepare_item_for_response( $item, $request );

		if (
			! wc_string_to_bool( $request['__experimental_visual'] ) ||
			! ( $item instanceof \WP_Term ) ||
			! VisualAttributeTermMeta::is_visual_attribute_taxonomy( $item->taxonomy )
		) {
			return $response;
		}

		$data = $response->get_data();

		$data[ ProductAttributeTermSchema::VISUAL_PROPERTY_NAME ] = VisualAttributeTermMeta::get_term_visual(
			(int) $item->term_id
		);

		$response->set_data( $data );

		return $response;
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
