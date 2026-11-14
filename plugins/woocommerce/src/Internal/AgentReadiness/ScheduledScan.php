<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Utilities\ActionSchedulerUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the readiness scan in Action Scheduler instead of the page request,
 * and keeps the stored report fresh with one run a day.
 *
 * Each run schedules the next one a day later, so nothing is queued until a
 * merchant or the REST API asks for the first scan, and no request pays for a
 * scheduling check. The next run is scheduled before the scan, so a scan that
 * fails or times out does not end the chain; a run that finds the feature
 * disabled does.
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

	public const GROUP = 'woocommerce-agent-readiness';

	public const STALLED_AFTER = 10 * MINUTE_IN_SECONDS;

	/**
	 * How long a daily scan marked running still counts as pending. A process killed mid-scan
	 * leaves it running until Action Scheduler's cleanup marks it failed (after 5 minutes, on a
	 * queue run), so this frees Run check even when that cleanup does not run.
	 */
	public const RUNNING_FOR = 10 * MINUTE_IN_SECONDS;

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

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
	 * @param FeaturesController $features_controller Features controller.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 * @param RemoteRules        $remote_rules        Rules from the published rules file.
	 */
	final public function init( FeaturesController $features_controller, AgentReadiness $agent_readiness, RemoteRules $remote_rules ): void {
		$this->features_controller = $features_controller;
		$this->agent_readiness     = $agent_readiness;
		$this->remote_rules        = $remote_rules;
	}

	/**
	 * Register the scan actions.
	 */
	public function register(): void {
		add_action( self::RUN_NOW_HOOK, array( $this, 'run' ) );
		add_action( self::DAILY_HOOK, array( $this, 'run' ) );
	}

	/**
	 * Queue a scan unless one is already waiting or running.
	 */
	public function queue(): void {
		if ( ! $this->is_pending() ) {
			// A single action due now rather than an async one, which has no date to tell a stalled scan by.
			// Unique, so two requests that both found nothing pending still queue one scan.
			as_schedule_single_action( time(), self::RUN_NOW_HOOK, array(), self::GROUP, true );
		}
	}

	/**
	 * Whether a requested scan has not finished yet, or the daily scan started running in the
	 * last RUNNING_FOR seconds. The next daily scan, still waiting for its date, doesn't count.
	 */
	public function is_pending(): bool {
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
	 * Schedule the next daily run, fetch the rules file, then run the scan and store the report.
	 *
	 * @internal
	 */
	public function run(): void {
		if ( ! $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID ) ) {
			return;
		}
		as_unschedule_all_actions( self::DAILY_HOOK, array(), self::GROUP );
		as_schedule_single_action( time() + DAY_IN_SECONDS, self::DAILY_HOOK, array(), self::GROUP );

		$this->remote_rules->refresh( array_keys( $this->agent_readiness->get_default_rules() ) );
		$this->agent_readiness->refresh_report();
	}
}
