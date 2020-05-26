<?php
/**
 * REST API Product Attributes controller customized for Products Block.
 *
 * Handles requests to the /products/attributes endpoint. These endpoints allow read-only access to editors.
 *
 * @internal This API is used internally by the block post editor--it is still in flux. It should not be used outside of wc-blocks.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Product_Attributes_Controller;

/**
 * REST API Product Attributes controller class.
 */
class ProductAttributes extends WC_REST_Product_Attributes_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/blocks';

	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
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
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
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
	 * Check if a given request has access to read the attributes.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => \rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to read a attribute.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$taxonomy = $this->get_taxonomy( $request );

		if ( ! $taxonomy || ! \taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'woocommerce_rest_taxonomy_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check permissions.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @param string           $context Request context.
	 * @return bool|\WP_Error
	 */
	protected function check_permissions( $request, $context = 'read' ) {
		// Get taxonomy.
		$taxonomy = $this->get_taxonomy( $request );
		if ( ! $taxonomy || ! \taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'woocommerce_rest_taxonomy_invalid', __( 'Taxonomy does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// Check permissions for a single term.
		$id = intval( $request['id'] );
		if ( $id ) {
			$term = get_term( $id, $taxonomy );

			if ( is_wp_error( $term ) || ! $term || $term->taxonomy !== $taxonomy ) {
				return new \WP_Error( 'woocommerce_rest_term_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
			}
		}

		return \current_user_can( 'edit_posts' );
	}

	/**
	 * Prepare a single product category output for response.
	 *
	 * @param \WP_Term         $item    Term object.
	 * @param \WP_REST_Request $request Request instance.
	 * @return \WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$taxonomy = \wc_attribute_taxonomy_name( $item->attribute_name );
		$data     = array(
			'id'    => (int) $item->attribute_id,
			'name'  => $item->attribute_label,
			'slug'  => $taxonomy,
			'count' => \wp_count_terms( $taxonomy ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = \rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $item ) );

		return $response;
	}

	/**
	 * Get the Product's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$raw_schema = parent::get_item_schema();
		$schema     = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_block_attribute',
			'type'       => 'object',
			'properties' => array(),
		);

		$schema['properties']['id']    = $raw_schema['properties']['id'];
		$schema['properties']['name']  = $raw_schema['properties']['name'];
		$schema['properties']['slug']  = $raw_schema['properties']['slug'];
		$schema['properties']['count'] = array(
			'description' => __( 'Number of terms in the attribute taxonomy.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
