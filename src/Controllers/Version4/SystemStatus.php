<?php
/**
 * REST API WC System Status controller
 *
 * Handles requests to the /system_status endpoint.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4;

defined( 'ABSPATH' ) || exit;

/**
 * REST API System Status controller class.
 */
class SystemStatus extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'system_status';

	/**
	 * Permission to check.
	 *
	 * @var string
	 */
	protected $resource_type = 'system_status';

	/**
	 * Register the route for /system_status
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			true
		);
	}

	/**
	 * Get a system status info, by section.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$schema   = $this->get_item_schema();
		$mappings = $this->get_item_mappings();
		$response = array();

		foreach ( $mappings as $section => $values ) {
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
				'environment'    => array(
					'description' => __( 'Environment.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'home_url'                  => array(
							'description' => __( 'Home URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'site_url'                  => array(
							'description' => __( 'Site URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wc_version'                => array(
							'description' => __( 'WooCommerce version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'log_directory'             => array(
							'description' => __( 'Log directory.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'log_directory_writable'    => array(
							'description' => __( 'Is log directory writable?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_version'                => array(
							'description' => __( 'WordPress version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_multisite'              => array(
							'description' => __( 'Is WordPress multisite?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_memory_limit'           => array(
							'description' => __( 'WordPress memory limit.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_debug_mode'             => array(
							'description' => __( 'Is WordPress debug mode active?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'wp_cron'                   => array(
							'description' => __( 'Are WordPress cron jobs enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'language'                  => array(
							'description' => __( 'WordPress language.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'server_info'               => array(
							'description' => __( 'Server info.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_version'               => array(
							'description' => __( 'PHP version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_post_max_size'         => array(
							'description' => __( 'PHP post max size.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_max_execution_time'    => array(
							'description' => __( 'PHP max execution time.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'php_max_input_vars'        => array(
							'description' => __( 'PHP max input vars.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'curl_version'              => array(
							'description' => __( 'cURL version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'suhosin_installed'         => array(
							'description' => __( 'Is SUHOSIN installed?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'max_upload_size'           => array(
							'description' => __( 'Max upload size.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'mysql_version'             => array(
							'description' => __( 'MySQL version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'mysql_version_string'             => array(
							'description' => __( 'MySQL version string.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'default_timezone'          => array(
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
						'soapclient_enabled'        => array(
							'description' => __( 'Is SoapClient class enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'domdocument_enabled'       => array(
							'description' => __( 'Is DomDocument class enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'gzip_enabled'              => array(
							'description' => __( 'Is GZip enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'mbstring_enabled'          => array(
							'description' => __( 'Is mbstring enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_post_successful'    => array(
							'description' => __( 'Remote POST successful?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_post_response'      => array(
							'description' => __( 'Remote POST response.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_get_successful'     => array(
							'description' => __( 'Remote GET successful?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'remote_get_response'       => array(
							'description' => __( 'Remote GET response.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
				'database'       => array(
					'description' => __( 'Database.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'wc_database_version'    => array(
							'description' => __( 'WC database version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'database_prefix'        => array(
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
						'database_tables'        => array(
							'description' => __( 'Database tables.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type' => 'string',
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
						'type' => 'string',
					),
				),
				'inactive_plugins' => array(
					'description' => __( 'Inactive plugins.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
					),
				),
				'dropins_mu_plugins' => array(
					'description' => __( 'Dropins & MU plugins.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
					),
				),
				'theme'          => array(
					'description' => __( 'Theme.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'name'                    => array(
							'description' => __( 'Theme name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'version'                 => array(
							'description' => __( 'Theme version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'version_latest'          => array(
							'description' => __( 'Latest version of theme.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'author_url'              => array(
							'description' => __( 'Theme author URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'is_child_theme'          => array(
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
						'has_woocommerce_file'    => array(
							'description' => __( 'Does the theme have a woocommerce.php file?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'has_outdated_templates'  => array(
							'description' => __( 'Does this theme have outdated templates?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'overrides'               => array(
							'description' => __( 'Template overrides.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type' => 'string',
							),
						),
						'parent_name'             => array(
							'description' => __( 'Parent theme name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'parent_version'          => array(
							'description' => __( 'Parent theme version.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'parent_author_url'       => array(
							'description' => __( 'Parent theme author URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
				'settings'       => array(
					'description' => __( 'Settings.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties'  => array(
						'api_enabled'              => array(
							'description' => __( 'REST API enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'force_ssl'                => array(
							'description' => __( 'SSL forced?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'currency'                 => array(
							'description' => __( 'Currency.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'currency_symbol'          => array(
							'description' => __( 'Currency symbol.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'currency_position'        => array(
							'description' => __( 'Currency position.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'thousand_separator'       => array(
							'description' => __( 'Thousand separator.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'decimal_separator'        => array(
							'description' => __( 'Decimal separator.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'number_of_decimals'       => array(
							'description' => __( 'Number of decimals.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'geolocation_enabled'      => array(
							'description' => __( 'Geolocation enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'taxonomies'               => array(
							'description' => __( 'Taxonomy terms for product/order statuses.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type' => 'string',
							),
						),
						'product_visibility_terms' => array(
							'description' => __( 'Terms in the product visibility taxonomy.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view' ),
							'readonly'    => true,
							'items'       => array(
								'type' => 'string',
							),
						),
					),
				),
				'security'       => array(
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
						'hide_errors'       => array(
							'description' => __( 'Hide errors from visitors?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
				'pages'          => array(
					'description' => __( 'WooCommerce pages.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
					),
				),
				'post_type_counts' => array(
					'description' => __( 'Post type counts.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
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
		$plugin_info     = new \Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities\PluginInformation();
		$theme_info      = new \Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities\ThemeInformation();
		$server          = new \Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities\ServerEnvironment();
		$database        = new \Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities\DatabaseInformation();
		$wp_environment  = new \Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities\WPEnvironment();
		$woo_environment = new \Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities\WooEnvironment();

		return array(
			'environment'        => $server->get_environment_info(),
			'database'           => $database->get_database_info(),
			'active_plugins'     => $plugin_info->get_active_plugin_data(),
			'inactive_plugins'   => $plugin_info->get_inactive_plugin_data(),
			'dropins_mu_plugins' => $plugin_info->get_dropin_and_mu_plugin_data(),
			'theme'              => $theme_info->get_theme_info(),
			'settings'           => $woo_environment->get_settings(),
			'security'           => $wp_environment->get_security_info(),
			'pages'              => $wp_environment->get_pages(),
			'post_type_counts'   => $wp_environment->get_post_type_counts(),
		);
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
}
