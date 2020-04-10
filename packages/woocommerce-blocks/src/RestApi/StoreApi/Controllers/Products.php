<?php
/**
 * Products controller.
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
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas\ProductSchema;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\Pagination;
use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\ProductQuery;

/**
 * Products API.
 *
 * @since 2.5.0
 */
class Products extends RestContoller {
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
	protected $rest_base = 'products';

	/**
	 * Schema class instance.
	 *
	 * @var ProductSchema
	 */
	protected $schema;

	/**
	 * Query class instance.
	 *
	 * @var ProductQuery
	 */
	protected $product_query;

	/**
	 * Setup API class.
	 */
	public function __construct() {
		$this->schema        = new ProductSchema();
		$this->product_query = new ProductQuery();
	}

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
					'callback' => [ $this, 'get_items' ],
					'args'     => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'  => RestServer::READABLE,
					'callback' => array( $this, 'get_item' ),
					'args'     => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Product item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return $this->schema->get_item_schema();
	}

	/**
	 * Prepare a single item for response.
	 *
	 * @param \WC_Product      $item Product object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		return rest_ensure_response( $this->schema->get_item_response( $item ) );
	}

	/**
	 * Get a single item.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return RestError|\WP_REST_Response
	 */
	public function get_item( $request ) {
		$object = wc_get_product( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new RestError( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_item_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get a collection of posts and add the post title filter option to \WP_Query.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return RestError|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_results = $this->product_query->get_objects( $request );
		$objects       = array();

		foreach ( $query_results['objects'] as $object ) {
			$data      = $this->prepare_item_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$total     = $query_results['total'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response = ( new Pagination() )->add_headers( $response, $request, $total, $max_pages );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param \WC_Product      $item Product object.
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $item->get_id() ) ),  // @codingStandardsIgnoreLine.
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),  // @codingStandardsIgnoreLine.
			),
		);

		if ( $item->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $item->get_parent_id() ) ),  // @codingStandardsIgnoreLine.
			);
		}

		return $links;
	}

	/**
	 * Get the query params for collections of products.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['page'] = array(
			'description'       => __( 'Current page of the collection.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);

		$params['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['search'] = array(
			'description'       => __( 'Limit results to those matching a string.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['after'] = array(
			'description'       => __( 'Limit response to resources created after a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['before'] = array(
			'description'       => __( 'Limit response to resources created before a given ISO8601 compliant date.', 'woocommerce' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['date_column'] = array(
			'description'       => __( 'When limiting response using after/before, which date column to compare against.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'date',
				'date_gmt',
				'modified',
				'modified_gmt',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['include'] = array(
			'description'       => __( 'Limit result set to specific ids.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['offset'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'date',
				'modified',
				'id',
				'include',
				'title',
				'slug',
				'price',
				'popularity',
				'rating',
				'menu_order',
				'comment_count',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['parent'] = array(
			'description'       => __( 'Limit result set to those of particular parent IDs.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => 'wp_parse_id_list',
			'default'           => array(),
		);

		$params['parent_exclude'] = array(
			'description'       => __( 'Limit result set to all items except those of a particular parent ID.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => 'wp_parse_id_list',
			'default'           => array(),
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to products assigned a specific type.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_keys( wc_get_product_types() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['sku'] = array(
			'description'       => __( 'Limit result set to products with specific SKU(s). Use commas to separate.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['featured'] = array(
			'description'       => __( 'Limit result set to featured products.', 'woocommerce' ),
			'type'              => 'boolean',
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['category'] = array(
			'description'       => __( 'Limit result set to products assigned a specific category ID.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['category_operator'] = array(
			'description'       => __( 'Operator to compare product category terms.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => [ 'in', 'not in', 'and' ],
			'default'           => 'in',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['tag'] = array(
			'description'       => __( 'Limit result set to products assigned a specific tag ID.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['tag_operator'] = array(
			'description'       => __( 'Operator to compare product tags.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => [ 'in', 'not in', 'and' ],
			'default'           => 'in',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['on_sale'] = array(
			'description'       => __( 'Limit result set to products on sale.', 'woocommerce' ),
			'type'              => 'boolean',
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['min_price'] = array(
			'description'       => __( 'Limit result set to products based on a minimum price.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['max_price'] = array(
			'description'       => __( 'Limit result set to products based on a maximum price.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['stock_status'] = array(
			'description'       => __( 'Limit result set to products with specified stock status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_keys( wc_get_product_stock_status_options() ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['attributes'] = array(
			'description' => __( 'Limit result set to products with selected global attributes.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'attribute' => array(
						'description'       => __( 'Attribute taxonomy name.', 'woocommerce' ),
						'type'              => 'string',
						'sanitize_callback' => 'wc_sanitize_taxonomy_name',
					),
					'term_id'   => array(
						'description'       => __( 'List of attribute term IDs.', 'woocommerce' ),
						'type'              => 'array',
						'sanitize_callback' => 'wp_parse_id_list',
					),
					'slug'      => array(
						'description'       => __( 'List of attribute slug(s). If a term ID is provided, this will be ignored.', 'woocommerce' ),
						'type'              => 'array',
						'sanitize_callback' => 'wp_parse_slug_list',
					),
					'operator'  => array(
						'description' => __( 'Operator to compare product attribute terms.', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => [ 'in', 'not in', 'and' ],
					),
				),
			),
			'default'     => array(),
		);

		$params['attribute_relation'] = array(
			'description'       => __( 'The logical relationship between attributes when filtering across multiple at once.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => [ 'in', 'and' ],
			'default'           => 'and',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['catalog_visibility'] = array(
			'description'       => __( 'Determines if hidden or visible catalog products are shown.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array( 'any', 'visible', 'catalog', 'search', 'hidden' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['rating'] = array(
			'description'       => __( 'Limit result set to products with a certain average rating.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
				'enum' => range( 1, 5 ),
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		return $params;
	}
}
