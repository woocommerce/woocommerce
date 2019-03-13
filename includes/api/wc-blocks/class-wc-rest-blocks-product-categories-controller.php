<?php
/**
 * REST API Product Categories controller customized for Products Block.
 *
 * Handles requests to the /products/categories endpoint.
 *
 * @internal This API is used internally by the block post editor--it is still in flux. It should not be used outside of wc-blocks.
 * @package WooCommerce\Blocks\Products\Rest\Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Product Categories controller class.
 *
 * @package WooCommerce/API
 */
class WC_REST_Blocks_Product_Categories_Controller extends WC_REST_Product_Categories_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-blocks/v1';

	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
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
					'methods'             => WP_REST_Server::READABLE,
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
	 * Check permissions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @param string          $context Request context.
	 * @return bool|WP_Error
	 */
	protected function check_permissions( $request, $context = 'read' ) {
		// Get taxonomy.
		$taxonomy = $this->get_taxonomy( $request );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'woocommerce_rest_taxonomy_invalid', __( 'Taxonomy does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// Check permissions for a single term.
		$id = intval( $request['id'] );
		if ( $id ) {
			$term = get_term( $id, $taxonomy );

			if ( is_wp_error( $term ) || ! $term || $term->taxonomy !== $taxonomy ) {
				return new WP_Error( 'woocommerce_rest_term_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
			}
		}

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Prepare a single product category output for response.
	 *
	 * @param WP_Term         $item    Term object.
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'     => (int) $item->term_id,
			'name'   => $item->name,
			'slug'   => $item->slug,
			'parent' => (int) $item->parent,
			'count'  => (int) $item->count,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->header( 'X-Woo-Notice', __( 'Private REST API for use by block editor only.', 'woocommerce' ) );
		$response->add_links( $this->prepare_links( $item, $request ) );

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
			'title'      => 'product_block_category',
			'type'       => 'object',
			'properties' => array(),
		);

		$schema['properties']['id']     = $raw_schema['properties']['id'];
		$schema['properties']['name']   = $raw_schema['properties']['name'];
		$schema['properties']['slug']   = $raw_schema['properties']['slug'];
		$schema['properties']['parent'] = $raw_schema['properties']['parent'];
		$schema['properties']['count']  = $raw_schema['properties']['count'];

		return $this->add_additional_fields_schema( $schema );
	}
}
