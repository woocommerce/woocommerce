<?php

/**
 * WC Tombstones
 *
 * Track WooCommerce objects that have been permanently deleted.
 */
class WC_Tombstones {
	const OPTION = 'woocommerce_deleted_posts';

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	public static $instance = null;

	/**
	 * A list of current tombstones.
	 *
	 * @var array
	 */
	private $tombstones = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->tombstones = get_option( self::OPTION, array() );
	}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 *  Initialize actions and hooks.
	 *
	 *  @internal
	 */
	final public function init() {
		add_action( 'deleted_post', array( $this, 'deleted_post' ), 10, 2 );
	}

	/**
	 * Number of days to track tombstones
	 *
	 * @return int Number of days to track tombstones
	 */
	private static function auto_purge_days() {
		return max( 10, EMPTY_TRASH_DAYS );
	}

	/**
	 * The safe sync threshold
	 *
	 * If the last sync was before this threshold, the best coarse of action is
	 * to purge any local cache and resync. We can't be sure that a continuous
	 * sync will result in the complete data set.
	 *
	 * @return int Timestamp of the sync threshold
	 */
	public static function auto_purge_threshold() {
		$threshold = self::auto_purge_days();
		$time      = time() - ( $threshold * DAY_IN_SECONDS );
		return $time;
	}

	/**
	 * A list of unexpired tombstones
	 *
	 * @return array List of tombstones that have not expired
	 */
	private function tombstones() {
		return array_filter(
			self::instance()->tombstones,
			function( $time ) {
				$threshold = self::auto_purge_days();
				return $threshold < ( time() - $time );
			}
		);
	}

	/**
	 * Get the tombstone IDs
	 *
	 * @param array $filters Query filters.
	 * @return array
	 */
	public static function ids( $filters ) {
		return array_keys( self::get( $filters ) );
	}

	/**
	 * Get the tombstone objects
	 *
	 * @param array $filters Query filters.
	 * @return array
	 */
	public static function get( array $filters = array() ) {
		$tombstones = self::instance()->tombstones();

		if ( isset( $filters['modified_before'] ) ) {
			$modified_before = strtotime( $filters['modified_before'] );

			$tombstones = array_filter(
				$tombstones,
				function( $time ) use ( $modified_before ) {
					return $modified_before > $time;
				}
			);
		}

		if ( isset( $filters['modified_after'] ) ) {
			$modified_after = strtotime( $filters['modified_after'] );

			$tombstones = array_filter(
				$tombstones,
				function( $time ) use ( $modified_after ) {
					return $modified_after < $time;
				}
			);
		}

		return $tombstones;
	}

	/**
	 * First tombstone to match a given set of filters
	 *
	 * @param array $filters Query filters.
	 * @return array The post ID and timestamp of the first tombstone to match a given set of filters
	 */
	public static function first( array $filters = array() ) {
		$tombstones = self::get( $filters );

		// Sort in descending order so we can pop the last element.
		arsort( $tombstones, SORT_NUMERIC );
		$id   = array_pop( array_keys( $tombstones ) );
		$time = array_pop( $tombstones );

		return array(
			'id'   => $id,
			'time' => $time,
		);
	}

	/**
	 * Last tombstone to match a given set of filters
	 *
	 * @param array $filters Query filters.
	 * @return array The post ID and timestamp of the last tombstone to match a given set of filters
	 */
	public static function last( array $filters = array() ) {
		$tombstones = self::get( $filters );

		// Sort in descending order so we can pop the last element.
		asort( $tombstones, SORT_NUMERIC );
		$id   = array_pop( array_keys( $tombstones ) );
		$time = array_pop( $tombstones );

		return array(
			'id'   => $id,
			'time' => $time,
		);
	}

	/**
	 * Handler to track post objects as they're deleted.
	 *
	 * @param int     $id Post ID that has been deleted.
	 * @param WP_Post $post Post object that has been deleted.
	 */
	public function deleted_post( $id, $post ) {
		$post_types = array(
			'shop_order',
			'product',
		);

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		// TODO: Only log posts that were manually deleted.
		$tombstones        = $this->tombstones();
		$tombstones[ $id ] = time();

		update_option( self::OPTION, $this->tombstones );
	}
}

WC_Tombstones::instance()->init();
