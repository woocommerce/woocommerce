<?php
/**
 * ProductMetaMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\WriterInterface;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates the legacy per-product "disable sign-ups" flag onto the Core product meta key.
 *
 * Legacy stores `_wc_bis_disabled = 'yes'` in `wp_postmeta`. Core stores the opposite
 * polarity, `customer_stock_notifications_enable_signups = 'no'`, via
 * `Config::get_product_signups_meta_key()`. Absent means enabled on both sides, so only
 * the `'yes'` rows are candidates; a row that is absent or any other value needs no write
 * and is never selected.
 *
 * No variation fan-out: `EligibilityService::product_allows_signups()` resolves a
 * variation to its parent and tests only the parent's flag (it never reads a variation's
 * own meta), matching legacy's own parent lookup in `WC_BIS_Product::is_disabled()`. This
 * migrator therefore only considers `product` posts; a flag set directly on a
 * `product_variation` post has nowhere to go on the Core side and is not migrated.
 */
class ProductMetaMigrator implements MigratorInterface {

	/**
	 * Section slug.
	 */
	private const SLUG = 'product-meta';

	/**
	 * Legacy post meta key holding the "sign-ups disabled" flag.
	 */
	private const LEGACY_META_KEY = '_wc_bis_disabled';

	/**
	 * The only legacy value this migrator acts on. Anything else means "enabled", which is
	 * also Core's default, so there is nothing to write.
	 */
	private const LEGACY_DISABLED_VALUE = 'yes';

	/**
	 * Product meta key holding this migrator's per-product fingerprint.
	 *
	 * Stored as `{source hash}:{target hash}:{timestamp}` rather than as an array, so the
	 * candidate query can compare both hashes in SQL. A row is a candidate again once the
	 * live target value's hash no longer matches the target hash recorded here, even
	 * though a fingerprint already exists — that is how a merchant edit made after this
	 * migrator last ran gets picked up on the next pass.
	 */
	private const FINGERPRINT_META_KEY = '_wc_bis_migration_signups_written';

	/**
	 * Separator between the two hashes and the timestamp in a stored fingerprint.
	 */
	private const FINGERPRINT_SEPARATOR = ':';

	/**
	 * Reporter every outcome is recorded through.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * Used for its fingerprint hashing helper and its OPTION_ACTION_* vocabulary, kept
	 * consistent with the option fingerprints even though per-product fingerprints live
	 * in product meta rather than in `wc_bis_migration_state`.
	 *
	 * @var MigrationState
	 */
	private MigrationState $migration_state;

	/**
	 * Whether to overwrite a product a merchant already edited since this migrator wrote it.
	 *
	 * @var bool
	 */
	private bool $force;

	/**
	 * Constructor.
	 *
	 * @param Reporter       $reporter        Reporter to record outcomes through.
	 * @param MigrationState $migration_state State helper, used here only for fingerprint hashing.
	 * @param bool           $force           Whether `--force` was passed on the CLI.
	 */
	public function __construct( Reporter $reporter, MigrationState $migration_state, bool $force = false ) {
		$this->reporter        = $reporter;
		$this->migration_state = $migration_state;
		$this->force           = $force;
	}

	/**
	 * Section slug, used in batch item prefixes, state keys and CLI `--section` values.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Count the rows this section still has to migrate.
	 *
	 * Same predicate as get_batch(), without the cursor, expressed as a COUNT(*).
	 *
	 * @return int
	 */
	public function count_remaining(): int {
		global $wpdb;

		$sql = $this->candidate_sql( 'COUNT( DISTINCT p.ID )', '', array() );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was prepared by candidate_sql(); table names are fixed internal names, never user input.
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Fetch the next batch of candidate product ids after the given keyset cursor.
	 *
	 * Side-effect free: reads only. A product leaves the candidate set once its recorded
	 * fingerprint matches both the legacy source value and the live target meta value,
	 * whatever the fingerprint decision was, so a settled row is never selected again.
	 *
	 * @param int $cursor Last product id handled in the current pass, or 0 to start a pass.
	 * @param int $size   Maximum number of ids to return.
	 * @return array List of product ids, ascending.
	 */
	public function get_batch( int $cursor, int $size ): array {
		global $wpdb;

		$sql = $this->candidate_sql(
			'DISTINCT p.ID',
			'AND p.ID > %d
			ORDER BY p.ID ASC
			LIMIT %d',
			array( $cursor, $size )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was prepared by candidate_sql(); table names are fixed internal names, never user input.
		$ids = $wpdb->get_col( $sql );

		return array_map( 'intval', $ids );
	}

	/**
	 * Build the candidate query shared by get_batch() and count_remaining().
	 *
	 * A product is a candidate while `decide_action()` would return anything other than
	 * "already handled, unchanged": no fingerprint yet, a fingerprint whose recorded
	 * source hash no longer matches the legacy value, or one whose recorded target hash
	 * no longer matches what is stored under the Core meta key today. Expressing all
	 * three in SQL keeps merchant edits visible on later runs while still letting the
	 * section drain — a row settled by migrate_one() matches on both hashes and drops out.
	 *
	 * `SHA2( ..., 256 )` mirrors `MigrationState::fingerprint_value()`: WordPress stores
	 * meta already serialized, and `maybe_serialize()` on the value read back reproduces
	 * that same string, so both sides hash identical bytes. An absent target meta row
	 * hashes as the empty string on both sides too. The two hashes are compared as binary
	 * so a connection collation that differs from the column's cannot make the comparison
	 * fail.
	 *
	 * @param string $select      Select list. A fixed internal string, never user input.
	 * @param string $suffix      Extra conditions and clauses. A fixed internal string, never user input.
	 * @param array  $suffix_args Values for the placeholders `$suffix` adds, in order.
	 * @return string Prepared SQL.
	 */
	private function candidate_sql( string $select, string $suffix, array $suffix_args ): string {
		global $wpdb;

		$separator = self::FINGERPRINT_SEPARATOR;

		$sql = "SELECT {$select}
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} legacy_meta
				ON legacy_meta.post_id = p.ID
				AND legacy_meta.meta_key = %s
				AND legacy_meta.meta_value = '" . self::LEGACY_DISABLED_VALUE . "'
			LEFT JOIN {$wpdb->postmeta} target_meta
				ON target_meta.post_id = p.ID
				AND target_meta.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} fingerprint_meta
				ON fingerprint_meta.post_id = p.ID
				AND fingerprint_meta.meta_key = %s
			WHERE p.post_type = 'product'
				AND p.post_status <> 'trash'
				AND (
					fingerprint_meta.meta_id IS NULL
					OR SUBSTRING_INDEX( fingerprint_meta.meta_value, '{$separator}', 1 ) <> %s
					OR CAST( SUBSTRING_INDEX( SUBSTRING_INDEX( fingerprint_meta.meta_value, '{$separator}', 2 ), '{$separator}', -1 ) AS BINARY )
						<> CAST( SHA2( COALESCE( target_meta.meta_value, '' ), 256 ) AS BINARY )
				)
				{$suffix}";

		$args = array_merge(
			array(
				self::LEGACY_META_KEY,
				Config::get_product_signups_meta_key(),
				self::FINGERPRINT_META_KEY,
				$this->get_source_hash(),
			),
			$suffix_args
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- the only values interpolated into $sql are fixed internal strings; every caller-supplied value is passed as a placeholder argument here.
		return (string) $wpdb->prepare( $sql, $args );
	}

	/**
	 * Migrate the given product ids.
	 *
	 * Per-row failures are recorded and reported rather than thrown.
	 *
	 * @param array           $ids    Product ids returned by get_batch().
	 * @param WriterInterface $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by outcome code.
	 */
	public function migrate_batch( array $ids, WriterInterface $writer ): array {
		$outcomes = array();

		foreach ( $ids as $product_id ) {
			$product_id = (int) $product_id;
			$outcome    = $this->migrate_one( $product_id, $writer );

			$outcomes[ $outcome ] = ( $outcomes[ $outcome ] ?? 0 ) + 1;
			$this->reporter->record( self::SLUG, $outcome, $product_id );
		}

		$this->reporter->report_batch( self::SLUG, count( $ids ) );

		return $outcomes;
	}

	/**
	 * Migrate a single product.
	 *
	 * @param int             $product_id Product id.
	 * @param WriterInterface $writer     Writer to route all persistence through.
	 * @return string One of the Reporter::OUTCOME_* constants.
	 */
	private function migrate_one( int $product_id, WriterInterface $writer ): string {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product ) {
			// Record the visit so the row leaves the candidate set. A row that keeps failing
			// without a marker is never drained, and the section would stall the whole run.
			$this->mark_terminal_failure( $product_id, $writer );

			return Reporter::OUTCOME_PRODUCT_MISSING;
		}

		$target_meta_key = Config::get_product_signups_meta_key();

		// Read the raw stored value rather than WC_Product::get_meta(), and let an absent
		// row read as '', which is what the candidate query hashes it as too.
		$current_value = get_post_meta( $product_id, $target_meta_key, true );

		$current_target_hash = $this->migration_state->fingerprint_value( $current_value );
		$source_hash         = $this->get_source_hash();

		$fingerprint = $this->parse_fingerprint( get_post_meta( $product_id, self::FINGERPRINT_META_KEY, true ) );

		$action = $this->decide_action( $fingerprint, $source_hash, $current_target_hash );

		if ( MigrationState::OPTION_ACTION_SKIP_USER_MODIFIED === $action ) {
			// Do not touch the value the merchant set; record what it is now, so the row
			// only comes back as user-modified again after a further edit.
			$writer->write_product_meta(
				$product_id,
				self::FINGERPRINT_META_KEY,
				$this->format_fingerprint( $source_hash, $current_target_hash )
			);

			return Reporter::OUTCOME_SKIPPED_USER_MODIFIED;
		}

		if ( MigrationState::OPTION_ACTION_SKIP_UNCHANGED === $action ) {
			return MigrationState::OPTION_ACTION_SKIP_UNCHANGED;
		}

		if ( ! $writer->write_product_meta( $product_id, $target_meta_key, 'no' ) ) {
			$this->mark_terminal_failure( $product_id, $writer );

			return Reporter::OUTCOME_FAILED;
		}

		$new_target_hash = $this->migration_state->fingerprint_value( 'no' );

		$writer->write_product_meta(
			$product_id,
			self::FINGERPRINT_META_KEY,
			$this->format_fingerprint( $source_hash, $new_target_hash )
		);

		return Reporter::OUTCOME_MIGRATED;
	}

	/**
	 * Fingerprint of the legacy source value.
	 *
	 * Both the candidate query and migrate_one() only ever deal with `'yes'` rows, so the
	 * source side of a fingerprint is the same for every product.
	 *
	 * @return string
	 */
	private function get_source_hash(): string {
		return $this->migration_state->fingerprint_value( self::LEGACY_DISABLED_VALUE );
	}

	/**
	 * Build the fingerprint meta value for a product.
	 *
	 * @param string $source_hash Fingerprint of the legacy source value.
	 * @param string $target_hash Fingerprint of the value now stored under the Core meta key.
	 * @return string
	 */
	private function format_fingerprint( string $source_hash, string $target_hash ): string {
		return implode( self::FINGERPRINT_SEPARATOR, array( $source_hash, $target_hash, (string) time() ) );
	}

	/**
	 * Read a stored fingerprint back into the shape decide_action() expects.
	 *
	 * Anything that is not a well-formed fingerprint reads as absent, so a garbled value
	 * is rewritten rather than trusted. The candidate query treats it the same way.
	 *
	 * @param mixed $stored Raw fingerprint meta value.
	 * @return array{written: string, hash: string}|null
	 */
	private function parse_fingerprint( $stored ): ?array {
		if ( ! is_string( $stored ) ) {
			return null;
		}

		$parts = explode( self::FINGERPRINT_SEPARATOR, $stored );

		if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
			return null;
		}

		return array(
			'written' => $parts[0],
			'hash'    => $parts[1],
		);
	}

	/**
	 * Decide what to do with a product's target meta, mirroring
	 * `MigrationState::decide_option_action()` for a fingerprint stored in product meta
	 * instead of `wc_bis_migration_state`.
	 *
	 * | Fingerprint  | Action                                                  |
	 * | ------------ | -------------------------------------------------------- |
	 * | Absent       | Write                                                     |
	 * | Hash matches | We wrote it; rewrite only if the source changed           |
	 * | Hash differs | Merchant edited it; skip, unless `--force`                |
	 *
	 * @param array<string,string>|null $fingerprint        Fingerprint on record, if any, as
	 *                                                       returned by parse_fingerprint().
	 * @param string                    $source_hash         Fingerprint of the current legacy source value.
	 * @param string                    $current_target_hash Fingerprint of the product's current stored value.
	 * @return string One of MigrationState::OPTION_ACTION_* constants.
	 */
	private function decide_action( ?array $fingerprint, string $source_hash, string $current_target_hash ): string {
		if ( null === $fingerprint || ! isset( $fingerprint['hash'], $fingerprint['written'] ) ) {
			return MigrationState::OPTION_ACTION_WRITE;
		}

		if ( $fingerprint['hash'] !== $current_target_hash ) {
			return $this->force ? MigrationState::OPTION_ACTION_WRITE : MigrationState::OPTION_ACTION_SKIP_USER_MODIFIED;
		}

		if ( $fingerprint['written'] !== $source_hash ) {
			return MigrationState::OPTION_ACTION_WRITE;
		}

		return MigrationState::OPTION_ACTION_SKIP_UNCHANGED;
	}

	/**
	 * Write a fingerprint for a row that can never be migrated, so it stops being a candidate.
	 *
	 * The fingerprint records the state actually observed rather than a successful write, so
	 * a later run re-admits the row only if something about it genuinely changes.
	 *
	 * @param int             $product_id Product id.
	 * @param WriterInterface $writer     Writer to persist through.
	 * @return void
	 */
	private function mark_terminal_failure( int $product_id, WriterInterface $writer ): void {
		$target_hash = $this->migration_state->fingerprint_value(
			(string) get_post_meta( $product_id, Config::get_product_signups_meta_key(), true )
		);

		$writer->write_product_meta(
			$product_id,
			self::FINGERPRINT_META_KEY,
			$this->format_fingerprint( $this->get_source_hash(), $target_hash )
		);
	}
}
