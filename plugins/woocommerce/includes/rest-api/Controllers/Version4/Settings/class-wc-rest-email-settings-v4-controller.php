<?php
/**
 * REST API Email Settings controller
 *
 * Handles requests to the /settings/email endpoints for WooCommerce API v4.
 *
 * @package WooCommerce\RestApi
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Email Settings controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_V4_Controller
 */
class WC_REST_Email_Settings_V4_Controller extends WC_REST_V4_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/email';

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
	 * Check permissions for reading email settings.
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
		return array(
			'woocommerce_email_from_name'        => array(
				'description' => __( 'Email sender name.', 'woocommerce' ),
				'type'        => 'string',
				'required'    => false,
			),
			'woocommerce_email_from_address'     => array(
				'description' => __( 'Email sender address.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'email',
				'required'    => false,
			),
			'woocommerce_email_reply_to_enabled' => array(
				'description' => __( 'Enable reply-to email address.', 'woocommerce' ),
				'type'        => 'boolean',
				'required'    => false,
			),
			'woocommerce_email_reply_to_name'    => array(
				'description' => __( 'Reply-to name.', 'woocommerce' ),
				'type'        => 'string',
				'required'    => false,
			),
			'woocommerce_email_reply_to_address' => array(
				'description' => __( 'Reply-to email address.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'email',
				'required'    => false,
			),
		);
	}

	/**
	 * Check permissions for updating email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		return $this->check_permissions( $request, 'edit' );
	}

	/**
	 * Get email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$settings = $this->get_email_settings_data();
		return rest_ensure_response( $settings );
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
		$response_data = $this->get_email_settings_data();
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
	 * Get email settings data by transforming email settings into REST API format.
	 *
	 * @return array
	 */
	private function get_email_settings_data() {
		$fields = array(
			array(
				'id'    => 'woocommerce_email_from_name',
				'label' => __( '"FROM" Name', 'woocommerce' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'woocommerce_email_from_address',
				'label' => __( '"FROM" Address', 'woocommerce' ),
				'type'  => 'email',
			),
			array(
				'id'          => 'woocommerce_email_reply_to_enabled',
				'label'       => __( 'Add "Reply-to" email', 'woocommerce' ),
				'type'        => 'boolean',
				'description' => __( 'Use a different email address for replies.', 'woocommerce' ),
			),
			array(
				'id'    => 'woocommerce_email_reply_to_name',
				'label' => __( '"Reply-to" Name', 'woocommerce' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'woocommerce_email_reply_to_address',
				'label' => __( '"Reply-to" Address', 'woocommerce' ),
				'type'  => 'email',
			),
		);

		$values = array(
			'woocommerce_email_from_name'        => get_option( 'woocommerce_email_from_name', get_option( 'blogname' ) ),
			'woocommerce_email_from_address'     => get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) ),
			'woocommerce_email_reply_to_enabled' => get_option( 'woocommerce_email_reply_to_enabled', 'no' ) === 'yes',
			'woocommerce_email_reply_to_name'    => get_option( 'woocommerce_email_reply_to_name', '' ),
			'woocommerce_email_reply_to_address' => get_option( 'woocommerce_email_reply_to_address', '' ),
		);

		return array(
			'id'          => 'email',
			'title'       => __( 'Email design', 'woocommerce' ),
			'description' => __( 'Customize the look and feel of all you notification emails.', 'woocommerce' ),
			'values'      => $values,
			'groups'      => array(
				'sender_details' => array(
					'title'       => __( 'Sender details', 'woocommerce' ),
					'description' => __( 'This is how your sender name and email address would appear in outgoing emails.', 'woocommerce' ),
					'order'       => 1,
					'fields'      => $fields,
				),
			),
		);
	}

	/**
	 * Get the schema for email settings, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'email_settings',
			'type'       => 'object',
			'properties' => array(
				'id'                                 => array(
					'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'                              => array(
					'description' => __( 'Settings title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'                        => array(
					'description' => __( 'Settings description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'values'                             => array(
					'description' => __( 'Flattened setting values.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'groups'                             => array(
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
				'woocommerce_email_from_name'        => array(
					'description' => __( 'Email sender name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'woocommerce_email_from_address'     => array(
					'description' => __( 'Email sender address.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
				),
				'woocommerce_email_reply_to_enabled' => array(
					'description' => __( 'Enable reply-to email address.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'woocommerce_email_reply_to_name'    => array(
					'description' => __( 'Reply-to name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'woocommerce_email_reply_to_address' => array(
					'description' => __( 'Reply-to email address.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
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
				'id'          => array(
					'description' => __( 'Setting field ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'label'       => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'        => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'email', 'boolean' ),
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'Setting field description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
	}
}
