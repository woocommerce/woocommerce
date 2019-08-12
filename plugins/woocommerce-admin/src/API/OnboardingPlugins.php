<?php
/**
 * REST API Onboarding Plugins Controller
 *
 * Handles requests to install and activate depedent plugins.
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Plugins Controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class OnboardingPlugins extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding/plugins';

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
			'/' . $this->rest_base . '/activate',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'activate_plugin' ),
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
	}

	/**
	 * Check if a given request has access to manage plugins.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot manage plugins.', 'woocommerce-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Get an array of plugins that can be installed & activated via the endpoints.
	 */
	public function get_allowed_plugins() {
		return apply_filters(
			'woocommerce_onboarding_plugins_whitelist',
			array(
				'jetpack'              => 'jetpack/jetpack.php',
				'woocommerce-services' => 'woocommerce-services/woocommerce-services.php',
			)
		);
	}

	/**
	 * Installs the requested plugin.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array Plugin Status
	 */
	public function install_plugin( $request ) {
		$allowed_plugins = $this->get_allowed_plugins();
		$plugin          = sanitize_title_with_dashes( $request['plugin'] );
		if ( ! in_array( $plugin, array_keys( $allowed_plugins ), true ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_plugin', __( 'Invalid plugin.', 'woocommerce-admin' ), 404 );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$slug              = $plugin;
		$path              = $allowed_plugins[ $slug ];
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
				'slug'   => sanitize_key( $slug ),
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return new \WP_Error( 'woocommerce_rest_plugin_install', __( 'The requested plugin could not be installed.', 'woocommerce-admin' ), 500 );
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) || is_null( $result ) ) {
			return new \WP_Error( 'woocommerce_rest_plugin_install', __( 'The requested plugin could not be installed.', 'woocommerce-admin' ), 500 );
		}

		return array(
			'slug'   => $slug,
			'name'   => $api->name,
			'status' => 'success',
		);
	}

	/**
	 * Activate the requested plugin.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array Plugin Status
	 */
	public function activate_plugin( $request ) {
		$allowed_plugins = $this->get_allowed_plugins();
		$plugin          = sanitize_title_with_dashes( $request['plugin'] );
		if ( ! in_array( $plugin, array_keys( $allowed_plugins ), true ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_plugin', __( 'Invalid plugin.', 'woocommerce-admin' ), 404 );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$slug              = $plugin;
		$path              = $allowed_plugins[ $slug ];
		$installed_plugins = get_plugins();

		if ( ! in_array( $path, array_keys( $installed_plugins ), true ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_plugin', __( 'Invalid plugin.', 'woocommerce-admin' ), 404 );
		}

		$result = activate_plugin( $path );
		if ( ! is_null( $result ) ) {
			return new \WP_Error( 'woocommerce_rest_invalid_plugin', __( 'The requested plugin could not be activated.', 'woocommerce-admin' ), 500 );
		}

		return( array(
			'slug'   => $slug,
			'name'   => $installed_plugins[ $path ]['Name'],
			'status' => 'success',
		) );
	}

	/**
	 * Generates a Jetpack Connect URL.
	 *
	 * @return array Connection URL for Jetpack
	 */
	public function connect_jetpack() {
		if ( ! class_exists( 'Jetpack' ) ) {
			return new \WP_Error( 'woocommerce_rest_jetpack_not_active', __( 'Jetpack is not installed or active.', 'woocommerce-admin' ), 404 );
		}

		$next_step_slug = apply_filters( 'woocommerce_onboarding_after_jetpack_step', 'store-details' );
		$redirect_url   = esc_url_raw(
			add_query_arg(
				array(
					'page' => 'wc-admin',
					'step' => $next_step_slug,
				),
				admin_url( 'admin.php' )
			)
		);

		$connect_url = \Jetpack::init()->build_connect_url( true, $redirect_url, 'woocommerce-setup-wizard' );

		// Redirect to local calypso instead of production.
		if ( defined( 'WOOCOMMERCE_CALYPSO_LOCAL' ) && WOOCOMMERCE_CALYPSO_LOCAL ) {
			$connect_url = add_query_arg(
				array(
					'calypso_env' => 'development',
				),
				$connect_url
			);
		}

		return( array(
			'slug'          => 'jetpack',
			'name'          => __( 'Jetpack', 'woocommerce-admin' ),
			'connectAction' => $connect_url,
		) );
	}

	/**
	 *  Kicks off the WCCOM Connect process.
	 *
	 * @return array Connection URL for WooCommerce.com
	 */
	public function request_wccom_connect() {
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php';
		if ( ! class_exists( 'WC_Helper_API' ) ) {
			return new WP_Error( 'woocommerce_rest_helper_not_active', __( 'There was an error loading the WooCommerce.com Helper API.', 'woocommerce-admin' ), 404 );
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
			return new WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce-admin' ), 500 );
		}

		$secret = json_decode( wp_remote_retrieve_body( $request ) );
		if ( empty( $secret ) ) {
			return new WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce-admin' ), 500 );
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

		// Redirect to local calypso instead of production.
		// @todo WordPress.com Connect / the OAuth flow does not currently respect this, but a patch is in the works to make this easier.
		if ( defined( 'WOOCOMMERCE_CALYPSO_LOCAL' ) && WOOCOMMERCE_CALYPSO_LOCAL ) {
			$connect_url = add_query_arg(
				array(
					'calypso_env' => 'development',
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
	 * @return array Contains success status.
	 */
	public function finish_wccom_connect( $rest_request ) {
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php';
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php';
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-updater.php';
		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-options.php';
		if ( ! class_exists( 'WC_Helper_API' ) ) {
			return new WP_Error( 'woocommerce_rest_helper_not_active', __( 'There was an error loading the WooCommerce.com Helper API.', 'woocommerce-admin' ), 404 );
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
			return new WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce-admin' ), 500 );
		}

		$access_token = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! $access_token ) {
			return new WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce-admin' ), 500 );
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
			return new WP_Error( 'woocommerce_rest_helper_connect', __( 'There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce-admin' ), 500 );
		}

		delete_transient( '_woocommerce_helper_subscriptions' );
		\WC_Helper_Updater::flush_updates_cache();

		do_action( 'woocommerce_helper_connected' );

		return array(
			'success' => true,
		);
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'onboarding_plugin',
			'type'       => 'object',
			'properties' => array(
				'slug'   => array(
					'description' => __( 'Plugin slug.', 'woocommerce-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'   => array(
					'description' => __( 'Plugin name.', 'woocommerce-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status' => array(
					'description' => __( 'Plugin status.', 'woocommerce-admin' ),
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
			'description' => __( 'Action that should be completed to connect Jetpack.', 'woocommerce-admin' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);
		return $schema;
	}
}
