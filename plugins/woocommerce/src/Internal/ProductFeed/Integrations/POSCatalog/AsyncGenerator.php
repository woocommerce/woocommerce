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

		// For existing jobs, make sure that everything in the status makes sense.
		if ( is_array( $status ) && ! $this->validate_status( $status ) ) {
			$status = false;
		}

		// If the status is an array, it means that there is nothing to schedule in this method.
		if ( is_array( $status ) ) {
			return $status;
		}

		// Clear all previous actions to avoid race conditions.
		as_unschedule_all_actions( self::FEED_GENERATION_ACTION, array( $option_key ), 'woo-product-feed' ); // @phpstan-ignore function.notFound

		$status = array(
			'scheduled_at' => time(),
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

		// Start an immediate async action to generate the feed.
		// @phpstan-ignore-next-line function.notFound -- Action Scheduler.
		as_enqueue_async_action(
			self::FEED_GENERATION_ACTION,
			array( $option_key ),
			'woo-product-feed',
			true,
			1
		);

		// Manually force an async request to be dispatched to process the action immediately.
		if ( class_exists( ActionScheduler_AsyncRequest_QueueRunner::class ) && class_exists( ActionScheduler_Store::class ) ) {
			$store         = ActionScheduler_Store::instance();
			$async_request = new ActionScheduler_AsyncRequest_QueueRunner( $store );
			$async_request->dispatch();
		}

		return $status;
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

		if ( ! is_array( $status ) || ! isset( $status['state'] ) || self::STATE_SCHEDULED !== $status['state'] ) {
			wc_get_logger()->error( 'Invalid feed generation status', array( 'status' => $status ) );
			return;
		}

		$status['state'] = self::STATE_IN_PROGRESS;
		update_option( $option_key, $status );

		try {
			$feed   = $this->integration->create_feed();
			$walker = ProductWalker::from_integration( $this->integration, $feed );

			// Add dynamic args to the mapper.
			$args = $status['args'] ?? array();
			if (
				isset( $args['_product_fields'] )
				&& is_string( $args['_product_fields'] ) &&
				! empty( $args['_product_fields'] )
			) {
				$this->integration->get_product_mapper()->set_fields( $args['_product_fields'] );
			}
			if (
				isset( $args['_variation_fields'] )
				&& is_string( $args['_variation_fields'] ) &&
				! empty( $args['_variation_fields'] )
			) {
				$this->integration->get_product_mapper()->set_variation_fields( $args['_variation_fields'] );
			}

			$walker->walk(
				function ( WalkerProgress $progress ) use ( &$status, $option_key ) {
					$status = $this->update_feed_progress( $status, $progress );
					update_option( $option_key, $status );
				}
			);

			// Store the final details.
			$status['state']        = self::STATE_COMPLETED;
			$status['url']          = $feed->get_file_url();
			$status['path']         = $feed->get_file_path();
			$status['completed_at'] = time();
			update_option( $option_key, $status );

			// Schedule another action to delete the file after the expiry time.
			// @phpstan-ignore-next-line function.notFound -- Action Scheduler.
			as_schedule_single_action(
				time() + self::FEED_EXPIRY,
				self::FEED_DELETION_ACTION,
				array(
					$option_key,
					$feed->get_file_path(),
				),
				'woo-product-feed',
				true
			);
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				'Feed generation failed',
				array(
					'error'      => $e->getMessage(),
					'option_key' => $option_key,
				)
			);

			$status['state']     = self::STATE_FAILED;
			$status['error']     = $e->getMessage();
			$status['failed_at'] = time();
			update_option( $option_key, $status );
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

		// If there is no option, there is nothing to force. If the option is invalid, we can restart.
		if ( ! is_array( $status ) || ! $this->validate_status( $status ) ) {
			return $this->get_status( $args );
		}

		switch ( $status['state'] ?? '' ) {
			case self::STATE_SCHEDULED:
				// If generation is scheduled, we can just let it be and return the current status.
				// It should start shortly.
				return $status;

			case self::STATE_IN_PROGRESS:
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
	 * Updates the feed progress while the feed is being generated.
	 *
	 * @param array          $status   The last previously known status.
	 * @param WalkerProgress $progress The progress of the walker.
	 * @return array                   Updated status of the feed generation.
	 */
	private function update_feed_progress( array $status, WalkerProgress $progress ): array {
		$status['progress']  = $progress->total_count > 0
			? round( ( $progress->processed_items / $progress->total_count ) * 100, 2 )
			: 0;
		$status['processed'] = $progress->processed_items;
		$status['total']     = $progress->total_count;
		return $status;
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

		// All good.
		return true;
	}
}
