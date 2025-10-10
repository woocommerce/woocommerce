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
		$settings = null;
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

		// Handle nested values structure - extract values if they exist.
		$settings_data = isset( $params['values'] ) && is_array( $params['values'] ) ? $params['values'] : $params;

		// Define valid email settings.
		$valid_settings     = array( 'woocommerce_email_from_name', 'woocommerce_email_from_address', 'woocommerce_email_reply_to_enabled', 'woocommerce_email_reply_to_name', 'woocommerce_email_reply_to_address' );
		$validated_settings = array();

		$reply_to_enabled = get_option( 'woocommerce_email_reply_to_enabled', 'no' );
		if ( isset( $settings_data['woocommerce_email_reply_to_enabled'] ) ) {
			$reply_to_enabled = $this->sanitize_setting_value( 'woocommerce_email_reply_to_enabled', $settings_data['woocommerce_email_reply_to_enabled'] );
		}

		// Process each setting in the payload.
		foreach ( $settings_data as $setting_id => $setting_value ) {
			// Sanitize the setting ID.
			$setting_id = sanitize_text_field( $setting_id );

			// Security check: only allow updating valid email settings.
			if ( ! in_array( $setting_id, $valid_settings, true ) ) {
				continue;
			}

			// Sanitize and validate the value.
			$sanitized_value   = $this->sanitize_setting_value( $setting_id, $setting_value );
			$validation_result = $this->validate_setting_value( $setting_id, $sanitized_value, $reply_to_enabled );

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

		// Return updated settings.
		$settings      = null;
		$response_data = $this->get_item_response( $settings, $request );
		return rest_ensure_response( $response_data );
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
	 * Sanitize setting value based on setting ID.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Setting value.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_setting_value( $setting_id, $value ) {
		switch ( $setting_id ) {
			case 'woocommerce_email_from_name':
			case 'woocommerce_email_from_address':
			case 'woocommerce_email_reply_to_name':
			case 'woocommerce_email_reply_to_address':
				return sanitize_text_field( $value );

			case 'woocommerce_email_reply_to_enabled':
				// Convert to boolean and store as string for WordPress options.
				if ( is_string( $value ) ) {
					$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
				}
				return $value ? 'yes' : 'no';

			default:
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
