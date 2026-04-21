<?php
/**
 * React settings schema helpers.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Features\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Transforms legacy WC_Settings_Page definitions into React-shaped settings responses.
 *
 * This class is the public transform contract used both for preloading data into
 * `window.wcSettings.admin.settings.{tab}.{section}` on settings pages that have
 * opted in to modernised rendering, and for the v4 REST API endpoints that
 * surface the same shape. Third-party callers MAY call `build_response()`
 * directly with a `$settings_definitions` array and a `$settings_page` instance.
 *
 * @since 10.8.0
 */
class ReactSettingsSchema {
	/**
	 * Settings definition marker types that don't render fields.
	 *
	 * @since 10.8.0
	 * @var string[]
	 */
	private const MARKER_TYPES = array( 'title', 'sectionend' );

	/**
	 * Default group ID used when settings are not grouped.
	 *
	 * @since 10.8.0
	 * @var string
	 */
	private const DEFAULT_GROUP_ID = 'default';

	/**
	 * Default order for ungrouped settings.
	 *
	 * @since 10.8.0
	 * @var int
	 */
	private const DEFAULT_GROUP_ORDER = 999;

	/**
	 * Normalized field types that accept options.
	 *
	 * @since 10.8.0
	 * @var string[]
	 */
	private const OPTION_TYPES = array( 'select', 'multiselect', 'single_select_page_with_search' );

	/**
	 * Feature flag name that gates the modernised settings renderer.
	 *
	 * @since 10.8.0
	 * @var string
	 */
	private const FEATURE_FLAG = 'modern-settings';

	/**
	 * Whether the modernised settings renderer is currently enabled.
	 *
	 * @since 10.8.0
	 * @return bool
	 */
	public static function is_feature_enabled(): bool {
		return Features::is_enabled( self::FEATURE_FLAG );
	}

	/**
	 * Get the payload path for a settings tab/section.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @return array
	 * @since 10.8.0
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
	 * @since 10.8.0
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
	 * @since 10.8.0
	 */
	public static function is_opted_out( string $tab, string $section, array $settings_definitions, $settings_page ): bool {
		/**
		 * Filter whether the settings page should opt out of React rendering.
		 *
		 * @since 10.8.0
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
	 * @since 10.8.0
	 */
	public static function get_supported_types( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		$default_types = array(
			'text',
			'number',
			'select',
			'multiselect',
			'checkbox',
			'radio',
			'toggle',
			'password',
			'email',
			'url',
			'tel',
			'color',
			'date',
			'datetime',
			'datetime-local',
			'month',
			'week',
			'time',
			'textarea',
			'single_select_page_with_search',
			'info',
		);
		/**
		 * Filter supported React settings field types.
		 *
		 * @since 10.8.0
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
	 * @since 10.8.0
	 */
	public static function get_type_map( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		$default_map = array(
			'single_select_country'  => 'select',
			'multi_select_countries' => 'multiselect',
			'single_select_page'     => 'select',
		);
		/**
		 * Filter the field type map for React settings.
		 *
		 * @since 10.8.0
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
	 * @since 10.8.0
	 */
	public static function get_unsupported_fields( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		return self::find_unsupported_fields(
			$settings_definitions,
			self::get_type_map( $tab, $section, $settings_definitions, $settings_page ),
			self::get_supported_types( $tab, $section, $settings_definitions, $settings_page )
		);
	}

	/**
	 * Core unsupported-fields scan. Takes pre-computed type-map and supported-types
	 * arrays so callers that already have them (e.g. `get_screen_render_context()`)
	 * can skip re-firing the `woocommerce_react_settings_*` filters.
	 *
	 * @param array $settings_definitions Settings definitions.
	 * @param array $type_map             Pre-resolved type map.
	 * @param array $supported_types      Pre-resolved supported types.
	 * @return array
	 */
	private static function find_unsupported_fields( array $settings_definitions, array $type_map, array $supported_types ): array {
		$unsupported = array();

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
	 * Check if a settings definition contains any renderable fields.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @return bool
	 * @since 10.8.0
	 */
	public static function has_renderable_fields( string $tab, string $section, array $settings_definitions, $settings_page ): bool {
		return self::any_renderable_field(
			$settings_definitions,
			self::get_type_map( $tab, $section, $settings_definitions, $settings_page ),
			self::get_supported_types( $tab, $section, $settings_definitions, $settings_page )
		);
	}

	/**
	 * Core renderable-field scan. See `find_unsupported_fields()` for the
	 * rationale on the separate private variant.
	 *
	 * @param array $settings_definitions Settings definitions.
	 * @param array $type_map             Pre-resolved type map.
	 * @param array $supported_types      Pre-resolved supported types.
	 * @return bool
	 */
	private static function any_renderable_field( array $settings_definitions, array $type_map, array $supported_types ): bool {
		foreach ( $settings_definitions as $setting ) {
			$type = $setting['type'] ?? '';
			if ( '' === $type || in_array( $type, self::MARKER_TYPES, true ) ) {
				continue;
			}

			if ( empty( $setting['id'] ) ) {
				continue;
			}

			$normalized_type = $type_map[ $type ] ?? $type;
			if ( in_array( $normalized_type, $supported_types, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the render plan for a settings tab/section.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @return array{
	 *     is_opted_out: bool,
	 *     unsupported_fields: array<int, array{id: string, type: string, normalized_type: string}>,
	 *     should_render: bool,
	 *     mount_id: string,
	 *     payload_path: array<int, string>,
	 *     response: ?array{
	 *         id: string,
	 *         title: string,
	 *         description: string,
	 *         values: array<string, mixed>,
	 *         groups: array<string, array{title: string, description: string, order: int, fields: array<int, array<string, mixed>>}>
	 *     }
	 * }
	 * @since 10.8.0
	 */
	public static function get_screen_render_context( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		$is_opted_out       = self::is_opted_out( $tab, $section, $settings_definitions, $settings_page );
		$unsupported_fields = array();
		$should_render      = false;
		$response           = null;

		if ( ! $is_opted_out ) {
			// Resolve the filter-driven maps once per render; internal helpers
			// below reuse them so we don't re-fire `woocommerce_react_settings_*`
			// up to 2 + N times per request (where N is the field count).
			$type_map           = self::get_type_map( $tab, $section, $settings_definitions, $settings_page );
			$supported_types    = self::get_supported_types( $tab, $section, $settings_definitions, $settings_page );
			$unsupported_fields = self::find_unsupported_fields( $settings_definitions, $type_map, $supported_types );

			if ( empty( $unsupported_fields ) && self::any_renderable_field( $settings_definitions, $type_map, $supported_types ) ) {
				$should_render = true;
				$response      = self::build_response_with_type_map( $tab, $section, $settings_definitions, $settings_page, $type_map );
			}
		}

		return array(
			'is_opted_out'       => $is_opted_out,
			'unsupported_fields' => $unsupported_fields,
			'should_render'      => $should_render,
			'mount_id'           => self::get_mount_id( $tab, $section ),
			'payload_path'       => self::get_payload_path( $tab, $section ),
			'response'           => $response,
		);
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
	 * @since 10.8.0
	 */
	public static function build_response( string $tab, string $section, array $settings_definitions, $settings_page ): array {
		return self::build_response_with_type_map(
			$tab,
			$section,
			$settings_definitions,
			$settings_page,
			self::get_type_map( $tab, $section, $settings_definitions, $settings_page )
		);
	}

	/**
	 * Core response builder. Takes a pre-resolved `$type_map` so callers that
	 * already have one (e.g. `get_screen_render_context()`) can skip re-firing
	 * the `woocommerce_react_settings_type_map` filter — otherwise it would
	 * fire once per field via `transform_setting_to_field()`.
	 *
	 * @param string $tab Tab id.
	 * @param string $section Section id.
	 * @param array  $settings_definitions Settings definitions.
	 * @param mixed  $settings_page Settings page instance.
	 * @param array  $type_map Pre-resolved type map.
	 * @return array
	 */
	private static function build_response_with_type_map( string $tab, string $section, array $settings_definitions, $settings_page, array $type_map ): array {
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
				++$group_index;
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

			// Opting into the React renderer requires every field (including legacy `info` rows) to declare an
			// explicit `id`. Array-key-only entries from the legacy renderer are intentionally not coerced.
			if ( empty( $setting['id'] ) ) {
				continue;
			}

			if ( ! $current_group ) {
				$current_id    = self::DEFAULT_GROUP_ID;
				$current_group = self::get_default_group();
			}

			$field = self::transform_setting_to_field( $tab, $setting, $type_map );
			if ( $field ) {
				$current_group['fields'][] = $field;
				$values[ $field['id'] ]    = self::get_field_value( $setting, $field['type'] );
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
	 * @param array  $setting WooCommerce setting array.
	 * @param array  $type_map Pre-resolved type map.
	 * @return array|null
	 * @since 10.8.0
	 */
	private static function transform_setting_to_field( string $tab, array $setting, array $type_map ): ?array {
		$setting_id   = $setting['id'] ?? '';
		$setting_type = $setting['type'] ?? 'text';
		$field_type   = $type_map[ $setting_type ] ?? $setting_type;

		$desc = $setting['desc'] ?? '';

		// Legacy `info` rows use `setting['text']` as the primary body channel
		// (see WC_Admin_Settings::output_fields() `case 'info'`). When `desc`
		// is empty, fall back to `text` so the React `info` Edit has content
		// to render. Note: the legacy renderer runs `wpautop`/`wp_kses_post`
		// on this text; the React side renders plain text for now — a Phase 2
		// channel for HTML payloads is pending.
		if ( 'info' === $setting_type && '' === $desc ) {
			$desc = $setting['text'] ?? '';
		}

		$field = array(
			'id'    => $setting_id,
			'label' => $setting['title'] ?? $setting['name'] ?? $setting_id,
			'type'  => $field_type,
			'desc'  => $desc,
		);

		$options = self::get_field_options( $tab, $setting, $field_type );
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
	 * @since 10.8.0
	 */
	private static function get_field_options( string $tab, array $setting, string $normalized_type ): array {
		if ( ! in_array( $normalized_type, self::OPTION_TYPES, true ) ) {
			return array();
		}

		$options = isset( $setting['options'] ) && is_array( $setting['options'] )
			? $setting['options']
			: array();

		// Built-in fallback: legacy `single_select_page_with_search` consumers
		// (see class-wc-settings-advanced.php) define no `options` array — they
		// rely on a server-side AJAX search at the legacy renderer level. To
		// keep the React combobox honest, synthesise the page list from
		// `get_pages()` honouring any `args.exclude` value when no options
		// have been supplied. The filter below can still override or augment.
		$raw_type = isset( $setting['type'] ) && is_string( $setting['type'] ) ? $setting['type'] : '';
		if ( empty( $options ) && 'single_select_page_with_search' === $raw_type && function_exists( 'get_pages' ) ) {
			$query_args = array(
				'sort_column' => 'menu_order, post_title',
				'post_status' => 'publish',
			);

			$exclude = $setting['args']['exclude'] ?? null;
			if ( is_array( $exclude ) ) {
				$query_args['exclude'] = array_values( array_filter( array_map( 'absint', $exclude ) ) );
			}

			$pages = get_pages( $query_args );
			if ( is_array( $pages ) ) {
				foreach ( $pages as $page ) {
					$page_id = (int) $page->ID;
					if ( $page_id <= 0 ) {
						continue;
					}
					$options[ $page_id ] = html_entity_decode( (string) $page->post_title, ENT_QUOTES, get_bloginfo( 'charset' ) );
				}
			}
		}

		$setting_id = isset( $setting['id'] ) && is_scalar( $setting['id'] ) ? (string) $setting['id'] : '';
		if ( empty( $options ) && '' !== $setting_id ) {
			$options = self::get_default_field_options( $tab, $setting_id );
		}

		/**
		 * Filter field options for a specific field.
		 *
		 * Allows tab-specific callers to inject options at response time for
		 * fields that don't embed a static options array in their definition
		 * (for example, currency lists, country lists, or dynamic page lists).
		 *
		 * @since 10.8.0
		 *
		 * @param array  $options         Current options array (associative label map or list of option arrays).
		 * @param string $field_id        Setting field ID.
		 * @param array  $setting         Raw setting definition.
		 * @param string $normalized_type Normalized field type (e.g. 'select', 'multiselect').
		 */
		$options = apply_filters(
			'woocommerce_react_settings_field_options',
			$options,
			$setting_id,
			$setting,
			$normalized_type
		);

		return self::normalize_options( is_array( $options ) ? $options : array() );
	}

	/**
	 * Get built-in options for known WooCommerce settings fields.
	 *
	 * @param string $tab Settings tab id.
	 * @param string $field_id Setting field id.
	 * @return array
	 */
	private static function get_default_field_options( string $tab, string $field_id ): array {
		switch ( $tab ) {
			case 'general':
				return self::get_general_field_options( $field_id );
			case 'products':
				return self::get_product_field_options( $field_id );
			default:
				return array();
		}
	}

	/**
	 * Get built-in options for general settings fields.
	 *
	 * @param string $field_id Setting field id.
	 * @return array
	 */
	private static function get_general_field_options( string $field_id ): array {
		switch ( $field_id ) {
			case 'woocommerce_currency':
				if ( ! function_exists( 'get_woocommerce_currencies' ) || ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
					return array();
				}

				$currencies = get_woocommerce_currencies();
				$generated  = array();

				foreach ( $currencies as $code => $name ) {
					$label              = wp_specialchars_decode( (string) $name );
					$symbol             = wp_specialchars_decode( (string) get_woocommerce_currency_symbol( $code ) );
					$generated[ $code ] = $label . ' (' . $symbol . ') — ' . $code;
				}

				return $generated;

			case 'woocommerce_default_country':
				if ( ! function_exists( 'WC' ) ) {
					return array();
				}

				$countries = WC()->countries->get_countries();
				$states    = WC()->countries->get_states();
				$generated = array();

				foreach ( $countries as $country_code => $country_name ) {
					$country_states = $states[ $country_code ] ?? array();

					if ( empty( $country_states ) ) {
						$generated[ $country_code ] = $country_name;
						continue;
					}

					foreach ( $country_states as $state_code => $state_name ) {
						$generated[ $country_code . ':' . $state_code ] = $country_name . ' — ' . $state_name;
					}
				}

				return $generated;

			case 'woocommerce_all_except_countries':
			case 'woocommerce_specific_allowed_countries':
			case 'woocommerce_specific_ship_to_countries':
				if ( ! function_exists( 'WC' ) ) {
					return array();
				}

				return WC()->countries->get_countries();

			default:
				return array();
		}
	}

	/**
	 * Get built-in options for product settings fields.
	 *
	 * @param string $field_id Setting field id.
	 * @return array
	 */
	private static function get_product_field_options( string $field_id ): array {
		switch ( $field_id ) {
			case 'woocommerce_weight_unit':
				return self::get_unit_options( 'weight', 'woocommerce_weight_units' );

			case 'woocommerce_dimension_unit':
				return self::get_unit_options( 'dimensions', 'woocommerce_dimension_units' );

			case 'woocommerce_product_type':
				if ( ! function_exists( 'wc_get_product_types' ) ) {
					return array();
				}

				$product_types = wc_get_product_types();
				return is_array( $product_types ) ? $product_types : array();

			case 'woocommerce_shop_page_id':
				return self::get_page_options();

			default:
				return array();
		}
	}

	/**
	 * Get options for a weight/dimension unit field.
	 *
	 * Loads the canonical `code => label` map from `i18n/units.php` (the same
	 * source `I18nUtil::get_weight_unit_label()` / `get_dimensions_unit_label()`
	 * read from) and filters the key set through the given filter so we stay in
	 * sync with the v4 Products controller's `validate_setting_value()` check.
	 *
	 * @param string $bucket Either 'weight' or 'dimensions'.
	 * @param string $filter Filter name for the valid-keys list.
	 * @return array<string, string>
	 */
	private static function get_unit_options( string $bucket, string $filter ): array {
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}

		$units = include WC()->plugin_path() . '/i18n/units.php';
		if ( ! is_array( $units ) || empty( $units[ $bucket ] ) ) {
			return array();
		}

		$valid_keys = apply_filters( $filter, array_keys( $units[ $bucket ] ) );

		return is_array( $valid_keys )
			? array_intersect_key( $units[ $bucket ], array_flip( $valid_keys ) )
			: $units[ $bucket ];
	}

	/**
	 * Get options for page selection fields.
	 *
	 * @return array
	 */
	private static function get_page_options(): array {
		if ( ! function_exists( 'get_pages' ) ) {
			return array();
		}

		$pages   = get_pages(
			array(
				'sort_column' => 'menu_order',
				'sort_order'  => 'ASC',
				'post_status' => array( 'publish', 'private', 'draft' ),
			)
		);
		$options = array(
			'' => __( 'Select a page…', 'woocommerce' ),
		);

		if ( ! is_array( $pages ) ) {
			return $options;
		}

		foreach ( $pages as $page ) {
			$options[ (string) $page->ID ] = wp_strip_all_tags( $page->post_title );
		}

		return $options;
	}

	/**
	 * Get a normalized field value for React settings.
	 *
	 * @param array  $setting Setting definition.
	 * @param string $type Normalized field type.
	 * @return mixed
	 * @since 10.8.0
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
		$value = \WC_Admin_Settings::get_option( $setting['id'], $default );
		return self::normalize_value( $value, $type );
	}

	/**
	 * Normalize field values to match React field expectations.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type Normalized field type.
	 * @return mixed
	 * @since 10.8.0
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
	 * @since 10.8.0
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
	 * @since 10.8.0
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
	 * @since 10.8.0
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
	 * @since 10.8.0
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
	 * @since 10.8.0
	 */
	private static function is_sequential_keys( array $options ): bool {
		$expected = 0;
		foreach ( $options as $key => $value ) {
			if ( $key !== $expected ) {
				return false;
			}
			++$expected;
		}

		return true;
	}
}
