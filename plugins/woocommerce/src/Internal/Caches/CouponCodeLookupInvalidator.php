<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Invalidation handler for the coupon code to coupon id lookup cache.
 *
 * The wc_get_coupon_id_by_code() function caches the ids of the published coupons matching a
 * given code. The entries live in the 'coupons' object cache group, and their keys nest a
 * dedicated 'coupon_code_lookups' prefix namespace inside the group prefix. Rotating the inner
 * namespace invalidates every lookup entry at once without flushing the meta cache shared by
 * every WC_Coupon in the same group, while rotating the outer 'coupons' group prefix (what
 * WC_Cache_Helper::invalidate_cache_group( 'coupons' ) does) keeps working as it did before
 * this class existed. Same nesting as WC_Data::generate_meta_cache_key().
 *
 * Known limitations:
 *
 * - Rotating the namespace throws away the whole store's lookup cache, not just the entries for
 *   the coupon that changed, and the WC_Coupon CRUD trashes and deletes coupons through those
 *   same hooks. A store that creates and removes coupons all day therefore keeps that cache
 *   mostly empty, and every miss runs get_ids_by_code(), which compares `LOWER(post_title)`
 *   against every published coupon. Deleting only the key for that one code would be cheaper,
 *   but it is not enough: the same code can sit in the cache under more than one key (see the
 *   last point below), and a lookup that started before the delete can still write the old id
 *   back after it.
 * - Both hook listeners key off a transition across the `publish` boundary, because the core
 *   coupon data store only resolves published coupons. A custom data store registered through
 *   the `woocommerce_data_stores` filter that resolves further statuses would need its own
 *   invalidation for transitions between two non-publish statuses.
 * - Renaming the code of a coupon that stays published crosses no boundary, so the old code
 *   keeps resolving to the coupon until the cache entry expires or the coupon is unpublished.
 *   This is pre-existing behaviour, not something this class introduced.
 * - wc_get_coupon_id_by_code() hashes the caller's raw input while `post_title` holds the
 *   sanitized code, so for codes with kses-escapable characters the two are different keys.
 *   Entering publish only invalidates the sanitized one; leaving publish rotates the whole
 *   namespace and therefore covers both.
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
	 * Get the object cache group the lookup entries are stored in.
	 *
	 * @return string The cache group.
	 */
	public function get_cache_group(): string {
		return self::CACHE_GROUP;
	}

	/**
	 * Get the object cache key holding the ids of the published coupons with the given code.
	 *
	 * The key must be used with the cache group returned by get_cache_group().
	 *
	 * @param string $code Coupon code.
	 * @return string The cache key.
	 */
	public function get_cache_key( string $code ): string {
		// Coupon code allows spaces, which doesn't work well with some cache engines (e.g. memcached), hence the hashing.
		$hashed_code = md5( wc_strtolower( $code ) );

		// The group prefix is nested so a 'coupons' group invalidation still reaches the lookup entries.
		return \WC_Cache_Helper::get_cache_prefix( self::CACHE_GROUP )
			. \WC_Cache_Helper::get_cache_prefix( self::LOOKUP_CACHE_NAMESPACE )
			. 'coupon_id_from_code_' . $hashed_code;
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
	 * Invalidate the lookup cache when a coupon crosses the publish boundary.
	 *
	 * Only transitions into or out of `publish` can change which ids a code resolves to, so
	 * other status changes (e.g. draft to pending) are ignored. The two directions need
	 * different breadth:
	 *
	 * - Leaving publish rotates the namespace, because the coupon may have been cached under
	 *   more than one representation of its code and every one of them has to go.
	 * - Entering publish only invalidates that one code. Nothing else can start or stop
	 *   resolving because of it, and rotating here would flush the whole store's lookup cache
	 *   on every coupon creation: wp_insert_post() reports `new` as the old status, so a
	 *   brand-new published coupon takes this branch too.
	 *
	 * @internal
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_transition_post_status( $new_status, $old_status, $post ): void {
		if ( ! $post instanceof \WP_Post || 'shop_coupon' !== $post->post_type || $new_status === $old_status ) {
			return;
		}

		if ( 'publish' === $old_status ) {
			$this->invalidate_lookup_namespace();
		} elseif ( 'publish' === $new_status ) {
			$this->invalidate( $post->post_title );
		}
	}

	/**
	 * Rotate the lookup namespace when a published coupon post is deleted.
	 *
	 * Deleting a coupon in any other status cannot strand a lookup entry, since only published
	 * coupons are ever cached. Skipping those keeps the scheduled auto-draft and trash cleanups
	 * from rotating the namespace once per coupon they remove.
	 *
	 * @internal
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function handle_deleted_post( $post_id, $post ): void {
		if ( $post instanceof \WP_Post && 'shop_coupon' === $post->post_type && 'publish' === $post->post_status ) {
			$this->invalidate_lookup_namespace();
		}
	}
}
