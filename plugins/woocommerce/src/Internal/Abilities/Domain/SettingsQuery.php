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
			'description'         => __( 'Read approved settings in a group. Use include for specific setting IDs. Unapproved fields and passwords are omitted.', 'woocommerce' ),
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
		$group = $input['group'];
		if ( ! in_array( $group, array_column( SettingsGroupsQuery::get_groups(), 'id' ), true ) ) {
			return new \WP_Error( 'woocommerce_ability_settings_group_not_found', __( 'Settings group not found.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		/**
		 * Filters registered fields for a settings group.
		 *
		 * @since 3.0.0
		 * @param array $settings Registered settings.
		 */
		$settings     = apply_filters( 'woocommerce_settings-' . $group, array() ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$readable     = array();
		$option_names = array();
		foreach ( is_array( $settings ) ? $settings : array() as $setting ) {
			if ( ! is_array( $setting ) || ! is_string( $setting['id'] ?? null ) || ! is_string( $setting['type'] ?? null ) ) {
				continue;
			}
			$id  = $setting['id'];
			$key = $setting['option_key'] ?? null;
			if ( ! is_array( $setting['abilities'] ?? null ) || true !== ( $setting['abilities']['read'] ?? false ) || in_array( $setting['type'], array( 'password', 'title', 'sectionend' ), true ) ) {
				continue;
			}
			if ( ! empty( $input['include'] ) && ! in_array( $id, $input['include'], true ) ) {
				continue;
			}
			if ( isset( $setting['fixed_value'] ) || isset( $setting['value'] ) ) {
				$readable[] = $setting;
				continue;
			}
			if ( is_string( $key ) ) {
				$option_names[] = explode( '[', $key, 2 )[0];
			} elseif ( is_array( $key ) && 2 === count( $key ) && is_string( $key[0] ?? null ) && is_string( $key[1] ?? null ) ) {
				$option_names[] = $key[0];
			} else {
				continue;
			}
			$readable[] = $setting;
		}
		wp_prime_option_caches( array_unique( $option_names ) );

		$items = array();
		foreach ( $readable as $setting ) {
			$id      = $setting['id'];
			$key     = $setting['option_key'] ?? null;
			$default = $setting['default'] ?? '';
			if ( isset( $setting['fixed_value'] ) || isset( $setting['value'] ) ) {
				$value = $setting['fixed_value'] ?? $setting['value'];
			} elseif ( is_string( $key ) ) {
				$value = \WC_Admin_Settings::get_option( $key, $default );
			} else {
				$option = get_option( $key[0], array() );
				$value  = is_array( $option ) ? ( $option[ $key[1] ] ?? $default ) : $default;
			}
			if ( true !== rest_validate_value_from_schema( $value, self::get_value_schema() ) ) {
				continue;
			}
			$item = array(
				'id'          => $id,
				'label'       => is_string( $setting['label'] ?? null ) ? $setting['label'] : '',
				'description' => is_string( $setting['description'] ?? null ) ? $setting['description'] : '',
				'type'        => $setting['type'],
				'value'       => $value,
			);
			if ( true === rest_validate_value_from_schema( $default, self::get_value_schema() ) ) {
				$item['default'] = $default;
			}
			if ( is_array( $setting['options'] ?? null ) ) {
				$item['options'] = (object) array_filter( $setting['options'], 'is_string' );
			}
			$items[] = $item;
		}
		$total    = count( $items );
		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 20;
		return array(
			'settings'    => array_slice( $items, ( $page - 1 ) * $per_page, $per_page ),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
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
	 * @return array
	 */
	private static function get_value_schema(): array {
		return array(
			'type'                 => array( 'string', 'number', 'boolean', 'null', 'array', 'object' ),
			'items'                => array( 'type' => array( 'string', 'number', 'boolean', 'null' ) ),
			'properties'           => array(
				'number' => array( 'type' => array( 'string', 'number' ) ),
				'unit'   => array( 'type' => 'string' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the settings collection schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		$schema                        = self::get_collection_output_schema(
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
		$schema['properties']['total'] = array( 'type' => 'integer' );
		return $schema;
	}
}
