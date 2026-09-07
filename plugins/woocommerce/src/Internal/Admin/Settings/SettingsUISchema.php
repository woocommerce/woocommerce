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
		$value   = \WC_Admin_Settings::get_option( self::get_option_name( $setting ), $default );

		return self::normalize_value( $value, $type );
	}

	/**
	 * Resolve the option name used to read and save a field.
	 *
	 * The 'field_name' key can specify an input field name that differs from the field 'id' (for example a nested
	 * `option_name[key]` path). It is authoritative for both reading and saving the value when it is that shape, so
	 * the two paths stay in sync, and the resolution is shared with the classic screen so those cannot diverge
	 * either. Falls back to the field 'id' otherwise.
	 *
	 * @param array $setting Legacy field definition.
	 * @return string
	 */
	private static function get_option_name( array $setting ): string {
		return \WC_Admin_Settings::get_read_name( $setting['field_name'] ?? null, (string) $setting['id'] );
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

		return array(
			'adapter' => $default_save_adapter,
			'name'    => self::get_option_name( $setting ),
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

		if ( ! self::is_settings_value( $field['value'] ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" value is not a valid Settings UI value.', $field['id'] ) );
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

		$adapter = is_array( $save ) ? ( $save['adapter'] ?? null ) : 'form_post';
		if ( ! is_string( $adapter ) || ! in_array( $adapter, array( 'form_post', 'none' ), true ) ) {
			throw self::invalid_schema( sprintf( 'Field "%s" save adapter must be "form_post" or "none".', $field['id'] ) );
		}

		if ( is_array( $save ) && array_key_exists( 'name', $save ) ) {
			self::assert_non_empty_string( $save['name'], sprintf( 'Field "%s" save name must be a non-empty string.', $field['id'] ) );
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

		return is_array( $value ) && ArrayUtil::array_is_list( $value ) && count( $value ) === count( array_filter( $value, 'is_string' ) );
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
