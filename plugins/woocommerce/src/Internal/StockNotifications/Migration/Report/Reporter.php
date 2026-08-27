<?php
/**
 * Reporter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Report;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;

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
	 * Outcome code for a row that failed permanently and was marked with `_wc_bis_migration_failed`.
	 *
	 * @var string
	 */
	public const OUTCOME_FAILED = 'failed';

	/**
	 * Outcome code for a legacy row excluded because it was never verified.
	 *
	 * @var string
	 */
	public const OUTCOME_UNVERIFIED = 'unverified';

	/**
	 * Outcome code for a row skipped because its email address is too long for Core's column.
	 *
	 * @var string
	 */
	public const OUTCOME_EMAIL_TOO_LONG = 'email_too_long';

	/**
	 * Outcome code for a row skipped because its email address does not validate.
	 *
	 * @var string
	 */
	public const OUTCOME_INVALID_EMAIL = 'invalid_email';

	/**
	 * Outcome code for a row skipped because its product is missing, trashed or not a product.
	 *
	 * @var string
	 */
	public const OUTCOME_PRODUCT_MISSING = 'product_missing';

	/**
	 * Outcome code for an option or row left untouched because the merchant had already edited it.
	 *
	 * @var string
	 */
	public const OUTCOME_SKIPPED_USER_MODIFIED = 'skipped_user_modified';

	/**
	 * Cached known-losses key: unverified legacy rows excluded from the migration entirely.
	 *
	 * @var string
	 */
	private const LOSS_UNVERIFIED_EXCLUDED = 'unverified_excluded';

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
	 * Cached known-losses key: migrated rows that lose their eventual legacy re-fire.
	 *
	 * @var string
	 */
	private const LOSS_RECURRING = 'recurring_lost';

	/**
	 * Cached known-losses key: migrated rows with no legacy unsubscribe secret to preserve.
	 *
	 * @var string
	 */
	private const LOSS_ROWS_WITHOUT_HASH = 'rows_without_hash';

	/**
	 * The four skip counts that together make up the lost-unsubscribe-link population.
	 *
	 * @var string[]
	 */
	private const SKIP_LOSS_KEYS = array(
		self::LOSS_UNVERIFIED_EXCLUDED,
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
	 * Every other outcome is a skip (`warning`) except `failed`, which is an `error` since it
	 * represents an exception the row could not recover from.
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

		if ( self::OUTCOME_FAILED === $outcome ) {
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
	 * All accumulated outcome counts, keyed by section then outcome code.
	 *
	 * @return array<string, array<string, int>>
	 */
	public function get_counts(): array {
		return $this->counts;
	}

	/**
	 * Outcome counts for one section.
	 *
	 * @param string $section Section slug.
	 * @return array<string, int>
	 */
	public function get_section_counts( string $section ): array {
		return $this->counts[ $section ] ?? array();
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
	 * rather than read from $this->counts, since some of these populations - recurring
	 * notifications, rows missing a hash - are not outcome codes but sub-counts a migrator
	 * derives while producing OUTCOME_MIGRATED rows.
	 *
	 * @param int $recurring_lost      Rows mapped to `sent` that would have re-fired on a future restock under legacy.
	 * @param int $unverified_excluded Unverified legacy rows excluded from migration entirely.
	 * @param int $links_lost_on_skip  Predicate skips (unverified, email_too_long, invalid_email, product_missing) whose legacy unsubscribe link stops working.
	 * @param int $rows_without_hash   Migrated rows with no `_hash_key`/`_hash_iv`, so no Core token - not a lost link, counted separately to distinguish pre-1.2.0 data from a bug.
	 * @return array<int, string> Translated summary lines, one per non-empty population.
	 */
	public function get_known_losses_summary( int $recurring_lost, int $unverified_excluded, int $links_lost_on_skip, int $rows_without_hash ): array {
		$lines = array();

		if ( $recurring_lost > 0 ) {
			$lines[] = sprintf(
				/* translators: %d: number of notifications */
				_n(
					'%d notification that would have re-fired on a future restock will not re-fire under Core.',
					'%d notifications that would have re-fired on a future restock will not re-fire under Core.',
					$recurring_lost,
					'woocommerce'
				),
				$recurring_lost
			);
		}

		if ( $unverified_excluded > 0 ) {
			$lines[] = sprintf(
				/* translators: %d: number of unverified signups */
				_n(
					'%d unverified signup was not migrated; its verification link had already expired and Core would have deleted it under the data-retention setting.',
					'%d unverified signups were not migrated; their verification links had already expired and Core would have deleted them under the data-retention setting.',
					$unverified_excluded,
					'woocommerce'
				),
				$unverified_excluded
			);
		}

		if ( $links_lost_on_skip > 0 ) {
			$lines[] = sprintf(
				/* translators: %d: number of unsubscribe links */
				_n(
					'%d customer\'s legacy unsubscribe link will stop working, because the row it pointed to was skipped and has no Core notification.',
					'%d customers\' legacy unsubscribe links will stop working, because the rows they pointed to were skipped and have no Core notification.',
					$links_lost_on_skip,
					'woocommerce'
				),
				$links_lost_on_skip
			);
		}

		if ( $rows_without_hash > 0 ) {
			$lines[] = sprintf(
				/* translators: %d: number of migrated rows without a legacy unsubscribe hash */
				_n(
					'%d migrated row had no legacy unsubscribe secret to preserve; it never had a link in a delivered email.',
					'%d migrated rows had no legacy unsubscribe secret to preserve; they never had a link in a delivered email.',
					$rows_without_hash,
					'woocommerce'
				),
				$rows_without_hash
			);
		}

		return $lines;
	}

	/**
	 * Count the skipped populations a run start caches, ready to hand to MigrationState.
	 *
	 * The four counts are one `COUNT(*)` each and are computed only here, at run start, never
	 * on a page load. The two accumulator keys start at zero: they are totals a run adds up as
	 * it migrates, and are filled in afterwards by with_run_losses().
	 *
	 * @param NotificationsMigrator $migrator The notifications migrator these counts come from.
	 * @return array<string, int> Known-losses counts, keyed by name.
	 */
	public function collect_known_losses( NotificationsMigrator $migrator ): array {
		return array(
			self::LOSS_UNVERIFIED_EXCLUDED => $migrator->count_unverified_excluded(),
			self::LOSS_EMAIL_TOO_LONG      => $migrator->count_email_too_long(),
			self::LOSS_INVALID_EMAIL       => $migrator->count_invalid_email(),
			self::LOSS_PRODUCT_MISSING     => $migrator->count_product_missing(),
			self::LOSS_RECURRING           => 0,
			self::LOSS_ROWS_WITHOUT_HASH   => 0,
		);
	}

	/**
	 * Fill in the two totals a finished run accumulated, leaving the cached skip counts alone.
	 *
	 * @param array<string, int>    $values   Known-losses counts cached at run start.
	 * @param NotificationsMigrator $migrator The notifications migrator that just ran.
	 * @return array<string, int> The counts, with the run's accumulated totals merged in.
	 */
	public function with_run_losses( array $values, NotificationsMigrator $migrator ): array {
		$values[ self::LOSS_RECURRING ]         = $migrator->get_recurring_lost_count();
		$values[ self::LOSS_ROWS_WITHOUT_HASH ] = $migrator->get_rows_without_hash_count();

		return $values;
	}

	/**
	 * Merchant-facing known-losses lines built from cached counts.
	 *
	 * Derives the lost-unsubscribe-link total from the four skip counts here, so the CLI and
	 * the Tools description cannot disagree about which skips cost a customer their link.
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
			(int) ( $values[ self::LOSS_RECURRING ] ?? 0 ),
			(int) ( $values[ self::LOSS_UNVERIFIED_EXCLUDED ] ?? 0 ),
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
