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
	 * Field types that require a choice list.
	 *
	 * @var string[]
	 */
	private const CHOICE_FIELD_TYPES = array( 'array', 'radio', 'select' );

	/**
	 * Custom attributes that describe a numeric range.
	 *
	 * @var string[]
	 */
	private const RANGE_ATTRIBUTES = array( 'min', 'max', 'step' );

	/**
	 * Largest integer JavaScript can represent exactly.
	 *
	 * @var string
	 */
	private const JAVASCRIPT_SAFE_INTEGER = '9007199254740991';

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

		return self::canonicalize_schema_values( $schema, true );
	}

	// Exception messages are not HTML output. Dynamic values are sanitized once
	// by invalid_schema() before the exception crosses the schema boundary.
	// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

	/**
	 * Assert that a schema is safe for the current Settings UI renderer.
	 *
	 * Compatibility normalization and request-owned shell defaults must run
	 * before this assertion. An invalid schema throws before it can be cached or
	 * emitted to JavaScript.
	 *
	 * @since 11.1.0
	 *
	 * @param array $schema Settings UI schema.
	 * @throws \InvalidArgumentException When the schema is malformed or unsupported.
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

		$group_ids = array();
		foreach ( $schema['groups'] as $group_key => $group ) {
			if ( ! is_string( $group_key ) || '' === $group_key ) {
				throw self::invalid_schema( 'Group map keys must be non-empty strings.' );
			}

			if ( ! is_array( $group ) ) {
				throw self::invalid_schema( sprintf( 'Group "%s" must be an array.', $group_key ) );
			}

			self::assert_non_empty_string( $group['id'] ?? null, sprintf( 'Group "%s" id must be a non-empty string.', $group_key ) );
			if ( $group_key !== $group['id'] ) {
				throw self::invalid_schema( sprintf( 'Group map key "%s" must match group id "%s".', $group_key, $group['id'] ) );
			}

			$group_ids[] = $group['id'];
		}

		$field_ids        = array();
		$visibility_rules = array();
		foreach ( $schema['groups'] as $group ) {
			$group_id = $group['id'];
			self::assert_optional_strings( $group, array( 'title', 'description' ), sprintf( 'Group "%s"', $group_id ) );
			self::assert_group_actions( $group['actions'] ?? null, $group_id );

			if ( ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) || ! ArrayUtil::array_is_list( $group['fields'] ) ) {
				throw self::invalid_schema( sprintf( 'Group "%s" fields must be a list.', $group_id ) );
			}

			foreach ( $group['fields'] as $field_index => $field ) {
				if ( ! is_array( $field ) ) {
					throw self::invalid_schema( sprintf( 'Group "%s" field %d must be an array.', $group_id, $field_index ) );
				}

				self::assert_non_empty_string( $field['id'] ?? null, sprintf( 'Group "%s" field %d id must be a non-empty string.', $group_id, $field_index ) );
				$field_id = $field['id'];
				if ( in_array( $field_id, $field_ids, true ) ) {
					throw self::invalid_schema( sprintf( 'Field id "%s" is duplicated.', $field_id ) );
				}
				if ( in_array( $field_id, $group_ids, true ) ) {
					throw self::invalid_schema( sprintf( 'Field id "%s" collides with a group id.', $field_id ) );
				}

				$field_ids[] = $field_id;
				self::assert_field( $field );
				if ( isset( $field['visibility'] ) ) {
					$visibility_rules[ $field_id ] = $field['visibility'];
				}
			}
		}

		foreach ( $visibility_rules as $field_id => $visibility ) {
			$controller = $visibility['controller'];
			if ( ! in_array( $controller, $field_ids, true ) ) {
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
	 * @since 11.1.0
	 *
	 * @param array $schema Settings UI schema.
	 * @param bool  $legacy_derived Whether the schema came from legacy settings definitions.
	 * @return array Canonicalized schema.
	 * @throws \InvalidArgumentException When a value cannot be converted without loss.
	 */
	public static function canonicalize_schema_values( array $schema, bool $legacy_derived = false ): array {
		if ( ! isset( $schema['groups'] ) || ! is_array( $schema['groups'] ) ) {
			return $schema;
		}

		$converted_fields = array();
		$original_values  = array();

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

				self::canonicalize_field( $field, $converted_fields );
			}
			unset( $field );
		}
		unset( $group );

		self::preserve_converted_form_values( $schema, $original_values );

		if ( ! $legacy_derived ) {
			self::emit_conversion_notice(
				__METHOD__,
				$converted_fields,
				/* translators: %s: comma-separated field ids. */
				__( 'A Settings UI schema provider supplied legacy field values or metadata that WooCommerce converted for compatibility: %s. Update the provider to supply canonical values.', 'woocommerce' ),
				'11.1.0'
			);
		}

		return $schema;
	}

	/**
	 * Canonicalize one field in place.
	 *
	 * @param array    $field Field definition.
	 * @param string[] $converted_fields Affected field ids.
	 */
	private static function canonicalize_field( array &$field, array &$converted_fields ): void {
		$type = $field['type'] ?? null;
		if ( ! is_string( $type ) ) {
			return;
		}

		$original_type  = $type;
		$original_value = $field['value'] ?? null;

		$numeric_validation_converted = false;

		if ( 'number' === $type && self::should_promote_to_integer( $field ) ) {
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
			}
		}

		if ( in_array( $type, array( 'number', 'integer' ), true ) ) {
			$numeric_validation_converted = self::canonicalize_numeric_validation( $field, 'integer' === $type );
		}

		if (
			$original_type !== $field['type'] ||
			( array_key_exists( 'value', $field ) && $original_value !== $field['value'] ) ||
			$numeric_validation_converted
		) {
			$converted_fields[] = $field['id'];
		}
	}

	/**
	 * Whether a legacy number follows the HTML integer step contract.
	 *
	 * @param array $field Field definition.
	 * @return bool
	 */
	private static function should_promote_to_integer( array $field ): bool {
		$attributes = isset( $field['customAttributes'] ) && is_array( $field['customAttributes'] ) ? $field['customAttributes'] : array();
		if ( ! array_key_exists( 'step', $attributes ) || '1' !== self::get_integral_decimal( $attributes['step'] ) ) {
			return false;
		}

		$base = $attributes['min'] ?? ( $field['value'] ?? '' );
		if ( is_string( $base ) && '' === trim( $base ) ) {
			$base = 0;
		}

		return null !== self::get_integral_decimal( $base );
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

		$number = (float) trim( $value );
		if ( ! is_finite( $number ) || ( 0.0 === $number && ! self::decimal_string_is_zero( trim( $value ) ) ) ) {
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
		if ( ! preg_match( '/^([+-]?)(?:(\d+)(?:\.(\d*))?|\.(\d+))(?:[eE]([+-]?\d+))?$/', $value, $matches ) ) {
			return null;
		}

		$whole    = $matches[2] ?? '';
		$fraction = '' !== ( $matches[3] ?? '' ) ? $matches[3] : ( $matches[4] ?? '' );
		$digits   = ltrim( $whole . $fraction, '0' );
		$digits   = '' === $digits ? '0' : $digits;
		$exponent = $matches[5] ?? '0';
		$negative = '-' === ( $matches[1] ?? '' );
		$scale    = strlen( $fraction );

		if ( strlen( ltrim( $exponent, '+-' ) ) > 6 ) {
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
		return 1 === preg_match( '/^[+-]?(?:(?:\d+(?:\.\d*)?)|(?:\.\d+))(?:[eE][+-]?\d+)?$/', $value );
	}

	/**
	 * Whether a decimal numeric string represents zero.
	 *
	 * @param string $value Candidate number.
	 * @return bool
	 */
	private static function decimal_string_is_zero( string $value ): bool {
		$mantissa = preg_replace( '/[eE].*$/', '', $value );
		return '' === trim( (string) $mantissa, '+-0.' );
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
			$has_attribute  = array_key_exists( $bound, $attributes );
			$has_validation = array_key_exists( $bound, $validation );
			if ( ! $has_attribute && ! $has_validation ) {
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
				( $has_attribute && ! $has_validation ) ||
				( $has_attribute && $original_attribute !== $attribute_value ) ||
				( $has_validation && $original_validation !== $validation_value );
		}

		if ( ! empty( $validation ) ) {
			$field['validation'] = $validation;
		}
		if ( ! empty( $attributes ) ) {
			$field['customAttributes'] = $attributes;
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
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/', $value ) ) {
			$format   = 16 === strlen( $value ) ? '!Y-m-d\TH:i' : '!Y-m-d\TH:i:s';
			$datetime = \DateTimeImmutable::createFromFormat( $format, $value, wp_timezone() );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?(?:Z|[+-]\d{2}:\d{2})$/', $value ) ) {
			$format   = preg_match( '/T\d{2}:\d{2}:/', $value ) ? '!Y-m-d\TH:i:sP' : '!Y-m-d\TH:iP';
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
	 * @param array $schema Canonical schema.
	 * @param array $original_values Original values keyed by field id.
	 * @throws \InvalidArgumentException When a converted value has no safe form representation.
	 */
	private static function preserve_converted_form_values( array &$schema, array $original_values ): void {
		$page_save_adapter = isset( $schema['save'] ) && is_array( $schema['save'] ) ? ( $schema['save']['adapter'] ?? 'form_post' ) : 'form_post';
		if ( 'form_post' !== $page_save_adapter ) {
			return;
		}

		foreach ( $schema['groups'] as &$group ) {
			if ( ! is_array( $group ) || ! isset( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as &$field ) {
				if ( ! is_array( $field ) || ! isset( $field['id'] ) || ! array_key_exists( $field['id'], $original_values ) ) {
					continue;
				}

				$original = $original_values[ $field['id'] ];
				if ( ( $field['value'] ?? null ) === $original || 'form_post' !== self::get_field_save_adapter( $field ) ) {
					continue;
				}

				if ( isset( $field['save'] ) && is_array( $field['save'] ) && array_key_exists( 'initialValue', $field['save'] ) ) {
					continue;
				}

				if ( ! self::is_form_value( $original ) ) {
					throw self::invalid_schema( sprintf( 'Field "%s" must define save.initialValue before its native form value can be converted.', $field['id'] ) );
				}

				if ( ! isset( $field['save'] ) || ! is_array( $field['save'] ) ) {
					$field['save'] = array( 'adapter' => 'form_post' );
				}
				$field['save']['initialValue'] = $original;
			}
			unset( $field );
		}
		unset( $group );
	}

	/**
	 * Get a field's effective save adapter.
	 *
	 * @param array $field Field definition.
	 * @return string
	 */
	private static function get_field_save_adapter( array $field ): string {
		return isset( $field['save'] ) && is_array( $field['save'] ) ? ( $field['save']['adapter'] ?? 'form_post' ) : 'form_post';
	}

	/**
	 * Whether a value is a valid HTML form representation.
	 *
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private static function is_form_value( $value ): bool {
		return is_string( $value ) || ( is_array( $value ) && ArrayUtil::array_is_list( $value ) && count( $value ) === count( array_filter( $value, 'is_string' ) ) );
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
			$field['save']['initialValue'] = $raw_value;
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
	 * Option-backed values are read only for legacy form-post fields. The field
	 * name is the persistence source of truth and supports the same flat or
	 * one-level nested shape as the classic form.
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

		if ( false === strpos( $field_name, '[' ) && false === strpos( $field_name, ']' ) ) {
			return get_option( $field_name, $default );
		}

		if ( ! preg_match( '/^([^\[\]]+)\[([^\[\]]+)\]$/', $field_name, $matches ) ) {
			throw self::invalid_schema( sprintf( 'Legacy form-post field "%s" may use only one bracketed setting name.', $field_name ) );
		}

		$option = get_option( $matches[1], array() );
		if ( ! is_array( $option ) ) {
			throw self::invalid_schema( sprintf( 'Legacy form-post field "%s" option value must be an array.', $field_name ) );
		}

		return array_key_exists( $matches[2], $option ) ? $option[ $matches[2] ] : $default;
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
			if ( in_array( $item['id'], $ids, true ) ) {
				throw self::invalid_schema( sprintf( '%s item id "%s" is duplicated.', $context, $item['id'] ) );
			}
			$ids[] = $item['id'];

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

			if ( isset( $badge['intent'] ) && ! in_array( $badge['intent'], array( 'default', 'info', 'success', 'warning', 'error' ), true ) ) {
				throw self::invalid_schema( sprintf( 'Shell badge %d intent "%s" is not supported.', $index, is_scalar( $badge['intent'] ) ? (string) $badge['intent'] : gettype( $badge['intent'] ) ) );
			}
		}
	}

	/**
	 * Assert group header actions.
	 *
	 * @param mixed  $actions Group actions, or null when omitted.
	 * @param string $group_id Group id.
	 */
	private static function assert_group_actions( $actions, string $group_id ): void {
		if ( null === $actions ) {
			return;
		}

		if ( ! is_array( $actions ) || ! ArrayUtil::array_is_list( $actions ) ) {
			throw self::invalid_schema( sprintf( 'Group "%s" actions must be a list.', $group_id ) );
		}

		$ids = array();
		foreach ( $actions as $index => $action ) {
			if ( ! is_array( $action ) ) {
				throw self::invalid_schema( sprintf( 'Group "%s" action %d must be an array.', $group_id, $index ) );
			}

			self::assert_non_empty_string( $action['id'] ?? null, sprintf( 'Group "%s" action %d id must be a non-empty string.', $group_id, $index ) );
			if ( in_array( $action['id'], $ids, true ) ) {
				throw self::invalid_schema( sprintf( 'Group "%s" action id "%s" is duplicated.', $group_id, $action['id'] ) );
			}
			$ids[] = $action['id'];

			foreach ( array( 'label', 'href' ) as $property ) {
				if ( ! isset( $action[ $property ] ) || ! is_string( $action[ $property ] ) ) {
					throw self::invalid_schema( sprintf( 'Group "%s" action %d %s must be a string.', $group_id, $index, $property ) );
				}
			}

			self::assert_optional_strings( $action, array( 'variant', 'target', 'rel' ), sprintf( 'Group "%s" action %d', $group_id, $index ) );
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
		if ( ! is_string( $type ) || ! in_array( $type, self::SUPPORTED_FIELD_TYPES, true ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" has unsupported type "%s".', $field_id, is_scalar( $type ) ? (string) $type : gettype( $type ) ) );
		}

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
				$valid = is_string( $value );
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
		$field_id = $field['id'];
		$options  = $field['options'] ?? null;
		if ( in_array( $field['type'], self::CHOICE_FIELD_TYPES, true ) && ( ! is_array( $options ) || empty( $options ) || ! ArrayUtil::array_is_list( $options ) ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" of type "%s" must define a non-empty options list.', $field_id, $field['type'] ) );
		}

		if ( null === $options ) {
			return;
		}

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

			if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) && ! is_bool( $value ) ) {
				throw self::invalid_schema( sprintf( 'Field "%s" custom attribute "%s" has an invalid value.', $field['id'], $attribute ) );
			}

			if ( in_array( $attribute, self::RANGE_ATTRIBUTES, true ) ) {
				if ( ! in_array( $field['type'], array( 'number', 'integer' ), true ) ) {
					throw self::invalid_schema( sprintf( 'Field "%s" may define "%s" only when its type is "number" or "integer".', $field['id'], $attribute ) );
				}

				$allow_any = 'step' === $attribute;
				$valid     = $allow_any
					? self::is_finite_number( $value, false ) || 'any' === $value
					: self::is_canonical_number( $value );
				if ( ! $valid ) {
					throw self::invalid_schema( sprintf( 'Field "%s" custom attribute "%s" must be a finite number.', $field['id'], $attribute ) );
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
				throw self::invalid_schema( sprintf( 'Field "%1$s" validation rule "%2$s" must be a finite numeric bound.', $field['id'], is_scalar( $rule ) ? (string) $rule : gettype( $rule ) ) );
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

		if ( is_array( $save ) && array_key_exists( 'initialValue', $save ) && ! self::is_form_value( $save['initialValue'] ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" save.initialValue must be a string or string list.', $field['id'] ) );
		}

		if ( 'info' === $field['type'] && 'none' !== $adapter ) {
			throw self::invalid_schema( sprintf( 'Field "%s" of type "info" must use the "none" save adapter.', $field['id'] ) );
		}
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
	 * Whether a value is a finite number accepted by the transitional renderer.
	 *
	 * @param mixed $value Candidate value.
	 * @param bool  $allow_empty_string Whether an empty input representation is valid.
	 * @return bool
	 */
	private static function is_finite_number( $value, bool $allow_empty_string ): bool {
		if ( is_int( $value ) ) {
			return true;
		}

		if ( is_float( $value ) ) {
			return is_finite( $value );
		}

		if ( ! is_string( $value ) ) {
			return false;
		}

		if ( $allow_empty_string && '' === $value ) {
			return true;
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
			return ! self::unsigned_decimal_is_greater( ltrim( (string) $value, '-' ), self::JAVASCRIPT_SAFE_INTEGER );
		}

		if ( ! is_float( $value ) || ! is_finite( $value ) ) {
			return false;
		}

		return floor( $value ) !== $value || ! self::unsigned_decimal_is_greater( ltrim( sprintf( '%.0f', $value ), '-' ), self::JAVASCRIPT_SAFE_INTEGER );
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
