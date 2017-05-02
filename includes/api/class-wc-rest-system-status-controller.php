<?php
/**
 * REST API WC System Status controller
 *
 * Handles requests to the /system_status endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_System_Status_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'system_status';

	/**
	 * Register the route for /system_status
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Get a system status info, by section.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$schema    = $this->get_item_schema();
		$mappings  = $this->get_item_mappings();
		$response  = array();

		foreach ( $mappings as $section => $values ) {
			foreach ( $values as $key => $value ) {
				if ( isset( $schema['properties'][ $section ]['properties'][ $key ]['type'] ) ) {
					settype( $values[ $key ], $schema['properties'][ $section ]['properties'][ $key ]['type'] );
				}
			}
			settype( $values, $schema['properties'][ $section ]['type'] );
			$response[ $section ] = $values;
		}

		$response = $this->prepare_item_for_response( $response, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Get the system status schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'system_status',
			'type'       => 'object',
			'properties' => array(
				'environment' => array(
					'description' => __( 'Environment.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'home_url' => array(
							'description' => __( 'Home URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'site_url' => array(
							'description' => __( 'Site URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wc_version' => array(
							'description' => __( 'WooCommerce version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'log_directory' => array(
							'description' => __( 'Log directory.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'log_directory_writable' => array(
							'description' => __( 'Is log directory writable?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_version' => array(
							'description' => __( 'WordPress version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_multisite' => array(
							'description' => __( 'Is WordPress multisite?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_memory_limit' => array(
							'description' => __( 'WordPress memory limit.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_debug_mode' => array(
							'description' => __( 'Is WordPress debug mode active?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_cron' => array(
							'description' => __( 'Are WordPress cron jobs enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'language' => array(
							'description' => __( 'WordPress language.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'server_info' => array(
							'description' => __( 'Server info.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_version' => array(
							'description' => __( 'PHP version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_post_max_size' => array(
							'description' => __( 'PHP post max size.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_max_execution_time' => array(
							'description' => __( 'PHP max execution time.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_max_input_vars' => array(
							'description' => __( 'PHP max input vars.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'curl_version' => array(
							'description' => __( 'cURL version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'suhosin_installed' => array(
							'description' => __( 'Is SUHOSIN installed?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'max_upload_size' => array(
							'description' => __( 'Max upload size.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'mysql_version' => array(
							'description' => __( 'MySQL version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'default_timezone' => array(
							'description' => __( 'Default timezone.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'fsockopen_or_curl_enabled' => array(
							'description' => __( 'Is fsockopen/cURL enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'soapclient_enabled' => array(
							'description' => __( 'Is SoapClient class enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'domdocument_enabled' => array(
							'description' => __( 'Is DomDocument class enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'gzip_enabled' => array(
							'description' => __( 'Is GZip enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'mbstring_enabled' => array(
							'description' => __( 'Is mbstring enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_post_successful' => array(
							'description' => __( 'Remote POST successful?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_post_response' => array(
							'description' => __( 'Remote POST response.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_get_successful' => array(
							'description' => __( 'Remote GET successful?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_get_response' => array(
							'description' => __( 'Remote GET response.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
				'database' => array(
					'description' => __( 'Database.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'wc_database_version' => array(
							'description' => __( 'WC database version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'database_prefix' => array(
							'description' => __( 'Database prefix.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'maxmind_geoip_database' => array(
							'description' => __( 'MaxMind GeoIP database.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'database_tables' => array(
							'description' => __( 'Database tables.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type'    => 'string',
							),
						),
					),
				),
				'active_plugins' => array(
					'description' => __( 'Active plugins.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'    => 'string',
					),
				),
				'theme' => array(
					'description' => __( 'Theme.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'name' => array(
							'description' => __( 'Theme name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'version' => array(
							'description' => __( 'Theme version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'version_latest' => array(
							'description' => __( 'Latest version of theme.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'author_url' => array(
							'description' => __( 'Theme author URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'is_child_theme' => array(
							'description' => __( 'Is this theme a child theme?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'has_woocommerce_support' => array(
							'description' => __( 'Does the theme declare WooCommerce support?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'has_woocommerce_file' => array(
							'description' => __( 'Does the theme have a woocommerce.php file?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'has_outdated_templates' => array(
							'description' => __( 'Does this theme have outdated templates?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'overrides' => array(
							'description' => __( 'Template overrides.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type'    => 'string',
							),
						),
						'parent_name' => array(
							'description' => __( 'Parent theme name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'parent_version' => array(
							'description' => __( 'Parent theme version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'parent_author_url' => array(
							'description' => __( 'Parent theme author URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
				'settings' => array(
					'description' => __( 'Settings.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'api_enabled' => array(
							'description' => __( 'REST API enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'force_ssl' => array(
							'description' => __( 'SSL forced?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'currency' => array(
							'description' => __( 'Currency.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'currency_symbol' => array(
							'description' => __( 'Currency symbol.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'currency_position' => array(
							'description' => __( 'Currency position.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'thousand_separator' => array(
							'description' => __( 'Thousand separator.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'decimal_separator' => array(
							'description' => __( 'Decimal separator.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'number_of_decimals' => array(
							'description' => __( 'Number of decimals.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'geolocation_enabled' => array(
							'description' => __( 'Geolocation enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'taxonomies' => array(
							'description' => __( 'Taxonomy terms for product/order statuses.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type'    => 'string',
							),
						),
						'product_visibility_terms' => array(
							'description' => __( 'Terms in the product visibility taxonomy.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type'    => 'string',
							),
						),
					),
				),
				'security' => array(
					'description' => __( 'Security.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'secure_connection' => array(
							'description' => __( 'Is the connection to your store secure?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'hide_errors' => array(
							'description' => __( 'Hide errors from visitors?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
				'pages' => array(
					'description' => __( 'WooCommerce pages.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'    => 'string',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Return an array of sections and the data associated with each.
	 *
	 * @return array
	 */
	public function get_item_mappings() {
		return array(
			'environment'    => $this->get_environment_info(),
			'database'       => $this->get_database_info(),
			'active_plugins' => $this->get_active_plugins(),
			'theme'          => $this->get_theme_info(),
			'settings'       => $this->get_settings(),
			'security'       => $this->get_security_info(),
			'pages'          => $this->get_pages(),
		);
	}

	/**
	 * Get array of environment information. Includes thing like software
	 * versions, and various server settings.
	 *
	 * @return array
	 */
	public function get_environment_info() {
		global $wpdb;

		// Figure out cURL version, if installed.
		$curl_version = '';
		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
		}

		// WP memory limit
		$wp_memory_limit = wc_let_to_num( WP_MEMORY_LIMIT );
		if ( function_exists( 'memory_get_usage' ) ) {
			$wp_memory_limit = max( $wp_memory_limit, wc_let_to_num( @ini_get( 'memory_limit' ) ) );
		}

		// Test POST requests
		$post_response = wp_safe_remote_post( 'https://www.paypal.com/cgi-bin/webscr', array(
			'timeout'     => 10,
			'user-agent'  => 'WooCommerce/' . WC()->version,
			'httpversion' => '1.1',
			'body'        => array(
				'cmd'    => '_notify-validate',
			),
		) );
		$post_response_successful = false;
		if ( ! is_wp_error( $post_response ) && $post_response['response']['code'] >= 200 && $post_response['response']['code'] < 300 ) {
			$post_response_successful = true;
		}

		// Test GET requests
		$get_response = wp_safe_remote_get( 'https://woocommerce.com/wc-api/product-key-api?request=ping&network=' . ( is_multisite() ? '1' : '0' ) );
		$get_response_successful = false;
		if ( ! is_wp_error( $post_response ) && $post_response['response']['code'] >= 200 && $post_response['response']['code'] < 300 ) {
			$get_response_successful = true;
		}

		// Return all environment info. Described by JSON Schema.
		return array(
			'home_url'                  => get_option( 'home' ),
			'site_url'                  => get_option( 'siteurl' ),
			'version'                => WC()->version,
			'log_directory'             => WC_LOG_DIR,
			'log_directory_writable'    => ( @fopen( WC_LOG_DIR . 'test-log.log', 'a' ) ? true : false ),
			'wp_version'                => get_bloginfo( 'version' ),
			'wp_multisite'              => is_multisite(),
			'wp_memory_limit'           => $wp_memory_limit,
			'wp_debug_mode'             => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'wp_cron'                   => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'language'                  => get_locale(),
			'server_info'               => $_SERVER['SERVER_SOFTWARE'],
			'php_version'               => phpversion(),
			'php_post_max_size'         => wc_let_to_num( ini_get( 'post_max_size' ) ),
			'php_max_execution_time'    => ini_get( 'max_execution_time' ),
			'php_max_input_vars'        => ini_get( 'max_input_vars' ),
			'curl_version'              => $curl_version,
			'suhosin_installed'         => extension_loaded( 'suhosin' ),
			'max_upload_size'           => wp_max_upload_size(),
			'mysql_version'             => ( ! empty( $wpdb->is_mysql ) ? $wpdb->db_version() : '' ),
			'default_timezone'          => date_default_timezone_get(),
			'fsockopen_or_curl_enabled' => ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ),
			'soapclient_enabled'        => class_exists( 'SoapClient' ),
			'domdocument_enabled'       => class_exists( 'DOMDocument' ),
			'gzip_enabled'              => is_callable( 'gzopen' ),
			'mbstring_enabled'          => extension_loaded( 'mbstring' ),
			'remote_post_successful'    => $post_response_successful,
			'remote_post_response'      => ( is_wp_error( $post_response ) ? $post_response->get_error_message() : $post_response['response']['code'] ),
			'remote_get_successful'     => $get_response_successful,
			'remote_get_response'       => ( is_wp_error( $get_response ) ? $get_response->get_error_message() : $get_response['response']['code'] ),
		);
	}

	/**
	 * Get array of database information. Version, prefix, and table existence.
	 *
	 * @return array
	 */
	public function get_database_info() {
		global $wpdb;

		// WC Core tables to check existence of
		$tables = apply_filters( 'woocommerce_database_tables', array(
			'woocommerce_sessions',
			'woocommerce_api_keys',
			'woocommerce_attribute_taxonomies',
			'woocommerce_downloadable_product_permissions',
			'woocommerce_order_items',
			'woocommerce_order_itemmeta',
			'woocommerce_tax_rates',
			'woocommerce_tax_rate_locations',
			'woocommerce_shipping_zones',
			'woocommerce_shipping_zone_locations',
			'woocommerce_shipping_zone_methods',
			'woocommerce_payment_tokens',
			'woocommerce_payment_tokenmeta',
		) );

		if ( get_option( 'db_version' ) < 34370 ) {
			$tables[] = 'woocommerce_termmeta';
		}
		$table_exists = array();
		foreach ( $tables as $table ) {
			$table_exists[ $table ] = ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s;", $wpdb->prefix . $table ) ) === $wpdb->prefix . $table );
		}

		// Return all database info. Described by JSON Schema.
		return array(
			'wc_database_version'    => get_option( 'woocommerce_db_version' ),
			'database_prefix'        => $wpdb->prefix,
			'maxmind_geoip_database' => WC_Geolocation::get_local_database_path(),
			'database_tables'        => $table_exists,
		);
	}

	/**
	 * Get a list of plugins active on the site.
	 *
	 * @return array
	 */
	public function get_active_plugins() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Get both site plugins and network plugins
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
		}

		$active_plugins_data = array();
		$available_updates   = get_plugin_updates();

		foreach ( $active_plugins as $plugin ) {
			$data           = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$dirname        = dirname( $plugin );
			$version_latest = '';
			$slug           = explode( '/', $plugin );
			$slug           = explode( '.', end( $slug ) );
			$slug           = $slug[0];

			if ( 'woocommerce' !== $slug && ( strstr( $data['PluginURI'], 'woothemes.com' ) || strstr( $data['PluginURI'], 'woocommerce.com' ) ) ) {
				if ( false === ( $version_data = get_transient( md5( $plugin ) . '_version_data' ) ) ) {
					$changelog = wp_safe_remote_get( 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $dirname . '/changelog.txt' );
					$cl_lines  = explode( "\n", wp_remote_retrieve_body( $changelog ) );
					if ( ! empty( $cl_lines ) ) {
						foreach ( $cl_lines as $line_num => $cl_line ) {
							if ( preg_match( '/^[0-9]/', $cl_line ) ) {
								$date         = str_replace( '.' , '-' , trim( substr( $cl_line , 0 , strpos( $cl_line , '-' ) ) ) );
								$version      = preg_replace( '~[^0-9,.]~' , '' ,stristr( $cl_line , "version" ) );
								$update       = trim( str_replace( "*" , "" , $cl_lines[ $line_num + 1 ] ) );
								$version_data = array( 'date' => $date , 'version' => $version , 'update' => $update , 'changelog' => $changelog );
								set_transient( md5( $plugin ) . '_version_data', $version_data, DAY_IN_SECONDS );
								break;
							}
						}
					}
				}
				$version_latest = $version_data['version'];
			} elseif ( isset( $available_updates[ $plugin ]->update->new_version ) ) {
				$version_latest = $available_updates[ $plugin ]->update->new_version;
			}

			// convert plugin data to json response format.
			$active_plugins_data[] = array(
				'plugin'            => $plugin,
				'name'              => $data['Name'],
				'version'           => $data['Version'],
				'version_latest'    => $version_latest,
				'url'               => $data['PluginURI'],
				'author_name'       => $data['AuthorName'],
				'author_url'        => esc_url_raw( $data['AuthorURI'] ),
				'network_activated' => $data['Network'],
			);
		}

		return $active_plugins_data;
	}

	/**
	 * Get info on the current active theme, info on parent theme (if presnet)
	 * and a list of template overrides.
	 *
	 * @return array
	 */
	public function get_theme_info() {
		$active_theme = wp_get_theme();

		// Get parent theme info if this theme is a child theme, otherwise
		// pass empty info in the response.
		if ( is_child_theme() ) {
			$parent_theme      = wp_get_theme( $active_theme->Template );
			$parent_theme_info = array(
				'parent_name'           => $parent_theme->Name,
				'parent_version'        => $parent_theme->Version,
				'parent_version_latest' => WC_Admin_Status::get_latest_theme_version( $parent_theme ),
				'parent_author_url'     => $parent_theme->{'Author URI'},
			);
		} else {
			$parent_theme_info = array( 'parent_name' => '', 'parent_version' => '', 'parent_version_latest' => '', 'parent_author_url' => '' );
		}

		/**
		 * Scan the theme directory for all WC templates to see if our theme
		 * overrides any of them.
		 */
		$override_files     = array();
		$outdated_templates = false;
		$scan_files         = WC_Admin_Status::scan_template_files( WC()->plugin_path() . '/templates/' );
		foreach ( $scan_files as $file ) {
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . WC()->template_path() . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . WC()->template_path() . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . WC()->template_path() . $file ) ) {
				$theme_file = get_template_directory() . '/' . WC()->template_path() . $file;
			} else {
				$theme_file = false;
			}

			if ( ! empty( $theme_file ) ) {
				$core_version  = WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $file );
				$theme_version = WC_Admin_Status::get_file_version( $theme_file );
				if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
					if ( ! $outdated_templates ) {
						$outdated_templates = true;
					}
				}
				$override_files[] = array(
					'file'         => str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
					'version'      => $theme_version,
					'core_version' => $core_version,
				);
			}
		}

		$active_theme_info = array(
			'name'                    => $active_theme->Name,
			'version'                 => $active_theme->Version,
			'version_latest'          => WC_Admin_Status::get_latest_theme_version( $active_theme ),
			'author_url'              => esc_url_raw( $active_theme->{'Author URI'} ),
			'is_child_theme'          => is_child_theme(),
			'has_woocommerce_support' => ( current_theme_supports( 'woocommerce' ) || in_array( $active_theme->template, wc_get_core_supported_themes() ) ),
			'has_woocommerce_file'    => ( file_exists( get_stylesheet_directory() . '/woocommerce.php' ) || file_exists( get_template_directory() . '/woocommerce.php' ) ),
			'has_outdated_templates'  => $outdated_templates,
			'overrides'               => $override_files,
		);

		return array_merge( $active_theme_info, $parent_theme_info );
	}

	/**
	 * Get some setting values for the site that are useful for debugging
	 * purposes. For full settings access, use the settings api.
	 *
	 * @return array
	 */
	public function get_settings() {
		// Get a list of terms used for product/order taxonomies
		$term_response = array();
		$terms         = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach ( $terms as $term ) {
			$term_response[ $term->slug ] = strtolower( $term->name );
		}

		// Get a list of terms used for product visibility.
		$product_visibility_terms = array();
		$terms                    = get_terms( 'product_visibility', array( 'hide_empty' => 0 ) );
		foreach ( $terms as $term ) {
			$product_visibility_terms[ $term->slug ] = strtolower( $term->name );
		}

		// Return array of useful settings for debugging.
		return array(
			'api_enabled'              => 'yes' === get_option( 'woocommerce_api_enabled' ),
			'force_ssl'                => 'yes' === get_option( 'woocommerce_force_ssl_checkout' ),
			'currency'                 => get_woocommerce_currency(),
			'currency_symbol'          => get_woocommerce_currency_symbol(),
			'currency_position'        => get_option( 'woocommerce_currency_pos' ),
			'thousand_separator'       => wc_get_price_thousand_separator(),
			'decimal_separator'        => wc_get_price_decimal_separator(),
			'number_of_decimals'       => wc_get_price_decimals(),
			'geolocation_enabled'      => in_array( get_option( 'woocommerce_default_customer_address' ), array( 'geolocation_ajax', 'geolocation' ) ),
			'taxonomies'               => $term_response,
			'product_visibility_terms' => $product_visibility_terms,
		);
	}

	/**
	 * Returns security tips.
	 *
	 * @return array
	 */
	public function get_security_info() {
		$check_page = 0 < wc_get_page_id( 'shop' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : get_home_url();
		return array(
			'secure_connection' => 'https' === substr( $check_page, 0, 5 ),
			'hide_errors'       => ! ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG && WP_DEBUG_DISPLAY ) || 0 === intval( ini_get( 'display_errors' ) ),
		);
	}

	/**
	 * Returns a mini-report on WC pages and if they are configured correctly:
	 * Present, visible, and including the correct shortcode.
	 *
	 * @return array
	 */
	public function get_pages() {
		// WC pages to check against
		$check_pages = array(
			_x( 'Shop base', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_shop_page_id',
				'shortcode' => '',
			),
			_x( 'Cart', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_cart_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']',
			),
			_x( 'Checkout', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_checkout_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']',
			),
			_x( 'My account', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_myaccount_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) . ']',
			),
		);

		$pages_output = array();
		foreach ( $check_pages as $page_name => $values ) {
			$errors   = array();
			$page_id  = get_option( $values['option'] );
			$page_set = $page_exists = $page_visible = false;
			$shortcode_present = $shortcode_required = false;

			// Page checks
			if ( $page_id ) {
				$page_set = true;
			}
			if ( get_post( $page_id ) ) {
				$page_exists = true;
			}
			if ( 'publish' === get_post_status( $page_id ) ) {
				$page_visible = true;
			}

			// Shortcode checks
			if ( $values['shortcode']  && get_post( $page_id ) ) {
				$shortcode_required = true;
				$page = get_post( $page_id );
				if ( strstr( $page->post_content, $values['shortcode'] ) ) {
					$shortcode_present = true;
				}
			}

			// Wrap up our findings into an output array
			$pages_output[] = array(
				'page_name'          => $page_name,
				'page_id'            => $page_id,
				'page_set'           => $page_set,
				'page_exists'        => $page_exists,
				'page_visible'       => $page_visible,
				'shortcode'          => $values['shortcode'],
				'shortcode_required' => $shortcode_required,
				'shortcode_present'  => $shortcode_present,
			);
		}

		return $pages_output;
	}

	/**
	 * Get any query params needed.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}

	/**
	 * Prepare the system status response
	 *
	 * @param array $system_status
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $system_status, $request ) {
		$data = $this->add_additional_fields_to_object( $system_status, $request );
		$data = $this->filter_response_by_context( $data, 'view' );

		$response = rest_ensure_response( $data );

		/**
		 * Filter the system status returned from the REST API.
		 *
		 * @param WP_REST_Response   $response The response object.
		 * @param mixed              $system_status System status
		 * @param WP_REST_Request    $request  Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_system_status', $response, $system_status, $request );
	}
}
