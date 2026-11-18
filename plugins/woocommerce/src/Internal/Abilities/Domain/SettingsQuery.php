<?php
/**
 * Reads only approved settings, excluding password fields before loading values.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Reads only approved settings, excluding password fields before loading values.
 */
class SettingsQuery extends AbstractDomainAbility implements AbilityDefinition {
	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/settings-query';
	}

	/**
	 * Register the settings read.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query settings', 'woocommerce' ),
			'description'         => __( 'Read approved settings in a group. Use include for specific setting IDs. Country fields return codes without choices. Unapproved fields and passwords are omitted.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( StoreSettingsGet::class, 'can_read_settings' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Read approved settings in a registered group.
	 *
	 * @since 11.4.0
	 * @param array $input Collection filters.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$fields = self::get_group_fields( $input['group'] );
		if ( is_wp_error( $fields ) ) {
			return $fields;
		}
		$readable     = array();
		$matched      = array();
		$option_names = array();
		foreach ( $fields as $setting ) {
			$id = $setting['id'];
			if ( ! empty( $input['include'] ) && ! in_array( $id, $input['include'], true ) ) {
				continue;
			}
			$matched[] = $id;
			if ( ! self::is_readable( $setting ) ) {
				continue;
			}
			if ( self::has_supplied_value( $setting ) ) {
				$readable[] = $setting;
				continue;
			}
			$option_name = self::get_option_name( $setting );
			if ( null === $option_name ) {
				continue;
			}
			$option_names[] = $option_name;
			$readable[]     = $setting;
		}
		wp_prime_option_caches( array_unique( $option_names ) );

		$items = array();
		foreach ( $readable as $setting ) {
			$default = $setting['default'] ?? '';
			$value   = self::get_value( $setting );
			if ( true !== rest_validate_value_from_schema( $value, self::get_value_schema() ) ) {
				continue;
			}
			$item = array(
				'id'          => $setting['id'],
				'label'       => is_string( $setting['label'] ?? null ) ? $setting['label'] : '',
				'description' => is_string( $setting['description'] ?? null ) ? $setting['description'] : '',
				'type'        => $setting['type'],
				'value'       => $value,
			);
			if ( true === rest_validate_value_from_schema( $default, self::get_value_schema() ) ) {
				$item['default'] = $default;
			}
			if ( is_array( $setting['options'] ?? null ) && ! in_array( $setting['type'], array( 'single_select_country', 'multi_select_countries' ), true ) ) {
				$item['options'] = (object) array_filter( $setting['options'], 'is_string' );
			}
			$items[] = $item;
		}
		$total    = count( $items );
		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 20;
		return array(
			'omitted'     => array_values( array_diff( array_unique( $matched ), array_column( $items, 'id' ) ) ),
			'settings'    => array_slice( $items, ( $page - 1 ) * $per_page, $per_page ),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Fields of a registered group that have string `id` and `type` values, without titles and section ends.
	 *
	 * @since 11.4.0
	 * @param string $group Settings group ID.
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	public static function get_group_fields( string $group ) {
		if ( ! in_array( $group, array_column( SettingsGroupsQuery::get_groups(), 'id' ), true ) ) {
			return new \WP_Error( 'woocommerce_ability_settings_group_not_found', __( 'Settings group not found.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		/**
		 * Filters registered fields for a settings group.
		 *
		 * @since 3.0.0
		 * @param array $settings Registered settings.
		 */
		$settings = apply_filters( 'woocommerce_settings-' . $group, array() ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$fields   = array();
		foreach ( is_array( $settings ) ? $settings : array() as $setting ) {
			if ( ! is_array( $setting ) || ! is_string( $setting['id'] ?? null ) || ! is_string( $setting['type'] ?? null ) ) {
				continue;
			}
			if ( in_array( $setting['type'], array( 'title', 'sectionend' ), true ) ) {
				continue;
			}
			$fields[] = $setting;
		}
		return $fields;
	}

	/**
	 * Readable only with `abilities.read` set to boolean true, and never for passwords.
	 *
	 * @since 11.4.0
	 * @param array $setting Field from get_group_fields().
	 * @return bool
	 */
	public static function is_readable( array $setting ): bool {
		return is_array( $setting['abilities'] ?? null ) && true === ( $setting['abilities']['read'] ?? false ) && 'password' !== $setting['type'];
	}

	/**
	 * A non-null `fixed_value` or `value` takes precedence over the stored option, as in the admin form.
	 *
	 * @since 11.4.0
	 * @param array $setting Field from get_group_fields().
	 * @return bool
	 */
	public static function has_supplied_value( array $setting ): bool {
		return isset( $setting['fixed_value'] ) || isset( $setting['value'] );
	}

	/**
	 * Null when `option_key` is neither a string nor an `[option, member]` pair.
	 *
	 * @since 11.4.0
	 * @param array $setting Field from get_group_fields().
	 * @return string|null
	 */
	public static function get_option_name( array $setting ): ?string {
		$key = $setting['option_key'] ?? null;
		if ( is_string( $key ) ) {
			return explode( '[', $key, 2 )[0];
		}
		if ( is_array( $key ) && 2 === count( $key ) && is_string( $key[0] ?? null ) && is_string( $key[1] ?? null ) ) {
			return $key[0];
		}
		return null;
	}

	/**
	 * Supplied values first, then the stored option and the field default.
	 *
	 * @since 11.4.0
	 * @param array $setting Field with a supplied value or a valid option name.
	 * @return mixed
	 */
	public static function get_value( array $setting ) {
		$key     = $setting['option_key'] ?? null;
		$default = $setting['default'] ?? '';
		if ( self::has_supplied_value( $setting ) ) {
			return $setting['fixed_value'] ?? $setting['value'];
		}
		// Read the stored option directly: WC_Admin_Settings::get_option() strips backslashes the stored value keeps.
		if ( is_string( $key ) && false !== strpos( $key, '[' ) ) {
			parse_str( $key, $parsed );
			$name = (string) key( $parsed );
			$key  = array( $name, (string) key( (array) $parsed[ $name ] ) );
		}
		if ( is_string( $key ) ) {
			$value = get_option( $key, null );
			return null === $value ? $default : $value;
		}
		$option = get_option( $key[0], array() );
		return is_array( $option ) ? ( $option[ $key[1] ] ?? $default ) : $default;
	}

	/**
	 * Get collection filters.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'group'    => array(
					'type'      => 'string',
					'minLength' => 1,
				),
				'include'  => array(
					'type'     => 'array',
					'items'    => array(
						'type'      => 'string',
						'minLength' => 1,
					),
					'maxItems' => 100,
				),
				'page'     => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 20,
				),
			),
			'required'             => array( 'group' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Permit scalar values, scalar lists and the closed retention-period shape.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_value_schema(): array {
		$scalar = array(
			'anyOf' => array(
				array( 'type' => 'string' ),
				array( 'type' => 'number' ),
				array( 'type' => 'boolean' ),
			),
		);
		return array(
			'anyOf' => array_merge(
				$scalar['anyOf'],
				array(
					array(
						'type'  => 'array',
						'items' => $scalar,
					),
					array(
						'type'                 => 'object',
						'properties'           => array(
							'number' => array(
								'anyOf' => array(
									array( 'type' => 'string' ),
									array( 'type' => 'number' ),
								),
							),
							'unit'   => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				)
			),
		);
	}

	/**
	 * Get the settings collection schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		$schema                          = self::get_collection_output_schema(
			'settings',
			array(
				'type'                 => 'object',
				'properties'           => array(
					'id'          => array( 'type' => 'string' ),
					'label'       => array( 'type' => 'string' ),
					'type'        => array( 'type' => 'string' ),
					'value'       => self::get_value_schema(),
					'default'     => self::get_value_schema(),
					'description' => array( 'type' => 'string' ),
					'options'     => array(
						'type'                 => 'object',
						'additionalProperties' => array( 'type' => 'string' ),
					),
				),
				'additionalProperties' => false,
			)
		);
		$schema['properties']['total']   = array( 'type' => 'integer' );
		$schema['properties']['omitted'] = array(
			'type'        => 'array',
			'items'       => array( 'type' => 'string' ),
			'description' => 'Known setting IDs withheld from this query, before pagination. Unknown IDs are not listed.',
		);
		return $schema;
	}
}
