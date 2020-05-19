<?php
/**
 * REST API Plugins Controller
 *
 * Handles requests to install and activate depedent plugins.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\PluginsHelper;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Install_JP_And_WCS_Plugins;

defined( 'ABSPATH' ) || exit;

/**
 * Plugins Controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class Plugins extends \WC_REST_Data_Controller {
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
	protected $rest_base = 'plugins';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/install',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'install_plugin' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/active',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'active_plugins' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/installed',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'installed_plugins' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'activate_plugins' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/connect-jetpack',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'connect_jetpack' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_connect_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/request-wccom-connect',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'request_wccom_connect' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_connect_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/finish-wccom-connect',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'finish_wccom_connect' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_connect_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/connect-paypal',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'connect_paypal' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_connect_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/connect-wcpay',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'connect_wcpay' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_connect_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/connect-square',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'connect_square' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_connect_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to manage plugins.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot manage plugins.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Create an alert notification in response to an error installing a plugin.
	 *
	 * @todo This should be moved to a filter to make this API more generic and less plugin-specific.
	 *
	 * @param string $slug The slug of the plugin being installed.
	 */
	private function create_install_plugin_error_inbox_notification_for_jetpack_installs( $slug ) {
		WC_Admin_Notes_Install_JP_And_WCS_Plugins::possibly_add_install_jp_and_wcs_note( $slug );
	}

	/**
	 * Installs the requested plugin.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|array Plugin Status
	 */
	public function install_plugin( $request ) {
		$allowed_plugins = self::get_allowed_plugins();
		$plugin          = sanitize_title_with_dashes( $request['plugin'] );
		if ( ! in_array( $plugin, array_keys( $allowed_plugins ), true ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_plugin', __( 'Invalid plugin.', 'woocommerce' ), 404 );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$path              = $allowed_plugins[ $plugin ];
		$slug              = sanitize_key( $plugin );
		$installed_plugins = get_plugins();

		if ( in_array( $path, array_keys( $installed_plugins ), true ) ) {
			return( array(
				'slug'   => $slug,
				'name'   => $installed_plugins[ $path ]['Name'],
				'status' => 'success',
			) );
		}

		include_once ABSPATH . '/wp-admin/includes/admin.php';
		include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
		include_once ABSPATH . '/wp-admin/includes/plugin.php';
		include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			$properties = array(
				/* translators: %s: plugin slug (example: woocommerce-services) */
				'error_message' => __( 'The requested plugin `%s` could not be installed. Plugin API call failed.', 'woocommerce' ),
				'api'           => $api,
				'slug'          => $slug,
			);
			wc_admin_record_tracks_event( 'install_plugin_error', $properties );

			$this->create_install_plugin_error_inbox_notification_for_jetpack_installs( $slug );

			return new \WP_Error(
				'woocommerce_rest_plugin_install',
				sprintf(
					/* translators: %s: plugin slug (example: woocommerce-services) */
					__( 'The requested plugin `%s` could not be installed. Plugin API call failed.', 'woocommerce' ),
					$slug
				),
				500
			);
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) || is_null( $result ) ) {
			$properties = array(
				/* translators: %s: plugin slug (example: woocommerce-services) */
				'error_message' => __( 'The requested plugin `%s` could not be installed.', 'woocommerce' ),
				'slug'          => $slug,
				'api'           => $api,
				'upgrader'      => $upgrader,
				'result'        => $result,
			);
			wc_admin_record_tracks_event( 'install_plugin_error', $properties );

			$this->create_install_plugin_error_inbox_notification_for_jetpack_installs( $slug );

			return new \WP_Error(
				'woocommerce_rest_plugin_install',
				sprintf(
					/* translators: %s: plugin slug (example: woocommerce-services) */
					__( 'The requested plugin `%s` could not be installed.', 'woocommerce' ),
					$slug
				),
				500
			);
		}

		return array(
			'slug'   => $slug,
			'name'   => $api->name,
			'status' => 'success',
		);
	}

	/**
	 * Gets an array of plugins that can be installed & activated.
	 *
	 * @return array
	 */
	public static function get_allowed_plugins() {
		return apply_filters( 'woocommerce_admin_plugins_whitelist', array() );
	}

	/**
	 * Returns a list of active plugins in API format.
	 *
	 * @return array Active plugins
	 */
	public static function active_plugins() {
		$allowed = self::get_allowed_plugins();
		$plugins = array_values( array_intersect( PluginsHelper::get_active_plugin_slugs(), array_keys( $allowed ) ) );
		return( array(
			'plugins' => array_values( $plugins ),
		) );
	}
	/**
	 * Returns a list of active plugins.
	 *
	 * @return array Active plugins
	 */
	public static function get_active_plugins() {
		$data = self::active_plugins();
		return $data['plugins'];
	}

	/**
	 * Returns a list of installed plugins.
	 *
	 * @return array Installed plugins
	 */
	public function installed_plugins() {
		return( array(
			'plugins' => PluginsHelper::get_installed_plugin_slugs(),
		) );
	}

	/**
	 * Activate the requested plugin.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|array Plugin Status
	 */
	public function activate_plugins( $request ) {
		$allowed_plugins = self::get_allowed_plugins();
		$_plugins        = explode( ',', $request['plugins'] );
		$plugins         = array_intersect( array_keys( $allowed_plugins ), $_plugins );

		if ( empty( $plugins ) || ! is_array( $plugins ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_plugins', __( 'Invalid plugins.', 'woocommerce' ), 404 );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( $plugins as $plugin ) {
			$slug = $plugin;
			$path = $allowed_plugins[ $slug ];

			if ( ! PluginsHelper::is_plugin_installed( $path ) ) {
				/* translators: %s: plugin slug (example: woocommerce-services) */
				return new \WP_Error( 'woocommerce_rest_invalid_plugin', sprintf( __( 'Invalid plugin %s.', 'woocommerce' ), $slug ), 404 );
			}

			$result = activate_plugin( $path );
			if ( ! is_null( $result ) ) {
				$this->create_install_plugin_error_inbox_notification_for_jetpack_installs( $slug );

				return new \WP_Error( 'woocommerce_rest_invalid_plugin', sprintf( __( 'The requested plugins could not be activated.', 'woocommerce' ), $slug ), 500 );
			}
		}

		return( array(
			'activatedPlugins' => array_values( $plugins ),
			'active'           => self::get_active_plugins(),
			'status'           => 'success',
		) );
	}

	/**
	 * Generates a Jetpack Connect URL.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|array Connection URL for Jetpack
	 */
	public function connect_jetpack( $request ) {
		if ( ! class_exists( '\Jetpack' ) ) {
			return new \WP_Error( 'woocommerce_rest_jetpack_not_active', __( 'Jetpack is not installed or active.', 'woocommerce' ), 404 );
		}

		$redirect_url = apply_filters( 'woocommerce_admin_onboarding_jetpack_connect_redirect_url', esc_url_raw( $request['redirect_url'] ) );
		$connect_url  = \Jetpack::init()->build_connect_url( true, $redirect_url, 'woocommerce-onboarding' );

		$calypso_env = defined( 'WOOCOMMERCE_CALYPSO_ENVIRONMENT' ) && in_array( WOOCOMMERCE_CALYPSO_ENVIRONMENT, array( 'development', 'wpcalypso', 'horizon', 'stage' ), true ) ? WOOCOMMERCE_CALYPSO_ENVIRONMENT : 'production';
		$connect_url = add_query_arg( array( 'calypso_env' => $calypso_env ), $connect_url );

		return( array(
			'slug'          => 'jetpack',
			'name'          => __( 'Jetpack', 'woocommerce' ),
			'connectAction' => $connect_url,
		) );
	}

	/**
	 *  Kicks off the WCCOM Connect process.
	 *
	 * @return WP_Error|array Connection URL for WooCommerce.com
	 */
	public function request_wccom_connect() {
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php';
		if ( ! class_exists( 'WC_Helper_API' ) ) {
			return new \WP_Error( 'woocommerce_rest_helper_not_active', __( 'There was an error loading the WooCommerce.com Helper API.', 'woocommerce' ), 404 );
		}

		$redirect_uri = wc_admin_url( '&task=connect&wccom-connected=1' );

		$request = \WC_Helper_API::post(
			'oauth/request_token',
			array(
				'body' => array(
					'home_url'     => home_url(),
					'redirect_uri' => $redirect_uri,
				),
			)
		);

		$code = wp_remote_retrieve_response_code( $request );
		if ( 200 !== $code ) {
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce' ), 500 );
		}

		$secret = json_decode( wp_remote_retrieve_body( $request ) );
		if ( empty( $secret ) ) {
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce' ), 500 );
		}

		do_action( 'woocommerce_helper_connect_start' );

		$connect_url = add_query_arg(
			array(
				'home_url'     => rawurlencode( home_url() ),
				'redirect_uri' => rawurlencode( $redirect_uri ),
				'secret'       => rawurlencode( $secret ),
				'wccom-from'   => 'onboarding',
			),
			\WC_Helper_API::url( 'oauth/authorize' )
		);

		if ( defined( 'WOOCOMMERCE_CALYPSO_ENVIRONMENT' ) && in_array( WOOCOMMERCE_CALYPSO_ENVIRONMENT, array( 'development', 'wpcalypso', 'horizon', 'stage' ), true ) ) {
			$connect_url = add_query_arg(
				array(
					'calypso_env' => WOOCOMMERCE_CALYPSO_ENVIRONMENT,
				),
				$connect_url
			);
		}

		return( array(
			'connectAction' => $connect_url,
		) );
	}

	/**
	 * Finishes connecting to WooCommerce.com.
	 *
	 * @param  object $rest_request Request details.
	 * @return WP_Error|array Contains success status.
	 */
	public function finish_wccom_connect( $rest_request ) {
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php';
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php';
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-updater.php';
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-options.php';
		if ( ! class_exists( 'WC_Helper_API' ) ) {
			return new \WP_Error( 'woocommerce_rest_helper_not_active', __( 'There was an error loading the WooCommerce.com Helper API.', 'woocommerce' ), 404 );
		}

		// Obtain an access token.
		$request = \WC_Helper_API::post(
			'oauth/access_token',
			array(
				'body' => array(
					'request_token' => wp_unslash( $rest_request['request_token'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'home_url'      => home_url(),
				),
			)
		);

		$code = wp_remote_retrieve_response_code( $request );
		if ( 200 !== $code ) {
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce' ), 500 );
		}

		$access_token = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! $access_token ) {
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce' ), 500 );
		}

		\WC_Helper_Options::update(
			'auth',
			array(
				'access_token'        => $access_token['access_token'],
				'access_token_secret' => $access_token['access_token_secret'],
				'site_id'             => $access_token['site_id'],
				'user_id'             => get_current_user_id(),
				'updated'             => time(),
			)
		);

		if ( ! \WC_Helper::_flush_authentication_cache() ) {
			\WC_Helper_Options::update( 'auth', array() );
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce' ), 500 );
		}

		delete_transient( '_woocommerce_helper_subscriptions' );
		\WC_Helper_Updater::flush_updates_cache();

		do_action( 'woocommerce_helper_connected' );

		return array(
			'success' => true,
		);
	}

	/**
	 * Returns a URL that can be used to connect to PayPal.
	 *
	 * @return WP_Error|array Connect URL.
	 */
	public function connect_paypal() {
		if ( ! function_exists( 'wc_gateway_ppec' ) ) {
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to PayPal.', 'woocommerce' ), 500 );
		}

		$redirect_url = add_query_arg(
			array(
				'env'                     => 'live',
				'wc_ppec_ips_admin_nonce' => wp_create_nonce( 'wc_ppec_ips' ),
			),
			wc_admin_url( '&task=payments&method=paypal&paypal-connect-finish=1' )
		);

		// https://github.com/woocommerce/woocommerce-gateway-paypal-express-checkout/blob/b6df13ba035038aac5024d501e8099a37e13d6cf/includes/class-wc-gateway-ppec-ips-handler.php#L79-L93.
		$query_args  = array(
			'redirect'    => rawurlencode( $redirect_url ),
			'countryCode' => WC()->countries->get_base_country(),
			'merchantId'  => md5( site_url( '/' ) . time() ),
		);
		$connect_url = add_query_arg( $query_args, wc_gateway_ppec()->ips->get_middleware_login_url( 'live' ) );

		return( array(
			'connectUrl' => $connect_url,
		) );
	}

	/**
	 * Returns a URL that can be used to connect to Square.
	 *
	 * @return WP_Error|array Connect URL.
	 */
	public function connect_square() {
		if ( ! class_exists( '\WooCommerce\Square\Handlers\Connection' ) ) {
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to Square.', 'woocommerce' ), 500 );
		}

		if ( 'US' === WC()->countries->get_base_country() ) {
			$profile = get_option( Onboarding::PROFILE_DATA_OPTION, array() );
			if ( ! empty( $profile['industry'] ) ) {
				$has_cbd_industry = in_array( 'cbd-other-hemp-derived-products', array_column( $profile['industry'], 'slug' ), true );
			}
		}

		if ( $has_cbd_industry ) {
			$url = 'https://squareup.com/t/f_partnerships/d_referrals/p_woocommerce/c_general/o_none/l_us/dt_alldevice/pr_payments/?route=/solutions/cbd';
		} else {
			$url = \WooCommerce\Square\Handlers\Connection::CONNECT_URL_PRODUCTION;
		}

		$redirect_url = wp_nonce_url( wc_admin_url( '&task=payments&method=square&square-connect-finish=1' ), 'wc_square_connected' );
		$args         = array(
			'redirect' => rawurlencode( rawurlencode( $redirect_url ) ),
			'scopes'   => implode(
				',',
				array(
					'MERCHANT_PROFILE_READ',
					'PAYMENTS_READ',
					'PAYMENTS_WRITE',
					'ORDERS_READ',
					'ORDERS_WRITE',
					'CUSTOMERS_READ',
					'CUSTOMERS_WRITE',
					'SETTLEMENTS_READ',
					'ITEMS_READ',
					'ITEMS_WRITE',
					'INVENTORY_READ',
					'INVENTORY_WRITE',
				)
			),
		);

		$connect_url = add_query_arg( $args, $url );

		return( array(
			'connectUrl' => $connect_url,
		) );
	}

	/**
	 * Returns a URL that can be used to by WCPay to verify business details with Stripe.
	 *
	 * @return WP_Error|array Connect URL.
	 */
	public function connect_wcpay() {
		if ( ! class_exists( 'WC_Payments_Account' ) ) {
			return new \WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error communicating with the WooCommerce Payments plugin.', 'woocommerce' ), 500 );
		}

		$connect_url = add_query_arg(
			array(
				'wcpay-connect' => 'WCADMIN_PAYMENT_TASK',
				'_wpnonce'      => wp_create_nonce( 'wcpay-connect' ),
			),
			admin_url()
		);

		return( array(
			'connectUrl' => $connect_url,
		) );
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'plugins',
			'type'       => 'object',
			'properties' => array(
				'slug'   => array(
					'description' => __( 'Plugin slug.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'   => array(
					'description' => __( 'Plugin name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status' => array(
					'description' => __( 'Plugin status.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_connect_schema() {
		$schema = $this->get_item_schema();
		unset( $schema['properties']['status'] );
		$schema['properties']['connectAction'] = array(
			'description' => __( 'Action that should be completed to connect Jetpack.', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);
		return $schema;
	}
}
