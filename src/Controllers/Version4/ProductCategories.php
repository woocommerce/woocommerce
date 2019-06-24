<?php
/**
 * REST API Product Categories controller
 *
 * Handles requests to the products/categories endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Categories controller class.
 */
class ProductCategories extends AbstractTermsContoller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/categories';

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = 'product_cat';

	/**
	 * Get data for this object in the format of this endpoint's schema.
	 *
	 * @param \WP_Term    $object Object to prepare.
	 * @param \WP_REST_Request $request Request object.
	 * @return array Array of data in the correct format.
	 */
	protected function get_data_for_response( $object, $request ) {
		$display_type = get_term_meta( $object->term_id, 'display_type', true );
		$menu_order   = get_term_meta( $object->term_id, 'order', true );
		$image_id     = get_term_meta( $object->term_id, 'thumbnail_id', true );
		$data         = array(
			'id'          => (int) $object->term_id,
			'name'        => $object->name,
			'slug'        => $object->slug,
			'parent'      => (int) $object->parent,
			'description' => $object->description,
			'display'     => $display_type ? $display_type : 'default',
			'image'       => null,
			'menu_order'  => (int) $menu_order,
			'count'       => (int) $object->count,
		);

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
		return $data;
	}

	/**
	 * Update term meta fields.
	 *
	 * @param \WP_Term         $term    Term object.
	 * @param \WP_REST_Request $request Request instance.
	 * @return bool|\WP_Error
	 *
	 * @since 3.5.5
	 */
	protected function update_term_meta_fields( $term, $request ) {
		$id = (int) $term->term_id;

		if ( isset( $request['display'] ) ) {
			update_term_meta( $id, 'display_type', 'default' === $request['display'] ? '' : $request['display'] );
		}

		if ( isset( $request['menu_order'] ) ) {
			update_term_meta( $id, 'order', $request['menu_order'] );
		}

		if ( isset( $request['image'] ) ) {
			if ( empty( $request['image']['id'] ) && ! empty( $request['image']['src'] ) ) {
				$upload = wc_rest_upload_image_from_url( esc_url_raw( $request['image']['src'] ) );

				if ( is_wp_error( $upload ) ) {
					return $upload;
				}

				$image_id = wc_rest_set_uploaded_image_as_attachment( $upload );
			} else {
				$image_id = isset( $request['image']['id'] ) ? absint( $request['image']['id'] ) : 0;
			}

			// Check if image_id is a valid image attachment before updating the term meta.
			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				update_term_meta( $id, 'thumbnail_id', $image_id );

				// Set the image alt.
				if ( ! empty( $request['image']['alt'] ) ) {
					update_post_meta( $image_id, '_wp_attachment_image_alt', wc_clean( $request['image']['alt'] ) );
				}

				// Set the image title.
				if ( ! empty( $request['image']['name'] ) ) {
					wp_update_post(
						array(
							'ID'         => $image_id,
							'post_title' => wc_clean( $request['image']['name'] ),
						)
					);
				}
			} else {
				delete_term_meta( $id, 'thumbnail_id' );
			}
		}

		return true;
	}

	/**
	 * Get the Category schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->taxonomy,
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-rest-api' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'        => array(
					'description' => __( 'Category name.', 'woocommerce-rest-api' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce-rest-api' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'parent'      => array(
					'description' => __( 'The ID for the parent of the resource.', 'woocommerce-rest-api' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'HTML description of the resource.', 'woocommerce-rest-api' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'display'     => array(
					'description' => __( 'Category archive display type.', 'woocommerce-rest-api' ),
					'type'        => 'string',
					'default'     => 'default',
					'enum'        => array( 'default', 'products', 'subcategories', 'both' ),
					'context'     => array( 'view', 'edit' ),
				),
				'image'       => array(
					'description' => __( 'Image data.', 'woocommerce-rest-api' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id'                => array(
							'description' => __( 'Image ID.', 'woocommerce-rest-api' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'date_created'      => array(
							'description' => __( "The date the image was created, in the site's timezone.", 'woocommerce-rest-api' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date_created_gmt'  => array(
							'description' => __( 'The date the image was created, as GMT.', 'woocommerce-rest-api' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date_modified'     => array(
							'description' => __( "The date the image was last modified, in the site's timezone.", 'woocommerce-rest-api' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date_modified_gmt' => array(
							'description' => __( 'The date the image was last modified, as GMT.', 'woocommerce-rest-api' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'src'               => array(
							'description' => __( 'Image URL.', 'woocommerce-rest-api' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
						'name'              => array(
							'description' => __( 'Image name.', 'woocommerce-rest-api' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'alt'               => array(
							'description' => __( 'Image alternative text.', 'woocommerce-rest-api' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'menu_order'  => array(
					'description' => __( 'Menu order, used to custom sort the resource.', 'woocommerce-rest-api' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'count'       => array(
					'description' => __( 'Number of published products for the resource.', 'woocommerce-rest-api' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
