<?php
/**
 * Async Generator class.
 *
 * @package WooCommerce\Internal\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductCatalog;

use Automattic\WooCommerce\ProductCatalog\ProductWalker;
use Automattic\WooCommerce\ProductCatalog\WalkerProgress;

defined( 'ABSPATH' ) || exit;

/**
 * Async Generator for the product catalog.
 *
 * @internal This class is intended for internal use only and should not be used by extensions.
 * @package  WooCommerce\Internal\ProductCatalog
 */
final class AsyncGenerator {
	/**
	 * The Action Scheduler action hook for the feed generation.
	 */
	const FEED_GENERATION_ACTION = 'woocommerce_product_catalog_feed_generation';

	/**
	 * The Action Scheduler action hook for the feed deletion.
	 */
	const FEED_DELETION_ACTION = 'woocommerce_product_catalog_feed_deletion';

	/**
	 * The option key for the feed generation status.
	 */
	const OPTION_KEY = 'woocommerce_product_catalog_feed_status';

	/**
	 * Feed expiry time, once completed (20 minutes).
	 */
	const FEED_EXPIRY = 20 * MINUTE_IN_SECONDS;

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
	private POSIntegration $integration;

	/**
	 * Dependency injector.
	 *
	 * @param POSIntegration $integration The integration instance.
	 *
	 * @internal
	 */
	final public function init( POSIntegration $integration ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Injection methods must be marked final per WooCommerce standards.
		$this->integration = $integration;
	}

	/**
	 * Register hooks for the async generator.
	 */
	public function register(): void {
		wc_get_container()->get( self::class )->register_hooks();
	}

	/**
	 * Register hooks for the async generator.
	 */
	public function register_hooks(): void {
		add_action( self::FEED_GENERATION_ACTION, array( self::class, 'handle_feed_generation_action' ) );
		add_action( self::FEED_DELETION_ACTION, array( self::class, 'handle_feed_deletion_action' ), 10, 1 );
	}

	/**
	 * Static wrapper for feed generation action.
	 */
	public static function handle_feed_generation_action(): void {
		$instance = wc_get_container()->get( self::class );
		$instance->feed_generation_action();
	}

	/**
	 * Static wrapper for feed deletion action.
	 *
	 * @param array $args Arguments with 'path' key.
	 */
	public static function handle_feed_deletion_action( array $args ): void {
		$instance = wc_get_container()->get( self::class );
		$instance->feed_deletion_action( $args );
	}

	/**
	 * Returns the current feed generation status.
	 * Initiates one if not already running.
	 *
	 * @param bool $force Whether to force regeneration.
	 * @return array The feed generation status.
	 */
	public function get_status( bool $force = false ): array {
		if ( $force ) {
			return $this->force_regeneration();
		}

		$status = get_option( self::OPTION_KEY );

		if ( false === $status ) {
			// Clear all previous actions to avoid race conditions.
			as_unschedule_all_actions( self::FEED_GENERATION_ACTION );

			// Schedule with 10 second delay.
			$delay     = 10;
			$action_id = as_schedule_single_action( time() + $delay, self::FEED_GENERATION_ACTION, array() );

			$status = array(
				'action_id' => $action_id,
				'state'     => self::STATE_SCHEDULED,
				'progress'  => 0,
				'processed' => 0,
				'total'     => -1,
			);

			update_option( self::OPTION_KEY, $status, false );
		}

		return $status;
	}

	/**
	 * Action scheduler callback for the feed generation.
	 */
	public function feed_generation_action(): void {
		$status = get_option( self::OPTION_KEY );

		if ( ! is_array( $status ) || ! isset( $status['state'] ) || self::STATE_SCHEDULED !== $status['state'] ) {
			return;
		}

		$status['state'] = self::STATE_IN_PROGRESS;
		update_option( self::OPTION_KEY, $status, false );

		try {
			$feed   = $this->integration->create_feed();
			$walker = new ProductWalker(
				$this->integration->get_product_mapper(),
				$this->integration->get_feed_validator(),
				$feed
			);

			$walker->walk(
				function ( WalkerProgress $progress ) use ( &$status ) {
					$status = $this->update_feed_progress( $status, $progress );
					update_option( self::OPTION_KEY, $status, false );
				}
			);

			// Store the final details.
			$status['state'] = self::STATE_COMPLETED;
			$status['url']   = $feed->get_file_url();
			$status['path']  = $feed->get_file_path();
			unset( $status['error'] );
			update_option( self::OPTION_KEY, $status, false );

			// Schedule deletion after expiry time.
			as_schedule_single_action(
				time() + self::FEED_EXPIRY,
				self::FEED_DELETION_ACTION,
				array( array( 'path' => $feed->get_file_path() ) )
			);
		} catch ( \Exception $e ) {
			$status['state'] = self::STATE_FAILED;
			$status['error'] = $e->getMessage();
			update_option( self::OPTION_KEY, $status, false );
		}
	}

	/**
	 * Forces a regeneration of the feed.
	 *
	 * @return array The feed generation status.
	 * @throws \Exception When regeneration cannot be forced.
	 */
	public function force_regeneration(): array {
		$status = get_option( self::OPTION_KEY );

		// If there is no option, start fresh generation.
		if ( false === $status ) {
			return $this->get_status();
		}

		switch ( $status['state'] ?? '' ) {
			case self::STATE_SCHEDULED:
				// Already scheduled, return current status.
				return $status;

			case self::STATE_IN_PROGRESS:
				throw new \Exception( 'Feed generation is already in progress and cannot be stopped.' );

			case self::STATE_COMPLETED:
			case self::STATE_FAILED:
				// Delete existing file and start fresh.
				if ( ! empty( $status['path'] ) && file_exists( $status['path'] ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					unlink( $status['path'] );
				}
				delete_option( self::OPTION_KEY );
				as_unschedule_all_actions( self::FEED_GENERATION_ACTION );
				as_unschedule_all_actions( self::FEED_DELETION_ACTION );
				return $this->get_status();

			default:
				throw new \Exception( 'Unknown feed generation state.' );
		}
	}

	/**
	 * Action scheduler callback for the feed deletion after expiry.
	 *
	 * @param array $args The arguments passed to the action.
	 */
	public function feed_deletion_action( array $args ): void {
		$path = $args['path'] ?? '';

		if ( ! empty( $path ) && file_exists( $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $path );
		}

		delete_option( self::OPTION_KEY );
	}

	/**
	 * Updates the feed progress while the feed is being generated.
	 *
	 * @param array          $status   The last previously known status.
	 * @param WalkerProgress $progress The progress of the walker.
	 * @return array Updated status of the feed generation.
	 */
	private function update_feed_progress( array $status, WalkerProgress $progress ): array {
		$status['progress']  = $progress->total_count > 0
			? round( ( $progress->processed_items / $progress->total_count ) * 100, 2 )
			: 0;
		$status['processed'] = $progress->processed_items;
		$status['total']     = $progress->total_count;
		return $status;
	}
}
