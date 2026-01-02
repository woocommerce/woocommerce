<?php
/**
 * WooCommerce Jetpack Integration
 *
 * Provides centralized access to Jetpack connection functionality.
 *
 * @package WooCommerce\Admin\Helper
 * @since   10.5.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Client;
use Automattic\Jetpack\Connection\Manager;

/**
 * WC_Jetpack class.
 *
 * Manages Jetpack connection for WooCommerce, providing methods to check
 * connection status and make authenticated requests to WordPress.com.
 *
 * @since 10.5.0
 */
class WC_Jetpack {

	/**
	 * Plugin slug used for Jetpack connection.
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'woocommerce';

	/**
	 * Singleton instance.
	 *
	 * @var WC_Jetpack|null
	 */
	private static ?WC_Jetpack $instance = null;

	/**
	 * Jetpack connection manager.
	 *
	 * @var Manager
	 */
	private Manager $connection_manager;

	/**
	 * Get the singleton instance.
	 *
	 * @since 10.5.0
	 *
	 * @return WC_Jetpack
	 */
	public static function instance(): WC_Jetpack {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 10.5.0
	 */
	private function __construct() {
		$this->connection_manager = new Manager( self::PLUGIN_SLUG );
	}

	/**
	 * Checks if the site is connected to WordPress.com.
	 *
	 * A site is considered connected when it has a blog token.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if connected.
	 */
	public function is_site_connected(): bool {
		return $this->connection_manager->is_connected();
	}

	/**
	 * Checks if the site has a connected owner.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if the site has a connected owner.
	 */
	public function has_connected_owner(): bool {
		return $this->connection_manager->has_connected_owner();
	}

	/**
	 * Checks if the site has a working connection to WordPress.com.
	 *
	 * A working connection requires both:
	 * - The site to be connected (has blog token)
	 * - A connected owner user
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if the connection is fully working.
	 */
	public function is_connected(): bool {
		return $this->is_site_connected() && $this->has_connected_owner();
	}

	/**
	 * Checks if the current user is connected to WordPress.com.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if the current user is connected.
	 */
	public function is_user_connected(): bool {
		return $this->connection_manager->is_user_connected();
	}

	/**
	 * Checks if the current user is the connection owner.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if the current user is the connection owner.
	 */
	public function is_connection_owner(): bool {
		return $this->connection_manager->is_connection_owner();
	}

	/**
	 * Gets the connection owner user ID.
	 *
	 * @since 10.5.0
	 *
	 * @return int|false The connection owner user ID or false if none.
	 */
	public function get_connection_owner_id() {
		return $this->connection_manager->get_connection_owner_id();
	}

	/**
	 * Gets the current WordPress.com blog ID.
	 *
	 * @since 10.5.0
	 *
	 * @return int|false The blog ID or false if not connected.
	 */
	public function get_blog_id() {
		return Jetpack_Options::get_option( 'id' );
	}

	/**
	 * Attempts to register the site with WordPress.com.
	 *
	 * This is a prerequisite for establishing a connection.
	 *
	 * @since 10.5.0
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function try_registration() {
		return $this->connection_manager->try_registration();
	}

	/**
	 * Gets the authorization URL for connecting to WordPress.com.
	 *
	 * @since 10.5.0
	 *
	 * @param string $redirect_url The URL to redirect to after authorization.
	 *
	 * @return string The authorization URL.
	 */
	public function get_authorization_url( string $redirect_url = '' ): string {
		return $this->connection_manager->get_authorization_url( null, $redirect_url );
	}

	/**
	 * Sends a remote request through the Jetpack connection.
	 *
	 * @since 10.5.0
	 *
	 * @param array       $args           Request arguments for Jetpack Client.
	 * @param string|null $body           Optional request body.
	 * @param bool        $use_user_token Whether to use the user token instead of blog token.
	 *
	 * @return array|WP_Error HTTP response on success, WP_Error on failure.
	 */
	public function remote_request( array $args, ?string $body = null, bool $use_user_token = false ) {
		if ( ! $this->is_connected() ) {
			return new WP_Error(
				'woocommerce_jetpack_not_connected',
				__( 'Site is not connected to WordPress.com', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$args['blog_id'] = $this->get_blog_id();

		if ( $use_user_token ) {
			$args['user_id'] = $this->get_connection_owner_id();
		}

		return Client::remote_request( $args, $body );
	}

	/**
	 * Sends a JSON request to a WordPress.com API endpoint.
	 *
	 * @since 10.5.0
	 *
	 * @param string $method    HTTP method (GET, POST, PUT, DELETE).
	 * @param string $endpoint  The API endpoint (e.g., '/wpcom/v2/sites/%d/some-endpoint').
	 * @param array  $body      Optional request body data (will be JSON encoded for non-GET requests).
	 * @param bool   $use_user_token Whether to use the user token instead of blog token.
	 *
	 * @return array|WP_Error Response body as array on success, WP_Error on failure.
	 */
	public function request( string $method, string $endpoint, array $body = array(), bool $use_user_token = false ) {
		$blog_id = $this->get_blog_id();
		if ( ! $blog_id ) {
			return new WP_Error(
				'woocommerce_jetpack_no_blog_id',
				__( 'Could not determine WordPress.com blog ID', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$url = sprintf( $endpoint, $blog_id );

		$args = array(
			'url'    => $url,
			'method' => strtoupper( $method ),
		);

		$request_body = null;
		if ( ! empty( $body ) ) {
			if ( 'GET' === $args['method'] ) {
				$args['url'] = add_query_arg( $body, $args['url'] );
			} else {
				$request_body    = wp_json_encode( $body );
				$args['headers'] = array( 'Content-Type' => 'application/json' );
			}
		}

		$response = $this->remote_request( $args, $request_body, $use_user_token );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 400 ) {
			$error_data = json_decode( $response_body, true );
			return new WP_Error(
				$error_data['code'] ?? 'woocommerce_jetpack_request_failed',
				$error_data['message'] ?? __( 'Request to WordPress.com failed', 'woocommerce' ),
				array( 'status' => $response_code )
			);
		}

		return json_decode( $response_body, true ) ?? array();
	}

	/**
	 * Gets the Jetpack connection manager instance.
	 *
	 * Use this for advanced operations not covered by this class.
	 *
	 * @since 10.5.0
	 *
	 * @return Manager The Jetpack connection manager.
	 */
	public function get_connection_manager(): Manager {
		return $this->connection_manager;
	}
}
