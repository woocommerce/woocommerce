<?php
/**
 * REST API General Settings controller
 *
 * Handles requests to the /settings/general endpoints for WooCommerce API v4.
 *
 * @package WooCommerce\RestApi
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the required model classes.
require_once __DIR__ . '/../../../Models/class-wc-rest-store-address-model.php';
require_once __DIR__ . '/../../../Models/class-wc-rest-general-options-model.php';
require_once __DIR__ . '/../../../Models/class-wc-rest-taxes-coupons-model.php';
require_once __DIR__ . '/../../../Models/class-wc-rest-currency-options-model.php';

/**
 * REST API General Settings controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_V4_Controller
 */
class WC_REST_General_Settings_V4_Controller extends WC_REST_V4_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/general';

	/**
	 * Settings groups manager.
	 *
	 * @var WC_REST_Settings_Groups_Manager
	 */
	protected $settings_groups_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_settings_groups();
	}

	/**
	 * Initialize settings groups.
	 *
	 * @return void
	 */
	private function init_settings_groups() {
		$this->settings_groups_manager = new WC_REST_Settings_Groups_Manager();

		// Add default settings groups.
		$this->settings_groups_manager->add_group( new WC_REST_Store_Address_Model() );
		$this->settings_groups_manager->add_group( new WC_REST_General_Options_Model() );
		$this->settings_groups_manager->add_group( new WC_REST_Taxes_Coupons_Model() );
		$this->settings_groups_manager->add_group( new WC_REST_Currency_Options_Model() );

		/**
		 * Allow other parts of the code to register additional settings groups.
		 *
		 * @param WC_REST_Settings_Groups_Manager $groups_manager The groups manager instance.
		 * @since 4.0.0
		 */
		do_action( 'woocommerce_rest_general_settings_groups', $this->settings_groups_manager );
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_update_args(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check permissions for reading general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return $this->check_permissions( $request, 'read' );
	}

	/**
	 * Get update arguments for the endpoint.
	 *
	 * @return array
	 */
	private function get_update_args() {
		return $this->settings_groups_manager->get_update_args();
	}


	/**
	 * Check permissions for updating general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		return $this->check_permissions( $request, 'edit' );
	}

	/**
	 * Get general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$groups_data = $this->settings_groups_manager->get_all_groups_data();

		$response_data = array(
			'id'          => 'general',
			'title'       => __( 'General', 'woocommerce' ),
			'description' => __( 'Set your store\'s address, visibility, currency, language, and timezone.', 'woocommerce' ),
			'groups'      => $groups_data,
		);

		return rest_ensure_response( $response_data );
	}

	/**
	 * Update general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		// Get all parameters from the request body.
		$params = $request->get_json_params();

		if ( ! is_array( $params ) || empty( $params ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid or empty request body.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Update settings across all groups with transactional behavior.
		$updated_settings = $this->settings_groups_manager->update_settings( $params );

		// Check if validation failed (WP_Error returned).
		if ( is_wp_error( $updated_settings ) ) {
			return $updated_settings;
		}

		// Log the update if settings were changed.
		if ( ! empty( $updated_settings ) ) {
			$all_updated_settings = array();
			foreach ( $updated_settings as $group_id => $group_updated ) {
				$all_updated_settings = array_merge( $all_updated_settings, $group_updated );
			}

			/**
			* Fires when WooCommerce settings are updated.
			*
			* @param array $all_updated_settings Array of updated settings IDs.
			* @param string $rest_base The REST base of the settings.
			* @since 4.0.0
			*/
			do_action( 'woocommerce_settings_updated', $all_updated_settings, $this->rest_base );
		}

		// Return updated settings.
		$groups_data = $this->settings_groups_manager->get_all_groups_data();

		$response_data = array(
			'id'          => 'general',
			'title'       => __( 'General', 'woocommerce' ),
			'description' => __( 'Set your store\'s address, visibility, currency, language, and timezone.', 'woocommerce' ),
			'groups'      => $groups_data,
		);

		return rest_ensure_response( $response_data );
	}




	/**
	 * Get the schema for general settings, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'general_settings',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'       => array(
					'description' => __( 'Settings title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Settings description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'groups'      => array(
					'description'          => __( 'Collection of setting groups.', 'woocommerce' ),
					'type'                 => 'object',
					'context'              => array( 'view', 'edit' ),
					'additionalProperties' => array(
						'type'        => 'object',
						'description' => __( 'Settings group.', 'woocommerce' ),
						'properties'  => array(
							'title'       => array(
								'description' => __( 'Group title.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'description' => array(
								'description' => __( 'Group description.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'order'       => array(
								'description' => __( 'Display order for the group.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'fields'      => array(
								'description' => __( 'Settings fields.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => $this->get_field_schema(),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the schema for individual setting fields.
	 *
	 * @return array
	 */
	private function get_field_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'      => array(
					'description' => __( 'Setting field ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'label'   => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'    => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'number', 'select', 'multiselect', 'checkbox' ),
					'context'     => array( 'view', 'edit' ),
				),
				'value'   => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
					'context'     => array( 'view', 'edit' ),
				),
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'tip'     => array(
					'description' => __( 'Help text for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'order'   => array(
					'description' => __( 'Display order for the field.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
	}
}
