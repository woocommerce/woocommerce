<?php
/**
 * React settings schema helpers.
 */
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Transforms legacy settings definitions into React settings responses.
 *
 * @since 10.6.0
 */
class ReactSettingsSchema {
	/**
	 * Settings definition marker types that don't render fields.
	 *
	 * @since 10.6.0
	 * @var string[]
	 */
	private const MARKER_TYPES = array( 'title', 'sectionend' );

	/**
	 * Default group ID used when settings are not grouped.
	 *
	 * @since 10.6.0
	 * @var string
	 */
	private const DEFAULT_GROUP_ID = 'default';

	/**
	 * Default order for ungrouped settings.
	 *
	 * @since 10.6.0
	 * @var int
	 */
	private const DEFAULT_GROUP_ORDER = 999;

	/**
	 * Normalized field types that accept options.
	 *
	 * @since 10.6.0
	 * @var string[]
	 */
	private const OPTION_TYPES = array( 'select', 'multiselect' );
	/**
	 * Get the payload path for a settings tab/section.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @return array
	 * @since 10.6.0
	 */
	public static function get_payload_path( string $tab, string $section ): array {
		$section_key = '' === $section ? 'default' : $section;
		return array( 'settings', $tab, $section_key );
	}

	/**
	 * Build a consistent mount ID for a settings tab/section.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @return string
	 * @since 10.6.0
	 */
	public static function get_mount_id( string $tab, string $section ): string {
		$section_key = '' === $section ? 'default' : $section;
		return 'wc_settings_react_' . $tab . '_' . $section_key;
	}

	/**
	 * Check if a settings page is opted out of React rendering.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @return bool
	 * @since 10.6.0
	 */
	public static function is_opted_out( string $tab, string $section, array $settings_definitions, $settings_page ): bool {
		/**
		 * Filter whether the settings page should opt out of React rendering.
		 *
		 * @param bool   $opt_out Whether to opt out of React rendering.
		 * @param string $tab Tab id.
		 * @param string $section Section id.
		 * @param array  $settings_definitions Settings definitions for the tab/section.
		 * @param mixed  $settings_page Settings page instance.
		 */
		return (bool) apply_filters(
			'woocommerce_react_settings_opt_out',
			false,
			$tab,
			$section,
			$settings_definitions,
			$settings_page
		);
	}

	/**
	 * Get normalized supported field types for React settings.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @return array
	 * @since 10.6.0
	 */
	public static function get_supported_types( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		$default_types = array( 'text', 'number', 'select', 'multiselect', 'checkbox', 'radio', 'toggle' );
		/**
		 * Filter supported React settings field types.
		 *
		 * @param array  $supported_types Supported normalized field types.
		 * @param string $tab Tab id.
		 * @param string $section Section id.
		 * @param array  $settings_definitions Settings definitions for the tab/section.
		 * @param mixed  $settings_page Settings page instance.
		 */
		$supported_types = apply_filters(
			'woocommerce_react_settings_supported_types',
			$default_types,
			$tab,
			$section,
			$settings_definitions,
			$settings_page
		);

		return array_values( array_unique( array_filter( (array) $supported_types ) ) );
	}

	/**
	 * Get a type map for normalizing WooCommerce settings types.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @return array
	 * @since 10.6.0
	 */
	public static function get_type_map( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		$default_map = array(
			'single_select_country'          => 'select',
			'multi_select_countries'         => 'multiselect',
			'single_select_page'             => 'select',
			'single_select_page_with_search' => 'select',
			'textarea'                       => 'text',
		);
		/**
		 * Filter the field type map for React settings.
		 *
		 * @param array  $type_map Map of WooCommerce field types to normalized types.
		 * @param string $tab Tab id.
		 * @param string $section Section id.
		 * @param array  $settings_definitions Settings definitions for the tab/section.
		 * @param mixed  $settings_page Settings page instance.
		 */
		$type_map = apply_filters(
			'woocommerce_react_settings_type_map',
			$default_map,
			$tab,
			$section,
			$settings_definitions,
			$settings_page
		);

		return is_array( $type_map ) ? $type_map : $default_map;
	}

	/**
	 * Get unsupported fields for a settings page.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @return array
	 * @since 10.6.0
	 */
	public static function get_unsupported_fields( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		$type_map        = self::get_type_map( $tab, $section, $settings_definitions, $settings_page );
		$supported_types = self::get_supported_types( $tab, $section, $settings_definitions, $settings_page );
		$unsupported     = array();

		foreach ( $settings_definitions as $setting ) {
			$type = $setting['type'] ?? '';
			if ( '' === $type ) {
				$unsupported[] = self::get_unsupported_field_payload( $setting, $type, $type );
				continue;
			}

			if ( in_array( $type, self::MARKER_TYPES, true ) ) {
				continue;
			}

			$normalized_type = $type_map[ $type ] ?? $type;
			if ( ! in_array( $normalized_type, $supported_types, true ) ) {
				$unsupported[] = self::get_unsupported_field_payload( $setting, $type, $normalized_type );
			}
		}

		return $unsupported;
	}

	/**
	 * Build a React settings response from legacy settings definitions.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @return array{
	 *     id: string,
	 *     title: string,
	 *     description: string,
	 *     values: array<string, mixed>,
	 *     groups: array<string, array{title: string, description: string, order: int, fields: array<int, array<string, mixed>>}>
	 * }
	 * @since 10.6.0
	 */
	public static function build_response( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		$groups        = array();
		$values        = array();
		$current_group = null;
		$current_id    = null;
		$group_index   = 0;

		foreach ( $settings_definitions as $setting ) {
			$setting_type = $setting['type'] ?? '';

			if ( 'title' === $setting_type ) {
				$current_id    = $setting['id'] ?? 'group_' . $group_index;
				$current_group = array(
					'title'       => $setting['title'] ?? '',
					'description' => $setting['desc'] ?? '',
					'order'       => isset( $setting['order'] ) ? (int) $setting['order'] : $group_index,
					'fields'      => array(),
				);
				$group_index++;
				continue;
			}

			if ( 'sectionend' === $setting_type ) {
				if ( $current_group && $current_id ) {
					$groups[ $current_id ] = $current_group;
				}
				$current_group = null;
				$current_id    = null;
				continue;
			}

			if ( in_array( $setting_type, self::MARKER_TYPES, true ) ) {
				continue;
			}

			if ( empty( $setting['id'] ) ) {
				continue;
			}

			if ( ! $current_group ) {
				$current_id    = self::DEFAULT_GROUP_ID;
				$current_group = self::get_default_group();
			}

			$field = self::transform_setting_to_field( $tab, $section, $setting, $settings_page );
			if ( $field ) {
				$current_group['fields'][] = $field;
				$values[ $field['id'] ]     = self::get_field_value( $setting, $field['type'] );
			}
		}

		if ( $current_group && $current_id ) {
			$groups[ $current_id ] = $current_group;
		}

		uasort(
			$groups,
			function ( $a, $b ) {
				$a_order = $a['order'] ?? 999;
				$b_order = $b['order'] ?? 999;
				return $a_order - $b_order;
			}
		);

		$title = is_object( $settings_page ) && method_exists( $settings_page, 'get_label' )
			? $settings_page->get_label()
			: ucfirst( $tab );

		return array(
			'id'          => $tab,
			'title'       => $title,
			'description' => '',
			'values'      => $values,
			'groups'      => $groups,
		);
	}

	/**
	 * Transform a WooCommerce setting into a React field.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $setting WooCommerce setting array.
	 * @param mixed  $settings_page Settings page instance.
	 * @return array|null
	 * @since 10.6.0
	 */
	private static function transform_setting_to_field( string $tab, string $section, array $setting, $settings_page ): ?array {
		$setting_id   = $setting['id'] ?? '';
		$setting_type = $setting['type'] ?? 'text';
		$type_map     = self::get_type_map( $tab, $section, array( $setting ), $settings_page );
		$field_type   = $type_map[ $setting_type ] ?? $setting_type;

		$field = array(
			'id'    => $setting_id,
			'label' => $setting['title'] ?? $setting['name'] ?? $setting_id,
			'type'  => $field_type,
			'desc'  => $setting['desc'] ?? '',
		);

		$options = self::get_field_options( $setting, $field_type );
		if ( ! empty( $options ) ) {
			$field['options'] = $options;
		}

		return $field;
	}

	/**
	 * Get field options for supported select/multiselect fields.
	 *
	 * @param array  $setting Setting definition.
	 * @param string $normalized_type Normalized field type.
	 * @return array
	 * @since 10.6.0
	 */
	private static function get_field_options( array $setting, string $normalized_type ): array {
		if ( ! in_array( $normalized_type, self::OPTION_TYPES, true ) ) {
			return array();
		}

		$options = isset( $setting['options'] ) && is_array( $setting['options'] )
			? $setting['options']
			: array();

		if ( empty( $options ) && 'multi_select_countries' === ( $setting['type'] ?? '' ) ) {
			if ( function_exists( 'WC' ) ) {
				$options = WC()->countries->get_countries();
			}
		}

		return self::normalize_options( $options );
	}

	/**
	 * Get a normalized field value for React settings.
	 *
	 * @param array  $setting Setting definition.
	 * @param string $type Normalized field type.
	 * @return mixed
	 * @since 10.6.0
	 */
	private static function get_field_value( array $setting, string $type ) {
		if ( array_key_exists( 'fixed_value', $setting ) && null !== $setting['fixed_value'] ) {
			return $setting['fixed_value'];
		}

		if ( array_key_exists( 'value', $setting ) ) {
			return self::normalize_value( $setting['value'], $type );
		}

		$default = $setting['default'] ?? '';
		if ( empty( $setting['id'] ) ) {
			return self::normalize_value( $default, $type );
		}
		$value   = \WC_Admin_Settings::get_option( $setting['id'], $default );
		return self::normalize_value( $value, $type );
	}

	/**
	 * Normalize field values to match React field expectations.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type Normalized field type.
	 * @return mixed
	 * @since 10.6.0
	 */
	private static function normalize_value( $value, string $type ) {
		switch ( $type ) {
			case 'number':
				return is_numeric( $value ) ? (float) $value : 0;
			case 'checkbox':
			case 'toggle':
				if ( function_exists( 'wc_string_to_bool' ) ) {
					return wc_string_to_bool( $value );
				}
				return is_bool( $value ) ? $value : (bool) $value;
			case 'multiselect':
				return is_array( $value ) ? array_values( $value ) : array();
			default:
				return is_string( $value ) ? $value : (string) $value;
		}
	}

	/**
	 * Build a log payload for unsupported fields.
	 *
	 * @param array  $setting Setting definition.
	 * @param string $type Original type.
	 * @param string $normalized_type Normalized type.
	 * @return array
	 * @since 10.6.0
	 */
	private static function get_unsupported_field_payload( array $setting, string $type, string $normalized_type ): array {
		return array(
			'id'              => $setting['id'] ?? '',
			'type'            => $type,
			'normalized_type' => $normalized_type,
		);
	}

	/**
	 * Get default group metadata for ungrouped fields.
	 *
	 * @return array
	 * @since 10.6.0
	 */
	private static function get_default_group(): array {
		return array(
			'title'       => '',
			'description' => '',
			'order'       => self::DEFAULT_GROUP_ORDER,
			'fields'      => array(),
		);
	}

	/**
	 * Normalize field options to supported formats.
	 *
	 * @param array $options Raw options array.
	 * @return array
	 * @since 10.6.0
	 */
	private static function normalize_options( array $options ): array {
		if ( self::is_list_of_option_arrays( $options ) ) {
			$normalized = array();
			foreach ( $options as $option ) {
				$label = $option['label'] ?? null;
				$value = $option['value'] ?? null;
				if ( ! is_scalar( $label ) || ! is_scalar( $value ) ) {
					continue;
				}

				$entry = array(
					'label' => (string) $label,
					'value' => (string) $value,
				);

				if ( isset( $option['desc'] ) && is_scalar( $option['desc'] ) ) {
					$entry['desc'] = (string) $option['desc'];
				}

				$normalized[] = $entry;
			}

			return $normalized;
		}

		$normalized = array();
		foreach ( $options as $key => $label ) {
			if ( ! is_scalar( $label ) && null !== $label ) {
				continue;
			}

			$normalized[ (string) $key ] = is_scalar( $label ) ? (string) $label : '';
		}

		return $normalized;
	}

	/**
	 * Determine whether the options array is a list of option arrays.
	 *
	 * @param array $options Raw options array.
	 * @return bool
	 * @since 10.6.0
	 */
	private static function is_list_of_option_arrays( array $options ): bool {
		if ( empty( $options ) ) {
			return false;
		}

		$is_list = function_exists( 'array_is_list' )
			? array_is_list( $options )
			: self::is_sequential_keys( $options );

		if ( ! $is_list ) {
			return false;
		}

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if the array keys are sequential integers.
	 *
	 * @param array $options Raw options array.
	 * @return bool
	 * @since 10.6.0
	 */
	private static function is_sequential_keys( array $options ): bool {
		$expected = 0;
		foreach ( $options as $key => $value ) {
			if ( $key !== $expected ) {
				return false;
			}
			$expected++;
		}

		return true;
	}
}
