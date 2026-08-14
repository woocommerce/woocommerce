<?php
/**
 *  Async Generator class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog;

use ActionScheduler_AsyncRequest_QueueRunner;
use ActionScheduler_Store;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedLockException;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductWalker;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ResumableFeedInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\WalkerProgress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Async Generator for feeds.
 *
 * @since 10.5.0
 */
class AsyncGenerator {
	/**
	 * The Action Scheduler action hook for the feed generation.
	 *
	 * @var string
	 */
	const FEED_GENERATION_ACTION = 'woocommerce_product_feed_generation';

	/**
	 * The Action Scheduler action hook for the feed deletion.
	 *
	 * @var string
	 */
	const FEED_DELETION_ACTION = 'woocommerce_product_feed_deletion';

	/**
	 * Feed expiry time, once completed.
	 * If the feed is not downloaded within this timeframe, a new one will need to be generated.
	 *
	 * @var int
	 */
	const FEED_EXPIRY = 20 * HOUR_IN_SECONDS;

	/**
	 * Possible states of generation.
	 */
	const STATE_SCHEDULED   = 'scheduled';
	const STATE_IN_PROGRESS = 'in_progress';
	const STATE_COMPLETED   = 'completed';
	const STATE_FAILED      = 'failed';

	/**
	 * The number of products fetched per database batch.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 100;

	/**
	 * The chunk sizes (products processed per action) tried in descending order.
	 *
	 * Generation starts at the first (largest) size so most catalogs finish in a single action. When a
	 * run gets stuck — likely killed because the size was too large for the host — the size steps down
	 * one rung and is persisted, so future runs do not repeat the attempt that failed.
	 *
	 * @var int[]
	 */
	const CHUNK_SIZE_STEPS = array( 100000, 2500, 1000 );

	/**
	 * Integration instance.
	 *
	 * @var POSIntegration
	 */
	private $integration;

	/**
	 * Dependency injector.
	 *
	 * @param POSIntegration $integration The integration instance.
	 * @internal
	 */
	final public function init( POSIntegration $integration ): void {
		$this->integration = $integration;
	}

	/**
	 * Register hooks for the async generator.
	 *
	 * @since 10.5.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( self::FEED_GENERATION_ACTION, array( $this, 'feed_generation_action' ) );
		add_action( self::FEED_DELETION_ACTION, array( $this, 'feed_deletion_action' ), 10, 2 );
	}

	/**
	 * Returns the current feed generation status.
	 * Initiates one if not already running.
	 *
	 * @since 10.5.0
	 *
	 * @param array|null $args The arguments to pass to the action.
	 * @return array           The feed generation status.
	 */
	public function get_status( ?array $args = null ): array {
		$option_key = $this->get_option_key( $args );
		$status     = get_option( $option_key );

		if ( is_array( $status ) ) {
			if ( $this->validate_status( $status ) ) {
				return $status;
			}

			// Surface a failed generation to the client once, then clear it so the next poll starts a
			// fresh run. The POS clients are built to read `failed`, stop, and let their own scheduling
			// drive the next attempt, so the server must report the failure rather than silently retry
			// it. Clearing the status (rather than leaving it sticky) matters because those clients poll
			// again with force=false, which would otherwise keep re-reading the same failure forever.
			if ( self::STATE_FAILED === ( $status['state'] ?? '' ) ) {
				$this->discard_feed( $status );
				delete_option( $option_key );
				return $status;
			}

			// A stuck in-progress job most likely died because its chunk was too large for this host;
			// step the size down so the restart is more likely to fit.
			$this->reduce_chunk_size_if_stuck( $status, $option_key );

			// Whatever made the status invalid (stuck, expired, …), discard the partial feed and start fresh.
			$this->discard_feed( $status );
		}

		// Clear all previous actions to avoid race conditions.
		as_unschedule_all_actions( self::FEED_GENERATION_ACTION, array( $option_key ), 'woo-product-feed' );

		$status = array(
			'scheduled_at' => time(),
			'updated_at'   => time(),
			'completed_at' => null,
			'state'        => self::STATE_SCHEDULED,
			'progress'     => 0,
			'processed'    => 0,
			'total'        => -1,
			'args'         => $args ?? array(),
		);

		update_option( $option_key, $status );

		$this->schedule_generation_action( $option_key );

		return $status;
	}

	/**
	 * Schedules (and immediately dispatches) an async action to process a feed generation chunk.
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return void
	 */
	private function schedule_generation_action( string $option_key ): void {
		// Deliberately not enqueued as "unique": Action Scheduler's uniqueness check matches on hook +
		// group only (not args) and treats a running action as a blocker, so a unique enqueue of the next
		// chunk would be rejected while the current chunk's action is still running. Per-job de-duplication
		// is handled by as_unschedule_all_actions() in get_status() instead.
		as_enqueue_async_action(
			self::FEED_GENERATION_ACTION,
			array( $option_key ),
			'woo-product-feed',
			false,
			1
		);

		// Force an async request so the action runs immediately.
		if ( class_exists( ActionScheduler_AsyncRequest_QueueRunner::class ) && class_exists( ActionScheduler_Store::class ) ) {
			$store         = ActionScheduler_Store::instance();
			$async_request = new ActionScheduler_AsyncRequest_QueueRunner( $store );
			$async_request->dispatch();
		}
	}

	/**
	 * Action scheduler callback for the feed generation.
	 *
	 * @since 10.5.0
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return void
	 */
	public function feed_generation_action( string $option_key ) {
		$status = get_option( $option_key );

		// Only a scheduled (first chunk) or in-progress (continuation) job should be processed here.
		if ( ! is_array( $status ) || ! in_array( $status['state'] ?? '', array( self::STATE_SCHEDULED, self::STATE_IN_PROGRESS ), true ) ) {
			wc_get_logger()->error( 'Invalid feed generation status', array( 'status' => $status ) );
			return;
		}

		$is_first_chunk = self::STATE_SCHEDULED === $status['state'];

		// A continuation must know which feed file it is appending to. If it doesn't, the status is
		// corrupt; bail and let the heartbeat-based recovery restart generation from scratch.
		if ( ! $is_first_chunk && empty( $status['file_name'] ) ) {
			wc_get_logger()->error( 'Invalid feed generation continuation status', array( 'status' => $status ) );
			return;
		}

		$feed = null;
		try {
			$this->raise_resource_limits();

			$feed = $this->integration->create_feed();

			if ( $is_first_chunk ) {
				$status['file_name']       = $feed->open();
				$status['page']            = 1;
				$status['processed']       = 0;
				$status['entries_written'] = 0;
			} else {
				$feed->open( (string) $status['file_name'], (int) ( $status['entries_written'] ?? 0 ) );
			}

			// Only now that the feed lock is held (acquired by open()) do we claim the job as in-progress
			// and refresh the heartbeat. Doing it earlier would rewrite the status — and bump updated_at —
			// even for a run that immediately steps aside on FeedLockException, contradicting that path's
			// "leave the status untouched" intent and refreshing the heartbeat of a job it never wrote.
			$status['state']      = self::STATE_IN_PROGRESS;
			$status['updated_at'] = time();
			update_option( $option_key, $status );

			$walker = ProductWalker::from_integration( $this->integration, $feed );
			$walker->set_batch_size( $this->get_batch_size() );
			$walker->add_time_limit( $this->get_batch_time_limit() );

			$this->apply_mapper_args( $status['args'] ?? array() );

			$start_page     = max( 1, (int) ( $status['page'] ?? 1 ) );
			$base_processed = (int) ( $status['processed'] ?? 0 );
			$progress       = $walker->walk_batches(
				function ( WalkerProgress $progress ) use ( &$status, $option_key, $base_processed ) {
					// Refresh progress and the heartbeat after every batch, so polling sees smooth progress
					// within a chunk rather than a single jump at the chunk boundary.
					$status = $this->update_progress( $status, $base_processed + $progress->processed_items, $progress->total_count );
					update_option( $option_key, $status );
				},
				$start_page,
				$this->get_chunk_batch_count( $option_key )
			);

			// The feed's entry count is already cumulative across chunks (open() seeds it with the running
			// total when resuming), so store it as-is rather than adding to the previous total.
			$status                    = $this->update_progress( $status, $base_processed + $progress->processed_items, $progress->total_count );
			$status['entries_written'] = $feed->get_entry_count();
			$status['page']            = $start_page + $progress->processed_batches;

			$is_complete = $progress->total_batch_count <= 0 || (int) $status['page'] > $progress->total_batch_count;

			if ( $is_complete ) {
				$feed->end();

				$status['state']        = self::STATE_COMPLETED;
				$status['progress']     = 100;
				$status['url']          = $feed->get_file_url();
				$status['path']         = $feed->get_file_path();
				$status['completed_at'] = time();
				update_option( $option_key, $status );

				// Schedule deletion of the file after the expiry time.
				as_schedule_single_action(
					time() + self::FEED_EXPIRY,
					self::FEED_DELETION_ACTION,
					array( $option_key, $feed->get_file_path() ),
					'woo-product-feed',
					false
				);
			} else {
				$feed->flush();
				update_option( $option_key, $status );
				$this->schedule_generation_action( $option_key );
			}
		} catch ( FeedLockException $e ) {
			// Another process already holds the feed file lock and is actively writing it, so this run
			// is a redundant duplicate (e.g. Action Scheduler re-ran a slow chunk while the original was
			// still in flight). Step aside and leave the status untouched: the lock holder is making
			// progress, so marking the job failed here would report a failure for a healthy generation
			// and let the next poll discard the partial file the holder is still writing. Release only
			// this run's own handle (which never acquired the lock, so closing it cannot free the holder's).
			if ( $feed instanceof ResumableFeedInterface ) {
				$feed->flush();
			}
			return;
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				'Feed generation failed',
				array(
					'error'      => $e->getMessage(),
					'option_key' => $option_key,
				)
			);

			// Release the file handle, if any, so it is not left dangling.
			if ( $feed instanceof ResumableFeedInterface ) {
				$feed->flush();
			}

			$status['state']     = self::STATE_FAILED;
			$status['error']     = $e->getMessage();
			$status['failed_at'] = time();
			update_option( $option_key, $status );
		}
	}

	/**
	 * Raises the memory and execution time limits for the current process before heavy work begins.
	 *
	 * These only affect the current process and never lower an already higher limit. They cannot
	 * override a hard host/server request timeout or Action Scheduler's failure period.
	 *
	 * @return void
	 */
	private function raise_resource_limits(): void {
		wp_raise_memory_limit( 'admin' );

		// Raise the time limit up front: the walker only resets it after each batch, so the initial
		// product query and the first batch would otherwise run under the request's (possibly low) limit.
		$batch_time_limit = $this->get_batch_time_limit();
		if ( $batch_time_limit > 0 ) {
			wc_set_time_limit( $batch_time_limit );
		}
	}

	/**
	 * Returns the per-batch PHP execution time limit (in seconds) for feed generation.
	 *
	 * @return int The per-batch time limit in seconds.
	 */
	private function get_batch_time_limit(): int {
		/**
		 * Filters the per-batch PHP execution time limit (in seconds) for product feed generation.
		 *
		 * The limit is set up front and reset after each processed batch, so a low `max_execution_time`
		 * does not abort generation part-way through a chunk. Return 0 to leave the time limit untouched.
		 * This only affects PHP's own execution timeout, not Action Scheduler's failure period nor any
		 * hard server/host request timeout.
		 *
		 * @param int $batch_time_limit The per-batch time limit in seconds.
		 *
		 * @since 11.0.0
		 */
		return (int) apply_filters( 'woocommerce_product_feed_batch_time_limit', 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Returns the number of batches to process per chunk, derived from the effective chunk size.
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return int The number of batches per chunk (at least 1).
	 */
	private function get_chunk_batch_count( string $option_key ): int {
		/**
		 * Filters the number of products processed per chunk during feed generation.
		 *
		 * Each chunk runs in its own Action Scheduler action and then schedules the next, keeping every
		 * run short enough to finish within Action Scheduler's failure period and the host's request
		 * timeout. Defaults to the effective chunk size, which starts large and shrinks if a run gets stuck.
		 *
		 * @param int $chunk_size The number of products to process per chunk.
		 *
		 * @since 11.0.0
		 */
		$chunk_size = (int) apply_filters( 'woocommerce_product_feed_chunk_size', $this->get_effective_chunk_size( $option_key ) );
		if ( $chunk_size < 1 ) {
			$chunk_size = self::CHUNK_SIZE_STEPS[0];
		}

		return (int) max( 1, (int) ceil( $chunk_size / $this->get_batch_size() ) );
	}

	/**
	 * Returns the option key under which the effective chunk size is persisted.
	 *
	 * Stored separately from the status so a shrunk chunk size survives the status being cleared when a
	 * job completes, expires, or restarts, and carries over to the next request from the app.
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return string The option key for the effective chunk size.
	 */
	private function get_chunk_size_option_key( string $option_key ): string {
		return $option_key . '_chunk_size';
	}

	/**
	 * Returns the effective chunk size (products per action) currently in use for a feed.
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return int The effective chunk size, defaulting to the largest configured step.
	 */
	private function get_effective_chunk_size( string $option_key ): int {
		$chunk_size = (int) get_option( $this->get_chunk_size_option_key( $option_key ), self::CHUNK_SIZE_STEPS[0] );

		return $chunk_size > 0 ? $chunk_size : self::CHUNK_SIZE_STEPS[0];
	}

	/**
	 * Steps the chunk size down when an invalidated status was a stuck in-progress job.
	 *
	 * A stuck job was most likely killed because its chunk was too large for the host, so a smaller chunk
	 * makes the restart more likely to fit. A genuine failure (state = failed) is a real error rather than
	 * a size symptom and is intentionally excluded. Both recovery paths — an ordinary poll
	 * ({@see get_status()}) and an explicit rebuild ({@see force_regeneration()}) — call this, so a stuck
	 * job adapts the same way however it is recovered.
	 *
	 * @param array  $status     The invalidated status being discarded.
	 * @param string $option_key The option key for the feed generation status.
	 * @return void
	 */
	private function reduce_chunk_size_if_stuck( array $status, string $option_key ): void {
		if ( self::STATE_IN_PROGRESS === ( $status['state'] ?? '' ) ) {
			$this->reduce_chunk_size( $option_key );
		}
	}

	/**
	 * Steps the effective chunk size down to the next-smaller configured size and persists it.
	 *
	 * Called when a job gets stuck. Once at the smallest configured size it stays there.
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return int The new effective chunk size.
	 */
	private function reduce_chunk_size( string $option_key ): int {
		$current = $this->get_effective_chunk_size( $option_key );

		// CHUNK_SIZE_STEPS is descending, so the first step smaller than the current size is the next rung down.
		$next = $current;
		foreach ( self::CHUNK_SIZE_STEPS as $step ) {
			if ( $step < $current ) {
				$next = $step;
				break;
			}
		}

		update_option( $this->get_chunk_size_option_key( $option_key ), $next );

		wc_get_logger()->warning(
			'Product feed generation got stuck; reducing the chunk size for future runs.',
			array(
				'option_key'          => $option_key,
				'previous_chunk_size' => $current,
				'chunk_size'          => $next,
			)
		);

		return $next;
	}

	/**
	 * Returns the number of products fetched per database batch.
	 *
	 * @return int The batch size (at least 1).
	 */
	private function get_batch_size(): int {
		/**
		 * Filters the number of products fetched per database query during feed generation.
		 *
		 * This is the granularity within a chunk; see `woocommerce_product_feed_chunk_size` for how many
		 * products each Action Scheduler action processes.
		 *
		 * @param int $batch_size The number of products per database batch.
		 *
		 * @since 11.0.0
		 */
		$batch_size = (int) apply_filters( 'woocommerce_product_feed_batch_size', self::BATCH_SIZE );

		return (int) max( 1, $batch_size );
	}

	/**
	 * Updates the cumulative progress fields on the status and refreshes the heartbeat.
	 *
	 * @param array $status    The current feed generation status.
	 * @param int   $processed The cumulative number of products processed so far.
	 * @param int   $total     The total number of products to process.
	 * @return array The updated status.
	 */
	private function update_progress( array $status, int $processed, int $total ): array {
		$status['processed']  = $processed;
		$status['total']      = $total;
		$status['progress']   = $total > 0 ? round( ( $processed / $total ) * 100, 2 ) : 0;
		$status['updated_at'] = time();
		return $status;
	}

	/**
	 * Applies the dynamic field arguments to the product mapper.
	 *
	 * @param array $args The feed generation arguments.
	 * @return void
	 */
	private function apply_mapper_args( array $args ): void {
		if ( isset( $args['_product_fields'] ) && is_string( $args['_product_fields'] ) && '' !== $args['_product_fields'] ) {
			$this->integration->get_product_mapper()->set_fields( $args['_product_fields'] );
		}
		if ( isset( $args['_variation_fields'] ) && is_string( $args['_variation_fields'] ) && '' !== $args['_variation_fields'] ) {
			$this->integration->get_product_mapper()->set_variation_fields( $args['_variation_fields'] );
		}
	}

	/**
	 * Deletes the feed file referenced by a status, if any.
	 *
	 * Completed feeds expose a full path; in-progress chunked feeds only track a file name.
	 *
	 * @param array $status The feed generation status.
	 * @return void
	 */
	private function discard_feed( array $status ): void {
		// A completed feed exposes a full path; an in-progress chunked feed only tracks a file name.
		// Reduce either to a plain identifier and let ResumableFeedInterface::delete() validate it and confine
		// the deletion to the feed directory, so a tampered path read back from the option can never escape it.
		$identifier = ! empty( $status['file_name'] )
			? (string) $status['file_name']
			: ( ! empty( $status['path'] ) ? wp_basename( (string) $status['path'] ) : '' );

		if ( '' !== $identifier ) {
			$this->integration->create_feed()->delete( $identifier );
		}
	}

	/**
	 * Forces a regeneration of the feed.
	 *
	 * @since 10.5.0
	 *
	 * @param array|null $args The arguments to pass to the action.
	 * @return array The feed generation status.
	 * @throws \Exception When there is a reason why the regeneration cannot be forced.
	 */
	public function force_regeneration( ?array $args = null ): array {
		$option_key = $this->get_option_key( $args );
		$status     = get_option( $option_key );

		// An invalid status (stale, expired, or a stalled in-progress job) always regenerates from
		// scratch: discard any partial feed and clear the option so the restart starts clean.
		if ( ! is_array( $status ) || ! $this->validate_status( $status ) ) {
			if ( is_array( $status ) ) {
				// A stuck in-progress job adapts its chunk size on a forced rebuild too, the same way an
				// ordinary poll does, so the rebuild does not re-die at the size that just got it killed.
				$this->reduce_chunk_size_if_stuck( $status, $option_key );
				$this->discard_feed( $status );
				delete_option( $option_key );
			}
			return $this->get_status( $args );
		}

		switch ( $status['state'] ?? '' ) {
			case self::STATE_SCHEDULED:
				// Generation is already scheduled and should start shortly; leave it be.
				return $status;

			case self::STATE_IN_PROGRESS:
				// A genuinely running job (fresh heartbeat) cannot be interrupted mid-flight.
				throw new \Exception( 'Feed generation is already in progress and cannot be stopped.' );

			case self::STATE_COMPLETED:
				$this->discard_feed( $status );
				delete_option( $option_key );
				return $this->get_status( $args );

			// A failed job is invalid (see validate_status()), so it never reaches this switch; it is
			// discarded and regenerated by the early return above.
			default:
				throw new \Exception( 'Unknown feed generation state.' );
		}
	}

	/**
	 * Action scheduler callback for the feed deletion after expiry.
	 *
	 * @since 10.5.0
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @param string $path       The path to the feed file.
	 * @return void
	 */
	public function feed_deletion_action( string $option_key, string $path ) {
		delete_option( $option_key );
		wp_delete_file( $path );
	}

	/**
	 * Returns the option key for the feed generation status.
	 *
	 * @param array|null $args The arguments to pass to the action.
	 * @return string          The option key.
	 */
	private function get_option_key( ?array $args = null ): string {
		$normalized_args = $args ?? array();
		if ( ! empty( $normalized_args ) ) {
			ksort( $normalized_args );
		}

		return 'feed_status_' . md5(
			// WPCS dislikes serialize for security reasons, but it will be hashed immediately.
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			serialize(
				array(
					'integration' => $this->integration->get_id(),
					'args'        => $normalized_args,
				)
			)
		);
	}

	/**
	 * Validates the status of the feed generation.
	 *
	 * Makes sure that the file exists for completed jobs,
	 * that scheduled jobs are not stuck, etc.
	 *
	 * @param array $status The status of the feed generation.
	 * @return bool         True if the status is valid, false otherwise.
	 */
	private function validate_status( array $status ): bool {
		// A failed job is never served as-is. get_status() surfaces the failure to the client once and
		// then clears it, so the client can react and its next poll starts a fresh run; force_regeneration()
		// likewise treats it as invalid and regenerates. Either way it must not validate.
		if ( self::STATE_FAILED === $status['state'] ) {
			return false;
		}

		// For completed jobs, the file must still exist and not be expired (e.g. manually deleted, or a
		// cleanup job that failed to clear an expired feed).
		if ( self::STATE_COMPLETED === $status['state'] ) {
			if ( ! file_exists( $status['path'] ) ) {
				return false;
			}

			if ( ! isset( $status['completed_at'] ) ) {
				return false;
			}

			if ( $status['completed_at'] + self::FEED_EXPIRY < time() ) {
				return false;
			}
		}

		/**
		 * Allows the timeout for a feed to remain in `scheduled` state to be changed. Past this point
		 * Action Scheduler is typically stuck and the job is regenerated.
		 *
		 * @param int $stuck_time The stuck time in seconds.
		 * @return int The stuck time in seconds.
		 * @since 10.5.0
		 */
		$scheduled_timeout = apply_filters( 'woocommerce_product_feed_scheduled_timeout', 10 * MINUTE_IN_SECONDS );
		if (
			self::STATE_SCHEDULED === $status['state']
			&& (
				! isset( $status['scheduled_at'] )
				|| time() - $status['scheduled_at'] > $scheduled_timeout
			)
		) {
			return false;
		}

		// An in-progress job that has not refreshed its heartbeat (`updated_at`, set on start and after
		// every batch) within the timeout was most likely killed (host timeout or out of memory) before
		// it could mark itself failed. Treat it as stuck so a new feed can be generated.
		if ( self::STATE_IN_PROGRESS === $status['state'] ) {
			$last_activity = $status['updated_at'] ?? $status['scheduled_at'] ?? 0;

			/**
			 * Allows the heartbeat timeout for an `in_progress` feed to be changed. Past this point the
			 * job is treated as stuck and regenerated.
			 *
			 * The default is kept comfortably larger than the per-batch time budget on purpose. The
			 * heartbeat only refreshes between batches, so the longest gap a healthy job can produce is
			 * roughly one batch (`woocommerce_product_feed_batch_time_limit`). A timeout at or near that
			 * budget would let a single slow-but-valid batch look stuck, and recovery would then discard
			 * the partial the live process is still writing. Deriving it as a multiple (with a floor)
			 * keeps that margin even when the batch budget is raised via its own filter.
			 *
			 * @param int $stuck_time The stuck time in seconds.
			 * @return int The stuck time in seconds.
			 * @since 11.0.0
			 */
			$in_progress_timeout = apply_filters(
				'woocommerce_product_feed_in_progress_timeout',
				max( 15 * MINUTE_IN_SECONDS, 3 * $this->get_batch_time_limit() )
			);
			if ( time() - $last_activity > $in_progress_timeout ) {
				return false;
			}
		}

		return true;
	}
}
