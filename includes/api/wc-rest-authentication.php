<?php
/**
 * REST API Authentication
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_REST_Authentication {

	/**
	 * Initialize authentication actions.
	 */
	public function __construct() {
		add_filter( 'determine_current_user', array( $this, 'authenticate' ), 30 );
		add_filter( 'rest_authentication_errors', array( $this, 'check_authentication_error' ) );
	}

	/**
	 * Authenticate user.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public function authenticate( $user_id ) {
		if ( is_ssl() ) {
			if ( $new_user_id = $this->perform_basic_authentication() ) {
				$user_id = $new_user_id;
			}
		}

		return $user_id;
	}

	/**
	 * Check for authentication error.
	 *
	 * @param WP_Error|null|bool $error
	 * @return WP_Error|null|bool
	 */
	public function check_authentication_error( $error ) {
		global $wc_rest_authentication_error;

		// Passthrough other errors.
		if ( ! empty( $error ) ) {
			return $error;
		}

		return $wc_rest_authentication_error;
	}

	/**
	 * Basic Authentication.
	 *
	 * SSL-encrypted requests are not subject to sniffing or man-in-the-middle
	 * attacks, so the request can be authenticated by simply looking up the user
	 * associated with the given consumer key and confirming the consumer secret
	 * provided is valid.
	 *
	 * @return int
	 */
	private function perform_basic_authentication() {
		global $wc_rest_authentication_error;

		$user            = null;
		$user_id         = 0;
		$consumer_key    = '';
		$consumer_secret = '';

		// If the $_GET parameters are present, use those first.
		if ( ! empty( $_GET['consumer_key'] ) && ! empty( $_GET['consumer_secret'] ) ) {
			$consumer_key    = $_GET['consumer_key'];
			$consumer_secret = $_GET['consumer_secret'];
		}

		// If the above is not present, we will do full basic auth.
		if ( ! $consumer_key && ! empty( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_SERVER['PHP_AUTH_PW'] ) ) {
			$consumer_key    = $_SERVER['PHP_AUTH_USER'];
			$consumer_secret = $_SERVER['PHP_AUTH_PW'];
		}

		// Get user data.
		if ( $consumer_key && $consumer_secret ) {
			$user    = $this->get_user_data_by_consumer_key( $consumer_key );
			$user_id = $user->user_id;
		}

		// Abort if don't have an user at this point.
		if ( empty( $user ) ) {
			return $user_id;
		}

		// Validate user secret.
		if ( ! hash_equals( $user->consumer_secret, $consumer_secret ) ) {
			$wc_rest_authentication_error = new WP_Error( 'woocommerce_rest_authentication_error', __( 'Consumer Secret is invalid', 'woocommerce' ), array( 'status' => 401 ) );
			$user_id = 0;
		}

		if ( ! $this->check_permissions( $user->permissions ) ) {
			$user_id = 0;
		}

		return $user_id;
	}

	/**
	 * Return the user data for the given consumer_key.
	 *
	 * @param string $consumer_key
	 * @return array
	 */
	private function get_user_data_by_consumer_key( $consumer_key ) {
		global $wpdb;

		$consumer_key = wc_api_hash( sanitize_text_field( $consumer_key ) );
		$user         = $wpdb->get_row( $wpdb->prepare( "
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = '%s'
		", $consumer_key ) );

		return $user;
	}

	/**
	 * Check that the API keys provided have the proper key-specific permissions to either read or write API resources.
	 *
	 * @param string $permissions
	 * @return bool
	 */
	private function check_permissions( $permissions ) {
		global $wc_rest_authentication_error;

		$valid = true;

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		switch ( $_SERVER['REQUEST_METHOD'] ) {

			case 'HEAD':
			case 'GET':
				if ( 'read' !== $permissions && 'read_write' !== $permissions ) {
					$wc_rest_authentication_error = new WP_Error( 'woocommerce_rest_authentication_error', __( 'The API key provided does not have read permissions', 'woocommerce' ), array( 'status' => 401 ) );
					$valid = false;
				}
				break;

			case 'POST':
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
				if ( 'write' !== $permissions && 'read_write' !== $permissions ) {
					$wc_rest_authentication_error = new WP_Error( 'woocommerce_rest_authentication_error', __( 'The API key provided does not have write permissions', 'woocommerce' ), array( 'status' => 401 ) );
					$valid = false;
				}
				break;
		}

		return $valid;
	}
}

new WC_REST_Authentication();
