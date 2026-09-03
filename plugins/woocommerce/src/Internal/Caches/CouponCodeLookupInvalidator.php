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
 * Invalidation is per coupon and happens in two layers:
 *
 * - On write, the hooks delete the lookup entry of the coupon's stored code whenever the coupon
 *   crosses the `publish` boundary or is deleted. Every other coupon's entry stays warm.
 * - On read, wc_get_coupon_id_by_code() only trusts a cached entry while every coupon id in it
 *   still belongs to a published coupon (see is_lookup_entry_stale()). The check reads the core
 *   post cache, which WordPress cleans on every post write, so it also catches what deleting one
 *   key cannot reach: the same code cached under another key (raw vs. sanitized form, surrounding
 *   whitespace, or a spelling the accent-insensitive database collation treats as equal), and a
 *   lookup that started before the delete and wrote the old id back after it.
 *
 * Known limitations:
 *
 * - Both layers assume that only published coupons resolve, because the core coupon data store
 *   only resolves published coupons. A custom data store registered through the
 *   `woocommerce_data_stores` filter that resolves further statuses would see its entries
 *   rejected on every read (correct, but never served from the cache) and would need its own
 *   invalidation for transitions between two non-publish statuses.
 * - Renaming the code of a coupon that stays published crosses no boundary, so the old code
 *   keeps resolving to the coupon until the cache entry expires or the coupon is unpublished.
 *   This is pre-existing behaviour, not something this class introduced.
 * - wc_get_coupon_id_by_code() hashes the caller's raw input while `post_title` holds the
 *   sanitized code, so for codes with kses-escapable characters the two are different keys.
 *   Publishing a newer coupon under a code that is already cached under such a raw alias does not
 *   reach the alias, so the older coupon keeps winning that lookup until its entry is invalidated.
 * - The read-side check is only as fresh as the core post cache. A post cache entry primed by a
 *   read that raced the unpublishing write vouches for the coupon until the next post write, the
 *   same way it would for any other post type.
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
	 * cache of every WC_Coupon in the same object cache group untouched. Meant for bulk changes
	 * such as migrations that rewrite many codes; single coupon changes use invalidate().
	 *
	 * @return void
	 */
	public function invalidate_lookup_namespace(): void {
		\WC_Cache_Helper::invalidate_cache_group( self::LOOKUP_CACHE_NAMESPACE );
	}

	/**
	 * Check whether a cached lookup entry can no longer be trusted.
	 *
	 * An entry is stale as soon as one of its ids no longer belongs to a published coupon, i.e.
	 * the coupon was unpublished, trashed or deleted after the entry was written. The post is read
	 * through get_post(), which serves it from the core post cache. Every post write cleans that
	 * cache, so the check costs no query on a warm cache and does not depend on which key the
	 * entry was cached under.
	 *
	 * @param array $ids The coupon ids stored in the lookup entry.
	 * @return bool True if the entry must not be used.
	 */
	public function is_lookup_entry_stale( array $ids ): bool {
		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $ids ) ) {
			return true;
		}

		foreach ( $ids as $id ) {
			$post = get_post( $id );

			if ( ! $post instanceof \WP_Post || 'shop_coupon' !== $post->post_type || 'publish' !== $post->post_status ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Delete the lookup entry of a coupon that crosses the publish boundary.
	 *
	 * Only transitions into or out of `publish` can change which ids a code resolves to, so
	 * other status changes (e.g. draft to pending) are ignored. wp_insert_post() reports `new`
	 * as the old status, so a brand-new published coupon takes this path too, which is what lets
	 * a newer coupon published under an already cached code win the lookup.
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

		if ( 'publish' === $old_status || 'publish' === $new_status ) {
			$this->invalidate( $post->post_title );
		}
	}

	/**
	 * Delete the lookup entry of a published coupon post that is deleted.
	 *
	 * Deleting a coupon in any other status cannot strand a lookup entry, since only published
	 * coupons are ever cached.
	 *
	 * @internal
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function handle_deleted_post( $post_id, $post ): void {
		if ( $post instanceof \WP_Post && 'shop_coupon' === $post->post_type && 'publish' === $post->post_status ) {
			$this->invalidate( $post->post_title );
		}
	}
}
