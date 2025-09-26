<?php
/**
 * Migrator Tracker
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Core;

defined( 'ABSPATH' ) || exit;

/**
 * MigratorTracker class.
 *
 * Implements subscriber pattern to track comprehensive migration analytics
 * for integration with WC_Tracker telemetry system.
 *
 * @internal This class is part of the CLI Migrator feature and should not be used directly.
 */
class MigratorTracker {

	/**
	 * Option name for storing migration analytics.
	 */
	private const OPTION_NAME = 'wc_migrator_analytics';

	/**
	 * Current migration session data.
	 *
	 * @var array
	 */
	private array $current_session = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks(): void {
		add_action( 'wc_migrator_session_started', array( $this, 'on_session_started' ), 10, 2 );
		add_action( 'wc_migrator_batch_processed', array( $this, 'on_batch_processed' ), 10, 3 );
		add_action( 'wc_migrator_session_completed', array( $this, 'on_session_completed' ), 10, 2 );
	}

	/**
	 * Handle migration session start.
	 *
	 * @param string $platform Platform identifier (e.g., 'shopify').
	 * @param array  $metadata Session metadata.
	 */
	public function on_session_started( string $platform, array $metadata ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->current_session = array(
			'platform'            => $platform,
			'started_at'          => time(),
			'products_total'      => 0,
			'products_attempted'  => 0,
			'products_successful' => 0,
			'products_failed'     => 0,
			'products_skipped'    => 0,
			'product_types'       => array(),
			'total_time'          => 0,
			'is_dry_run'          => $metadata['is_dry_run'] ?? false,
		);
	}

	/**
	 * Handle batch processing completion.
	 *
	 * @param array $batch_results Results from the batch import.
	 * @param array $source_data   Source platform data for the batch.
	 * @param array $mapped_data   Mapped WooCommerce data for the batch.
	 */
	public function on_batch_processed( array $batch_results, array $source_data, array $mapped_data ): void {
		if ( empty( $this->current_session ) ) {
			return;
		}

		// Track detailed statistics for better telemetry accuracy.
		$batch_stats                                   = $batch_results['stats'] ?? array();
		$this->current_session['products_attempted']  += count( $mapped_data );
		$this->current_session['products_successful'] += $batch_stats['successful'] ?? 0;
		$this->current_session['products_failed']     += $batch_stats['failed'] ?? 0;
		$this->current_session['products_skipped']    += $batch_stats['skipped'] ?? 0;

		$this->track_product_types( $mapped_data, $batch_results );
	}

	/**
	 * Handle migration session completion.
	 *
	 * @param string $platform    Platform identifier.
	 * @param array  $final_stats Final migration statistics.
	 */
	public function on_session_completed( string $platform, array $final_stats ): void {
		if ( empty( $this->current_session ) ) {
			// Log warning for debugging - session completed without active session.
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning(
					'Migration session completed event fired without active session.',
					array( 'source' => 'migrator_tracker' )
				);
			}
			return;
		}

		// Use consistent time() calls to avoid any timezone issues.
		$completion_time                       = time();
		$this->current_session['total_time']   = $completion_time - $this->current_session['started_at'];
		$this->current_session['completed_at'] = $completion_time;

		$this->current_session['products_total'] = $final_stats['total_found'] ?? $this->current_session['products_attempted'];

		$this->save_session_data();

		$this->current_session = array();
	}

	/**
	 * Track product types from mapped data and import results.
	 *
	 * Only count product types for successfully imported products to ensure
	 * telemetry accuracy.
	 *
	 * @param array $mapped_data   Array of mapped product data.
	 * @param array $batch_results Results from the batch import.
	 */
	private function track_product_types( array $mapped_data, array $batch_results ): void {
		$successful_results = array_filter(
			$batch_results['results'] ?? array(),
			function ( $result ) {
				return 'success' === ( $result['status'] ?? '' ) && 'skipped' !== ( $result['action'] ?? '' );
			}
		);

		// Only track types for successfully imported products.
		foreach ( $successful_results as $index => $result ) {
			if ( ! isset( $mapped_data[ $index ] ) ) {
				continue;
			}

			$product = $mapped_data[ $index ];
			$type    = $product['type'] ?? 'simple';

			if ( ! isset( $this->current_session['product_types'][ $type ] ) ) {
				$this->current_session['product_types'][ $type ] = 0;
			}

			++$this->current_session['product_types'][ $type ];
		}
	}

	/**
	 * Save current session data to persistent storage.
	 */
	private function save_session_data(): void {
		$analytics = $this->get_stored_analytics();
		$platform  = $this->current_session['platform'];

		if ( ! isset( $analytics['platforms'][ $platform ] ) ) {
			$analytics['platforms'][ $platform ] = array(
				'total_products_attempted'  => 0,
				'total_products_successful' => 0,
				'total_products_failed'     => 0,
				'total_products_skipped'    => 0,
				'total_sessions'            => 0,
				'total_time'                => 0,
				'product_types'             => array(),
				'last_migration'            => null,
				'dry_run_sessions'          => 0,
			);
		}

		$platform_data = &$analytics['platforms'][ $platform ];

		$products_attempted  = $this->current_session['products_attempted'] ?? 0;
		$products_successful = $this->current_session['products_successful'] ?? 0;
		$products_failed     = $this->current_session['products_failed'] ?? 0;
		$products_skipped    = $this->current_session['products_skipped'] ?? 0;
		$total_time          = $this->current_session['total_time'] ?? 0;
		$completed_at        = $this->current_session['completed_at'] ?? time();
		$product_types       = $this->current_session['product_types'] ?? array();
		$is_dry_run          = $this->current_session['is_dry_run'] ?? false;

		// Update platform statistics.
		if ( ! $is_dry_run ) {
			$platform_data['total_products_attempted']  += $products_attempted;
			$platform_data['total_products_successful'] += $products_successful;
			$platform_data['total_products_failed']     += $products_failed;
			$platform_data['total_products_skipped']    += $products_skipped;
			$platform_data['last_migration']             = $completed_at;
		} else {
			++$platform_data['dry_run_sessions'];
		}

		++$platform_data['total_sessions'];
		$platform_data['total_time'] += $total_time;

		foreach ( $product_types as $type => $count ) {
			if ( ! isset( $platform_data['product_types'][ $type ] ) ) {
				$platform_data['product_types'][ $type ] = 0;
			}
			$platform_data['product_types'][ $type ] += $count;
		}

		if ( ! isset( $analytics['totals'] ) || ! is_array( $analytics['totals'] ) ) {
			$analytics['totals'] = array();
		}

		// Only update global totals for non-dry-run sessions.
		if ( ! $is_dry_run ) {
			$analytics['totals']['products_attempted']  = ( $analytics['totals']['products_attempted'] ?? 0 ) + $products_attempted;
			$analytics['totals']['products_successful'] = ( $analytics['totals']['products_successful'] ?? 0 ) + $products_successful;
			$analytics['totals']['products_failed']     = ( $analytics['totals']['products_failed'] ?? 0 ) + $products_failed;
			$analytics['totals']['products_skipped']    = ( $analytics['totals']['products_skipped'] ?? 0 ) + $products_skipped;
		}

		$analytics['totals']['total_sessions']       = ( $analytics['totals']['total_sessions'] ?? 0 ) + 1;
		$analytics['totals']['total_migration_time'] = ( $analytics['totals']['total_migration_time'] ?? 0 ) + $total_time;
		$analytics['totals']['dry_run_sessions']     = ( $analytics['totals']['dry_run_sessions'] ?? 0 ) + ( $is_dry_run ? 1 : 0 );

		$this->save_analytics( $analytics );
	}

	/**
	 * Get comprehensive migration data for WC_Tracker integration.
	 *
	 * @return array Formatted data for telemetry reporting.
	 */
	public function get_data(): array {
		$analytics = $this->get_stored_analytics();

		$totals = $analytics['totals'] ?? array();

		$data = array(
			'products_attempted'       => $totals['products_attempted'] ?? 0,
			'products_successful'      => $totals['products_successful'] ?? 0,
			'products_failed'          => $totals['products_failed'] ?? 0,
			'products_skipped'         => $totals['products_skipped'] ?? 0,
			'total_migration_sessions' => $totals['total_sessions'] ?? 0,
			'total_migration_time'     => $totals['total_migration_time'] ?? 0,
			'dry_run_sessions'         => $totals['dry_run_sessions'] ?? 0,
			'platforms_used'           => array_keys( $analytics['platforms'] ?? array() ),
			'platform_breakdown'       => array(),
			'success_rate'             => $this->calculate_success_rate( $totals ),
		);

		$platforms = $analytics['platforms'] ?? array();
		foreach ( $platforms as $platform => $platform_data ) {
			$data['platform_breakdown'][ $platform ] = array(
				'products_attempted'  => $platform_data['total_products_attempted'] ?? 0,
				'products_successful' => $platform_data['total_products_successful'] ?? 0,
				'products_failed'     => $platform_data['total_products_failed'] ?? 0,
				'products_skipped'    => $platform_data['total_products_skipped'] ?? 0,
				'sessions_count'      => $platform_data['total_sessions'] ?? 0,
				'dry_run_sessions'    => $platform_data['dry_run_sessions'] ?? 0,
				'total_time'          => $platform_data['total_time'] ?? 0,
				'product_types'       => $platform_data['product_types'] ?? array(),
				'last_migration'      => $platform_data['last_migration'] ?? null,
				'success_rate'        => $this->calculate_success_rate( $platform_data ),
			);
		}

		return $data;
	}

	/**
	 * Calculate success rate as a percentage.
	 *
	 * @param array $stats Statistics array containing attempted and successful counts.
	 * @return float Success rate as a percentage (0-100).
	 */
	private function calculate_success_rate( array $stats ): float {
		$attempted  = $stats['total_products_attempted'] ?? $stats['products_attempted'] ?? 0;
		$successful = $stats['total_products_successful'] ?? $stats['products_successful'] ?? 0;

		if ( 0 === $attempted ) {
			return 0.0;
		}

		return round( ( $successful / $attempted ) * 100, 2 );
	}

	/**
	 * Get stored analytics data with defaults.
	 *
	 * @return array Analytics data structure.
	 */
	private function get_stored_analytics(): array {
		$defaults = array(
			'totals'    => array(
				'products_attempted'   => 0,
				'products_successful'  => 0,
				'products_failed'      => 0,
				'products_skipped'     => 0,
				'total_sessions'       => 0,
				'total_migration_time' => 0,
				'dry_run_sessions'     => 0,
			),
			'platforms' => array(),
		);

		$stored = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Save analytics data to WordPress options.
	 *
	 * @param array $analytics Analytics data to save.
	 */
	private function save_analytics( array $analytics ): void {
		if ( false === get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, $analytics, '', 'no' );
		} else {
			update_option( self::OPTION_NAME, $analytics, 'no' );
		}
	}

	/**
	 * Clear all stored analytics data.
	 * Useful for development/testing or user privacy requests.
	 */
	public function clear_data(): void {
		delete_option( self::OPTION_NAME );
		$this->current_session = array();
	}
}
