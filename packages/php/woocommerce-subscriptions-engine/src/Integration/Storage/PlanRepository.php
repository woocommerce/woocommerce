<?php
/**
 * PlanRepository - persistence for {@see Plan} entities. The three policy columns are
 * stored as the opaque JSON payloads the entity carries; null stays SQL NULL.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;

defined( 'ABSPATH' ) || exit;

/**
 * Plan repository.
 */
final class PlanRepository {

	/**
	 * SQL condition on the `p` alias: the plan's owner is exactly the bound slug. The binary cast
	 * makes the match case-sensitive, as the engine's PHP comparisons of the owner are.
	 */
	private const OWNER_MATCH = 'p.extension_slug = CAST(%s AS BINARY)';

	/**
	 * Policy columns stored as JSON.
	 *
	 * @var array<int, string>
	 */
	private const JSON_COLUMNS = array( 'billing_policy', 'delivery_policy', 'pricing_policy' );

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
		'date_created_gmt' => 'date_created_gmt',
		'date_updated_gmt' => 'date_updated_gmt',
	);

	/**
	 * Insert a new plan and stamp its id and stored dates back onto the entity.
	 *
	 * @param Plan $plan Plan to insert.
	 * @return int The new plan id.
	 * @throws \RuntimeException If the insert fails.
	 */
	public function insert( Plan $plan ): int {
		global $wpdb;

		$now  = gmdate( 'Y-m-d H:i:s' );
		$data = $this->get_row_data( $plan );

		$data['date_created_gmt'] = $now;
		$data['date_updated_gmt'] = $now;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS ), $data );

		if ( false === $inserted ) {
			throw new \RuntimeException( sprintf( 'Failed to insert plan: %s', esc_html( $wpdb->last_error ) ) );
		}

		$id = (int) $wpdb->insert_id;
		$plan->set_id( $id );
		$plan->set_date_created_gmt( $now );
		$plan->set_date_updated_gmt( $now );

		return $id;
	}

	/**
	 * Fetch a plan by id, in any status, of any extension or (when given) only of the given extension.
	 *
	 * @param int         $id             Plan id.
	 * @param string|null $extension_slug Owning extension slug to scope the read to; null reads any extension.
	 * @return Plan|null Hydrated plan, or null if not found (also when it belongs to another extension).
	 */
	public function find( int $id, ?string $extension_slug = null ): ?Plan {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		if ( null === $extension_slug ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id );
		} else {
			$owner_match = self::OWNER_MATCH;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- the owner condition carries one placeholder.
			$sql = $wpdb->prepare( "SELECT p.* FROM {$table} p WHERE p.id = %d AND {$owner_match}", $id, $extension_slug );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $sql, ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_row( $row );
	}

	/**
	 * Query plans.
	 *
	 * Supported args: limit, offset, search, status, extension_slugs, ids,
	 * orderby, order. `status` is a slug or a list of slugs (an empty list or a
	 * non-string entry matches nothing). `extension_slugs` filters by owning
	 * extension: a list of slugs (a single-slug list unfolds to an equality
	 * match) or `array( 'any' )` to skip the scope. `ids` filters to plans whose
	 * id is in the given int list; it composes with the other filters and is
	 * honored by count(). `search` matches the name. `orderby` is one of `id`
	 * (default), `name`, `date_created_gmt`, `date_updated_gmt`, with the id
	 * ascending as a stable tiebreaker.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, Plan>
	 */
	public function query( array $args = array() ): array {
		global $wpdb;

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$order  = $this->build_order_clause( $args );
		$limit  = max( 1, Coercion::coerce_int( $args['limit'] ?? null, 50 ) );
		$offset = max( 0, Coercion::coerce_int( $args['offset'] ?? null, 0 ) );

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
			$plans[] = $this->hydrate_row( Coercion::coerce_string_keyed( $row ) );
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
	 * Write only the given columns of an existing plan's row (plus its update time, stamped
	 * back onto the entity), so columns a concurrent writer changed in between keep its
	 * values. The write is scoped to the plan's extension: it matches the row by id and the
	 * entity's extension slug, so a row of another extension is never written. Plan meta is
	 * never touched. Existence (by id and extension slug) is checked only when the write changes
	 * nothing. Opens no transaction: a caller's transaction covers the write.
	 *
	 * @param Plan               $plan   Plan to read the values from. Must have an id and an extension slug.
	 * @param array<int, string> $fields Columns to write: `name`, `status`, `billing_policy`,
	 *                                   `pricing_policy`, `delivery_policy`.
	 * @return bool False when no row of the plan's extension has its id (nothing is written).
	 * @throws \InvalidArgumentException If a field is not a writable column.
	 * @throws \RuntimeException If the plan has no id or no extension slug, a value does not fit its
	 *                           column, the update fails, or the existence read after a 0-row update fails.
	 */
	public function update_fields( Plan $plan, array $fields ): bool {
		global $wpdb;

		$id = $plan->get_id();
		if ( null === $id ) {
			throw new \RuntimeException( 'Cannot update a plan that has no id.' );
		}

		$extension_slug = $plan->get_extension_slug();
		if ( null === $extension_slug || '' === $extension_slug ) {
			throw new \RuntimeException( 'Cannot update a plan that has no extension slug.' );
		}

		$table   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$row     = $this->get_row_data( $plan );
		$columns = array();
		foreach ( $fields as $field ) {
			if ( 'extension_slug' === $field || ! array_key_exists( $field, $row ) ) {
				throw new \InvalidArgumentException( esc_html( sprintf( 'Cannot update plan field "%s".', $field ) ) );
			}
			if ( is_string( $row[ $field ] ) ) {
				ColumnFit::assert_fits_column( $table, $field, $row[ $field ] );
			}
			$columns[ $field ] = $row[ $field ];
		}

		$now = gmdate( 'Y-m-d H:i:s' );

		$set    = array();
		$values = array();
		foreach ( array_merge( $columns, array( 'date_updated_gmt' => $now ) ) as $column => $value ) {
			if ( null === $value ) {
				$set[] = "`{$column}` = NULL";
			} else {
				$set[]    = "`{$column}` = %s";
				$values[] = $value;
			}
		}
		$values[] = $id;
		$values[] = $extension_slug;

		$sql = "UPDATE {$table} p SET " . implode( ', ', $set ) . ' WHERE p.id = %d AND ' . self::OWNER_MATCH;

		// Not `$wpdb->update()`: its WHERE cannot express the binary owner match. Column names come
		// from the plan's own row data; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$updated = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $updated ) {
			throw new \RuntimeException( sprintf( 'Failed to update plan %d: %s', (int) $id, esc_html( $wpdb->last_error ) ) );
		}

		// Zero changed rows: no row of this extension, or identical values written within the same second.
		if ( 0 === $updated && ! $this->exists( $id, $extension_slug ) ) {
			return false;
		}

		$plan->set_date_updated_gmt( $now );

		return true;
	}

	/**
	 * Whether a plan row exists, of any extension or (when given) of the given extension.
	 *
	 * @param int         $id             Plan id.
	 * @param string|null $extension_slug Owning extension slug to scope the check to; null checks any extension.
	 * @throws \RuntimeException If the read fails: a failed read must not look like a missing plan.
	 */
	public function exists( int $id, ?string $extension_slug = null ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		if ( null === $extension_slug ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $id );
		} else {
			$owner_match = self::OWNER_MATCH;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- the owner condition carries one placeholder.
			$sql = $wpdb->prepare( "SELECT p.id FROM {$table} p WHERE p.id = %d AND {$owner_match}", $id, $extension_slug );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$found = $wpdb->get_var( $sql );
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( sprintf( 'Failed to read plan %d: %s', (int) $id, esc_html( $wpdb->last_error ) ) );
		}

		return null !== $found;
	}

	/**
	 * Delete a plan and its meta rows by id and (optionally) extension slug.
	 * Most usages from applications should specify the extension slug
	 * to guard against cross-application operations.
	 *
	 * A failed delete throws, so a caller's transaction can roll back.
	 *
	 * @param int         $id             Plan id.
	 * @param string|null $extension_slug Extension slug for the plan.
	 * @return bool True when a row was removed.
	 * @throws \RuntimeException If the plan row or its meta rows fail to delete.
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

		if ( false === $deleted ) {
			throw new \RuntimeException( sprintf( 'Failed to delete plan %d: %s', (int) $id, esc_html( $wpdb->last_error ) ) );
		}

		if ( 0 === $deleted ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted_meta = $wpdb->delete( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META ), array( 'plan_id' => $id ) );

		if ( false === $deleted_meta ) {
			throw new \RuntimeException( sprintf( 'Failed to delete meta rows for plan %d: %s', (int) $id, esc_html( $wpdb->last_error ) ) );
		}

		return true;
	}

	/**
	 * Add a meta row for a plan of `$extension_slug`, like `add_post_meta()`, as one
	 * `INSERT ... SELECT` from the plan row, so no row is written for a missing plan or a plan of
	 * another extension. With `$unique` the same statement checks that the key is absent.
	 *
	 * @param int    $plan_id        Plan id.
	 * @param string $extension_slug The expected owning extension.
	 * @param string $key            Meta key.
	 * @param mixed  $value          Meta value; serialized when not scalar.
	 * @param bool   $unique         When true, add nothing if the key already exists. Advisory:
	 *                               no unique index, so concurrent adds can both write.
	 * @return int|null The new meta row id, or null when `$unique` and the key exists.
	 * @throws \InvalidArgumentException If no plan of that extension has the id.
	 * @throws \RuntimeException If the key is longer than the meta_key column (255 characters), or the insert or the existence read fails.
	 */
	public function add_meta( int $plan_id, string $extension_slug, string $key, $value, bool $unique = false ): ?int {
		$meta_id = $this->insert_meta_row( $plan_id, $extension_slug, $key, $value, $unique );
		if ( null !== $meta_id ) {
			return $meta_id;
		}

		// Zero rows: the key is taken (unique only) or no plan of that extension has the id.
		$owned = $unique && $this->exists( $plan_id, $extension_slug );
		if ( ! $owned ) {
			self::throw_not_found( $plan_id, $extension_slug );
		}

		return null;
	}

	/**
	 * Insert one meta row selected from the plan row of `$extension_slug`; with `$unique`, only
	 * while the plan has no row for `$key`.
	 *
	 * @param int    $plan_id        Plan id.
	 * @param string $extension_slug The expected owning extension.
	 * @param string $key            Meta key.
	 * @param mixed  $value          Meta value; serialized when not scalar.
	 * @param bool   $unique         Whether the key must be absent.
	 * @return int|null The new meta row id, or null when nothing was inserted.
	 * @throws \RuntimeException If the key does not fit the meta_key column, or the insert fails.
	 */
	private function insert_meta_row( int $plan_id, string $extension_slug, string $key, $value, bool $unique ): ?int {
		global $wpdb;

		$meta = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META );
		ColumnFit::assert_fits_column( $meta, 'meta_key', $key );

		$plans  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$sql    = "INSERT INTO {$meta} ( plan_id, meta_key, meta_value ) SELECT p.id, %s, %s FROM {$plans} p WHERE p.id = %d AND " . self::OWNER_MATCH;
		$values = array( $key, maybe_serialize( $value ), $plan_id, $extension_slug );
		if ( $unique ) {
			$sql     .= " AND NOT EXISTS ( SELECT 1 FROM {$meta} m WHERE m.plan_id = p.id AND m.meta_key = %s )";
			$values[] = $key;
		}

		// Fixed SQL fragments only; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$inserted = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $inserted ) {
			throw new \RuntimeException( sprintf( 'Failed to add plan meta "%s" for plan %d: %s', esc_html( $key ), (int) $plan_id, esc_html( $wpdb->last_error ) ) );
		}
		if ( 1 !== (int) $inserted ) {
			return null;
		}

		return (int) $wpdb->insert_id;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Update the meta rows for `$key` of a plan of `$extension_slug`, like `update_post_meta()`:
	 * rewrites every row for the key, or only the rows holding `$prev_value`, and adds the key when
	 * the plan has no row for it. The rows are written first; the plan and key are read only after
	 * a write that changed nothing.
	 *
	 * @param int    $plan_id        Plan id.
	 * @param string $extension_slug The expected owning extension.
	 * @param string $key            Meta key.
	 * @param mixed  $value          New value; serialized when not scalar.
	 * @param mixed  $prev_value     Only update rows holding this value; null updates all rows for the key.
	 *                               Any other value ('' and false included) matches literally.
	 * @return bool True when a row was added or at least one row changed.
	 * @throws \InvalidArgumentException If no plan of that extension has the id.
	 * @throws \RuntimeException If the key is longer than the meta_key column (255 characters), or a write or the classifying read fails.
	 */
	public function update_meta( int $plan_id, string $extension_slug, string $key, $value, $prev_value = null ): bool {
		global $wpdb;

		$meta   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META );
		$plans  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$values = array( maybe_serialize( $value ), $plan_id, $extension_slug, $key );
		$where  = 'm.plan_id = %d AND ' . self::OWNER_MATCH . ' AND m.meta_key = %s';
		if ( null !== $prev_value ) {
			$where   .= ' AND m.meta_value = %s';
			$values[] = maybe_serialize( $prev_value );
		}

		// The WHERE clause is built from fixed fragments above; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$meta} m JOIN {$plans} p ON p.id = m.plan_id SET m.meta_value = %s WHERE {$where}", $values ) );

		if ( false === $updated ) {
			throw new \RuntimeException( sprintf( 'Failed to update plan meta "%s" for plan %d: %s', esc_html( $key ), (int) $plan_id, esc_html( $wpdb->last_error ) ) );
		}
		if ( $updated > 0 ) {
			return true;
		}

		$has_key = $this->get_meta_key_presence( $plan_id, $extension_slug, $key );
		if ( null === $has_key ) {
			self::throw_not_found( $plan_id, $extension_slug );
		}
		if ( $has_key ) {
			return false;
		}

		$meta_id = $this->insert_meta_row( $plan_id, $extension_slug, $key, $value, false );
		if ( null === $meta_id ) {
			self::throw_not_found( $plan_id, $extension_slug );
		}

		return true;
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Whether a plan of `$extension_slug` has a meta row for `$key`, read in one statement.
	 *
	 * @param int    $plan_id        Plan id.
	 * @param string $extension_slug The expected owning extension.
	 * @param string $key            Meta key.
	 * @return bool|null Whether the key has a row; null when no plan of that extension has the id.
	 * @throws \RuntimeException If the read fails.
	 */
	private function get_meta_key_presence( int $plan_id, string $extension_slug, string $key ): ?bool {
		global $wpdb;

		$meta  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META );
		$plans = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		$owner_match = self::OWNER_MATCH;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- the owner condition carries one placeholder.
		$has_key = $wpdb->get_var( $wpdb->prepare( "SELECT EXISTS ( SELECT 1 FROM {$meta} m WHERE m.plan_id = p.id AND m.meta_key = %s ) FROM {$plans} p WHERE p.id = %d AND {$owner_match}", $key, $plan_id, $extension_slug ) );
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( sprintf( 'Failed to read plan meta "%s" for plan %d: %s', esc_html( $key ), (int) $plan_id, esc_html( $wpdb->last_error ) ) );
		}

		return null === $has_key ? null : '1' === (string) $has_key;
	}

	/**
	 * Delete the meta rows for `$key` of a plan of `$extension_slug`, like `delete_post_meta()`.
	 *
	 * @param int    $plan_id        Plan id.
	 * @param string $extension_slug The expected owning extension.
	 * @param string $key            Meta key.
	 * @param mixed  $value          Only delete rows holding this value; null deletes every row for the key.
	 *                               Any other value ('' and false included) matches literally.
	 * @return bool True when at least one row was deleted (false when no plan of that extension has the id).
	 * @throws \RuntimeException If the delete fails.
	 */
	public function delete_meta( int $plan_id, string $extension_slug, string $key, $value = null ): bool {
		global $wpdb;

		$meta   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META );
		$plans  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );
		$values = array( $plan_id, $extension_slug, $key );
		$where  = 'm.plan_id = %d AND ' . self::OWNER_MATCH . ' AND m.meta_key = %s';
		if ( null !== $value ) {
			$where   .= ' AND m.meta_value = %s';
			$values[] = maybe_serialize( $value );
		}

		// The WHERE clause is built from fixed fragments above; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE m FROM {$meta} m JOIN {$plans} p ON p.id = m.plan_id WHERE {$where}", $values ) );

		if ( false === $deleted ) {
			throw new \RuntimeException( sprintf( 'Failed to delete plan meta "%s" for plan %d: %s', esc_html( $key ), (int) $plan_id, esc_html( $wpdb->last_error ) ) );
		}

		return $deleted > 0;
	}

	/**
	 * Refuse a meta write for a plan id no plan of `$extension_slug` has.
	 *
	 * @param int    $plan_id        Plan id.
	 * @param string $extension_slug The expected owning extension.
	 * @return never
	 * @throws \InvalidArgumentException Always.
	 */
	private static function throw_not_found( int $plan_id, string $extension_slug ): void {
		throw new \InvalidArgumentException( esc_html( sprintf( 'No plan of extension "%s" has id %d.', $extension_slug, $plan_id ) ) );
	}

	/**
	 * Read plan meta (WordPress `get_post_meta()` semantics), values unserialized,
	 * oldest row first.
	 *
	 * @param int    $plan_id Plan id.
	 * @param string $key     Meta key; empty for every key.
	 * @param bool   $single  With a key: return the first value only.
	 * @return mixed Empty key: `array<string, array<int, mixed>>` of all keys. Key + `$single`:
	 *               the first value, or '' when absent. Key only: the list of values (`[]` when absent).
	 */
	public function get_meta( int $plan_id, string $key = '', bool $single = false ) {
		if ( '' === $key ) {
			$all = array();
			foreach ( $this->find_meta_rows( $plan_id, null ) as $row ) {
				$all[ $row['meta_key'] ][] = maybe_unserialize( $row['meta_value'] );
			}

			return $all;
		}

		$values = $this->find_meta_values( $plan_id, $key );

		if ( $single ) {
			return array() === $values ? '' : $values[0];
		}

		return $values;
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

		if ( array_key_exists( 'status', $args ) && null !== $args['status'] ) {
			$statuses = is_array( $args['status'] ) ? array_values( $args['status'] ) : array( $args['status'] );
			$valid    = array();
			foreach ( $statuses as $status ) {
				if ( ! is_string( $status ) || '' === $status ) {
					$valid = array();
					break;
				}
				$valid[ $status ] = $status;
			}

			if ( array() === $valid ) {
				$clauses[] = self::MATCH_NOTHING;
			} elseif ( 1 === count( $valid ) ) {
				$clauses[] = 'status = %s';
				$params[]  = reset( $valid );
			} else {
				$clauses[] = 'status IN (' . implode( ',', array_fill( 0, count( $valid ), '%s' ) ) . ')';
				$params    = array_merge( $params, array_values( $valid ) );
			}
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
					$plan_id = Coercion::coerce_int( $possible_id );
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

		$search = Coercion::coerce_string( $args['search'] ?? null );
		if ( '' !== $search ) {
			$like      = '%' . $wpdb->esc_like( $search ) . '%';
			$clauses[] = 'name LIKE %s';
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
		$orderby_arg = Coercion::coerce_string( $args['orderby'] ?? null );
		$orderby     = self::ORDERBY_COLUMNS[ $orderby_arg ] ?? 'id';
		$order       = 'desc' === strtolower( Coercion::coerce_string( $args['order'] ?? null ) ) ? 'DESC' : 'ASC';

		if ( 'id' === $orderby ) {
			return "ORDER BY id {$order}";
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
	 * The writable plan columns for `$plan`, policies JSON-encoded (null stays null).
	 *
	 * @param Plan $plan Plan.
	 * @return array<string, mixed>
	 */
	private function get_row_data( Plan $plan ): array {
		$data = $plan->to_storage();

		foreach ( self::JSON_COLUMNS as $column ) {
			$data[ $column ] = null !== $data[ $column ] ? wp_json_encode( $data[ $column ] ) : null;
		}

		return $data;
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
	 * A SQL NULL column stays null so the nullable policy columns round-trip
	 * back to null. A present-but-empty value decodes to an empty array.
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
	 * Unserialized values stored under `$key` for a plan, oldest first.
	 *
	 * @param int    $plan_id Plan id.
	 * @param string $key     Meta key.
	 * @return array<int, mixed>
	 */
	private function find_meta_values( int $plan_id, string $key ): array {
		$values = array();
		foreach ( $this->find_meta_rows( $plan_id, $key ) as $row ) {
			$values[] = maybe_unserialize( $row['meta_value'] );
		}

		return $values;
	}

	/**
	 * Raw meta rows for a plan, optionally for one key, by id ascending.
	 *
	 * @param int         $plan_id Plan id.
	 * @param string|null $key     Meta key, or null for every key.
	 * @return array<int, array{meta_key: string, meta_value: string}>
	 */
	private function find_meta_rows( int $plan_id, ?string $key ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META );

		if ( null === $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE plan_id = %d ORDER BY id ASC", $plan_id ), ARRAY_A );
		} else {
			// The engine's own plan-meta columns, not post/order meta; the
			// slow-meta-query heuristic does not apply.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE plan_id = %d AND meta_key = %s ORDER BY id ASC", $plan_id, $key ), ARRAY_A );
		}

		$result = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$result[] = array(
					'meta_key'   => Coercion::coerce_string( $row['meta_key'] ?? null ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => Coercion::coerce_string( $row['meta_value'] ?? null ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				);
			}
		}

		return $result;
	}
}
