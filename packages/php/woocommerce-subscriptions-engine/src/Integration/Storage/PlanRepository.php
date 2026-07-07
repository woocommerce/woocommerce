<?php
/**
 * PlanRepository - persistence for {@see Plan} entities.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;

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
	 * Always-false WHERE clause: a filter arg that is present but empty or
	 * invalid must match NOTHING, never fall open to matching everything
	 * (the WP core / WooCommerce fail-closed posture, e.g. WP_Tax_Query and
	 * the HPOS OrdersTableFieldQuery force_no_results clause).
	 *
	 * @var string
	 */
	private const MATCH_NOTHING = '0 = 1';

	/**
	 * Columns callers may sort by through query().
	 *
	 * @var array<string, string>
	 */
	private const ORDERBY_COLUMNS = array(
		'id'               => 'id',
		'name'             => 'name',
		'sort_order'       => 'sort_order',
		'status'           => 'status',
		'date_created_gmt' => 'date_created_gmt',
		'date_updated_gmt' => 'date_updated_gmt',
	);

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
				'status'           => $data['status'],
				'sort_order'       => $data['sort_order'],
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
	 * Fetch a plan by id and (optionally) extension slug.
	 * Most usages from applications should specify the extension slug
	 * to guard against cross-application collisions.
	 *
	 * @param int         $id             Plan id.
	 * @param string|null $extension_slug Extension slug to filter plans by.
	 * @return Plan|null Hydrated plan, or null if not found.
	 */
	public function find( int $id, ?string $extension_slug = null ): ?Plan {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		$extension_clause = '';
		$params           = array( $id );
		if ( null !== $extension_slug && 'any' !== $extension_slug ) {
			$extension_clause = ' AND extension_slug = %s';
			$params[]         = $extension_slug;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d {$extension_clause}", $params ),
			ARRAY_A
		);

		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_row( $row );
	}

	/**
	 * Query plans.
	 *
	 * Supported args: limit, offset, search, status, extension_slugs, ids,
	 * orderby, order. `extension_slugs` filters by owning extension: a list
	 * of slugs (a single-slug list unfolds to an equality match) or
	 * `array( 'any' )` to skip the scope. `ids` filters to plans whose id is
	 * in the given int list; it composes with the other filters and is
	 * honored by count(). Results default to manual order, oldest id as a
	 * stable tiebreaker.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, Plan>
	 */
	public function query( array $args = array() ): array {
		global $wpdb;

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$order  = $this->build_order_clause( $args );
		$limit  = max( 1, ScalarCoercion::coerce_int( $args['limit'] ?? null, 50 ) );
		$offset = max( 0, ScalarCoercion::coerce_int( $args['offset'] ?? null, 0 ) );

		// phpcs:ignore Generic.Arrays.DisallowShortArraySyntax.Found
		[
			'sql'    => $where_sql,
			'params' => $where_params,
		] = $this->build_where_clause( $args );

		$params = array( ...$where_params, $limit, $offset );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table}{$where_sql} {$order} LIMIT %d OFFSET %d", $params ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$plans = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$plans[] = $this->hydrate_row( self::string_keyed_array( $row ) );
		}

		return $plans;
	}

	/**
	 * Count plans matching a query.
	 *
	 * Supported args are the filter args accepted by query().
	 *
	 * @param array<string, mixed> $args Query args.
	 */
	public function count( array $args = array() ): int {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		// phpcs:ignore Generic.Arrays.DisallowShortArraySyntax.Found
		[
			'sql'    => $where_sql,
			'params' => $where_params,
		] = $this->build_where_clause( $args );

		if ( array() === $where_params ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}{$where_sql}" );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}{$where_sql}", $where_params ) );
		}

		return (int) $result;
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
				'status'           => $data['status'],
				'sort_order'       => $data['sort_order'],
				'extension_slug'   => $data['extension_slug'],
				'date_updated_gmt' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id )
		);

		return false !== $updated;
	}

	/**
	 * Delete a plan by id and (optionally) extension slug.
	 * Most usages from applications should specify the extension slug
	 * to guard against cross-application operations.
	 *
	 * @param int         $id             Plan id.
	 * @param string|null $extension_slug Extension slug for the plan.
	 * @return bool True when a row was removed.
	 */
	public function delete( int $id, ?string $extension_slug = null ): bool {
		global $wpdb;

		$where = array(
			'id' => $id,
		);
		if ( null !== $extension_slug ) {
			$where['extension_slug'] = $extension_slug;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ), $where );

		return (bool) $deleted;
	}

	/**
	 * Persist manual sort-order values for plans in one extension.
	 *
	 * @param string          $extension_slug   Extension slug for the plans to operate on.
	 * @param array<int, int> $sort_order_by_id Map of plan id => sort order.
	 * @return bool True when every update succeeds.
	 */
	public function reorder( string $extension_slug, array $sort_order_by_id ): bool {
		global $wpdb;

		if ( ! self::is_valid_extension_slug( $extension_slug ) ) {
			return false;
		}

		if ( array() === $sort_order_by_id ) {
			return true;
		}

		$ok  = true;
		$now = gmdate( 'Y-m-d H:i:s' );

		$plans_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$ids         = array_map( 'intval', array_keys( $sort_order_by_id ) );
		foreach ( $ids as $id ) {
			if ( $id <= 0 ) {
				return false;
			}
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$params       = array_merge( array( $extension_slug ), $ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$matched_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$plans_table} WHERE extension_slug = %s AND id IN ({$placeholders})", $params ) );
		$matched_ids = is_array( $matched_ids )
			? array_unique(
				array_map(
					static function ( $matched_id ): int {
						return ScalarCoercion::coerce_int( $matched_id );
					},
					$matched_ids
				)
			)
			: array();
		if ( count( $matched_ids ) !== count( $ids ) ) {
			return false;
		}

		foreach ( $sort_order_by_id as $id => $sort_order ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$plans_table,
				array(
					'sort_order'       => (int) $sort_order,
					'date_updated_gmt' => $now,
				),
				array(
					'id'             => (int) $id,
					'extension_slug' => $extension_slug,
				)
			);

			$ok = $ok && false !== $updated;
		}

		return $ok;
	}

	/**
	 * Build SQL WHERE clauses and params from supported query args.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array{sql: string, params: array<int, mixed>}
	 */
	private function build_where_clause( array $args ): array {
		global $wpdb;

		$clauses = array();
		$params  = array();

		$status = ScalarCoercion::coerce_string( $args['status'] ?? null );
		if ( '' !== $status ) {
			$clauses[] = 'status = %s';
			$params[]  = $status;
		}

		if ( array_key_exists( 'extension_slugs', $args ) && null !== $args['extension_slugs'] ) {
			$are_extension_slugs_valid = false;

			if ( is_array( $args['extension_slugs'] ) ) {
				if ( 1 === count( $args['extension_slugs'] ) && 'any' === reset( $args['extension_slugs'] ) ) {
					$are_extension_slugs_valid = true;
				} else {
					$possible_slugs = array_values( $args['extension_slugs'] );
					$valid_slugs    = array();
					foreach ( $possible_slugs as $possible_slug ) {
						if ( self::is_valid_extension_slug( $possible_slug ) && is_string( $possible_slug ) ) {
							$valid_slugs[ $possible_slug ] = $possible_slug;
						}
					}

					// Require all slugs to be valid before running the query.
					if ( array() !== $valid_slugs && count( $valid_slugs ) === count( $possible_slugs ) ) {
						$are_extension_slugs_valid = true;

						$extension_slugs = array_values( $valid_slugs );
						if ( 1 === count( $extension_slugs ) ) {
							$clauses[] = 'extension_slug = %s';
							$params[]  = $extension_slugs[0];
						} else {
							$clauses[] = 'extension_slug IN (' . implode( ',', array_fill( 0, count( $extension_slugs ), '%s' ) ) . ')';
							$params    = array_merge( $params, $extension_slugs );
						}
					}
				}
			}

			if ( ! $are_extension_slugs_valid ) {
				$clauses[] = self::MATCH_NOTHING;
			}
		}

		if ( array_key_exists( 'ids', $args ) && null !== $args['ids'] ) {
			$are_ids_valid = false;

			if ( is_array( $args['ids'] ) && array() !== $args['ids'] ) {
				$ids       = array();
				$all_valid = true;
				foreach ( array_values( $args['ids'] ) as $possible_id ) {
					$plan_id = ScalarCoercion::coerce_int( $possible_id );
					if ( $plan_id <= 0 ) {
						$all_valid = false;
						break;
					}
					$ids[ $plan_id ] = $plan_id;
				}

				// Require all ids to be positive ints before running the query.
				if ( $all_valid ) {
					$are_ids_valid = true;

					$ids       = array_values( $ids );
					$clauses[] = 'id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';
					$params    = array_merge( $params, $ids );
				}
			}

			if ( ! $are_ids_valid ) {
				$clauses[] = self::MATCH_NOTHING;
			}
		}

		$search = ScalarCoercion::coerce_string( $args['search'] ?? null );
		if ( '' !== $search ) {
			$like      = '%' . $wpdb->esc_like( $search ) . '%';
			$clauses[] = '(name LIKE %s OR description LIKE %s)';
			$params[]  = $like;
			$params[]  = $like;
		}

		if ( empty( $clauses ) ) {
			return array(
				'sql'    => '',
				'params' => array(),
			);
		}

		return array(
			'sql'    => ' WHERE ' . implode( ' AND ', $clauses ),
			'params' => $params,
		);
	}

	/**
	 * Build a safe ORDER BY clause from supported query args.
	 *
	 * @param array<string, mixed> $args Query args.
	 */
	private function build_order_clause( array $args ): string {
		$orderby_arg = ScalarCoercion::coerce_string( $args['orderby'] ?? null );
		$orderby     = isset( self::ORDERBY_COLUMNS[ $orderby_arg ] )
			? self::ORDERBY_COLUMNS[ $orderby_arg ]
			: 'sort_order';
		$order       = 'desc' === strtolower( ScalarCoercion::coerce_string( $args['order'] ?? null ) ) ? 'DESC' : 'ASC';

		if ( 'sort_order' === $orderby ) {
			return "ORDER BY sort_order {$order}, id ASC";
		}

		return "ORDER BY {$orderby} {$order}, id ASC";
	}

	/**
	 * Whether a value is a valid concrete extension slug.
	 *
	 * @param mixed $slug Possible extension slug.
	 */
	private static function is_valid_extension_slug( $slug ): bool {
		if ( ! is_string( $slug ) ) {
			return false;
		}
		if ( '' === $slug || 'any' === $slug ) {
			return false;
		}
		return true;
	}

	/**
	 * Hydrate a database row into a plan.
	 *
	 * @param array<string, mixed> $row Raw row.
	 */
	private function hydrate_row( array $row ): Plan {
		foreach ( self::JSON_COLUMNS as $column ) {
			$row[ $column ] = self::decode_json( $row[ $column ] ?? null );
		}

		return Plan::from_storage( $row );
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

	/**
	 * Normalize a database row to string keys.
	 *
	 * @param array<array-key, mixed> $row Raw row.
	 * @return array<string, mixed>
	 */
	private static function string_keyed_array( array $row ): array {
		$data = array();
		foreach ( $row as $key => $value ) {
			if ( is_string( $key ) ) {
				$data[ $key ] = $value;
			}
		}

		return $data;
	}
}
