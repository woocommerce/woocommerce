<?php
/**
 * REST API Product Reviews Controller
 *
 * Handles requests to /products/reviews.
 *
 * @package WooCommerce/API
 * @since   3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Reviews Controller Class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Product_Reviews_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

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
			$this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
							'product_id'     => array(
								'required'    => true,
								'description' => __( 'Unique identifier for the product.', 'woocommerce' ),
								'type'        => 'integer',
							),
							'review'         => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Review content.', 'woocommerce' ),
							),
							'reviewer'       => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Name of the reviewer.', 'woocommerce' ),
							),
							'reviewer_email' => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Email of the reviewer.', 'woocommerce' ),
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
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
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to read webhook deliveries.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_product_reviews_permissions( 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a product review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$id     = (int) $request['id'];
		$review = get_comment( $id );

		if ( $review && ! wc_rest_check_product_reviews_permissions( 'read', $review->comment_ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create a new product review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_product_reviews_permissions( 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to update a product review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$id     = (int) $request['id'];
		$review = get_comment( $id );

		if ( $review && ! wc_rest_check_product_reviews_permissions( 'edit', $review->comment_ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete a product review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		$id     = (int) $request['id'];
		$review = get_comment( $id );

		if ( $review && ! wc_rest_check_product_reviews_permissions( 'delete', $review->comment_ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all reviews.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
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
			'reviewer'         => 'author__in',
			'reviewer_email'   => 'author_email',
			'reviewer_exclude' => 'author__not_in',
			'exclude'          => 'comment__not_in',
			'include'          => 'comment__in',
			'offset'           => 'offset',
			'order'            => 'order',
			'per_page'         => 'number',
			'product'          => 'post__in',
			'search'           => 'search',
			'status'           => 'status',
		);

		$prepared_args = array();

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $prepared_args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		// Ensure certain parameter values default to empty strings.
		foreach ( array( 'reviewer_email', 'search' ) as $param ) {
			if ( ! isset( $prepared_args[ $param ] ) ) {
				$prepared_args[ $param ] = '';
			}
		}

		if ( isset( $registered['orderby'] ) ) {
			$prepared_args['orderby'] = $this->normalize_query_param( $request['orderby'] );
		}

		if ( isset( $prepared_args['status'] ) ) {
			$prepared_args['status'] = 'approved' === $prepared_args['status'] ? 'approve' : $prepared_args['status'];
		}

		$prepared_args['no_found_rows'] = false;
		$prepared_args['date_query']    = array();

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['before'], $request['before'] ) ) {
			$prepared_args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['after'], $request['after'] ) ) {
			$prepared_args['date_query'][0]['after'] = $request['after'];
		}

		if ( isset( $registered['page'] ) && empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $prepared_args['number'] * ( absint( $request['page'] ) - 1 );
		}

		/**
		 * Filters arguments, before passing to WP_Comment_Query, when querying reviews via the REST API.
		 *
		 * @since 3.5.0
		 * @link https://developer.wordpress.org/reference/classes/wp_comment_query/
		 * @param array           $prepared_args Array of arguments for WP_Comment_Query.
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( 'wc_rest_review_query', $prepared_args, $request );

		$query        = new WP_Comment_Query();
		$query_result = $query->query( $prepared_args );
		$reviews      = array();

		foreach ( $query_result as $review ) {
			if ( ! wc_rest_check_product_reviews_permissions( 'read', $review->comment_ID ) ) {
				continue;
			}

			$data      = $this->prepare_item_for_response( $review, $request );
			$reviews[] = $this->prepare_response_for_collection( $data );
		}

		$total_reviews = (int) $query->found_comments;
		$max_pages     = (int) $query->max_num_pages;

		if ( $total_reviews < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $prepared_args['number'], $prepared_args['offset'] );

			$query                  = new WP_Comment_Query();
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
	 * Get a single product review.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id     = (int) $request['id'];
		$review = get_comment( $id );

		if ( ! $review ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_item_for_response( $review, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Prepare a single product review output for response.
	 *
	 * @param WP_Comment      $review Product review object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $review, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = array(
			'id'                   => (int) $review->comment_ID,
			'date_created'         => wc_rest_prepare_date_response( $review->comment_date ),
			'date_created_gmt'     => wc_rest_prepare_date_response( $review->comment_date_gmt ),
			'product_id'           => (int) $review->comment_post_ID,
			'status'               => $this->prepare_status_response( (string) $review->comment_approved ),
			'reviewer'             => $review->comment_author,
			'reviewer_email'       => $review->comment_author_email,
			'review'               => 'view' === $context ? wpautop( $review->comment_content ) : $review->comment_content,
			'rating'               => (int) get_comment_meta( $review->comment_ID, 'rating', true ),
			'verified'             => wc_review_is_from_verified_owner( $review->comment_ID ),
			'reviewer_avatar_urls' => rest_get_avatar_urls( $review->comment_author_email ),
		);

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $review, $request ) );

		/**
		 * Filter product reviews object returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Comment       $review   Product review object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_product_review', $response, $review, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Comment      $review Product review object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given product review.
	 */
	protected function prepare_links( $review, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $review->comment_ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
			'product'    => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $review->comment_post_ID ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Product Review's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_review',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'     => array(
					'description' => __( "The date the review was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt' => array(
					'description' => __( 'The date the review was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_id'       => array(
					'description' => __( 'Unique identifier for the product that the review belongs to.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status'           => array(
					'description' => __( 'Status of the review', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'approved', 'pending', 'spam', 'trash' ),
					'context'     => array( 'view', 'edit' ),
				),
				'reviewer'         => array(
					'description' => __( 'Reviewer name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'reviewer_email'   => array(
					'description' => __( 'Reviewer email.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'review'           => array(
					'description' => __( 'The content of the review.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'rating'           => array(
					'description' => __( 'Review rating (0 to 5).', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'verified'         => array(
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

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['after']            = array(
			'description' => __( 'Limit response to reviews published after a given ISO8601 compliant date.', 'woocommerce' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);
		$params['reviewer']         = array(
			'description' => __( 'Limit result set to reviews assigned to specific user IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);
		$params['reviewer_exclude'] = array(
			'description' => __( 'Ensure result set excludes reviews assigned to specific user IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);
		$params['reviewer_email']   = array(
			'default'     => null,
			'description' => __( 'Limit result set to that from a specific author email.', 'woocommerce' ),
			'format'      => 'email',
			'type'        => 'string',
		);
		$params['before']           = array(
			'description' => __( 'Limit response to reviews published before a given ISO8601 compliant date.', 'woocommerce' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);
		$params['exclude']          = array(
			'description' => __( 'Ensure result set excludes specific IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);
		$params['include']          = array(
			'description' => __( 'Limit result set to specific IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);
		$params['offset']           = array(
			'description' => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
			'type'        => 'integer',
		);
		$params['order']            = array(
			'description' => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array(
				'asc',
				'desc',
			),
		);
		$params['orderby']          = array(
			'description' => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'type'        => 'string',
			'default'     => 'date_gmt',
			'enum'        => array(
				'date',
				'date_gmt',
				'id',
				'include',
				'product',
			),
		);
		$params['product']          = array(
			'default'     => array(),
			'description' => __( 'Limit result set to reviews assigned to specific product IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
		);
		$params['status']           = array(
			'default'           => 'approved',
			'description'       => __( 'Limit result set to reviews assigned a specific status.', 'woocommerce' ),
			'sanitize_callback' => 'sanitize_key',
			'type'              => 'string',
			'enum'              => array(
				'all',
				'hold',
				'approved',
				'spam',
				'trash',
			),
		);

		/**
		 * Filter collection parameters for the reviews controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Comment_Query parameter. Use the
		 * `wc_rest_review_query` filter to set WP_Comment_Query parameters.
		 *
		 * @since 3.5.0
		 *
		 * @param array $params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'wc_rest_review_collection_params', $params );
	}

	/**
	 * Prepends internal property prefix to query parameters to match our response fields.
	 *
	 * @since 3.5.0
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
				break;
			case 'include':
				$normalized = 'comment__in';
				break;
			default:
				$normalized = $prefix . $query_param;
				break;
		}

		return $normalized;
	}

	/**
	 * Checks comment_approved to set comment status for single comment output.
	 *
	 * @since 3.5.0
	 * @param string|int $comment_approved comment status.
	 * @return string Comment status.
	 */
	protected function prepare_status_response( $comment_approved ) {
		switch ( $comment_approved ) {
			case 'hold':
			case '0':
				$status = 'hold';
				break;
			case 'approve':
			case '1':
				$status = 'approved';
				break;
			case 'spam':
			case 'trash':
			default:
				$status = $comment_approved;
				break;
		}

		return $status;
	}
}
