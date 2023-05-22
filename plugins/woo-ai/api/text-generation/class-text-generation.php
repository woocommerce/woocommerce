<?php
/**
 * REST API Data Controller
 *
 * Handles requests to /data
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\Admin\API;

use WP_Error, WP_REST_Request, WP_REST_Server, WP_REST_Response;

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
	protected array $models = array(
		'openai' => array(
			'url' => 'https://api.openai.com/v1/chat/completions',
			'key' => OPEN_AI_KEY,
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
	protected $namespace = 'wooai';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'text-generation';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_response' ),
					'permission_callback' => array( $this, 'get_response_permission_check' ),
					'args'                => array(
						'prompt'            => array(
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						),
						'temperature'       => array(
							'type'              => 'number',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'previous_messages' => array(
							'description' => __( 'Optional parameter to get only specific task lists by id.', 'woocommerce' ),
							'type'        => 'array',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to create.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_response_permission_check(): WP_Error|bool {
		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the text completion.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_response( WP_REST_Request $request ): WP_Error|WP_REST_Response {
		$prompt            = wp_strip_all_tags( $request->get_param( 'prompt' ) );
		$temperature       = $request->get_param( 'temperature' ) ? $request->get_param( 'temperature' ) : 1;
		$previous_messages = $request->get_param( 'previous_messages' ) ? $request->get_param( 'previous_messages' ) : array();

		array_map ( function( $message ) {
			$message['content'] = wp_strip_all_tags( $message['content'] );
			return $message;
		}, $previous_messages );

		$model = $this->models['openai'];

		$api_url = $model['url'];
		$api_key = $model['key'];

		$messages   = $previous_messages;
		$messages[] = array(
			'role'    => 'user',
			'content' => $prompt,
		);

		$post_data = array(
			'messages'    => $messages,
			'model'       => 'gpt-3.5-turbo',
			'temperature' => $temperature,
		);

		$raw_response = wp_remote_post(
			$api_url,
			array(
				'method'  => 'POST',
				'body'    => wp_json_encode( $post_data ),
				'timeout' => 45,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $raw_response ) ) {
			return new \WP_Error(
				'wooai_api_error',
				__( 'Sorry, there was an error accessing this service.', 'woocommerce' ),
				array(
					'status' => 500,
				)
			);
		}

		$res_status = $raw_response['response'];
		if ( $res_status['code'] >= 400 ) {
			return new \WP_Error(
				'wooai_api_bad_status',
				$res_status['message'],
				array(
					'status' => $res_status['code'],
				)
			);
		}

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );
		$last_choice = end( $response['choices'] );
		$messages[] = $last_choice['message'];
		// Sanitize message content for allowed post content HTML tags.
		array_map ( function( $message ) {
			$message['content'] = wp_kses_post( $message['content'] );
			return $message;
		}, $messages );

		return rest_ensure_response(
			array(
				'generated_text'    => $last_choice['message']['content'],
				'previous_messages' => array_merge( $messages, array( $last_choice['message'] ) ),
			)
		);

	}

}

new Text_Generation();
