<?php
/**
 * REST API Products controller customized for Products Block.
 *
 * Handles requests to the /products endpoint.
 *
 * @package WooCommerce\Blocks\Products\Rest\Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Products controller class.
 *
 * @package WooCommerce/API
 */
class WGPB_Products_Controller extends WC_REST_Products_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-pb/v3';

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
						'description' => __( 'Unique identifier for the resource.', 'woo-gutenberg-products-block' ),
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
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woo-gutenberg-products-block' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woo-gutenberg-products-block' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args );

		$objects = array();
		foreach ( $query_results['objects'] as $object ) {
			$data      = $this->prepare_object_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$page      = (int) $query_args['paged'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base          = $this->rest_base;
		$attrib_prefix = '(?P<';
		if ( strpos( $base, $attrib_prefix ) !== false ) {
			$attrib_names = array();
			preg_match( '/\(\?P<[^>]+>.*\)/', $base, $attrib_names, PREG_OFFSET_CAPTURE );
			foreach ( $attrib_names as $attrib_name_match ) {
				$beginning_offset = strlen( $attrib_prefix );
				$attrib_name_end  = strpos( $attrib_name_match[0], '>', $attrib_name_match[1] );
				$attrib_name      = substr( $attrib_name_match[0], $beginning_offset, $attrib_name_end - $beginning_offset );
				if ( isset( $request[ $attrib_name ] ) ) {
					$base = str_replace( "(?P<$attrib_name>[\d]+)", $request[ $attrib_name ], $base );
				}
			}
		}
		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Get the images for a product or product variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product Product instance.
	 * @return array
	 */
	protected function get_images( $product ) {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( has_post_thumbnail( $product->get_id() ) ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'   => (int) $attachment_id,
				'src'  => current( $attachment ),
				'name' => get_the_title( $attachment_id ),
				'alt'  => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}

	/**
	 * Prepare a single product output for response.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $post, $request ) {
		$product = wc_get_product( $post );
		$data    = $this->get_product_data( $product );

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $product, $request ) );

		return $response;
	}

	/**
	 * Make extra product orderby features supported by WooCommerce available to the WC API.
	 * This includes 'price', 'popularity', and 'rating'.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$orderby       = $request->get_param( 'orderby' );
		$order         = $request->get_param( 'order' );
		$cat_operator  = $request->get_param( 'cat_operator' );
		$attributes    = $request->get_param( 'attributes' );
		$attr_operator = $request->get_param( 'attr_operator' );

		$ordering_args   = WC()->query->get_catalog_ordering_args( $orderby, $order );
		$args['orderby'] = $ordering_args['orderby'];
		$args['order']   = $ordering_args['order'];
		if ( $ordering_args['meta_key'] ) {
			$args['meta_key'] = $ordering_args['meta_key']; // WPCS: slow query ok.
		}

		if ( $cat_operator && isset( $args['tax_query'] ) ) {
			foreach ( $args['tax_query'] as $i => $tax_query ) {
				if ( 'product_cat' === $tax_query['taxonomy'] ) {
					$args['tax_query'][ $i ]['operator']         = $cat_operator;
					$args['tax_query'][ $i ]['include_children'] = 'AND' === $cat_operator ? false : true;
				}
			}
		}

		$tax_query = array();
		if ( $attributes ) {
			foreach ( $attributes as $attribute => $attribute_terms ) {
				if ( in_array( $attribute, wc_get_attribute_taxonomy_names(), true ) ) {
					$tax_query[] = array(
						'taxonomy' => $attribute,
						'field'    => 'term_id',
						'terms'    => $attribute_terms,
						'operator' => ! $attr_operator ? 'IN' : $attr_operator,
					);
				}
			}
		}

		// Merge attribute `$tax_query`s into the request's WP_Query args.
		if ( ! empty( $tax_query ) ) {
			if ( ! empty( $args['tax_query'] ) ) {
				$args['tax_query'] = array_merge( $tax_query, $args['tax_query'] ); // WPCS: slow query ok.
			} else {
				$args['tax_query'] = $tax_query; // WPCS: slow query ok.
			}
			$args['tax_query']['relation'] = 'AND' === $attr_operator ? 'AND' : 'OR';
		}

		return $args;
	}

	/**
	 * Get product data.
	 *
	 * @param WC_Product $product Product instance.
	 * @param string     $context Request context.
	 *                            Options: 'view' and 'edit'.
	 * @return array
	 */
	protected function get_product_data( $product, $context = 'view' ) {
		$raw_data = parent::get_product_data( $product, $context );
		$data     = array();

		$data['id']                = $raw_data['id'];
		$data['name']              = $raw_data['name'];
		$data['sku']               = $raw_data['sku'];
		$data['description']       = $raw_data['description'];
		$data['short_description'] = $raw_data['short_description'];
		$data['price']             = $raw_data['price'];
		$data['price_html']        = $raw_data['price_html'];
		$data['images']            = $raw_data['images'];

		return $data;
	}

	/**
	 * Update the collection params.
	 *
	 * Adds new options for 'orderby', and new parameter 'cat_operator'.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                    = parent::get_collection_params();
		$params['orderby']['enum'] = array_merge( $params['orderby']['enum'], array( 'price', 'popularity', 'rating', 'menu_order' ) );
		$params['cat_operator']    = array(
			'description'       => __( 'Operator to compare product category terms.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'enum'              => array( 'IN', 'NOT IN', 'AND' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['attr_operator']   = array(
			'description'       => __( 'Operator to compare product attribute terms.', 'woo-gutenberg-products-block' ),
			'type'              => 'string',
			'enum'              => array( 'IN', 'AND' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$attr_properties = array();
		foreach ( wc_get_attribute_taxonomy_names() as $name ) {
			$attr_properties[ $name ] = array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			);
		}
		$params['attributes'] = array(
			'description'       => __( 'Map of attributes to selected terms.', 'woo-gutenberg-products-block' ),
			'type'              => 'object',
			'validate_callback' => 'rest_validate_request_arg',
		);
		if ( ! empty( $attr_properties ) ) {
			$params['attributes']['properties']           = $attr_properties;
			$params['attributes']['additionalProperties'] = false;
		}

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
			'title'      => 'product_block_product',
			'type'       => 'object',
			'properties' => array(),
		);

		$schema['properties']['id']                = $raw_schema['properties']['id'];
		$schema['properties']['name']              = $raw_schema['properties']['name'];
		$schema['properties']['sku']               = $raw_schema['properties']['sku'];
		$schema['properties']['description']       = $raw_schema['properties']['description'];
		$schema['properties']['short_description'] = $raw_schema['properties']['short_description'];
		$schema['properties']['price']             = $raw_schema['properties']['price'];
		$schema['properties']['price_html']        = $raw_schema['properties']['price_html'];
		$schema['properties']['images']            = array(
			'description' => $raw_schema['properties']['images']['description'],
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'items'       => array(
				'type'       => 'object',
				'properties' => array(),
			),
		);

		$images_schema = $raw_schema['properties']['images']['items']['properties'];

		$schema['properties']['images']['items']['properties']['id']   = $images_schema['id'];
		$schema['properties']['images']['items']['properties']['src']  = $images_schema['src'];
		$schema['properties']['images']['items']['properties']['name'] = $images_schema['name'];
		$schema['properties']['images']['items']['properties']['alt']  = $images_schema['alt'];

		return $this->add_additional_fields_schema( $schema );
	}
}
