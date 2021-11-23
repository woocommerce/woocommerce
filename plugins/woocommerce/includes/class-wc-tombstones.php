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

	private $tombstones = array();

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

	public static function auto_purge_days() {
		return max( 10, EMPTY_TRASH_DAYS );
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
		$tombstones = self::instance()->tombstones;

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

		return array_filter(
			$tombstones,
			function( $time ) {
				$threshold = self::auto_purge_days();
				return $threshold < ( time() - $time );
			}
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
		$this->tombstones[ $id ] = time();

		update_option( self::OPTION, $this->tombstones );
	}
}

WC_Tombstones::instance()->init();
