<?php
/**
 * ToolsRegistrar class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
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
	 * The one section whose size is worth a number on this screen.
	 */
	private const SUBSCRIBERS_SECTION = 'notifications';

	/**
	 * Cells in the text progress bar. Text rather than markup: the Tools description is run
	 * through `wp_kses_post()`, and a bar built from characters cannot be filtered away or
	 * misrender against a merchant's admin colour scheme.
	 */
	private const PROGRESS_BAR_CELLS = 20;

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
					'Cannot start: %d legacy row is still queued for sending by the active Back In Stock Notifications extension, so migrating now risks sending the same notification twice. Let the legacy queue drain, then start again. This cannot be overridden here; run `wp wc bis-migrate run --force --yes` if you have to override it.',
					'Cannot start: %d legacy rows are still queued for sending by the active Back In Stock Notifications extension, so migrating now risks sending the same notification twice. Let the legacy queue drain, then start again. This cannot be overridden here; run `wp wc bis-migrate run --force --yes` if you have to override it.',
					$queued,
					'woocommerce'
				),
				$queued
			);
		}

		// The cursor a previous run left behind is kept: it only ever advances, and the
		// per-batch already-migrated lookup means resuming behind it costs a re-scan rather
		// than correctness. Re-walking the whole legacy table is what this migration cannot
		// afford to repeat, so a run that has nothing left to visit does nothing at all.
		$notifications_migrator = new NotificationsMigrator( new Reporter() );

		$this->refresh_cached_counts( $migration_state, $notifications_migrator );

		// The denominator the Tools screen shows progress against: how many legacy rows this
		// run has to walk, whatever the cursor has already been past.
		$migration_state->set_total( 'notifications', $notifications_migrator->count_remaining() );

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
	 * @param NotificationsMigrator $notifications_migrator The notifications section, built by the caller.
	 * @return void
	 */
	private function refresh_cached_counts( MigrationState $migration_state, NotificationsMigrator $notifications_migrator ): void {
		foreach ( $this->build_migrators( $migration_state, $notifications_migrator ) as $migrator ) {
			$slug = $migrator->get_slug();

			$migration_state->set_count( $slug, $migrator->count_remaining( $migration_state->get_cursor( $slug ) ) );
		}
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
	 * Build the tool's description: what it does, where the migration stands, and what it has
	 * skipped so far.
	 *
	 * Reads only `MigrationState`'s cached values - written at run start and on section drain,
	 * never here - so rendering the Tools screen never runs a count query. Every number is
	 * therefore as of the moment it was cached, and is shown with that timestamp once rather
	 * than being presented as current.
	 *
	 * @param bool $is_running Whether a background run is enqueued right now.
	 * @return string
	 */
	private function get_description( bool $is_running ): string {
		$lines = array( __( 'Moves subscribers, product settings and email settings from the Back In Stock Notifications extension into the built-in customer stock notifications. Runs in the background, a batch at a time.', 'woocommerce' ) );

		foreach ( array( $this->get_progress_line( $is_running ), $this->get_losses_line() ) as $line ) {
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}

		return implode( '<br />', $lines );
	}

	/**
	 * The progress line: where the subscriber migration stands, then whether the three
	 * settings sections have been imported.
	 *
	 * Only subscribers carry a number. The other three sections are a handful of options and
	 * product flags each, so a count of them tells a merchant nothing they would act on -
	 * whether they have been imported does.
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
		$progress  = $this->get_progress_bar( $migration_state, $remaining );

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
	 * A text progress bar for the subscriber section, against the total the last run started
	 * from.
	 *
	 * Empty while no run has recorded a total, since a bar with no denominator would have to
	 * invent one. Progress is clamped: legacy rows added after a run started can push the
	 * remaining count above the total, and a merchant reading "-3%" learns nothing.
	 *
	 * @param MigrationState $migration_state Run state holding the cached total.
	 * @param int            $remaining       Subscriber rows left to check.
	 * @return string
	 */
	private function get_progress_bar( MigrationState $migration_state, int $remaining ): string {
		$total = $migration_state->get_total( self::SUBSCRIBERS_SECTION );

		if ( null === $total || $total <= 0 ) {
			return '';
		}

		$checked = max( 0, min( $total, $total - $remaining ) );
		$percent = (int) floor( ( $checked / $total ) * 100 );
		$filled  = (int) round( ( $percent / 100 ) * self::PROGRESS_BAR_CELLS );

		return sprintf(
			/* translators: 1: text progress bar, 2: percentage checked, 3: number of subscribers checked, 4: total number of subscribers */
			__( '%1$s %2$d%% (%3$s of %4$s checked).', 'woocommerce' ),
			str_repeat( '▮', $filled ) . str_repeat( '▯', self::PROGRESS_BAR_CELLS - $filled ),
			$percent,
			number_format_i18n( $checked ),
			number_format_i18n( $total )
		);
	}

	/**
	 * Whether the three settings sections have been imported, named rather than counted.
	 *
	 * A section that has never been counted reads as outstanding: an absent count is the
	 * absence of a measurement, not a measurement of nothing.
	 *
	 * @param MigrationState $migration_state Run state holding the cached counts.
	 * @return string
	 */
	private function get_settings_sections_line( MigrationState $migration_state ): string {
		$outstanding = array();

		foreach ( Constants::SECTION_ORDER as $section ) {
			if ( self::SUBSCRIBERS_SECTION === $section ) {
				continue;
			}

			$cached = $migration_state->get_count( $section );

			if ( null === $cached || (int) $cached['count'] > 0 ) {
				$outstanding[] = $this->get_section_label( $section );
			}
		}

		if ( empty( $outstanding ) ) {
			return __( 'Product settings, email settings and general settings have been imported.', 'woocommerce' );
		}

		return sprintf(
			/* translators: %s: comma-separated list of the settings still to import, e.g. "email settings, general settings" */
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

	/**
	 * The merchant-facing name of one settings section, worded to sit mid-sentence. The slugs
	 * themselves are internal: `product-meta` names a database column, not anything a merchant
	 * configured.
	 *
	 * @param string $section Section slug.
	 * @return string
	 */
	private function get_section_label( string $section ): string {
		switch ( $section ) {
			case 'product-meta':
				return __( 'product settings', 'woocommerce' );
			case 'emails':
				return __( 'email settings', 'woocommerce' );
			default:
				return __( 'general settings', 'woocommerce' );
		}
	}
}
