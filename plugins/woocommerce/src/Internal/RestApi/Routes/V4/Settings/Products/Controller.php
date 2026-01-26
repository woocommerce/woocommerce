<?php
/**
 * REST API Product Settings controller
 *
 * Handles requests to the /settings/products endpoints.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Products;

use WP_Error;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Products\Schema\ProductSettingsSchema;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WC_Settings_Products;

defined( 'ABSPATH' ) || exit;

/**
 * Product Settings controller class.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/products';

	/**
	 * Schema class instance.
	 *
	 * @var ProductSettingsSchema
	 */
	protected $schema;

	/**
	 * WC_Settings_Products instance.
	 *
	 * @var \WC_Settings_Products
	 */
	protected $settings_products_instance;

	/**
	 * Initialize dependencies.
	 *
	 * @param ProductSettingsSchema $schema Schema class instance.
	 * @internal
	 */
	final public function init( ProductSettingsSchema $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Get the WC_Settings_Products instance.
	 *
	 * @return \WC_Settings_Products
	 */
	private function get_settings_products_instance() {
		if ( is_null( $this->settings_products_instance ) ) {
			$this->settings_products_instance = new WC_Settings_Products();
		}
		return $this->settings_products_instance;
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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access product settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if a given request has access to update settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to edit product settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get product settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$settings = $this->get_all_settings();

		$response = $this->schema->get_item_response( $settings, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Update product settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) || empty( $params ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid or empty request body.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Check if the request contains a 'values' field with the flat key-value mapping.
		$values_to_update = array();
		if ( isset( $params['values'] ) && is_array( $params['values'] ) ) {
			$values_to_update = $params['values'];
		} else {
			// Fallback to the old format for backward compatibility.
			$values_to_update = $params;
		}

		// Get all product settings definitions.
		$settings           = $this->get_all_settings();
		$settings_by_id     = array_column( $settings, null, 'id' );
		$valid_setting_ids  = array_keys( $settings_by_id );
		$validated_settings = array();

		// Process each setting in the payload.
		foreach ( $values_to_update as $setting_id => $setting_value ) {
			// Sanitize the setting ID.
			$setting_id = sanitize_text_field( $setting_id );

			// Security check: only allow updating valid WooCommerce product settings.
			if ( ! in_array( $setting_id, $valid_setting_ids, true ) ) {
				continue;
			}

			// Sanitize the value based on the setting type.
			$setting_definition = $settings_by_id[ $setting_id ];
			$setting_type       = $setting_definition['type'] ?? 'text';
			$sanitized_value    = $this->sanitize_setting_value( $setting_type, $setting_value );

			// Additional validation for specific settings.
			$validation_result = $this->validate_setting_value( $setting_id, $sanitized_value );
			if ( is_wp_error( $validation_result ) ) {
				return $validation_result;
			}

			// Store validated values first.
			$validated_settings[ $setting_id ] = $sanitized_value;
		}

		// After validation loop, update all settings.
		$updated_settings = array();
		foreach ( $validated_settings as $setting_id => $value ) {
			$update_result = update_option( $setting_id, $value );
			if ( $update_result ) {
				$updated_settings[] = $setting_id;
			}
		}

		// Log the update if settings were changed.
		if ( ! empty( $updated_settings ) ) {
			/**
			* Fires when WooCommerce product settings are updated.
			*
			* @param array $updated_settings Array of updated settings IDs.
			* @param string $rest_base The REST base of the settings.
			* @since 4.0.0
			*/
			do_action( 'woocommerce_settings_updated', $updated_settings, $this->rest_base );
		}

		// Get all settings after update.
		$settings = $this->get_all_settings();

		// Return updated settings.
		$response = $this->schema->get_item_response( $settings, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Validate a setting value before updating.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Setting value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_setting_value( string $setting_id, $value ) {
		// Custom validation rules for specific product settings.
		switch ( $setting_id ) {
			case 'woocommerce_weight_unit':
				/**
				 * Filter the available weight units.
				 *
				 * @since 10.4.0
				 *
				 * @param array $weight_units Array of weight unit strings.
				 */
				$valid_units = apply_filters( 'woocommerce_weight_units', array( 'kg', 'g', 'lbs', 'oz' ) );
				if ( ! in_array( $value, $valid_units, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid weight unit. Valid units are: kg, g, lbs, oz.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_dimension_unit':
				/**
				 * Filter the available dimension units.
				 *
				 * @since 10.4.0
				 *
				 * @param array $dimension_units Array of dimension unit strings.
				 */
				$valid_units = apply_filters( 'woocommerce_dimension_units', array( 'm', 'cm', 'mm', 'in', 'yd' ) );
				if ( ! in_array( $value, $valid_units, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid dimension unit. Valid units are: m, cm, mm, in, yd.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_product_type':
				$valid_types = array_keys( wc_get_product_types() );
				if ( ! in_array( $value, $valid_types, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid product type.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;
		}

		return true;
	}

	/**
	 * Sanitize setting value based on its type.
	 *
	 * @param string $setting_type Setting type.
	 * @param mixed  $value        Setting value.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_setting_value( string $setting_type, $value ) {
		switch ( $setting_type ) {
			case 'text':
				return sanitize_text_field( $value );

			case 'number':
				if ( ! is_numeric( $value ) ) {
					return 0;
				}

				return filter_var( $value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE ) ?? floatval( $value );

			case 'checkbox':
				// Ensure we have a scalar value for checkbox settings.
				if ( is_array( $value ) ) {
					$value = ! empty( $value ); // Convert array to boolean based on emptiness.
				}
				return wc_bool_to_string( $value );

			case 'select':
				return sanitize_text_field( $value );

			case 'multiselect':
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', $value );
				}

				if ( is_string( $value ) ) {
					return array( sanitize_text_field( $value ) );
				}

				if ( is_scalar( $value ) ) {
					return array( sanitize_text_field( (string) $value ) );
				}

				return array();
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return $this->schema->get_item_schema();
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		return $this->get_schema();
	}

	/**
	 * Prepare a single item for response.
	 *
	 * @param mixed           $item    Object to prepare.
	 * @param WP_REST_Request $request Request object.
	 * @return array Response data.
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $this->schema->get_item_response( $item, $request );
	}

	/**
	 * Get all product settings definitions.
	 *
	 * @return array Array of setting definitions.
	 */
	private function get_all_settings(): array {
		$settings_instance = $this->get_settings_products_instance();
		$sections          = $settings_instance->get_sections();
		$settings          = array();

		foreach ( array_keys( $sections ) as $section ) {
			$section_settings = $settings_instance->get_settings_for_section( $section );
			$settings         = array_merge( $settings, $section_settings );
		}

		return $settings;
	}
}
