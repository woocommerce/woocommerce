<?php
/**
 * PlanRepository - persistence for {@see Plan} entities.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;

defined( 'ABSPATH' ) || exit;

/**
 * Plan repository.
 */
final class PlanRepository {

	/**
	 * Policy columns stored as JSON.
	 *
	 * @var array<int, string>
	 */
	private const JSON_COLUMNS = array( 'options', 'billing_policy', 'delivery_policy', 'pricing_policy' );

	/**
	 * Insert a new plan and stamp its id back onto the entity.
	 *
	 * @param Plan $plan Plan to insert.
	 * @return int The new plan id.
	 * @throws \RuntimeException If the insert fails.
	 */
	public function insert( Plan $plan ): int {
		global $wpdb;

		$now  = gmdate( 'Y-m-d H:i:s' );
		$data = $plan->to_storage();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ),
			array(
				'group_id'         => $data['group_id'],
				'name'             => $data['name'],
				'description'      => $data['description'],
				'options'          => wp_json_encode( $data['options'] ),
				'billing_policy'   => wp_json_encode( $data['billing_policy'] ),
				'delivery_policy'  => null !== $data['delivery_policy'] ? wp_json_encode( $data['delivery_policy'] ) : null,
				'inventory_policy' => null,
				'pricing_policy'   => null !== $data['pricing_policy'] ? wp_json_encode( $data['pricing_policy'] ) : null,
				'category'         => $data['category'],
				'extension_slug'   => $data['extension_slug'],
				'date_created_gmt' => $now,
				'date_updated_gmt' => $now,
			)
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to insert plan.' );
		}

		$id = (int) $wpdb->insert_id;
		$plan->set_id( $id );

		return $id;
	}

	/**
	 * Fetch a plan by id.
	 *
	 * @param int $id Plan id.
	 * @return Plan|null Hydrated plan, or null if not found.
	 */
	public function find( int $id ): ?Plan {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		foreach ( self::JSON_COLUMNS as $column ) {
			$row[ $column ] = self::decode_json( $row[ $column ] ?? null );
		}

		return Plan::from_storage( $row );
	}

	/**
	 * Persist changes to an existing plan.
	 *
	 * @param Plan $plan Plan to update. Must have an id.
	 * @return bool True on success.
	 * @throws \RuntimeException If the plan has no id.
	 */
	public function update( Plan $plan ): bool {
		global $wpdb;

		$id = $plan->get_id();
		if ( null === $id ) {
			throw new \RuntimeException( 'Cannot update a plan that has no id.' );
		}

		$data = $plan->to_storage();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ),
			array(
				'name'             => $data['name'],
				'description'      => $data['description'],
				'options'          => wp_json_encode( $data['options'] ),
				'billing_policy'   => wp_json_encode( $data['billing_policy'] ),
				'delivery_policy'  => null !== $data['delivery_policy'] ? wp_json_encode( $data['delivery_policy'] ) : null,
				'pricing_policy'   => null !== $data['pricing_policy'] ? wp_json_encode( $data['pricing_policy'] ) : null,
				'category'         => $data['category'],
				'extension_slug'   => $data['extension_slug'],
				'date_updated_gmt' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id )
		);

		return false !== $updated;
	}

	/**
	 * Delete a plan by id.
	 *
	 * @param int $id Plan id.
	 * @return bool True when a row was removed.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ),
			array( 'id' => $id )
		);

		return (bool) $deleted;
	}

	/**
	 * Decode a JSON column into an array.
	 *
	 * A SQL NULL column stays null so nullable policy columns
	 * (delivery_policy, pricing_policy) round-trip back to null rather than to
	 * an empty value object. A present-but-empty value decodes to an array.
	 *
	 * @param mixed $value Raw column value.
	 * @return array<mixed>|null
	 */
	private static function decode_json( $value ): ?array {
		if ( null === $value ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
