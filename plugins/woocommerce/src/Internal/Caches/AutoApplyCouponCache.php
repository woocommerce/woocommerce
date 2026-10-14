<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Cache of the codes of the coupons flagged to be applied automatically.
 *
 * WC_Cart::get_auto_apply_coupon_codes() resolves the published coupons carrying the `auto_apply`
 * meta once and keeps the result in a transient, so the lookup does not run on every totals
 * recalculation. A transient rather than the object cache, so the result survives across requests
 * on sites without a persistent object cache.
 *
 * Invalidation happens in two layers:
 *
 * - WC_Coupon_Data_Store_CPT drops the entry whenever a coupon is saved, trashed or deleted
 *   through the CRUD, which covers the admin screens and the REST API.
 * - The hooks this class registers cover what the CRUD cannot see: a coupon published or
 *   unpublished by a bulk or quick edit, and the `auto_apply` meta written directly by an
 *   importer, a migration or WP-CLI. Without them a newly flagged coupon would not apply until
 *   the transient expired, with nothing in the admin to explain why.
 *
 * @since 11.3.0
 */
class AutoApplyCouponCache {

	/**
	 * The transient holding the cached coupon codes.
	 *
	 * @var string
	 */
	public const TRANSIENT_KEY = 'wc_auto_apply_coupon_codes';

	/**
	 * The post meta key flagging a coupon to be applied automatically.
	 *
	 * @var string
	 */
	private const META_KEY = 'auto_apply';

	/**
	 * Register the WordPress hooks that cover coupon changes made outside the WC_Coupon CRUD.
	 *
	 * @return void
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'transition_post_status', array( $this, 'handle_transition_post_status' ), 10, 3 );
		add_action( 'deleted_post', array( $this, 'handle_deleted_post' ), 10, 2 );

		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			add_action( $hook, array( $this, 'handle_post_meta_change' ), 10, 3 );
		}
	}

	/**
	 * Drop the cached coupon codes.
	 *
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function invalidate(): void {
		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Invalidate when a coupon crosses the published boundary.
	 *
	 * Only the `publish` status is cached, so a transition that neither leaves nor enters it
	 * cannot change the result.
	 *
	 * @param string   $new_status The status the post is transitioning to.
	 * @param string   $old_status The status the post is transitioning from.
	 * @param \WP_Post $post       The post being transitioned.
	 * @return void
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 */
	public function handle_transition_post_status( $new_status, $old_status, $post ): void {
		if ( ! $post instanceof \WP_Post || 'shop_coupon' !== $post->post_type || $new_status === $old_status ) {
			return;
		}

		if ( 'publish' === $old_status || 'publish' === $new_status ) {
			$this->invalidate();
		}
	}

	/**
	 * Invalidate when a coupon is deleted.
	 *
	 * @param int           $post_id The id of the post that was deleted.
	 * @param \WP_Post|null $post    The post that was deleted.
	 * @return void
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 */
	public function handle_deleted_post( $post_id, $post = null ): void {
		$post_type = $post instanceof \WP_Post ? $post->post_type : get_post_type( $post_id );

		if ( 'shop_coupon' === $post_type ) {
			$this->invalidate();
		}
	}

	/**
	 * Invalidate when the auto-apply flag of a coupon is written directly.
	 *
	 * @param int|array $meta_id  The id of the meta row, or ids of the rows, that changed.
	 * @param int       $post_id  The id of the post the meta belongs to.
	 * @param string    $meta_key The meta key that changed.
	 * @return void
	 *
	 * @since 11.3.0
	 *
	 * @internal
	 */
	public function handle_post_meta_change( $meta_id, $post_id, $meta_key ): void {
		if ( self::META_KEY !== $meta_key ) {
			return;
		}

		if ( 'shop_coupon' === get_post_type( $post_id ) ) {
			$this->invalidate();
		}
	}
}
