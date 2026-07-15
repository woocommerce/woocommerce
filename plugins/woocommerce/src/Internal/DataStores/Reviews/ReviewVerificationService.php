<?php
/**
 * ReviewVerificationService class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Reviews;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Schedules off-hours backfills of the "verified owner" status for product reviews.
 *
 * Rather than resolve the per-review N+1 on read, this schedules a per-product Action Scheduler
 * task that resolves every unverified review for the product in a single query (via
 * ReviewVerificationDataStore) and persists the same 'verified' meta. The per-review path stays as
 * the fallback for reviews not yet backfilled.
 */
class ReviewVerificationService implements RegisterHooksInterface {

	/**
	 * Action Scheduler hook that runs a product's backfill.
	 */
	public const BACKFILL_ACTION = 'woocommerce_backfill_review_verification';

	/**
	 * Action Scheduler group for the backfill actions.
	 */
	private const BACKFILL_GROUP = 'woocommerce-review-verification';

	/**
	 * The data store that resolves and persists the verified-owner status.
	 *
	 * @var ReviewVerificationDataStore
	 */
	private ReviewVerificationDataStore $data_store;

	/**
	 * Inject dependencies.
	 *
	 * @internal
	 *
	 * @param ReviewVerificationDataStore $data_store The verified-owner data store.
	 * @return void
	 */
	final public function init( ReviewVerificationDataStore $data_store ): void {
		$this->data_store = $data_store;
	}

	/**
	 * Register the scheduling hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'comments_array', array( $this, 'schedule_for_page' ), 10, 2 );
		add_action( 'wp_insert_comment', array( $this, 'schedule_for_new_review' ), 10, 2 );
		add_action( self::BACKFILL_ACTION, array( $this, 'run_backfill' ) );
	}

	/**
	 * Schedule a backfill for a product page when any displayed review still needs its status.
	 *
	 * Hooked on `comments_array`; returns the comments unchanged.
	 *
	 * @since 11.0.0
	 *
	 * @param mixed $comments The comments for the current post (WP_Comment[] when it is a list).
	 * @param int   $post_id  The product (post) ID whose comments are displayed.
	 * @return mixed The unchanged comments array.
	 */
	public function schedule_for_page( $comments, $post_id ) {
		if ( empty( $comments ) || ! is_array( $comments ) ) {
			return $comments;
		}

		$post_id = absint( $post_id );
		if ( ! $this->data_store->is_enabled( $post_id ) ) {
			return $comments;
		}

		foreach ( $comments as $comment ) {
			if ( $comment instanceof \WP_Comment && 'review' === $comment->comment_type && '' === get_comment_meta( (int) $comment->comment_ID, 'verified', true ) ) {
				$this->schedule_backfill( $post_id );
				break;
			}
		}

		return $comments;
	}

	/**
	 * Schedule a backfill when a new product review is inserted.
	 *
	 * Hooked on `wp_insert_comment`.
	 *
	 * @since 11.0.0
	 *
	 * @param int         $comment_id The new comment ID.
	 * @param \WP_Comment $comment    The new comment object.
	 * @return void
	 */
	public function schedule_for_new_review( $comment_id, $comment ): void {
		if ( ! $comment instanceof \WP_Comment || 'review' !== $comment->comment_type ) {
			return;
		}

		$product_id = absint( $comment->comment_post_ID );
		if ( $this->data_store->is_enabled( $product_id ) ) {
			$this->schedule_backfill( $product_id );
		}
	}

	/**
	 * Run a product's scheduled backfill, rescheduling a follow-up when a batch fills.
	 *
	 * Hooked on the Action Scheduler backfill action.
	 *
	 * @since 11.0.0
	 *
	 * @param int $product_id The product (post) ID to backfill.
	 * @return void
	 */
	public function run_backfill( $product_id ): void {
		$product_id = absint( $product_id );

		/**
		 * Filters how many of a product's reviews are resolved per backfill run. Any remainder is
		 * picked up by a rescheduled follow-up, keeping memory and the query's IN() list bounded on
		 * products with very many reviews.
		 *
		 * @since 11.0.0
		 *
		 * @param int $batch_size Reviews resolved per run.
		 * @param int $product_id The product (post) ID being backfilled.
		 */
		$batch_size = max( 1, (int) apply_filters( 'woocommerce_review_verification_backfill_batch_size', 500, $product_id ) );

		$resolved = $this->data_store->backfill( $product_id, $batch_size );

		// A full batch means more may remain; pick them up shortly rather than waiting a full day.
		if ( $resolved >= $batch_size ) {
			$this->reschedule_remaining( $product_id );
		}
	}

	/**
	 * Schedule a single, deduplicated backfill for a product in the next off-hours window.
	 *
	 * @param int $product_id The product (post) ID.
	 * @return void
	 */
	private function schedule_backfill( int $product_id ): void {
		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		$args = array( 'product_id' => $product_id );
		if ( as_has_scheduled_action( self::BACKFILL_ACTION, $args, self::BACKFILL_GROUP ) ) {
			return;
		}

		// The unique flag makes the dedup atomic against a concurrent request that passed the check above.
		as_schedule_single_action( $this->next_run_timestamp(), self::BACKFILL_ACTION, $args, self::BACKFILL_GROUP, true );
	}

	/**
	 * Schedule a prompt follow-up run for a product whose backfill filled its batch.
	 *
	 * Called from within a running backfill for the same product, so the executing action itself
	 * matches as_has_scheduled_action() (which counts in-progress). Dedup on the PENDING state only,
	 * and without the unique flag, so the follow-up is not rejected by the still-running action.
	 *
	 * @param int $product_id The product (post) ID.
	 * @return void
	 */
	private function reschedule_remaining( int $product_id ): void {
		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_get_scheduled_actions' ) ) {
			return;
		}

		$args    = array( 'product_id' => $product_id );
		$pending = as_get_scheduled_actions(
			array(
				'hook'     => self::BACKFILL_ACTION,
				'args'     => $args,
				'group'    => self::BACKFILL_GROUP,
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1,
			),
			'ids'
		);
		if ( ! empty( $pending ) ) {
			return;
		}

		as_schedule_single_action( time() + MINUTE_IN_SECONDS, self::BACKFILL_ACTION, $args, self::BACKFILL_GROUP );
	}

	/**
	 * Timestamp of the next 06:00 in the site's timezone (before business hours).
	 *
	 * @return int
	 */
	private function next_run_timestamp(): int {
		$timezone = wp_timezone();
		$now      = new \DateTime( 'now', $timezone );
		$next     = new \DateTime( 'today 06:00', $timezone );
		if ( $next <= $now ) {
			$next->modify( '+1 day' );
		}
		return $next->getTimestamp();
	}
}
