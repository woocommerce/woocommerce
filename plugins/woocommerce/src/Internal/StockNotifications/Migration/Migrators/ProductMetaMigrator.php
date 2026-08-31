<?php
/**
 * ProductMetaMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
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
 * Write once, never revisit: the target value itself is self-terminating. Once a row
 * holds `'no'` it no longer matches the candidate query, so no separate marker is needed
 * for a successful write. A row this migrator can never write (the product does not
 * load, or the write fails) is excluded instead via `Constants::PRODUCT_FAILED_META_KEY`,
 * since otherwise it would be served on every pass and the section would never drain.
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
	private const LEGACY_META_KEY = Constants::PRODUCT_LEGACY_META_KEY;

	/**
	 * The only legacy value this migrator acts on. Anything else means "enabled", which is
	 * also Core's default, so there is nothing to write.
	 */
	private const LEGACY_DISABLED_VALUE = 'yes';

	/**
	 * The value written to the Core meta key for a legacy-disabled product. Polarity is
	 * inverted: legacy `_wc_bis_disabled = 'yes'` means Core signups are off.
	 */
	private const TARGET_DISABLED_VALUE = 'no';

	/**
	 * Product meta key marking a product this section can never settle: the product does
	 * not load, or the write itself failed. Such a row is excluded from the candidate set
	 * so re-admitting it would not serve the same row on every pass. `--retry-failed`
	 * clears it.
	 */
	private const FAILED_META_KEY = Constants::PRODUCT_FAILED_META_KEY;

	/**
	 * Reporter every outcome is recorded through.
	 *
	 * @var Reporter
	 */
	private Reporter $reporter;

	/**
	 * Constructor.
	 *
	 * @param Reporter $reporter Reporter to record outcomes through.
	 */
	public function __construct( Reporter $reporter ) {
		$this->reporter = $reporter;
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
	 * Exactly the predicate get_batch() serves from, expressed as a COUNT(*). Neither uses
	 * a cursor, so the two cannot disagree about what is left.
	 *
	 * @param int $cursor Last product id handled. Ignored; this section keeps no cursor.
	 * @return int
	 */
	public function count_remaining( int $cursor = 0 ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface; see above.
		global $wpdb;

		$sql = $this->candidate_sql( 'COUNT( DISTINCT p.ID )', '', array() );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was prepared by candidate_sql(); table names are fixed internal names, never user input.
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Fetch the next batch of candidate product ids.
	 *
	 * Side-effect free: reads only. Deliberately keyset-free, unlike the notifications
	 * section: the predicate is self-terminating — a product leaves the candidate set the
	 * moment the target meta key holds the migrated value, or the row is marked failed — so
	 * re-reading from the start each time cannot serve a settled row twice, and the section
	 * still drains. A cursor here would strand any product that becomes a candidate below
	 * it, which is an ordinary thing to happen: a merchant can toggle "disable sign-ups"
	 * on an existing product while the legacy extension is still running. That row would
	 * never be served, yet would keep being counted, and the section would never drain.
	 *
	 * @param int $cursor Last product id handled. Ignored; see above.
	 * @param int $size   Maximum number of ids to return.
	 * @return array List of product ids, ascending.
	 */
	public function get_batch( int $cursor, int $size ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface; see above.
		global $wpdb;

		$sql = $this->candidate_sql(
			'DISTINCT p.ID',
			'ORDER BY p.ID ASC
			LIMIT %d',
			array( $size )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was prepared by candidate_sql(); table names are fixed internal names, never user input.
		$ids = $wpdb->get_col( $sql );

		return array_map( 'intval', $ids );
	}

	/**
	 * Build the candidate query shared by get_batch() and count_remaining().
	 *
	 * A product is a candidate while the legacy flag is `'yes'`, the target meta key does
	 * not already hold the migrated value, and the row is not marked as a permanent
	 * failure. This is a pure value predicate: once migrate_one() writes the target value
	 * the row stops matching, so the section drains without any per-row marker.
	 *
	 * @param string $select      Select list. A fixed internal string, never user input.
	 * @param string $suffix      Extra conditions and clauses. A fixed internal string, never user input.
	 * @param array  $suffix_args Values for the placeholders `$suffix` adds, in order.
	 * @return string Prepared SQL.
	 */
	private function candidate_sql( string $select, string $suffix, array $suffix_args ): string {
		global $wpdb;

		$sql = "SELECT {$select}
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} legacy_meta
				ON legacy_meta.post_id = p.ID
				AND legacy_meta.meta_key = %s
				AND legacy_meta.meta_value = '" . self::LEGACY_DISABLED_VALUE . "'
			LEFT JOIN {$wpdb->postmeta} target_meta
				ON target_meta.post_id = p.ID
				AND target_meta.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} failed_meta
				ON failed_meta.post_id = p.ID
				AND failed_meta.meta_key = %s
			WHERE p.post_type = 'product'
				AND p.post_status <> 'trash'
				AND COALESCE( target_meta.meta_value, '' ) <> '" . self::TARGET_DISABLED_VALUE . "'
				AND failed_meta.meta_id IS NULL
				{$suffix}";

		$args = array_merge(
			array(
				self::LEGACY_META_KEY,
				Config::get_product_signups_meta_key(),
				self::FAILED_META_KEY,
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
	 * @param array  $ids    Product ids returned by get_batch().
	 * @param Writer $writer Writer to route all persistence through.
	 * @return array Outcome counts keyed by outcome code.
	 */
	public function migrate_batch( array $ids, Writer $writer ): array {
		$outcomes = array();

		foreach ( $ids as $product_id ) {
			$product_id = (int) $product_id;

			try {
				$outcome = $this->migrate_one( $product_id, $writer );
			} catch ( \Throwable $e ) {
				// migrate_one() goes through the product CRUD layer, which runs third-party
				// code on save. Letting one product's throw out of here would fail the whole
				// batch, and the controller would retry it, hit the same product, and
				// eventually drop the processor — stalling the notifications section too,
				// since both share one. Settle the row instead and carry on.
				$this->mark_terminal_failure( $product_id, $writer );
				$outcome = Reporter::OUTCOME_FAILED;
			}

			$outcomes[ $outcome ] = ( $outcomes[ $outcome ] ?? 0 ) + 1;
			$this->reporter->record( self::SLUG, $outcome, $product_id );
		}

		$this->reporter->report_batch( self::SLUG, count( $ids ) );

		return $outcomes;
	}

	/**
	 * Migrate a single product.
	 *
	 * @param int    $product_id Product id.
	 * @param Writer $writer     Writer to route all persistence through.
	 * @return string One of the Reporter::OUTCOME_* constants.
	 */
	private function migrate_one( int $product_id, Writer $writer ): string {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product ) {
			// Record the visit so the row leaves the candidate set. A row that keeps failing
			// without a marker is never drained, and the section would stall the whole run.
			$this->mark_terminal_failure( $product_id, $writer );

			return Reporter::OUTCOME_PRODUCT_MISSING;
		}

		$written = $writer->write_product_meta(
			$product_id,
			Config::get_product_signups_meta_key(),
			self::TARGET_DISABLED_VALUE
		);

		if ( ! $written ) {
			$this->mark_terminal_failure( $product_id, $writer );

			return Reporter::OUTCOME_FAILED;
		}

		return Reporter::OUTCOME_MIGRATED;
	}

	/**
	 * Settle a row that can never be migrated, so it stops being a candidate.
	 *
	 * @param int    $product_id Product id.
	 * @param Writer $writer     Writer to persist through.
	 * @return void
	 */
	private function mark_terminal_failure( int $product_id, Writer $writer ): void {
		$writer->write_product_meta( $product_id, self::FAILED_META_KEY, (string) time() );
	}
}
