<?php
/**
 * Settings UI schema builder.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the canonical settings schema consumed by the settings UI renderer.
 *
 * @since 10.9.0
 */
class SettingsUISchema {

	/**
	 * Default group id for fields before the first title marker.
	 *
	 * @var string
	 */
	private const DEFAULT_GROUP_ID = 'default';

	/**
	 * Build a schema from a legacy WC settings array.
	 *
	 * @since 10.9.0
	 *
	 * @param string $page_id Settings page id.
	 * @param string $section Section id. Empty string means the default section.
	 * @param string $title Page title.
	 * @param array  $settings Legacy settings definitions.
	 * @param string $default_save_adapter Default save adapter.
	 * @return array
	 */
	public static function from_legacy_settings( string $page_id, string $section, string $title, array $settings, string $default_save_adapter = 'form_post' ): array {
		$groups                = array();
		$current_group         = null;
		$current_id            = null;
		$group_index           = 0;
		$visibility_controller = null;

		foreach ( $settings as $setting ) {
			if ( ! is_array( $setting ) ) {
				continue;
			}

			$type = isset( $setting['type'] ) && is_string( $setting['type'] ) ? $setting['type'] : 'text';

			if ( 'title' === $type ) {
				$visibility_controller = null;
				if ( $current_group && $current_id ) {
					$groups[ $current_id ] = $current_group;
				}

				$current_id    = isset( $setting['id'] ) && is_scalar( $setting['id'] ) && '' !== (string) $setting['id']
					? (string) $setting['id']
					: 'group_' . $group_index;
				$current_group = array(
					'id'          => $current_id,
					'title'       => isset( $setting['title'] ) && is_scalar( $setting['title'] ) ? html_entity_decode( (string) $setting['title'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) : '',
					'description' => isset( $setting['desc'] ) && is_scalar( $setting['desc'] ) ? wp_kses_post( (string) $setting['desc'] ) : '',
					'actions'     => self::get_group_actions( $setting ),
					'order'       => isset( $setting['order'] ) ? (int) $setting['order'] : $group_index,
					'fields'      => array(),
				);
				++$group_index;
				continue;
			}

			if ( 'sectionend' === $type ) {
				$visibility_controller = null;
				if ( $current_group && $current_id ) {
					$groups[ $current_id ] = $current_group;
				}
				$current_group = null;
				$current_id    = null;
				continue;
			}

			if ( empty( $setting['id'] ) ) {
				continue;
			}

			if ( ! $current_group ) {
				$current_id    = self::DEFAULT_GROUP_ID;
				$current_group = self::get_default_group( $group_index );
				++$group_index;
			}

			$field = self::transform_legacy_field( $setting, $default_save_adapter, $visibility_controller );
			if ( $field ) {
				$current_group['fields'][] = $field;
			}

			if ( 'checkbox' === $type && 'option' === ( $setting['show_if_checked'] ?? null ) ) {
				$visibility_controller = $field['id'] ?? null;
			}

			if ( 'end' === ( $setting['checkboxgroup'] ?? null ) ) {
				$visibility_controller = null;
			}
		}

		if ( $current_group && $current_id ) {
			$groups[ $current_id ] = $current_group;
		}

		uasort(
			$groups,
			static function ( array $a, array $b ): int {
				return ( $a['order'] ?? 999 ) <=> ( $b['order'] ?? 999 );
			}
		);

		foreach ( $groups as $group_id => $group ) {
			unset( $group['order'] );
			$groups[ $group_id ] = $group;
		}

		$decoded_title = html_entity_decode( $title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );

		return array(
			'id'      => $page_id,
			'title'   => $decoded_title,
			'section' => '' === $section ? self::DEFAULT_GROUP_ID : $section,
			'save'    => array(
				'adapter' => $default_save_adapter,
			),
			'shell'   => array(
				'title' => $decoded_title,
			),
			'groups'  => $groups,
		);
	}

	/**
	 * Canonicalize option values supplied by native Settings UI schema providers.
	 *
	 * Schemas built from legacy settings always carry string option values, but
	 * native providers can supply any scalar. The client matches options against
	 * the stored value with strict string comparison, so scalar option values,
	 * the selected values they match, and visibility values compared against
	 * them are cast here to the string the client's own String() coercion
	 * produces. Malformed entries remain unchanged for the provider to fix.
	 *
	 * @since 11.0.0
	 *
	 * @param array $schema Settings UI schema.
	 * @return array Schema with scalar option values canonicalized to strings.
	 */
	public static function canonicalize_option_values( array $schema ): array {
		if ( ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			return $schema;
		}

		$converted_fields = array();
		$option_field_ids = array();

		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if (
					! is_array( $field ) ||
					! isset( $field['id'], $field['options'] ) ||
					! is_string( $field['id'] ) ||
					! is_array( $field['options'] )
				) {
					continue;
				}

				$option_field_ids[] = $field['id'];
				$converted          = false;

				foreach ( $field['options'] as &$option ) {
					if (
						! is_array( $option ) ||
						! array_key_exists( 'value', $option ) ||
						is_string( $option['value'] ) ||
						! is_scalar( $option['value'] )
					) {
						continue;
					}

					$option['value'] = self::to_canonical_string( $option['value'] );
					$converted       = true;
				}
				unset( $option );

				if ( array_key_exists( 'value', $field ) ) {
					if ( is_scalar( $field['value'] ) && ! is_string( $field['value'] ) ) {
						$field['value'] = self::to_canonical_string( $field['value'] );
						$converted      = true;
					} elseif ( is_array( $field['value'] ) ) {
						$canonical_list = self::canonicalize_scalar_list( $field['value'] );
						if ( null !== $canonical_list ) {
							$field['value'] = $canonical_list;
							$converted      = true;
						}
					}
				}

				if ( $converted ) {
					$converted_fields[] = $field['id'];
				}
			}
			unset( $field );
		}
		unset( $group );

		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if ( ! is_array( $field ) || ! isset( $field['id'] ) || ! is_string( $field['id'] ) ) {
					continue;
				}

				if ( ! self::is_canonicalizable_visibility_rule( $field['visibility'] ?? null, $option_field_ids ) ) {
					continue;
				}

				$rule_value = $field['visibility']['value'];

				if ( is_scalar( $rule_value ) && ! is_string( $rule_value ) ) {
					$field['visibility']['value'] = self::to_canonical_string( $rule_value );
					$converted_fields[]           = $field['id'];
				} elseif ( is_array( $rule_value ) ) {
					$canonical_list = self::canonicalize_scalar_list( $rule_value );
					if ( null !== $canonical_list ) {
						$field['visibility']['value'] = $canonical_list;
						$converted_fields[]           = $field['id'];
					}
				}
			}
			unset( $field );
		}
		unset( $group );

		if ( ! empty( $converted_fields ) ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: comma-separated field ids. */
					esc_html__( 'A Settings UI schema provider supplied non-string option, field, or visibility values that WooCommerce converted for compatibility: %s. Update the provider to supply string values.', 'woocommerce' ),
					esc_html( implode( ', ', array_unique( $converted_fields ) ) )
				),
				'11.0.0'
			);
		}

		return $schema;
	}

	/**
	 * Canonicalize a list of scalar values to strings.
	 *
	 * @param array $values Candidate value list.
	 * @return array|null String list, or null when unchanged or not a scalar list.
	 */
	private static function canonicalize_scalar_list( array $values ): ?array {
		if ( ! ArrayUtil::array_is_list( $values ) ) {
			return null;
		}

		$needs_conversion = false;

		foreach ( $values as $item ) {
			if ( ! is_scalar( $item ) ) {
				return null;
			}

			if ( ! is_string( $item ) ) {
				$needs_conversion = true;
			}
		}

		return $needs_conversion ? array_map( array( __CLASS__, 'to_canonical_string' ), $values ) : null;
	}

	/**
	 * Whether a visibility rule carries a value compared against an options field.
	 *
	 * @param mixed $rule Candidate visibility rule.
	 * @param array $option_field_ids Ids of fields carrying an options array.
	 * @return bool
	 */
	private static function is_canonicalizable_visibility_rule( $rule, array $option_field_ids ): bool {
		return is_array( $rule )
			&& array_key_exists( 'value', $rule )
			&& in_array( $rule['controller'] ?? null, $option_field_ids, true );
	}

	/**
	 * Cast a scalar to the string the client's String() coercion produces, so
	 * canonicalizing a value never changes which option or visibility rule it
	 * matches. PHP casts diverge from String() for booleans: (string) true is
	 * '1' and (string) false is '', while String() gives 'true' and 'false'.
	 * Floats convert through wc_float_to_string() because a plain cast is
	 * locale-sensitive before PHP 8.0 and String() never emits a comma.
	 *
	 * @param bool|int|float|string $value Scalar value.
	 * @return string
	 */
	private static function to_canonical_string( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_float( $value ) ) {
			return wc_float_to_string( $value );
		}

		return (string) $value;
	}

	/**
	 * Transform a legacy field into the canonical schema.
	 *
	 * @param array       $setting Legacy field definition.
	 * @param string      $default_save_adapter Default save adapter.
	 * @param string|null $visibility_controller Current checkbox group controller.
	 * @return array|null
	 */
	private static function transform_legacy_field( array $setting, string $default_save_adapter, ?string $visibility_controller = null ): ?array {
		$id   = isset( $setting['id'] ) && is_scalar( $setting['id'] ) ? (string) $setting['id'] : '';
		$type = isset( $setting['type'] ) && is_string( $setting['type'] ) ? $setting['type'] : 'text';
		if ( '' === $id ) {
			return null;
		}

		$canonical_type = self::normalize_type( $type );
		$field          = array(
			'id'          => $id,
			'label'       => self::get_field_label( $setting, $id, $type ),
			'type'        => $canonical_type,
			'description' => self::get_field_description( $setting, $type ),
			'value'       => self::get_field_value( $setting, $canonical_type ),
			'save'        => self::get_save_schema( $setting, $default_save_adapter ),
		);

		foreach ( array( 'component', 'placeholder', 'disabled' ) as $key ) {
			if ( array_key_exists( $key, $setting ) ) {
				$field[ $key ] = $setting[ $key ];
			}
		}

		if ( isset( $setting['custom_attributes'] ) && is_array( $setting['custom_attributes'] ) ) {
			$field['customAttributes'] = self::get_custom_attributes( $setting['custom_attributes'] );
		}

		$visibility = self::get_field_visibility( $setting, $visibility_controller );
		if ( $visibility ) {
			$field['visibility'] = $visibility;
		}

		$options = self::get_options( $setting );
		if ( ! empty( $options ) ) {
			$field['options'] = $options;
		}

		if ( 'info' === $type && '' === $field['description'] && isset( $setting['text'] ) && is_scalar( $setting['text'] ) ) {
			$field['description'] = wp_kses_post( (string) $setting['text'] );
			$field['save']        = array( 'adapter' => 'none' );
		}

		return $field;
	}

	/**
	 * Get a field label.
	 *
	 * @param array  $setting Legacy field definition.
	 * @param string $id Field id.
	 * @param string $type Raw field type.
	 * @return string
	 */
	private static function get_field_label( array $setting, string $id, string $type ): string {
		if ( 'checkbox' === $type && isset( $setting['desc'] ) && is_scalar( $setting['desc'] ) && '' !== (string) $setting['desc'] ) {
			return wp_strip_all_tags( html_entity_decode( (string) $setting['desc'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
		}

		foreach ( array( 'title', 'name' ) as $key ) {
			if ( isset( $setting[ $key ] ) && is_scalar( $setting[ $key ] ) && '' !== (string) $setting[ $key ] ) {
				return wp_strip_all_tags( html_entity_decode( (string) $setting[ $key ], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
			}
		}

		return $id;
	}

	/**
	 * Get a field description.
	 *
	 * @param array  $setting Legacy field definition.
	 * @param string $type Raw field type.
	 * @return string
	 */
	private static function get_field_description( array $setting, string $type ): string {
		$description = 'checkbox' === $type || ! isset( $setting['desc'] ) || ! is_scalar( $setting['desc'] )
			? ''
			: wp_kses_post( (string) $setting['desc'] );

		$desc_tip = isset( $setting['desc_tip'] ) && is_string( $setting['desc_tip'] ) && '' !== $setting['desc_tip']
			? wp_kses_post( $setting['desc_tip'] )
			: '';

		if ( '' === $description ) {
			return $desc_tip;
		}

		if ( '' === $desc_tip ) {
			return $description;
		}

		return $description . '<br />' . $desc_tip;
	}

	/**
	 * Normalize legacy field type.
	 *
	 * @param string $type Legacy field type.
	 * @return string
	 */
	private static function normalize_type( string $type ): string {
		$type_map = array(
			'multiselect'            => 'array',
			'multi_select_countries' => 'array',
			'single_select_country'  => 'select',
			'single_select_page'     => 'select',
		);

		return $type_map[ $type ] ?? $type;
	}

	/**
	 * Get a field value.
	 *
	 * @param array  $setting Legacy field definition.
	 * @param string $type Canonical field type.
	 * @return mixed
	 */
	private static function get_field_value( array $setting, string $type ) {
		if ( array_key_exists( 'value', $setting ) ) {
			return self::normalize_value( $setting['value'], $type );
		}

		$default = $setting['default'] ?? '';
		$value   = \WC_Admin_Settings::get_option( (string) $setting['id'], $default );

		return self::normalize_value( $value, $type );
	}

	/**
	 * Normalize a value for the canonical schema.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type Canonical type.
	 * @return mixed
	 */
	private static function normalize_value( $value, string $type ) {
		switch ( $type ) {
			case 'array':
				return is_array( $value ) ? array_values( $value ) : array();
			case 'checkbox':
				return function_exists( 'wc_string_to_bool' ) ? wc_string_to_bool( $value ) : (bool) $value;
			default:
				return $value;
		}
	}

	/**
	 * Get a field save schema.
	 *
	 * @param array  $setting Legacy field definition.
	 * @param string $default_save_adapter Default save adapter.
	 * @return array
	 */
	private static function get_save_schema( array $setting, string $default_save_adapter ): array {
		if ( isset( $setting['save'] ) && is_array( $setting['save'] ) ) {
			return $setting['save'];
		}

		if ( isset( $setting['is_option'] ) && false === $setting['is_option'] ) {
			return array( 'adapter' => 'none' );
		}

		$field_name = isset( $setting['field_name'] ) && is_scalar( $setting['field_name'] )
			? (string) $setting['field_name']
			: (string) $setting['id'];

		return array(
			'adapter' => $default_save_adapter,
			'name'    => $field_name,
		);
	}

	/**
	 * Get visibility metadata for legacy conditional fields.
	 *
	 * @param array       $setting Legacy field definition.
	 * @param string|null $visibility_controller Current checkbox group controller.
	 * @return array|null
	 */
	private static function get_field_visibility( array $setting, ?string $visibility_controller ): ?array {
		$class_names = isset( $setting['class'] ) && is_string( $setting['class'] ) ? explode( ' ', $setting['class'] ) : array();
		if ( in_array( 'manage_stock_field', $class_names, true ) ) {
			return array(
				'controller' => 'woocommerce_manage_stock',
				'value'      => true,
			);
		}

		if ( 'yes' === ( $setting['show_if_checked'] ?? null ) && $visibility_controller ) {
			return array(
				'controller' => $visibility_controller,
				'value'      => true,
			);
		}

		return null;
	}

	/**
	 * Normalize field options.
	 *
	 * @param array $setting Legacy field definition.
	 * @return array
	 */
	private static function get_options( array $setting ): array {
		if ( ! isset( $setting['options'] ) || ! is_array( $setting['options'] ) ) {
			return array();
		}

		$options = array();
		foreach ( $setting['options'] as $value => $label ) {
			if ( ! is_scalar( $label ) && null !== $label ) {
				continue;
			}

			$options[] = array(
				'label' => is_scalar( $label ) ? wp_strip_all_tags( html_entity_decode( (string) $label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) : '',
				'value' => (string) $value,
			);
		}

		return $options;
	}

	/**
	 * Normalize custom attributes for React controls.
	 *
	 * @param array $custom_attributes Raw custom attributes.
	 * @return array
	 */
	private static function get_custom_attributes( array $custom_attributes ): array {
		$attributes = array();

		foreach ( $custom_attributes as $attribute => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$attribute_key = sanitize_key( (string) $attribute );
			if ( '' === $attribute_key ) {
				continue;
			}

			$attributes[ $attribute_key ] = $value;
		}

		return $attributes;
	}

	/**
	 * Normalize group header actions.
	 *
	 * @param array $setting Legacy title setting definition.
	 * @return array
	 */
	private static function get_group_actions( array $setting ): array {
		if ( empty( $setting['actions'] ) || ! is_array( $setting['actions'] ) ) {
			return array();
		}

		$actions = array();

		foreach ( $setting['actions'] as $index => $action ) {
			if ( ! is_array( $action ) || empty( $action['label'] ) || ! is_scalar( $action['label'] ) ) {
				continue;
			}

			$href = $action['href'] ?? $action['url'] ?? '';
			if ( ! is_scalar( $href ) || '' === (string) $href ) {
				continue;
			}

			$href = esc_url_raw( (string) $href );
			if ( '' === $href ) {
				continue;
			}

			$normalized_action = array(
				'id'    => isset( $action['id'] ) && is_scalar( $action['id'] ) ? sanitize_key( (string) $action['id'] ) : 'action_' . $index,
				'label' => wp_strip_all_tags( html_entity_decode( (string) $action['label'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),
				'href'  => $href,
			);

			if ( isset( $action['variant'] ) && is_scalar( $action['variant'] ) ) {
				$normalized_action['variant'] = sanitize_key( (string) $action['variant'] );
			}

			if ( isset( $action['target'] ) && is_scalar( $action['target'] ) && in_array( (string) $action['target'], array( '_blank', '_self', '_parent', '_top' ), true ) ) {
				$normalized_action['target'] = (string) $action['target'];
			}

			if ( isset( $action['rel'] ) && is_scalar( $action['rel'] ) ) {
				$normalized_action['rel'] = sanitize_text_field( (string) $action['rel'] );
			}

			$actions[] = $normalized_action;
		}

		return $actions;
	}

	/**
	 * Get the default group.
	 *
	 * @param int $order Group order.
	 * @return array
	 */
	private static function get_default_group( int $order ): array {
		return array(
			'id'          => self::DEFAULT_GROUP_ID,
			'title'       => '',
			'description' => '',
			'actions'     => array(),
			'order'       => $order,
			'fields'      => array(),
		);
	}
}
