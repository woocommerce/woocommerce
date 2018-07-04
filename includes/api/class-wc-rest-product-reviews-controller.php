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
							'product_id' => array(
								'required'    => true,
								'description' => __( 'Unique identifier for the product.', 'woocommerce' ),
								'type'        => 'integer',
							),
							'review'     => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Review content.', 'woocommerce' ),
							),
							'name'       => array(
								'required'    => true,
								'type'        => 'string',
								'description' => __( 'Name of the reviewer.', 'woocommerce' ),
							),
							'email'      => array(
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
		$status  = (string) $review->comment_approved;

		$statuses = array(
			'0'     => 'pending',
			'1'     => 'approved',
			'spam'  => 'spam',
			'trash' => 'trash',
		);

		$data = array(
			'id'               => (int) $review->comment_ID,
			'date_created'     => wc_rest_prepare_date_response( $review->comment_date ),
			'date_created_gmt' => wc_rest_prepare_date_response( $review->comment_date_gmt ),
			'product_id'       => (int) $review->comment_post_ID,
			'status'           => isset( $statuses[ $status ] ) ? $statuses[ $status ] : 'pending',
			'reviewer'         => $review->comment_author,
			'email'            => $review->comment_author_email,
			'avatar_urls'      => rest_get_avatar_urls( $review->comment_author_email ),
			'verified'         => wc_review_is_from_verified_owner( $review->comment_ID ),
			'review'           => 'view' === $context ? wpautop( $review->comment_content ) : $review->comment_content,
			'rating'           => (int) get_comment_meta( $review->comment_ID, 'rating', true ),
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
				),
				'date_created_gmt' => array(
					'description' => __( 'The date the review was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
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
				'email'            => array(
					'description' => __( 'Reviewer email.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'avatar_urls'      => array(
					'description' => __( "URLs for the reviewer's avatar.", 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'verified'         => array(
					'description' => __( 'Shows if the reviewer bought the product or not.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}
