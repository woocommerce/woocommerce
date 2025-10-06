<?php
/**
 * PaymentGatewaySchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;
use WC_Payment_Gateway;
use WP_Error;
use WP_REST_Request;

/**
 * PaymentGatewaySchema class.
 *
 * Defines the schema for payment gateway objects in the REST API.
 *
 * The `settings` property is an object where keys are arbitrary setting IDs
 * and values are setting configuration objects with the following structure:
 *
 * - id (string, readonly): A unique identifier for the setting
 * - label (string, readonly): A human readable label for the setting used in interfaces
 * - description (string, readonly): A human readable description for the setting used in interfaces
 * - type (string, readonly): Type of setting (text, email, number, color, password, textarea, select, multiselect, radio, image_width, checkbox)
 * - value (string): Setting value
 * - default (string, readonly): Default value for the setting
 * - tip (string, readonly): Additional help text shown to the user about the setting
 * - placeholder (string, readonly): Placeholder text to be displayed in text inputs
 * - options (object, optional): Available options for select/multiselect type settings
 */
class PaymentGatewaySettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'payment-gateway';

	/**
	 * Map of gateway IDs to their special fields that require custom handling.
	 *
	 * @var array
	 */
	private const GATEWAY_SPECIAL_FIELDS = array(
		'bacs' => array(
			'account_details' => array(
				'option_name' => 'woocommerce_bacs_accounts',
				'type'        => 'array',
				'label'       => 'Account details',
				'description' => 'Bank account details for direct bank transfer.',
				'default'     => array(),
			),
		),
	);

	/**
	 * Return all properties for the item schema.
	 *
	 * Note that context determines under which context data should be visible. For example, edit would be the context
	 * used when getting records with the intent of editing them. embed context allows the data to be visible when the
	 * item is being embedded in another response.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'                 => array(
				'description' => __( 'Payment gateway ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'              => array(
				'description' => __( 'Payment gateway title on checkout.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'description'        => array(
				'description' => __( 'Payment gateway description on checkout.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'order'              => array(
				'description' => __( 'Payment gateway sort order.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'enabled'            => array(
				'description' => __( 'Payment gateway enabled status.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'method_title'       => array(
				'description' => __( 'Payment gateway method title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'method_description' => array(
				'description' => __( 'Payment gateway method description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'method_supports'    => array(
				'description' => __( 'Supported features for this payment gateway.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'items'       => array(
					'type' => 'string',
				),
			),
			'values'             => array(
				'description'          => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => array( 'view', 'edit' ),
				'additionalProperties' => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
				),
			),
			'groups'             => array(
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
		);
	}

	/**
	 * Get the schema for individual setting fields.
	 *
	 * @return array
	 */
	private function get_field_schema(): array {
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

	/**
	 * Get flat key-value mapping of all setting values.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	private function get_values( WC_Payment_Gateway $gateway ): array {
		$values = array();
		$gateway->init_form_fields();

		foreach ( $gateway->form_fields as $id => $field ) {
			$field_type = $field['type'] ?? '';

			// Skip non-data fields.
			if ( in_array( $field_type, array( 'title', 'sectionend' ), true ) ) {
				continue;
			}

			// Get value from gateway settings.
			$values[ $id ] = $gateway->settings[ $id ] ?? ( $field['default'] ?? '' );
		}

		// Add special fields for this gateway.
		$special_fields = $this->get_special_field_values( $gateway );
		$values         = array_merge( $values, $special_fields );

		return $values;
	}

	/**
	 * Get values for gateway-specific special fields.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	private function get_special_field_values( WC_Payment_Gateway $gateway ): array {
		$values = array();

		if ( ! isset( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ] ) ) {
			return $values;
		}

		foreach ( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ] as $field_id => $field_config ) {
			$option_name         = $field_config['option_name'];
			$default             = $field_config['default'] ?? array();
			$values[ $field_id ] = get_option( $option_name, $default );
		}

		return $values;
	}

	/**
	 * Get grouped settings structure with field metadata.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	private function get_groups( WC_Payment_Gateway $gateway ): array {
		// Check if gateway has custom grouping.
		$custom_groups = $this->get_custom_groups_for_gateway( $gateway );
		if ( ! empty( $custom_groups ) ) {
			return $custom_groups;
		}

		// Default: single group with all fields.
		return $this->get_default_group( $gateway );
	}

	/**
	 * Get custom groups for specific gateways.
	 * Extensible - add more gateway-specific groupings as needed.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	private function get_custom_groups_for_gateway( WC_Payment_Gateway $gateway ): array {
		// Currently no custom groupings, but structure is ready for future additions.
		// Example for future:
		// switch ( $gateway->id ) {
		// case 'bacs':
		// return $this->get_bacs_groups( $gateway );
		// case 'custom_gateway':
		// return $this->get_custom_gateway_groups( $gateway );
		// }

		return array();
	}

	/**
	 * Get default single group with all gateway fields.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	private function get_default_group( WC_Payment_Gateway $gateway ): array {
		$gateway->init_form_fields();

		$group = array(
			'title'       => __( 'Settings', 'woocommerce' ),
			'description' => '',
			'order'       => 1,
			'fields'      => array(),
		);

		foreach ( $gateway->form_fields as $id => $field ) {
			$field_type = $field['type'] ?? '';

			// Skip non-data fields and top-level fields (handled separately).
			if ( in_array( $field_type, array( 'title', 'sectionend' ), true ) ||
				in_array( $id, array( 'enabled', 'description', 'title' ), true ) ) {
				continue;
			}

			$group['fields'][] = $this->transform_field_to_schema( $id, $field, $gateway );
		}

		// Add special fields.
		$special_fields  = $this->get_special_field_schemas( $gateway );
		$group['fields'] = array_merge( $group['fields'], $special_fields );

		if ( empty( $group['fields'] ) ) {
			return array();
		}

		return array( 'settings' => $group );
	}

	/**
	 * Get field schemas for gateway-specific special fields.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	private function get_special_field_schemas( WC_Payment_Gateway $gateway ): array {
		$fields = array();

		if ( ! isset( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ] ) ) {
			return $fields;
		}

		foreach ( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ] as $field_id => $field_config ) {
			$fields[] = array(
				'id'    => $field_id,
				'label' => $field_config['label'] ?? ucwords( str_replace( '_', ' ', $field_id ) ),
				'type'  => $field_config['type'],
				'desc'  => $field_config['description'] ?? '',
			);
		}

		return $fields;
	}

	/**
	 * Transform WooCommerce field definition to API field schema.
	 *
	 * @param string             $id      Field ID.
	 * @param array              $field   Field definition.
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	private function transform_field_to_schema( string $id, array $field, WC_Payment_Gateway $gateway ): array {
		$schema_field = array(
			'id'    => $id,
			'label' => $field['title'] ?? $field['label'] ?? '',
			'type'  => $this->normalize_field_type( $field['type'] ?? 'text' ),
			'desc'  => $field['description'] ?? '',
		);

		// Add options for select/multiselect fields.
		if ( ! empty( $field['options'] ) &&
			in_array( $schema_field['type'], array( 'select', 'multiselect' ), true ) ) {
			$schema_field['options'] = $field['options'];
		}

		return $schema_field;
	}

	/**
	 * Normalize WooCommerce field types to standard REST API types.
	 *
	 * @param string $wc_type WooCommerce field type.
	 * @return string
	 */
	private function normalize_field_type( string $wc_type ): string {
		$type_map = array(
			'email'       => 'text',
			'password'    => 'text',
			'textarea'    => 'text',
			'safe_text'   => 'text',
			'color'       => 'text',
			'image_width' => 'text',
			'radio'       => 'select',
		);

		return $type_map[ $wc_type ] ?? $wc_type;
	}

	/**
	 * Return settings associated with this payment gateway.
	 *
	 * Note: Some gateways may conditionally populate the 'options' array for select/multiselect fields
	 * based on context (e.g., only when accessing settings pages) for performance reasons.
	 * For example, the COD gateway's `enable_for_methods` field loads shipping method options only
	 * when `is_accessing_settings()` returns true. This means the options array may be empty when
	 * accessed via the REST API, even though the field type is multiselect.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 *
	 * @return array
	 */
	public function get_settings( WC_Payment_Gateway $gateway ): array {
		$settings = array();
		$gateway->init_form_fields();
		foreach ( $gateway->form_fields as $id => $field ) {
			// Make sure we at least have a title and type.
			if ( empty( $field['title'] ) || empty( $field['type'] ) ) {
				continue;
			}

			// Ignore 'enabled' and 'description' which get included elsewhere.
			if ( in_array( $id, array( 'enabled', 'description' ), true ) ) {
				continue;
			}

			$data = array(
				'id'          => $id,
				'label'       => empty( $field['label'] ) ? $field['title'] : $field['label'],
				'description' => empty( $field['description'] ) ? '' : $field['description'],
				'type'        => $field['type'],
				'value'       => empty( $gateway->settings[ $id ] ) ? '' : $gateway->settings[ $id ],
				'default'     => empty( $field['default'] ) ? '' : $field['default'],
				'tip'         => empty( $field['description'] ) ? '' : $field['description'],
				'placeholder' => empty( $field['placeholder'] ) ? '' : $field['placeholder'],
			);
			if ( ! empty( $field['options'] ) ) {
				$data['options'] = $field['options'];
			}
			$settings[ $id ] = $data;
		}
		return $settings;
	}

	/**
	 * Get the item response.
	 *
	 * @param WC_Payment_Gateway $gateway Payment gateway object.
	 * @param WP_REST_Request    $request Request object.
	 * @param array              $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $gateway, WP_REST_Request $request, array $include_fields = array() ): array {
		$order = (array) get_option( 'woocommerce_gateway_order' );
		return array(
			'id'                 => $gateway->id,
			'title'              => $gateway->title,
			'description'        => $gateway->description,
			'order'              => $order[ $gateway->id ] ?? '',
			'enabled'            => ( 'yes' === $gateway->enabled ),
			'method_title'       => $gateway->get_method_title(),
			'method_description' => $gateway->get_method_description(),
			'method_supports'    => $gateway->supports,
			'values'             => $this->get_values( $gateway ),
			'groups'             => $this->get_groups( $gateway ),
		);
	}

	/**
	 * Check if a field is a special field.
	 *
	 * @param string $gateway_id Gateway ID.
	 * @param string $field_id   Field ID.
	 * @return bool
	 */
	public function is_special_field( string $gateway_id, string $field_id ): bool {
		return isset( self::GATEWAY_SPECIAL_FIELDS[ $gateway_id ][ $field_id ] );
	}

	/**
	 * Validate and sanitize standard gateway settings.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @param array              $values  Values to validate and sanitize.
	 * @return array|WP_Error Validated settings or error.
	 */
	public function validate_and_sanitize_settings( WC_Payment_Gateway $gateway, array $values ) {
		$gateway->init_form_fields();
		$validated = array();

		foreach ( $values as $key => $value ) {
			// Security: only allow valid form fields.
			if ( ! isset( $gateway->form_fields[ $key ] ) ) {
				continue;
			}

			$field      = $gateway->form_fields[ $key ];
			$field_type = $field['type'] ?? 'text';

			// Sanitize by type.
			$sanitized = $this->sanitize_field_value( $field_type, $value );

			// Validate.
			$validation = $this->validate_field_value( $key, $sanitized, $field, $gateway );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			$validated[ $key ] = $sanitized;
		}

		return $validated;
	}

	/**
	 * Sanitize field value based on type.
	 *
	 * @param string $type  Field type.
	 * @param mixed  $value Field value.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_field_value( string $type, $value ) {
		// Normalize type first.
		$type = $this->normalize_field_type( $type );

		switch ( $type ) {
			case 'checkbox':
				return wc_bool_to_string( $value );

			case 'number':
				if ( ! is_numeric( $value ) ) {
					return '';
				}
				$int_value = filter_var( $value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE );
				return null !== $int_value ? $int_value : floatval( $value );

			case 'multiselect':
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', $value );
				}
				return is_string( $value ) ? array( sanitize_text_field( $value ) ) : array();

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'email':
				return sanitize_email( $value );

			case 'text':
			case 'select':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Validate field value.
	 *
	 * @param string             $key     Field key.
	 * @param mixed              $value   Sanitized value.
	 * @param array              $field   Field definition.
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_field_value( string $key, $value, array $field, WC_Payment_Gateway $gateway ) {
		$field_type = $this->normalize_field_type( $field['type'] ?? 'text' );

		// Validate select/radio options.
		if ( in_array( $field_type, array( 'select', 'radio' ), true ) && ! empty( $field['options'] ) ) {
			if ( ! array_key_exists( $value, $field['options'] ) && '' !== $value ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf(
						/* translators: 1: field key, 2: valid options */
						__( 'Invalid value for %1$s. Valid options: %2$s', 'woocommerce' ),
						$key,
						implode( ', ', array_keys( $field['options'] ) )
					),
					array( 'status' => 400 )
				);
			}
		}

		// Validate multiselect options.
		if ( 'multiselect' === $field_type && ! empty( $field['options'] ) ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					if ( ! array_key_exists( $v, $field['options'] ) ) {
						return new WP_Error(
							'rest_invalid_param',
							sprintf(
								/* translators: 1: field key, 2: invalid value */
								__( 'Invalid option "%2$s" for %1$s.', 'woocommerce' ),
								$key,
								$v
							),
							array( 'status' => 400 )
						);
					}
				}
			}
		}

		// Add more validations as needed.

		return true;
	}

	/**
	 * Validate and sanitize special fields.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @param array              $values  Special field values.
	 * @return array|WP_Error Validated values or error.
	 */
	public function validate_and_sanitize_special_fields( WC_Payment_Gateway $gateway, array $values ) {
		if ( ! isset( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ] ) ) {
			return array();
		}

		$validated = array();

		foreach ( $values as $field_id => $value ) {
			if ( ! isset( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ][ $field_id ] ) ) {
				continue;
			}

			$field_config = self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ][ $field_id ];

			// Gateway-specific validation.
			switch ( $gateway->id ) {
				case 'bacs':
					if ( 'account_details' === $field_id ) {
						$validated[ $field_id ] = $this->validate_bacs_accounts( $value );
						if ( is_wp_error( $validated[ $field_id ] ) ) {
							return $validated[ $field_id ];
						}
					}
					break;
			}
		}

		return $validated;
	}

	/**
	 * Validate BACS account details array.
	 *
	 * @param mixed $value Account details value.
	 * @return array|WP_Error Validated accounts or error.
	 */
	private function validate_bacs_accounts( $value ) {
		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Account details must be an array.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$validated_accounts = array();
		$valid_fields       = array( 'account_name', 'account_number', 'sort_code', 'bank_name', 'iban', 'bic' );

		foreach ( $value as $index => $account ) {
			if ( ! is_array( $account ) ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf(
						/* translators: %d: account index */
						__( 'Account at index %d must be an object.', 'woocommerce' ),
						$index
					),
					array( 'status' => 400 )
				);
			}

			$validated_account = array();

			// Sanitize each field.
			foreach ( $valid_fields as $field ) {
				$validated_account[ $field ] = isset( $account[ $field ] )
					? sanitize_text_field( $account[ $field ] )
					: '';
			}

			// Only add if at least one field is filled.
			if ( array_filter( $validated_account ) ) {
				$validated_accounts[] = $validated_account;
			}
		}

		return $validated_accounts;
	}

	/**
	 * Update special fields in database.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @param array              $values  Validated special field values.
	 * @return void
	 */
	public function update_special_fields( WC_Payment_Gateway $gateway, array $values ): void {
		if ( ! isset( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ] ) ) {
			return;
		}

		foreach ( $values as $field_id => $value ) {
			if ( ! isset( self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ][ $field_id ] ) ) {
				continue;
			}

			$field_config = self::GATEWAY_SPECIAL_FIELDS[ $gateway->id ][ $field_id ];
			$option_name  = $field_config['option_name'];

			update_option( $option_name, $value );
		}
	}
}
