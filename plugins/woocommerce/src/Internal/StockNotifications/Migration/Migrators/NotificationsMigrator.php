<?php
/**
 * NotificationsMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\CancellationSourceMiner;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\DateMapper;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\LegacyHash;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\StatusMapper;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates `woocommerce_bis_notifications` rows to Core `wc_stock_notifications` rows.
 *
 * `get_batch()` is a plain keyset scan over the legacy table: it walks ids in order and
 * knows nothing about candidacy, so it stays constant-cost however much of the table has
 * already been migrated. Candidacy is decided in `migrate_batch()` instead, in PHP, from
 * a handful of batched indexed lookups. Every row the scan serves therefore leaves the
 * batch either migrated, adopted, failed, or with a recorded skip outcome - a row that
 * records nothing would be dropped silently while the run reported success.
 *
 * `get_batch()` returns identifiers only and is side-effect free; `migrate_batch()`
 * fetches the full rows for those identifiers. Legacy meta, cancellation sources and
 * adoption targets are all resolved once per batch, never per row.
 */
class NotificationsMigrator implements MigratorInterface {

	/**
	 * Section slug.
	 *
	 * @var string
	 */
	private const SLUG = 'notifications';

	/**
	 * Legacy meta keys read for a batch in one query: everything the migration carries into
	 * Core meta, plus the two secrets the unsubscribe token is computed from.
	 *
	 * @var string[]
	 */
	public const LEGACY_META_KEYS = array(
		'posted_attributes',
		'_customer_locale',
		'_customer_location_data',
		'_hash_key',
		'_hash_iv',
	);

	/**
	 * Legacy meta keys read only to reproduce the verification token for a pending row.
	 *
	 * Deliberately kept out of LEGACY_META_KEYS: nothing derives a row's state from these,
	 * and they hold the legacy verification secrets, so they are read, reduced to a single
	 * digest and discarded rather than carried into Core meta.
	 *
	 * @var string[]
	 */
	private const VERIFICATION_META_KEYS = array(
		'_verification_code',
		'_verification_key',
		'_verification_iv',
		'_verification_created_at',
	);

	/**
	 * Prefix of the migration marker recording a successful migration onto a Core
	 * notification. Inserted, never updated; a Core row can carry several. The legacy id
	 * completes the key, so `fetch_migrated_legacy_ids()` selects on the indexed `meta_key`
	 * column instead of the unindexed `meta_value` one.
	 *
	 * @var string
	 */
	private const LEGACY_ID_META_KEY_PREFIX = Constants::LEGACY_ID_META_KEY_PREFIX;

	/**
	 * Legacy meta key recording a permanent per-row failure. The migration's only write
	 * into the legacy schema.
	 *
	 * @var string
	 */
	private const FAILED_META_KEY = Constants::LEGACY_FAILED_META_KEY;

	/**
	 * Autoloaded option guarding registration of the legacy link shim. Set only when a
	 * migrated row carries a legacy token, of either kind — narrower than, and not a
	 * substitute for, HAS_MIGRATED_ROWS_OPTION below.
	 *
	 * @var string
	 */
	private const HAS_LEGACY_LINKS_OPTION = Constants::HAS_LEGACY_LINKS_OPTION;

	/**
	 * Autoloaded option set the first time any row is migrated, inserted or adopted,
	 * regardless of whether it carries a legacy unsubscribe token. Answers "have any
	 * rows been migrated" for the double-send admin notice, which HAS_LEGACY_LINKS_OPTION
	 * cannot: a store whose legacy rows all lack `_hash_key`/`_hash_iv` never sets that flag.
	 *
	 * @var string
	 */
	private const HAS_MIGRATED_ROWS_OPTION = Constants::HAS_MIGRATED_ROWS_OPTION;

	/**
	 * Failure reason recorded when a row throws while being mapped or written.
	 *
	 * @var string
	 */
	private const FAILURE_REASON_EXCEPTION = 'exception';

	/**
	 * Longest address Core's `user_email` column holds. A legacy row above it is a loss:
	 * the address cannot be stored, so the row cannot be migrated.
	 *
	 * @var int
	 */
	private const MAX_EMAIL_LENGTH = 100;

	/**
	 * Core statuses a legacy row may adopt, in the order they are preferred.
	 *
	 * @var string[]
	 */
	private const ADOPTABLE_STATUSES = array( NotificationStatus::ACTIVE, NotificationStatus::PENDING );

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
	 * Running count of migrated or adopted rows with no `_hash_key`/`_hash_iv`, so no
	 * legacy unsubscribe token could be computed. Not a lost link: legacy mints these
	 * lazily, so a row without them never had one.
	 *
	 * @var int
	 */
	private int $rows_without_hash_count = 0;

	/**
	 * Whether the batch being assembled carries a legacy token of either kind, and so should
	 * set HAS_LEGACY_LINKS_OPTION. Recorded while the rows are built and acted on only once
	 * their insert has committed: the option write does not join that transaction, so setting
	 * it any earlier survives a rolled-back batch and registers the link shim with no
	 * migrated row behind it to resolve.
	 *
	 * @var bool
	 */
	private bool $batch_carries_legacy_links = false;

	/**
	 * Verification link expiry threshold in seconds, resolved once on first use.
	 *
	 * @var int|null
	 */
	private ?int $verification_expiry_threshold = null;

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
	 * Count the legacy rows this section has still to visit, above the given cursor.
	 *
	 * "Left to visit", not "left to migrate": the scan does not know which of those rows
	 * are candidates, so this counts every row above the cursor, including ones a batch
	 * will skip as already migrated or as a recorded loss. Display only.
	 *
	 * @param int $cursor Last legacy id handled, or 0 to count the whole table.
	 * @return int
	 */
	public function count_remaining( int $cursor = 0 ): int {
		global $wpdb;

		$table = Constants::legacy_notifications();

		// $table is $wpdb->prefix-based, never user input; the cursor is bound below.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id > %d", $cursor );

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Fetch the next batch of legacy ids after the given keyset cursor.
	 *
	 * A plain walk of the primary key: no predicate, no joins, so its cost does not grow
	 * as the migrated set does. Side-effect free - calling this twice with the same cursor
	 * returns the same ids. The cursor itself is owned and advanced by the caller, on
	 * successful migrate_batch().
	 *
	 * @param int $cursor Last legacy id handled in the current pass, or 0 to start a pass.
	 * @param int $size   Maximum number of ids to return.
	 * @return array List of legacy ids, ascending.
	 */
	public function get_batch( int $cursor, int $size ): array {
		global $wpdb;

		$table = Constants::legacy_notifications();

		// $table is $wpdb->prefix-based, never user input; the cursor and size are bound below.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$table} WHERE id > %d ORDER BY id ASC LIMIT %d", $cursor, $size );

		return array_map( 'intval', (array) $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Migrate the given legacy ids.
	 *
	 * Fetches full rows for the given ids, drops the ones that are not candidates - and
	 * records an outcome for every drop that is a loss - then fetches batched meta and
	 * adoption targets for the survivors and bulk-inserts everything that did not adopt.
	 * Per-row failures are caught, marked with `_wc_bis_migration_failed`, and reported
	 * rather than thrown; only a whole-batch write failure (from the writer) propagates,
	 * since that is the one condition a retry can fix.
	 *
	 * @param array  $ids    Legacy ids returned by get_batch().
	 * @param Writer $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by Reporter::OUTCOME_* code.
	 */
	public function migrate_batch( array $ids, Writer $writer ): array {
		$outcomes = array();

		if ( empty( $ids ) ) {
			return $outcomes;
		}

		$ids         = array_values( array_unique( array_map( 'intval', $ids ) ) );
		$legacy_rows = $this->select_candidates( $this->fetch_legacy_rows( $ids ), $outcomes );

		if ( empty( $legacy_rows ) ) {
			$this->reporter->report_batch( self::SLUG, count( $ids ) );

			return $outcomes;
		}

		$candidate_ids        = array_map( 'intval', array_column( $legacy_rows, 'id' ) );
		$legacy_meta          = $this->fetch_legacy_meta( $candidate_ids );
		$cancellation_sources = $this->cancellation_source_miner->mine( $legacy_rows );
		$date_mapper          = new DateMapper( time() );

		// Must match the stored value byte-for-byte: map_legacy_meta() hands posted_attributes to
		// the writer unserialized, and the writer is the sole maybe_serialize() owner, so
		// this local serialize (for comparison only) has to mirror that exactly.
		$posted_attributes = array();

		foreach ( $candidate_ids as $candidate_id ) {
			$row_meta = $legacy_meta[ $candidate_id ] ?? array();

			$posted_attributes[ $candidate_id ] = array_key_exists( 'posted_attributes', $row_meta )
				? (string) maybe_serialize( $row_meta['posted_attributes'] )
				: '';
		}

		$adoption_targets = $this->find_adoption_targets( $legacy_rows, $posted_attributes );

		$insert_rows       = array();
		$insert_legacy_ids = array();

		$this->batch_carries_legacy_links = false;

		foreach ( $legacy_rows as $legacy_row ) {
			$legacy_id = (int) $legacy_row['id'];

			try {
				$row_meta     = $legacy_meta[ $legacy_id ] ?? array();
				$cancellation = $cancellation_sources[ $legacy_id ] ?? null;
				$status       = StatusMapper::map( $legacy_row, $cancellation );

				$adoption_target = $adoption_targets[ $legacy_id ] ?? null;

				if ( null !== $adoption_target ) {
					$this->adopt( (int) $adoption_target['id'], $legacy_row, $row_meta, $status, $writer );

					// Adoption writes markers only, never status, so an active legacy row that
					// lands on a pending Core row leaves the subscriber pending. The data is the
					// merchant's and stays as it is; the outcome says so rather than reporting a
					// plain success, so a downgraded subscriber is a number someone can act on.
					$this->record_outcome(
						$outcomes,
						NotificationStatus::ACTIVE === $status && NotificationStatus::PENDING === (string) $adoption_target['status']
							? Reporter::OUTCOME_ADOPTED_DOWNGRADED
							: Reporter::OUTCOME_ADOPTED,
						$legacy_id
					);
					continue;
				}

				$insert_rows[]       = array(
					'columns' => $this->build_columns( $legacy_row, $status, $date_mapper, $cancellation ),
					'meta'    => $this->build_meta( $legacy_id, $legacy_row, $row_meta, $status ),
				);
				$insert_legacy_ids[] = $legacy_id;
			} catch ( \Throwable $e ) {
				$this->fail_row( $legacy_id, $writer );
				$this->reporter->report_row_exception( self::SLUG, $legacy_id, $e );
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

			// Only now the inserts have committed. Setting it while the rows were still being
			// assembled would survive a rolled-back batch, leaving the shim intercepting every
			// legacy link with no migrated row behind it to resolve.
			if ( $this->batch_carries_legacy_links ) {
				$this->maybe_set_has_legacy_links_option( $writer );
			}

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
	 * Drop every row in a fetched batch that is not a candidate, recording an outcome for
	 * each drop that costs a customer their notification.
	 *
	 * Four exclusions, each one batched indexed lookup rather than a join in the scan:
	 * already migrated and already failed leave quietly, since they are settled rows the
	 * scan simply walked past again; an over-long address, an address that cannot be an
	 * address, and a product that is gone are losses, and each records its outcome.
	 *
	 * @param array<int,array<string,mixed>> $rows     Full legacy rows for the batch.
	 * @param array                          $outcomes Outcome counts for this batch, by reference.
	 * @return array<int,array<string,mixed>> The rows that are still candidates.
	 */
	private function select_candidates( array $rows, array &$outcomes ): array {
		if ( empty( $rows ) ) {
			return array();
		}

		$ids      = array_map( 'intval', array_column( $rows, 'id' ) );
		$migrated = $this->fetch_migrated_legacy_ids( $ids );
		$failed   = $this->fetch_failed_legacy_ids( $ids );
		$products = $this->fetch_existing_product_ids( array_map( 'intval', array_column( $rows, 'product_id' ) ) );

		$candidates = array();

		foreach ( $rows as $row ) {
			$legacy_id = (int) $row['id'];

			if ( isset( $migrated[ $legacy_id ] ) || isset( $failed[ $legacy_id ] ) ) {
				continue;
			}

			$email = (string) ( $row['user_email'] ?? '' );

			if ( mb_strlen( $email ) > self::MAX_EMAIL_LENGTH ) {
				$this->record_outcome( $outcomes, Reporter::OUTCOME_EMAIL_TOO_LONG, $legacy_id );
				continue;
			}

			// The legacy `LIKE '%_@_%'` this replaces: an `@` with at least one character on
			// either side. Deliberately not is_email(), which would newly reject addresses
			// earlier runs of this migration admitted.
			if ( ! preg_match( '/.@./s', $email ) ) {
				$this->record_outcome( $outcomes, Reporter::OUTCOME_INVALID_EMAIL, $legacy_id );
				continue;
			}

			if ( ! isset( $products[ (int) ( $row['product_id'] ?? 0 ) ] ) ) {
				$this->record_outcome( $outcomes, Reporter::OUTCOME_PRODUCT_MISSING, $legacy_id );
				continue;
			}

			$candidates[] = $row;
		}

		return $candidates;
	}

	/**
	 * The subset of the given legacy ids that already carry a Core migration marker.
	 *
	 * @param int[] $ids Legacy ids.
	 * @return array<int,true> Legacy ids as keys.
	 */
	private function fetch_migrated_legacy_ids( array $ids ): array {
		global $wpdb;

		$table        = Constants::core_meta();
		$keys         = array_map( array( Constants::class, 'legacy_id_meta_key' ), array_map( 'intval', $ids ) );
		$placeholders = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );

		// $table is $wpdb->prefix-based, never user input; $placeholders is a locally built
		// %s placeholder list, bound via $wpdb->prepare() below. The markers are matched on
		// meta_key, the indexed column: meta_value is an unindexed longtext, so selecting the
		// legacy id there would scan every migrated row on every batch.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT meta_key FROM {$table} WHERE meta_key IN ( $placeholders )",
			$keys
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$found = (array) $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		$migrated = array();

		foreach ( $found as $meta_key ) {
			$migrated[ (int) substr( (string) $meta_key, strlen( self::LEGACY_ID_META_KEY_PREFIX ) ) ] = true;
		}

		return $migrated;
	}

	/**
	 * The subset of the given legacy ids marked as permanently failed.
	 *
	 * @param int[] $ids Legacy ids.
	 * @return array<int,true> Legacy ids as keys.
	 */
	private function fetch_failed_legacy_ids( array $ids ): array {
		global $wpdb;

		$table        = Constants::legacy_meta();
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// $table is $wpdb->prefix-based, never user input; $placeholders is a locally built
		// %d placeholder list, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT bis_notifications_id FROM {$table}
			WHERE bis_notifications_id IN ( $placeholders ) AND meta_key = %s",
			array_merge( $ids, array( self::FAILED_META_KEY ) )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$values = (array) $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		return array_fill_keys( array_map( 'intval', $values ), true );
	}

	/**
	 * The subset of the given product ids that still exist as a product or variation and
	 * are not trashed.
	 *
	 * Read straight from `posts` rather than through wc_get_product(), which would be one
	 * query and one hydrated object per row.
	 *
	 * @param int[] $product_ids Product ids referenced by the batch.
	 * @return array<int,true> Product ids as keys.
	 */
	private function fetch_existing_product_ids( array $product_ids ): array {
		global $wpdb;

		$product_ids = array_values( array_unique( array_filter( $product_ids ) ) );

		if ( empty( $product_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );

		// $wpdb->posts is a $wpdb->prefix-based table name, never user input; $placeholders is
		// a locally built %d placeholder list, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE ID IN ( $placeholders )
			  AND post_type IN ( 'product', 'product_variation' )
			  AND post_status <> 'trash'",
			$product_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$values = (array) $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		return array_fill_keys( array_map( 'intval', $values ), true );
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

		$table        = Constants::legacy_notifications();
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
	 * Only the keys the migration reads are fetched: LEGACY_META_KEYS, plus the
	 * VERIFICATION_META_KEYS a pending row needs to reproduce its verification token.
	 *
	 * @param int[] $ids Legacy ids.
	 * @return array<int,array<string,mixed>>
	 */
	private function fetch_legacy_meta( array $ids ): array {
		global $wpdb;

		if ( empty( $ids ) ) {
			return array();
		}

		$table            = Constants::legacy_meta();
		$meta_keys        = array_merge( self::LEGACY_META_KEYS, self::VERIFICATION_META_KEYS );
		$id_placeholders  = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$key_placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );

		// $table is $wpdb->prefix-based, never user input; $id_placeholders/$key_placeholders
		// are locally built %d/%s placeholder lists, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT bis_notifications_id, meta_key, meta_value FROM {$table}
			WHERE bis_notifications_id IN ( $id_placeholders ) AND meta_key IN ( $key_placeholders )",
			array_merge( $ids, $meta_keys )
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
	 * Resolve the Core notification each legacy row in the batch adopts, if any.
	 *
	 * Natural key: product_id, plus user_id when non-zero and, for a guest row, a zero
	 * user_id with a matching user_email (lowercased, trimmed), plus posted_attributes in
	 * maybe_serialize() form. A guest row and a registered row never adopt each other, in
	 * either direction.
	 *
	 * Resolved for the whole batch in two indexed lookups - one per branch, each a row
	 * constructor list against `user_lookup` / `email_lookup` - plus one lookup for the
	 * candidates' posted_attributes. The email is bound as a bare column comparison: any
	 * function on `user_email` loses the index, and `=` already matches on case and on a
	 * trailing space under the column's collation.
	 *
	 * Candidates are ranked in PHP, active before pending then by ascending id, so a
	 * legacy row adopts the same target on every run.
	 *
	 * The posted_attributes comparison is byte-exact between two independently produced
	 * `maybe_serialize()` strings, deliberately: `SignupService::is_already_signed_up()`
	 * dedupes Core's own signups by exactly that rule, so matching any more loosely here
	 * would adopt a row Core's signup path treats as a separate subscription. It rests on an
	 * assumption this repository cannot check — the legacy extension is not in it — that the
	 * legacy and Core serializations of the same attributes agree byte for byte. If they ever
	 * disagree the mismatch is systematic rather than occasional, since the two build the
	 * array through different code paths, and the symptom is a duplicate Core row per
	 * variation subscription. Loosen both sides together or neither.
	 *
	 * The matched candidate is returned whole rather than as a bare id, because its status is
	 * what tells `migrate_batch()` whether adopting it leaves the subscriber less live than
	 * the legacy row was.
	 *
	 * @param array<int,array<string,mixed>> $legacy_rows       Candidate rows from `woocommerce_bis_notifications`.
	 * @param array<int,string>              $posted_attributes Normalised posted_attributes value, by legacy id.
	 * @return array<int,array{id:int,status:string}> Target notification, by legacy id. Rows that adopt nothing are absent.
	 */
	private function find_adoption_targets( array $legacy_rows, array $posted_attributes ): array {
		$registered_pairs = array();
		$guest_pairs      = array();
		$keys             = array();

		foreach ( $legacy_rows as $legacy_row ) {
			$legacy_id  = (int) $legacy_row['id'];
			$product_id = (int) ( $legacy_row['product_id'] ?? 0 );
			$user_id    = (int) ( $legacy_row['user_id'] ?? 0 );

			if ( $user_id > 0 ) {
				$key                      = $this->registered_key( $product_id, $user_id );
				$registered_pairs[ $key ] = array( $product_id, $user_id );
			} else {
				$email               = strtolower( trim( (string) ( $legacy_row['user_email'] ?? '' ) ) );
				$key                 = $this->guest_key( $product_id, $email );
				$guest_pairs[ $key ] = array( $product_id, $email );
			}

			$keys[ $legacy_id ] = $key;
		}

		$candidates = $this->fetch_adoption_candidates( $registered_pairs, $guest_pairs );

		if ( empty( $candidates ) ) {
			return array();
		}

		$candidate_ids = array();

		foreach ( $candidates as $group ) {
			foreach ( $group as $candidate ) {
				$candidate_ids[] = $candidate['id'];
			}
		}

		$candidate_attributes = $this->fetch_candidate_posted_attributes( $candidate_ids );

		$targets = array();

		foreach ( $legacy_rows as $legacy_row ) {
			$legacy_id = (int) $legacy_row['id'];
			$wanted    = $posted_attributes[ $legacy_id ] ?? '';

			foreach ( $candidates[ $keys[ $legacy_id ] ] ?? array() as $candidate ) {
				$stored = $candidate_attributes[ $candidate['id'] ] ?? array( '' );

				if ( in_array( $wanted, $stored, true ) ) {
					$targets[ $legacy_id ] = $candidate;
					break;
				}
			}
		}

		return $targets;
	}

	/**
	 * Fetch every adoptable Core notification matching one of the batch's natural keys,
	 * grouped by that key and ranked active before pending, then by ascending id.
	 *
	 * @param array<string,array{0:int,1:int}>    $registered_pairs Product/user pairs, keyed by natural key.
	 * @param array<string,array{0:int,1:string}> $guest_pairs      Product/email pairs, keyed by natural key.
	 * @return array<string,array<int,array{id:int,status:string}>> Candidates by natural key.
	 */
	private function fetch_adoption_candidates( array $registered_pairs, array $guest_pairs ): array {
		$grouped = array();

		foreach ( $this->query_registered_candidates( $registered_pairs ) as $row ) {
			$key               = $this->registered_key( (int) $row['product_id'], (int) $row['user_id'] );
			$grouped[ $key ][] = array(
				'id'     => (int) $row['id'],
				'status' => (string) $row['status'],
			);
		}

		foreach ( $this->query_guest_candidates( $guest_pairs ) as $row ) {
			$key               = $this->guest_key( (int) $row['product_id'], strtolower( trim( (string) $row['user_email'] ) ) );
			$grouped[ $key ][] = array(
				'id'     => (int) $row['id'],
				'status' => (string) $row['status'],
			);
		}

		foreach ( $grouped as $key => $candidates ) {
			usort(
				$candidates,
				static function ( array $left, array $right ): int {
					$left_rank  = array_search( $left['status'], self::ADOPTABLE_STATUSES, true );
					$right_rank = array_search( $right['status'], self::ADOPTABLE_STATUSES, true );

					return $left_rank === $right_rank ? $left['id'] <=> $right['id'] : $left_rank <=> $right_rank;
				}
			);

			$grouped[ $key ] = $candidates;
		}

		return $grouped;
	}

	/**
	 * Query the adoptable Core rows for the batch's registered natural keys.
	 *
	 * @param array<string,array{0:int,1:int}> $pairs Product/user pairs, keyed by natural key.
	 * @return array<int,array<string,mixed>>
	 */
	private function query_registered_candidates( array $pairs ): array {
		global $wpdb;

		if ( empty( $pairs ) ) {
			return array();
		}

		$table        = Constants::core_notifications();
		$placeholders = implode( ', ', array_fill( 0, count( $pairs ), '( %d, %d )' ) );
		$params       = self::ADOPTABLE_STATUSES;

		foreach ( $pairs as $pair ) {
			$params[] = $pair[0];
			$params[] = $pair[1];
		}

		// $table is $wpdb->prefix-based, never user input; $placeholders is a locally built
		// row-constructor placeholder list, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$sql = $wpdb->prepare(
			"SELECT id, status, product_id, user_id FROM {$table}
			WHERE status IN ( %s, %s ) AND ( product_id, user_id ) IN ( $placeholders )",
			$params
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Query the adoptable Core rows for the batch's guest natural keys.
	 *
	 * A guest legacy row only ever adopts a guest Core row. Matching a registered Core row
	 * on the address alone would hand one person's subscription to a row that belongs to
	 * their account, which is a different subscription.
	 *
	 * @param array<string,array{0:int,1:string}> $pairs Product/email pairs, keyed by natural key.
	 * @return array<int,array<string,mixed>>
	 */
	private function query_guest_candidates( array $pairs ): array {
		global $wpdb;

		if ( empty( $pairs ) ) {
			return array();
		}

		$table        = Constants::core_notifications();
		$placeholders = implode( ', ', array_fill( 0, count( $pairs ), '( %d, %s )' ) );
		$params       = self::ADOPTABLE_STATUSES;

		foreach ( $pairs as $pair ) {
			$params[] = $pair[0];
			$params[] = $pair[1];
		}

		// $table is $wpdb->prefix-based, never user input; $placeholders is a locally built
		// row-constructor placeholder list, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$sql = $wpdb->prepare(
			"SELECT id, status, product_id, user_email FROM {$table}
			WHERE status IN ( %s, %s ) AND user_id = 0 AND ( product_id, user_email ) IN ( $placeholders )",
			$params
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Fetch the stored posted_attributes values of the batch's adoption candidates.
	 *
	 * A candidate with no such meta is absent from the result; the caller reads that as a
	 * single empty value, which is what an unserialized empty posted_attributes compares
	 * against.
	 *
	 * @param int[] $notification_ids Candidate notification ids.
	 * @return array<int,string[]> Stored values, by notification id.
	 */
	private function fetch_candidate_posted_attributes( array $notification_ids ): array {
		global $wpdb;

		$notification_ids = array_values( array_unique( array_map( 'intval', $notification_ids ) ) );

		if ( empty( $notification_ids ) ) {
			return array();
		}

		$table        = Constants::core_meta();
		$placeholders = implode( ', ', array_fill( 0, count( $notification_ids ), '%d' ) );

		// $table is $wpdb->prefix-based, never user input; $placeholders is a locally built
		// %d placeholder list, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT notification_id, meta_value FROM {$table}
			WHERE meta_key = 'posted_attributes' AND notification_id IN ( $placeholders )
			ORDER BY id ASC",
			$notification_ids
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$rows   = (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
		$values = array();

		foreach ( $rows as $row ) {
			$values[ (int) $row['notification_id'] ][] = (string) $row['meta_value'];
		}

		return $values;
	}

	/**
	 * Natural key of a registered legacy row or Core notification.
	 *
	 * @param int $product_id Product id.
	 * @param int $user_id    Registered user id.
	 * @return string
	 */
	private function registered_key( int $product_id, int $user_id ): string {
		return 'user:' . $product_id . ':' . $user_id;
	}

	/**
	 * Natural key of a guest legacy row or Core notification.
	 *
	 * @param int    $product_id Product id.
	 * @param string $email      Address, lowercased and trimmed.
	 * @return string
	 */
	private function guest_key( int $product_id, string $email ): string {
		return 'guest:' . $product_id . ':' . $email;
	}

	/**
	 * Adopt an existing Core notification: write only the legacy id marker, the adoption
	 * marker that records this was adoption rather than an insert, and, when both hash
	 * secrets are present, the legacy unsubscribe token. Never reconciles status, dates,
	 * or any other meta onto the target — the Core row is the merchant's.
	 *
	 * The adoption marker is a record, not an input: nothing reads it back, but it is the
	 * only way to tell later that this row predates the migration.
	 *
	 * @param int                 $target_id  Core notification id being adopted.
	 * @param array<string,mixed> $legacy_row Row from `woocommerce_bis_notifications`.
	 * @param array<string,mixed> $row_meta   This row's legacy meta bag.
	 * @param string              $status     Status resolved by StatusMapper for the legacy row.
	 * @param Writer              $writer     Writer to route the marker writes through.
	 * @return void
	 * @throws \RuntimeException When the marker write does not persist every row.
	 */
	private function adopt( int $target_id, array $legacy_row, array $row_meta, string $status, Writer $writer ): void {
		$legacy_id = (int) $legacy_row['id'];
		$meta      = array(
			array( Constants::legacy_id_meta_key( $legacy_id ), $legacy_id ),
			array( Constants::adopted_marker_meta_key( $legacy_id ), $legacy_id ),
		);
		$token     = $this->compute_token( $legacy_id, $legacy_row, $row_meta );

		if ( null !== $token ) {
			$meta[] = array( Constants::legacy_unsub_hash_meta_key( $legacy_id ), LegacyHash::to_meta_value( $token ) );
		}

		$verify_meta_value = $this->build_verification_meta_value( $row_meta, $status );

		if ( null !== $verify_meta_value ) {
			$meta[] = array( Constants::legacy_verify_hash_meta_key( $legacy_id ), $verify_meta_value );
		}

		$written = $writer->insert_notification_meta( $target_id, $meta );

		// The markers are what take this legacy row out of the candidate set. Without
		// them the row is picked up again on every later pass, so a short write has to
		// fail the row and let the per-row catch write the failure marker.
		if ( count( $meta ) !== $written ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal message, never rendered.
			throw new \RuntimeException(
				sprintf(
					'Wrote %d of %d adoption markers for legacy notification %d onto notification %d.',
					$written,
					count( $meta ),
					$legacy_id,
					$target_id
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( null === $token ) {
			++$this->rows_without_hash_count;
		}

		$this->maybe_set_has_migrated_rows_option( $writer );

		if ( null !== $token || null !== $verify_meta_value ) {
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
			'date_confirmed_gmt'    => $date_mapper->date_confirmed_gmt( $legacy_row, $status ),
			'date_last_attempt_gmt' => $date_mapper->date_last_attempt_gmt( $legacy_row ),
			'date_notified_gmt'     => $date_mapper->date_notified_gmt( $legacy_row, $status ),
			'date_cancelled_gmt'    => $date_mapper->date_cancelled_gmt( $legacy_row, $status, $latest_activity_date ),
			'cancellation_source'   => $cancellation_source,
		);
	}

	/**
	 * Map a legacy notification's meta bag to the Core meta rows to write.
	 *
	 * Carries `_customer_locale`, `_customer_location_data` and `posted_attributes` across
	 * unchanged (unserialized), and drops `_hash_key`, `_hash_iv`, `awaiting_verification`
	 * and `_verification_*` along with any other legacy key. The writer is the sole owner of
	 * `maybe_serialize()`; serializing here too would double-serialize the value once it
	 * reaches the writer. Migration markers are added by `build_meta()`, not here.
	 *
	 * @param array<string,mixed> $legacy_meta Legacy meta bag, keyed by meta key.
	 * @return array<int,array{0:string,1:mixed}> Meta rows in Writer shape.
	 */
	private function map_legacy_meta( array $legacy_meta ): array {
		$rows = array();

		foreach ( array( '_customer_locale', '_customer_location_data', 'posted_attributes' ) as $key ) {
			if ( array_key_exists( $key, $legacy_meta ) ) {
				$rows[] = array( $key, $legacy_meta[ $key ] );
			}
		}

		return $rows;
	}

	/**
	 * Build the Core meta rows for a new notification row: the mapped legacy meta bag,
	 * the legacy id marker, and the legacy unsubscribe token when both hash secrets exist.
	 *
	 * @param int                 $legacy_id  Legacy notification id.
	 * @param array<string,mixed> $legacy_row Row from `woocommerce_bis_notifications`.
	 * @param array<string,mixed> $row_meta   This row's legacy meta bag.
	 * @param string              $status     Status resolved by StatusMapper for this row.
	 * @return array<int,array{0:string,1:mixed}>
	 */
	private function build_meta( int $legacy_id, array $legacy_row, array $row_meta, string $status ): array {
		$meta   = $this->map_legacy_meta( $row_meta );
		$meta[] = array( Constants::legacy_id_meta_key( $legacy_id ), $legacy_id );

		$token = $this->compute_token( $legacy_id, $legacy_row, $row_meta );

		if ( null !== $token ) {
			$meta[]                           = array( Constants::legacy_unsub_hash_meta_key( $legacy_id ), LegacyHash::to_meta_value( $token ) );
			$this->batch_carries_legacy_links = true;
		} else {
			++$this->rows_without_hash_count;
		}

		$verify_meta_value = $this->build_verification_meta_value( $row_meta, $status );

		if ( null !== $verify_meta_value ) {
			$meta[]                           = array( Constants::legacy_verify_hash_meta_key( $legacy_id ), $verify_meta_value );
			$this->batch_carries_legacy_links = true;
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
	 * Build the stored verification-token meta value for a row migrated as pending.
	 *
	 * Returns null for any other status, for a row whose verification secrets are missing
	 * or unusable, and for a link that had already expired when the migration ran — there
	 * is nothing to honour in those cases, so nothing is stored.
	 *
	 * @param array<string,mixed> $row_meta This row's legacy meta bag.
	 * @param string              $status   Status resolved by StatusMapper for this row.
	 * @return string|null
	 */
	private function build_verification_meta_value( array $row_meta, string $status ): ?string {
		if ( NotificationStatus::PENDING !== $status ) {
			return null;
		}

		$created_at = (int) ( $row_meta['_verification_created_at'] ?? 0 );

		if ( $created_at <= 0 ) {
			return null;
		}

		$token = LegacyHash::compute_verification(
			(string) ( $row_meta['_verification_code'] ?? '' ),
			(string) ( $row_meta['_verification_key'] ?? '' ),
			(string) ( $row_meta['_verification_iv'] ?? '' )
		);

		if ( null === $token ) {
			return null;
		}

		$expires_at = $created_at + $this->verification_expiry_threshold();

		if ( $expires_at <= time() ) {
			return null;
		}

		return LegacyHash::to_meta_value( $token, $expires_at );
	}

	/**
	 * The expiry threshold legacy verification links are honoured under, in seconds.
	 *
	 * Prefers the legacy plugin's own filtered value, since that is the lifetime the
	 * shopper's email actually promised and the extension is normally still active while
	 * the migration runs. Falls back to Core's threshold when it is not loadable. Resolved
	 * once per run and baked into each stored expiry, because at request time the shim
	 * outlives the extension and Core's threshold is statically cached per request.
	 *
	 * @return int
	 */
	private function verification_expiry_threshold(): int {
		if ( null === $this->verification_expiry_threshold ) {
			$this->verification_expiry_threshold = function_exists( 'wc_bis_get_verification_expiration_time_threshold' )
				? (int) wc_bis_get_verification_expiration_time_threshold()
				: Config::get_verification_expiration_time_threshold();
		}

		return $this->verification_expiry_threshold;
	}

	/**
	 * Set the autoloaded `wc_bis_migration_has_legacy_links` flag the first time a row
	 * carrying a legacy token of either kind — unsubscribe or verification — is written.
	 * Reads the option first so an already-set flag costs nothing beyond the cached
	 * autoloaded read.
	 *
	 * @param Writer $writer Writer to route the option write through.
	 * @return void
	 */
	private function maybe_set_has_legacy_links_option( Writer $writer ): void {
		if ( 'yes' !== get_option( self::HAS_LEGACY_LINKS_OPTION ) ) {
			$writer->write_option( self::HAS_LEGACY_LINKS_OPTION, 'yes' );
		}
	}

	/**
	 * Set the autoloaded `wc_bis_migration_has_migrated_rows` flag the first time any row
	 * is migrated, inserted or adopted. Reads the option first so an already-set flag
	 * costs nothing beyond the cached autoloaded read.
	 *
	 * @param Writer $writer Writer to route the option write through.
	 * @return void
	 */
	private function maybe_set_has_migrated_rows_option( Writer $writer ): void {
		if ( 'yes' !== get_option( self::HAS_MIGRATED_ROWS_OPTION ) ) {
			$writer->write_option( self::HAS_MIGRATED_ROWS_OPTION, 'yes' );
		}
	}

	/**
	 * Mark a legacy row as permanently failed and leave it out of the candidate set.
	 *
	 * @param int    $legacy_id Legacy notification id.
	 * @param Writer $writer    Writer to route the marker write through.
	 * @return void
	 */
	private function fail_row( int $legacy_id, Writer $writer ): void {
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
