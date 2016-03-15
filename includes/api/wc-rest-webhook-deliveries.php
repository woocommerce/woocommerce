<?php
/**
 * REST API Webhooks controller
 *
 * Handles requests to the /webhooks/(?P<webhook_id>[\d]+)/deliveries endpoint.
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
 * REST API Webhook Deliveries controller class.
 *
 * @package WooCommerce/API
 * @extends WP_REST_Controller
 */
class WC_REST_Webhook_Deliveries_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	public $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'webhooks/(?P<webhook_id>[\d]+)/deliveries';

	/**
	 * Register the routes for webhook deliveries.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
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
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get the Webhook's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'webhook_delivery',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'duration' => array(
					'description' => __( 'The delivery duration, in seconds.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'summary' => array(
					'description' => __( 'A friendly summary of the response including the HTTP response code, message, and body.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'request_url' => array(
					'description' => __( 'The URL where the webhook was delivered.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'request_headers' => array(
					'description' => __( 'The URL where the webhook was delivered.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'request_headers' => array(
					'description' => __( 'Request headers.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'request_body' => array(
					'description' => __( 'Request body.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'response_code' => array(
					'description' => __( 'The HTTP response code from the receiving server.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'response_message' => array(
					'description' => __( 'The HTTP response message from the receiving server.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'response_headers' => array(
					'description' => __( 'Array of the response headers from the receiving server.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'response_body' => array(
					'description' => __( 'The response body from the receiving server.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'created_at' => array(
					'description' => __( "The date the webhook delivery was logged, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
