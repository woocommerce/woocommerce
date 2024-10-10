<?php
/**
 * REST API Settings Controller
 *
 * Handles requests to save Settings.
 */

namespace Automattic\WooCommerce\Admin\API;

use WC_Admin_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Controller.
 *
 * @extends WC_REST_Data_Controller
 */
class Settings extends \WC_REST_Data_Controller {
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
	protected $rest_base = 'settings';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base, 
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to update settings.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		return true;
	}

	/**
	 * Save settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function save_settings( $request ) {
		global $current_section, $current_tab;

		$params = $request->get_params();
		// Get all registered WooCommerce settings pages
		$settings_pages = WC_Admin_Settings::get_settings_pages();

		// Loop through each settings page and save its settings
		foreach ( $settings_pages as $settings_page ) {
			if ( $settings_page->get_id() === $params['tab'] ) {
				if ( method_exists( $settings_page, 'save' ) ) {
					$current_section = $params['section'];
					$current_tab = $params['tab'];
					$settings_page->save();
				}
			}
		}

		return new \WP_REST_Response( array( 'status' => 'success' ) );
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'options',
			'type'       => 'object',
			'properties' => array(
				'options' => array(
					'type'        => 'array',
					'description' => __( 'Array of options with associated values.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}
}