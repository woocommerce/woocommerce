<?php
/**
 * Product Attribute Terms Controller.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WP_Error as RestError;
use \WP_REST_Server as RestServer;
use \WP_REST_Controller as RestContoller;
use \WC_REST_Exception as RestException;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas\TermSchema;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\TermQuery;

/**
 * Product attribute terms API.
 *
 * @since 2.5.0
 */
class ProductAttributeTerms extends RestContoller {
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
	protected $rest_base = 'products/attributes/(?P<attribute_id>[\d]+)/terms';

	/**
	 * Schema class instance.
	 *
	 * @var TermSchema
	 */
	protected $schema;

	/**
	 * Query class instance.
	 *
	 * @var TermQuery
	 */
	protected $term_query;

	/**
	 * Setup API class.
	 */
	public function __construct() {
		$this->schema     = new TermSchema();
		$this->term_query = new TermQuery();
	}

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args'   => array(
					'attribute_id' => array(
						'description' => __( 'Unique identifier for the attribute.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'  => RestServer::READABLE,
					'callback' => [ $this, 'get_items' ],
					'args'     => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return $this->schema->get_item_schema();
	}

	/**
	 * Prepare a single item for response.
	 *
	 * @param \WP_Term         $item Term object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		return rest_ensure_response( $this->schema->get_item_response( $item ) );
	}

	/**
	 * Get a collection of attribute terms.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return RestError|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$attribute = wc_get_attribute( $request['attribute_id'] );

		if ( ! $attribute || ! taxonomy_exists( $attribute->slug ) ) {
			return new \WP_Error( 'woocommerce_rest_taxonomy_invalid', __( 'Attribute does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$term_request = [
			'taxonomy'   => $attribute->slug,
			'order'      => $request['order'],
			'orderby'    => $request['orderby'],
			'hide_empty' => $request['hide_empty'],
		];

		$objects = $this->term_query->get_objects( $term_request );
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
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
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
			'description' => __( 'Should empty terms be hidden?', 'woocommerce' ),
			'type'        => 'boolean',
			'default'     => true,
		);

		return $params;
	}
}
