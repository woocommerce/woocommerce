<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caching;

/**
 * A cache action that can be scheduled to run in the background.
 */
class CacheAction {

	/**
	 * ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	private $cache_key;
	
	/**
	 * Callback.
	 *
	 * @var callable
	 */
	private $callback;
	
	/**
	 * Force refresh.
	 *
	 * @var bool
	 */
	private $force_refresh;
	
	/**
	 * Interval in seconds.
	 *
	 * @var int
	 */
	private $interval_in_seconds;
	
	/**
	 * Is cached.
	 *
	 * @var callable
	 */
	private $is_cached;

	/**
	 * Create a new CacheAction instance.
	 *
	 * @var array {
	 *     An array of action arguments.
	 *
	 *     @type string   $id                  ID used to identify the cache action.
	 *     @type string   $cache_key           Cache key used to check if the cache exists.
	 *     @type callable $callback            The callback function to run when the cache is expired.
	 *     @type bool     $force_refresh       Whether or not the callback should be forcibly run even if the cache exists.
	 *     @type int      $interval_in_seconds How long before this cache should be refreshed.
	 *     @type callable $is_cached           A function that determines if the respective cache still exists.  Should return a boolean.
	 * }
	 */
	public function __construct( $args ) {
		$this->validate_action_args( $args );

		$args = wp_parse_args(
			$args,
			array(
				'cache_key'           => null,
				'force_refresh'       => false,
				'interval_in_seconds' => DAY_IN_SECONDS,
				'is_cached'           => null,
			)
		);

		$this->id                  = $args['id'];
		$this->cache_key           = $args['cache_key'];
		$this->callback            = $args['callback'];
		$this->force_refresh       = $args['force_refresh'];
		$this->interval_in_seconds = $args['interval_in_seconds'];
		$this->is_cached           = $args['is_cached'];
	}

	/**
	 * Validate and throw errors on action args if required args are missing.
	 *
	 * @throws \InvalidArgumentException
	 */
	private function validate_action_args( $args ) {
		if ( ! isset( $args['id' ] ) ) {
			throw new \InvalidArgumentException( __( 'Background cache action must include an ID argument', 'woocommerce' ) );
		}

		if ( ! isset( $args['callback' ] ) || ! is_callable( $args['callback'] ) ) {
			throw new \InvalidArgumentException( __( 'Background cache action must include a callable callback argument', 'woocommerce' ) );
		}
	}

	/**
	 * Get the ID of the action.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the interval of the action.
	 *
	 * @return int
	 */
	public function get_interval() {
		return $this->interval_in_seconds;
	}

	/**
	 * Check if cache exists for a given key.
	 *
	 * @param string $cache_key Cache key.
	 * @return bool
	 */
	public function is_cached() {
		if ( is_callable( $this->is_cached ) ) {
			return call_user_func( $this->is_cached, $this->cache_key );
		}

		if ( $this->cache_key ) {
			return false !== wp_cache_get( $this->cache_key );
		}

		return false;
	}

	/**
	 * Run a background cache callback if the item is no longer cached or force refresh is true.
	 *
	 * @return void
	 */
	public function maybe_run_callback() {
		if ( $this->force_refresh ) {
			call_user_func( $this->callback );
			return;
		}

		if ( ! $this->is_cached() ) {
			call_user_func( $this->callback );
		}
	}
}
