<?php

class WC_Tombstones {
	const option = 'woocommerce_deleted_posts';

	public static function init() {
		add_action( 'deleted_post', array( __CLASS__, 'deleted_post' ), 10, 2 );
	}

	public static function ids() {
		return array_keys( self::get() );
	}

	public static function get() {
		$tombstones = get_option( self::option, array() );
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

		update_option( self::option, $tombstones );
	}
}

WC_Tombstones::init();
