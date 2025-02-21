<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caching;

/**
 * A thin wrapper around ActionScheduler to handle background
 * caching jobs on intervals and cache expiration.
 */
class BackgroundCache {

	/**
	 * Action scheduler hook name.
	 */
	const BACKGROUND_HOOK_NAME = 'woocommerce_background_caching';

	/**
	 * Action schedule unschedule hook name.
	 */
	const UNSCHEDULE_HOOK_NAME = 'woocommerce_background_caching_unschedule';
	
	/**
	 * Scheduled actions.
	 *
	 * @var array {
	 *     An array of action arguments.
	 *
	 *     @type string   $cache_key           Cache key used to check if the cache exists.
	 *     @type callable $callback            The callback function to run in the background.
	 *     @type bool     $force_refresh       Whether or not the callback should be forcibly run even if the cache exists.
	 *     @type int      $interval_in_seconds How long between scheduling background refreshes.
	 *     @type callable $is_cached           A function that determines if the respective cache still exists.  Should return a boolean.
	 * }
	 */
	private $scheduled_actions = array();

	/**
	 * Initialize the background scheduler.
	 */
	public function init() {
		add_action( self::BACKGROUND_HOOK_NAME, array( $this, 'run_callback' ) );
		add_action( self::UNSCHEDULE_HOOK_NAME, array( $this, 'unschedule_action' ) );
	}

	/**
	 * Schedule an action to run in the background.
	 *
	 * @param CacheAction
	 * @return void
	 */
	public function schedule_action( $background_cache_action ) {
		if ( ! is_a( $background_cache_action, CacheAction::class ) ) {
			throw new \InvalidArgumentException( 'Background cache action must be an instance of CacheAction' );
		}

		if ( function_exists( 'as_schedule_recurring_action' ) && false === as_next_scheduled_action( self::BACKGROUND_HOOK_NAME, array( $background_cache_action->get_id() ) ) ) {
			as_schedule_recurring_action( time(), $background_cache_action->get_interval(), self::BACKGROUND_HOOK_NAME, array( $background_cache_action->get_id() ) );
			$this->scheduled_actions[ $background_cache_action->get_id() ] = $background_cache_action;
		}

	}

	/**
	 * Unschedule an action.
	 *
	 * @param string $id Action ID.
	 * @return void
	 */
	public function unschedule_action( $id ) {
		as_unschedule_action( self::BACKGROUND_HOOK_NAME, array( $id ) );
	}

	/**
	 * Run a background cache callback if the item is no longer cached or force refresh is true.
	 * Any actions that have been removed will be unscheduled.
	 *
	 * @param string $id Action ID.
	 * @return void
	 */
	public function run_callback( $id ) {
		$action = $this->scheduled_actions[ $id ] ?? null;

		if ( ! $action ) {
			as_enqueue_async_action( self::UNSCHEDULE_HOOK_NAME, array( $id ) );
			return;
		}

		$action->maybe_run_callback();
	}
}
