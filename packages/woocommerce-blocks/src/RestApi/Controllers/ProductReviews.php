<?php
/**
 * REST API Reviews controller customized for Blocks.
 *
 * Handles requests to the /products/reviews endpoint.
 *
 * @package WooCommerce/Blocks
 * @internal This API is used internally by the block post editor--it is still in flux. It should not be used outside of wc-blocks.
 * @internal This endpoint is open to anyone and is used on the frontend. Personal data such as reviewer email address is purposely left out. Only approved reviews are returned.
 */

namespace Automattic\WooCommerce\Blocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

use \WC_REST_Controller;

/**
 * REST API Product Reviews controller class.
 */
class ProductReviews extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/blocks';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/reviews';

	/**
	 * Register the routes for product reviews.
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
	}

	/**
	 * Check if a given request has access to read the attributes.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( 'edit' === $request['context'] ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => \rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Get all reviews.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();

		/*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'offset'     => 'offset',
			'order'      => 'order',
			'per_page'   => 'number',
			'product_id' => 'post__in',
		);

		$prepared_args = array(
			'type'          => 'review',
			'status'        => 'approve',
			'no_found_rows' => false,
		);

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $prepared_args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		/**
		 * Map category id to list of product ids.
		 */
		if ( isset( $registered['category_id'] ) && ! empty( $request['category_id'] ) ) {
			$category_ids = wp_parse_id_list( $request['category_id'] );
			$child_ids    = [];
			foreach ( $category_ids as $category_id ) {
				$child_ids = array_merge( $child_ids, get_term_children( $category_id, 'product_cat' ) );
			}
			$category_ids = array_unique( array_merge( $category_ids, $child_ids ) );
			$product_ids  = get_objects_in_term( $category_ids, 'product_cat' );

			$prepared_args['post__in'] = isset( $prepared_args['post__in'] ) ? array_merge( $prepared_args['post__in'], $product_ids ) : $product_ids;
		}

		if ( isset( $registered['orderby'] ) ) {
			if ( 'rating' === $request['orderby'] ) {
				$prepared_args['meta_query'] = array( // phpcs:ignore
					'relation' => 'OR',
					array(
						'key'     => 'rating',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'rating',
						'compare' => 'NOT EXISTS',
					),
				);
			}
			$prepared_args['orderby'] = $this->normalize_query_param( $request['orderby'] );
		}

		if ( isset( $registered['page'] ) && empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $prepared_args['number'] * ( absint( $request['page'] ) - 1 );
		}

		$query        = new \WP_Comment_Query();
		$query_result = $query->query( $prepared_args );
		$reviews      = array();

		foreach ( $query_result as $review ) {
			$data      = $this->prepare_item_for_response( $review, $request );
			$reviews[] = $this->prepare_response_for_collection( $data );
		}

		$total_reviews = (int) $query->found_comments;
		$max_pages     = (int) $query->max_num_pages;

		if ( $total_reviews < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $prepared_args['number'], $prepared_args['offset'] );

			$query                  = new \WP_Comment_Query();
			$prepared_args['count'] = true;

			$total_reviews = $query->query( $prepared_args );
			$max_pages     = ceil( $total_reviews / $request['per_page'] );
		}

		$response = rest_ensure_response( $reviews );
		$response->header( 'X-WP-Total', $total_reviews );
		$response->header( 'X-WP-TotalPages', $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $request['page'] > 1 ) {
			$prev_page = $request['page'] - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $request['page'] ) {
			$next_page = $request['page'] + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Prepends internal property prefix to query parameters to match our response fields.
	 *
	 * @param string $query_param Query parameter.
	 * @return string
	 */
	protected function normalize_query_param( $query_param ) {
		$prefix = 'comment_';

		switch ( $query_param ) {
			case 'id':
				$normalized = $prefix . 'ID';
				break;
			case 'product':
				$normalized = $prefix . 'post_ID';
				break;
			case 'rating':
				$normalized = 'meta_value_num';
				break;
			default:
				$normalized = $prefix . $query_param;
				break;
		}

		return $normalized;
	}

	/**
	 * Prepare a single product review output for response.
	 *
	 * @param \WP_Comment      $review Product review object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $review, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$rating  = get_comment_meta( $review->comment_ID, 'rating', true ) === '' ? null : (int) get_comment_meta( $review->comment_ID, 'rating', true );
		$data    = array(
			'id'                     => (int) $review->comment_ID,
			'date_created'           => wc_rest_prepare_date_response( $review->comment_date ),
			'formatted_date_created' => get_comment_date( 'F j, Y', $review->comment_ID ),
			'date_created_gmt'       => wc_rest_prepare_date_response( $review->comment_date_gmt ),
			'product_id'             => (int) $review->comment_post_ID,
			'product_name'           => get_the_title( (int) $review->comment_post_ID ),
			'product_permalink'      => get_permalink( (int) $review->comment_post_ID ),
			'product_picture'        => get_the_post_thumbnail_url( (int) $review->comment_post_ID, 96 ),
			'reviewer'               => $review->comment_author,
			'review'                 => $review->comment_content,
			'rating'                 => $rating,
			'verified'               => wc_review_is_from_verified_owner( $review->comment_ID ),
			'reviewer_avatar_urls'   => rest_get_avatar_urls( $review->comment_author_email ),
		);

		if ( 'view' === $context ) {
			$data['review'] = wpautop( $data['review'] );
		}

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Get the Product Review's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_block_review',
			'type'       => 'object',
			'properties' => array(
				'id'                     => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'           => array(
					'description' => __( "The date the review was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'formatted_date_created' => array(
					'description' => __( "The date the review was created, in the site's timezone in human-readable format.", 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'       => array(
					'description' => __( 'The date the review was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_id'             => array(
					'description' => __( 'Unique identifier for the product that the review belongs to.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_name'           => array(
					'description' => __( 'Name of the product that the review belongs to.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_permalink'      => array(
					'description' => __( 'Permalink of the product that the review belongs to.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_picture'        => array(
					'description' => __( 'Image of the product that the review belongs to.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'reviewer'               => array(
					'description' => __( 'Reviewer name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'review'                 => array(
					'description' => __( 'The content of the review.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
					'readonly'    => true,
				),
				'rating'                 => array(
					'description' => __( 'Review rating (0 to 5).', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'verified'               => array(
					'description' => __( 'Shows if the reviewer bought the product or not.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		if ( get_option( 'show_avatars' ) ) {
			$avatar_properties = array();
			$avatar_sizes      = rest_get_avatar_sizes();

			foreach ( $avatar_sizes as $size ) {
				$avatar_properties[ $size ] = array(
					/* translators: %d: avatar image size in pixels */
					'description' => sprintf( __( 'Avatar URL with image size of %d pixels.', 'woocommerce' ), $size ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
				);
			}
			$schema['properties']['reviewer_avatar_urls'] = array(
				'description' => __( 'Avatar URLs for the object reviewer.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		return $schema;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['offset'] = array(
			'description' => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
			'type'        => 'integer',
		);

		$params['order'] = array(
			'description' => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array(
				'asc',
				'desc',
			),
		);

		$params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'type'        => 'string',
			'default'     => 'date_gmt',
			'enum'        => array(
				'date',
				'date_gmt',
				'id',
				'rating',
				'product',
			),
		);

		$params['product_id'] = array(
			'default'     => array(),
			'description' => __( 'Limit result set to reviews assigned to specific product IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);

		$params['category_id'] = array(
			'default'     => array(),
			'description' => __( 'Limit result set to reviews from products within specific category IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);

		return $params;
	}
}
