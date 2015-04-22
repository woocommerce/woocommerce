<?php
/**
 * WooCommerce Auth
 *
 * Handles wc-auth endpoint requests
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Auth' ) ) :

class WC_Auth {

	/**
	 * Version
	 */
	const VERSION = 1;

	/**
	 * Setup class
	 *
	 * @since 2.4.0
	 */
	public function __construct() {
		// Register auth endpoint
		add_action( 'init', array( __CLASS__, 'add_endpoint' ), 0 );

		// Handle auth requests
		add_action( 'parse_request', array( $this, 'handle_auth_requests' ), 0 );
	}

	/**
	 * Add auth endpoint
	 *
	 * @since 2.4.0
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( 'wc-auth', EP_ROOT );
	}

	/**
	 * Get permission type name.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $type
	 *
	 * @return string
	 */
	protected function get_i18n_permission_type( $type ) {
		$permissions = array(
			'read'       => __( 'Read', 'woocommerce' ),
			'write'      => __( 'Write', 'woocommerce' ),
			'read_write' => __( 'Read/Write', 'woocommerce' ),
		);

		return $permissions[ $type ];
	}

	/**
	 * Build auth urls
	 *
	 * @since  2.4.0
	 *
	 * @param  array $data
	 * @param  string $endpoint
	 *
	 * @return string
	 */
	protected function build_url( $data, $endpoint ) {
		$url = wc_get_endpoint_url( 'wc-auth', $endpoint, get_home_url( '/' ) );

		return add_query_arg( array(
			'app_name'        => wc_clean( $data['app_name'] ),
			'return_url'      => urlencode( $data['return_url'] ),
			'callback_url'    => urlencode( $data['callback_url'] ),
			'permission_type' => wc_clean( $data['permission_type'] ),
		), $url );
	}

	/**
	 * Make validation
	 */
	protected function make_validation() {
		if ( empty( $_REQUEST['app_name'] ) ) {
			throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'app_name' ) );
		}

		if ( empty( $_REQUEST['return_url'] ) ) {
			throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'return_url' ) );
		}

		if ( false === filter_var( urldecode( $_REQUEST['return_url'] ), FILTER_VALIDATE_URL ) ) {
			throw new Exception( __( 'The return_url is not a valid URL', 'woocommerce' ) );
		}

		if ( empty( $_REQUEST['callback_url'] ) ) {
			throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'callback_url' ) );
		}

		if ( 0 !== stripos( urldecode( $_REQUEST['callback_url'] ), 'https://' ) ) {
			throw new Exception( __( 'The callback_url need to be over SSL', 'woocommerce' ) );
		}

		if ( empty( $_REQUEST['permission_type'] ) ) {
			throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'permission_type' ) );
		}

		if ( ! in_array( $_REQUEST['permission_type'], array( 'read', 'write', 'read_write' ) ) ) {
			throw new Exception( sprintf( __( 'Invalid permission_type %s', 'woocommerce' ), wc_clean( $_REQUEST['permission_type'] ) ) );
		}
	}

	/**
	 * Handle auth requests
	 *
	 * @since 2.4.0
	 */
	public function handle_auth_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-auth'] ) ) {
			$wp->query_vars['wc-auth'] = $_GET['wc-auth'];
		}

		// wc-auth endpoint requests
		if ( ! empty( $wp->query_vars['wc-auth'] ) ) {
			ob_start();

			try {
				$method = strtolower( wc_clean( $wp->query_vars['wc-auth'] ) );

				$this->make_validation();

				// Login endpoint
				if ( 'login' == $method && ! is_user_logged_in() ) {
					wc_get_template( 'auth/form-login.php', array(
						'app_name'     => $_REQUEST['app_name'],
						'return_url'   => $_REQUEST['return_url'],
						'redirect_url' => $this->build_url( $_REQUEST, 'login' ),
					) );

					exit;

				// Grant access endpoint
				} else if ( ( 'grant_access' == $method && current_user_can( 'manage_woocommerce' ) ) || ( 'login' == $method && is_user_logged_in() ) ) {
					wc_get_template( 'auth/form-grant-access.php', array(
						'app_name'        => $_REQUEST['app_name'],
						'return_url'      => $_REQUEST['return_url'],
						'permission_type' => $this->get_i18n_permission_type( wc_clean( $_REQUEST['permission_type'] ) ),
						'granted_url'     => wp_nonce_url( $this->build_url( $_REQUEST, 'granted_access' ), 'wc_auth_grant_access', 'wc_auth_nonce' ),
						'logout_url'      => wp_logout_url( $this->build_url( $_REQUEST, 'login' ) )
					) );

					exit;

				// Granted access endpoint
				} else if ( 'granted_access' == $method && current_user_can( 'manage_woocommerce' ) ) {
					echo '@TODO';

					exit;
				}

				wp_die( __( 'You do not have permissions to access this page!' ), __( 'Access Denied', 'woocommerce' ), array( 'response' => 401 ) );
			} catch ( Exception $e ) {
				wp_die( sprintf( __( 'Error: %s', 'woocommerce' ), $e->getMessage() ), __( 'Access Denied', 'woocommerce' ), array( 'response' => 401 ) );
			}
		}
	}
}

endif;

return new WC_Auth();
