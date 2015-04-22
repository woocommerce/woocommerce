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
	 * [build_url description]
	 * @param  [type] $data [description]
	 * @param  [type] $url  [description]
	 * @return [type]       [description]
	 */
	protected function build_url( $data, $url ) {
		return add_query_arg( array(
			'app_name'        => wc_clean( $data['app_name'] ),
			'return_url'      => urlencode( $data['return_url'] ),
			'permission_type' => wc_clean( $data['permission_type'] ),
		), $url );
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

				if ( empty( $_REQUEST['app_name'] ) ) {
					throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'app_name' ) );
				}

				if ( empty( $_REQUEST['return_url'] ) ) {
					throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'return_url' ) );
				}

				if ( empty( $_REQUEST['permission_type'] ) ) {
					throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'permission_type' ) );
				}

				if ( is_user_logged_in() ) {
					$method = 'grant_access';
				}

				$params = array(
					'app_name'        => $_REQUEST['app_name'],
					'return_url'      => $_REQUEST['return_url'],
					'permission_type' => $_REQUEST['permission_type'],
					'redirect_url'    => $this->build_url( $_REQUEST, wc_get_endpoint_url( 'wc-auth', 'grant_access', get_home_url( '/' ) ) ),
					'logout_url'      => wp_logout_url( $this->build_url( $_REQUEST, wc_get_endpoint_url( 'wc-auth', 'login', get_home_url( '/' ) ) ) )
				);

				if ( 'login' == $method && ! is_user_logged_in() ) { // Login endpoint
					wc_get_template( 'auth/form-login.php', $params );

					exit;
				} else if ( 'grant_access' == $method && current_user_can( 'manage_woocommerce' ) ) {
					wc_get_template( 'auth/form-grant-access.php', $params );

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
