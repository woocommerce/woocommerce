<?php
/**
 * ToolsRegistrar class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationRun;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the `woocommerce_debug_tools` entry that starts and stops the BIS migration.
 *
 * Modelled on `OrderCouponDataMigrator`'s start/stop split: a start button when
 * `MigrationBatchProcessor` is not enqueued, a stop button that dequeues it when it is. The
 * capability check lives in the callback itself rather than being inherited from the surrounding
 * Tools screen, because the callback is what actually starts or stops a run that rewrites
 * subscriber data.
 */
class ToolsRegistrar {


	/**
	 * The one section whose size is worth a number on this screen.
	 */
	private const SUBSCRIBERS_SECTION = 'notifications';

	/**
	 * Owner string a background run's lock carries. Recognising it is what lets this screen
	 * tell its own abandoned lock from one a WP-CLI run is still holding.
	 */
	private const BACKGROUND_LOCK_OWNER = 'background migration';

	/**
	 * Add the migration's start/stop entry to the Tools list.
	 *
	 * @internal
	 *
	 * @param array $tools Existing `woocommerce_debug_tools` entries.
	 * @return array
	 */
	public function handle_woocommerce_debug_tools( array $tools ): array {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $tools;
		}

		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		$is_running      = $batch_processor->is_enqueued( MigrationBatchProcessor::class );
		$description     = $this->get_description( $is_running );

		if ( $is_running ) {
			$tools['stop_bis_migration'] = array(
				'name'     => __( 'Stop migrating Back In Stock Notifications subscribers', 'woocommerce' ),
				'button'   => __( 'Stop migration', 'woocommerce' ),
				'desc'     => $description,
				'callback' => array( $this, 'stop' ),
			);

			return $tools;
		}

		$tools['start_bis_migration'] = array(
			'name'     => __( 'Migrate Back In Stock Notifications subscribers', 'woocommerce' ),
			'button'   => __( 'Start migration', 'woocommerce' ),
			'desc'     => $description,
			'callback' => array( $this, 'start' ),
		);

		return $tools;
	}

	/**
	 * Start the migration's background run.
	 *
	 * Refuses while a CLI run holds the lock, since the migration is not safe to run twice
	 * concurrently: natural-key adoption is check-then-insert, and two concurrent workers can
	 * each create a Core notification for the same legacy row.
	 *
	 * Also refuses while the legacy extension still has rows queued for its own sender, since
	 * migrating those rows lets the extension and Core both send the same notification. There
	 * is no override on this screen: the CLI's `--force` is the only way past it.
	 *
	 * @internal
	 *
	 * @return string Message shown after the tool runs.
	 */
	public function start(): string {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return __( 'You do not have permission to do this.', 'woocommerce' );
		}

		$migration_state = wc_get_container()->get( MigrationState::class );
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );

		if ( $batch_processor->is_enqueued( MigrationBatchProcessor::class ) ) {
			return __( 'Migration already in progress, nothing done.', 'woocommerce' );
		}

		$queued = wc_get_container()->get( Requirements::class )->count_legacy_queued_rows();

		if ( $queued > 0 ) {
			return sprintf(
				/* translators: %d: number of legacy rows still queued by the Back In Stock Notifications extension */
				_n(
					'Cannot start yet: Back In Stock Notifications still has %d notification queued to send, and migrating now could send it twice. Let the queue drain, then start again. To override, run `wp wc bis-migrate run --force --yes`.',
					'Cannot start yet: Back In Stock Notifications still has %d notifications queued to send, and migrating now could send them twice. Let the queue drain, then start again. To override, run `wp wc bis-migrate run --force --yes`.',
					$queued,
					'woocommerce'
				),
				$queued
			);
		}

		// A lock still owned by a background run that is no longer enqueued has no holder
		// left: `BatchProcessingController` drops a consistently failing processor without
		// telling it, so the run that took this lock is already gone. Reclaim it rather than
		// making the merchant wait out the stale threshold.
		$migration_state->release_lock_owned_by( self::BACKGROUND_LOCK_OWNER );

		// Before the counts below, which are the expensive part of this callback: a start
		// refused by another process should not pay for a full pass over the legacy table.
		if ( ! $migration_state->acquire_lock( self::BACKGROUND_LOCK_OWNER ) ) {
			$lock = $migration_state->get_lock();

			return sprintf(
				/* translators: %s: identifier of the process holding the migration lock */
				__( 'A migration is already running (%s). Stop it there first; starting one here does not override it.', 'woocommerce' ),
				$lock['owner'] ?? __( 'unknown', 'woocommerce' )
			);
		}

		// The cursor a previous run left behind is kept: it only ever advances, and the
		// per-batch already-migrated lookup means resuming behind it costs a re-scan rather
		// than correctness. Re-walking the whole legacy table is what this migration cannot
		// afford to repeat, so a run that has nothing left to visit does nothing at all.
		$run = new MigrationRun();

		$this->refresh_cached_counts( $migration_state, $run );

		// The denominator the Tools screen shows progress against: how many legacy rows this
		// run has to walk, whatever the cursor has already been past.
		$migration_state->set_total( 'notifications', $run->get_notifications_migrator()->count_remaining() );

		// A new run supersedes whatever stopped the last one, parked sections included: this
		// screen has no equivalent of `--retry-failed`, so pressing Run is the merchant's
		// retry. A section that still cannot settle its rows parks itself again on its first
		// batch, at the cost of that one batch.
		$migration_state->clear_failure();
		$migration_state->unpark_all();

		$batch_processor->enqueue_processor( MigrationBatchProcessor::class );

		return __( 'Migration started. Subscribers will be migrated in the background over the next few minutes.', 'woocommerce' );
	}

	/**
	 * Recompute and cache every section's remaining count at run start.
	 *
	 * The processor itself only refreshes a section's cached count on drain, so the
	 * "computed at run start" half of that contract has to happen here, in the callback
	 * that actually starts a run.
	 *
	 * @param MigrationState $migration_state Run state to write the refreshed counts into.
	 * @param MigrationRun   $run             The run whose migrators are counted.
	 * @return void
	 */
	private function refresh_cached_counts( MigrationState $migration_state, MigrationRun $run ): void {
		foreach ( $run->build_migrators() as $slug => $migrator ) {
			$migration_state->set_count( $slug, $migrator->count_remaining( $migration_state->get_cursor( $slug ) ) );
		}
	}

	/**
	 * Stop the migration's background run.
	 *
	 * Stopping is never a terminal outcome: nothing migrated so far is undone, and the
	 * outstanding count above continues to reflect what is left to do.
	 *
	 * @internal
	 *
	 * @return string Message shown after the tool runs.
	 */
	public function stop(): string {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return __( 'You do not have permission to do this.', 'woocommerce' );
		}

		$batch_processor = wc_get_container()->get( BatchProcessingController::class );

		if ( ! $batch_processor->is_enqueued( MigrationBatchProcessor::class ) ) {
			return __( 'Migration not in progress, nothing done.', 'woocommerce' );
		}

		$batch_processor->remove_processor( MigrationBatchProcessor::class );

		$migration_state = wc_get_container()->get( MigrationState::class );
		$migration_state->release_lock();
		// Stopping is a deliberate outcome, so it supersedes whatever the last batch failed
		// on — otherwise the screen would keep reporting an error the merchant just acted on.
		$migration_state->clear_failure();

		return __( 'Migration stopped. Subscribers already moved stay put, and the next run picks up where this one left off.', 'woocommerce' );
	}

	/**
	 * Build the tool's description: what it does, where the migration stands, and what it has
	 * skipped so far.
	 *
	 * Every number comes from `MigrationState`'s cached values - written at run start and on
	 * section drain, never here - so rendering the Tools screen never runs a count query, and
	 * every number is shown with the timestamp it was taken at rather than as current. The one
	 * live read is whether the store settings have landed, which is a handful of option reads.
	 *
	 * @param bool $is_running Whether a background run is enqueued right now.
	 * @return string
	 */
	private function get_description( bool $is_running ): string {
		$lines = array( __( 'Moves subscribers and settings from Back In Stock Notifications into the built-in stock notifications. Runs in the background, a batch at a time.', 'woocommerce' ) );

		$description_lines = array(
			$this->get_progress_line( $is_running ),
			$this->get_failure_line( $is_running ),
			$this->get_parked_line(),
			$this->get_losses_line(),
		);

		foreach ( $description_lines as $line ) {
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}

		return implode( '<br />', $lines );
	}

	/**
	 * The line that says a run stopped on an error rather than because someone stopped it.
	 *
	 * Without this the two look identical on this screen: `BatchProcessingController` drops a
	 * consistently failing processor on its own, so `is_enqueued()` goes false exactly as it
	 * would after a deliberate Stop, and the only trace is a wc-logger entry. The processor
	 * records what it failed on before rethrowing, and that is what this reads.
	 *
	 * @param bool $is_running Whether a background run is enqueued right now.
	 * @return string
	 */
	private function get_failure_line( bool $is_running ): string {
		if ( $is_running ) {
			return '';
		}

		$failure = wc_get_container()->get( MigrationState::class )->get_failure();

		if ( null === $failure ) {
			return '';
		}

		return sprintf(
			/* translators: 1: date and time the migration last failed, 2: error message */
			__( 'The last run stopped on an error at %1$s: %2$s. Starting again retries from the same point. If it keeps stopping, check WooCommerce &gt; Status &gt; Logs.', 'woocommerce' ),
			wc_get_container()->get( Reporter::class )->format_site_time( (int) $failure['at'] ),
			esc_html( (string) $failure['message'] )
		);
	}

	/**
	 * The line that says part of the migration has been set aside.
	 *
	 * A parked section is skipped rather than retried, so without this the screen would show
	 * work outstanding and a run that keeps ending immediately, with nothing to explain it.
	 * Shown whether or not a run is enqueued, since the next run skips the section too.
	 *
	 * @return string
	 */
	private function get_parked_line(): string {
		$parked = wc_get_container()->get( MigrationState::class )->get_parked_sections();

		if ( array() === $parked ) {
			return '';
		}

		return sprintf(
			/* translators: %s: comma-separated list of migration section names */
			__( 'Part of the migration was set aside because its rows could not be recorded either way: %s. Starting the migration again retries it. If it keeps being set aside, check WooCommerce &gt; Status &gt; Logs.', 'woocommerce' ),
			esc_html( implode( ', ', array_keys( $parked ) ) )
		);
	}

	/**
	 * The progress line: where the subscriber migration stands, then whether product and
	 * store settings have been imported.
	 *
	 * Only subscribers carry a number. The rest is a handful of options and product flags, so
	 * a count of them tells a merchant nothing they would act on - whether they have been
	 * imported does.
	 *
	 * @param bool $is_running Whether a background run is enqueued right now.
	 * @return string
	 */
	private function get_progress_line( bool $is_running ): string {
		$migration_state = wc_get_container()->get( MigrationState::class );
		$cached          = $migration_state->get_count( self::SUBSCRIBERS_SECTION );

		if ( null === $cached ) {
			return __( 'Not started yet. Start the migration to see how much there is to move.', 'woocommerce' );
		}

		$as_of     = wc_get_container()->get( Reporter::class )->format_site_time( (int) $cached['at'] );
		$remaining = (int) $cached['count'];
		$progress  = $this->get_progress( $migration_state, $remaining );

		if ( 0 === $remaining ) {
			$headline = sprintf(
				/* translators: %s: site-local date/time the count was taken at */
				__( 'Every subscriber has been checked, as of %s.', 'woocommerce' ),
				$as_of
			);
		} elseif ( $is_running ) {
			$headline = sprintf(
				/* translators: 1: number of subscriber rows left to check, 2: site-local date/time the count was taken at */
				_n(
					'Running now. %1$d subscriber left to check, as of %2$s.',
					'Running now. %1$d subscribers left to check, as of %2$s.',
					$remaining,
					'woocommerce'
				),
				$remaining,
				$as_of
			);
		} else {
			$headline = sprintf(
				/* translators: 1: number of subscriber rows left to check, 2: site-local date/time the count was taken at */
				_n(
					'Paused. %1$d subscriber left to check, as of %2$s. Start the migration to pick up where it stopped.',
					'Paused. %1$d subscribers left to check, as of %2$s. Start the migration to pick up where it stopped.',
					$remaining,
					'woocommerce'
				),
				$remaining,
				$as_of
			);
		}

		$line = '' === $progress ? $headline : $headline . ' ' . $progress;

		return $line . ' ' . $this->get_settings_sections_line( $migration_state );
	}

	/**
	 * How far through the subscriber section a run has got, against the total the last run
	 * started from.
	 *
	 * Empty while no run has recorded a total, since a percentage with no denominator would
	 * have to invent one. Clamped: legacy rows added after a run started can push the
	 * remaining count above the total, and a merchant reading "-3%" learns nothing.
	 *
	 * @param MigrationState $migration_state Run state holding the cached total.
	 * @param int            $remaining       Subscriber rows left to check.
	 * @return string
	 */
	private function get_progress( MigrationState $migration_state, int $remaining ): string {
		$total = $migration_state->get_total( self::SUBSCRIBERS_SECTION );

		if ( null === $total || $total <= 0 ) {
			return '';
		}

		$checked = max( 0, min( $total, $total - $remaining ) );

		return sprintf(
			/* translators: 1: number of subscribers checked, 2: total number of subscribers, 3: percentage checked */
			__( '%1$s of %2$s checked (%3$d%%).', 'woocommerce' ),
			number_format_i18n( $checked ),
			number_format_i18n( $total ),
			(int) floor( ( $checked / $total ) * 100 )
		);
	}

	/**
	 * Whether the settings have been imported, named rather than counted.
	 *
	 * Product settings read from the cached count, since they are a scan like subscribers are:
	 * a section that has never been counted reads as outstanding, because an absent count is
	 * the absence of a measurement, not a measurement of nothing. Store settings are a fixed
	 * set of options `OptionsMigrator` can just look at.
	 *
	 * @param MigrationState $migration_state Run state holding the cached counts.
	 * @return string
	 */
	private function get_settings_sections_line( MigrationState $migration_state ): string {
		$outstanding = array();
		$cached      = $migration_state->get_count( 'product-meta' );

		if ( null === $cached || (int) $cached['count'] > 0 ) {
			$outstanding[] = __( 'product settings', 'woocommerce' );
		}

		if ( ! ( new MigrationRun() )->get_options_migrator()->is_done() ) {
			$outstanding[] = __( 'store settings', 'woocommerce' );
		}

		if ( empty( $outstanding ) ) {
			return __( 'Product settings and store settings have been imported.', 'woocommerce' );
		}

		return sprintf(
			/* translators: %s: comma-separated list of the settings still to import, e.g. "product settings, store settings" */
			__( 'Still to import: %s.', 'woocommerce' ),
			implode( ', ', $outstanding )
		);
	}

	/**
	 * The skipped-and-lost line, or an empty string while a run has nothing to report.
	 *
	 * These counts come from the rows a run has actually walked, so they are complete only once
	 * a run has finished; until then they are what has been seen so far.
	 *
	 * @return string
	 */
	private function get_losses_line(): string {
		$cached_losses = wc_get_container()->get( MigrationState::class )->get_losses();

		if ( null === $cached_losses ) {
			return '';
		}

		$reporter   = wc_get_container()->get( Reporter::class );
		$loss_lines = $reporter->summarize_cached_losses( is_array( $cached_losses['values'] ?? null ) ? $cached_losses['values'] : array() );

		if ( empty( $loss_lines ) ) {
			return '';
		}

		return sprintf(
			/* translators: %s: site-local date/time the skipped populations were last recorded at */
			__( 'Skipped so far, as of %s:', 'woocommerce' ),
			$reporter->format_site_time( (int) $cached_losses['at'] )
		) . ' ' . implode( ' ', $loss_lines );
	}
}
