<?php

/**
 * WC Tombstones
 *
 * Track WooCommerce objects that have been permanently deleted.
 */
class WC_Tombstones {
	const OPTION = 'woocommerce_deleted_posts';

	/**
	 *  Initialize actions and hooks.
	 *
	 *  @internal
	 */
	final public static function init() {
		add_action( 'deleted_post', array( __CLASS__, 'deleted_post' ), 10, 2 );
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
		$tombstones = get_option( self::OPTION, array() );

		if ( $filters['modified_before'] ) {
			$modified_before = strtotime( $filters['modified_before'] );

			$tombstones = array_filter(
				$tombstones,
				function( $time ) use ( $modified_before ) {
					return $modified_before > $time;
				}
			);
		}

		if ( $filters['modified_after'] ) {
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
				$threshold = EMPTY_TRASH_DAYS;
				if ( $threshold < 10 ) {
					$threshold = 30;
				}

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
	public static function deleted_post( $id, $post ) {
		$post_types = array(
			'shop_order',
			'product',
		);

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		// TODO: Only log posts that were manually deleted.
		$tombstones        = self::get();
		$tombstones[ $id ] = time();

		update_option( self::OPTION, $tombstones );
	}
}

WC_Tombstones::init();
