<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Invalidation handler for the coupon code to coupon ids lookup cache.
 *
 * The wc_get_coupon_id_by_code() function caches the ids of the published coupons matching a given
 * code in the 'coupons' object cache group. The entry must not exist when a coupon is
 * not published, otherwise the coupon remains applicable from the stale entry on sites
 * with a persistent object cache.
 *
 * The WC_Coupon CRUD invalidates the entry when a coupon is saved as unpublished or
 * force-deleted, but changes that bypass the CRUD (bulk edit, WP-CLI, direct
 * wp_update_post() or wp_delete_post() calls) only fire WordPress hooks. This class
 * listens to those hooks to keep the cache consistent.
 *
 * @since 11.1.0
 */
class CouponCodeLookupInvalidator {

	/**
	 * Register the WordPress hooks that cover coupon changes made outside the WC_Coupon CRUD.
	 *
	 * @internal
	 *
	 * @return void
	 */
	final public function init(): void {
		add_action( 'transition_post_status', array( $this, 'handle_transition_post_status' ), 10, 3 );
		add_action( 'deleted_post', array( $this, 'handle_deleted_post' ), 10, 2 );
	}

	/**
	 * Get the object cache key holding the ids of the published coupons with the given code.
	 *
	 * The key must be used with the 'coupons' cache group.
	 *
	 * @param string $code Coupon code.
	 * @return string The cache key.
	 */
	public function get_cache_key( string $code ): string {
		// Coupon code allows spaces, which doesn't work well with some cache engines (e.g. memcached), hence the hashing.
		$hashed_code = md5( wc_strtolower( $code ) );
		return \WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $hashed_code;
	}

	/**
	 * Delete the cached coupon ids for the given code.
	 *
	 * @param string $code Coupon code.
	 * @return void
	 */
	public function invalidate( string $code ): void {
		if ( '' === $code ) {
			return;
		}

		wp_cache_delete( $this->get_cache_key( $code ), 'coupons' );
	}

	/**
	 * Invalidate the lookup cache when a coupon transitions to a non-published status.
	 *
	 * @internal
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_transition_post_status( $new_status, $old_status, $post ): void {
		if ( 'shop_coupon' === $post->post_type && 'publish' !== $new_status ) {
			$this->invalidate( wc_format_coupon_code( $post->post_title ) );
		}
	}

	/**
	 * Invalidate the lookup cache when a coupon post is deleted.
	 *
	 * @internal
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function handle_deleted_post( $post_id, $post ): void {
		if ( $post instanceof \WP_Post && 'shop_coupon' === $post->post_type ) {
			$this->invalidate( wc_format_coupon_code( $post->post_title ) );
		}
	}
}
