<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

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
 * @since 11.4.0
 */
class ScheduledScan implements RegisterHooksInterface {

	public const RUN_NOW_HOOK = 'woocommerce_agent_readiness_scan';

	public const DAILY_HOOK = 'woocommerce_agent_readiness_daily_scan';

	public const GROUP = 'woocommerce-agent-readiness';

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
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 */
	final public function init( FeaturesController $features_controller, AgentReadiness $agent_readiness ): void {
		$this->features_controller = $features_controller;
		$this->agent_readiness     = $agent_readiness;
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
			as_enqueue_async_action( self::RUN_NOW_HOOK, array(), self::GROUP );
		}
	}

	/**
	 * Whether a requested scan has not finished yet.
	 */
	public function is_pending(): bool {
		return as_has_scheduled_action( self::RUN_NOW_HOOK, array(), self::GROUP );
	}

	/**
	 * Schedule the next daily run, then run the scan and store the report.
	 *
	 * @internal
	 */
	public function run(): void {
		if ( ! $this->features_controller->feature_is_enabled( StatusTab::FEATURE_ID ) ) {
			return;
		}
		as_unschedule_all_actions( self::DAILY_HOOK, array(), self::GROUP );
		as_schedule_single_action( time() + DAY_IN_SECONDS, self::DAILY_HOOK, array(), self::GROUP );

		$this->agent_readiness->refresh_report();
	}
}
