<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caching;

/**
 * A thin wrapper around ActionScheduler to handle background
 * caching jobs on intervals and cache expiration.
 */
class BackgroundCaching {

	/**
	 * Action scheduler hook name.
	 */
	const BACKGROUND_HOOK_NAME = 'woocommerce_background_caching';

	/**
	 * Action schedule unschedule hook name.
	 */
	const UNSCHEDULE_HOOK_NAME = 'woocommerce_background_caching_unschedule';
	
	/**
	 * Registered actions.
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
	private $registered_actions = array();

	/**
	 * Initialize the background scheduler.
	 */
	public function init() {
		add_action( self::BACKGROUND_HOOK_NAME, array( $this, 'run_callback' ) );
		add_action( self::UNSCHEDULE_HOOK_NAME, array( $this, 'unschedule_action' ) );
	}
	
	/**
	 * Register an action to run in the background.
	 *
	 * @param array $args {
	 *     An array of action arguments.
	 *
	 *     @type string   $cache_key           Cache key used to check if the cache exists.
	 *     @type callable $callback            The callback function to run in the background.
	 *     @type bool     $force_refresh       Whether or not the callback should be forcibly run even if the cache exists.
	 *     @type int      $interval_in_seconds How long between scheduling background refreshes.
	 *     @type callable $is_cached           A function that determines if the respective cache still exists.  Should return a boolean.
	 * }
	 * @return void
	 */
	public function register_action( $args ) {
		$this->validate_action_args( $args );

		$args = wp_parse_args(
			$args,
			array(
				'cache_key'           => null,
				'force_refresh'       => false,
				'interval_in_seconds' => DAY_IN_SECONDS,
				'is_cached'           => array( $this, 'is_cached' ),
			)
		);

		if ( function_exists( 'as_schedule_recurring_action' ) && false === as_next_scheduled_action( self::BACKGROUND_HOOK_NAME, array( $args['id'] ) ) ) {
			as_schedule_recurring_action( time(), $args['interval_in_seconds'], self::BACKGROUND_HOOK_NAME, array( $args['id'] ) );
		}

		$this->registered_actions[ $args['id'] ] = $args;
	}

	/**
	 * Validate and throw errors on action args if required args are missing.
	 *
	 * @throws \Exception
	 */
	private function validate_action_args( $args ) {
		if ( ! isset( $args['id' ] ) ) {
			throw new \Exception( __( 'Background cache action must include an ID argument', 'woocommerce' ) );
		}

		if ( ! isset( $args['callback' ] ) ) {
			throw new \Exception( __( 'Background cache action must include a callback argument', 'woocommerce' ) );
		}
	}

	/**
	 * Default method to check if cache exists for a given key.
	 *
	 * @param string $cache_key Cache key.
	 * @return bool
	 */
	private function is_cached( $cache_key ) {
		return false !== wp_cache_get( $cache_key );
	}

	/**
	 * Unschedule an action.
	 *
	 * @param string $id Action ID.
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
		$action = $this->registered_actions[ $id ] ?? null;

		if ( ! $action ) {
			as_enqueue_async_action( self::UNSCHEDULE_HOOK_NAME, array( $id ) );
			return;
		}

		if ( $action[ 'force_refresh' ] ) {
			call_user_func( $action['callback'] );
			return;
		}

		if ( ! call_user_func_array( $action[ 'is_cached' ], array( $action['cache_key'] ) ) ) {
			call_user_func( $action['callback'] );
		}
	}
}
