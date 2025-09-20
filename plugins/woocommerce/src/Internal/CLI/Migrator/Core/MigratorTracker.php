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
		add_action( 'wc_migrator_error_occurred', array( $this, 'on_error_occurred' ), 10, 3 );
	}

	/**
	 * Handle migration session start.
	 *
	 * @param string $platform Platform identifier (e.g., 'shopify').
	 * @param array  $metadata Session metadata.
	 */
	public function on_session_started( string $platform, array $metadata ): void {
		$this->current_session = array(
			'platform'        => $platform,
			'started_at'      => time(),
			'products_total'  => 0,
			'products_processed' => 0,
			'product_types'   => array(),
			'errors'          => array(),
			'total_time'      => 0,
			'session_id'      => $metadata['session_id'] ?? uniqid(),
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

		$this->current_session['products_processed'] += $batch_results['stats']['successful'] ?? 0;

		$this->track_product_types( $mapped_data );

		if ( ! empty( $batch_results['results'] ) ) {
			$this->track_batch_errors( $batch_results['results'] );
		}
	}

	/**
	 * Handle migration session completion.
	 *
	 * @param string $platform Platform identifier.
	 * @param array  $final_stats Final migration statistics.
	 */
	public function on_session_completed( string $platform, array $final_stats ): void {
		if ( empty( $this->current_session ) ) {
			return;
		}

		$this->current_session['total_time'] = time() - $this->current_session['started_at'];

		$this->current_session['completed_at'] = time();

		$this->current_session['products_total'] = $final_stats['total_found'] ?? $this->current_session['products_processed'];

		$this->save_session_data();

		$this->current_session = array();
	}

	/**
	 * Handle error occurrence during migration.
	 *
	 * @param string $error_type  Type of error (e.g., 'fetch', 'import', 'mapping').
	 * @param string $message     Error message.
	 * @param array  $context     Additional error context.
	 */
	public function on_error_occurred( string $error_type, string $message, array $context ): void {
		if ( empty( $this->current_session ) ) {
			return;
		}

		$this->current_session['errors'][] = array(
			'type'      => $error_type,
			'message'   => $message,
			'timestamp' => time(),
			'context'   => $context,
		);
	}

	/**
	 * Track product types from mapped data.
	 *
	 * @param array $mapped_data Array of mapped product data.
	 */
	private function track_product_types( array $mapped_data ): void {
		foreach ( $mapped_data as $product ) {
			$type = $product['type'] ?? 'simple';
			
			if ( ! isset( $this->current_session['product_types'][ $type ] ) ) {
				$this->current_session['product_types'][ $type ] = 0;
			}
			
			$this->current_session['product_types'][ $type ]++;
		}
	}

	/**
	 * Track errors from batch results.
	 *
	 * @param array $batch_results Individual result entries from batch import.
	 */
	private function track_batch_errors( array $batch_results ): void {
		foreach ( $batch_results as $result ) {
			if ( 'error' === ( $result['status'] ?? '' ) ) {
				$this->current_session['errors'][] = array(
					'type'      => 'import',
					'message'   => $result['message'] ?? 'Unknown import error',
					'timestamp' => time(),
					'context'   => array(
						'product_data' => $result['product_data'] ?? array(),
					),
				);
			}
		}
	}

	/**
	 * Save current session data to persistent storage.
	 */
	private function save_session_data(): void {
		$analytics = $this->get_stored_analytics();

		$platform = $this->current_session['platform'];
		if ( ! isset( $analytics['platforms'][ $platform ] ) ) {
			$analytics['platforms'][ $platform ] = array(
				'sessions'           => array(),
				'total_products'     => 0,
				'total_sessions'     => 0,
				'total_time'         => 0,
				'product_types'      => array(),
				'last_migration'     => null,
			);
		}

		$platform_data = &$analytics['platforms'][ $platform ];
		$platform_data['sessions'][] = $this->current_session;
		$platform_data['total_products'] += $this->current_session['products_processed'];
		$platform_data['total_sessions']++;
		$platform_data['total_time'] += $this->current_session['total_time'];
		$platform_data['last_migration'] = $this->current_session['completed_at'];

		foreach ( $this->current_session['product_types'] as $type => $count ) {
			if ( ! isset( $platform_data['product_types'][ $type ] ) ) {
				$platform_data['product_types'][ $type ] = 0;
			}
			$platform_data['product_types'][ $type ] += $count;
		}

		$analytics['totals']['products_migrated_in'] += $this->current_session['products_processed'];
		$analytics['totals']['total_sessions']++;
		$analytics['totals']['total_migration_time'] += $this->current_session['total_time'];

		if ( count( $platform_data['sessions'] ) > 10 ) {
			$platform_data['sessions'] = array_slice( $platform_data['sessions'], -10 );
		}

		$this->save_analytics( $analytics );
	}

	/**
	 * Get comprehensive migration data for WC_Tracker integration.
	 *
	 * @return array Formatted data for telemetry reporting.
	 */
	public function get_data(): array {
		$analytics = $this->get_stored_analytics();

		$data = array(
			'products_migrated_in'    => $analytics['totals']['products_migrated_in'],
			'total_migration_sessions' => $analytics['totals']['total_sessions'],
			'total_migration_time'    => $analytics['totals']['total_migration_time'],
			'platforms_used'          => array_keys( $analytics['platforms'] ),
			'platform_breakdown'      => array(),
		);

		foreach ( $analytics['platforms'] as $platform => $platform_data ) {
			$data['platform_breakdown'][ $platform ] = array(
				'products_migrated'  => $platform_data['total_products'],
				'sessions_count'     => $platform_data['total_sessions'],
				'total_time'         => $platform_data['total_time'],
				'product_types'      => $platform_data['product_types'],
				'last_migration'     => $platform_data['last_migration'],
				'avg_products_per_session' => $platform_data['total_sessions'] > 0 
					? round( $platform_data['total_products'] / $platform_data['total_sessions'] ) 
					: 0,
			);
		}

		$data['recent_errors'] = $this->get_recent_error_stats();

		return $data;
	}

	/**
	 * Get error statistics for recent migrations.
	 *
	 * @return array Error statistics.
	 */
	private function get_recent_error_stats(): array {
		$analytics = $this->get_stored_analytics();
		$cutoff_time = time() - ( 30 * DAY_IN_SECONDS );
		
		$error_types = array();
		$total_errors = 0;

		foreach ( $analytics['platforms'] as $platform_data ) {
			foreach ( $platform_data['sessions'] as $session ) {
				if ( ( $session['completed_at'] ?? 0 ) < $cutoff_time ) {
					continue;
				}

				foreach ( $session['errors'] as $error ) {
					$type = $error['type'] ?? 'unknown';
					if ( ! isset( $error_types[ $type ] ) ) {
						$error_types[ $type ] = 0;
					}
					$error_types[ $type ]++;
					$total_errors++;
				}
			}
		}

		return array(
			'total_errors'  => $total_errors,
			'error_types'   => $error_types,
		);
	}

	/**
	 * Get stored analytics data with defaults.
	 *
	 * @return array Analytics data structure.
	 */
	private function get_stored_analytics(): array {
		$defaults = array(
			'totals' => array(
				'products_migrated_in'   => 0,
				'total_sessions'         => 0,
				'total_migration_time'   => 0,
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
		update_option( self::OPTION_NAME, $analytics );
	}

	/**
	 * Clear all stored analytics data.
	 * Useful for development/testing or user privacy requests
	 */
	public function clear_data(): void {
		delete_option( self::OPTION_NAME );
		$this->current_session = array();
	}
}