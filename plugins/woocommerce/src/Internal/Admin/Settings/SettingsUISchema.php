<?php
/**
 * Settings UI schema builder.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

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

		$raw_value       = self::get_raw_field_value( $setting );
		$canonical_type  = self::normalize_type( $type, $setting, $raw_value );
		$canonical_value = self::normalize_value( $raw_value, $canonical_type, $id );
		$save_schema     = self::get_save_schema( $setting, $default_save_adapter );

		if ( 'form_post' === ( $save_schema['adapter'] ?? null ) ) {
			$save_schema['initialValue'] = self::get_initial_form_value( $raw_value, $canonical_type, $canonical_value );
		}

		$field = array(
			'id'          => $id,
			'label'       => self::get_field_label( $setting, $id, $type ),
			'type'        => $canonical_type,
			'description' => self::get_field_description( $setting, $type ),
			'value'       => $canonical_value,
			'save'        => $save_schema,
		);

		$validation = self::get_validation_schema( $setting, $canonical_type );
		if ( ! empty( $validation ) ) {
			$field['validation'] = $validation;
		}

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
	 * @param array  $setting Legacy field definition.
	 * @param mixed  $value Raw field value.
	 * @return string
	 */
	private static function normalize_type( string $type, array $setting, $value ): string {
		$type_map = array(
			'multiselect'            => 'array',
			'multi_select_countries' => 'array',
			'single_select_country'  => 'select',
			'single_select_page'     => 'select',
		);
		$type     = $type_map[ $type ] ?? $type;

		if ( 'number' !== $type ) {
			return $type;
		}

		$attributes = isset( $setting['custom_attributes'] ) && is_array( $setting['custom_attributes'] )
			? $setting['custom_attributes']
			: array();
		$step       = self::to_finite_number( $attributes['step'] ?? null );
		$min        = self::to_finite_number( $attributes['min'] ?? null );
		$step_base  = null !== $min ? $min : self::to_finite_number( $value );

		return 1 === $step && ( null === $step_base || is_int( $step_base ) ) ? 'integer' : $type;
	}

	/**
	 * Get the stored value before canonical normalization.
	 *
	 * @param array $setting Legacy field definition.
	 * @return mixed
	 */
	private static function get_raw_field_value( array $setting ) {
		if ( array_key_exists( 'value', $setting ) ) {
			return $setting['value'];
		}

		$default = $setting['default'] ?? '';
		return \WC_Admin_Settings::get_option( (string) $setting['id'], $default );
	}

	/**
	 * Normalize a value for the canonical schema.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type Canonical type.
	 * @param string $field_id Field id for diagnostics.
	 * @return mixed
	 * @throws \UnexpectedValueException When a built-in value cannot be normalized safely.
	 */
	private static function normalize_value( $value, string $type, string $field_id ) {
		switch ( $type ) {
			case 'array':
				if ( ! is_array( $value ) ) {
					return array();
				}

				return array_map(
					static function ( $item ) use ( $field_id ): string {
						if ( ! is_scalar( $item ) && null !== $item ) {
							throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" contains a non-scalar array value.', esc_html( $field_id ) ) );
						}

						return (string) $item;
					},
					array_values( $value )
				);
			case 'checkbox':
				return function_exists( 'wc_string_to_bool' ) ? wc_string_to_bool( $value ) : (bool) $value;
			case 'integer':
			case 'number':
				return self::normalize_numeric_value( $value, $type, $field_id );
			case 'datetime-local':
				return self::normalize_datetime_value( $value, $field_id );
			default:
				return $value;
		}
	}

	/**
	 * Normalize a numeric value.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type Canonical number type.
	 * @param string $field_id Field id for diagnostics.
	 * @return int|float|null
	 * @throws \UnexpectedValueException When the value is not a finite number of the requested type.
	 */
	private static function normalize_numeric_value( $value, string $type, string $field_id ) {
		if ( null === $value || ( is_string( $value ) && '' === trim( $value ) ) ) {
			return null;
		}

		$number = self::to_finite_number( $value );
		if ( null === $number || ( 'integer' === $type && ! is_int( $number ) ) ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" does not contain a valid %s value.', esc_html( $field_id ), esc_html( $type ) ) );
		}

		return $number;
	}

	/**
	 * Normalize a local datetime value to an ISO value.
	 *
	 * @param mixed  $value Field value.
	 * @param string $field_id Field id for diagnostics.
	 * @return string|null
	 * @throws \UnexpectedValueException When the value is not a supported local datetime.
	 */
	private static function normalize_datetime_value( $value, string $field_id ): ?string {
		if ( null === $value || '' === $value ) {
			return null;
		}

		if ( ! is_string( $value ) ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" does not contain a valid local datetime.', esc_html( $field_id ) ) );
		}

		// Parse warnings reject malformed values. Wall-clock times inside a
		// DST gap parse to the shifted instant and are kept: rejecting them
		// would fall the whole page back to the legacy renderer.
		foreach ( array( 'Y-m-d\\TH:i:s', 'Y-m-d\\TH:i' ) as $format ) {
			$date   = \DateTimeImmutable::createFromFormat( '!' . $format, $value, wp_timezone() );
			$errors = \DateTimeImmutable::getLastErrors();
			if ( $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
				return $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d\\TH:i:sP' );
			}
		}

		throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" does not contain a valid local datetime.', esc_html( $field_id ) ) );
	}

	/**
	 * Get the original value used when an unchanged field is submitted.
	 *
	 * @param mixed  $raw_value Raw field value.
	 * @param string $type Canonical field type.
	 * @param mixed  $canonical_value Canonical field value.
	 * @return string|string[]
	 */
	private static function get_initial_form_value( $raw_value, string $type, $canonical_value ) {
		return 'array' === $type && is_array( $canonical_value )
			? $canonical_value
			: ( is_scalar( $raw_value ) ? (string) $raw_value : '' );
	}

	/**
	 * Get supported validation metadata from a legacy field.
	 *
	 * @param array  $setting Legacy field definition.
	 * @param string $type Canonical field type.
	 * @return array<string, int|float>
	 */
	private static function get_validation_schema( array $setting, string $type ): array {
		if ( ! in_array( $type, array( 'integer', 'number' ), true ) || ! isset( $setting['custom_attributes'] ) || ! is_array( $setting['custom_attributes'] ) ) {
			return array();
		}

		$validation = array();
		foreach ( array( 'min', 'max' ) as $rule ) {
			$value = self::to_finite_number( $setting['custom_attributes'][ $rule ] ?? null );
			if ( null !== $value ) {
				$validation[ $rule ] = $value;
			}
		}

		return $validation;
	}

	/**
	 * Convert a numeric value to a finite integer or float.
	 *
	 * @param mixed $value Candidate value.
	 * @return int|float|null
	 */
	private static function to_finite_number( $value ) {
		if ( ! is_int( $value ) && ! is_float( $value ) && ! ( is_string( $value ) && is_numeric( $value ) ) ) {
			return null;
		}

		$number = (float) $value;
		if ( ! is_finite( $number ) ) {
			return null;
		}

		return floor( $number ) === $number && $number <= PHP_INT_MAX && $number >= PHP_INT_MIN ? (int) $number : $number;
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
	 * Canonicalize explicitly supported field values from legacy schema providers.
	 *
	 * Malformed or ambiguous values remain unchanged so assert_valid() can reject
	 * them instead of guessing at the provider's intent.
	 *
	 * @since 11.1.0
	 *
	 * @param array $schema Settings UI schema.
	 * @return array Schema with supported legacy values canonicalized.
	 */
	public static function canonicalize_legacy_values( array $schema ): array {
		if ( ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			return $schema;
		}

		$converted_fields = array();

		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if (
					! is_array( $field ) ||
					! isset( $field['id'], $field['type'] ) ||
					! is_string( $field['id'] ) ||
					! is_string( $field['type'] ) ||
					! array_key_exists( 'value', $field )
				) {
					continue;
				}

				list( $value, $converted ) = self::canonicalize_legacy_field_value( $field['value'], $field['type'], $field['id'] );
				if ( ! $converted ) {
					continue;
				}

				$field['value']     = $value;
				$converted_fields[] = sprintf( '%1$s (%2$s)', $field['id'], $field['type'] );
			}
			unset( $field );
		}
		unset( $group );

		if ( ! empty( $converted_fields ) ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: comma-separated field ids and types. */
					esc_html__( 'A Settings UI schema provider supplied legacy field values that WooCommerce converted for compatibility: %s. Update the provider to return canonical values.', 'woocommerce' ),
					esc_html( implode( ', ', array_unique( $converted_fields ) ) )
				),
				'11.1.0'
			);
		}

		return $schema;
	}

	/**
	 * Canonicalize one supported legacy field value.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type Field type.
	 * @param string $field_id Field id for diagnostics.
	 * @return array{0: mixed, 1: bool} Canonical value and whether it changed.
	 */
	private static function canonicalize_legacy_field_value( $value, string $type, string $field_id ): array {
		switch ( $type ) {
			case 'checkbox':
				if ( is_bool( $value ) ) {
					return array( $value, false );
				}

				$is_legacy_integer = is_int( $value ) && in_array( $value, array( 0, 1 ), true );
				$is_legacy_string  = is_string( $value ) && in_array( strtolower( $value ), array( '', 'yes', 'no', 'true', 'false', '1', '0' ), true );
				if ( ! $is_legacy_integer && ! $is_legacy_string ) {
					return array( $value, false );
				}

				$canonical = function_exists( 'wc_string_to_bool' )
					? wc_string_to_bool( $value )
					: 1 === $value || in_array( strtolower( (string) $value ), array( 'yes', 'true', '1' ), true );
				return array( $canonical, true );
			case 'array':
				if ( ! is_array( $value ) || self::is_string_list( $value ) ) {
					return array( $value, false );
				}

				foreach ( $value as $item ) {
					if ( ! is_scalar( $item ) ) {
						return array( $value, false );
					}
				}

				return array( array_map( 'strval', array_values( $value ) ), true );
			case 'integer':
			case 'number':
				if ( ! is_string( $value ) || ! is_numeric( $value ) ) {
					return array( $value, false );
				}

				try {
					return array( self::normalize_numeric_value( $value, $type, $field_id ), true );
				} catch ( \UnexpectedValueException $error ) {
					return array( $value, false );
				}
			case 'datetime-local':
				if ( self::is_iso_datetime( $value ) || ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/', $value ) ) {
					return array( $value, false );
				}

				try {
					return array( self::normalize_datetime_value( $value, $field_id ), true );
				} catch ( \UnexpectedValueException $error ) {
					return array( $value, false );
				}
			default:
				return array( $value, false );
		}
	}

	/**
	 * Assert that a schema is safe to pass to the Settings UI.
	 *
	 * @since 11.1.0
	 *
	 * @param array $schema Canonical Settings UI schema.
	 * @throws \UnexpectedValueException When the schema violates the canonical contract.
	 */
	public static function assert_valid( array $schema ): void {
		if ( empty( $schema['id'] ) || ! is_string( $schema['id'] ) ) {
			throw new \UnexpectedValueException( 'The Settings UI schema must have a non-empty string id.' );
		}

		if ( ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI schema "%s" must contain a groups array.', esc_html( $schema['id'] ) ) );
		}

		$field_ids = array();
		foreach ( $schema['groups'] as $group_key => $group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				throw new \UnexpectedValueException( sprintf( 'Settings UI group "%s" must contain a fields array.', esc_html( (string) $group_key ) ) );
			}

			foreach ( $group['fields'] as $field ) {
				self::assert_valid_field( $field, $field_ids );
			}
		}
	}

	/**
	 * Assert that a field follows the canonical Settings UI contract.
	 *
	 * @param mixed               $field Field schema.
	 * @param array<string, bool> $field_ids Previously validated field ids.
	 * @throws \UnexpectedValueException When the field is invalid.
	 */
	private static function assert_valid_field( $field, array &$field_ids ): void {
		if ( ! is_array( $field ) || empty( $field['id'] ) || ! is_string( $field['id'] ) ) {
			throw new \UnexpectedValueException( 'Every Settings UI field must have a non-empty string id.' );
		}

		$field_id = $field['id'];
		if ( isset( $field_ids[ $field_id ] ) ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI field id "%s" is duplicated.', esc_html( $field_id ) ) );
		}
		$field_ids[ $field_id ] = true;

		if ( ! isset( $field['label'] ) || ! is_string( $field['label'] ) || empty( $field['type'] ) || ! is_string( $field['type'] ) ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" must have a string label and non-empty string type.', esc_html( $field_id ) ) );
		}

		$requires_value = in_array( $field['type'], array( 'checkbox', 'array', 'number', 'integer', 'datetime-local' ), true );
		if ( $requires_value && ! array_key_exists( 'value', $field ) ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" must provide a canonical value.', esc_html( $field_id ) ) );
		}

		if ( array_key_exists( 'value', $field ) ) {
			self::assert_valid_field_value( $field_id, $field['type'], $field['value'] );
		}

		if ( isset( $field['options'] ) ) {
			if ( ! is_array( $field['options'] ) ) {
				throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" options must be an array.', esc_html( $field_id ) ) );
			}
			foreach ( $field['options'] as $option ) {
				if ( ! is_array( $option ) || ! isset( $option['label'], $option['value'] ) || ! is_string( $option['label'] ) || ! is_string( $option['value'] ) ) {
					throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" options must have string labels and values.', esc_html( $field_id ) ) );
				}
			}
		}

		if ( isset( $field['validation'] ) ) {
			if ( ! is_array( $field['validation'] ) ) {
				throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" validation must be an array.', esc_html( $field_id ) ) );
			}
			foreach ( array( 'min', 'max' ) as $rule ) {
				$value = $field['validation'][ $rule ] ?? null;
				if ( null !== $value && ! is_int( $value ) && ! ( is_float( $value ) && is_finite( $value ) ) ) {
					throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" validation rule "%s" must be a finite number.', esc_html( $field_id ), esc_html( $rule ) ) );
				}
			}
		}

		$initial_value = $field['save']['initialValue'] ?? null;
		if ( null !== $initial_value && ! is_string( $initial_value ) && ! self::is_string_list( $initial_value ) ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" has an invalid form POST initial value.', esc_html( $field_id ) ) );
		}
	}

	/**
	 * Assert that a field value uses the canonical type.
	 *
	 * @param string $field_id Field id.
	 * @param string $type Field type.
	 * @param mixed  $value Field value.
	 * @throws \UnexpectedValueException When the value is invalid.
	 */
	private static function assert_valid_field_value( string $field_id, string $type, $value ): void {
		$is_valid = is_string( $value ) || is_int( $value ) || is_bool( $value ) || null === $value || ( is_float( $value ) && is_finite( $value ) ) || self::is_string_list( $value );

		switch ( $type ) {
			case 'checkbox':
				$is_valid = is_bool( $value );
				break;
			case 'array':
				$is_valid = self::is_string_list( $value );
				break;
			case 'integer':
				$is_valid = is_int( $value ) || null === $value;
				break;
			case 'number':
				$is_valid = is_int( $value ) || ( is_float( $value ) && is_finite( $value ) ) || null === $value;
				break;
			case 'datetime-local':
				$is_valid = null === $value || self::is_iso_datetime( $value );
				break;
		}

		if ( ! $is_valid ) {
			throw new \UnexpectedValueException( sprintf( 'Settings UI field "%s" has a noncanonical value for type "%s".', esc_html( $field_id ), esc_html( $type ) ) );
		}
	}

	/**
	 * Whether a value is a list of strings.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_string_list( $value ): bool {
		if ( ! is_array( $value ) || ( ! empty( $value ) && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) ) {
			return false;
		}

		foreach ( $value as $item ) {
			if ( ! is_string( $item ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether a value is an ISO datetime with an explicit timezone.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_iso_datetime( $value ): bool {
		if ( ! is_string( $value ) || ! preg_match( '/T.+(?:Z|[+-]\\d{2}:\\d{2})$/', $value ) ) {
			return false;
		}

		try {
			new \DateTimeImmutable( $value );
			return true;
		} catch ( \Exception $error ) {
			return false;
		}
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
