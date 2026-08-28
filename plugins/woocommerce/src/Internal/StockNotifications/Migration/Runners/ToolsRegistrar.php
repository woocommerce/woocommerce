<?php
/**
 * ToolsRegistrar class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\EmailSettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\MigratorInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\ProductMetaMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\SettingsMigrator;
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
	 * Section slugs, in the order `MigrationState` and the migrators use them. Not sourced from
	 * the migrators themselves: instantiating all four just to read a slug would defeat the
	 * point of the cached counts this class only ever reads.
	 *
	 * @var string[]
	 */
	private const SECTIONS = array( 'notifications', 'product-meta', 'emails', 'settings' );

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

		$description     = $this->get_description();
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );

		if ( $batch_processor->is_enqueued( MigrationBatchProcessor::class ) ) {
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
					'Cannot start: %d legacy row is still queued for sending by the active Back In Stock Notifications extension, so migrating now risks sending the same notification twice. Let the legacy queue drain, then start again. This cannot be overridden here; run `wp wc bis-migrate run --force --yes` if you have to override it.',
					'Cannot start: %d legacy rows are still queued for sending by the active Back In Stock Notifications extension, so migrating now risks sending the same notification twice. Let the legacy queue drain, then start again. This cannot be overridden here; run `wp wc bis-migrate run --force --yes` if you have to override it.',
					$queued,
					'woocommerce'
				),
				$queued
			);
		}

		// A run always starts from zero: MigrationBatchProcessor only ever advances a
		// cursor, never resets one, so a fresh run has to clear whatever a previous,
		// possibly killed, run left behind - otherwise it resumes behind a stale cursor
		// and strands every row below it.
		$migration_state->reset_all_cursors();

		$notifications_migrator = new NotificationsMigrator( new Reporter() );

		$this->refresh_cached_counts( $migration_state, $notifications_migrator );
		$this->cache_known_losses( $migration_state, $notifications_migrator );

		// Taken once for the whole run rather than per batch, and last of all so nothing after
		// it can bail and leave the lock behind. It is handed back when the last batch drains,
		// when the run is stopped, or when it goes stale.
		if ( ! $migration_state->acquire_lock( 'background migration' ) ) {
			$lock = $migration_state->get_lock();

			return sprintf(
				/* translators: %s: identifier of the process holding the migration lock */
				__( 'A migration run is already in progress via WP-CLI (%s). Stop it there before starting a run here — starting one here does not override it.', 'woocommerce' ),
				$lock['owner'] ?? __( 'unknown', 'woocommerce' )
			);
		}

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
	 * @param MigrationState        $migration_state        Run state to write the refreshed counts into.
	 * @param NotificationsMigrator $notifications_migrator The notifications section, shared with
	 *                                                       cache_known_losses().
	 * @return void
	 */
	private function refresh_cached_counts( MigrationState $migration_state, NotificationsMigrator $notifications_migrator ): void {
		foreach ( $this->build_migrators( $migration_state, $notifications_migrator ) as $migrator ) {
			$migration_state->set_count( $migrator->get_slug(), $migrator->count_remaining() );
		}
	}

	/**
	 * Count and cache the skipped populations at run start.
	 *
	 * These are one `COUNT(*)` each, so they are computed here, where a merchant has just
	 * asked for a run, and never while rendering the Tools list.
	 *
	 * @param MigrationState        $migration_state        Run state to write the counts into.
	 * @param NotificationsMigrator $notifications_migrator The notifications section, already built.
	 * @return void
	 */
	private function cache_known_losses( MigrationState $migration_state, NotificationsMigrator $notifications_migrator ): void {
		$migration_state->set_losses(
			wc_get_container()->get( Reporter::class )->collect_known_losses( $notifications_migrator )
		);
	}

	/**
	 * Build the four section migrators.
	 *
	 * Built here rather than resolved from the container, which cannot reflect over their
	 * constructor arguments - the same reason `MigrationBatchProcessor` builds its own.
	 *
	 * @param MigrationState        $migration_state        Run state the option-backed sections read.
	 * @param NotificationsMigrator $notifications_migrator The notifications section, built by the caller
	 *                                                       so its run-level counters are shared.
	 * @return MigratorInterface[]
	 */
	private function build_migrators( MigrationState $migration_state, NotificationsMigrator $notifications_migrator ): array {
		$reporter = new Reporter();

		return array(
			$notifications_migrator,
			new ProductMetaMigrator( $reporter ),
			new EmailSettingsMigrator( $migration_state, $reporter ),
			new SettingsMigrator( $migration_state, $reporter ),
		);
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
		wc_get_container()->get( MigrationState::class )->release_lock();

		return __( 'Migration stopped. Rows already migrated are untouched; the outstanding count above will still be there next time you start it.', 'woocommerce' );
	}

	/**
	 * Build the tool's description from the cached, per-section counts.
	 *
	 * Reads only `MigrationState`'s cached counts - computed at run start and on section drain,
	 * never here - so this never runs the live candidate-selection query. Rendered whether or not
	 * a run is in progress, so a half-finished migration reads as outstanding work rather than as
	 * an idle screen, and each number carries the timestamp it was computed at rather than being
	 * presented as current.
	 *
	 * @return string
	 */
	private function get_description(): string {
		$migration_state = wc_get_container()->get( MigrationState::class );
		$reporter        = wc_get_container()->get( Reporter::class );

		$lines = array(
			__( 'Migrates subscribers, product settings and email settings from the legacy Back In Stock Notifications extension into the built-in Customer stock notifications feature. Runs in the background over several requests via Action Scheduler.', 'woocommerce' ),
		);

		$section_lines = array();
		$known_total   = 0;
		$has_unknown   = false;

		foreach ( self::SECTIONS as $section ) {
			$cached = $migration_state->get_count( $section );

			if ( null === $cached ) {
				$has_unknown     = true;
				$section_lines[] = sprintf(
					/* translators: %s: migration section slug */
					__( '%s: not yet counted.', 'woocommerce' ),
					$section
				);
				continue;
			}

			$known_total    += $cached['count'];
			$section_lines[] = sprintf(
				/* translators: 1: migration section slug, 2: cached remaining count with its timestamp */
				__( '%1$s: %2$s remaining.', 'woocommerce' ),
				$section,
				$reporter->format_cached_count( $cached['count'], $cached['at'] )
			);
		}

		if ( $has_unknown ) {
			$lines[] = __( 'Outstanding rows are counted the first time a run starts.', 'woocommerce' );
		} else {
			$lines[] = sprintf(
				/* translators: %d: total rows still outstanding across every section */
				_n(
					'%d row is currently outstanding across every section.',
					'%d rows are currently outstanding across every section.',
					$known_total,
					'woocommerce'
				),
				$known_total
			);
		}

		$lines[] = implode( ' ', $section_lines );

		$cached_losses = $migration_state->get_losses();

		if ( null === $cached_losses ) {
			$lines[] = __( 'Rows that will be skipped, and what they cost, are counted the first time a run starts.', 'woocommerce' );
		} else {
			$loss_lines = $reporter->summarize_cached_losses( is_array( $cached_losses['values'] ?? null ) ? $cached_losses['values'] : array() );

			if ( ! empty( $loss_lines ) ) {
				$lines[] = sprintf(
					/* translators: %s: site-local date/time the skipped populations were counted at */
					__( 'Skipped and lost, as of %s:', 'woocommerce' ),
					$reporter->format_site_time( (int) $cached_losses['at'] )
				);
				$lines[] = implode( ' ', $loss_lines );
			}
		}

		return implode( ' ', $lines );
	}
}
