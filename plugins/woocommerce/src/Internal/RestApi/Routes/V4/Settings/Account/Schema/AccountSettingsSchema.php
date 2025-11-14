<?php
/**
 * AccountSettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Account\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * AccountSettingsSchema class.
 */
class AccountSettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'account_settings';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'          => array(
				'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'       => array(
				'description' => __( 'Settings title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Settings description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'values'      => array(
				'description'          => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'additionalProperties' => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
				),
			),
			'groups'      => array(
				'description'          => __( 'Collection of setting groups.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'additionalProperties' => array(
					'type'        => 'object',
					'description' => __( 'Settings group.', 'woocommerce' ),
					'properties'  => array(
						'title'       => array(
							'description' => __( 'Group title.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'description' => array(
							'description' => __( 'Group description.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'order'       => array(
							'description' => __( 'Display order for the group.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_CONTEXT,
							'readonly'    => true,
						),
						'fields'      => array(
							'description' => __( 'Settings fields.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_CONTEXT,
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
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'label'   => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'type'    => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'textarea', 'number', 'select', 'multiselect', 'checkbox' ),
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'desc'    => array(
					'description' => __( 'Description for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
			),
		);
	}

	/**
	 * Get account settings data by transforming raw settings into REST API format.
	 *
	 * @param mixed           $item             Raw settings array.
	 * @param WP_REST_Request $request          Request object.
	 * @param array           $include_fields   Fields to include.
	 * @return array
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$raw_settings = $item;

		// Transform raw settings into grouped format based on title/sectionend markers.
		$groups           = array();
		$values           = array();
		$current_group    = null;
		$current_group_id = null;

		foreach ( $raw_settings as $setting ) {
			$setting_type = $setting['type'] ?? '';

			// Handle section titles - start of a new group.
			if ( 'title' === $setting_type ) {
				$current_group_id = $setting['id'] ?? '';
				$current_group    = array(
					'title'       => $setting['title'] ?? '',
					'description' => $setting['desc'] ?? '',
					'order'       => isset( $setting['order'] ) ? (int) $setting['order'] : 999,
					'fields'      => array(),
				);
				continue;
			}

			// Handle section ends - save the current group.
			if ( 'sectionend' === $setting_type ) {
				if ( $current_group && $current_group_id ) {
					$groups[ $current_group_id ] = $current_group;
				}
				$current_group    = null;
				$current_group_id = null;
				continue;
			}

			// Convert setting to field format.
			if ( isset( $setting['id'] ) && $current_group ) {
				$field = $this->transform_setting_to_field( $setting );
				if ( $field ) {
					$current_group['fields'][] = $field;
					// Add field value to the flat values array.
					$raw_value              = get_option( $field['id'], $setting['default'] ?? '' );
					$values[ $field['id'] ] = $this->validate_field_value( $raw_value, $field['type'] );
				}
			}
		}

		// Sort groups by their order if available.
		uasort(
			$groups,
			function ( $a, $b ) {
				$a_order = $a['order'] ?? 999;
				$b_order = $b['order'] ?? 999;
				return $a_order - $b_order;
			}
		);

		$response = array(
			'id'          => 'account',
			'title'       => __( 'Accounts & Privacy', 'woocommerce' ),
			'description' => __( 'Set options relating to customer accounts and data privacy.', 'woocommerce' ),
			'values'      => $values,
			'groups'      => $groups,
		);

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}

	/**
	 * Transform a WooCommerce setting into REST API field format.
	 *
	 * @param array $setting WooCommerce setting array.
	 * @return array|null Transformed field or null if should be skipped.
	 */
	private function transform_setting_to_field( array $setting ): ?array {
		$setting_id   = $setting['id'] ?? '';
		$setting_type = $setting['type'] ?? 'text';

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
			// Generate options for special field types.
			$field['options'] = $this->get_field_options( $setting_id );
		}

		return $field;
	}

	/**
	 * Get options for specific field types.
	 *
	 * @param string $field_id Field ID.
	 * @return array Field options.
	 */
	private function get_field_options( string $field_id ): array {
		// No field has options for now.
		return array();
	}

	/**
	 * Normalize WooCommerce field types to REST API field types.
	 *
	 * @param string $wc_type WooCommerce field type.
	 * @return string Normalized field type.
	 */
	private function normalize_field_type( string $wc_type ): string {
		$type_map = array(
			'single_select_page'             => 'select',
			'single_select_page_with_search' => 'select',
		);

		return $type_map[ $wc_type ] ?? $wc_type;
	}

	/**
	 * Validate and sanitize field value based on its type.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type  Field type.
	 * @return mixed Validated value.
	 */
	private function validate_field_value( $value, string $type ) {
		switch ( $type ) {
			case 'number':
				return is_numeric( $value ) ? (float) $value : 0;
			case 'checkbox':
				if ( function_exists( 'wc_string_to_bool' ) ) {
					return wc_string_to_bool( $value );
				}
				if ( is_bool( $value ) ) {
					return $value;
				}
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			case 'multiselect':
				if ( ! is_array( $value ) ) {
					return array();
				}
				return array_map( 'sanitize_text_field', $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'text':
			case 'select':
			default:
				return sanitize_text_field( $value );
		}
	}
}
