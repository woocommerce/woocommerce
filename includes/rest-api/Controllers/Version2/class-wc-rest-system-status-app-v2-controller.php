<?php
/**
 * REST API WC System Status App controller
 *
 * Handles requests to the /system_status endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * System status app controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_System_Status_App_V2_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'system_status/app';

	/**
	 * Register the route for /system_status
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
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
		$fields   = $this->get_fields_for_response( $request );
		$mappings = $this->get_item_mappings_per_fields( $fields );
		$response = $this->prepare_item_for_response( $mappings, $request );

		$query_params = $request->get_query_params();
		$this->record_usage_data( $query_params );

		return rest_ensure_response( $response );
	}

	/**
	 * Record WCTracker Data
	 *
	 * @param Array $query_params List of query parameters for the current request.
	 */
	public function record_usage_data( $query_params ) {
		$new = $this->get_usage_data( $query_params );
		if ( ! $new || ! $new['platform'] ) {
			return;
		}

		$data = get_option( 'woocommerce_mobile_app_usage' );
		if ( ! $data ) {
			$data = array();
		}

		$platform = $new['platform'];
		if ( ! $data[ $platform ] || version_compare( $new['version'], $data[ $platform ]['version'], '>=' ) ) {
			$data[ $platform ] = $new;
		}

		update_option( 'woocommerce_mobile_app_usage', $data );
	}

	/**
	 * Get usage data from current request
	 *
	 * @param Array $query_params List of query parameters for the current request.
	 * @return Array
	 */
	public function get_usage_data( $query_params ) {
		$platform = strtolower( $query_params['platform'] );
		switch ( $platform ) {
			case 'ios':
			case 'android':
				break;
			default:
				return;
		}

		$version = $query_params['version'];
		if ( ! $version ) {
			return;
		}

		return array(
			'platform'  => sanitize_text_field( $platform ),
			'version'   => sanitize_text_field( $version ),
			'last_used' => date( 'c' ),
		);
	}

	/**
	 * Get the system status schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'system_status/app',
			'type'       => 'object',
			'properties' => array(
				'version'        => array(
					'description' => __( 'WooCommerce Version.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type' => 'string',
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
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Return an array of sections and the data associated with each.
	 *
	 * @deprecated 3.9.0
	 * @return array
	 */
	public function get_item_mappings() {
		return array(
			'version'        => WC()->version,
			'active_plugins' => $this->get_active_plugins(),
		);
	}

	/**
	 * Return an array of sections and the data associated with each.
	 *
	 * @since 3.9.0
	 * @param array $fields List of fields to be included on the response.
	 * @return array
	 */
	public function get_item_mappings_per_fields( $fields ) {
		return array(
			'version'        => WC()->version,
			'active_plugins' => $this->get_active_plugins(),
		);
	}

	/**
	 * Get a list of plugins active on the site.
	 *
	 * @return array
	 */
	public function get_active_plugins() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! function_exists( 'get_plugin_data' ) ) {
			return array();
		}

		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
		}

		$active_plugins_data = array();

		foreach ( $active_plugins as $plugin ) {
			$data                  = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$active_plugins_data[] = $this->format_plugin_data( $plugin, $data );
		}

		return $active_plugins_data;
	}

	/**
	 * Format plugin data, including data on updates, into a standard format.
	 *
	 * @since 3.6.0
	 * @param string $plugin Plugin directory/file.
	 * @param array  $data Plugin data from WP.
	 * @return array Formatted data.
	 */
	protected function format_plugin_data( $plugin, $data ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';

		if ( ! function_exists( 'get_plugin_updates' ) ) {
			return array();
		}

		// Use WP API to lookup latest updates for plugins. WC_Helper injects updates for premium plugins.
		if ( empty( $this->available_updates ) ) {
			$this->available_updates = get_plugin_updates();
		}

		$version_latest = $data['Version'];

		// Find latest version.
		if ( isset( $this->available_updates[ $plugin ]->update->new_version ) ) {
			$version_latest = $this->available_updates[ $plugin ]->update->new_version;
		}

		return array(
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
	 * @param  array           $system_status System status data.
	 * @param  WP_REST_Request $request       Request object.
	 * @return WP_REST_Response
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
