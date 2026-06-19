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
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductWalker;
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
	 * Generation starts at the first (largest) size, which lets most catalogs finish in a single
	 * action with no inter-action latency. Each time a run gets stuck — most likely killed because the
	 * size was too large for the host — the effective size steps down one rung and is persisted, so
	 * future runs (including the next request from the app) do not repeat the attempt that failed.
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
		// Determine the option key based on the integration ID and arguments.
		$option_key = $this->get_option_key( $args );
		$status     = get_option( $option_key );

		if ( is_array( $status ) ) {
			// A still-valid status (including a healthy, actively-progressing job) is returned as-is.
			if ( $this->validate_status( $status ) ) {
				return $status;
			}

			// An in-progress job that fails validation has a stale heartbeat: it was killed mid-run,
			// most likely because the chunk size was too large for this host. Step the chunk size down
			// so this and future jobs use a smaller, more reliable size.
			if ( self::STATE_IN_PROGRESS === ( $status['state'] ?? '' ) ) {
				$this->reduce_chunk_size( $option_key );
			}

			// Whatever made it invalid (stuck, expired, …), discard any partial feed it left behind
			// and start fresh.
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

		update_option(
			$option_key,
			$status
		);

		// Start an immediate async action to generate the first chunk of the feed.
		$this->schedule_generation_action( $option_key );

		return $status;
	}

	/**
	 * Schedules (and immediately dispatches) an async action to process a feed generation chunk.
	 *
	 * Used both to start generation and to queue each subsequent chunk, so that a large catalog is
	 * built across several short actions rather than one long-running one.
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return void
	 */
	private function schedule_generation_action( string $option_key ): void {
		// Note: the action is deliberately not scheduled as "unique". Action Scheduler's uniqueness
		// check matches on hook + group only (not args), and treats both pending AND running actions
		// as blockers. When this is called to queue the next chunk, the current chunk's own action is
		// still running, so a unique enqueue would be silently rejected and generation would stall.
		// Per-job de-duplication is instead handled by as_unschedule_all_actions() in get_status().
		as_enqueue_async_action(
			self::FEED_GENERATION_ACTION,
			array( $option_key ),
			'woo-product-feed',
			false,
			1
		);

		// Manually force an async request to be dispatched to process the action immediately.
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
			wc_get_logger()->error(
				'Invalid feed generation status',
				array(
					'status'     => $status,
					'chunk_size' => $this->get_effective_chunk_size( $option_key ),
				)
			);
			return;
		}

		$is_first_chunk = self::STATE_SCHEDULED === $status['state'];

		// A continuation must know which feed file it is appending to. If it doesn't, the status is
		// corrupt; bail and let the heartbeat-based recovery restart generation from scratch.
		if ( ! $is_first_chunk && empty( $status['file_name'] ) ) {
			wc_get_logger()->error(
				'Invalid feed generation continuation status',
				array(
					'status'     => $status,
					'chunk_size' => $this->get_effective_chunk_size( $option_key ),
				)
			);
			return;
		}

		$status['state']      = self::STATE_IN_PROGRESS;
		$status['updated_at'] = time();
		update_option( $option_key, $status );

		$feed = null;
		try {
			$this->raise_resource_limits();

			$feed = $this->integration->create_feed();

			// Start a fresh feed, or resume the one a previous chunk began. start() returns the
			// identifier it actually used; a continuation whose partial file has vanished (e.g. cleaned
			// up by the host) falls back to a fresh feed, which we detect by the returned identifier
			// differing from the stored one — in which case the cursor is reset to start over.
			$resume_identifier = $is_first_chunk ? null : (string) $status['file_name'];
			$identifier        = $feed->start( $resume_identifier, (int) ( $status['entries_written'] ?? 0 ) );

			if ( ( $status['file_name'] ?? null ) !== $identifier ) {
				$status['file_name']       = $identifier;
				$status['page']            = 1;
				$status['processed']       = 0;
				$status['entries_written'] = 0;

				// Persist the new feed reference immediately so a job killed during its first batch can
				// still be cleaned up (and polled) by the stuck-job recovery.
				update_option( $option_key, $status );
			}

			$walker = ProductWalker::from_integration( $this->integration, $feed );
			$walker->set_batch_size( $this->get_batch_size() );
			$walker->add_time_limit( $this->get_batch_time_limit() );

			$this->apply_mapper_args( $status['args'] ?? array() );

			// The current effective chunk size determines how many batches this action processes before
			// it finalizes (if complete) or schedules the next chunk. It starts large enough that most
			// catalogs finish in one action, and shrinks if a previous run got stuck.
			$start_page     = max( 1, (int) ( $status['page'] ?? 1 ) );
			$base_processed = (int) ( $status['processed'] ?? 0 );
			$progress       = $walker->walk(
				function ( WalkerProgress $progress ) use ( &$status, $option_key, $base_processed ) {
					// Update progress (and the heartbeat) after every batch, so polling sees smooth
					// progress within a chunk rather than a single jump at the chunk boundary.
					$status = $this->update_progress( $status, $base_processed + $progress->processed_items, $progress->total_count );
					update_option( $option_key, $status );
				},
				$start_page,
				$this->get_chunk_batch_count( $option_key )
			);

			// Advance the cursor and cumulative counters. The feed's entry count is already cumulative
			// across chunks (start() seeds it with the running total when resuming), so it is stored
			// as-is rather than added to the previous total.
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

				// Schedule another action to delete the file after the expiry time.
				as_schedule_single_action(
					time() + self::FEED_EXPIRY,
					self::FEED_DELETION_ACTION,
					array(
						$option_key,
						$feed->get_file_path(),
					),
					'woo-product-feed',
					false
				);
			} else {
				// Persist this chunk and schedule the next one.
				$feed->flush();
				update_option( $option_key, $status );
				$this->schedule_generation_action( $option_key );
			}
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				'Feed generation failed',
				array(
					'error'      => $e->getMessage(),
					'option_key' => $option_key,
					'chunk_size' => $this->get_effective_chunk_size( $option_key ),
				)
			);

			// Close the file handle, if any, so it is not left dangling.
			if ( $feed instanceof FeedInterface ) {
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
		// Large catalogs are memory heavy, so give the process as much headroom as the host allows.
		wp_raise_memory_limit( 'admin' );

		// Raise the time limit up front: the walker only resets it after each batch, so the initial
		// product query and the first batch would otherwise run under whatever (possibly very low)
		// limit the Action Scheduler request started with.
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
		 * The execution time limit is set to this value up front and reset to it after each processed
		 * batch, so that a low `max_execution_time` does not abort generation part-way through a chunk.
		 * Return 0 to leave the time limit untouched.
		 *
		 * This only affects PHP's own execution timeout. It does not extend Action Scheduler's failure
		 * period (`action_scheduler_failure_period`, 300 seconds by default) nor any hard server/host
		 * request timeout.
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
		 * Each chunk runs in its own Action Scheduler action and then schedules the next, keeping
		 * every run short enough to finish well within Action Scheduler's failure period and the
		 * host's request timeout. Larger chunks mean fewer actions but longer individual runs.
		 *
		 * Defaults to the effective chunk size, which starts large (so most catalogs finish in one
		 * action) and shrinks automatically if a run gets stuck.
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
	 * Returns the option key under which the effective chunk size is persisted for a feed.
	 *
	 * The chunk size lives in its own option (a sibling of the status option) so it survives the
	 * status being deleted when a job completes, expires, or is restarted — that is what lets a
	 * shrunk chunk size carry over to the next request from the app.
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
	 * Steps the effective chunk size down to the next-smaller configured size and persists it.
	 *
	 * Called when a job gets stuck (its run was killed, most likely because the chunk size was too
	 * large for this host). Once at the smallest configured size it stays there.
	 *
	 * @param string $option_key The option key for the feed generation status.
	 * @return int The new effective chunk size.
	 */
	private function reduce_chunk_size( string $option_key ): int {
		$current = $this->get_effective_chunk_size( $option_key );

		// CHUNK_SIZE_STEPS is in descending order, so the first step smaller than the current size is
		// the next rung down. If none is smaller, we are already at the smallest size.
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
		 * Smaller batches use less memory per query at the cost of more queries. This is the
		 * granularity within a chunk; see `woocommerce_product_feed_chunk_size` for how many
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
		if ( ! empty( $status['path'] ) ) {
			wp_delete_file( (string) $status['path'] );
		} elseif ( ! empty( $status['file_name'] ) ) {
			$this->integration->create_feed()->delete( (string) $status['file_name'] );
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

		// If there is no option, there is nothing to force. If the status is invalid (stale, expired,
		// or a stalled in-progress job), force always regenerates from scratch — unlike get_status(),
		// which resumes a stalled job. Discard any partial feed and clear the option so the restart
		// starts clean rather than resuming.
		if ( ! is_array( $status ) || ! $this->validate_status( $status ) ) {
			if ( is_array( $status ) ) {
				$this->discard_feed( $status );
				delete_option( $option_key );
			}
			return $this->get_status( $args );
		}

		switch ( $status['state'] ?? '' ) {
			case self::STATE_SCHEDULED:
				// If generation is scheduled, we can just let it be and return the current status.
				// It should start shortly.
				return $status;

			case self::STATE_IN_PROGRESS:
				// A genuinely running job (its heartbeat is still fresh, otherwise validate_status()
				// above would have restarted it) cannot be interrupted mid-flight.
				throw new \Exception( 'Feed generation is already in progress and cannot be stopped.' );

			case self::STATE_COMPLETED:
				// Delete the existing file, clear the option and let generation start again.
				wp_delete_file( (string) $status['path'] );
				delete_option( $option_key );
				return $this->get_status( $args );

			case self::STATE_FAILED:
				// Clear the failed status and restart generation.
				delete_option( $option_key );
				return $this->get_status( $args );

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
		/**
		 * For completed jobs, make sure the file still exists. Regenerate otherwise.
		 *
		 * The file should typically get deleted at the same time as the status is cleared.
		 * However, something else could cause the file to disappear in the meantime (ex. manual delete).
		 *
		 * Also, if the cleanup job failed, the feed might appear as complete, but be expired.
		 */
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
		 * If the job has been scheduled more than 10 minutes ago but has not
		 * transitioned to IN_PROGRESS yet, ActionScheduler is typically stuck.
		 */

		/**
		 * Allows the timeout for a feed to remain in `scheduled` state to be changed.
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

		/**
		 * If the job is in progress but has not updated its heartbeat within the timeout, the
		 * process was most likely killed (server/host timeout or out of memory) before it could
		 * mark itself as failed. Without this check, such a job would stay `in_progress` forever
		 * and no new feed could ever be generated.
		 *
		 * The heartbeat (`updated_at`) is refreshed when the job starts and after every processed
		 * batch, so an active job keeps it fresh while a killed one does not.
		 */
		if ( self::STATE_IN_PROGRESS === $status['state'] ) {
			$last_activity = $status['updated_at'] ?? $status['scheduled_at'] ?? 0;

			/**
			 * Allows the timeout for a feed to remain in `in_progress` state without a heartbeat
			 * update to be changed. Past this point the job is treated as stuck and regenerated.
			 *
			 * @param int $stuck_time The stuck time in seconds.
			 * @return int The stuck time in seconds.
			 * @since 11.0.0
			 */
			$in_progress_timeout = apply_filters( 'woocommerce_product_feed_in_progress_timeout', 5 * MINUTE_IN_SECONDS );
			if ( time() - $last_activity > $in_progress_timeout ) {
				return false;
			}
		}

		// All good.
		return true;
	}
}
