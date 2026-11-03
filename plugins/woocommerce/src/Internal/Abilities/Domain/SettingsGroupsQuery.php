<?php
/**
 * Discovers registered settings groups without reading their values.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Discovers registered settings groups without reading their values.
 */
class SettingsGroupsQuery extends AbstractDomainAbility implements AbilityDefinition {
	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/settings-groups-query';
	}

	/**
	 * Register the settings read.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query settings groups', 'woocommerce' ),
			'description'         => __( 'List settings groups, including extension and email groups. Use settings-query to read approved settings in a group.', 'woocommerce' ),
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
	 * List registered settings groups.
	 *
	 * @since 11.4.0
	 * @param array $input Collection filters.
	 * @return array
	 */
	public static function execute( array $input = array() ): array {
		$items = array();
		foreach ( self::get_groups() as $group ) {
			if ( ! empty( $input['include'] ) && ! in_array( $group['id'], $input['include'], true ) ) {
				continue;
			}
			$items[] = $group;
		}
		$total    = count( $items );
		$page     = $input['page'] ?? 1;
		$per_page = $input['per_page'] ?? 20;
		return array(
			'groups'      => array_slice( $items, ( $page - 1 ) * $per_page, $per_page ),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Load group metadata in CLI as well as REST requests.
	 *
	 * @internal
	 * @return array
	 */
	public static function get_groups(): array {
		WC()->register_wp_admin_settings();
		/**
		 * Filters registered settings groups.
		 *
		 * @since 3.0.0
		 * @param array $groups Registered groups.
		 */
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		$items  = array();
		foreach ( is_array( $groups ) ? $groups : array() as $group ) {
			if ( ! is_array( $group ) || ! is_string( $group['id'] ?? null ) || ! is_string( $group['label'] ?? null ) ) {
				continue;
			}
			$items[ $group['id'] ] = array(
				'id'        => $group['id'],
				'label'     => $group['label'],
				'parent_id' => is_string( $group['parent_id'] ?? null ) ? $group['parent_id'] : '',
			);
		}
		return array_values( $items );
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
			'default'              => array(),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the group collection schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		$schema                        = self::get_collection_output_schema(
			'groups',
			array(
				'type'                 => 'object',
				'properties'           => array(
					'id'        => array( 'type' => 'string' ),
					'label'     => array( 'type' => 'string' ),
					'parent_id' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
			)
		);
		$schema['properties']['total'] = array( 'type' => 'integer' );
		return $schema;
	}
}
