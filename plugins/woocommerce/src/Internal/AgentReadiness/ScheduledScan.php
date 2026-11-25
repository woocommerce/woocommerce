<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Utilities\ActionSchedulerUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the readiness scan in Action Scheduler instead of the page request,
 * and keeps the stored report fresh with one run a day.
 *
 * Each run schedules the next one for the coming night, on the store's clock
 * (next_daily_run()), unless the merchant turned daily checks off (DAILY_OPTION),
 * so nothing is queued until a
 * merchant or the REST API asks for the first scan, and no request pays for a
 * scheduling check. The next run is scheduled before the scan, so a scan that
 * fails or times out does not end the chain. Cancelling the scheduled actions
 * under Tools > Scheduled Actions stops the chain until the next manual check.
 *
 * A requested scan that has not started 10 minutes after it was due, while no
 * other scheduled action has finished either, is taken as stalled (Action
 * Scheduler is not running on the site). It stays queued: the scan never runs
 * in a page or REST request, where a large catalog could run out of time or
 * slow the site. Splitting the product pass into one action per batch is
 * WOOAI-1661.
 *
 * @since 11.4.0
 */
class ScheduledScan implements RegisterHooksInterface {

	public const RUN_NOW_HOOK = 'woocommerce_agent_readiness_scan';

	public const DAILY_HOOK = 'woocommerce_agent_readiness_daily_scan';

	/**
	 * Hook that tests the next batch of a scan, with the scan's id.
	 */
	public const NEXT_HOOK = 'woocommerce_agent_readiness_scan_next';

	/**
	 * Seconds before a batch that ended early, as on a timeout, is tried again.
	 */
	public const RETRY_AFTER = 10 * MINUTE_IN_SECONDS;

	public const GROUP = 'woocommerce-agent-readiness';

	/**
	 * Option that holds whether the store is checked every day: 'no' when the merchant turned it off.
	 */
	public const DAILY_OPTION = 'woocommerce_agent_readiness_daily_scan';

	/**
	 * Hours of the night, on the store's clock, between which the daily scan is scheduled.
	 */
	public const NIGHT_FROM = 1;

	public const NIGHT_TO = 5;

	/**
	 * The least time between a run and the daily run it schedules.
	 */
	public const MIN_GAP = 6 * HOUR_IN_SECONDS;

	public const STALLED_AFTER = 10 * MINUTE_IN_SECONDS;

	/**
	 * How long a daily scan marked running still counts as pending. A process killed mid-scan
	 * leaves it running until Action Scheduler's cleanup marks it failed (after 5 minutes, on a
	 * queue run), so this frees Run checks even when that cleanup does not run.
	 */
	public const RUNNING_FOR = 10 * MINUTE_IN_SECONDS;


	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Rules from the published rules file.
	 *
	 * @var RemoteRules
	 */
	private RemoteRules $remote_rules;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param AgentReadiness $agent_readiness     Readiness service.
	 * @param RemoteRules    $remote_rules        Rules from the published rules file.
	 */
	final public function init( AgentReadiness $agent_readiness, RemoteRules $remote_rules ): void {
		$this->agent_readiness = $agent_readiness;
		$this->remote_rules    = $remote_rules;
	}

	/**
	 * Register the scan actions.
	 */
	public function register(): void {
		add_action( self::RUN_NOW_HOOK, array( $this, 'run' ) );
		add_action( self::DAILY_HOOK, array( $this, 'run' ) );
		add_action( self::NEXT_HOOK, array( $this, 'run_batch' ) );
	}

	/**
	 * Queue a scan unless one is already waiting or running.
	 */
	public function queue(): void {
		// A scan already testing batches is replaced: the new one starts over with the current catalog.
		if ( ! $this->is_starting() ) {
			// A single action due now rather than an async one, which has no date to tell a stalled scan by.
			// Unique, so two requests that both found nothing pending still queue one scan.
			as_schedule_single_action( time(), self::RUN_NOW_HOOK, array(), self::GROUP, true );
		}
	}

	/**
	 * Whether a scan has not finished yet: requested, testing batches, or a daily scan that
	 * started in the last RUNNING_FOR seconds. The next daily scan, still waiting for its date, doesn't count.
	 */
	public function is_pending(): bool {
		return $this->is_starting() || as_has_scheduled_action( self::NEXT_HOOK, null, self::GROUP );
	}

	/**
	 * Whether a requested scan has not started its batches yet, or the daily scan started running in the last RUNNING_FOR seconds.
	 */
	private function is_starting(): bool {
		if ( ActionSchedulerUtil::has_scheduled_action( self::RUN_NOW_HOOK, array(), self::GROUP ) ) {
			return true;
		}
		$running_daily = as_get_scheduled_actions(
			array(
				'hook'             => self::DAILY_HOOK,
				'group'            => self::GROUP,
				'status'           => \ActionScheduler_Store::STATUS_RUNNING,
				'modified'         => new \DateTime( '@' . ( time() - self::RUNNING_FOR ) ),
				'modified_compare' => '>=',
				'per_page'         => 1,
			),
			'ids'
		);
		return array() !== $running_daily;
	}

	/**
	 * Whether a requested scan is still waiting to start STALLED_AFTER seconds
	 * after it was due, and no other action has finished, failed or started in that
	 * time either. A queue that is only behind is still running, so its scan stays queued.
	 */
	public function is_stalled(): bool {
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return false;
		}
		// The timestamp of a pending action; true while it runs.
		$due = as_next_scheduled_action( self::RUN_NOW_HOOK, array(), self::GROUP );
		$due = false === $due ? as_next_scheduled_action( self::NEXT_HOOK, null, self::GROUP ) : $due;
		if ( ! is_int( $due ) || time() - $due <= self::STALLED_AFTER ) {
			return false;
		}
		$recently_run = as_get_scheduled_actions(
			array(
				'status'           => array( \ActionScheduler_Store::STATUS_COMPLETE, \ActionScheduler_Store::STATUS_FAILED, \ActionScheduler_Store::STATUS_RUNNING ),
				'modified'         => new \DateTime( '@' . ( time() - self::STALLED_AFTER ) ),
				'modified_compare' => '>=',
				'per_page'         => 1,
			),
			'ids'
		);
		return empty( $recently_run );
	}

	/**
	 * When daily checks are on, schedule the next one, then fetch the rules file and start the scan, replacing one in progress.
	 * A daily run does nothing when the saved switch is off, so one queued before the merchant turned it off does not scan.
	 * Each batch of products runs in its own action, then the last stores the report.
	 *
	 * @internal
	 */
	public function run(): void {
		// The row in the database, not the value this request already read. The merchant may have saved since.
		if ( self::DAILY_HOOK === current_action() && ! self::daily_enabled_saved() ) {
			as_unschedule_all_actions( self::DAILY_HOOK, array(), self::GROUP );
			return;
		}

		$this->sync_daily_run();

		$this->remote_rules->refresh( array_keys( $this->agent_readiness->get_default_rules() ) );
		as_unschedule_all_actions( self::NEXT_HOOK );
		as_schedule_single_action( time(), self::NEXT_HOOK, array( $this->agent_readiness->start_refresh() ), self::GROUP );
	}

	/**
	 * Whether the store is checked every day. On unless the merchant turned it off.
	 *
	 * @since 11.4.0
	 */
	public static function daily_enabled(): bool {
		return 'no' !== get_option( self::DAILY_OPTION, 'yes' );
	}

	/**
	 * Turn the daily scan on or off. Off removes the scheduled run, and checks only run on request.
	 * On schedules the next run once the store has been checked, or when a check is already running.
	 * Before the first check, nothing is queued.
	 *
	 * @since 11.4.0
	 *
	 * @param bool $enable Whether to check the store every day.
	 */
	public function set_daily_enabled( bool $enable ): void {
		update_option( self::DAILY_OPTION, $enable ? 'yes' : 'no', false );
		// A check already running queued its next run before it saved a report. Keep that run.
		if ( $enable && null === $this->agent_readiness->get_cached_report() && ! $this->is_pending() ) {
			as_unschedule_all_actions( self::DAILY_HOOK, array(), self::GROUP );
			return;
		}
		$this->sync_daily_run();
	}

	/**
	 * Queue the next nightly run when daily checks are on, and remove it when they are off.
	 * The saved option is read again after scheduling, so a switch turned off while this runs drops the queued run.
	 */
	private function sync_daily_run(): void {
		as_unschedule_all_actions( self::DAILY_HOOK, array(), self::GROUP );
		if ( ! self::daily_enabled_saved() ) {
			return;
		}
		as_schedule_single_action( self::next_daily_run(), self::DAILY_HOOK, array(), self::GROUP );
		$this->drop_queued_daily_run_if_off();
	}

	/**
	 * Remove the run just queued when daily checks were turned off while it was stored.
	 */
	private function drop_queued_daily_run_if_off(): void {
		if ( self::daily_enabled_saved() ) {
			return;
		}
		as_unschedule_all_actions( self::DAILY_HOOK, array(), self::GROUP );
	}

	/**
	 * Whether daily checks are on in the database.
	 * get_option() would repeat the value this request already read, which can be from before the merchant saved.
	 */
	private static function daily_enabled_saved(): bool {
		global $wpdb;

		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				self::DAILY_OPTION
			)
		);

		return ! is_string( $value ) || 'no' !== $value;
	}

	/**
	 * When the next daily scan is due: the next time the store's clock shows its night slot.
	 * The slot is a minute between NIGHT_FROM and NIGHT_TO, fixed per store by its address,
	 * so stores on one host don't all start together. A run that starts late doesn't move it.
	 * A slot less than MIN_GAP away is left for the night after, so a check started late in
	 * the evening or at night isn't followed by a second one within hours.
	 *
	 * @since 11.4.0
	 *
	 * @param int|null $time The current time, or null for now.
	 */
	public static function next_daily_run( ?int $time = null ): int {
		$now    = ( new \DateTimeImmutable( '@' . ( $time ?? time() ) ) )->setTimezone( wp_timezone() );
		$minute = self::NIGHT_FROM * 60 + absint( crc32( home_url() ) ) % ( ( self::NIGHT_TO - self::NIGHT_FROM ) * 60 );
		$slot   = $now->setTime( intdiv( $minute, 60 ), $minute % 60 );
		// Twice at most: tonight's slot may be past, and tomorrow's may still be too close.
		while ( $slot->getTimestamp() - $now->getTimestamp() < self::MIN_GAP ) {
			$slot = $slot->modify( '+1 day' );
		}
		return $slot->getTimestamp();
	}

	/**
	 * Test one batch of a scan, then queue the next, or store the report after the last.
	 *
	 * @internal
	 *
	 * @param mixed $chain Scan id. Untyped, so a stray call without one does nothing instead of failing.
	 */
	public function run_batch( $chain = '' ): void {
		$chain = is_string( $chain ) ? $chain : '';
		// Runs only if this action ends early, as on a timeout, and goes on from the last finished batch.
		as_schedule_single_action( time() + self::RETRY_AFTER, self::NEXT_HOOK, array( $chain ), self::GROUP );
		$more = $this->agent_readiness->continue_refresh( $chain );
		as_unschedule_all_actions( self::NEXT_HOOK, array( $chain ), self::GROUP );
		if ( $more ) {
			as_schedule_single_action( time(), self::NEXT_HOOK, array( $chain ), self::GROUP );
		}
	}
}
