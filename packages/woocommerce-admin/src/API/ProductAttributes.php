<?php
/**
 * REST API Product Attributes Controller
 *
 * Handles requests to /products/attributes.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Product categories controller.
 *
 * @extends WC_REST_Product_Attributes_Controller
 */
class ProductAttributes extends \WC_REST_Product_Attributes_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Register the routes for custom product attributes.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'products/attributes/(?P<slug>[a-z0-9_\-]+)',
			array(
				'args'   => array(
					'slug' => array(
						'description' => __( 'Slug identifier for the resource.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_by_slug' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params           = parent::get_collection_params();
		$params['search'] = array(
			'description'       => __( 'Search by similar attribute name.', 'woocommerce' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the Attribute's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();
		// Custom attributes substitute slugs for numeric IDs.
		$schema['properties']['id']['type'] = array( 'integer', 'string' );

		return $schema;
	}

	/**
	 * Get all attributes, with support for searching (which includes custom attributes).
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		if ( empty( $request['search'] ) ) {
			return parent::get_items( $request );
		}

		$search_string       = $request['search'];
		$matching_attributes = $this->get_custom_attributes( array( 'name' => $search_string ) );
		$taxonomy_attributes = wc_get_attribute_taxonomies();

		foreach ( $taxonomy_attributes as $attribute_obj ) {
			// Skip taxonomy attributes that didn't match the query.
			if ( false === stripos( $attribute_obj->attribute_label, $search_string ) ) {
				continue;
			}

			$attribute             = $this->prepare_item_for_response( $attribute_obj, $request );
			$matching_attributes[] = $this->prepare_response_for_collection( $attribute );
		}

		$response = rest_ensure_response( $matching_attributes );
		$response->header( 'X-WP-Total', count( $matching_attributes ) );
		$response->header( 'X-WP-TotalPages', 1 );

		return $response;
	}

	/**
	 * Get a single attribute by it's slug.
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return WP_REST_Response
	 */
	public function get_item_by_slug( $request ) {
		if ( empty( $request['slug'] ) ) {
			return array();
		}

		$matching_attributes = $this->get_custom_attributes( array( 'slug' => $request['slug'] ) );

		if ( empty( $matching_attributes ) ) {
			return new \WP_Error(
				'woocommerce_rest_product_attribute_not_found',
				__( 'No product attribute with that slug was found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$response = rest_ensure_response( $matching_attributes[0] );

		return $response;
	}

	/**
	 * Query custom attributes by name or slug.
	 *
	 * @param string $args Search arguments, either name or slug.
	 * @return array Matching attributes, formatted for response.
	 */
	protected function get_custom_attributes( $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'name' => '',
				'slug' => '',
			)
		);

		if ( empty( $args['name'] ) && empty( $args['slug'] ) ) {
			return array();
		}

		$mode = $args['name'] ? 'name' : 'slug';

		if ( 'name' === $mode ) {
			$name = $args['name'];
			// Get as close as we can to matching the name property of custom attributes using SQL.
			$like = '%"name";s:%:"%' . $wpdb->esc_like( $name ) . '%"%';
		} else {
			$slug = sanitize_title_for_query( $args['slug'] );
			// Get as close as we can to matching the slug property of custom attributes using SQL.
			$like = '%s:' . strlen( $slug ) . ':"' . $slug . '";a:6:{%';
		}

		// Find all serialized product attributes with names like the search string.
		$query_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_product_attributes'
				AND meta_value LIKE %s
				LIMIT 100",
				$like
			),
			ARRAY_A
		);

		$custom_attributes = array();

		foreach ( $query_results as $raw_product_attributes ) {

			$meta_attributes = maybe_unserialize( $raw_product_attributes['meta_value'] );

			if ( empty( $meta_attributes ) || ! is_array( $meta_attributes ) ) {
				continue;
			}

			foreach ( $meta_attributes as $meta_attribute_key => $meta_attribute_value ) {
				$meta_value = array_merge(
					array(
						'name'        => '',
						'is_taxonomy' => 0,
					),
					(array) $meta_attribute_value
				);

				// Skip non-custom attributes.
				if ( ! empty( $meta_value['is_taxonomy'] ) ) {
					continue;
				}

				// Skip custom attributes that didn't match the query.
				// (There can be any number of attributes in the meta value).
				if ( ( 'name' === $mode ) && ( false === stripos( $meta_value['name'], $name ) ) ) {
					continue;
				}

				if ( ( 'slug' === $mode ) && ( $meta_attribute_key !== $slug ) ) {
					continue;
				}

				// Skip already matched attributes.
				if ( isset( $custom_attributes[ $meta_attribute_key ] ) ) {
					continue;
				}

				// Mimic the structure of a taxonomy-backed attribute for response.
				$data = array(
					'id'           => $meta_attribute_key,
					'name'         => $meta_value['name'],
					'slug'         => $meta_attribute_key,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				);

				$response = rest_ensure_response( $data );
				$response->add_links( $this->prepare_links( (object) array( 'attribute_id' => $meta_attribute_key ) ) );
				$response = $this->prepare_response_for_collection( $response );

				$custom_attributes[ $meta_attribute_key ] = $response;
			}
		}

		return array_values( $custom_attributes );
	}
}
