<?php
/**
 * REST API Coupons controller
 *
 * Handles requests to the /coupons endpoint.
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
 * REST API Coupons controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Coupons_Controller extends WC_REST_Posts_Controller {

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
	protected $rest_base = 'coupons';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_coupon';

	/**
	 * Order refunds actions.
	 */
	public function __construct() {
		add_filter( "woocommerce_rest_{$this->post_type}_query", array( $this, 'query_args' ), 10, 2 );
	}

	/**
	 * Register the routes for coupons.
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
					'code' => array(
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
					'context'         => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Whether to bypass trash and force deletion.', 'woocommerce' ),
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
	 * Query args.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function query_args( $args, $request ) {
		global $wpdb;

		if ( ! empty( $request['code'] ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish'", $request['code'] ) );

			$args['post__in'] = array( $id );
		}

		return $args;
	}

	/**
	 * Prepare a single coupon output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		global $wpdb;

		// Get the coupon code.
		$code = wc_get_coupon_code_by_id( $post->ID );

		$coupon = new WC_Coupon( $code );

		$data = array(
			'id'                           => $coupon->get_id(),
			'code'                         => $coupon->get_code(),
			'date_created'                 => wc_rest_prepare_date_response( $post->post_date_gmt ),
			'date_modified'                => wc_rest_prepare_date_response( $post->post_modified_gmt ),
			'discount_type'                => $coupon->get_discount_type(),
			'description'                  => $coupon->get_description(),
			'amount'                       => wc_format_decimal( $coupon->get_amount(), 2 ),
			'expiry_date'                  => ( ! empty( $coupon->get_expiry_date() ) ) ? wc_rest_prepare_date_response( $coupon->get_expiry_date() ) : null,
			'usage_count'                  => (int) $coupon->get_usage_count(),
			'individual_use'               => $coupon->get_individual_use(),
			'product_ids'                  => array_map( 'absint', (array) $coupon->get_product_ids() ),
			'exclude_product_ids'          => array_map( 'absint', (array) $coupon->get_excluded_product_ids() ),
			'usage_limit'                  => ( ! empty( $coupon->get_usage_limit() ) ) ? $coupon->get_usage_limit() : null,
			'usage_limit_per_user'         => ( ! empty( $coupon->get_usage_limit_per_user() ) ) ? $coupon->get_usage_limit_per_user() : null,
			'limit_usage_to_x_items'       => (int) $coupon->get_limit_usage_to_x_items(),
			'free_shipping'                => $coupon->get_free_shipping(),
			'product_categories'           => array_map( 'absint', (array) $coupon->get_product_categories() ),
			'excluded_product_categories'  => array_map( 'absint', (array) $coupon->get_excluded_product_categories() ),
			'exclude_sale_items'           => $coupon->get_exclude_sale_items(),
			'minimum_amount'               => wc_format_decimal( $coupon->get_minimum_amount(), 2 ),
			'maximum_amount'               => wc_format_decimal( $coupon->get_maximum_amount(), 2 ),
			'email_restrictions'           => $coupon->get_email_restrictions(),
			'used_by'                      => $coupon->get_used_by(),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $post ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param WP_Post            $post       Post object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Prepare a single coupon for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|stdClass $data Post object.
	 */
	protected function prepare_item_for_database( $request ) {
		global $wpdb;

		// ID.
		if ( isset( $request['id'] ) ) {
			$code   = wc_get_coupon_code_by_id( $request['id'] );
			$coupon = new WC_Coupon( $code );
		} else {
			$coupon = new WC_Coupon();
		}

		$schema = $this->get_item_schema();

		// Validate required POST fields.
		if ( 'POST' === $request->get_method() && empty( $coupon->get_id() ) ) {
			if ( empty( $request['code'] ) ) {
				return new WP_Error( 'woocommerce_rest_empty_coupon_code', sprintf( __( 'The coupon code cannot be empty.', 'woocommerce' ), 'code' ), array( 'status' => 400 ) );
			}
		}

		// Code.
		if ( ! empty( $schema['properties']['code'] ) && ! empty( $request['code'] ) ) {
			$coupon_code = apply_filters( 'woocommerce_coupon_code', $request['code'] );
			$id = $coupon->get_id() ? $coupon->get_id() : 0;

			// Check for duplicate coupon codes.
			$coupon_found = $wpdb->get_var( $wpdb->prepare( "
				SELECT $wpdb->posts.ID
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type = 'shop_coupon'
				AND $wpdb->posts.post_status = 'publish'
				AND $wpdb->posts.post_title = '%s'
				AND $wpdb->posts.ID != %s
			 ", $coupon_code, $id ) );

			if ( $coupon_found ) {
				return new WP_Error( 'woocommerce_rest_coupon_code_already_exists', __( 'The coupon code already exists', 'woocommerce' ), array( 'status' => 400 ) );
			}

			$coupon->set_code( $coupon_code );
		}

		// Coupon description (excerpt).
		if ( ! empty( $schema['properties']['description'] ) && isset( $request['description'] ) ) {
			$coupon->set_description( wp_filter_post_kses( $request['description'] ) );
		}

		/**
		 * Filter the query_vars used in `get_items` for the constructed query.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for insertion.
		 *
		 * @param stdClass        $data An object representing a single item prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}", $coupon, $request );
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'woocommerce' ), $this->post_type ), array( 'status' => 400 ) );
		}

		$coupon_id = $this->save_coupon( $request );
		if ( is_wp_error( $coupon_id ) ) {
			return $coupon_id;
		}

		$post = get_post( $coupon_id );
		$this->update_additional_fields_for_object( $post, $request );

		$this->add_post_meta_fields( $post, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param object          $post      Inserted object (not a WP_Post object).
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->post_type}", $post, $request, true );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $post->ID ) ) );

		return $response;
	}

	/**
	 * Update a single coupon.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$post_id = (int) $request['id'];

		if ( empty( $post_id ) || $this->post_type !== get_post_type( $post_id ) ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'ID is invalid.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$coupon_id = $this->save_coupon( $request );
		if ( is_wp_error( $coupon_id ) ) {
			return $coupon_id;
		}

		$post = get_post( $coupon_id );
		$this->update_additional_fields_for_object( $post, $request );

		$this->update_post_meta_fields( $post, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param object          $post      Inserted object (not a WP_Post object).
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->post_type}", $post, $request, false );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Saves a coupon to the database.
	 */
	public function save_coupon( $request ) {
		$coupon = $this->prepare_item_for_database( $request );
		$coupon->save();
		return $coupon->get_id();
	}

	/**
	 * Expiry date format.
	 *
	 * @param string $expiry_date
	 * @return string
	 */
	protected function get_coupon_expiry_date( $expiry_date ) {
		if ( '' != $expiry_date ) {
			return date( 'Y-m-d', strtotime( $expiry_date ) );
		}

		return '';
	}

	/**
	 * Add post meta fields.
	 *
	 * @param WP_Post $post
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	protected function add_post_meta_fields( $post, $request ) {
		$data = array_filter( $request->get_params() );

		$defaults = array(
			'discount_type'                => 'fixed_cart',
			'amount'                       => 0,
			'individual_use'               => false,
			'product_ids'                  => array(),
			'exclude_product_ids'          => array(),
			'usage_limit'                  => '',
			'usage_limit_per_user'         => '',
			'limit_usage_to_x_items'       => '',
			'usage_count'                  => '',
			'expiry_date'                  => '',
			'free_shipping'                => false,
			'product_categories'           => array(),
			'excluded_product_categories'  => array(),
			'exclude_sale_items'           => false,
			'minimum_amount'               => '',
			'maximum_amount'               => '',
			'email_restrictions'           => array(),
			'description'                  => ''
		);

		$data = wp_parse_args( $data, $defaults );

		if ( ! empty( $post->post_title ) ) {
			$coupon = new WC_Coupon( $post->post_title );
			// Set coupon meta.
			$coupon->set_discount_type( $data['discount_type'] );
			$coupon->set_amount( $data['amount'] );
			$coupon->set_individual_use( $data['individual_use'] );
			$coupon->set_product_ids( $data['product_ids'] );
			$coupon->set_excluded_product_ids( $data['exclude_product_ids'] );
			$coupon->set_usage_limit( $data['usage_limit'] );
			$coupon->set_usage_limit_per_user( $data['usage_limit_per_user'] );
			$coupon->set_limit_usage_to_x_items( $data['limit_usage_to_x_items'] );
			$coupon->set_usage_count( $data['usage_count'] );
			$coupon->set_expiry_date( $data['expiry_date'] );
			$coupon->set_free_shipping( $data['free_shipping'] );
			$coupon->set_product_categories( $data['product_categories'] );
			$coupon->set_excluded_product_categories( $data['excluded_product_categories'] );
			$coupon->set_exclude_sale_items( $data['exclude_sale_items'] );
			$coupon->set_minimum_amount( $data['minimum_amount'] );
			$coupon->set_maximum_amount( $data['maximum_amount'] );
			$coupon->set_email_restrictions( $data['email_restrictions'] );
			$coupon->save();
		}

		return true;
	}

	/**
	 * Update post meta fields.
	 *
	 * @param WP_Post $post
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	protected function update_post_meta_fields( $post, $request ) {

		$coupon = new WC_Coupon( $post->post_title );

		if ( isset( $request['amount'] ) ) {
			$coupon->set_amount( $request['amount'] );
		}

		if ( isset( $request['individual_use'] ) ) {
			$coupon->set_individual_use( $request['individual_use'] );
		}

		if ( isset( $request['product_ids'] ) ) {
			$coupon->set_product_ids( $request['product_ids'] );
		}

		if ( isset( $request['exclude_product_ids'] ) ) {
			$coupon->set_excluded_product_ids( $request['exclude_product_ids'] );
		}

		if ( isset( $request['usage_limit'] ) ) {
			$coupon->set_usage_limit( $request['usage_limit'] );
		}

		if ( isset( $request['usage_limit_per_user'] ) ) {
			$coupon->set_usage_limit_per_user( $request['usage_limit_per_user'] );
		}

		if ( isset( $request['limit_usage_to_x_items'] ) ) {
			$coupon->set_limit_usage_to_x_items( $request['limit_usage_to_x_items'] );
		}

		if ( isset( $request['usage_count'] ) ) {
			$coupon->set_usage_count( $request['usage_count'] );
		}

		if ( isset( $request['expiry_date'] ) ) {
			$coupon->set_expiry_date( $request['expiry_date'] );
		}

		if ( isset( $request['free_shipping'] ) ) {
			$coupon->set_free_shipping( $request['free_shipping'] );
		}

		if ( isset( $request['product_categories'] ) ) {
			$coupon->set_product_categories( $request['product_categories'] );
		}

		if ( isset( $request['excluded_product_categories'] ) ) {
			$coupon->set_excluded_product_categories( $request['excluded_product_categories'] );
		}

		if ( isset( $request['exclude_sale_items'] ) ) {
			$coupon->set_exclude_sale_items( $request['exclude_sale_items'] );
		}

		if ( isset( $request['minimum_amount'] ) ) {
			$coupon->set_minimum_amount( $request['minimum_amount'] );
		}

		if ( isset( $request['maximum_amount'] ) ) {
			$coupon->set_maximum_amount( $request['maximum_amount'] );
		}

		if ( isset( $request['email_restrictions'] ) ) {
			$coupon->set_email_restrictions( $request['email_restrictions'] );
		}

		$coupon->save();

		return true;
	}

	/**
	 * Get the Coupon's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the object.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'code' => array(
					'description' => __( 'Coupon code.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created' => array(
					'description' => __( "The date the coupon was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the coupon was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Coupon description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'discount_type' => array(
					'description' => __( 'Determines the type of discount that will be applied.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'fixed_cart',
					'enum'        => array_keys( wc_get_coupon_types() ),
					'context'     => array( 'view', 'edit' ),
				),
				'amount' => array(
					'description' => __( 'The amount of discount.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'expiry_date' => array(
					'description' => __( 'UTC DateTime when the coupon expires.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'usage_count' => array(
					'description' => __( 'Number of times the coupon has been used already.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'individual_use' => array(
					'description' => __( 'Whether coupon can only be used individually.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'product_ids' => array(
					'description' => __( "List of product ID's the coupon can be used on.", 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'exclude_product_ids' => array(
					'description' => __( "List of product ID's the coupon cannot be used on.", 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'usage_limit' => array(
					'description' => __( 'How many times the coupon can be used.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'usage_limit_per_user' => array(
					'description' => __( 'How many times the coupon can be used per customer.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'limit_usage_to_x_items' => array(
					'description' => __( 'Max number of items in the cart the coupon can be applied to.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'free_shipping' => array(
					'description' => __( 'Define if can be applied for free shipping.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'product_categories' => array(
					'description' => __( "List of category ID's the coupon applies to.", 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'excluded_product_categories' => array(
					'description' => __( "List of category ID's the coupon does not apply to.", 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'exclude_sale_items' => array(
					'description' => __( 'Define if should not apply when have sale items.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'minimum_amount' => array(
					'description' => __( 'Minimum order amount that needs to be in the cart before coupon applies.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'maximum_amount' => array(
					'description' => __( 'Maximum order amount allowed when using the coupon.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'email_restrictions' => array(
					'description' => __( 'List of email addresses that can use this coupon.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'used_by' => array(
					'description' => __( 'List of user IDs who have used the coupon.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['code'] = array(
			'description'       => __( 'Limit result set to resources with a specific code.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
