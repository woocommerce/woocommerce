<?php
/**
 * ProductMetaMigrator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Enums\ProductType;
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
 * Write once, never revisit: a post leaves the candidate set as soon as it carries either
 * of the two keys this migrator writes — the target key, or
 * `Constants::PRODUCT_FAILED_META_KEY` for a post it can never write (the product does not
 * load, is a variation, or the write fails). Existence is the test, not the value: Core
 * only ever flips an already-present value (see
 * `Admin\SettingsController::save_product_meta()`), so the target key exists only because
 * something deliberately wrote it. A merchant who re-enables sign-ups on a migrated product
 * mid-run therefore keeps their choice instead of having it overwritten on the next pass.
 *
 * No variation fan-out: `EligibilityService::product_allows_signups()` resolves a
 * variation to its parent and tests only the parent's flag (it never reads a variation's
 * own meta), matching legacy's own parent lookup in `WC_BIS_Product::is_disabled()`. A flag
 * set directly on a `product_variation` post has nowhere to go on the Core side, so
 * migrate_one() settles it with the failure marker rather than writing to it.
 *
 * Trashed products migrate like any other. The flag has to survive the trash: a merchant
 * who restores a product after this section has drained would otherwise find sign-ups
 * silently re-enabled on it, since nothing revisits a drained section.
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

		$sql = $this->candidate_sql( 'COUNT( DISTINCT legacy_meta.post_id )', '', array() );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was prepared by candidate_sql(); table names are fixed internal names, never user input.
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Fetch the next batch of candidate product ids.
	 *
	 * Side-effect free: reads only. Deliberately keyset-free, unlike the notifications
	 * section: the predicate is self-terminating — a post leaves the candidate set the
	 * moment it carries either key this migrator writes — so re-reading from the start each
	 * time cannot serve a settled row twice, and the section still drains. A cursor here would strand any product that becomes a candidate below
	 * it, which is an ordinary thing to happen: a merchant can toggle "disable sign-ups"
	 * on an existing product while the legacy extension is still running. That row would
	 * never be served, yet would keep being counted, and the section would never drain.
	 *
	 * @param int $cursor Last product id handled. Ignored; see above.
	 * @param int $size   Maximum number of ids to return.
	 * @return array List of product ids, ascending. Ordered in PHP; see below for why.
	 */
	public function get_batch( int $cursor, int $size ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- part of MigratorInterface; see above.
		global $wpdb;

		$sql = $this->candidate_sql( 'DISTINCT legacy_meta.post_id', 'LIMIT %d', array( $size ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was prepared by candidate_sql(); table names are fixed internal names, never user input.
		$ids = array_map( 'intval', $wpdb->get_col( $sql ) );

		// Sorted here rather than in SQL: an `ORDER BY post_id` next to the `LIMIT` makes
		// MySQL drop the `meta_key` index and walk the `post_id` index instead, filtering as
		// it goes until it has a batch — a full scan of `wp_postmeta` on any real store.
		sort( $ids );

		return $ids;
	}

	/**
	 * Build the candidate query shared by get_batch() and count_remaining().
	 *
	 * Driven off the legacy meta rows themselves: they are the only posts with anything to
	 * migrate, so there is nothing to gain from walking products. A row is a candidate while
	 * the legacy flag is `'yes'` and the post carries neither key this migrator writes,
	 * which is one index probe per candidate rather than a join per exclusion.
	 *
	 * The `'yes'` test belongs here rather than in PHP. `meta_value` is not indexed, but the
	 * `meta_key` index is not covering either, so the row is read either way and the test is
	 * free — while leaving it out would hand back rows holding any other value, which need
	 * no write and would therefore never settle. This section has no cursor to move past
	 * them, so they would be re-served every pass until the processor parked the section.
	 *
	 * @param string $select      Select list. A fixed internal string, never user input.
	 * @param string $suffix      Extra conditions and clauses. A fixed internal string, never user input.
	 * @param array  $suffix_args Values for the placeholders `$suffix` adds, in order.
	 * @return string Prepared SQL.
	 */
	private function candidate_sql( string $select, string $suffix, array $suffix_args ): string {
		global $wpdb;

		$sql = "SELECT {$select}
			FROM {$wpdb->postmeta} legacy_meta
			WHERE legacy_meta.meta_key = %s
				AND legacy_meta.meta_value = '" . self::LEGACY_DISABLED_VALUE . "'
				AND NOT EXISTS (
					SELECT 1
					FROM {$wpdb->postmeta} settled_meta
					WHERE settled_meta.post_id = legacy_meta.post_id
						AND settled_meta.meta_key IN ( %s, %s )
				)
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
				$this->reporter->report_row_exception( self::SLUG, $product_id, $e );

				$outcome = $this->mark_terminal_failure( $product_id, $writer )
					? Reporter::OUTCOME_FAILED
					: Reporter::OUTCOME_UNSETTLED;
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
			return $this->mark_terminal_failure( $product_id, $writer )
				? Reporter::OUTCOME_PRODUCT_MISSING
				: Reporter::OUTCOME_UNSETTLED;
		}

		// A variation's own flag is never read on either side, so there is nothing to write
		// here — but the row still has to be settled, or it stays a candidate for good.
		if ( $product->is_type( ProductType::VARIATION ) ) {
			return $this->mark_terminal_failure( $product_id, $writer )
				? Reporter::OUTCOME_VARIATION_SKIPPED
				: Reporter::OUTCOME_UNSETTLED;
		}

		$written = $writer->write_product_meta(
			$product_id,
			Config::get_product_signups_meta_key(),
			self::TARGET_DISABLED_VALUE
		);

		if ( ! $written ) {
			return $this->mark_terminal_failure( $product_id, $writer )
				? Reporter::OUTCOME_FAILED
				: Reporter::OUTCOME_UNSETTLED;
		}

		return Reporter::OUTCOME_MIGRATED;
	}

	/**
	 * Settle a row that can never be migrated, so it stops being a candidate.
	 *
	 * Deliberately not `write_product_meta()`: this is the recovery path, and it runs from
	 * inside migrate_batch()'s catch, so it must not repeat the `$product->save()` that just
	 * threw. The marker is read back only by candidate_sql(), never off a product object, so
	 * the raw write costs nothing.
	 *
	 * Swallowing a throw here keeps migrate_batch()'s contract that a per-row failure is
	 * reported rather than thrown. `update_post_meta()` fires its own hooks, so a
	 * third-party callback can throw from this path too; those fire after the row is
	 * written, so the marker is normally in place by then, and the batch carries on either
	 * way rather than failing on a row it has already given up on.
	 *
	 * The return is read back rather than taken from the writer, whose own contract says its
	 * boolean means only that a write was issued. Whether the marker actually landed is what
	 * decides if this section can still make progress, so it is the one thing worth a read.
	 *
	 * @param int    $product_id Product id.
	 * @param Writer $writer     Writer to persist through.
	 * @return bool Whether the row is now settled and will leave the candidate set.
	 */
	private function mark_terminal_failure( int $product_id, Writer $writer ): bool {
		try {
			$writer->write_product_marker( $product_id, self::FAILED_META_KEY, (string) time() );
		} catch ( \Throwable $e ) {
			// Swallowed, then checked below: the hooks a meta write fires run after the row
			// itself is written, so a throwing callback does not mean the marker is missing.
			unset( $e );
		}

		if ( $writer->is_dry_run() ) {
			return true;
		}

		return '' !== (string) get_post_meta( $product_id, self::FAILED_META_KEY, true );
	}
}
