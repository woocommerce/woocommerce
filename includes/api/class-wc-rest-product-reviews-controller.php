<?php
/**
 * REST API Product Reviews controller
 *
 * Handles requests to the /products/<product_id>/reviews endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Products controller class.
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
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/(?P<product_id>[\d]+)/reviews';

	/**
	 * Register the routes for product reviews.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
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
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'review' => array(
						'required' => true,
					),
					'name' => array(
						'required' => true,
					),
					'email' => array(
						'required' => true,
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
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
						'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch_items' ),
				'permission_callback' => array( $this, 'batch_items_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to read webhook deliveries.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'product', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a  product review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$post = get_post( (int) $request['product_id'] );

		if ( $post && ! wc_rest_check_post_permissions( 'product', 'read', $post->ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create a new  product review.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		$post = get_post( (int) $request['product_id'] );
		if ( $post && ! wc_rest_check_post_permissions( 'product', 'create', $post->ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you cannot create new resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
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
		$post = get_post( (int) $request['product_id'] );
		if ( $post && ! wc_rest_check_post_permissions( 'product', 'edit', $post->ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot edit resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
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
		$post = get_post( (int) $request['product_id'] );
		if ( $post && ! wc_rest_check_post_permissions( 'product', 'delete', $post->ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot delete product reviews.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to batch manage product reviews.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function batch_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'product', 'batch' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you cannot manipulate product reviews.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Get all reviews from a product.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_items( $request ) {
		$product = get_post( (int) $request['product_id'] );

		if ( empty( $product->post_type ) || 'product' !== $product->post_type ) {
			return new WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$reviews = get_approved_comments( $product->ID );
		$data    = array();
		foreach ( $reviews as $review_data ) {
			$review = $this->prepare_item_for_response( $review_data, $request );
			$review = $this->prepare_response_for_collection( $review );
			$data[] = $review;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get a single product review.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id      = (int) $request['id'];
		$product = get_post( (int) $request['product_id'] );

		if ( empty( $product->post_type ) || 'product' !== $product->post_type ) {
			return new WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$review = get_comment( $id );

		if ( empty( $id ) || empty( $review ) || intval( $review->comment_post_ID ) !== intval( $product->ID ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$delivery = $this->prepare_item_for_response( $review, $request );
		$response = rest_ensure_response( $delivery );

		return $response;
	}


	/**
	 * Create a product review.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$product = get_post( (int) $request['product_id'] );

		if ( empty( $product->post_type ) || 'product' !== $product->post_type ) {
			return new WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( empty( $request['review'] ) ) {
			return new WP_Error( 'woocommerce_rest_product_review_invalid_review', __( 'Product review content is required.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['name'] ) ) {
			return new WP_Error( 'woocommerce_rest_product_review_invalid_name', __( 'Product review author name is required.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['email'] ) ) {
			return new WP_Error( 'woocommerce_rest_product_review_invalid_email', __( 'Product review email is required.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$data = array(
			'comment_post_ID'      => $product->ID,
			'comment_author'       => $request['name'],
			'comment_author_email' => $request['email'],
			'comment_content'      => $request['review'],
			'comment_approved'     => 1,
			'comment_type'         => 'review',
		);

		$product_review_id = wp_insert_comment( $data );
		update_comment_meta( $product_review_id, 'rating', ( ! empty( $request['rating'] ) ? $request['rating'] : '0' ) );

		$comment = get_comment( $product_review_id );
		$this->update_additional_fields_for_object( $comment, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Comment         $comment      Inserted object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_product_review", $comment, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $comment, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$base = str_replace( '(?P<product_id>[\d]+)', $product->id, $this->rest_base );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $product_review_id ) ) );

		return $response;
	}

	/**
	 * Update a single product review.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$id      = (int) $request['id'];
		$product = get_post( (int) $request['product_id'] );

		if ( empty( $product->post_type ) || 'product' !== $product->post_type ) {
			return new WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$review = get_comment( $id );

		if ( empty( $id ) || empty( $review ) || intval( $review->comment_post_ID ) !== intval( $product->ID ) ) {
			return new WP_Error( 'woocommerce_rest_product_review_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// Update fields
		$commentdata = array( 'comment_ID' => $id );

		if ( ! empty( $request['name'] ) ) {
			$commentdata['comment_author'] = $request['name' ];
		}

		if ( ! empty( $request['email'] ) ) {
			$commentdata['comment_author_email'] = $request['email' ];
		}

		if ( ! empty( $request['review'] ) ) {
			$commentdata['comment_content'] = $request['review' ];
		}


		wp_update_comment( $commentdata );

		if ( ! empty( $request['rating'] ) ) {
			update_comment_meta( $id, 'rating', $request['rating'] );
		}

		$comment = get_comment( $id );
		$this->update_additional_fields_for_object( $comment, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Comment         $comment      Inserted object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_product_review", $comment, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $comment, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Delete a product review.
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_Error|boolean
	 */
	public function delete_item( $request ) {
		$id  = is_array( $request['id'] ) ? $request['id']['id'] : $request['id'];
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Product reviews do not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$comment = get_comment( $id );

		if ( empty( $id ) || empty( $comment->comment_ID ) || empty( $comment->comment_post_ID ) ) {
			return new WP_Error( 'woocommerce_rest_product_review_invalid_id', __( 'Invalid product review ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $comment, $request );

		$result  = wp_delete_comment( $id, true );

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The product review cannot be deleted.' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a product review is deleted via the REST API.
		 *
		 * @param object           $comment  The deleted item.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'rest_delete_product_review', $comment, $response, $request );

		return $response;
	}

	/**
	 * Bulk create, update and delete items.
	 *
	 * @since  2.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Of WP_Error or WP_REST_Response.
	 */
	public function batch_items( $request ) {
		$items       = array_filter( $request->get_params() );
		$params      = $request->get_url_params();
		$product_id  = $params['product_id'];
		$body_params = array();

		foreach ( array( 'update', 'create', 'delete' ) as $batch_type ) {
			if ( ! empty( $items[ $batch_type ] ) ) {
				$injected_items = array();
				foreach ( $items[ $batch_type ] as $item ) {
					$injected_items[] = array_merge( array( 'product_id' => $product_id ), $item );
				}
				$body_params[ $batch_type ] = $injected_items;
			}
		}

		$request = new WP_REST_Request( $request->get_method() );
		$request->set_body_params( $body_params );

		return parent::batch_items( $request );
	}

	/**
	 * Prepare a single product review output for response.
	 *
	 * @param WP_Comment $review Product review object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $review, $request ) {
		$data = array(
			'id'           => (int) $review->comment_ID,
			'date_created' => wc_rest_prepare_date_response( $review->comment_date_gmt ),
			'review'       => $review->comment_content,
			'rating'       => (int) get_comment_meta( $review->comment_ID, 'rating', true ),
			'name'         => $review->comment_author,
			'email'        => $review->comment_author_email,
			'verified'     => wc_review_is_from_verified_owner( $review->comment_ID ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

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
	 * @param WP_Comment $review Product review object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given product review.
	 */
	protected function prepare_links( $review, $request ) {
		$product_id = (int) $request['product_id'];
		$base       = str_replace( '(?P<product_id>[\d]+)', $product_id, $this->rest_base );
		$links      = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $review->comment_ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up' => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product_id ) ),
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
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'review' => array(
					'description' => __( 'The content of the review.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created' => array(
					'description' => __( "The date the review was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'rating' => array(
					'description' => __( 'Review rating (0 to 5).', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Reviewer name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'email' => array(
					'description' => __( 'Reviewer email.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'verified' => array(
					'description' => __( 'Shows if the reviewer bought the product or not.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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
