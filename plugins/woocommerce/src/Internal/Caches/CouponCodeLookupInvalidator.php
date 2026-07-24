<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Invalidation handler for the coupon code to coupon id lookup cache.
 *
 * The wc_get_coupon_id_by_code() function caches the ids of the published coupons matching a
 * given code. The entries live in the 'coupons' object cache group, but their keys are built
 * with a dedicated 'coupon_code_lookups' prefix namespace so they can be invalidated on their
 * own, without flushing the meta cache shared by every WC_Coupon in the same group.
 *
 * @since 11.1.0
 */
class CouponCodeLookupInvalidator {

	/**
	 * The object cache group the lookup entries are stored in.
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'coupons';

	/**
	 * The dedicated prefix namespace used to build (and rotate) the lookup keys.
	 *
	 * @var string
	 */
	private const LOOKUP_CACHE_NAMESPACE = 'coupon_code_lookups';

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
	 * The key must be used with the CACHE_GROUP cache group.
	 *
	 * @param string $code Coupon code.
	 * @return string The cache key.
	 */
	public function get_cache_key( string $code ): string {
		// Coupon code allows spaces, which doesn't work well with some cache engines (e.g. memcached), hence the hashing.
		$hashed_code = md5( wc_strtolower( $code ) );
		return \WC_Cache_Helper::get_cache_prefix( self::LOOKUP_CACHE_NAMESPACE ) . 'coupon_id_from_code_' . $hashed_code;
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

		wp_cache_delete( $this->get_cache_key( $code ), self::CACHE_GROUP );
	}

	/**
	 * Invalidate every code to coupon id lookup entry at once.
	 *
	 * Rotates the lookup prefix namespace, which strands all previously cached lookup keys
	 * (regardless of the code representation they were primed under) while leaving the meta
	 * cache of every WC_Coupon in the same object cache group untouched.
	 *
	 * @return void
	 */
	public function invalidate_lookup_namespace(): void {
		\WC_Cache_Helper::invalidate_cache_group( self::LOOKUP_CACHE_NAMESPACE );
	}

	/**
	 * Rotate the lookup namespace when a coupon crosses the publish boundary.
	 *
	 * Only transitions into or out of `publish` can change which ids a code resolves to, so
	 * other status changes (e.g. draft to pending) are ignored to avoid needless cache churn.
	 *
	 * @internal
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_transition_post_status( $new_status, $old_status, $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if (
			'shop_coupon' === $post->post_type
			&& $new_status !== $old_status
			&& ( 'publish' === $new_status || 'publish' === $old_status )
		) {
			$this->invalidate_lookup_namespace();
		}
	}

	/**
	 * Rotate the lookup namespace when a coupon post is deleted.
	 *
	 * @internal
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function handle_deleted_post( $post_id, $post ): void {
		if ( $post instanceof \WP_Post && 'shop_coupon' === $post->post_type ) {
			$this->invalidate_lookup_namespace();
		}
	}
}
