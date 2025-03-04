<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caching;

/**
 * A thin wrapper around ActionScheduler to handle background
 * scheduling jobs on intervals.
 */
class BackgroundScheduler {

	/**
	 * Action scheduler hook name.
	 */
	const BACKGROUND_HOOK_NAME = 'woocommerce_background_scheduler';

	/**
	 * Action scheduler group name.
	 */
	const BACKGROUND_GROUP_NAME = 'woocommerce-background-scheduler';

	/**
	 * Scheduled actions.
	 *
	 * @var array {
	 *     An array of action arguments.
	 *
	 *     @type string   $action_name         Action name.
	 *     @type callable $callback            The callback function to run in the background.
	 *     @type int      $interval_in_seconds How long between scheduling background refreshes.
	 * }
	 */
	private $registered_actions = array();

	/**
	 * Initialize the background scheduler.
	 */
	public function init() {
		$this->init_scheduler_hooks();
		add_action( self::BACKGROUND_HOOK_NAME, array( $this, 'schedule_actions_from_registered_actions' ) );
	}

	/**
	 * Initialize the scheduler hooks.
	 */
	private function init_scheduler_hooks() {
		if ( function_exists( 'as_schedule_recurring_action' ) && false === as_has_scheduled_action( self::BACKGROUND_HOOK_NAME ) ) {
			as_schedule_recurring_action( time(), HOUR_IN_SECONDS, self::BACKGROUND_HOOK_NAME, array() );
		}
	}

	/**
	 * Schedule an action to run in the background.
	 *
	 * @param BackgroundAction $background_action
	 * @return void
	 */
	public function register_action( $background_action ) {
		if ( ! is_a( $background_action, BackgroundAction::class ) ) {
			throw new \InvalidArgumentException( 'Background cache action must be an instance of BackgroundAction' );
		}

		$this->registered_actions[ $background_action->get_hook() ] = $background_action;
	}

	/**
	 * Schedule actions from registered actions.
	 *
	 * @return void
	 */
	public function schedule_actions_from_registered_actions() {
		foreach ( $this->registered_actions as $action ) {
			$action->schedule();
		}
		$this->clean_up_unregistered_actions();
	}

	/**
	 * Clean up unregistered actions.
	 *
	 * @return void
	 */
	public function clean_up_unregistered_actions() {
		$scheduled_actions = as_get_scheduled_actions( array(
			'group'  => self::BACKGROUND_GROUP_NAME,
			'status' => \ActionScheduler_Store::STATUS_PENDING,
		) );

		foreach ( $scheduled_actions as $action ) {
			if ( ! isset( $this->registered_actions[ $action->get_hook() ] ) ) {
				as_unschedule_action( $action->get_hook() );
			}
		}
	}
}
