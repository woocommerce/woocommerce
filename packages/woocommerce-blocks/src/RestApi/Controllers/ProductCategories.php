<?php
/**
 * REST API Product Categories controller customized for Products Block.
 *
 * Handles requests to the /products/categories endpoint. These endpoints allow read-only access to editors.
 *
 * @internal This API is used internally by the block post editor--it is still in flux. It should not be used outside of wc-blocks.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Product_Categories_Controller;

/**
 * REST API Product Categories controller class.
 */
class ProductCategories extends WC_REST_Product_Categories_Controller {

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
			$term = \get_term( $id, $taxonomy );

			if ( \is_wp_error( $term ) || ! $term || $term->taxonomy !== $taxonomy ) {
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
		$data = array(
			'id'           => (int) $item->term_id,
			'name'         => $item->name,
			'slug'         => $item->slug,
			'parent'       => (int) $item->parent,
			'count'        => (int) $item->count,
			'description'  => $item->description,
			'image'        => null,
			'review_count' => null,
			'permalink'    => get_term_link( $item->term_id, 'product_cat' ),
		);

		if ( $request->get_param( 'show_review_count' ) ) {
			global $wpdb;

			$products_of_category_sql = $wpdb->prepare(
				"SELECT SUM( DISTINCT comment_count) as review_count
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
				INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
				INNER JOIN {$wpdb->terms} AS terms USING( term_id )
				WHERE terms.term_id=%d
				AND term_taxonomy.taxonomy='product_cat'",
				$item->term_id
			);

			$review_count = $wpdb->get_var( $products_of_category_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$data['review_count'] = (int) $review_count;
		}

		$image_id = get_term_meta( $item->term_id, 'thumbnail_id', true );

		if ( $image_id ) {
			$attachment = get_post( $image_id );

			$data['image'] = array(
				'id'                => (int) $image_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment->post_date ),
				'date_created_gmt'  => wc_rest_prepare_date_response( $attachment->post_date_gmt ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment->post_modified ),
				'date_modified_gmt' => wc_rest_prepare_date_response( $attachment->post_modified_gmt ),
				'src'               => wp_get_attachment_url( $image_id ),
				'name'              => get_the_title( $attachment ),
				'alt'               => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			);
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = \rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $item, $request ) );

		return $response;
	}


	/**
	 * Update the collection params.
	 *
	 * Adds new options for 'show_review_count'.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                      = parent::get_collection_params();
		$params['show_review_count'] = array(
			'description' => __( 'Should we return how many reviews in a category?', 'woocommerce' ),
			'type'        => 'boolean',
			'default'     => false,
		);

		return $params;
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

		$schema['properties']['id']          = $raw_schema['properties']['id'];
		$schema['properties']['name']        = $raw_schema['properties']['name'];
		$schema['properties']['slug']        = $raw_schema['properties']['slug'];
		$schema['properties']['parent']      = $raw_schema['properties']['parent'];
		$schema['properties']['count']       = $raw_schema['properties']['count'];
		$schema['properties']['description'] = $raw_schema['properties']['description'];
		$schema['properties']['image']       = $raw_schema['properties']['image'];
		// review_count will return null unless show_review_count param is trus.
		$schema['properties']['review_count'] = array(
			'description' => __( 'Number of reviews in the category.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);
		$schema['properties']['permalink']    = array(
			'description' => __( 'Category URL.', 'woocommerce' ),
			'type'        => 'string',
			'format'      => 'uri',
			'context'     => array( 'view', 'edit', 'embed' ),
			'readonly'    => true,
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
