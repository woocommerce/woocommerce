<?php
/**
 * NotificationsMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\CancellationSourceMiner;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\DateMapper;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\MetaMapper;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\StatusMapper;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\WriterInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates `woocommerce_bis_notifications` rows to Core `wc_stock_notifications` rows.
 *
 * The candidate predicate in `predicate_sql()` is authoritative: `get_batch()` and
 * `count_remaining()` both express it, and nothing downstream may skip a row it admits
 * without writing `_wc_bis_migration_failed`. `get_batch()` returns identifiers only and
 * is side-effect free; `migrate_batch()` re-fetches the full rows for those identifiers
 * and does the actual work. Legacy meta and cancellation sources are fetched once per
 * batch, never per row.
 */
class NotificationsMigrator implements MigratorInterface {

	/**
	 * Section slug.
	 *
	 * @var string
	 */
	private const SLUG = 'notifications';

	/**
	 * Legacy meta keys read for a batch in one query.
	 *
	 * @var string[]
	 */
	private const LEGACY_META_KEYS = array(
		'posted_attributes',
		'_customer_locale',
		'_customer_location_data',
		'_hash_key',
		'_hash_iv',
	);

	/**
	 * Migration marker recording a successful migration onto a Core notification.
	 * Inserted, never updated; a Core row can carry several.
	 *
	 * @var string
	 */
	private const LEGACY_ID_META_KEY = '_wc_bis_legacy_id';

	/**
	 * Meta key holding the precomputed legacy unsubscribe token digest, one row per
	 * legacy id.
	 *
	 * @var string
	 */
	private const LEGACY_UNSUB_HASH_META_KEY = '_wc_bis_legacy_unsub_hash';

	/**
	 * Marker meta carrying the legacy id, written only when a legacy row adopts a
	 * pre-existing Core notification instead of being inserted. Inserted alongside
	 * `_wc_bis_legacy_id`, never updated; a Core row adopted by several legacy rows
	 * carries one pair per legacy id. This is what lets `rollback` tell an adopted row
	 * — never deleted — apart from one this migration inserted.
	 *
	 * @var string
	 */
	private const ADOPTED_MARKER_META_KEY = '_wc_bis_legacy_adopted';

	/**
	 * Legacy meta key recording a permanent per-row failure. The migration's only write
	 * into the legacy schema.
	 *
	 * @var string
	 */
	private const FAILED_META_KEY = '_wc_bis_migration_failed';

	/**
	 * Autoloaded option that gates registration of the legacy unsubscribe link shim.
	 * Set only when a migrated row carries a legacy unsubscribe token — narrower than,
	 * and not a substitute for, HAS_MIGRATED_ROWS_OPTION below.
	 *
	 * @var string
	 */
	private const HAS_LEGACY_LINKS_OPTION = 'wc_bis_migration_has_legacy_links';

	/**
	 * Autoloaded option set the first time any row is migrated, inserted or adopted,
	 * regardless of whether it carries a legacy unsubscribe token. Answers "have any
	 * rows been migrated" for the double-send admin notice, which HAS_LEGACY_LINKS_OPTION
	 * cannot: a store whose legacy rows all lack `_hash_key`/`_hash_iv` never sets that flag.
	 *
	 * @var string
	 */
	private const HAS_MIGRATED_ROWS_OPTION = 'wc_bis_migration_has_migrated_rows';

	/**
	 * Failure reason recorded when a row throws while being mapped or written.
	 *
	 * @var string
	 */
	private const FAILURE_REASON_EXCEPTION = 'exception';

	/**
	 * Mines `woocommerce_bis_activity` for cancellation source and date, once per batch.
	 *
	 * @var CancellationSourceMiner
	 */
	private CancellationSourceMiner $cancellation_source_miner;

	/**
	 * Collects and logs outcome counts.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * Running count of migrated rows mapped to `sent`, which lose their eventual
	 * legacy re-fire under Core's terminal `sent` status. See Known losses.
	 *
	 * @var int
	 */
	private int $recurring_lost_count = 0;

	/**
	 * Running count of migrated or adopted rows with no `_hash_key`/`_hash_iv`, so no
	 * legacy unsubscribe token could be computed. Not a lost link: legacy mints these
	 * lazily, so a row without them never had one.
	 *
	 * @var int
	 */
	private int $rows_without_hash_count = 0;

	/**
	 * Constructor.
	 *
	 * @param Reporter                     $reporter                   Outcome collector.
	 * @param CancellationSourceMiner|null $cancellation_source_miner Cancellation source miner.
	 *                                                                 Defaults to a new instance.
	 */
	public function __construct( Reporter $reporter, ?CancellationSourceMiner $cancellation_source_miner = null ) {
		$this->reporter                  = $reporter;
		$this->cancellation_source_miner = $cancellation_source_miner ?? new CancellationSourceMiner();
	}

	/**
	 * Section slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Count candidate rows still outstanding. Same predicate as get_batch(), as COUNT(*).
	 *
	 * @return int
	 */
	public function count_remaining(): int {
		global $wpdb;

		$sql = 'SELECT COUNT(*) ' . $this->predicate_sql();

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is a fixed literal built by this class, never user input.
	}

	/**
	 * Fetch the next batch of candidate legacy ids after the given keyset cursor.
	 *
	 * Side-effect free: calling this twice with the same cursor returns the same ids.
	 * The cursor itself is owned and advanced by the caller, on successful migrate_batch().
	 *
	 * @param int $cursor Last legacy id handled in the current pass, or 0 to start a pass.
	 * @param int $size   Maximum number of ids to return.
	 * @return array List of legacy ids, ascending.
	 */
	public function get_batch( int $cursor, int $size ): array {
		global $wpdb;

		// predicate_sql() returns a fixed literal built by this class, never user input; only
		// the cursor and size are bound, so only that tail goes through $wpdb->prepare().
		$sql = 'SELECT n.id ' . $this->predicate_sql() . $wpdb->prepare( ' AND n.id > %d ORDER BY n.id ASC LIMIT %d', $cursor, $size );

		return array_map( 'intval', (array) $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- see above.
	}

	/**
	 * Migrate the given legacy ids.
	 *
	 * Fetches full rows and batched meta for the given ids, resolves natural-key
	 * adoption per row, and bulk-inserts everything that did not adopt. Per-row failures
	 * are caught, marked with `_wc_bis_migration_failed`, and reported rather than
	 * thrown; only a whole-batch write failure (from the writer) propagates, since that
	 * is the one condition a retry can fix.
	 *
	 * @param array           $ids    Legacy ids returned by get_batch().
	 * @param WriterInterface $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by Reporter::OUTCOME_* code.
	 */
	public function migrate_batch( array $ids, WriterInterface $writer ): array {
		$outcomes = array();

		if ( empty( $ids ) ) {
			return $outcomes;
		}

		$legacy_rows          = $this->fetch_legacy_rows( $ids );
		$legacy_meta          = $this->fetch_legacy_meta( $ids );
		$cancellation_sources = $this->cancellation_source_miner->mine( $legacy_rows );
		$date_mapper          = new DateMapper( time() );

		$insert_rows       = array();
		$insert_legacy_ids = array();

		foreach ( $legacy_rows as $legacy_row ) {
			$legacy_id = (int) $legacy_row['id'];

			try {
				$row_meta = $legacy_meta[ $legacy_id ] ?? array();
				$status   = StatusMapper::map( $legacy_row, $row_meta );

				// Must match the stored value byte-for-byte: MetaMapper hands posted_attributes
				// to the writer unserialized, and the writer is the sole maybe_serialize() owner,
				// so this local serialize (for comparison only) has to mirror that exactly.
				$posted_attributes_value = array_key_exists( 'posted_attributes', $row_meta )
					? maybe_serialize( $row_meta['posted_attributes'] )
					: '';

				$adoption_target_id = $this->find_adoption_target( $legacy_row, (string) $posted_attributes_value );

				if ( null !== $adoption_target_id ) {
					$this->adopt( $adoption_target_id, $legacy_row, $row_meta, $writer );
					$this->record_outcome( $outcomes, Reporter::OUTCOME_ADOPTED, $legacy_id );
					continue;
				}

				$cancellation = $cancellation_sources[ $legacy_id ] ?? null;

				$insert_rows[]       = array(
					'columns' => $this->build_columns( $legacy_row, $status, $date_mapper, $cancellation ),
					'meta'    => $this->build_meta( $legacy_id, $legacy_row, $row_meta, $writer ),
				);
				$insert_legacy_ids[] = $legacy_id;

				if ( NotificationStatus::SENT === $status ) {
					++$this->recurring_lost_count;
				}
			} catch ( \Throwable $e ) {
				$this->fail_row( $legacy_id, $writer );
				$this->record_outcome( $outcomes, Reporter::OUTCOME_FAILED, $legacy_id );
			}
		}

		if ( ! empty( $insert_rows ) ) {
			// A failure here is a whole-batch condition (DB error, lost connection); let it
			// propagate so the controller retries. Rows that already adopted or failed above
			// have already left the candidate set via their own markers, so a retry re-queries
			// rather than replaying them.
			$writer->insert_notifications( $insert_rows );
			$this->maybe_set_has_migrated_rows_option( $writer );

			foreach ( $insert_legacy_ids as $legacy_id ) {
				$this->record_outcome( $outcomes, Reporter::OUTCOME_MIGRATED, $legacy_id );
			}
		}

		$this->reporter->report_batch( self::SLUG, count( $ids ) );

		return $outcomes;
	}

	/**
	 * Rows with no `_hash_key`/`_hash_iv`, accumulated across every migrate_batch() call
	 * on this instance. See Known losses.
	 *
	 * @return int
	 */
	public function get_rows_without_hash_count(): int {
		return $this->rows_without_hash_count;
	}

	/**
	 * Rows mapped to `sent` that lose their eventual legacy re-fire, accumulated across
	 * every migrate_batch() call on this instance. See Known losses.
	 *
	 * @return int
	 */
	public function get_recurring_lost_count(): int {
		return $this->recurring_lost_count;
	}

	/**
	 * Count legacy rows excluded because they are unverified. Not a candidate population,
	 * so invisible to count_remaining(); counted separately for the Known losses report.
	 *
	 * @return int
	 */
	public function count_unverified_excluded(): int {
		global $wpdb;

		$sql = 'SELECT COUNT(*) ' . $this->base_sql() . " AND ( n.is_verified = 'no' OR ( av.meta_value IS NOT NULL AND av.meta_value = 'yes' ) )";

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is a fixed literal built by this class, never user input.
	}

	/**
	 * Count legacy rows skipped because their email is longer than Core's column allows.
	 *
	 * @return int
	 */
	public function count_email_too_long(): int {
		global $wpdb;

		$sql = 'SELECT COUNT(*) ' . $this->base_sql() . $this->verified_clause() . ' AND CHAR_LENGTH( n.user_email ) > 100';

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is a fixed literal built by this class, never user input.
	}

	/**
	 * Count legacy rows skipped because their email does not validate.
	 *
	 * @return int
	 */
	public function count_invalid_email(): int {
		global $wpdb;

		// The LIKE wildcards are part of this class's own literal, not a bound value.
		$sql = 'SELECT COUNT(*) ' . $this->base_sql() . $this->verified_clause()
			. " AND CHAR_LENGTH( n.user_email ) <= 100 AND ( n.user_email = '' OR n.user_email NOT LIKE '%_@_%' )";

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is a fixed literal built by this class, never user input.
	}

	/**
	 * Count legacy rows skipped because their product is missing, trashed, or not a product.
	 *
	 * @return int
	 */
	public function count_product_missing(): int {
		global $wpdb;

		$posts_table = $wpdb->prefix . 'posts';

		// $posts_table is $wpdb->prefix-based, never user input; every other fragment is a
		// fixed literal built by this class, including the LIKE wildcards.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = 'SELECT COUNT(*) ' . $this->base_sql() . $this->verified_clause()
			. " AND CHAR_LENGTH( n.user_email ) <= 100 AND n.user_email <> '' AND n.user_email LIKE '%_@_%'"
			. " AND NOT EXISTS ( SELECT 1 FROM {$posts_table} p"
			. " WHERE p.ID = n.product_id AND p.post_type IN ( 'product', 'product_variation' ) AND p.post_status <> 'trash' )";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is a fixed literal built by this class, never user input.
	}

	/**
	 * The candidate predicate: FROM/JOIN/WHERE, matching the migration plan's SQL
	 * verbatim in shape. Reused by count_remaining() and get_batch(), which append
	 * only a cursor bound and a LIMIT.
	 *
	 * Never passed through $wpdb->prepare(): it binds no values, and prepare() on a
	 * placeholder-free query is a `_doing_it_wrong()` notice. Callers that do bind
	 * values prepare only their own tail and concatenate it onto this fragment.
	 *
	 * @return string
	 */
	private function predicate_sql(): string {
		global $wpdb;

		$posts_table = $wpdb->prefix . 'posts';

		return $this->base_sql() . $this->verified_clause()
			. " AND CHAR_LENGTH( n.user_email ) <= 100 AND n.user_email <> '' AND n.user_email LIKE '%_@_%'"
			. " AND EXISTS ( SELECT 1 FROM {$posts_table} p" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $posts_table is $wpdb->prefix-based, never user input.
			. " WHERE p.ID = n.product_id AND p.post_type IN ( 'product', 'product_variation' ) AND p.post_status <> 'trash' )";
	}

	/**
	 * The shared FROM/JOIN/WHERE base every predicate variant starts from: the two
	 * anti-joins against the migrated set and the permanently-failed set. Callers append
	 * their own conditions. Binds no values; see predicate_sql() on why it is never
	 * passed through $wpdb->prepare().
	 *
	 * @return string
	 */
	private function base_sql(): string {
		global $wpdb;

		$notifications_table = $wpdb->prefix . 'woocommerce_bis_notifications';
		$core_meta_table     = $wpdb->prefix . 'wc_stock_notificationmeta';
		$legacy_meta_table   = $wpdb->prefix . 'woocommerce_bis_notificationsmeta';

		// Table names are $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return "
			FROM {$notifications_table} n
			LEFT JOIN {$core_meta_table} mm
			       ON mm.meta_key = '_wc_bis_legacy_id'
			      AND CAST( mm.meta_value AS UNSIGNED ) = n.id
			LEFT JOIN {$legacy_meta_table} av
			       ON av.bis_notifications_id = n.id AND av.meta_key = 'awaiting_verification'
			LEFT JOIN {$legacy_meta_table} fm
			       ON fm.bis_notifications_id = n.id AND fm.meta_key = '_wc_bis_migration_failed'
			WHERE mm.notification_id IS NULL
			  AND fm.bis_notifications_id IS NULL
		";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * The verified test shared by every predicate variant except count_unverified_excluded().
	 *
	 * @return string
	 */
	private function verified_clause(): string {
		return " AND n.is_verified <> 'no' AND ( av.meta_value IS NULL OR av.meta_value <> 'yes' )";
	}

	/**
	 * Fetch full legacy rows for a batch of ids.
	 *
	 * @param int[] $ids Legacy ids.
	 * @return array<int,array<string,mixed>> Rows, keyed sequentially, each an associative array.
	 */
	private function fetch_legacy_rows( array $ids ): array {
		global $wpdb;

		if ( empty( $ids ) ) {
			return array();
		}

		$table        = $wpdb->prefix . 'woocommerce_bis_notifications';
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// $table is $wpdb->prefix-based, never user input; $placeholders is a locally built
		// %d placeholder list, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE id IN ( $placeholders )", $ids );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Fetch legacy meta for a batch of ids in one query, indexed by id then meta key.
	 *
	 * Only the keys the migration reads are fetched: posted_attributes, _customer_locale,
	 * _customer_location_data, _hash_key, _hash_iv.
	 *
	 * @param int[] $ids Legacy ids.
	 * @return array<int,array<string,mixed>>
	 */
	private function fetch_legacy_meta( array $ids ): array {
		global $wpdb;

		if ( empty( $ids ) ) {
			return array();
		}

		$table            = $wpdb->prefix . 'woocommerce_bis_notificationsmeta';
		$id_placeholders  = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$key_placeholders = implode( ', ', array_fill( 0, count( self::LEGACY_META_KEYS ), '%s' ) );

		// $table is $wpdb->prefix-based, never user input; $id_placeholders/$key_placeholders
		// are locally built %d/%s placeholder lists, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT bis_notifications_id, meta_key, meta_value FROM {$table}
			WHERE bis_notifications_id IN ( $id_placeholders ) AND meta_key IN ( $key_placeholders )",
			array_merge( $ids, self::LEGACY_META_KEYS )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$rows = (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		$indexed = array();

		foreach ( $rows as $row ) {
			$legacy_id                                 = (int) $row['bis_notifications_id'];
			$indexed[ $legacy_id ][ $row['meta_key'] ] = maybe_unserialize( $row['meta_value'] );
		}

		return $indexed;
	}

	/**
	 * Find an existing Core notification carrying this legacy row's natural key, if any.
	 *
	 * Natural key: product_id, plus user_id when non-zero and, for a guest row, a zero
	 * user_id with a matching user_email (lowercased, trimmed), plus posted_attributes in
	 * maybe_serialize() form. A guest row and a registered row never adopt each other, in
	 * either direction. Restricted to
	 * `active` and `pending` targets, ordered active before pending then by ascending id,
	 * so the same legacy row adopts the same target on every run.
	 *
	 * @param array<string,mixed> $legacy_row               Row from `woocommerce_bis_notifications`.
	 * @param string              $posted_attributes_value Normalised posted_attributes value for this row.
	 * @return int|null Target notification id, or null when no target matches.
	 */
	private function find_adoption_target( array $legacy_row, string $posted_attributes_value ): ?int {
		global $wpdb;

		$table      = $wpdb->prefix . 'wc_stock_notifications';
		$meta_table = $wpdb->prefix . 'wc_stock_notificationmeta';
		$user_id    = (int) ( $legacy_row['user_id'] ?? 0 );

		$conditions = array( 'n.product_id = %d' );
		$params     = array( (int) ( $legacy_row['product_id'] ?? 0 ) );

		if ( $user_id > 0 ) {
			$conditions[] = 'n.user_id = %d';
			$params[]     = $user_id;
		} else {
			// A guest legacy row only ever adopts a guest Core row. Matching a registered
			// Core row on the address alone would hand one person's subscription to a row
			// that belongs to their account, which is a different subscription.
			$conditions[] = 'n.user_id = 0';
			$conditions[] = 'LOWER( TRIM( n.user_email ) ) = %s';
			$params[]     = strtolower( trim( (string) ( $legacy_row['user_email'] ?? '' ) ) );
		}

		$conditions[] = "COALESCE( pm.meta_value, '' ) = %s";
		$params[]     = $posted_attributes_value;

		// $table/$meta_table are $wpdb->prefix-based, never user input; $conditions is a
		// locally built list of %d/%s placeholder fragments, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT n.id FROM {$table} n
			LEFT JOIN {$meta_table} pm ON pm.notification_id = n.id AND pm.meta_key = 'posted_attributes'
			WHERE n.status IN ( 'active', 'pending' )
			  AND " . implode( ' AND ', $conditions ) . '
			ORDER BY FIELD( n.status, \'active\', \'pending\' ), n.id ASC
			LIMIT 1',
			$params
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$target_id = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		return null === $target_id ? null : (int) $target_id;
	}

	/**
	 * Adopt an existing Core notification: write only the legacy id marker, the adoption
	 * marker that records this was adoption rather than an insert, and, when both hash
	 * secrets are present, the legacy unsubscribe token. Never reconciles status, dates,
	 * or any other meta onto the target — the Core row is the merchant's.
	 *
	 * The adoption marker is what lets `rollback` remove only this legacy id's markers
	 * from the target row instead of deleting a row the migration did not create.
	 *
	 * @param int                 $target_id  Core notification id being adopted.
	 * @param array<string,mixed> $legacy_row Row from `woocommerce_bis_notifications`.
	 * @param array<string,mixed> $row_meta   This row's legacy meta bag.
	 * @param WriterInterface     $writer     Writer to route the marker writes through.
	 * @return void
	 */
	private function adopt( int $target_id, array $legacy_row, array $row_meta, WriterInterface $writer ): void {
		$legacy_id = (int) $legacy_row['id'];
		$meta      = array(
			array( self::LEGACY_ID_META_KEY, $legacy_id ),
			array( self::ADOPTED_MARKER_META_KEY, $legacy_id ),
		);
		$token     = $this->compute_token( $legacy_id, $legacy_row, $row_meta );

		if ( null !== $token ) {
			$meta[] = array( self::LEGACY_UNSUB_HASH_META_KEY, LegacyHash::to_meta_value( $legacy_id, $token ) );
		} else {
			++$this->rows_without_hash_count;
		}

		$writer->insert_notification_meta( $target_id, $meta );
		$this->maybe_set_has_migrated_rows_option( $writer );

		if ( null !== $token ) {
			$this->maybe_set_has_legacy_links_option( $writer );
		}
	}

	/**
	 * Build the Core column values for a new notification row.
	 *
	 * @param array<string,mixed>                 $legacy_row   Row from `woocommerce_bis_notifications`.
	 * @param string                              $status       Status already resolved by StatusMapper.
	 * @param DateMapper                          $date_mapper  Date mapper shared by the whole batch.
	 * @param array{source:string,date:?int}|null $cancellation Cancellation source/date mined for this row.
	 * @return array<string,mixed>
	 */
	private function build_columns( array $legacy_row, string $status, DateMapper $date_mapper, ?array $cancellation ): array {
		$latest_activity_date = null !== $cancellation ? $cancellation['date'] : null;
		$cancellation_source  = null;

		if ( NotificationStatus::CANCELLED === $status ) {
			$cancellation_source = null !== $cancellation ? $cancellation['source'] : NotificationCancellationSource::SYSTEM;
		}

		return array(
			'product_id'            => (int) $legacy_row['product_id'],
			'user_id'               => (int) $legacy_row['user_id'],
			'user_email'            => strtolower( trim( (string) $legacy_row['user_email'] ) ),
			'status'                => $status,
			'date_created_gmt'      => $date_mapper->date_created_gmt( $legacy_row ),
			'date_modified_gmt'     => $date_mapper->date_modified_gmt(),
			'date_confirmed_gmt'    => $date_mapper->date_confirmed_gmt( $legacy_row ),
			'date_last_attempt_gmt' => $date_mapper->date_last_attempt_gmt( $legacy_row ),
			'date_notified_gmt'     => $date_mapper->date_notified_gmt( $legacy_row, $status ),
			'date_cancelled_gmt'    => $date_mapper->date_cancelled_gmt( $legacy_row, $status, $latest_activity_date ),
			'cancellation_source'   => $cancellation_source,
		);
	}

	/**
	 * Build the Core meta rows for a new notification row: the mapped legacy meta bag,
	 * the legacy id marker, and the legacy unsubscribe token when both hash secrets exist.
	 *
	 * @param int                 $legacy_id  Legacy notification id.
	 * @param array<string,mixed> $legacy_row Row from `woocommerce_bis_notifications`.
	 * @param array<string,mixed> $row_meta   This row's legacy meta bag.
	 * @param WriterInterface     $writer     Writer, used only to set the legacy-links option.
	 * @return array<int,array{0:string,1:mixed}>
	 */
	private function build_meta( int $legacy_id, array $legacy_row, array $row_meta, WriterInterface $writer ): array {
		$meta   = MetaMapper::map( $row_meta );
		$meta[] = array( self::LEGACY_ID_META_KEY, $legacy_id );

		$token = $this->compute_token( $legacy_id, $legacy_row, $row_meta );

		if ( null !== $token ) {
			$meta[] = array( self::LEGACY_UNSUB_HASH_META_KEY, LegacyHash::to_meta_value( $legacy_id, $token ) );
			$this->maybe_set_has_legacy_links_option( $writer );
		} else {
			++$this->rows_without_hash_count;
		}

		return $meta;
	}

	/**
	 * Reproduce the legacy unsubscribe token for one row, when both hash secrets exist.
	 *
	 * @param int                 $legacy_id  Legacy notification id.
	 * @param array<string,mixed> $legacy_row Row from `woocommerce_bis_notifications`.
	 * @param array<string,mixed> $row_meta   This row's legacy meta bag.
	 * @return string|null
	 */
	private function compute_token( int $legacy_id, array $legacy_row, array $row_meta ): ?string {
		$hash_key = (string) ( $row_meta['_hash_key'] ?? '' );
		$hash_iv  = (string) ( $row_meta['_hash_iv'] ?? '' );

		return LegacyHash::compute(
			$legacy_id,
			(int) $legacy_row['product_id'],
			(int) $legacy_row['create_date'],
			$hash_key,
			$hash_iv
		);
	}

	/**
	 * Set the autoloaded `wc_bis_migration_has_legacy_links` flag the first time a row
	 * carrying a legacy unsubscribe token is written. Reads the option first so an
	 * already-set flag costs nothing beyond the cached autoloaded read.
	 *
	 * @param WriterInterface $writer Writer to route the option write through.
	 * @return void
	 */
	private function maybe_set_has_legacy_links_option( WriterInterface $writer ): void {
		if ( 'yes' !== get_option( self::HAS_LEGACY_LINKS_OPTION ) ) {
			$writer->write_option( self::HAS_LEGACY_LINKS_OPTION, 'yes' );
		}
	}

	/**
	 * Set the autoloaded `wc_bis_migration_has_migrated_rows` flag the first time any row
	 * is migrated, inserted or adopted. Reads the option first so an already-set flag
	 * costs nothing beyond the cached autoloaded read.
	 *
	 * @param WriterInterface $writer Writer to route the option write through.
	 * @return void
	 */
	private function maybe_set_has_migrated_rows_option( WriterInterface $writer ): void {
		if ( 'yes' !== get_option( self::HAS_MIGRATED_ROWS_OPTION ) ) {
			$writer->write_option( self::HAS_MIGRATED_ROWS_OPTION, 'yes' );
		}
	}

	/**
	 * Mark a legacy row as permanently failed and leave it out of the candidate set.
	 *
	 * @param int             $legacy_id Legacy notification id.
	 * @param WriterInterface $writer    Writer to route the marker write through.
	 * @return void
	 */
	private function fail_row( int $legacy_id, WriterInterface $writer ): void {
		$writer->write_legacy_meta(
			$legacy_id,
			self::FAILED_META_KEY,
			array(
				'reason' => self::FAILURE_REASON_EXCEPTION,
				'at'     => time(),
			)
		);
	}

	/**
	 * Record one outcome for one row against both the returned outcome-count array and
	 * the shared Reporter.
	 *
	 * @param array  $outcomes  Outcome counts accumulated so far, by reference.
	 * @param string $outcome   One of the Reporter::OUTCOME_* constants.
	 * @param int    $legacy_id Legacy row identifier.
	 * @return void
	 */
	private function record_outcome( array &$outcomes, string $outcome, int $legacy_id ): void {
		$outcomes[ $outcome ] = ( $outcomes[ $outcome ] ?? 0 ) + 1;
		$this->reporter->record( self::SLUG, $outcome, $legacy_id );
	}
}
