<?php
/**
 * REST API Webhooks controller
 *
 * Handles requests to the /webhooks endpoint.
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
 * REST API Webhooks controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Webhooks_Controller extends WC_REST_Posts_Controller {

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
	protected $rest_base = 'webhooks';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_webhook';

	/**
	 * Initialize Webhooks actions.
	 */
	public function __construct() {
		add_filter( "woocommerce_rest_{$this->post_type}_query", array( $this, 'query_args' ), 10, 2 );
	}

	/**
	 * Register the routes for webhooks.
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
					'topic' => array(
						'required' => true,
					),
					'delivery_url' => array(
						'required' => true,
					),
					'secret' => array(
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
	 * Create a single webhook.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'woocommerce' ), $this->post_type ), array( 'status' => 400 ) );
		}

		// Validate topic.
		if ( empty( $request['topic'] ) || ! wc_is_webhook_valid_topic( strtolower( $request['topic'] ) ) ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_topic", __( 'Webhook topic is required and must be valid.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Validate delivery URL.
		if ( empty( $request['delivery_url'] ) || ! wc_is_valid_url( $request['delivery_url'] ) ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_delivery_url", __( 'Webhook delivery URL must be a valid URL starting with http:// or https://.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$post = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post->post_type = $this->post_type;
		$post_id = wp_insert_post( $post, true );

		if ( is_wp_error( $post_id ) ) {

			if ( in_array( $post_id->get_error_code(), array( 'db_insert_error' ) ) ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}
			return $post_id;
		}
		$post->ID = $post_id;

		$webhook = new WC_Webhook( $post_id );

		// Set topic.
		$webhook->set_topic( $request['topic'] );

		// Set delivery URL.
		$webhook->set_delivery_url( $request['delivery_url'] );

		// Set secret.
		$webhook->set_secret( ! empty( $request['secret'] ) ? $request['secret'] : '' );

		// Set status.
		if ( ! empty( $request['status'] ) ) {
			$webhook->update_status( $request['status'] );
		}

		$post = get_post( $post_id );
		$this->update_additional_fields_for_object( $post, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Post         $post      Inserted object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->post_type}", $post, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $post_id ) ) );

		// Send ping.
		$webhook->deliver_ping();

		// Clear cache.
		delete_transient( 'woocommerce_webhook_ids' );

		return $response;
	}

	/**
	 * Update a single webhook.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'ID is invalid.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$webhook = new WC_Webhook( $id );

		// Update topic.
		if ( ! empty( $request['topic'] ) ) {
			if ( wc_is_webhook_valid_topic( strtolower( $request['topic'] ) ) ) {
				$webhook->set_topic( $request['topic'] );
			} else {
				return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_topic", __( 'Webhook topic must be valid.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		// Update delivery URL.
		if ( ! empty( $request['delivery_url'] ) ) {
			if ( wc_is_valid_url( $request['delivery_url'] ) ) {
				$webhook->set_delivery_url( $request['delivery_url'] );
			} else {
				return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_delivery_url", __( 'Webhook delivery URL must be a valid URL starting with http:// or https://.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		// Update secret.
		if ( ! empty( $request['secret'] ) ) {
			$webhook->set_secret( $request['secret'] );
		}

		// Update status.
		if ( ! empty( $request['status'] ) ) {
			$webhook->update_status( $request['status'] );
		}

		$post = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		// Convert the post object to an array, otherwise wp_update_post will expect non-escaped input.
		$post_id = wp_update_post( (array) $post, true );
		if ( is_wp_error( $post_id ) ) {
			if ( in_array( $post_id->get_error_code(), array( 'db_update_error' ) ) ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}
			return $post_id;
		}

		$post = get_post( $post_id );
		$this->update_additional_fields_for_object( $post, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Post         $post      Inserted object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->post_type}", $post, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );

		// Clear cache.
		delete_transient( 'woocommerce_webhook_ids' );

		return rest_ensure_response( $response );
	}

	/**
	 * Delete a single webhook.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id    = (int) $request['id'];
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Webhooks do not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid post id.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );

		$result = wp_delete_post( $id, true );

		if ( ! $result ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', sprintf( __( 'The %s cannot be deleted.', 'woocommerce' ), $this->post_type ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single item is deleted or trashed via the REST API.
		 *
		 * @param object           $post     The deleted or trashed item.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "woocommerce_rest_delete_{$this->post_type}", $post, $response, $request );

		// Clear cache.
		delete_transient( 'woocommerce_webhook_ids' );

		return $response;
	}

	/**
	 * Prepare a single webhook for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|stdClass $data Post object.
	 */
	protected function prepare_item_for_database( $request ) {
		global $wpdb;

		$data = new stdClass;

		// Post ID.
		if ( isset( $request['id'] ) ) {
			$data->ID = absint( $request['id'] );
		}

		// Validate required POST fields.
		if ( 'POST' === $request->get_method() && empty( $data->ID ) ) {
			$data->post_title = ! empty( $request['name'] ) ? $request['name'] : sprintf( __( 'Webhook created on %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) );

			// Post author.
			$data->post_author = get_current_user_id();

			// Post password.
			$password = strlen( uniqid( 'webhook_' ) );
			$data->post_password = $password > 20 ? substr( $password, 0, 20 ) : $password;

			// Post status.
			$data->post_status = 'publish';
		} else {

			// Allow edit post title.
			if ( ! empty( $request['name'] ) ) {
				$data->post_title = $request['name'];
			}
		}

		// Comment status.
		$data->comment_status = 'closed';

		// Ping status.
		$data->ping_status = 'closed';

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
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}", $data, $request );
	}

	/**
	 * Prepare a single webhook output for response.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$id      = (int) $post->ID;
		$webhook = new WC_Webhook( $id );
		$data    = array(
			'id'            => $webhook->id,
			'name'          => $webhook->get_name(),
			'status'        => $webhook->get_status(),
			'topic'         => $webhook->get_topic(),
			'resource'      => $webhook->get_resource(),
			'event'         => $webhook->get_event(),
			'hooks'         => $webhook->get_hooks(),
			'delivery_url'  => $webhook->get_delivery_url(),
			'date_created'  => wc_rest_prepare_date_response( $webhook->get_post_data()->post_date_gmt ),
			'date_modified' => wc_rest_prepare_date_response( $webhook->get_post_data()->post_modified_gmt ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $post ) );

		/**
		 * Filter webhook object returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Webhook       $webhook  Webhook object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}", $response, $webhook, $request );
	}

	/**
	 * Query args.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function query_args( $args, $request ) {
		// Set post_status.
		switch ( $request['status'] ) {
			case 'active' :
				$args['post_status'] = 'publish';
				break;
			case 'paused' :
				$args['post_status'] = 'draft';
				break;
			case 'disabled' :
				$args['post_status'] = 'pending';
				break;
			default :
				$args['post_status'] = 'any';
				break;
		}

		return $args;
	}

	/**
	 * Get the Webhook's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'webhook',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'A friendly name for the webhook.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Webhook status.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'active',
					'enum'        => array( 'active', 'paused', 'disabled' ),
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wc_is_webhook_valid_topic',
					),
				),
				'topic' => array(
					'description' => __( 'Webhook topic.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'resource' => array(
					'description' => __( 'Webhook resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'event' => array(
					'description' => __( 'Webhook event.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'hooks' => array(
					'description' => __( 'WooCommerce action names associated with the webhook.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'delivery_url' => array(
					'description' => __( 'The URL where the webhook payload is delivered.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'secret' => array(
					'description' => __( "Secret key used to generate a hash of the delivered webhook and provided in the request headers. This will default is a MD5 hash from the current user's ID|username if not provided.", 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'date_created' => array(
					'description' => __( "The date the webhook was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the webhook was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
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

		$params['status'] = array(
			'default'           => 'all',
			'description'       => __( 'Limit result set to webhooks assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array( 'all', 'active', 'paused', 'disabled' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
