<?php
/**
 * Reporter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Report;

defined( 'ABSPATH' ) || exit;

/**
 * Collects and shapes the migration's outcome counts and logs its progress.
 *
 * Every migrator and runner reports through this class rather than logging directly, so the
 * CLI's final table and the Tools description render from the same accumulated structure. Only
 * ids and outcome codes are ever logged or displayed; full rows are never touched, since they
 * carry customer PII (email addresses).
 */
class Reporter {

	/**
	 * Outcome code for a row that was migrated to a new Core notification.
	 *
	 * @var string
	 */
	public const OUTCOME_MIGRATED = 'migrated';

	/**
	 * Outcome code for a row that resolved to an existing Core row via natural-key adoption.
	 *
	 * @var string
	 */
	public const OUTCOME_ADOPTED = 'adopted';

	/**
	 * Outcome code for an active legacy row that adopted a pending Core row. Adoption writes
	 * markers only, never status, so the subscriber stays pending: they were live in the
	 * legacy extension and are not live in Core. Nothing is lost — the Core row is the
	 * merchant's and predates the migration — but it is not the plain success `adopted` is,
	 * so it carries its own code and is reported as a warning.
	 *
	 * @var string
	 */
	public const OUTCOME_ADOPTED_DOWNGRADED = 'adopted_downgraded';

	/**
	 * Outcome code for a row that failed permanently and was marked with `_wc_bis_migration_failed`.
	 *
	 * @var string
	 */
	public const OUTCOME_FAILED = 'failed';

	/**
	 * Outcome code for a row that failed and could not even be marked as failed, so it stays
	 * a candidate. A batch made entirely of these is a section that cannot progress.
	 *
	 * @var string
	 */
	public const OUTCOME_UNSETTLED = 'unsettled';

	/**
	 * Outcome code for a row skipped because its product is missing, trashed or not a product.
	 *
	 * @var string
	 */
	public const OUTCOME_PRODUCT_MISSING = 'product_missing';

	/**
	 * Outcome code for a post skipped because it is a variation: neither side reads a
	 * variation's own flag, so there is nothing to write, only a row to settle.
	 *
	 * @var string
	 */
	public const OUTCOME_VARIATION_SKIPPED = 'variation_skipped';

	/**
	 * Outcome code for a row skipped because its address is longer than Core's column allows.
	 *
	 * @var string
	 */
	public const OUTCOME_EMAIL_TOO_LONG = 'email_too_long';

	/**
	 * Outcome code for a row skipped because its address cannot be an address.
	 *
	 * @var string
	 */
	public const OUTCOME_INVALID_EMAIL = 'invalid_email';

	/**
	 * Cached known-losses key: rows skipped because their email is too long for Core's column.
	 *
	 * @var string
	 */
	private const LOSS_EMAIL_TOO_LONG = 'email_too_long';

	/**
	 * Cached known-losses key: rows skipped because their email does not validate.
	 *
	 * @var string
	 */
	private const LOSS_INVALID_EMAIL = 'invalid_email';

	/**
	 * Cached known-losses key: rows skipped because their product is missing, trashed or not a product.
	 *
	 * @var string
	 */
	private const LOSS_PRODUCT_MISSING = 'product_missing';

	/**
	 * Cached known-losses key: migrated rows with no legacy unsubscribe secret to preserve.
	 *
	 * @var string
	 */
	private const LOSS_ROWS_WITHOUT_HASH = 'rows_without_hash';

	/**
	 * The skip counts that together make up the lost-legacy-link population.
	 *
	 * @var string[]
	 */
	private const SKIP_LOSS_KEYS = array(
		self::LOSS_EMAIL_TOO_LONG,
		self::LOSS_INVALID_EMAIL,
		self::LOSS_PRODUCT_MISSING,
	);

	/**
	 * Logger source used for every log entry this class writes.
	 *
	 * @var string
	 */
	private const LOG_SOURCE = 'bis-migration';

	/**
	 * Outcome counts, keyed by section slug then outcome code.
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $counts = array();

	/**
	 * Whether any error-severity outcome has been logged.
	 *
	 * @var bool
	 */
	private bool $has_errors = false;

	/**
	 * Record one outcome for one row and log it at the appropriate severity.
	 *
	 * `migrated` and `adopted` log nothing here; per-batch totals are logged by report_batch().
	 * Every other outcome is a skip (`warning`) except `failed` and `unsettled`, which are
	 * `error`s since they represent an exception the row could not recover from. `adopted_downgraded` is not a
	 * skip — the row did adopt — but it warns, because the subscriber came out less live than
	 * they went in and that is worth someone seeing.
	 *
	 * @param string $section Section slug, e.g. `notifications`.
	 * @param string $outcome One of the OUTCOME_* constants.
	 * @param int    $id      Legacy row identifier. Never a full row.
	 * @return void
	 */
	public function record( string $section, string $outcome, int $id ): void {
		$this->counts[ $section ][ $outcome ] = ( $this->counts[ $section ][ $outcome ] ?? 0 ) + 1;

		if ( self::OUTCOME_MIGRATED === $outcome || self::OUTCOME_ADOPTED === $outcome ) {
			return;
		}

		if ( self::OUTCOME_FAILED === $outcome || self::OUTCOME_UNSETTLED === $outcome ) {
			$this->has_errors = true;
			wc_get_logger()->error(
				sprintf( 'section=%s id=%d outcome=%s', $section, $id, $outcome ),
				array( 'source' => self::LOG_SOURCE )
			);
			return;
		}

		wc_get_logger()->warning(
			sprintf( 'section=%s id=%d outcome=%s', $section, $id, $outcome ),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Log one `info` entry summarising a completed batch.
	 *
	 * @param string $section Section slug.
	 * @param int    $count   Number of ids the batch attempted.
	 * @return void
	 */
	public function report_batch( string $section, int $count ): void {
		wc_get_logger()->info(
			sprintf( 'section=%s batch complete, %d row(s) processed', $section, $count ),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Log one `error` entry for an exception that failed a whole batch.
	 *
	 * Used for transient conditions - a DB error, a lost connection - that the controller
	 * should retry, as distinct from a per-row failure recorded via record().
	 *
	 * @param string     $section Section slug.
	 * @param \Throwable $error   The exception that failed the batch.
	 * @return void
	 */
	public function report_exception( string $section, \Throwable $error ): void {
		$this->has_errors = true;
		wc_get_logger()->error(
			sprintf( 'section=%s batch failed: %s', $section, $error->getMessage() ),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Log one `error` entry for an exception that failed a single row.
	 *
	 * The row itself is settled and counted through record(); this adds the class and
	 * message, which are otherwise discarded at the only point they exist. Identifiers
	 * only, never the row, so the log stays free of subscriber data.
	 *
	 * @param string     $section Section slug.
	 * @param int        $id      Legacy row identifier. Never a full row.
	 * @param \Throwable $error   The exception that failed the row.
	 * @return void
	 */
	public function report_row_exception( string $section, int $id, \Throwable $error ): void {
		$this->has_errors = true;
		wc_get_logger()->error(
			sprintf( 'section=%s id=%d row failed: %s: %s', $section, $id, get_class( $error ), $error->getMessage() ),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Log one `error` entry for a section the run has stopped serving.
	 *
	 * Distinct from a failed batch: nothing threw, the section simply cannot settle any of
	 * the rows it is handed, so it is skipped rather than retried until a merchant clears
	 * the markers with `--retry-failed`.
	 *
	 * @param string $section Section slug.
	 * @param string $reason  Why the section was parked.
	 * @return void
	 */
	public function report_section_parked( string $section, string $reason ): void {
		$this->has_errors = true;
		wc_get_logger()->error(
			sprintf( 'section=%s parked: %s', $section, $reason ),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Whether any error-severity outcome has occurred so far.
	 *
	 * Used by the CLI to decide its exit code.
	 *
	 * @return bool
	 */
	public function has_errors(): bool {
		return $this->has_errors;
	}

	/**
	 * Render the section x outcome x count structure as a flat table.
	 *
	 * Used by both the CLI's final table and the Tools description, so the two surfaces never
	 * drift out of sync with each other.
	 *
	 * @return array<int, array{section: string, outcome: string, count: int}>
	 */
	public function get_table(): array {
		$table = array();

		foreach ( $this->counts as $section => $outcomes ) {
			foreach ( $outcomes as $outcome => $count ) {
				$table[] = array(
					'section' => $section,
					'outcome' => $outcome,
					'count'   => $count,
				);
			}
		}

		return $table;
	}

	/**
	 * Merchant-facing "known losses" summary lines for the given counts.
	 *
	 * Each line is translated and carries its own count, so a zero-count loss can be omitted by
	 * the caller rather than presented as if it happened. Counts are supplied by the caller
	 * rather than read from $this->counts, since rows missing a hash are not an outcome code
	 * but a sub-count a migrator derives while producing OUTCOME_MIGRATED rows.
	 *
	 * A row delivered under legacy is not a loss: the extension deactivates a notification once
	 * it sends it, and its only type is one-time, so Core's terminal `sent` is the same state.
	 * A customer who wants the next restock signs up again, which Core allows for a sent row.
	 *
	 * @param int $links_lost_on_skip Skipped rows (email_too_long, invalid_email, product_missing) whose already-sent links stop working.
	 * @param int $rows_without_hash  Migrated rows with no `_hash_key`/`_hash_iv`, so no Core token - not a lost link, counted separately to distinguish pre-1.2.0 data from a bug.
	 * @return array<int, string> Translated summary lines, one per non-empty population.
	 */
	public function get_known_losses_summary( int $links_lost_on_skip, int $rows_without_hash ): array {
		$lines = array();

		if ( $links_lost_on_skip > 0 ) {
			$lines[] = sprintf(
				/* translators: %d: number of skipped sign-ups whose old links stop working */
				_n(
					'%d sign-up could not be moved, so the links in the emails it already sent stop working.',
					'%d sign-ups could not be moved, so the links in the emails they already sent stop working.',
					$links_lost_on_skip,
					'woocommerce'
				),
				$links_lost_on_skip
			);
		}

		if ( $rows_without_hash > 0 ) {
			$lines[] = sprintf(
				/* translators: %d: number of migrated sign-ups that never had an unsubscribe link */
				_n(
					'%d sign-up moved without an unsubscribe link, because it never had one in a delivered email.',
					'%d sign-ups moved without an unsubscribe link, because they never had one in a delivered email.',
					$rows_without_hash,
					'woocommerce'
				),
				$rows_without_hash
			);
		}

		return $lines;
	}

	/**
	 * Merchant-facing known-losses lines built from cached counts.
	 *
	 * Derives the lost-link total from the skip counts here, so the CLI and the Tools
	 * description cannot disagree about which skips cost a customer their link.
	 *
	 * @param array<string, int> $values Known-losses counts, as cached by MigrationState.
	 * @return array<int, string> Translated summary lines, one per non-empty population.
	 */
	public function summarize_cached_losses( array $values ): array {
		$links_lost_on_skip = 0;

		foreach ( self::SKIP_LOSS_KEYS as $key ) {
			$links_lost_on_skip += (int) ( $values[ $key ] ?? 0 );
		}

		return $this->get_known_losses_summary(
			$links_lost_on_skip,
			(int) ( $values[ self::LOSS_ROWS_WITHOUT_HASH ] ?? 0 )
		);
	}

	/**
	 * Format a cached count with the timestamp it was computed at.
	 *
	 * Counts are computed at run start and on section drain, then cached - never a live query on
	 * a normal page load - so they must always be presented as of that timestamp, not as a
	 * current remaining count. The timestamp is rendered in site-local time via wp_date(), since
	 * merchants have been reading localised values in the legacy admin.
	 *
	 * @param int $count     Cached count.
	 * @param int $timestamp Unix timestamp (UTC) the count was computed at.
	 * @return string Merchant-facing string, e.g. "42 (as of 27 Aug 2026, 14:03)".
	 */
	public function format_cached_count( int $count, int $timestamp ): string {
		return sprintf(
			/* translators: 1: cached count, 2: site-local date/time the count was computed at */
			__( '%1$d (as of %2$s)', 'woocommerce' ),
			$count,
			$this->format_site_time( $timestamp )
		);
	}

	/**
	 * Format a UTC timestamp in site-local time.
	 *
	 * Merchants have been reading localised values in the legacy admin, so a raw UTC value
	 * here would read as the migration having shifted their dates.
	 *
	 * @param int $timestamp Unix timestamp (UTC).
	 * @return string
	 */
	public function format_site_time( int $timestamp ): string {
		$formatted = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );

		return false === $formatted ? '' : $formatted;
	}
}
