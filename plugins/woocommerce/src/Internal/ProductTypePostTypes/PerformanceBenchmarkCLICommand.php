<?php
/**
 * WP-CLI command for benchmarking product type post type performance.
 *
 * @package WooCommerce\Internal\ProductTypePostTypes
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductTypePostTypes;

use Automattic\WooCommerce\Enums\ProductType;
use WP_CLI;
use WP_CLI_Command;

defined( 'ABSPATH' ) || exit;

/**
 * Benchmark performance of the product_type_post_types feature.
 *
 * ## EXAMPLES
 *
 *     wp wc product-type-benchmark run
 */
class PerformanceBenchmarkCLICommand extends WP_CLI_Command {

	/**
	 * Register this command with WP-CLI.
	 */
	public static function register() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		WP_CLI::add_command( 'wc product-type-benchmark', self::class );
	}

	/**
	 * Run all performance benchmarks.
	 *
	 * ## OPTIONS
	 *
	 * [--iterations=<number>]
	 * : Number of iterations per benchmark.
	 * ---
	 * default: 100
	 * ---
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function run( $args, $assoc_args ) {
		global $wpdb;

		$iterations = (int) ( $assoc_args['iterations'] ?? 100 );

		$feature_enabled = \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_type_post_types' );
		$mode            = $feature_enabled ? 'POST TYPE' : 'TAXONOMY';

		WP_CLI::log( sprintf( '=== Performance Benchmark (mode: %s, iterations: %d) ===', $mode, $iterations ) );
		WP_CLI::log( '' );

		// Count products.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$product_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			WHERE post_type IN ('product', 'wc_product_simple', 'wc_product_variable', 'wc_product_grouped', 'wc_product_external')
			AND post_status = 'publish'"
		);
		WP_CLI::log( sprintf( 'Total published products: %d', $product_count ) );
		WP_CLI::log( '' );

		// Get a sample of product IDs.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$sample_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type IN ('product', 'wc_product_simple', 'wc_product_variable', 'wc_product_grouped', 'wc_product_external')
				AND post_status = 'publish'
				ORDER BY RAND()
				LIMIT %d",
				min( $iterations, $product_count )
			)
		);

		if ( empty( $sample_ids ) ) {
			WP_CLI::warning( 'No products found to benchmark.' );
			return;
		}

		// Benchmark 1: Type resolution speed.
		WP_CLI::log( '--- Benchmark 1: Type Resolution Speed ---' );
		$this->benchmark_type_resolution( $sample_ids, $iterations );

		// Benchmark 2: Shop page query speed.
		WP_CLI::log( '' );
		WP_CLI::log( '--- Benchmark 2: Shop Page Query Speed ---' );
		$this->benchmark_shop_query( $iterations );

		// Benchmark 3: Product count overhead.
		WP_CLI::log( '' );
		WP_CLI::log( '--- Benchmark 3: Product Count Overhead ---' );
		$this->benchmark_product_count( $iterations );

		// Benchmark 4: Product CRUD.
		WP_CLI::log( '' );
		WP_CLI::log( '--- Benchmark 4: Product Creation Speed ---' );
		$this->benchmark_product_creation( min( 10, $iterations ) );

		WP_CLI::log( '' );
		WP_CLI::success( 'Benchmarks complete.' );
	}

	/**
	 * Benchmark type resolution for individual products.
	 *
	 * @param array $ids        Product IDs.
	 * @param int   $iterations Number of iterations.
	 */
	private function benchmark_type_resolution( array $ids, int $iterations ): void {
		// Clear all caches.
		wp_cache_flush();

		$start = microtime( true );
		for ( $i = 0; $i < $iterations; $i++ ) {
			$id = $ids[ $i % count( $ids ) ];
			\WC_Product_Factory::get_product_type( (int) $id );
		}
		$elapsed = microtime( true ) - $start;

		WP_CLI::log( sprintf(
			'  %d type lookups in %.4fs (avg: %.4fms per lookup)',
			$iterations,
			$elapsed,
			( $elapsed / $iterations ) * 1000
		) );

		// Now with warm cache.
		$start = microtime( true );
		for ( $i = 0; $i < $iterations; $i++ ) {
			$id = $ids[ $i % count( $ids ) ];
			\WC_Product_Factory::get_product_type( (int) $id );
		}
		$elapsed_warm = microtime( true ) - $start;

		WP_CLI::log( sprintf(
			'  %d type lookups (warm cache) in %.4fs (avg: %.4fms per lookup)',
			$iterations,
			$elapsed_warm,
			( $elapsed_warm / $iterations ) * 1000
		) );
	}

	/**
	 * Benchmark shop page queries.
	 *
	 * @param int $iterations Number of iterations.
	 */
	private function benchmark_shop_query( int $iterations ): void {
		$iterations = min( $iterations, 20 ); // Limit to avoid heavy DB load.
		wp_cache_flush();

		$start = microtime( true );
		for ( $i = 0; $i < $iterations; $i++ ) {
			wc_get_products(
				array(
					'status'  => 'publish',
					'limit'   => 20,
					'page'    => 1,
					'orderby' => 'date',
					'order'   => 'DESC',
					'return'  => 'ids',
				)
			);
			// Flush cache each time to measure cold query.
			wp_cache_flush();
		}
		$elapsed = microtime( true ) - $start;

		WP_CLI::log( sprintf(
			'  %d shop queries (20 products each) in %.4fs (avg: %.4fms per query)',
			$iterations,
			$elapsed,
			( $elapsed / $iterations ) * 1000
		) );

		// Type-filtered query.
		$start = microtime( true );
		for ( $i = 0; $i < $iterations; $i++ ) {
			wc_get_products(
				array(
					'status'  => 'publish',
					'type'    => 'simple',
					'limit'   => 20,
					'page'    => 1,
					'orderby' => 'date',
					'order'   => 'DESC',
					'return'  => 'ids',
				)
			);
			wp_cache_flush();
		}
		$elapsed_typed = microtime( true ) - $start;

		WP_CLI::log( sprintf(
			'  %d type-filtered queries (type=simple, 20 products) in %.4fs (avg: %.4fms per query)',
			$iterations,
			$elapsed_typed,
			( $elapsed_typed / $iterations ) * 1000
		) );
	}

	/**
	 * Benchmark product counting.
	 *
	 * @param int $iterations Number of iterations.
	 */
	private function benchmark_product_count( int $iterations ): void {
		$iterations = min( $iterations, 50 );
		wp_cache_flush();

		$start = microtime( true );
		for ( $i = 0; $i < $iterations; $i++ ) {
			$feature_enabled = \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_type_post_types' );
			if ( $feature_enabled ) {
				// Need to count across all post types.
				$total = 0;
				foreach ( array( 'wc_product_simple', 'wc_product_variable', 'wc_product_grouped', 'wc_product_external' ) as $pt ) {
					$counts = wp_count_posts( $pt );
					$total += $counts->publish ?? 0;
				}
			} else {
				$counts = wp_count_posts( 'product' );
				// Type filtering requires additional taxonomy query.
			}
		}
		$elapsed = microtime( true ) - $start;

		WP_CLI::log( sprintf(
			'  %d product count operations in %.4fs (avg: %.4fms per count)',
			$iterations,
			$elapsed,
			( $elapsed / $iterations ) * 1000
		) );
	}

	/**
	 * Benchmark product creation.
	 *
	 * @param int $iterations Number of products to create and delete.
	 */
	private function benchmark_product_creation( int $iterations ): void {
		$created_ids = array();

		$start = microtime( true );
		for ( $i = 0; $i < $iterations; $i++ ) {
			$product = new \WC_Product_Simple();
			$product->set_name( 'Benchmark Product ' . $i );
			$product->set_status( 'draft' );
			$product->set_regular_price( '9.99' );
			$product->save();
			$created_ids[] = $product->get_id();
		}
		$elapsed = microtime( true ) - $start;

		WP_CLI::log( sprintf(
			'  %d product creates in %.4fs (avg: %.4fms per create)',
			$iterations,
			$elapsed,
			( $elapsed / $iterations ) * 1000
		) );

		// Clean up.
		foreach ( $created_ids as $id ) {
			wp_delete_post( $id, true );
		}
	}
}
