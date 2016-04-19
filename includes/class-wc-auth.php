<?php
/**
 * WooCommerce Auth
 *
 * Handles wc-auth endpoint requests.
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
	 * Version.
	 *
	 * @var int
	 */
	const VERSION = 1;

	/**
	 * Setup class.
	 *
	 * @since 2.4.0
	 */
	public function __construct() {
		// Add query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Register auth endpoint
		add_action( 'init', array( __CLASS__, 'add_endpoint' ), 0 );

		// Handle auth requests
		add_action( 'parse_request', array( $this, 'handle_auth_requests' ), 0 );
	}

	/**
	 * Add query vars.
	 *
	 * @since  2.4.0
	 *
	 * @param  array $vars
	 *
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wc-auth-version';
		$vars[] = 'wc-auth-route';
		return $vars;
	}

	/**
	 * Add auth endpoint.
	 *
	 * @since 2.4.0
	 */
	public static function add_endpoint() {
		add_rewrite_rule( '^wc-auth/v([1]{1})/(.*)?', 'index.php?wc-auth-version=$matches[1]&wc-auth-route=$matches[2]', 'top' );
	}

	/**
	 * Get scope name.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $scope
	 *
	 * @return string
	 */
	protected function get_i18n_scope( $scope ) {
		$permissions = array(
			'read'       => __( 'Read', 'woocommerce' ),
			'write'      => __( 'Write', 'woocommerce' ),
			'read_write' => __( 'Read/Write', 'woocommerce' ),
		);

		return $permissions[ $scope ];
	}

	/**
	 * Return a list of permissions a scope allows.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $scope
	 *
	 * @return array
	 */
	protected function get_permissions_in_scope( $scope ) {
		$permissions = array();
		switch ( $scope )  {
			case 'read' :
				$permissions[] = __( 'View coupons', 'woocommerce' );
				$permissions[] = __( 'View customers', 'woocommerce' );
				$permissions[] = __( 'View orders and sales reports', 'woocommerce' );
				$permissions[] = __( 'View products', 'woocommerce' );
			break;
			case 'write' :
				$permissions[] = __( 'Create webhooks', 'woocommerce' );
				$permissions[] = __( 'Create coupons', 'woocommerce' );
				$permissions[] = __( 'Create customers', 'woocommerce' );
				$permissions[] = __( 'Create orders', 'woocommerce' );
				$permissions[] = __( 'Create products', 'woocommerce' );
			break;
			case 'read_write' :
				$permissions[] = __( 'Create webhooks', 'woocommerce' );
				$permissions[] = __( 'View and manage coupons', 'woocommerce' );
				$permissions[] = __( 'View and manage customers', 'woocommerce' );
				$permissions[] = __( 'View and manage orders and sales reports', 'woocommerce' );
				$permissions[] = __( 'View and manage products', 'woocommerce' );
			break;
		}
		return apply_filters( 'woocommerce_api_permissions_in_scope', $permissions, $scope );
	}

	/**
	 * Build auth urls.
	 *
	 * @since  2.4.0
	 *
	 * @param  array $data
	 * @param  string $endpoint
	 *
	 * @return string
	 */
	protected function build_url( $data, $endpoint ) {
		$url = wc_get_endpoint_url( 'wc-auth/v' . self::VERSION, $endpoint, home_url( '/' ) );

		return add_query_arg( array(
			'app_name'            => wc_clean( $data['app_name'] ),
			'user_id'             => wc_clean( $data['user_id'] ),
			'return_url'          => urlencode( $this->get_formatted_url( $data['return_url'] ) ),
			'callback_url'        => urlencode( $this->get_formatted_url( $data['callback_url'] ) ),
			'scope'               => wc_clean( $data['scope'] ),
		), $url );
	}

	/**
	 * Decode and format a URL.
	 * @param  string $url
	 * @return array
	 */
	protected function get_formatted_url( $url ) {
		$url = urldecode( $url );

		if ( ! strstr( $url, '://' ) ) {
			$url = 'https://' . $url;
		}

		return $url;
	}

	/**
	 * Make validation.
	 *
	 * @since  2.4.0
	 */
	protected function make_validation() {
		$params = array(
			'app_name',
			'user_id',
			'return_url',
			'callback_url',
			'scope'
		);

		foreach ( $params as $param ) {
			if ( empty( $_REQUEST[ $param ] ) ) {
				throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), $param ) );
			}
		}

		if ( ! in_array( $_REQUEST['scope'], array( 'read', 'write', 'read_write' ) ) ) {
			throw new Exception( sprintf( __( 'Invalid scope %s', 'woocommerce' ), wc_clean( $_REQUEST['scope'] ) ) );
		}

		foreach ( array( 'return_url', 'callback_url' ) as $param ) {
			$param = $this->get_formatted_url( $_REQUEST[ $param ] );

			if ( false === filter_var( $param, FILTER_VALIDATE_URL ) ) {
				throw new Exception( sprintf( __( 'The %s is not a valid URL', 'woocommerce' ), $param ) );
			}
		}

		$callback_url = $this->get_formatted_url( $_REQUEST['callback_url'] );

		if ( 0 !== stripos( $callback_url, 'https://' ) ) {
			throw new Exception( __( 'The callback_url need to be over SSL', 'woocommerce' ) );
		}
	}

	/**
	 * Create keys.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $app_name
	 * @param  string $app_user_id
	 * @param  string $scope
	 *
	 * @return array
	 */
	protected function create_keys( $app_name, $app_user_id, $scope ) {
		global $wpdb;

		$description = sprintf( __( '%s - API %s (created on %s at %s).', 'woocommerce' ), wc_clean( $app_name ), $this->get_i18n_scope( $scope ), date_i18n( wc_date_format() ), date_i18n( wc_time_format() ) );
		$user        = wp_get_current_user();

		// Created API keys.
		$permissions     = ( in_array( $scope, array( 'read', 'write', 'read_write' ) ) ) ? sanitize_text_field( $scope ) : 'read';
		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $user->ID,
				'description'     => $description,
				'permissions'     => $permissions,
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 )
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);

		return array(
			'key_id'          => $wpdb->insert_id,
			'user_id'         => $app_user_id,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'key_permissions' => $permissions
		);
	}

	/**
	 * Post consumer data.
	 *
	 * @since  2.4.0
	 *
	 * @param  array  $consumer_data
	 * @param  string $url
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function post_consumer_data( $consumer_data, $url ) {
		$params = array(
			'body'      => json_encode( $consumer_data ),
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type' => 'application/json;charset=' . get_bloginfo( 'charset' ),
			)
		);

		$response = wp_safe_remote_post( esc_url_raw( $url ), $params );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		} else if ( 200 != $response['response']['code'] ) {
			throw new Exception( __( 'An error occurred in the request and at the time were unable to send the consumer data', 'woocommerce' ) );
		}

		return true;
	}

	/**
	 * Handle auth requests.
	 *
	 * @since 2.4.0
	 */
	public function handle_auth_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-auth-version'] ) ) {
			$wp->query_vars['wc-auth-version'] = $_GET['wc-auth-version'];
		}

		if ( ! empty( $_GET['wc-auth-route'] ) ) {
			$wp->query_vars['wc-auth-route'] = $_GET['wc-auth-route'];
		}

		// wc-auth endpoint requests
		if ( ! empty( $wp->query_vars['wc-auth-version'] ) && ! empty( $wp->query_vars['wc-auth-route'] ) ) {
			$this->auth_endpoint( $wp->query_vars['wc-auth-route'] );
		}
	}

	/**
	 * Auth endpoint.
	 *
	 * @since 2.4.0
	 *
	 * @param string $route
	 */
	protected function auth_endpoint( $route ) {
		ob_start();

		$consumer_data = array();

		try {
			if ( 'yes' !== get_option( 'woocommerce_api_enabled' ) ) {
				throw new Exception( __( 'API disabled!', 'woocommerce' ) );
			}

			$route = strtolower( wc_clean( $route ) );
			$this->make_validation();

			// Login endpoint
			if ( 'login' == $route && ! is_user_logged_in() ) {
				wc_get_template( 'auth/form-login.php', array(
					'app_name'     => $_REQUEST['app_name'],
					'return_url'   => add_query_arg( array( 'success' => 0, 'user_id' => wc_clean( $_REQUEST['user_id'] ) ), $this->get_formatted_url( $_REQUEST['return_url'] ) ),
					'redirect_url' => $this->build_url( $_REQUEST, 'authorize' ),
				) );

				exit;

			// Redirect with user is logged in
			} else if ( 'login' == $route && is_user_logged_in() ) {
				wp_redirect( esc_url_raw( $this->build_url( $_REQUEST, 'authorize' ) ) );
				exit;

			// Redirect with user is not logged in and trying to access the authorize endpoint
			} else if ( 'authorize' == $route && ! is_user_logged_in() ) {
				wp_redirect( esc_url_raw( $this->build_url( $_REQUEST, 'login' ) ) );
				exit;

			// Authorize endpoint
			} else if ( 'authorize' == $route && current_user_can( 'manage_woocommerce' ) ) {
				wc_get_template( 'auth/form-grant-access.php', array(
					'app_name'    => $_REQUEST['app_name'],
					'return_url'  => add_query_arg( array( 'success' => 0, 'user_id' => wc_clean( $_REQUEST['user_id'] ) ), $this->get_formatted_url( $_REQUEST['return_url'] ) ),
					'scope'       => $this->get_i18n_scope( wc_clean( $_REQUEST['scope'] ) ),
					'permissions' => $this->get_permissions_in_scope( wc_clean( $_REQUEST['scope'] ) ),
					'granted_url' => wp_nonce_url( $this->build_url( $_REQUEST, 'access_granted' ), 'wc_auth_grant_access', 'wc_auth_nonce' ),
					'logout_url'  => wp_logout_url( $this->build_url( $_REQUEST, 'login' ) ),
					'user'        => wp_get_current_user()
				) );
				exit;

			// Granted access endpoint
			} else if ( 'access_granted' == $route && current_user_can( 'manage_woocommerce' ) ) {
				if ( ! isset( $_GET['wc_auth_nonce'] ) || ! wp_verify_nonce( $_GET['wc_auth_nonce'], 'wc_auth_grant_access' ) ) {
					throw new Exception( __( 'Invalid nonce verification', 'woocommerce' ) );
				}

				$consumer_data = $this->create_keys( $_REQUEST['app_name'], $_REQUEST['user_id'], $_REQUEST['scope'] );
				$response      = $this->post_consumer_data( $consumer_data, $this->get_formatted_url( $_REQUEST['callback_url'] ) );

				if ( $response ) {
					wp_redirect( esc_url_raw( add_query_arg( array( 'success' => 1, 'user_id' => wc_clean( $_REQUEST['user_id'] ) ), $this->get_formatted_url( $_REQUEST['return_url'] ) ) ) );
					exit;
				}
			} else {
				throw new Exception( __( 'You do not have permissions to access this page!', 'woocommerce' ) );
			}
		} catch ( Exception $e ) {
			$this->maybe_delete_key( $consumer_data );

			wp_die( sprintf( __( 'Error: %s', 'woocommerce' ), $e->getMessage() ), __( 'Access Denied', 'woocommerce' ), array( 'response' => 401 ) );
		}
	}

	/**
	 * Maybe delete key.
	 *
	 * @since 2.4.0
	 *
	 * @param array $key
	 */
	private function maybe_delete_key( $key ) {
		global $wpdb;

		if ( isset( $key['key_id'] ) ) {
			$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key['key_id'] ), array( '%d' ) );
		}
	}
}

endif;

return new WC_Auth();
