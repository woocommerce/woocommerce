<?php
/**
 * Changes only settings whose definitions opt in to both ability reads and writes.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Changes only settings whose definitions opt in to both ability reads and writes.
 */
class SettingsUpdate extends AbstractDomainAbility implements AbilityDefinition {
	/**
	 * Field types whose values are sent in `values` rather than `value`.
	 */
	private const LIST_TYPES = array( 'multiselect', 'multi_select_countries' );

	/**
	 * Page settings that must not share a page, matching the advanced settings tab's save checks and page picker exclusions.
	 */
	private const DISTINCT_PAGES = array(
		array( 'woocommerce_terms_page_id', 'woocommerce_checkout_page_id' ),
		array( 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id' ),
		array( 'woocommerce_cart_page_id', 'woocommerce_myaccount_page_id' ),
		array( 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id' ),
		array( 'woocommerce_refund_returns_page_id', 'woocommerce_cart_page_id' ),
		array( 'woocommerce_refund_returns_page_id', 'woocommerce_checkout_page_id' ),
		array( 'woocommerce_refund_returns_page_id', 'woocommerce_shop_page_id' ),
		array( 'woocommerce_refund_returns_page_id', 'woocommerce_myaccount_page_id' ),
	);

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/settings-update';
	}

	/**
	 * Register the settings update.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update settings', 'woocommerce' ),
			'description'         => __( 'Change approved settings in one group, such as the currency, store address or checkout pages. Changes apply to the whole store and every customer at once, so confirm them with the merchant before you send them. Read current values with woocommerce/settings-query first. Unapproved fields and passwords cannot be changed. If any setting is refused, nothing is saved. Each result returns the previous value, so a change can be undone.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_edit_settings' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Same capability as the REST settings update endpoints.
	 *
	 * @since 11.4.0
	 * @return bool
	 */
	public static function can_edit_settings(): bool {
		return (bool) wc_rest_check_manager_permissions( 'settings', 'edit' );
	}

	/**
	 * Validate every requested change before saving any of them.
	 *
	 * @since 11.4.0
	 * @param array $input Group and changes.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$fields = SettingsQuery::get_group_fields( $input['group'] );
		if ( is_wp_error( $fields ) ) {
			return $fields;
		}
		$fields_by_id = array();
		foreach ( $fields as $setting ) {
			$fields_by_id[ $setting['id'] ] ??= $setting;
		}

		$ids = array_column( $input['settings'], 'id' );
		if ( count( $ids ) !== count( array_unique( $ids ) ) ) {
			return self::invalid_value_error(
				array_values( array_unique( array_diff_assoc( $ids, array_unique( $ids ) ) ) ),
				/* translators: %s: comma-separated setting IDs. */
				__( 'These settings are named more than once: %s. Send each setting once. Nothing was saved.', 'woocommerce' )
			);
		}
		$malformed = array();
		foreach ( $input['settings'] as $item ) {
			if ( array_key_exists( 'value', $item ) === array_key_exists( 'values', $item ) ) {
				$malformed[] = $item['id'];
			}
		}
		if ( $malformed ) {
			return self::invalid_value_error(
				$malformed,
				/* translators: %s: comma-separated setting IDs. */
				__( 'These settings need exactly one of value or values: %s. Nothing was saved.', 'woocommerce' )
			);
		}

		$denied = array();
		foreach ( $ids as $id ) {
			if ( ! isset( $fields_by_id[ $id ] ) || ! self::is_writable( $fields_by_id[ $id ] ) ) {
				$denied[] = $id;
			}
		}
		if ( $denied ) {
			return new \WP_Error(
				'woocommerce_ability_settings_not_writable',
				/* translators: %s: comma-separated setting IDs. */
				sprintf( __( 'These settings cannot be changed: %s. Nothing was saved.', 'woocommerce' ), implode( ', ', $denied ) ),
				array(
					'status' => 403,
					'ids'    => $denied,
				)
			);
		}

		$expected = array();
		foreach ( $input['settings'] as $item ) {
			$field = in_array( $fields_by_id[ $item['id'] ]['type'], self::LIST_TYPES, true ) ? 'values' : 'value';
			if ( ! array_key_exists( $field, $item ) ) {
				$expected[ $item['id'] ] = $field;
			}
		}
		if ( $expected ) {
			$messages = array();
			$in_list  = array_keys( $expected, 'values', true );
			$single   = array_keys( $expected, 'value', true );
			if ( $in_list ) {
				/* translators: %s: comma-separated setting IDs. */
				$messages[] = sprintf( __( 'Send these settings as a list in values: %s.', 'woocommerce' ), implode( ', ', $in_list ) );
			}
			if ( $single ) {
				/* translators: %s: comma-separated setting IDs. */
				$messages[] = sprintf( __( 'Send these settings in value, not values: %s.', 'woocommerce' ), implode( ', ', $single ) );
			}
			$messages[] = __( 'Nothing was saved.', 'woocommerce' );
			return new \WP_Error(
				'woocommerce_ability_settings_invalid_value',
				implode( ' ', $messages ),
				array(
					'status'   => 400,
					'ids'      => array_keys( $expected ),
					'expected' => $expected,
				)
			);
		}

		$controller   = new \WC_REST_Setting_Options_Controller();
		$changes      = array();
		$invalid      = array();
		$option_names = array();
		foreach ( $input['settings'] as $item ) {
			$setting = $fields_by_id[ $item['id'] ];
			$value   = self::validate_value( $controller, $setting, $item['values'] ?? $item['value'] );
			if ( is_wp_error( $value ) ) {
				$invalid[] = $item['id'];
				continue;
			}
			$changes[]   = array( $setting, $value );
			$option_name = SettingsQuery::get_option_name( $setting );
			if ( null !== $option_name ) {
				$option_names[] = $option_name;
			}
		}
		if ( $invalid ) {
			return self::invalid_value_error( $invalid );
		}
		$conflicts = self::get_page_conflicts( $changes );
		if ( $conflicts ) {
			return new \WP_Error(
				'woocommerce_ability_settings_page_conflict',
				/* translators: %s: comma-separated setting IDs. */
				sprintf( __( 'These settings would share a page: %s. Cart, checkout and My account each need their own page, terms cannot use the checkout page, and the refund and returns policy cannot use the cart, checkout, shop or My account page. Nothing was saved.', 'woocommerce' ), implode( ', ', $conflicts ) ),
				array(
					'status' => 400,
					'ids'    => $conflicts,
				)
			);
		}

		$value_schema = SettingsQuery::get_value_schema();
		wp_prime_option_caches( array_unique( $option_names ) );
		$results = array();
		foreach ( $changes as $change ) {
			$result   = array( 'id' => $change[0]['id'] );
			$previous = SettingsQuery::get_value( $change[0] );
			if ( true === rest_validate_value_from_schema( $previous, $value_schema ) ) {
				$result['previous'] = $previous;
			}
			$results[] = $result;
		}

		$hide_out_of_stock = get_option( 'woocommerce_hide_out_of_stock_items' );
		$autoload          = self::get_declared_autoload( $input['group'] );
		foreach ( $changes as $index => $change ) {
			list( $setting, $value ) = $change;
			if ( isset( $autoload[ $setting['id'] ] ) ) {
				$setting['autoload'] = $autoload[ $setting['id'] ];
			}
			self::save( $setting, $value );
			$saved                      = SettingsQuery::get_value( $setting );
			$results[ $index ]['value'] = true === rest_validate_value_from_schema( $saved, $value_schema ) ? $saved : $value;
		}
		// Endpoint slugs and the shop page feed rewrite rules, so like the admin save, queue a flush after every save.
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		// Term counts leave out hidden out-of-stock products, so the products settings save recounts them.
		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) !== $hide_out_of_stock ) {
			wc_recount_all_terms( false );
		}
		// Cart sessions keep calculated shipping rates until this version changes, which the shipping and tax admin saves bump.
		if ( in_array( $input['group'], array( 'shipping', 'tax' ), true ) ) {
			\WC_Cache_Helper::get_transient_version( 'shipping', true );
		}

		return array( 'settings' => $results );
	}

	/**
	 * Writable only when readable, opted in with `abilities.write` set to boolean true, and backed by a stored option.
	 *
	 * @param array $setting Field from SettingsQuery::get_group_fields().
	 * @return bool
	 */
	private static function is_writable( array $setting ): bool {
		return SettingsQuery::is_readable( $setting )
			&& true === ( $setting['abilities']['write'] ?? false )
			&& ! SettingsQuery::has_supplied_value( $setting )
			&& null !== SettingsQuery::get_option_name( $setting );
	}

	/**
	 * Applies the REST settings validator for the field type, then the shapes the settings read can return.
	 *
	 * @param \WC_REST_Setting_Options_Controller $controller Validator source.
	 * @param array                               $setting    Writable field.
	 * @param mixed                               $value      Requested value.
	 * @return mixed|\WP_Error
	 */
	private static function validate_value( \WC_REST_Setting_Options_Controller $controller, array $setting, $value ) {
		$type    = $setting['type'];
		$invalid = new \WP_Error( 'woocommerce_ability_settings_invalid_value', __( 'An invalid setting value was passed.', 'woocommerce' ) );

		// Stored options are strings: the admin save compares select values strictly and checkbox values as yes/no.
		if ( is_bool( $value ) ) {
			if ( 'checkbox' !== $type ) {
				return $invalid;
			}
			$value = $value ? 'yes' : 'no';
		} elseif ( is_int( $value ) || is_float( $value ) ) {
			$value = (string) $value;
		}
		// The REST checkbox validator saves the default for "" and "0", while the admin save stores an unticked box as "no".
		// An unset checkbox reads as "", so "" is kept for undo and saved as "no".
		if ( 'checkbox' === $type ) {
			if ( ! in_array( $value, array( 'yes', 'no', '' ), true ) ) {
				return $invalid;
			}
			$value = '' === $value ? 'no' : $value;
		}
		if ( is_array( $value ) !== ( in_array( $type, self::LIST_TYPES, true ) || 'relative_date_selector' === $type ) ) {
			return $invalid;
		}
		if ( is_array( $value ) && 'relative_date_selector' !== $type ) {
			if ( array_filter( $value, 'is_bool' ) ) {
				return $invalid;
			}
			$value = array_map( 'strval', $value );
			// The admin save drops "0" and empty entries from lists, so such a list would not be stored as sent.
			if ( count( array_filter( $value ) ) !== count( $value ) ) {
				return $invalid;
			}
		}

		$method = 'validate_setting_' . $type . '_field';
		if ( in_array( $type, array( 'select', 'radio', 'multiselect' ), true ) ) {
			$setting['options'] = is_array( $setting['options'] ?? null ) ? $setting['options'] : array();
		}
		switch ( $type ) {
			case 'single_select_country':
				$setting['options'] = self::get_country_state_choices();
				$method             = 'validate_setting_select_field';
				break;
			case 'multi_select_countries':
				$setting['options'] = WC()->countries->get_countries();
				$method             = 'validate_setting_multiselect_field';
				break;
			case 'relative_date_selector':
				return self::validate_relative_date( $value, $invalid );
			case 'single_select_page':
			case 'single_select_page_with_search':
				return self::validate_page( $value, $invalid );
			case 'number':
				if ( '' !== $value && ! is_numeric( $value ) ) {
					return $invalid;
				}
				break;
		}
		if ( ! is_callable( array( $controller, $method ) ) ) {
			$method = 'validate_setting_text_field';
		}
		// These validators strip slashes, but ability input was never slashed.
		if ( in_array( $method, array( 'validate_setting_text_field', 'validate_setting_textarea_field' ), true ) ) {
			$value = wp_slash( $value );
		}

		$validated = $controller->$method( $value, $setting );
		if ( is_wp_error( $validated ) ) {
			return $invalid;
		}
		if ( is_array( $value ) && count( (array) $validated ) !== count( $value ) ) {
			return $invalid;
		}
		if ( true !== rest_validate_value_from_schema( $validated, SettingsQuery::get_value_schema() ) ) {
			return $invalid;
		}
		return $validated;
	}

	/**
	 * Requires a known unit, since the admin parser silently falls back to days.
	 *
	 * @param array     $value   Requested retention period.
	 * @param \WP_Error $invalid Error to return.
	 * @return array|\WP_Error
	 */
	private static function validate_relative_date( array $value, \WP_Error $invalid ) {
		$number = $value['number'] ?? null;
		if ( ! in_array( $value['unit'] ?? null, array( 'days', 'weeks', 'months', 'years' ), true ) || ( '' !== $number && ! ( is_numeric( $number ) && $number >= 0 ) ) ) {
			return $invalid;
		}
		return wc_parse_relative_date_option( $value );
	}

	/**
	 * A published page ID, or an empty string to unset the page, matching the pages the REST settings API offers.
	 *
	 * @param string    $value   Requested page ID.
	 * @param \WP_Error $invalid Error to return.
	 * @return string|\WP_Error
	 */
	private static function validate_page( string $value, \WP_Error $invalid ) {
		// An unset checkout page reads as wc_get_page_id()'s -1, so `previous` can carry it back.
		if ( '' === $value || '-1' === $value ) {
			return '';
		}
		$page = ctype_digit( $value ) ? get_post( (int) $value ) : null;
		if ( ! $page instanceof \WP_Post || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			return $invalid;
		}
		return $value;
	}

	/**
	 * Requested page settings that would share a page with another page setting after the batch is saved.
	 *
	 * @param array $changes Validated `[ setting, value ]` pairs.
	 * @return string[]
	 */
	private static function get_page_conflicts( array $changes ): array {
		$requested = array();
		foreach ( $changes as $change ) {
			$requested[ $change[0]['id'] ] = $change[1];
		}
		$conflicts = array();
		foreach ( self::DISTINCT_PAGES as $pair ) {
			if ( ! isset( $requested[ $pair[0] ] ) && ! isset( $requested[ $pair[1] ] ) ) {
				continue;
			}
			$pages = array_map(
				static fn( string $id ): int => absint( $requested[ $id ] ?? get_option( $id ) ),
				$pair
			);
			if ( $pages[0] > 0 && $pages[0] === $pages[1] ) {
				$conflicts = array_merge( $conflicts, array_keys( array_intersect_key( $requested, array_flip( $pair ) ) ) );
			}
		}
		return array_values( array_unique( $conflicts ) );
	}

	/**
	 * Country and country:state codes, matching the choices the REST settings API offers for base-location fields.
	 *
	 * @return array<string, string>
	 */
	private static function get_country_state_choices(): array {
		$choices = array();
		foreach ( WC()->countries->get_countries() as $code => $name ) {
			$states = WC()->countries->get_states( $code );
			if ( ! $states ) {
				$choices[ $code ] = $name;
				continue;
			}
			foreach ( $states as $state_code => $state_name ) {
				$choices[ $code . ':' . $state_code ] = $name . ' - ' . $state_name;
			}
		}
		return $choices;
	}

	/**
	 * String keys go through the admin save so its sanitize filters and option hooks still run.
	 *
	 * @param array $setting Writable field.
	 * @param mixed $value   Validated value.
	 * @return void
	 */
	private static function save( array $setting, $value ): void {
		$key = $setting['option_key'];
		if ( is_array( $key ) ) {
			$option            = get_option( $key[0], array() );
			$option            = is_array( $option ) ? $option : array();
			$option[ $key[1] ] = $value;
			update_option( $key[0], $option );
			return;
		}

		// The admin save unslashes posted data and names the option after the field ID.
		$data = array( $key => wp_slash( $value ) );
		if ( false !== strpos( $key, '[' ) ) {
			parse_str( $key, $parsed );
			$name = (string) key( $parsed );
			$data = array( $name => array( (string) key( (array) $parsed[ $name ] ) => wp_slash( $value ) ) );
		}
		$setting['id'] = $key;
		unset( $setting['field_name'] );
		\WC_Admin_Settings::save_fields( array( $setting ), $data );
	}

	/**
	 * Autoload flags declared by the group's settings page, keyed by field ID, since registered fields drop them.
	 *
	 * @param string $group Settings group ID.
	 * @return array<string, bool>
	 */
	private static function get_declared_autoload( string $group ): array {
		$autoload = array();
		foreach ( \WC_Admin_Settings::get_settings_pages() as $page ) {
			if ( ! $page instanceof \WC_Settings_Page || $page->get_id() !== $group ) {
				continue;
			}
			// Sections and get_settings() as the page registrar reads them, so the same fields are found.
			$sections = $page->get_sections();
			foreach ( array_keys( $sections ? $sections : array( '' => '' ) ) as $section ) {
				foreach ( (array) $page->get_settings( $section ) as $field ) {
					if ( is_array( $field ) && is_string( $field['id'] ?? null ) && isset( $field['autoload'] ) ) {
						$autoload[ $field['id'] ] ??= (bool) $field['autoload'];
					}
				}
			}
		}
		return $autoload;
	}

	/**
	 * Names every refused ID in the message too, since MCP clients receive only the message.
	 *
	 * @param string[]    $ids    Setting IDs with invalid values.
	 * @param string|null $format Translated message with one %s for the IDs; defaults to the invalid values message.
	 * @return \WP_Error
	 */
	private static function invalid_value_error( array $ids, ?string $format = null ): \WP_Error {
		/* translators: %s: comma-separated setting IDs. */
		$format ??= __( 'These settings have invalid values: %s. Nothing was saved.', 'woocommerce' );
		return new \WP_Error(
			'woocommerce_ability_settings_invalid_value',
			sprintf( $format, implode( ', ', $ids ) ),
			array(
				'status' => 400,
				'ids'    => $ids,
			)
		);
	}

	/**
	 * Changes are limited to one group and twenty settings per call. Each item sends `value` or `values`, never both.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$branches = SettingsQuery::get_value_schema()['anyOf'];
		$is_list  = static fn( array $branch ): bool => 'array' === ( $branch['type'] ?? null );
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'group'    => array(
					'type'      => 'string',
					'minLength' => 1,
				),
				'settings' => array(
					'type'     => 'array',
					'minItems' => 1,
					'maxItems' => 20,
					'items'    => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'     => array(
								'type'      => 'string',
								'minLength' => 1,
							),
							'value'  => array(
								'description' => __( 'A single value, or a retention period with number and unit.', 'woocommerce' ),
								'anyOf'       => array_values( array_filter( $branches, static fn( array $branch ): bool => ! $is_list( $branch ) ) ),
							),
							'values' => array(
								'description' => __( 'A list, for multiselect and country list settings. Lists from woocommerce/settings-query or previous go here.', 'woocommerce' ),
							) + current( array_filter( $branches, $is_list ) ),
						),
						'required'             => array( 'id' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array( 'group', 'settings' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * `previous` is omitted when the stored value has a shape the settings read would not return.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'settings' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'       => array( 'type' => 'string' ),
							'previous' => SettingsQuery::get_value_schema(),
							'value'    => SettingsQuery::get_value_schema(),
						),
						'required'             => array( 'id', 'value' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array( 'settings' ),
			'additionalProperties' => false,
		);
	}
}
