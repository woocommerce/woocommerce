<?php
/**
 * REST API Email Settings Controller
 *
 * Handles requests to the /settings/email endpoints.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Email;

use WP_Error;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Email\Schema\EmailSettingsSchema;
use WC_Settings_Emails;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Email Settings Controller Class.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/email';

	/**
	 * WC_Settings_Emails instance.
	 *
	 * @var WC_Settings_Emails
	 */
	protected $settings_emails_instance;

	/**
	 * Schema instance.
	 *
	 * @var EmailSettingsSchema
	 */
	protected $schema;

	/**
	 * Initialize the controller.
	 *
	 * @param EmailSettingsSchema $schema Schema class.
	 * @internal
	 */
	final public function init( EmailSettingsSchema $schema ) {
		$this->schema = $schema;
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
	 * Get the WC_Settings_Emails instance.
	 *
	 * @return WC_Settings_Emails
	 */
	private function get_settings_emails_instance() {
		if ( is_null( $this->settings_emails_instance ) ) {
			$this->settings_emails_instance = new WC_Settings_Emails();
		}
		return $this->settings_emails_instance;
	}

	/**
	 * Get all email settings definitions.
	 *
	 * @return array Array of setting definitions.
	 */
	private function get_all_settings(): array {
		$settings_instance = $this->get_settings_emails_instance();
		$sections          = $settings_instance->get_sections();
		$settings          = array();

		foreach ( array_keys( $sections ) as $section ) {
			$section_settings = $settings_instance->get_settings_for_section( $section );
			$settings         = array_merge( $settings, $section_settings );
		}

		return $settings;
	}

	/**
	 * Check permissions for reading email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access email settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check permissions for updating email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to edit email settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		try {
			$settings = $this->get_all_settings();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_email_settings_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		$response = $this->get_item_response( $settings, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Update email settings.
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

		// Get all email settings definitions.
		$settings       = $this->get_all_settings();
		$settings_by_id = array_column( $settings, null, 'id' );

		// Exclude non-editable markers like 'title' and 'sectionend, ...'.
		$settings_by_id = array_filter(
			$settings_by_id,
			static function ( $def ) {
				$type = $def['type'] ?? '';
				return isset( $def['id'] ) && ! in_array( $type, EmailSettingsSchema::NON_EDITABLE_TYPES, true );
			}
		);

		$valid_setting_ids  = array_keys( $settings_by_id );
		$validated_settings = array();

		// Get reply_to_enabled for validation context.
		$reply_to_enabled = get_option( 'woocommerce_email_reply_to_enabled', 'no' );
		if ( isset( $values_to_update['woocommerce_email_reply_to_enabled'] ) ) {
			$reply_to_enabled = wc_bool_to_string( $values_to_update['woocommerce_email_reply_to_enabled'] );
		}

		// Process each setting in the payload.
		foreach ( $values_to_update as $setting_id => $setting_value ) {
			// Sanitize the setting ID.
			$setting_id = sanitize_text_field( $setting_id );

			// Security check: only allow updating valid WooCommerce email settings.
			if ( ! in_array( $setting_id, $valid_setting_ids, true ) ) {
				continue;
			}

			// Sanitize the value based on the setting type.
			$setting_definition = $settings_by_id[ $setting_id ];
			$setting_type       = $setting_definition['type'] ?? 'text';
			$sanitized_value    = $this->sanitize_setting_value( $setting_type, $setting_value );

			// Additional validation for specific settings.
			$validation_result = $this->validate_setting_value( $setting_id, $sanitized_value, $reply_to_enabled );
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
	 * @param string $reply_to_enabled Reply-to enabled.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_setting_value( $setting_id, $value, $reply_to_enabled ) {
		$check_reply_to = 'yes' === $reply_to_enabled;
		switch ( $setting_id ) {
			case 'woocommerce_email_from_name':
				if ( empty( $value ) || ! is_string( $value ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Email sender name cannot be empty.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_email_from_address':
				if ( empty( $value ) || ! is_email( $value ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Please enter a valid email address.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_email_reply_to_enabled':
				// Convert string 'true'/'false' to boolean if needed.
				if ( is_string( $value ) ) {
					$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
				}
				if ( ! is_bool( $value ) && null !== $value ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Reply-to enabled must be a boolean value.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_email_reply_to_name':
				// Only validate if reply-to is enabled.
				if ( $check_reply_to && ( empty( $value ) || ! is_string( $value ) ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Reply-to name cannot be empty when reply-to is enabled.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				break;

			case 'woocommerce_email_reply_to_address':
				// Only validate if reply-to is enabled.
				if ( $check_reply_to && ( empty( $value ) || ! is_email( $value ) ) ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'Please enter a valid reply-to email address.', 'woocommerce' ),
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
	private function sanitize_setting_value( $setting_type, $value ) {
		switch ( $setting_type ) {
			case 'text':
			case 'select':
			case 'color':
				return sanitize_text_field( $value );

			case 'email':
				return sanitize_email( $value );

			case 'checkbox':
				// Ensure we have a scalar value for checkbox settings.
				if ( is_array( $value ) ) {
					$value = ! empty( $value ); // Convert array to boolean based on emptiness.
				}
				return wc_bool_to_string( $value );

			case 'number':
				if ( ! is_numeric( $value ) ) {
					return 0;
				}

				return filter_var( $value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE ) ?? floatval( $value );

			default:
				// If a type is not explicitly handled, treat it as text.
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Get the schema for the current resource.
	 *
	 * @return array
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
