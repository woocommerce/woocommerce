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
	 * WC_Settings_General instance.
	 *
	 * @var WC_Settings_General
	 */
	protected $settings_general_instance;

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
		$args = array(
			'values' => array(
				'description'          => __( 'Flat key-value mapping of setting field values to update.', 'woocommerce' ),
				'type'                 => 'object',
				'required'             => false,
				'additionalProperties' => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
				),
			),
		);

		return $args;
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
		$settings = $this->get_general_settings_data();
		return rest_ensure_response( $settings );
	}

	/**
	 * Update general settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$updated_settings = array();

		// Get all parameters from the request body.
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

		// Get all general settings definitions.
		$settings           = $this->get_settings_general_instance()->get_settings_for_section( '' );
		$settings_by_id     = array_column( $settings, null, 'id' );
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

		// Return updated settings.
		$response_data = $this->get_general_settings_data();
		return rest_ensure_response( $response_data );
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
				if ( ! is_numeric( $value ) || $value < 0 || $value > 10 ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Number of decimals must be between 0 and 10.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_default_country':
				// Validate country code format (e.g., "US" or "US:CA").
				if ( ! empty( $value ) && ! preg_match( '/^[A-Z]{2}(:[A-Z0-9]+)?$/', $value ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Invalid country/state format.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}

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
		switch ( $setting_type ) {
			case 'text':
				return sanitize_text_field( $value );

			case 'number':
				return is_numeric( $value ) ? floatval( $value ) : 0;

			case 'checkbox':
				return wc_bool_to_string( $value );

			case 'select':
			case 'single_select_country':
				return sanitize_text_field( $value );

			case 'multiselect':
			case 'multi_select_countries':
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', $value );
				}

				// Handle empty values and string inputs.
				if ( empty( $value ) ) {
					return array();
				}

				// If it's a string, convert to array (for single values).
				return is_string( $value ) ? array( sanitize_text_field( $value ) ) : array();

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get the display order for a settings group.
	 *
	 * @param array $setting  Setting definition array.
	 * @return int Display order.
	 */
	private function get_group_order( $setting ) {
		if ( isset( $setting['order'] ) && is_numeric( $setting['order'] ) ) {
			return (int) $setting['order'];
		}

		return 999;
	}

	/**
	 * Get general settings data by transforming WC_Settings_General data into REST API format.
	 *
	 * @return array
	 */
	private function get_general_settings_data() {
		$settings_general = $this->get_settings_general_instance();
		$raw_settings     = $settings_general->get_settings_for_section( '' );

		// Transform raw settings into grouped format.
		$groups            = array();
		$current_group     = null;
		$current_group_key = null;
		$values            = array();

		foreach ( $raw_settings as $setting ) {
			$setting_type = $setting['type'] ?? '';

			// Handle section titles.
			if ( 'title' === $setting_type ) {
				$current_group_key = $setting['id'] ?? '';
				$current_group     = array(
					'title'       => $setting['title'] ?? '',
					'description' => $setting['desc'] ?? '',
					'order'       => $this->get_group_order( $setting ),
					'fields'      => array(),
				);
				continue;
			}

			// Handle section ends.
			if ( 'sectionend' === $setting_type ) {
				if ( $current_group && $current_group_key ) {
					$groups[ $current_group_key ] = $current_group;
				}
				$current_group     = null;
				$current_group_key = null;
				continue;
			}

			// Skip non-field types.
			if ( in_array( $setting_type, array( 'title', 'sectionend' ), true ) ) {
				continue;
			}

			// Convert setting to field format.
			if ( $current_group && isset( $setting['id'] ) ) {
				$field = $this->transform_setting_to_field( $setting );
				if ( $field ) {
					$current_group['fields'][] = $field;
					// Add field value to the flat values array.
					$values[ $field['id'] ] = get_option( $field['id'], $setting['default'] ?? '' );
				}
			}
		}

		return array(
			'id'          => 'general',
			'title'       => __( 'General', 'woocommerce' ),
			'description' => __( 'Set your store\'s address, visibility, currency, language, and timezone.', 'woocommerce' ),
			'values'      => $values,
			'groups'      => $groups,
		);
	}

	/**
	 * Transform a WooCommerce setting into REST API field format.
	 *
	 * @param array $setting WooCommerce setting array.
	 * @return array|null Transformed field or null if should be skipped.
	 */
	private function transform_setting_to_field( $setting ) {
		$setting_id   = $setting['id'] ?? '';
		$setting_type = $setting['type'] ?? 'text';

		// Skip certain settings that shouldn't be exposed via REST API.
		// This is a temporary array until designs are finalized.
		$skip_settings = array(
			'woocommerce_address_autocomplete_enabled',
			'woocommerce_address_autocomplete_provider',
		);

		if ( in_array( $setting_id, $skip_settings, true ) ) {
			return null;
		}

		$field = array(
			'id'    => $setting_id,
			'label' => $setting['title'] ?? $setting_id,
			'type'  => $this->normalize_field_type( $setting_type ),
			'desc'  => $setting['desc'] ?? '',
		);

		// Add options for select fields.
		if ( isset( $setting['options'] ) && is_array( $setting['options'] ) ) {
			$field['options'] = $setting['options'];
		} else {
			// Generate options for special field types that don't have them in the setting definition.
			$field['options'] = $this->get_field_options( $setting_type, $setting_id );
		}

		return $field;
	}

	/**
	 * Get options for specific field types.
	 *
	 * @param string $field_type Field type.
	 * @param string $field_id Field ID.
	 * @return array Field options.
	 */
	private function get_field_options( $field_type, $field_id ) {
		switch ( $field_type ) {
			case 'single_select_country':
				return $this->get_country_state_options();

			case 'multi_select_countries':
				return WC()->countries->get_countries();

			case 'select':
				// Handle specific select fields that need custom options.
				if ( 'woocommerce_currency' === $field_id ) {
					return $this->get_currency_options();
				}
				break;
		}

		return array();
	}

	/**
	 * Get country/state options for single select country field.
	 *
	 * @return array Country/state options.
	 */
	private function get_country_state_options() {
		$countries             = WC()->countries->get_countries();
		$states                = WC()->countries->get_states();
		$country_state_options = array();

		foreach ( $countries as $country_code => $country_name ) {
			$country_states = $states[ $country_code ] ?? array();

			if ( empty( $country_states ) ) {
				$country_state_options[ $country_code ] = $country_name;
			} else {
				foreach ( $country_states as $state_code => $state_name ) {
					$country_state_options[ $country_code . ':' . $state_code ] = $country_name . ' — ' . $state_name;
				}
			}
		}

		return $country_state_options;
	}

	/**
	 * Get currency options.
	 *
	 * @return array Currency options.
	 */
	private function get_currency_options() {
		$currency_options = array();
		$currencies       = get_woocommerce_currencies();

		foreach ( $currencies as $code => $name ) {
			$label                     = wp_specialchars_decode( (string) $name );
			$symbol                    = wp_specialchars_decode( (string) get_woocommerce_currency_symbol( $code ) );
			$currency_options[ $code ] = $label . ' (' . $symbol . ') — ' . $code;
		}

		return $currency_options;
	}

	/**
	 * Normalize WooCommerce field types to REST API field types.
	 *
	 * @param string $wc_type WooCommerce field type.
	 * @return string Normalized field type.
	 */
	private function normalize_field_type( $wc_type ) {
		$type_map = array(
			'single_select_country'  => 'select',
			'multi_select_countries' => 'multiselect',
		);

		return $type_map[ $wc_type ] ?? $wc_type;
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
				'values'      => array(
					'description'          => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
					'type'                 => 'object',
					'context'              => array( 'view', 'edit' ),
					'additionalProperties' => array(
						'description' => __( 'Setting field value.', 'woocommerce' ),
						'type'        => array( 'string', 'number', 'array', 'boolean' ),
					),
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
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'desc'    => array(
					'description' => __( 'Description for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
	}
}
