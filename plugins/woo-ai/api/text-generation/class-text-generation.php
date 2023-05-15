<?php
/**
 * REST API Data Controller
 *
 * Handles requests to /data
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Data controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Text_Generation extends \WC_REST_Data_Controller {

	/**
	 * Model details.
	 *
	 * @var array
	 */
	protected $models = array(
		'openai' => array(
			'url' => 'https://api.openai.com/v1/chat/completions',
		),
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_routes();
	}

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'wooai/text-generation';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_response' ),
					'permission_callback' => array( $this, 'get_response_permission_check' ),
					'args'                => array(
						'prompt' => array(
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to create a product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_response_permission_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the onboarding tasks.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_response( $request ) {
		$prompt        = $request->get_param( 'prompt' );
		$request_model = $request->get_param( 'model' ) ? $request->get_param( 'model' ) : 'openai';

		$model = $this->models[ $request_model ];

		$api_url = $model['url'];

		$messages = array(
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		);

		$post_data = array(
			'messages'    => $messages,
			'model'       => 'gpt-3.5-turbo',
			'temperature' => 1,
		);

		$raw_response = wp_remote_post(
			$api_url,
			array(
				'method'  => 'POST',
				'body'    => wp_json_encode( $post_data ),
				'timeout' => 45,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . OPEN_AI_KEY,
				),
			)
		);

		if ( is_wp_error( $raw_response ) ) {
			$error_message = $raw_response->get_error_message();
			return "Something went wrong: $error_message";
		}

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

		$generated_text = $response['choices'][0]['message']['content'];

		return rest_ensure_response( $generated_text );

	}

}

new Text_Generation();
