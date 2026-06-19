<?php
/**
 * PlanGroupRepository - persistence for {@see PlanGroup} entities.
 *
 * The engine's tables are private API; consumers reach plan groups through the public surface.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;

defined( 'ABSPATH' ) || exit;

/**
 * PlanGroup repository.
 */
final class PlanGroupRepository {

	/**
	 * Insert a new plan group and stamp its id back onto the entity.
	 *
	 * @param PlanGroup $group Group to insert.
	 * @return int The new group id.
	 * @throws \RuntimeException If the insert fails.
	 */
	public function insert( PlanGroup $group ): int {
		global $wpdb;

		$now  = gmdate( 'Y-m-d H:i:s' );
		$data = $group->to_storage();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_GROUPS ),
			array(
				'name'             => $data['name'],
				'merchant_code'    => $data['merchant_code'],
				'options_display'  => wp_json_encode( $data['options_display'] ),
				'app_id'           => $data['app_id'],
				'date_created_gmt' => $now,
				'date_updated_gmt' => $now,
			)
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to insert plan group.' );
		}

		$id = (int) $wpdb->insert_id;
		$group->set_id( $id );

		return $id;
	}

	/**
	 * Fetch a plan group by id.
	 *
	 * @param int $id Group id.
	 * @return PlanGroup|null Hydrated group, or null if not found.
	 */
	public function find( int $id ): ?PlanGroup {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_GROUPS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		$row['options_display'] = self::decode_json( $row['options_display'] );

		return PlanGroup::from_storage( $row );
	}

	/**
	 * Delete a plan group by id.
	 *
	 * @param int $id Group id.
	 * @return bool True when a row was removed.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_GROUPS ),
			array( 'id' => $id )
		);

		return (bool) $deleted;
	}

	/**
	 * Decode a JSON column into an array, tolerating null/empty values.
	 *
	 * @param mixed $value Raw column value.
	 * @return array<mixed>
	 */
	private static function decode_json( $value ): array {
		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
