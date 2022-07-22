<?php
/**
 * REST API Data countries controller.
 *
 * Handles requests to the /data/countries endpoint.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;

/**
 * REST API Data countries controller class.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class LoginQR extends \WC_REST_Data_Controller {

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
	protected $rest_base = 'login-qr';

	/**
	 * Register routes.
	 *
	 * @since 3.5.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/jetpack_status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'jetpack_status' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/send-magic-link',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'send_magic_link' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		parent::register_routes();
	}

	/**
	 * Get Jetpack status.
	 *
	 * @return array
	 */
	public function jetpack_status() {
		return rest_ensure_response(
			[
				'installed'      => true,
				'activated'      => true,
				'connected'      => true,
				'user_connected' => false,
			]
		);
	}

	/**
	 * Sends request to generate magic link email.
	 *
	 * @return array
	 */
	public function send_magic_link() {
		// Attempt to get email from Jetpack.
		if ( class_exists( Jetpack_Connection_Manager::class ) ) {
			$jetpack_connection_manager = new Jetpack_Connection_Manager();
			if ( $jetpack_connection_manager->is_active() ) {
				$jetpack_user = $jetpack_connection_manager->get_connected_user_data();

				$params = [
					// This is currently not sending the right magic link email.
					// Change client_id and client_secret to Woo since this is generating WordPress magic link instead.
					'client_id'     => 39911,
					'client_secret' => 'cOaYKdrkgXz8xY7aysv4fU6wL6sK5J8a6ojReEIAPwggsznj4Cb6mW0nffTxtYT8',
					'email'         => $jetpack_user['email'],
					'scheme'        => 'woocommerce',
				];

				$raw_response = wp_remote_post(
					'https://public-api.wordpress.com/rest/v1.3/auth/send-login-email',
					array(
						'timeout'     => 30,
						'redirection' => 5,
						'body'        => wp_json_encode( $params ),
					)
				);

				$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

				$response_code = wp_remote_retrieve_response_code( $raw_response );
				if ( 200 !== $response_code ) {
					return new \WP_Error( $response['error'], $response['message'] );
				}

				return rest_ensure_response( [ 'code' => 'success' ] );
			}
		}

		return new \WP_Error( 'jetpack_not_connected', __( 'Jetpack is not connected.', 'woocommerce' ) );
	}
}
