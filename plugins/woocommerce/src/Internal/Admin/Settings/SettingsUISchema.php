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
	 * Field types that the current Settings UI renderer handles explicitly.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_FIELD_TYPES = array(
		'array',
		'checkbox',
		'date',
		'datetime-local',
		'email',
		'info',
		'integer',
		'number',
		'password',
		'radio',
		'select',
		'tel',
		'text',
		'textarea',
		'time',
		'url',
	);

	/**
	 * Field types whose values cross the typed canonicalization boundary.
	 *
	 * @var string[]
	 */
	private const TYPED_VALUE_FIELD_TYPES = array( 'array', 'checkbox', 'datetime-local', 'integer', 'number' );

	/**
	 * Custom attributes that describe an input range.
	 *
	 * @var string[]
	 */
	private const RANGE_ATTRIBUTES = array( 'min', 'max', 'step' );

	/**
	 * Native temporal field types that accept range attributes.
	 *
	 * @var string[]
	 */
	private const TEMPORAL_RANGE_FIELD_TYPES = array( 'date', 'datetime-local', 'time' );

	/**
	 * Largest integer JavaScript can represent exactly.
	 *
	 * @var string
	 */
	private const JAVASCRIPT_SAFE_INTEGER = '9007199254740991';

	/**
	 * HTML decimal-number grammar with named captures for exact normalization.
	 *
	 * Captures the optional sign, whole-number digits, fractional digits after a
	 * whole number, fractional digits without a whole number, and the optional
	 * signed exponent.
	 *
	 * @var string
	 */
	private const DECIMAL_PATTERN = '/^(?<sign>[+-]?)(?:(?<whole>\d+)(?:\.(?<fraction>\d*))?|\.(?<bare_fraction>\d+))(?:[eE](?<exponent>[+-]?\d+))?$/';

	/**
	 * Store-local datetime grammar with a four-digit year, two-digit date and
	 * time components, and optional seconds. Calendar validity is checked after
	 * the pattern matches.
	 *
	 * @var string
	 */
	private const LOCAL_DATETIME_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/';

	/**
	 * Timezone-qualified datetime grammar. It uses the local datetime components
	 * and requires either UTC "Z" or a signed two-digit hour and minute offset.
	 * Malformed offsets do not match, and calendar validity is checked after the
	 * pattern matches.
	 *
	 * @var string
	 */
	private const QUALIFIED_DATETIME_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/';

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
	 * @throws \InvalidArgumentException When legacy settings contain duplicate group ids.
	 */
	public static function from_legacy_settings( string $page_id, string $section, string $title, array $settings, string $default_save_adapter = 'form_post' ): array {
		$groups                = array();
		$declared_group_ids    = self::get_declared_group_ids( $settings );
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
				if ( null !== $current_group && null !== $current_id ) {
					self::add_group( $groups, $current_id, $current_group );
				}

				$current_id    = isset( $setting['id'] ) && is_scalar( $setting['id'] ) && '' !== (string) $setting['id']
					? (string) $setting['id']
					: self::get_unique_group_id( 'group_' . $group_index, $groups, $declared_group_ids );
				$current_group = array(
					'id'          => $current_id,
					'title'       => isset( $setting['title'] ) && is_scalar( $setting['title'] ) ? html_entity_decode( (string) $setting['title'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) : '',
					'description' => isset( $setting['desc'] ) && is_scalar( $setting['desc'] ) ? wp_kses_post( (string) $setting['desc'] ) : '',
					'order'       => isset( $setting['order'] ) ? (int) $setting['order'] : $group_index,
					'fields'      => array(),
				);
				++$group_index;
				continue;
			}

			if ( 'sectionend' === $type ) {
				$visibility_controller = null;
				if ( null !== $current_group && null !== $current_id ) {
					self::add_group( $groups, $current_id, $current_group );
				}
				$current_group = null;
				$current_id    = null;
				continue;
			}

			if ( empty( $setting['id'] ) ) {
				continue;
			}

			if ( ! $current_group ) {
				$current_id    = self::get_unique_group_id( self::DEFAULT_GROUP_ID, $groups, $declared_group_ids );
				$current_group = self::get_default_group( $current_id, $group_index );
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

		if ( null !== $current_group && null !== $current_id ) {
			self::add_group( $groups, $current_id, $current_group );
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

		$schema = array(
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

		return self::canonicalize_schema_values_for_source( $schema, true );
	}

	// Exception messages are not HTML output. Dynamic values are sanitized once
	// by invalid_schema() before the exception crosses the schema boundary.
	// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

	/**
	 * Assert that a schema can safely cross the PHP-to-JavaScript boundary.
	 *
	 * Compatibility normalization and request-owned shell defaults must run
	 * before this assertion. An invalid schema throws before it can be cached or
	 * emitted to JavaScript.
	 *
	 * @since 11.2.0
	 *
	 * @param array $schema Settings UI schema.
	 * @throws \InvalidArgumentException When the schema is malformed.
	 */
	public static function assert_valid_schema( array $schema ): void {
		self::assert_non_empty_string( $schema['id'] ?? null, 'Schema id must be a non-empty string.' );

		foreach ( array( 'title', 'section' ) as $property ) {
			if ( array_key_exists( $property, $schema ) && ! is_string( $schema[ $property ] ) ) {
				throw self::invalid_schema( sprintf( 'Schema %s must be a string.', $property ) );
			}
		}

		if ( array_key_exists( 'section', $schema ) && '' === $schema['section'] ) {
			throw self::invalid_schema( 'Schema section must be a non-empty string.' );
		}

		self::assert_page_save_strategy( $schema['save'] ?? null );
		self::assert_shell( $schema['shell'] ?? null );

		if ( ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			throw self::invalid_schema( 'Schema groups must be a map.' );
		}

		foreach ( $schema['groups'] as $group_key => $group ) {
			$group_id = (string) $group_key;
			if ( '' === $group_id ) {
				throw self::invalid_schema( 'Group map keys must be non-empty strings.' );
			}

			if ( ! is_array( $group ) ) {
				throw self::invalid_schema( sprintf( 'Group "%s" must be an array.', $group_id ) );
			}

			self::assert_non_empty_string( $group['id'] ?? null, sprintf( 'Group "%s" id must be a non-empty string.', $group_id ) );
			if ( $group_id !== $group['id'] ) {
				throw self::invalid_schema( sprintf( 'Group map key "%s" must match group id "%s".', $group_id, $group['id'] ) );
			}
		}

		$field_ids        = array();
		$visibility_rules = array();
		foreach ( $schema['groups'] as $group ) {
			$group_id = $group['id'];
			self::assert_optional_strings( $group, array( 'title', 'description' ), sprintf( 'Group "%s"', $group_id ) );

			if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) || ! ArrayUtil::array_is_list( $group['fields'] ) ) {
				throw self::invalid_schema( sprintf( 'Group "%s" fields must be a list.', $group_id ) );
			}

			foreach ( $group['fields'] as $field_index => $field ) {
				if ( ! is_array( $field ) ) {
					throw self::invalid_schema( sprintf( 'Group "%s" field %d must be an array.', $group_id, $field_index ) );
				}

				self::assert_non_empty_string( $field['id'] ?? null, sprintf( 'Group "%s" field %d id must be a non-empty string.', $group_id, $field_index ) );
				$field_id = $field['id'];
				if ( isset( $field_ids[ $field_id ] ) ) {
					throw self::invalid_schema( sprintf( 'Field id "%s" is duplicated.', $field_id ) );
				}
				if ( isset( $schema['groups'][ $field_id ] ) ) {
					throw self::invalid_schema( sprintf( 'Field id "%s" collides with a group id.', $field_id ) );
				}

				$field_ids[ $field_id ] = true;
				self::assert_field( $field );
				if ( isset( $field['visibility'] ) ) {
					$visibility_rules[ $field_id ] = $field['visibility'];
				}
			}
		}

		foreach ( $visibility_rules as $field_id => $visibility ) {
			$controller = $visibility['controller'];
			if ( ! isset( $field_ids[ $controller ] ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" visibility controller "%s" does not reference a field.', $field_id, $controller ) );
			}
		}
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
		$converted_fields = array();
		$schema           = self::canonicalize_option_values_and_collect( $schema, $converted_fields );

		self::emit_conversion_notice(
			__METHOD__,
			$converted_fields,
			/* translators: %s: comma-separated field ids. */
			__( 'A Settings UI schema provider supplied non-string option, field, or visibility values that WooCommerce converted for compatibility: %s. Update the provider to supply string values.', 'woocommerce' ),
			'11.0.0'
		);

		return $schema;
	}

	/**
	 * Canonicalize option values and collect affected field ids.
	 *
	 * @param array    $schema Settings UI schema.
	 * @param string[] $converted_fields Affected field ids.
	 * @param-out string[] $converted_fields
	 * @return array Canonicalized schema.
	 */
	private static function canonicalize_option_values_and_collect( array $schema, array &$converted_fields ): array {
		if ( ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			return $schema;
		}

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

				$option_field_ids[ $field['id'] ] = true;

				$converted = false;

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

		return $schema;
	}

	/**
	 * Canonicalize typed field values and compatibility metadata.
	 *
	 * This additive entry point preserves canonicalize_option_values() for
	 * existing callers while letting request resolution issue one aggregate
	 * compatibility notice for the complete native-provider pass.
	 *
	 * @since 11.2.0
	 *
	 * @param array $schema Settings UI schema.
	 * @return array Canonicalized schema.
	 * @throws \InvalidArgumentException When a value cannot be converted without loss.
	 */
	public static function canonicalize_schema_values( array $schema ): array {
		return self::canonicalize_schema_values_for_source( $schema, false );
	}

	/**
	 * Canonicalize typed field values for a schema source.
	 *
	 * @param array $schema Settings UI schema.
	 * @param bool  $legacy_derived Whether the schema came from legacy settings definitions.
	 * @return array Canonicalized schema.
	 * @throws \InvalidArgumentException When a value cannot be converted without loss.
	 */
	private static function canonicalize_schema_values_for_source( array $schema, bool $legacy_derived ): array {
		if ( ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			return $schema;
		}

		$converted_fields                   = array();
		$fields_requiring_form_preservation = array();
		$original_values                    = array();
		$controller_types                   = array();

		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if ( ! is_array( $field ) || ! isset( $field['id'] ) || ! is_string( $field['id'] ) ) {
					continue;
				}

				if ( array_key_exists( 'value', $field ) ) {
					$original_values[ $field['id'] ] = $field['value'];
				}
			}
			unset( $field );
		}
		unset( $group );

		$schema = self::canonicalize_option_values_and_collect( $schema, $converted_fields );
		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if ( ! is_array( $field ) || ! isset( $field['id'] ) || ! is_string( $field['id'] ) ) {
					continue;
				}

				$typed_conversion = self::canonicalize_field( $field, $legacy_derived );
				if ( $typed_conversion ) {
					$converted_fields[] = $field['id'];
				}
				if ( isset( $field['type'] ) && is_string( $field['type'] ) ) {
					$controller_types[ $field['id'] ] = $field['type'];
				}

				$original_value_changed = array_key_exists( $field['id'], $original_values )
					&& array_key_exists( 'value', $field )
					&& $original_values[ $field['id'] ] !== $field['value'];
				if (
					$typed_conversion ||
					(
						$original_value_changed &&
						is_string( $field['type'] ?? null ) &&
						in_array( $field['type'], self::TYPED_VALUE_FIELD_TYPES, true )
					)
				) {
					$fields_requiring_form_preservation[ $field['id'] ] = true;
				}
			}
			unset( $field );
		}
		unset( $group );

		self::canonicalize_typed_visibility_values( $schema, $controller_types, $converted_fields );
		self::preserve_converted_form_values( $schema, $original_values, $fields_requiring_form_preservation );

		if ( ! $legacy_derived ) {
			self::emit_conversion_notice(
				self::class . '::canonicalize_schema_values',
				$converted_fields,
				/* translators: %s: comma-separated field ids. */
				__( 'A Settings UI schema provider supplied legacy field values or metadata that WooCommerce converted for compatibility: %s. Update the provider to supply canonical values.', 'woocommerce' ),
				'11.2.0'
			);
		}

		return $schema;
	}

	/**
	 * Canonicalize one field in place.
	 *
	 * @param array $field Field definition.
	 * @param bool  $legacy_derived Whether the schema came from legacy settings definitions.
	 * @return bool Whether the field required compatibility conversion.
	 */
	private static function canonicalize_field( array &$field, bool $legacy_derived ): bool {
		$type = $field['type'] ?? null;
		if ( ! is_string( $type ) ) {
			return false;
		}

		$original_type  = $type;
		$original_value = $field['value'] ?? null;

		$numeric_validation_converted = false;

		if ( $legacy_derived && 'number' === $type && self::should_promote_to_integer( $field ) ) {
			$type          = 'integer';
			$field['type'] = $type;
		}

		if ( array_key_exists( 'value', $field ) ) {
			switch ( $type ) {
				case 'array':
					$field['value'] = self::canonicalize_array_value( $field['value'], $field['id'] );
					break;
				case 'checkbox':
					$field['value'] = self::canonicalize_checkbox_value( $field['value'], $field['id'] );
					break;
				case 'number':
					$field['value'] = self::canonicalize_number( $field['value'], false, $field['id'], 'value' );
					break;
				case 'integer':
					$field['value'] = self::canonicalize_number( $field['value'], true, $field['id'], 'value' );
					break;
				case 'datetime-local':
					$field['value'] = self::canonicalize_datetime( $field['value'], $field['id'] );
					break;
				default:
					if ( in_array( $type, self::SUPPORTED_FIELD_TYPES, true ) && ( null === $field['value'] || is_scalar( $field['value'] ) ) && ! is_string( $field['value'] ) ) {
						$field['value'] = null === $field['value'] ? '' : self::to_canonical_string( $field['value'] );
					}
					break;
			}
		}

		if ( in_array( $type, array( 'number', 'integer' ), true ) ) {
			$numeric_validation_converted = self::canonicalize_numeric_validation( $field, 'integer' === $type );
		}

		return (
			$original_type !== $field['type'] ||
			( array_key_exists( 'value', $field ) && $original_value !== $field['value'] ) ||
			$numeric_validation_converted
		);
	}

	/**
	 * Canonicalize visibility values with their controller field type.
	 *
	 * @param array                $schema Schema being canonicalized.
	 * @param array<string,string> $controller_types Field types keyed by field id.
	 * @param string[]             $converted_fields Affected field ids.
	 * @param-out string[] $converted_fields
	 */
	private static function canonicalize_typed_visibility_values( array &$schema, array $controller_types, array &$converted_fields ): void {
		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if ( ! is_array( $field ) || ! isset( $field['id'] ) || ! is_string( $field['id'] ) ) {
					continue;
				}

				$visibility = $field['visibility'] ?? null;
				if ( ! is_array( $visibility ) || ! array_key_exists( 'value', $visibility ) ) {
					continue;
				}

				$controller = $visibility['controller'] ?? null;
				if ( ! is_string( $controller ) ) {
					continue;
				}

				$type = $controller_types[ $controller ] ?? null;
				if ( ! is_string( $type ) || ! in_array( $type, self::TYPED_VALUE_FIELD_TYPES, true ) ) {
					continue;
				}

				$original  = $visibility['value'];
				$canonical = self::canonicalize_typed_visibility_value( $original, $type, $controller );
				if ( $original !== $canonical ) {
					$field['visibility']['value'] = $canonical;
					$converted_fields[]           = $field['id'];
				}
			}
			unset( $field );
		}
		unset( $group );
	}

	/**
	 * Canonicalize one visibility value or list of alternatives.
	 *
	 * @param mixed  $value Visibility value.
	 * @param string $type Controller field type.
	 * @param string $controller_id Controller field id.
	 * @return mixed
	 */
	private static function canonicalize_typed_visibility_value( $value, string $type, string $controller_id ) {
		if ( is_array( $value ) ) {
			$canonical = array();
			foreach ( $value as $key => $item ) {
				$canonical[ $key ] = 'array' === $type && ! is_array( $item )
					? $item
					: self::canonicalize_typed_visibility_item( $item, $type, $controller_id );
			}

			return $canonical;
		}

		return self::canonicalize_typed_visibility_item( $value, $type, $controller_id );
	}

	/**
	 * Canonicalize one typed visibility alternative when it is compatible.
	 *
	 * @param mixed  $value Visibility alternative.
	 * @param string $type Controller field type.
	 * @param string $controller_id Controller field id.
	 * @return mixed
	 */
	private static function canonicalize_typed_visibility_item( $value, string $type, string $controller_id ) {
		try {
			switch ( $type ) {
				case 'array':
					return self::canonicalize_array_value( $value, $controller_id );
				case 'checkbox':
					return null === $value ? null : self::canonicalize_checkbox_value( $value, $controller_id );
				case 'number':
					return self::canonicalize_number( $value, false, $controller_id, 'visibility value' );
				case 'integer':
					return self::canonicalize_number( $value, true, $controller_id, 'visibility value' );
				case 'datetime-local':
					return self::canonicalize_datetime( $value, $controller_id );
			}
		} catch ( \InvalidArgumentException $e ) {
			unset( $e );
			// Preserve incompatible alternatives instead of narrowing the existing visibility contract.
			return $value;
		}

		return $value;
	}

	/**
	 * Whether a legacy number follows the HTML integer step contract.
	 *
	 * Promotion happens only when the step is 1 and every quantity the integer
	 * path will later canonicalize — the stored value and the min/max bounds — is
	 * itself integral. A step=1 control holding a decimal value or bound (which
	 * the classic sanitizer never rejected) stays a 'number' field, so integer
	 * canonicalization does not throw and collapse the section into the fallback.
	 *
	 * @param array $field Field definition.
	 * @return bool
	 */
	private static function should_promote_to_integer( array $field ): bool {
		$attributes = isset( $field['customAttributes'] ) && is_array( $field['customAttributes'] ) ? $field['customAttributes'] : array();
		if ( ! array_key_exists( 'step', $attributes ) || '1' !== self::get_integral_decimal( $attributes['step'] ) ) {
			return false;
		}

		$validation = isset( $field['validation'] ) && is_array( $field['validation'] ) ? $field['validation'] : array();
		$candidates = array(
			$field['value'] ?? null,
			$attributes['min'] ?? null,
			$attributes['max'] ?? null,
			$validation['min'] ?? null,
			$validation['max'] ?? null,
		);

		foreach ( $candidates as $candidate ) {
			if ( null === $candidate || ( is_string( $candidate ) && '' === trim( $candidate ) ) ) {
				// An absent or empty quantity imposes no integer constraint.
				continue;
			}
			if ( null === self::get_integral_decimal( $candidate ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Canonicalize an array value to a string list.
	 *
	 * @param mixed  $value Candidate value.
	 * @param string $field_id Field id.
	 * @return array
	 * @throws \InvalidArgumentException When the value cannot become a string list.
	 */
	private static function canonicalize_array_value( $value, string $field_id ): array {
		if ( is_string( $value ) ) {
			return '' === $value ? array() : array( $value );
		}

		if ( ! is_array( $value ) || ! ArrayUtil::array_is_list( $value ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" value must be a string list.', $field_id ) );
		}

		$canonical = array();
		foreach ( $value as $item ) {
			if ( ! is_scalar( $item ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" value must be a string list.', $field_id ) );
			}
			$canonical[] = is_string( $item ) ? $item : self::to_canonical_string( $item );
		}

		return $canonical;
	}

	/**
	 * Canonicalize a checkbox value.
	 *
	 * @param mixed  $value Candidate value.
	 * @param string $field_id Field id.
	 * @return bool
	 * @throws \InvalidArgumentException When the value is ambiguous.
	 */
	private static function canonicalize_checkbox_value( $value, string $field_id ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) && in_array( $value, array( 0, 1 ), true ) ) {
			return 1 === $value;
		}

		if ( is_string( $value ) ) {
			$normalized = strtolower( trim( $value ) );
			if ( in_array( $normalized, array( '1', 'true', 'yes' ), true ) ) {
				return true;
			}
			if ( in_array( $normalized, array( '', '0', 'false', 'no' ), true ) ) {
				return false;
			}
		}

		throw self::invalid_schema( sprintf( 'Field "%s" checkbox value is ambiguous.', $field_id ) );
	}

	/**
	 * Canonicalize a number without first rounding integral strings.
	 *
	 * @param mixed  $value Candidate value.
	 * @param bool   $integer_only Whether the result must be an integer.
	 * @param string $field_id Field id.
	 * @param string $property Value or bound name.
	 * @return int|float|null
	 * @throws \InvalidArgumentException When the number is invalid, unsafe, or lossy.
	 */
	private static function canonicalize_number( $value, bool $integer_only, string $field_id, string $property ) {
		if ( null === $value || ( is_string( $value ) && '' === trim( $value ) ) ) {
			return null;
		}

		if ( is_int( $value ) ) {
			self::assert_safe_integer( (string) $value, $field_id, $property );
			return $value;
		}

		if ( is_float( $value ) ) {
			if ( ! is_finite( $value ) ) {
				throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s must be a finite number.', $field_id, $property ) );
			}

			if ( floor( $value ) === $value ) {
				$integral = sprintf( '%.0f', $value );
				self::assert_safe_integer( $integral, $field_id, $property );
				return $integer_only ? (int) $integral : $value;
			}

			if ( $integer_only ) {
				throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s must be an integer.', $field_id, $property ) );
			}

			return $value;
		}

		$integral = self::get_integral_decimal( $value );
		if ( null !== $integral ) {
			self::assert_safe_integer( $integral, $field_id, $property );

			return (int) $integral;
		}

		if ( $integer_only ) {
			throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s must be an integer.', $field_id, $property ) );
		}

		if ( ! is_string( $value ) || ! self::is_decimal_number( trim( $value ) ) ) {
			throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s must be a finite number.', $field_id, $property ) );
		}

		$number         = (float) trim( $value );
		$encoded_number = wp_json_encode( $number, JSON_PRESERVE_ZERO_FRACTION );
		if (
			! is_finite( $number ) ||
			( 0.0 === $number && ! self::decimal_string_is_zero( trim( $value ) ) ) ||
			! is_string( $encoded_number ) ||
			! self::decimal_strings_represent_same_value( trim( $value ), $encoded_number )
		) {
			throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s cannot be represented as a finite number without loss.', $field_id, $property ) );
		}

		return $number;
	}

	/**
	 * Return the exact integral decimal represented by a numeric value.
	 *
	 * @param mixed $value Candidate numeric value.
	 * @return string|null Normalized signed integer, or null when non-integral.
	 */
	private static function get_integral_decimal( $value ): ?string {
		if ( is_int( $value ) ) {
			return (string) $value;
		}

		if ( is_float( $value ) ) {
			return is_finite( $value ) && floor( $value ) === $value ? sprintf( '%.0f', $value ) : null;
		}

		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );
		if ( ! preg_match( self::DECIMAL_PATTERN, $value, $matches ) ) {
			return null;
		}

		$whole    = $matches['whole'] ?? '';
		$fraction = '' !== ( $matches['fraction'] ?? '' ) ? $matches['fraction'] : ( $matches['bare_fraction'] ?? '' );
		$digits   = ltrim( $whole . $fraction, '0' );
		$digits   = '' === $digits ? '0' : $digits;
		$exponent = $matches['exponent'] ?? '0';
		$negative = '-' === $matches['sign'];
		$scale    = strlen( $fraction );

		if ( strlen( ltrim( $exponent, '+-0' ) ) > 6 ) {
			if ( '0' === $digits ) {
				return '0';
			}

			return '-' === substr( $exponent, 0, 1 ) ? null : ( $negative ? '-' : '' ) . str_repeat( '9', 17 );
		}

		$decimal_places = $scale - (int) $exponent;
		if ( 0 < $decimal_places ) {
			if ( $decimal_places >= strlen( $digits ) ) {
				return '0' === $digits ? '0' : null;
			}

			$trailing = substr( $digits, -$decimal_places );
			if ( '' !== trim( $trailing, '0' ) ) {
				return null;
			}
			$digits = substr( $digits, 0, -$decimal_places );
		} elseif ( 0 > $decimal_places ) {
			$zeros = -$decimal_places;
			if ( 17 < strlen( $digits ) + $zeros ) {
				$digits = str_repeat( '9', 17 );
			} else {
				$digits .= str_repeat( '0', $zeros );
			}
		}

		$digits = ltrim( $digits, '0' );
		if ( '' === $digits ) {
			return '0';
		}

		return $negative ? '-' . $digits : $digits;
	}

	/**
	 * Assert an exact integer fits both JavaScript and the current PHP runtime.
	 *
	 * @param string $integer Normalized signed integer.
	 * @param string $field_id Field id.
	 * @param string $property Value or bound name.
	 * @throws \InvalidArgumentException When the integer is unsafe.
	 */
	private static function assert_safe_integer( string $integer, string $field_id, string $property ): void {
		$absolute = ltrim( $integer, '-' );
		if ( self::unsigned_decimal_is_greater( $absolute, self::JAVASCRIPT_SAFE_INTEGER ) ) {
			throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s is outside the JavaScript safe integer range.', $field_id, $property ) );
		}

		$php_integer_limit = '-' === substr( $integer, 0, 1 ) ? ltrim( (string) PHP_INT_MIN, '-' ) : (string) PHP_INT_MAX;
		if ( self::unsigned_decimal_is_greater( $absolute, $php_integer_limit ) ) {
			throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s cannot be represented as an integer on this PHP platform.', $field_id, $property ) );
		}
	}

	/**
	 * Compare normalized unsigned decimal strings.
	 *
	 * @param string $left Left operand.
	 * @param string $right Right operand.
	 * @return bool
	 */
	private static function unsigned_decimal_is_greater( string $left, string $right ): bool {
		$left  = ltrim( $left, '0' );
		$right = ltrim( $right, '0' );
		$left  = '' === $left ? '0' : $left;
		$right = '' === $right ? '0' : $right;

		return strlen( $left ) !== strlen( $right ) ? strlen( $left ) > strlen( $right ) : 0 < strcmp( $left, $right );
	}

	/**
	 * Whether a string follows the HTML decimal-number grammar.
	 *
	 * @param string $value Candidate number.
	 * @return bool
	 */
	private static function is_decimal_number( string $value ): bool {
		return 1 === preg_match( self::DECIMAL_PATTERN, $value );
	}

	/**
	 * Whether a decimal numeric string represents zero.
	 *
	 * @param string $value Candidate number.
	 * @return bool
	 */
	private static function decimal_string_is_zero( string $value ): bool {
		$mantissa = substr( $value, 0, strcspn( $value, 'eE' ) );
		return '' === trim( $mantissa, '+-0.' );
	}

	/**
	 * Whether two decimal strings represent the same normalized value.
	 *
	 * @param string $left Left decimal value.
	 * @param string $right Right decimal value.
	 * @return bool
	 */
	private static function decimal_strings_represent_same_value( string $left, string $right ): bool {
		$normalized_left  = self::normalize_decimal_string( $left );
		$normalized_right = self::normalize_decimal_string( $right );

		return null !== $normalized_left && $normalized_left === $normalized_right;
	}

	/**
	 * Normalize a decimal string to its significant digits and base-ten power.
	 *
	 * @param string $value Decimal value.
	 * @return array{string, int}|null Normalized signed digits and power, or null when invalid.
	 */
	private static function normalize_decimal_string( string $value ): ?array {
		if ( ! preg_match( self::DECIMAL_PATTERN, $value, $matches ) ) {
			return null;
		}

		$whole    = $matches['whole'] ?? '';
		$fraction = '' !== ( $matches['fraction'] ?? '' ) ? $matches['fraction'] : ( $matches['bare_fraction'] ?? '' );
		$digits   = ltrim( $whole . $fraction, '0' );
		if ( '' === $digits ) {
			return array( '0', 0 );
		}

		$exponent        = $matches['exponent'] ?? '0';
		$exponent_digits = ltrim( $exponent, '+-0' );
		if ( 6 < strlen( $exponent_digits ) ) {
			return null;
		}

		$power          = (int) $exponent - strlen( $fraction );
		$trimmed_digits = rtrim( $digits, '0' );
		$power         += strlen( $digits ) - strlen( $trimmed_digits );
		$signed_digits  = '-' === $matches['sign'] ? '-' . $trimmed_digits : $trimmed_digits;

		return array( $signed_digits, $power );
	}

	/**
	 * Canonicalize numeric validation and mirror it to legacy attributes.
	 *
	 * @param array $field Field definition.
	 * @param bool  $integer_only Whether bounds must be integers.
	 * @return bool Whether provider-supplied metadata required compatibility conversion.
	 * @throws \InvalidArgumentException When bounds are invalid or disagree.
	 */
	private static function canonicalize_numeric_validation( array &$field, bool $integer_only ): bool {
		$attributes = $field['customAttributes'] ?? array();
		$validation = $field['validation'] ?? array();
		$converted  = false;

		if ( ! is_array( $attributes ) || ! is_array( $validation ) ) {
			return false;
		}

		foreach ( array( 'min', 'max' ) as $bound ) {
			$has_attribute    = array_key_exists( $bound, $attributes );
			$has_validation   = array_key_exists( $bound, $validation );
			$empty_validation = $has_validation && is_string( $validation[ $bound ] ) && '' === trim( $validation[ $bound ] );

			if ( $has_attribute && is_string( $attributes[ $bound ] ) && '' === trim( $attributes[ $bound ] ) ) {
				unset( $attributes[ $bound ] );
				$has_attribute = false;
			}
			if ( $empty_validation ) {
				unset( $validation[ $bound ] );
				$has_validation = false;
			}
			if ( ! $has_attribute && ! $has_validation ) {
				$converted = $converted || $empty_validation;
				continue;
			}

			$original_attribute  = $has_attribute ? $attributes[ $bound ] : null;
			$original_validation = $has_validation ? $validation[ $bound ] : null;
			$attribute_value     = $has_attribute ? self::canonicalize_number( $original_attribute, $integer_only, $field['id'], $bound ) : null;
			$validation_value    = $has_validation ? self::canonicalize_number( $original_validation, $integer_only, $field['id'], $bound ) : null;

			if ( $has_attribute && $has_validation && ! self::numeric_values_agree( $attribute_value, $validation_value ) ) {
				throw self::invalid_schema( sprintf( 'Field "%1$s" %2$s disagrees between customAttributes and validation.', $field['id'], $bound ) );
			}

			$canonical            = $has_validation ? $validation_value : $attribute_value;
			$validation[ $bound ] = $canonical;
			$attributes[ $bound ] = $canonical;
			$converted            = $converted ||
				$empty_validation ||
				( $has_attribute && ! $has_validation ) ||
				( $has_attribute && $has_validation && $original_attribute !== $attribute_value ) ||
				( $has_validation && $original_validation !== $validation_value );
		}

		if ( ! empty( $validation ) ) {
			$field['validation'] = $validation;
		} else {
			unset( $field['validation'] );
		}
		if ( ! empty( $attributes ) ) {
			$field['customAttributes'] = $attributes;
		} else {
			unset( $field['customAttributes'] );
		}

		return $converted;
	}

	/**
	 * Whether two numeric values describe the same number.
	 *
	 * @param mixed $left Left operand.
	 * @param mixed $right Right operand.
	 * @return bool
	 */
	private static function numeric_values_agree( $left, $right ): bool {
		if ( ! is_numeric( $left ) || ! is_numeric( $right ) ) {
			return false;
		}

		return (float) $left === (float) $right;
	}

	/**
	 * Canonicalize a store-local or already-qualified datetime.
	 *
	 * @param mixed  $value Candidate value.
	 * @param string $field_id Field id.
	 * @return string|null
	 * @throws \InvalidArgumentException When the datetime is malformed.
	 */
	private static function canonicalize_datetime( $value, string $field_id ): ?string {
		if ( null === $value || ( is_string( $value ) && '' === trim( $value ) ) ) {
			return null;
		}

		if ( ! is_string( $value ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" datetime value must be a string or null.', $field_id ) );
		}

		$value = trim( $value );
		// Seconds occupy index 16 (`:ss`) for both local and qualified values.
		$has_seconds = isset( $value[16] ) && ':' === $value[16];
		$wall_format = $has_seconds ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i';

		if ( preg_match( self::LOCAL_DATETIME_PATTERN, $value ) ) {
			$format   = '!' . $wall_format;
			$datetime = \DateTimeImmutable::createFromFormat( $format, $value, wp_timezone() );
		} elseif ( preg_match( self::QUALIFIED_DATETIME_PATTERN, $value ) ) {
			$format   = '!' . $wall_format . 'P';
			$datetime = \DateTimeImmutable::createFromFormat( $format, $value );
		} else {
			throw self::invalid_schema( sprintf( 'Field "%s" datetime value is malformed.', $field_id ) );
		}

		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $datetime || ( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" datetime value is malformed.', $field_id ) );
		}

		return $datetime->format( 'Y-m-d\TH:i:sP' );
	}

	/**
	 * Preserve valid pre-conversion form values for changed fields.
	 *
	 * @param array               $schema Canonical schema.
	 * @param array               $original_values Original values keyed by field id.
	 * @param array<string, bool> $fields_requiring_preservation Fields changed by typed canonicalization.
	 * @throws \InvalidArgumentException When a converted value has no safe form representation.
	 */
	private static function preserve_converted_form_values( array &$schema, array $original_values, array $fields_requiring_preservation ): void {
		$page_save_adapter = isset( $schema['save'] ) && is_array( $schema['save'] ) ? ( $schema['save']['adapter'] ?? 'form_post' ) : 'form_post';
		if ( 'form_post' !== $page_save_adapter ) {
			return;
		}

		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if (
					! is_array( $field ) ||
					! isset( $field['id'] ) ||
					! isset( $fields_requiring_preservation[ $field['id'] ] ) ||
					! array_key_exists( $field['id'], $original_values )
				) {
					continue;
				}

				$original = $original_values[ $field['id'] ];
				if ( ( $field['value'] ?? null ) === $original || 'form_post' !== self::get_field_save_adapter( $field ) ) {
					continue;
				}

				if ( isset( $field['save'] ) && is_array( $field['save'] ) && array_key_exists( 'initialValue', $field['save'] ) ) {
					continue;
				}

				$form_value = 'array' === ( $field['type'] ?? null ) && '' === $original ? array() : $original;
				if ( ! self::is_form_value_for_field( $form_value, $field ) ) {
					throw self::invalid_schema( sprintf( 'Field "%s" must define save.initialValue because its original value cannot be replayed safely through classic form-post semantics.', $field['id'] ) );
				}

				if ( ! isset( $field['save'] ) || ! is_array( $field['save'] ) ) {
					$field['save'] = array( 'adapter' => 'form_post' );
				}
				$field['save']['initialValue'] = $form_value;
			}
			unset( $field );
		}
		unset( $group );
	}

	/**
	 * Get a field's effective save adapter.
	 *
	 * @param array $field Field definition.
	 * @return mixed
	 */
	private static function get_field_save_adapter( array $field ) {
		return isset( $field['save'] ) && is_array( $field['save'] ) ? ( $field['save']['adapter'] ?? 'form_post' ) : 'form_post';
	}

	/**
	 * Whether a value is a valid HTML form representation.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_form_value( $value ): bool {
		if ( is_string( $value ) ) {
			return true;
		}

		if ( ! is_array( $value ) || ! ArrayUtil::array_is_list( $value ) ) {
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
	 * Whether a value can be replayed safely for a field through classic
	 * form-post semantics.
	 *
	 * @param mixed $value Candidate form value.
	 * @param array $field Canonical field definition.
	 * @return bool
	 */
	private static function is_form_value_for_field( $value, array $field ): bool {
		if ( ! self::is_form_value( $value ) ) {
			return false;
		}

		if ( 'array' !== ( $field['type'] ?? null ) && ! is_string( $value ) ) {
			return false;
		}

		$type = $field['type'] ?? null;
		if ( ! is_string( $type ) || ! in_array( $type, self::TYPED_VALUE_FIELD_TYPES, true ) ) {
			return array_key_exists( 'value', $field ) && $value === $field['value'];
		}
		if ( ! array_key_exists( 'value', $field ) ) {
			return false;
		}

		try {
			switch ( $type ) {
				case 'array':
					$canonical_value = self::canonicalize_array_value( $value, $field['id'] );
					break;
				case 'checkbox':
					$canonical_value = '1' === $value || 'yes' === $value;
					break;
				case 'number':
					$canonical_value = self::canonicalize_number( $value, false, $field['id'], 'save.initialValue' );
					break;
				case 'integer':
					$canonical_value = self::canonicalize_number( $value, true, $field['id'], 'save.initialValue' );
					break;
				case 'datetime-local':
					$canonical_value = self::canonicalize_datetime( $value, $field['id'] );
					break;
				default:
					return true;
			}
		} catch ( \InvalidArgumentException $exception ) {
			unset( $exception );
			return false;
		}

		$current_value = $field['value'];
		if ( 'number' === $type && null !== $canonical_value && null !== $current_value ) {
			return self::numeric_values_agree( $canonical_value, $current_value );
		}

		return $canonical_value === $current_value;
	}

	/**
	 * Emit one compatibility notice for all affected fields.
	 *
	 * @param string   $method Method name reported by the notice.
	 * @param string[] $field_ids Affected field ids.
	 * @param string   $message Translatable sprintf message.
	 * @param string   $version Version when the notice was introduced.
	 */
	private static function emit_conversion_notice( string $method, array $field_ids, string $message, string $version ): void {
		if ( empty( $field_ids ) ) {
			return;
		}

		wc_doing_it_wrong(
			$method,
			sprintf(
				/* translators: %s: comma-separated field ids. */
				esc_html( $message ),
				esc_html( implode( ', ', array_unique( $field_ids ) ) )
			),
			$version
		);
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
	 * @param mixed               $rule Candidate visibility rule.
	 * @param array<string, bool> $option_field_ids Ids of fields carrying an options array.
	 * @return bool
	 */
	private static function is_canonicalizable_visibility_rule( $rule, array $option_field_ids ): bool {
		$controller = is_array( $rule ) ? ( $rule['controller'] ?? null ) : null;

		return is_array( $rule )
			&& array_key_exists( 'value', $rule )
			&& is_string( $controller )
			&& isset( $option_field_ids[ $controller ] );
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
		$save           = self::get_save_schema( $setting, $default_save_adapter );
		if ( 'info' === $type ) {
			$save = array( 'adapter' => 'none' );
		}
		$raw_value = self::get_field_raw_value( $setting, $save );
		$field     = array(
			'id'          => $id,
			'label'       => self::get_field_label( $setting, $id, $type ),
			'type'        => $canonical_type,
			'description' => self::get_field_description( $setting, $type ),
			'value'       => $raw_value,
			'save'        => $save,
		);

		if ( 'form_post' === ( $save['adapter'] ?? null ) && ! array_key_exists( 'initialValue', $save ) && self::is_form_value( $raw_value ) ) {
			$field['save']['initialValue'] = 'array' === $canonical_type && '' === $raw_value ? array() : $raw_value;
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

		if ( 'info' === $type ) {
			if ( '' === $field['description'] && isset( $setting['text'] ) && is_scalar( $setting['text'] ) ) {
				$field['description'] = wp_kses_post( (string) $setting['text'] );
			}
			$field['save'] = array( 'adapter' => 'none' );
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
	 * Get the raw value for a legacy field.
	 *
	 * Option-backed values are read only for legacy form-post fields. The option
	 * reader supports flat and one-level nested names.
	 *
	 * @param array $setting Legacy field definition.
	 * @param array $save Field save metadata.
	 * @return mixed
	 * @throws \InvalidArgumentException When the effective field name is unsupported.
	 */
	private static function get_field_raw_value( array $setting, array $save ) {
		if ( array_key_exists( 'value', $setting ) ) {
			return $setting['value'];
		}

		$default = $setting['default'] ?? '';
		if ( 'form_post' !== ( $save['adapter'] ?? null ) ) {
			return $default;
		}

		$field_name = $save['name'] ?? $setting['id'] ?? '';
		if ( ! is_string( $field_name ) || '' === $field_name ) {
			throw self::invalid_schema( 'A legacy form-post field must define a non-empty field name.' );
		}

		$type = isset( $setting['type'] ) && is_string( $setting['type'] ) ? self::normalize_type( $setting['type'] ) : 'text';
		if ( 'array' === $type && '[]' === substr( $field_name, -2 ) ) {
			$field_name = substr( $field_name, 0, -2 );
		}

		if ( ! self::is_supported_form_post_name( $field_name, false ) ) {
			throw self::invalid_schema( sprintf( 'Legacy form-post field "%s" has an unsupported name.', $field_name ) );
		}

		return woocommerce_settings_get_option( $field_name, $default );
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

		$field_name = isset( $setting['field_name'] ) && is_scalar( $setting['field_name'] ) && '' !== (string) $setting['field_name']
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
		$type = isset( $setting['type'] ) && is_string( $setting['type'] ) ? $setting['type'] : '';

		if ( 'single_select_page' === $type ) {
			return self::get_page_options( $setting );
		}

		if ( 'single_select_country' === $type ) {
			$countries = self::get_countries_controller();

			return $countries ? self::get_country_and_state_options( $countries ) : array();
		}

		if ( 'multi_select_countries' === $type ) {
			if ( ! isset( $setting['options'] ) || ! is_array( $setting['options'] ) || empty( $setting['options'] ) ) {
				$countries_controller = self::get_countries_controller();
				if ( ! $countries_controller ) {
					return array();
				}

				$options = $countries_controller->get_countries();
			} else {
				$options = $setting['options'];
			}

			asort( $options );

			return self::normalize_options( $options );
		}

		if ( ! isset( $setting['options'] ) || ! is_array( $setting['options'] ) ) {
			return array();
		}

		return self::normalize_options( $setting['options'] );
	}

	/**
	 * Get the initialized WooCommerce countries controller.
	 *
	 * @return \WC_Countries|null
	 */
	private static function get_countries_controller(): ?\WC_Countries {
		if ( ! function_exists( 'WC' ) ) {
			return null;
		}

		$woocommerce = WC();

		return $woocommerce && $woocommerce->countries instanceof \WC_Countries ? $woocommerce->countries : null;
	}

	/**
	 * Build options for a legacy page selector.
	 *
	 * @param array $setting Legacy field definition.
	 * @return array
	 */
	private static function get_page_options( array $setting ): array {
		$args = array(
			'sort_column' => 'menu_order',
			'sort_order'  => 'ASC',
			'post_status' => array( 'publish', 'private', 'draft' ),
		);

		if ( isset( $setting['args'] ) && is_array( $setting['args'] ) ) {
			$args = wp_parse_args( $setting['args'], $args );
		}

		$options = array(
			array(
				'label' => __( 'Select a page...', 'woocommerce' ),
				'value' => '',
			),
		);

		$pages = get_pages( $args );
		if ( ! is_array( $pages ) ) {
			return $options;
		}

		foreach ( $pages as $page ) {
			$options[] = array(
				'label' => wp_strip_all_tags( $page->post_title ),
				'value' => (string) $page->ID,
			);
		}

		return $options;
	}

	/**
	 * Build country and state options for a legacy country selector.
	 *
	 * @param \WC_Countries $countries Countries controller.
	 * @return array
	 */
	private static function get_country_and_state_options( \WC_Countries $countries ): array {
		$options = array();
		foreach ( $countries->get_countries() as $country_code => $country_label ) {
			$states = $countries->get_states( $country_code );
			if ( $states ) {
				foreach ( $states as $state_code => $state_label ) {
					$options[] = array(
						'label' => wp_strip_all_tags( $country_label . ' — ' . $state_label ),
						'value' => $country_code . ':' . $state_code,
					);
				}
				continue;
			}

			$options[] = array(
				'label' => wp_strip_all_tags( $country_label ),
				'value' => (string) $country_code,
			);
		}

		return $options;
	}

	/**
	 * Normalize an option map.
	 *
	 * @param array $raw_options Raw option map.
	 * @return array
	 */
	private static function normalize_options( array $raw_options ): array {
		$options = array();
		foreach ( $raw_options as $value => $label ) {
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

	// The assertion helpers deliberately propagate InvalidArgumentException to
	// the public boundary method, whose contract documents that exception.
	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.Missing

	/**
	 * Assert page-level save metadata.
	 *
	 * @param mixed $save Save metadata, or null when omitted.
	 */
	private static function assert_page_save_strategy( $save ): void {
		if ( null === $save ) {
			return;
		}

		if ( ! is_array( $save ) ) {
			throw self::invalid_schema( 'Schema save strategy must be an array.' );
		}

		$adapter = $save['adapter'] ?? null;
		if ( ! is_string( $adapter ) || ! in_array( $adapter, array( 'custom', 'form_post', 'none' ), true ) ) {
			throw self::invalid_schema( 'Schema save adapter must be "custom", "form_post", or "none".' );
		}

		if ( 'custom' === $adapter ) {
			self::assert_non_empty_string( $save['handler'] ?? null, 'Schema custom save strategy must define a non-empty handler.' );
		} elseif ( array_key_exists( 'handler', $save ) ) {
			throw self::invalid_schema( 'Schema save handler is only valid for the "custom" adapter.' );
		}
	}

	/**
	 * Assert shell metadata.
	 *
	 * @param mixed $shell Shell metadata, or null when omitted.
	 */
	private static function assert_shell( $shell ): void {
		if ( null === $shell ) {
			return;
		}

		if ( ! is_array( $shell ) ) {
			throw self::invalid_schema( 'Schema shell must be an array.' );
		}

		self::assert_optional_strings( $shell, array( 'title', 'subtitle' ), 'Shell' );
		if ( isset( $shell['header'] ) && ! in_array( $shell['header'], array( 'hidden', 'visible' ), true ) ) {
			throw self::invalid_schema( 'Shell header must be "hidden" or "visible".' );
		}

		if ( array_key_exists( 'navigationComponent', $shell ) ) {
			self::assert_non_empty_string( $shell['navigationComponent'], 'Shell navigationComponent must be a non-empty string.' );
		}

		foreach ( array( 'navigation', 'sectionNavigation' ) as $property ) {
			if ( ! array_key_exists( $property, $shell ) ) {
				continue;
			}

			self::assert_shell_navigation( $shell[ $property ], 'Shell ' . $property );
		}

		self::assert_shell_breadcrumbs( $shell['breadcrumbs'] ?? null );
		self::assert_shell_badges( $shell['badges'] ?? null );
	}

	/**
	 * Assert shell navigation items.
	 *
	 * @param mixed  $items Navigation items.
	 * @param string $context Error message context.
	 */
	private static function assert_shell_navigation( $items, string $context ): void {
		if ( ! is_array( $items ) || ! ArrayUtil::array_is_list( $items ) ) {
			throw self::invalid_schema( $context . ' must be a list.' );
		}

		$ids = array();
		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				throw self::invalid_schema( sprintf( '%s item %d must be an array.', $context, $index ) );
			}

			self::assert_non_empty_string( $item['id'] ?? null, sprintf( '%s item %d id must be a non-empty string.', $context, $index ) );
			if ( isset( $ids[ $item['id'] ] ) ) {
				throw self::invalid_schema( sprintf( '%s item id "%s" is duplicated.', $context, $item['id'] ) );
			}
			$ids[ $item['id'] ] = true;

			foreach ( array( 'label', 'href' ) as $property ) {
				if ( ! isset( $item[ $property ] ) || ! is_string( $item[ $property ] ) ) {
					throw self::invalid_schema( sprintf( '%s item %d %s must be a string.', $context, $index, $property ) );
				}
			}

			if ( isset( $item['active'] ) && ! is_bool( $item['active'] ) ) {
				throw self::invalid_schema( sprintf( '%s item %d active must be a boolean.', $context, $index ) );
			}
		}
	}

	/**
	 * Assert shell breadcrumbs.
	 *
	 * @param mixed $breadcrumbs Breadcrumb metadata, or null when omitted.
	 */
	private static function assert_shell_breadcrumbs( $breadcrumbs ): void {
		if ( null === $breadcrumbs ) {
			return;
		}

		if ( ! is_array( $breadcrumbs ) || ! ArrayUtil::array_is_list( $breadcrumbs ) ) {
			throw self::invalid_schema( 'Shell breadcrumbs must be a list.' );
		}

		foreach ( $breadcrumbs as $index => $breadcrumb ) {
			if ( ! is_array( $breadcrumb ) || ! isset( $breadcrumb['label'] ) || ! is_string( $breadcrumb['label'] ) ) {
				throw self::invalid_schema( sprintf( 'Shell breadcrumb %d label must be a string.', $index ) );
			}

			if ( isset( $breadcrumb['href'] ) && ! is_string( $breadcrumb['href'] ) ) {
				throw self::invalid_schema( sprintf( 'Shell breadcrumb %d href must be a string.', $index ) );
			}
		}
	}

	/**
	 * Assert shell badges.
	 *
	 * @param mixed $badges Badge metadata, or null when omitted.
	 */
	private static function assert_shell_badges( $badges ): void {
		if ( null === $badges ) {
			return;
		}

		if ( ! is_array( $badges ) || ! ArrayUtil::array_is_list( $badges ) ) {
			throw self::invalid_schema( 'Shell badges must be a list.' );
		}

		foreach ( $badges as $index => $badge ) {
			if ( ! is_array( $badge ) || ! isset( $badge['label'] ) || ! is_string( $badge['label'] ) ) {
				throw self::invalid_schema( sprintf( 'Shell badge %d label must be a string.', $index ) );
			}

			if ( isset( $badge['intent'] ) && ! is_string( $badge['intent'] ) ) {
				throw self::invalid_schema( sprintf( 'Shell badge %d intent must be a string.', $index ) );
			}
		}
	}


	/**
	 * Assert a field definition.
	 *
	 * @param array $field Field definition.
	 */
	private static function assert_field( array $field ): void {
		$field_id = $field['id'];
		if ( ! isset( $field['label'] ) || ! is_string( $field['label'] ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" label must be a string.', $field_id ) );
		}

		$type = $field['type'] ?? null;
		self::assert_non_empty_string( $type, sprintf( 'Field "%s" type must be a non-empty string.', $field_id ) );

		self::assert_optional_strings( $field, array( 'description', 'placeholder' ), sprintf( 'Field "%s"', $field_id ) );
		if ( isset( $field['disabled'] ) && ! is_bool( $field['disabled'] ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" disabled must be a boolean.', $field_id ) );
		}

		if ( array_key_exists( 'component', $field ) ) {
			self::assert_non_empty_string( $field['component'], sprintf( 'Field "%s" component must be a non-empty string.', $field_id ) );
		}

		self::assert_field_value( $field );
		self::assert_field_options( $field );
		self::assert_custom_attributes( $field );
		self::assert_field_validation( $field );
		self::assert_field_save( $field );
		self::assert_visibility( $field );
	}

	/**
	 * Assert a field value when supplied.
	 *
	 * @param array $field Field definition.
	 */
	private static function assert_field_value( array $field ): void {
		if ( ! array_key_exists( 'value', $field ) ) {
			return;
		}

		$value = $field['value'];
		switch ( $field['type'] ) {
			case 'array':
				$valid = is_array( $value ) && self::is_form_value( $value );
				break;
			case 'checkbox':
				$valid = is_bool( $value );
				break;
			case 'number':
				$valid = self::is_canonical_number( $value );
				break;
			case 'integer':
				$valid = null === $value || ( is_int( $value ) && self::is_canonical_number( $value ) );
				break;
			case 'datetime-local':
				$valid = null === $value || ( is_string( $value ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $value ) );
				break;
			default:
				$valid = in_array( $field['type'], self::SUPPORTED_FIELD_TYPES, true )
					? is_string( $value )
					: self::is_settings_value( $value );
				break;
		}

		if ( ! $valid ) {
			throw self::invalid_schema( sprintf( 'Field "%s" value is invalid for type "%s".', $field['id'], $field['type'] ) );
		}
	}

	/**
	 * Assert choice options.
	 *
	 * @param array $field Field definition.
	 */
	private static function assert_field_options( array $field ): void {
		if ( ! array_key_exists( 'options', $field ) ) {
			return;
		}

		$field_id = $field['id'];
		$options  = $field['options'];

		if ( ! is_array( $options ) || ! ArrayUtil::array_is_list( $options ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" options must be a list.', $field_id ) );
		}

		foreach ( $options as $index => $option ) {
			if ( ! is_array( $option ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" option %d must be an array.', $field_id, $index ) );
			}

			foreach ( array( 'label', 'value' ) as $property ) {
				if ( ! isset( $option[ $property ] ) || ! is_string( $option[ $property ] ) ) {
					throw self::invalid_schema( sprintf( 'Field "%s" option %d %s must be a string.', $field_id, $index, $property ) );
				}
			}
		}
	}

	/**
	 * Assert custom input attributes.
	 *
	 * @param array $field Field definition.
	 */
	private static function assert_custom_attributes( array $field ): void {
		if ( ! array_key_exists( 'customAttributes', $field ) ) {
			return;
		}

		if ( ! is_array( $field['customAttributes'] ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" customAttributes must be a map.', $field['id'] ) );
		}

		foreach ( $field['customAttributes'] as $attribute => $value ) {
			if ( ! is_string( $attribute ) || '' === $attribute ) {
				throw self::invalid_schema( sprintf( 'Field "%s" custom attribute names must be non-empty strings.', $field['id'] ) );
			}

			if ( ! is_scalar( $value ) || ( is_float( $value ) && ! is_finite( $value ) ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" custom attribute "%s" has an invalid value.', $field['id'], $attribute ) );
			}

			if ( in_array( $attribute, self::RANGE_ATTRIBUTES, true ) ) {
				$is_numeric_field  = in_array( $field['type'], array( 'number', 'integer' ), true );
				$is_temporal_field = in_array( $field['type'], self::TEMPORAL_RANGE_FIELD_TYPES, true );
				if ( ! $is_numeric_field && ! $is_temporal_field && in_array( $field['type'], self::SUPPORTED_FIELD_TYPES, true ) ) {
					throw self::invalid_schema( sprintf( 'Field "%s" may define "%s" only when its type supports range attributes.', $field['id'], $attribute ) );
				}
				if ( ! $is_numeric_field ) {
					continue;
				}

				$allow_any        = 'step' === $attribute;
				$is_any           = $allow_any && is_string( $value ) && 0 === strcasecmp( $value, 'any' );
				$is_integer_field = 'integer' === $field['type'];
				$valid            = $allow_any
					? $is_any || ( self::is_finite_number( $value ) && ( $is_integer_field || 0 < (float) $value ) )
					: self::is_canonical_number( $value );
				if ( ! $valid ) {
					$message = $allow_any
						? sprintf( 'Field "%s" custom attribute "step" must be a positive finite number or "any".', $field['id'] )
						: sprintf( 'Field "%s" custom attribute "%s" must be a finite number.', $field['id'], $attribute );
					throw self::invalid_schema( $message );
				}

				if ( 'integer' === $field['type'] && 'step' === $attribute ) {
					$integer_step = self::get_integral_decimal( $value );
					if ( null === $integer_step || '0' === $integer_step || '-' === substr( $integer_step, 0, 1 ) ) {
						throw self::invalid_schema( sprintf( 'Field "%s" custom attribute "step" must be a positive integer.', $field['id'] ) );
					}
				}

				if ( 'integer' === $field['type'] && in_array( $attribute, array( 'min', 'max' ), true ) && ! is_int( $value ) ) {
					throw self::invalid_schema( sprintf( 'Field "%s" custom attribute "%s" must be an integer.', $field['id'], $attribute ) );
				}
			}
		}
	}

	/**
	 * Assert canonical field validation metadata.
	 *
	 * @param array $field Field definition.
	 */
	private static function assert_field_validation( array $field ): void {
		if ( ! array_key_exists( 'validation', $field ) ) {
			return;
		}

		if ( ! is_array( $field['validation'] ) || ! in_array( $field['type'], array( 'number', 'integer' ), true ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" validation is supported only for numeric fields.', $field['id'] ) );
		}

		foreach ( $field['validation'] as $rule => $value ) {
			if ( ! in_array( $rule, array( 'min', 'max' ), true ) || null === $value || ! self::is_canonical_number( $value ) ) {
				throw self::invalid_schema( sprintf( 'Field "%1$s" validation rule "%2$s" must be a finite numeric bound.', $field['id'], (string) $rule ) );
			}

			if ( 'integer' === $field['type'] && ! is_int( $value ) ) {
				throw self::invalid_schema( sprintf( 'Field "%1$s" validation rule "%2$s" must be an integer.', $field['id'], $rule ) );
			}
		}
	}

	/**
	 * Assert field save metadata.
	 *
	 * @param array $field Field definition.
	 */
	private static function assert_field_save( array $field ): void {
		$save = $field['save'] ?? null;
		if ( null !== $save && ! is_array( $save ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" save metadata must be an array.', $field['id'] ) );
		}

		$adapter = self::get_field_save_adapter( $field );
		if ( ! is_string( $adapter ) || ! in_array( $adapter, array( 'form_post', 'none' ), true ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" save adapter must be "form_post" or "none".', $field['id'] ) );
		}

		if ( is_array( $save ) && array_key_exists( 'name', $save ) ) {
			self::assert_non_empty_string( $save['name'], sprintf( 'Field "%s" save name must be a non-empty string.', $field['id'] ) );
		}

		if ( 'form_post' === $adapter ) {
			$name = is_array( $save ) && array_key_exists( 'name', $save ) ? $save['name'] : $field['id'];
			if ( ! self::is_supported_form_post_name( $name, 'array' === $field['type'] ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" save name "%s" is not a supported form-post field name.', $field['id'], $name ) );
			}
		}

		if ( is_array( $save ) && array_key_exists( 'initialValue', $save ) ) {
			if ( ! self::is_form_value( $save['initialValue'] ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" save.initialValue must be a string or string list.', $field['id'] ) );
			}

			if ( ! self::is_form_value_for_field( $save['initialValue'], $field ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" save.initialValue cannot be replayed safely through classic form-post semantics.', $field['id'] ) );
			}
		}

		if ( 'info' === $field['type'] && 'none' !== $adapter ) {
			throw self::invalid_schema( sprintf( 'Field "%s" of type "info" must use the "none" save adapter.', $field['id'] ) );
		}
	}

	/**
	 * Whether a field name can be serialized by the form-post adapter.
	 *
	 * @param mixed $name Candidate field name.
	 * @param bool  $is_array Whether the field posts a string list.
	 * @return bool
	 */
	private static function is_supported_form_post_name( $name, bool $is_array ): bool {
		if ( ! is_string( $name ) || '' === $name ) {
			return false;
		}

		$base_name = $is_array && '[]' === substr( $name, -2 ) ? substr( $name, 0, -2 ) : $name;
		return 1 === preg_match( '/^[^\[\]]+(?:\[[^\[\]]+\])?$/', $base_name );
	}

	/**
	 * Assert field visibility metadata.
	 *
	 * @param array $field Field definition.
	 */
	private static function assert_visibility( array $field ): void {
		if ( ! array_key_exists( 'visibility', $field ) ) {
			return;
		}

		$visibility = $field['visibility'];
		if ( ! is_array( $visibility ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" visibility must be an array.', $field['id'] ) );
		}

		self::assert_non_empty_string( $visibility['controller'] ?? null, sprintf( 'Field "%s" visibility controller must be a non-empty string.', $field['id'] ) );
		if ( array_key_exists( 'value', $visibility ) && ! self::is_visibility_value( $visibility['value'] ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" visibility value is invalid.', $field['id'] ) );
		}
	}

	/**
	 * Whether a value is representable by the visibility rule contract.
	 *
	 * Visibility rules accept either one settings value or a list of settings
	 * values. String lists are valid in both positions and are disambiguated by
	 * the renderer at comparison time.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_visibility_value( $value ): bool {
		if ( self::is_settings_value( $value ) ) {
			return true;
		}

		return is_array( $value )
			&& ArrayUtil::array_is_list( $value )
			&& count( $value ) === count( array_filter( $value, array( __CLASS__, 'is_settings_value' ) ) );
	}

	/**
	 * Whether a value is representable by the Settings UI state contract.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_settings_value( $value ): bool {
		if ( null === $value || is_string( $value ) || is_bool( $value ) || is_int( $value ) ) {
			return true;
		}

		if ( is_float( $value ) ) {
			return is_finite( $value );
		}

		return is_array( $value ) && self::is_form_value( $value );
	}

	/**
	 * Whether a value is a finite number accepted by the renderer.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_finite_number( $value ): bool {
		if ( is_int( $value ) ) {
			return true;
		}

		if ( is_float( $value ) ) {
			return is_finite( $value );
		}

		if ( ! is_string( $value ) ) {
			return false;
		}

		return is_numeric( $value ) && is_finite( (float) $value );
	}

	/**
	 * Whether a value satisfies the final JavaScript number contract.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_canonical_number( $value ): bool {
		if ( null === $value ) {
			return true;
		}

		if ( is_int( $value ) ) {
			/**
			 * Unsigned decimal representation.
			 *
			 * @var string $absolute
			 */
			$absolute = ltrim( (string) $value, '-' );
			return ! self::unsigned_decimal_is_greater( $absolute, self::JAVASCRIPT_SAFE_INTEGER );
		}

		if ( ! is_float( $value ) || ! is_finite( $value ) ) {
			return false;
		}

		/**
		 * Unsigned decimal representation.
		 *
		 * @var string $absolute
		 */
		$absolute = ltrim( sprintf( '%.0f', $value ), '-' );
		return floor( $value ) !== $value || ! self::unsigned_decimal_is_greater( $absolute, self::JAVASCRIPT_SAFE_INTEGER );
	}

	/**
	 * Assert optional string properties.
	 *
	 * @param array    $value Candidate container.
	 * @param string[] $properties Property names.
	 * @param string   $context Error message context.
	 */
	private static function assert_optional_strings( array $value, array $properties, string $context ): void {
		foreach ( $properties as $property ) {
			if ( array_key_exists( $property, $value ) && ! is_string( $value[ $property ] ) ) {
				throw self::invalid_schema( sprintf( '%s %s must be a string.', $context, $property ) );
			}
		}
	}

	/**
	 * Assert a non-empty string.
	 *
	 * @param mixed  $value Candidate value.
	 * @param string $message Exception message.
	 */
	private static function assert_non_empty_string( $value, string $message ): void {
		if ( ! is_string( $value ) || '' === $value ) {
			throw self::invalid_schema( $message );
		}
	}

	/**
	 * Build a safely escaped schema validation exception.
	 *
	 * @param string $message Developer-facing validation reason.
	 * @return \InvalidArgumentException
	 */
	private static function invalid_schema( string $message ): \InvalidArgumentException {
		return new \InvalidArgumentException( sanitize_text_field( $message ) );
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.Missing
	// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

	/**
	 * Get the default group.
	 *
	 * @param string $group_id Group id.
	 * @param int    $order Group order.
	 * @return array
	 */
	private static function get_default_group( string $group_id, int $order ): array {
		return array(
			'id'          => $group_id,
			'title'       => '',
			'description' => '',
			'order'       => $order,
			'fields'      => array(),
		);
	}

	/**
	 * Get explicit legacy group ids that generated ids must not claim.
	 *
	 * @param array $settings Legacy settings definitions.
	 * @return array<string, true>
	 */
	private static function get_declared_group_ids( array $settings ): array {
		$group_ids = array();

		foreach ( $settings as $setting ) {
			if (
				is_array( $setting )
				&& 'title' === ( $setting['type'] ?? null )
				&& isset( $setting['id'] )
				&& is_scalar( $setting['id'] )
				&& '' !== (string) $setting['id']
			) {
				$group_ids[ (string) $setting['id'] ] = true;
			}
		}

		return $group_ids;
	}

	/**
	 * Get an unused id for a generated legacy group.
	 *
	 * @param string               $base_id Base group id.
	 * @param array<string, array> $groups Existing groups.
	 * @param array<string, true>  $declared_group_ids Explicit group ids.
	 * @return string
	 */
	private static function get_unique_group_id( string $base_id, array $groups, array $declared_group_ids ): string {
		$group_id = $base_id;
		$suffix   = 1;

		while ( array_key_exists( $group_id, $groups ) || isset( $declared_group_ids[ $group_id ] ) ) {
			$group_id = $base_id . '_' . $suffix;
			++$suffix;
		}

		return $group_id;
	}

	/**
	 * Add a legacy group without allowing a later group to overwrite it.
	 *
	 * @param array  $groups Groups keyed by id.
	 * @param string $group_id Group id.
	 * @param array  $group Group definition.
	 * @throws \InvalidArgumentException When the group id is duplicated.
	 */
	private static function add_group( array &$groups, string $group_id, array $group ): void {
		// Exception messages are sanitized by invalid_schema() before they cross the schema boundary.
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		if ( array_key_exists( $group_id, $groups ) ) {
			throw self::invalid_schema( sprintf( 'Group id "%s" is duplicated.', $group_id ) );
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		$groups[ $group_id ] = $group;
	}
}
