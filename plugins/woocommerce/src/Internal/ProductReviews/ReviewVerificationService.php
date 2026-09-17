<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductReviews;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Manages product reviews purchase verification meta population.
 */
final class ReviewVerificationService implements RegisterHooksInterface {

	/**
	 * Action Scheduler hook for the daily orchestrator.
	 */
	public const BACKFILL_SCHEDULE_BATCHES_ACTION = 'woocommerce_review_verification_schedule_backfill_batches';

	/**
	 * Action Scheduler hook for individual batch execution.
	 */
	public const BACKFILL_PROCESS_BATCH_ACTION = 'woocommerce_review_verification_process_backfill_batch';

	/**
	 * Action Scheduler group.
	 */
	public const PRODUCT_REVIEWS_GROUP = 'woocommerce-reviews';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Using comment_post instead of wp_insert_comment for backward compatibility (extensions can remove this callback).
		// Do not gate these hooks: suppressing them lets unverified reviews accumulate, causing backfill spikes when lifted.
		add_action( 'comment_post', array( \WC_Comments::class, 'add_comment_purchase_verification' ) ); // @phpstan-ignore return.void (required for backward compatibility)
		// Heavy backfilling cannot be triggered reactively, hence a recurring task with predictable performance characteristics.
		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'register_recurring_actions' ) );

		add_action( self::BACKFILL_SCHEDULE_BATCHES_ACTION, array( $this, 'schedule_backfill_batches' ) );
		add_action( self::BACKFILL_PROCESS_BATCH_ACTION, array( $this, 'process_backfill_batch' ) );
	}

	/**
	 * Register a daily recurring orchestrator at 3 AM local time.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @return void
	 */
	public function register_recurring_actions(): void {
		$gmt_offset   = get_option( 'gmt_offset' );
		$offset_hours = ( $gmt_offset > 0 ? '-' : '+' ) . absint( $gmt_offset ) . ' hours';

		$tomorrow_3am = strtotime( 'tomorrow 03:00 am ' . $offset_hours );
		if ( false === $tomorrow_3am ) {
			$tomorrow_3am = strtotime( 'tomorrow 03:00 am' );
		}

		as_schedule_recurring_action( $tomorrow_3am, DAY_IN_SECONDS, self::BACKFILL_SCHEDULE_BATCHES_ACTION, array(), self::PRODUCT_REVIEWS_GROUP, true );
	}

	/**
	 * Determine if a review is from a verified owner at submission.
	 *
	 * @since 11.3.0
	 *
	 * @param int $comment_id The comment ID.
	 * @return bool
	 */
	public function add_comment_purchase_verification( $comment_id ): bool {
		$verified = false;
		$comment  = get_comment( $comment_id );
		if ( $comment instanceof \WP_Comment && 'product' === get_post_type( (int) $comment->comment_post_ID ) ) {
			$email    = $comment->user_id ? '' : $comment->comment_author_email;
			$verified = (bool) wc_customer_bought_product( $email, (int) $comment->user_id, (int) $comment->comment_post_ID );
			add_comment_meta( $comment_id, 'verified', (int) $verified, true );
		}

		return $verified;
	}

	/**
	 * Daily orchestrator: scan unverified comments oldest-first, chunk into batches,
	 * and schedule continuation jobs spread over the next 24 hours.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @return void
	 */
	public function schedule_backfill_batches(): void {
		/**
		 * Customizes the batch size for backfilling reviews purchase verification meta.
		 *
		 * @since 11.3.0
		 *
		 * @param int $batch_size Default batch size (50).
		 * @return int
		 */
		$batch_size    = max( 1, (int) apply_filters( 'woocommerce_review_verification_backfill_batch_size', 50 ) );
		$cursor_filter = static function ( $clauses ) {
			global $wpdb;
			$clauses['where'] .= " AND {$wpdb->comments}.comment_ID > " . (int) get_option( 'woocommerce_review_verification_last_id', 0 );
			return $clauses;
		};

		// Performance note: HVMs and clustering friendly batches processing - 15 mins interval over next 24 hours.
		// The moving cursor (the option above) has its weak spots, but we target regular stores where comment IDs grow incrementally.
		// For unhappy path (edge-cases and custom workflows), dropping/modifying the option is the escape hatch.
		$batches_scheduling_interval = 15 * MINUTE_IN_SECONDS;
		$batches_number              = 96;
		$now                         = time();

		add_filter( 'comments_clauses', $cursor_filter );
		$comment_ids = get_comments(
			array(
				'fields'     => 'ids',
				'type__in'   => array( 'review', 'comment', '' ),
				'status'     => array( 'approve', 'hold' ),
				'number'     => $batches_number * $batch_size,
				'orderby'    => 'comment_ID',
				'order'      => 'ASC',
				'post_type'  => 'product',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Background orchestrator, not on read path.
					array(
						'key'     => 'verified',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		/** @var int[] $comment_ids */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
		remove_filter( 'comments_clauses', $cursor_filter );

		if ( ! empty( $comment_ids ) ) {
			// At this point we don't know if batch processing will succeed or fail (if so, not critical), hence moving cursor forward here.
			update_option( 'woocommerce_review_verification_last_id', end( $comment_ids ), false );
			foreach ( array_chunk( $comment_ids, $batch_size ) as $index => $batch ) {
				as_schedule_single_action(
					$now + ( ( $index + 1 ) * $batches_scheduling_interval ),
					self::BACKFILL_PROCESS_BATCH_ACTION,
					array( 'comment_ids' => $batch ),
					self::PRODUCT_REVIEWS_GROUP
				);
			}
		}
	}

	/**
	 * Process a single batch of comment IDs — resolve and persist verified meta.
	 *
	 * @since 11.3.0
	 * @internal
	 *
	 * @param int[] $comment_ids List of comment IDs to process.
	 * @return void
	 */
	public function process_backfill_batch( array $comment_ids ): void {
		// Prime comment and post caches to reduce the number of SQL queries.
		_prime_comment_caches( $comment_ids, false );
		$comments = array_filter( array_map( 'get_comment', $comment_ids ), static fn( $comment ) => $comment instanceof \WP_Comment );
		$post_ids = array_unique( array_map( static fn( $comment ) => (int) $comment->comment_post_ID, $comments ) );
		if ( ! empty( $post_ids ) ) {
			_prime_post_caches( $post_ids, false, false );
		}

		foreach ( $comments as $comment ) {
			if ( 'product' === get_post_type( (int) $comment->comment_post_ID ) ) {
				$this->add_comment_purchase_verification( (int) $comment->comment_ID );
			}
		}
	}
}
