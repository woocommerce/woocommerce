<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

/**
 * Comments stats caching class, which handles switching between caching APIs (persisting cache vs transients).
 *
 * Transient API is potentially exposed to the race conditions and in order to compensate this a bit, we set
 * the transients expiration withing 24 hours and then external components will need to re-populate the transients.
 */
class CommentCountCache {
	/**
	 * The cache group to use for comment counts.
	 *
	 * @var string
	 */
	private const COMMENT_COUNT_CACHE_GROUP = 'wc_comment_counts';

	/**
	 * Flag indicating usage cache APIs over transients.
	 *
	 * @var bool
	 */
	protected $use_cache_api;

	/**
	 * Flag indicating usage cache APIs over transients.
	 *
	 * @var int
	 */
	private $transient_expiration;

	/**
	 * Constructor, detects which cache API to use.
	 */
	public function __construct() {
		$this->use_cache_api = ( true === wp_using_ext_object_cache() );
		if ( ! $this->use_cache_api ) {
			// Set the transients expiration to the same day midnight (GMT).
			$this->transient_expiration = DAY_IN_SECONDS - ( time() % DAY_IN_SECONDS );
		}
	}

	/**
	 * Checks whether cache entry associated with the provided name presented in the cache.
	 *
	 * @param string $name The name of cache entry.
	 * @return bool
	 */
	public function has( string $name ) {
		return false !== $this->get( $name );
	}

	/**
	 * Retrieves the cache value associated with the provided name.
	 *
	 * @param string $name The name of cache entry.
	 * @return false|int|array
	 */
	public function get( string $name ) {
		return $this->use_cache_api
			? wp_cache_get( $name, self::COMMENT_COUNT_CACHE_GROUP )
			: get_transient( $name );
	}

	/**
	 * Stores the cache entry with the provided name and value.
	 *
	 * @param string    $name  The name of cache entry.
	 * @param int|array $value The cache entry value.
	 * @return bool
	 */
	public function set( string $name, $value ) {
		return $this->use_cache_api
			? wp_cache_set( $name, $value, self::COMMENT_COUNT_CACHE_GROUP )
			: set_transient( $name, $value, $this->transient_expiration );
	}

	/**
	 * Increments the cache entry value by given increment.
	 *
	 * @param string $name      The name of cache entry.
	 * @param int    $increment The value increment to be applied.
	 * @return false|int
	 */
	public function increment( string $name, int $increment = 1 ) {
		if ( $this->use_cache_api ) {
			return wp_cache_incr( $name, $increment, self::COMMENT_COUNT_CACHE_GROUP );
		}

		$transient_value = get_transient( $name );
		if ( false !== $transient_value && false !== set_transient( $name, ++$transient_value, $this->transient_expiration ) ) {
			return $transient_value;
		}

		return false;
	}

	/**
	 * Decrements the cache entry value by given increment.
	 *
	 * @param string $name      The name of cache entry.
	 * @param int    $decrement The value decrement to be applied.
	 * @return false|int
	 */
	public function decrement( string $name, int $decrement = 1 ) {
		if ( $this->use_cache_api ) {
			return wp_cache_decr( $name, $decrement, self::COMMENT_COUNT_CACHE_GROUP );
		}

		$transient_value = get_transient( $name );
		if ( false !== $transient_value && false !== set_transient( $name, --$transient_value, $this->transient_expiration ) ) {
			return $transient_value;
		}

		return false;
	}
}
