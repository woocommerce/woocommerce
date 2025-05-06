<?php
/**
 * REST API Onboarding Profile Controller
 *
 * Handles requests to /onboarding/profile
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use ActionScheduler;
use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\PluginsInstallLoggers\AsynPluginsInstallLogger;
use WC_REST_Data_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Onboarding Plugins controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class OnboardingPlugins extends WC_REST_Data_Controller {
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
	protected $rest_base = 'onboarding/plugins';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/install-and-activate-async',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_and_activate_async' ),
					'permission_callback' => array( $this, 'can_install_and_activate_plugins' ),
					'args'                => array(
						'plugins' => array(
							'description'       => 'A list of plugins to install',
							'type'              => 'array',
							'items'             => 'string',
							'sanitize_callback' => function ( $value ) {
								return array_map(
									function ( $value ) {
										return sanitize_text_field( $value );
									},
									$value
								);
							},
							'required'          => true,
						),
						'source'  => array(
							'description'       => 'The source of the request',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => false,
						),
					),
				),
				'schema' => array( $this, 'get_install_async_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/install-and-activate',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_and_activate' ),
					'permission_callback' => array( $this, 'can_install_and_activate_plugins' ),

				),
				'schema' => array( $this, 'get_install_activate_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scheduled-installs/(?P<job_id>\w+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_scheduled_installs' ),
					'permission_callback' => array( $this, 'can_install_plugins' ),
				),
				'schema' => array( $this, 'get_install_async_schema' ),
			)
		);

		// This is an experimental endpoint and is subject to change in the future.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/jetpack-authorization-url',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_jetpack_authorization_url' ),
					'permission_callback' => array( $this, 'can_install_plugins' ),
					'args'                => array(
						'redirect_url' => array(
							'description'       => 'The URL to redirect to after authorization',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => true,
						),
						'from'         => array(
							'description'       => 'from value for the jetpack authorization page',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => false,
							'default'           => 'woocommerce-onboarding',
						),
					),
				),
			)
		);
		add_action( 'woocommerce_plugins_install_error', array( $this, 'log_plugins_install_error' ), 10, 4 );
		add_action( 'woocommerce_plugins_install_api_error', array( $this, 'log_plugins_install_api_error' ), 10, 2 );
	}

	/**
	 * Install and activate a plugin.
	 *
	 * @param WP_REST_Request $request WP Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function install_and_activate( WP_REST_Request $request ) {
		$response             = array();
		$response['install']  = PluginsHelper::install_plugins( $request->get_param( 'plugins' ) );
		$response['activate'] = PluginsHelper::activate_plugins( $response['install']['installed'] );

		return new WP_REST_Response( $response );
	}

	/**
	 * Queue plugin install request.
	 *
	 * @param WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return array
	 */
	public function install_and_activate_async( WP_REST_Request $request ) {
		$plugins = $request->get_param( 'plugins' );
		$source  = $request->get_param( 'source' );
		$job_id  = uniqid();

		WC()->queue()->add( 'woocommerce_plugins_install_and_activate_async_callback', array( $plugins, $job_id, $source ) );

		$plugin_status = array();
		foreach ( $plugins as $plugin ) {
			$plugin_status[ $plugin ] = array(
				'status' => 'pending',
				'errors' => array(),
			);
		}

		return array(
			'job_id'  => $job_id,
			'status'  => 'pending',
			'plugins' => $plugin_status,
		);
	}

	/**
	 * Returns current status of given job.
	 *
	 * @param WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return array|WP_REST_Response
	 */
	public function get_scheduled_installs( WP_REST_Request $request ) {
		$job_id = $request->get_param( 'job_id' );

		$actions = WC()->queue()->search(
			array(
				'hook'    => 'woocommerce_plugins_install_and_activate_async_callback',
				'search'  => $job_id,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$actions = array_filter(
			PluginsHelper::get_action_data( $actions ),
			function ( $action ) use ( $job_id ) {
				return $action['job_id'] === $job_id;
			}
		);

		if ( empty( $actions ) ) {
			return new WP_REST_Response( null, 404 );
		}

		$response = array(
			'job_id' => $actions[0]['job_id'],
			'status' => $actions[0]['status'],
		);

		$option = get_option( 'woocommerce_onboarding_plugins_install_and_activate_async_' . $job_id );
		if ( isset( $option['plugins'] ) ) {
			$response['plugins'] = $option['plugins'];
		}

		return $response;
	}


	/**
	 * Return Jetpack authorization URL.
	 *
	 * @param WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return array
	 * @throws \Exception If there is an error registering the site.
	 */
	public function get_jetpack_authorization_url( WP_REST_Request $request ) {
		$manager = new Manager( 'woocommerce' );
		$errors  = new WP_Error();

		// Register the site to wp.com.
		if ( ! $manager->is_connected() ) {
			$result = $manager->try_registration();
			if ( is_wp_error( $result ) ) {
				$errors->add( $result->get_error_code(), $result->get_error_message() );
			}
		}

		$redirect_url = $request->get_param( 'redirect_url' );
		$calypso_env  = defined( 'WOOCOMMERCE_CALYPSO_ENVIRONMENT' ) && in_array( WOOCOMMERCE_CALYPSO_ENVIRONMENT, array( 'development', 'wpcalypso', 'horizon', 'stage' ), true ) ? WOOCOMMERCE_CALYPSO_ENVIRONMENT : 'production';

		$authorization_url = $manager->get_authorization_url( null, $redirect_url );
		$authorization_url = add_query_arg( 'locale', $this->get_wpcom_locale(), $authorization_url );

		if ( Features::is_enabled( 'use-wp-horizon' ) ) {
			$calypso_env = 'horizon';
		}

		$color_scheme = get_user_option( 'admin_color', get_current_user_id() );
		if ( ! $color_scheme ) {
			$color_scheme = 'default';
		}

		return array(
			'success'      => ! $errors->has_errors(),
			'errors'       => $errors->get_error_messages(),
			'color_scheme' => 'fresh' === $color_scheme ? 'default' : $color_scheme,
			'url'          => add_query_arg(
				array(
					'from'        => $request->get_param( 'from' ),
					'calypso_env' => $calypso_env,
				),
				$authorization_url,
			),
		);
	}

	/**
	 * Return a locale string for wpcom.
	 *
	 * @return string
	 */
	private function get_wpcom_locale() {
		// List of locales that should be used with region code.
		$locale_to_lang = array(
			'bre'   => 'br',
			'de_AT' => 'de-at',
			'de_CH' => 'de-ch',
			'de'    => 'de_formal',
			'el'    => 'el-po',
			'en_GB' => 'en-gb',
			'es_CL' => 'es-cl',
			'es_MX' => 'es-mx',
			'fr_BE' => 'fr-be',
			'fr_CA' => 'fr-ca',
			'nl_BE' => 'nl-be',
			'nl'    => 'nl_formal',
			'pt_BR' => 'pt-br',
			'sr'    => 'sr_latin',
			'zh_CN' => 'zh-cn',
			'zh_HK' => 'zh-hk',
			'zh_SG' => 'zh-sg',
			'zh_TW' => 'zh-tw',
		);

		$system_locale = get_locale();
		if ( isset( $locale_to_lang[ $system_locale ] ) ) {
			// Return the locale with region code if it's in the list.
			return $locale_to_lang[ $system_locale ];
		}

		// If the locale is not in the list, return the language code only.
		return explode( '_', $system_locale )[0];
	}

	/**
	 * Check whether the current user has permission to install plugins
	 *
	 * @return WP_Error|boolean
	 */
	public function can_install_plugins() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_update',
				__( 'Sorry, you cannot manage plugins.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check whether the current user has permission to install and activate plugins
	 *
	 * @return WP_Error|boolean
	 */
	public function can_install_and_activate_plugins() {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_update',
				__( 'Sorry, you cannot manage plugins.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * JSON Schema for both install-async and scheduled-installs endpoints.
	 *
	 * @return array
	 */
	public function get_install_async_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Install Async Schema',
			'type'       => 'object',
			'properties' => array(
				'type'       => 'object',
				'properties' => array(
					'job_id' => 'integer',
					'status' => array(
						'type' => 'string',
						'enum' => array( 'pending', 'complete', 'failed' ),
					),
				),
			),
		);
	}

	/**
	 * JSON Schema for install-and-activate endpoint.
	 *
	 * @return array
	 */
	public function get_install_activate_schema() {
		$error_schema = array(
			'type'              => 'object',
			'patternProperties' => array(
				'^.*$' => array(
					'type' => 'string',
				),
			),
			'items'             => array(
				'type' => 'string',
			),
		);

		$install_schema = array(
			'type'       => 'object',
			'properties' => array(
				'installed' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'results'   => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'errors'    => array(
					'type'       => 'object',
					'properties' => array(
						'errors'     => $error_schema,
						'error_data' => $error_schema,
					),
				),
			),
		);

		$activate_schema = array(
			'type'       => 'object',
			'properties' => array(
				'activated' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'active'    => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'errors'    => array(
					'type'       => 'object',
					'properties' => array(
						'errors'     => $error_schema,
						'error_data' => $error_schema,
					),
				),
			),
		);

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Install and Activate Schema',
			'type'       => 'object',
			'properties' => array(
				'type'       => 'object',
				'properties' => array(
					'install'  => $install_schema,
					'activate' => $activate_schema,
				),
			),
		);
	}

	public function log_plugins_install_error( $slug, $api, $result, $upgrader ) {
		$properties = array(
			'error_message'         => sprintf(
			/* translators: %s: plugin slug (example: woocommerce-services) */
				__(
					'The requested plugin `%s` could not be installed.',
					'woocommerce'
				),
				$slug
			),
			'type'                  => 'plugin_info_api_error',
			'slug'                  => $slug,
			'api_version'           => $api->version,
			'api_download_link'     => $api->download_link,
			'upgrader_skin_message' => implode( ',', $upgrader->skin->get_upgrade_messages() ),
			'result'                => is_wp_error( $result ) ? $result->get_error_message() : 'null',
		);
		wc_admin_record_tracks_event( 'coreprofiler_install_plugin_error', $properties );
	}

	public function log_plugins_install_api_error( $slug, $api ) {
		$properties = array(
			'error_message'     => sprintf(
			// translators: %s: plugin slug (example: woocommerce-services).
				__(
					'The requested plugin `%s` could not be installed. Plugin API call failed.',
					'woocommerce'
				),
				$slug
			),
			'type'              => 'plugin_install_error',
			'api_error_message' => $api->get_error_message(),
			'slug'              => $slug,
		);
		wc_admin_record_tracks_event( 'coreprofiler_install_plugin_error', $properties );
	}
}
