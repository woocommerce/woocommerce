<?php
/**
 * REST API Tax Settings Controller
 *
 * Handles requests to the /settings/tax endpoints.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Tax;

use WP_Error;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Tax\Schema\TaxSettingsSchema;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WC_Settings_Tax;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Tax Settings Controller Class.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/tax';

	/**
	 * WC_Settings_Tax instance.
	 *
	 * @var WC_Settings_Tax
	 */
	protected $settings_tax_instance;

	/**
	 * Schema instance.
	 *
	 * @var TaxSettingsSchema
	 */
	protected $item_schema;

	/**
	 * Initialize the controller.
	 *
	 * @param TaxSettingsSchema $item_schema Schema class.
	 * @internal
	 */
	final public function init( TaxSettingsSchema $item_schema ) {
		$this->item_schema = $item_schema;
	}

	/**
	 * Get the WC_Settings_Tax instance.
	 *
	 * @return WC_Settings_Tax
	 */
	private function get_settings_tax_instance() {
		if ( is_null( $this->settings_tax_instance ) ) {
			$this->settings_tax_instance = new WC_Settings_Tax();
		}
		return $this->settings_tax_instance;
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
	 * Check permissions for reading tax settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );

		}
		return true;
	}

	/**
	 * Check permissions for updating tax settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );

		}
		return true;
	}

	/**
	 * Get tax settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$settings = $this->get_all_settings();
		return $this->prepare_item_for_response( $settings, $request );
	}

	/**
	 * Update tax settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) || empty( $params ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_param',
				__( 'Invalid or empty request body.', 'woocommerce' ),
				400
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

		// Get all tax settings definitions.
		$settings       = $this->get_all_settings();
		$settings_by_id = array_column( $settings, null, 'id' );

		// Exclude non-editable markers like 'title' and 'sectionend'.
		$settings_by_id = array_filter(
			$settings_by_id,
			static function ( $def ) {
				$type = $def['type'] ?? '';
				return isset( $def['id'] ) && ! in_array( $type, array( 'title', 'sectionend', 'conflict_error', 'add_settings_slot' ), true );
			}
		);

		$valid_setting_ids  = array_keys( $settings_by_id );
		$validated_settings = array();

		// Process each setting in the payload.
		foreach ( $values_to_update as $setting_id => $setting_value ) {
			// Sanitize the setting ID.
			$setting_id = sanitize_text_field( $setting_id );

			// Security check: only allow updating valid WooCommerce tax settings.
			if ( ! in_array( $setting_id, $valid_setting_ids, true ) ) {
				continue;
			}

			// Sanitize the value based on the setting type.
			$setting_definition = $settings_by_id[ $setting_id ];
			$setting_type       = $setting_definition['type'] ?? 'text';
			$sanitized_value    = $this->sanitize_setting_value( $setting_type, $setting_value );

			// Additional validation for specific settings.
			$validation_result = $this->validate_setting_value( $setting_definition, $sanitized_value );
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
			 * Fires when WooCommerce settings are updated.
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
		return $this->prepare_item_for_response( $settings, $request );
	}

	/**
	 * Validate a setting value before updating.
	 *
	 * @param array $setting Setting definition.
	 * @param mixed $value      Setting value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_setting_value( $setting, $value ) {
		$setting_id = $setting['id'] ?? '';
		$options    = $setting['options'] ?? array();

		if ( empty( $options ) ) {
			return true;
		}

		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo Fix ciab-next plugin's settings transformation.
		// The ciab-next plugin filters 'woocommerce_tax_settings' and transforms options from the simple
		// WooCommerce format to a structured format, also clearing out the 'title' field. This causes
		// validation issues in the REST API. The plugin should preserve the original format or only
		// transform settings for UI display purposes without affecting the REST API.

		// Handle both formats of options since plugins can filter the settings structure.
		// Original WooCommerce format: array( 'yes' => 'Label', 'no' => 'Label' )
		// Transformed format (e.g., ciab-next plugin): array( 0 => array( 'value' => 'yes', 'label' => 'Label', 'desc' => '...' ) )
		// Without this, validation extracts ['0', '1'] instead of ['yes', 'no'], causing valid requests to fail.
		$allowed_values = array();
		foreach ( $options as $key => $option ) {
			if ( is_array( $option ) && isset( $option['value'] ) ) {
				// Structured format - extract the value.
				$allowed_values[] = (string) $option['value'];
			} else {
				// Simple format - use the key.
				$allowed_values[] = (string) $key;
			}
		}

		// Normalize value to array for consistent validation.
		$check_values = is_array( $value ) ? array_map( 'strval', $value ) : array( (string) $value );

		$invalid_values = array_diff( $check_values, $allowed_values );
		if ( ! empty( $invalid_values ) ) {
			// Note: Using setting_id instead of setting_label because plugins can filter settings
			// and clear the 'title' field (e.g., ciab-next), making the label unreliable.
			// The setting_id is always present and provides a clear, machine-readable identifier.
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_param',
				sprintf(
				/* translators: 1: Setting ID, 2: Allowed values list. */
					__( 'Invalid value for "%1$s". Allowed values: %2$s.', 'woocommerce' ),
					$setting_id,
					implode( ', ', $allowed_values )
				),
				400
			);
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
	private function sanitize_setting_value( $setting_type, $value ) {
		// Normalize WooCommerce setting types to REST API schema types.
		$type_map     = array(
			'single_select_country'  => 'select',
			'multi_select_countries' => 'multiselect',
		);
		$setting_type = $type_map[ $setting_type ] ?? $setting_type;

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

			case 'radio':
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

			case 'textarea':
				return sanitize_textarea_field( $value );

			default:
				// If a type is not explicitly handled, treat it as text.
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get all tax settings definitions.
	 *
	 * @return array Array of setting definitions.
	 */
	private function get_all_settings(): array {
		$settings_instance = $this->get_settings_tax_instance();
		$settings          = $settings_instance->get_settings_for_section( '' );

		return $settings;
	}

	/**
	 * Get the schema for the current resource.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Get the item response for a single settings group.
	 *
	 * @param mixed           $item Settings data.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $item, $request );
	}
}
