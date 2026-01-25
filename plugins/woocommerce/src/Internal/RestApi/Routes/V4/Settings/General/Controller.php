<?php
/**
 * REST API General Settings Controller
 *
 * Handles requests to the /settings/general endpoints.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\General;

use WP_Error;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\General\Schema\GeneralSettingsSchema;
use WC_Settings_General;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * REST API General Settings Controller Class.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/general';

	/**
	 * WC_Settings_General instance.
	 *
	 * @var WC_Settings_General
	 */
	protected $settings_general_instance;

	/**
	 * Schema instance.
	 *
	 * @var GeneralSettingsSchema
	 */
	protected $schema;

	/**
	 * Initialize the controller.
	 *
	 * @param GeneralSettingsSchema $schema Schema class.
	 * @internal
	 */
	final public function init( GeneralSettingsSchema $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Get the WC_Settings_General instance.
	 *
	 * @return WC_Settings_General
	 */
	private function get_settings_general_instance() {
		if ( is_null( $this->settings_general_instance ) ) {
			$this->settings_general_instance = new WC_Settings_General();
		}
		return $this->settings_general_instance;
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
	 * Check permissions for reading general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access general settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check permissions for updating general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to edit general settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		try {
			$settings = $this->get_all_settings();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_general_settings_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		$response = $this->get_item_response( $settings, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Update general settings.
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

		// Filter out the woocommerce_share_key_display field as it's not allowed to be updated via API.
		if ( isset( $values_to_update['woocommerce_share_key_display'] ) ) {
			unset( $values_to_update['woocommerce_share_key_display'] );
		}

		// Get all general settings definitions.
		$settings       = $this->get_all_settings();
		$settings_by_id = array_column( $settings, null, 'id' );

		// Exclude non-editable markers like 'title' and 'sectionend'.
		$settings_by_id = array_filter(
			$settings_by_id,
			static function ( $def ) {
				$type = $def['type'] ?? '';
				return isset( $def['id'] ) && ! in_array( $type, array( 'title', 'sectionend' ), true );
			}
		);

		$valid_setting_ids  = array_keys( $settings_by_id );
		$validated_settings = array();

		// Process each setting in the payload.
		foreach ( $values_to_update as $setting_id => $setting_value ) {
			// Sanitize the setting ID.
			$setting_id = sanitize_text_field( $setting_id );

			// Security check: only allow updating valid WooCommerce general settings.
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
		$response = $this->get_item_response( $settings, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Validate a setting value before updating.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Setting value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_setting_value( $setting_id, $value ) {
		// Custom validation rules for specific settings.
		switch ( $setting_id ) {
			case 'woocommerce_price_num_decimals':
				$int = filter_var( $value, FILTER_VALIDATE_INT );
				if ( false === $int || $int < 0 || $int > 10 ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Number of decimals must be between 0 and 10.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_default_country':
				if ( ! $this->validate_country_or_state_code( $value ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid country/state format.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_allowed_countries':
				$valid_options = array( 'all', 'all_except', 'specific' );
				if ( ! in_array( $value, $valid_options, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid selling location option.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_ship_to_countries':
				$valid_options = array( '', 'all', 'specific', 'disabled' );
				if ( ! in_array( $value, $valid_options, true ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid shipping location option.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_specific_allowed_countries':
			case 'woocommerce_specific_ship_to_countries':
				if ( ! is_array( $value ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Expected an array of country codes.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}

				foreach ( $value as $code ) {
					if ( ! is_string( $code ) || ! $this->validate_country_or_state_code( $code ) ) {
						return new WP_Error(
							'rest_invalid_param',
							__( 'Invalid country code in list.', 'woocommerce' ),
							array( 'status' => 400 )
						);
					}
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
				// If a type is not explicitly handled, treat it as text.
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get all general settings definitions.
	 *
	 * @return array Array of setting definitions.
	 */
	private function get_all_settings(): array {
		$settings_instance = $this->get_settings_general_instance();
		$sections          = $settings_instance->get_sections();
		$settings          = array();

		foreach ( array_keys( $sections ) as $section ) {
			$section_settings = $settings_instance->get_settings_for_section( $section );
			$settings         = array_merge( $settings, $section_settings );
		}

		return $settings;
	}

	/**
	 * Validate country or state code.
	 *
	 * @param string $country_or_state Country or state code.
	 * @return boolean Valid or not valid.
	 */
	private function validate_country_or_state_code( $country_or_state ) {
		list( $country, $state ) = array_pad( explode( ':', (string) $country_or_state, 2 ), 2, '' );
		if ( '' === $country ) {
			return false;
		}
		$country_codes = array_keys( WC()->countries->get_countries() );
		if ( ! in_array( $country, $country_codes, true ) ) {
			return false;
		}
		if ( '' === $state ) {
			return true;
		}
		$states_for_country = WC()->countries->get_states( $country );
		if ( empty( $states_for_country ) ) {
			return false;
		}
		return isset( $states_for_country[ $state ] );
	}

	/**
	 * Get the schema for the current resource.
	 *
	 * @return array
	 */
	public function get_schema(): array {
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
	 * Get the item response for a single settings group.
	 *
	 * @param mixed           $item Settings data.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $this->schema->get_item_response( $item, $request );
	}

	/**
	 * Get the endpoint args for item schema.
	 *
	 * @param string $method HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ): array {
		return rest_get_endpoint_args_for_schema( $this->get_item_schema(), $method );
	}
}
