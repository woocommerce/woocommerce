<?php
/**
 * WP-CLI command for migrating products to per-type post types.
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
 * Migrate product post types for the product_type_post_types feature.
 *
 * ## EXAMPLES
 *
 *     # Migrate products to per-type post types
 *     wp wc product-type-migration migrate --batch-size=500
 *
 *     # Rollback to the legacy 'product' post type
 *     wp wc product-type-migration rollback --batch-size=500
 *
 *     # Show current migration status
 *     wp wc product-type-migration status
 */
class MigrationCLICommand extends WP_CLI_Command {

	/**
	 * Mapping of product type taxonomy terms to new post types.
	 */
	private const TYPE_MAP = array(
		ProductType::SIMPLE   => 'wc_product_simple',
		ProductType::VARIABLE => 'wc_product_variable',
		ProductType::GROUPED  => 'wc_product_grouped',
		ProductType::EXTERNAL => 'wc_product_external',
	);

	/**
	 * Register this command with WP-CLI.
	 */
	public static function register() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		WP_CLI::add_command( 'wc product-type-migration', self::class );
	}

	/**
	 * Migrate products from 'product' post type to per-type post types.
	 *
	 * Products without a product_type taxonomy term are treated as simple.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<number>]
	 * : Number of products to process per batch.
	 * ---
	 * default: 500
	 * ---
	 *
	 * [--dry-run]
	 * : Show what would be migrated without making changes.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function migrate( $args, $assoc_args ) {
		global $wpdb;

		$batch_size = (int) ( $assoc_args['batch-size'] ?? 500 );
		$dry_run    = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			WP_CLI::log( '--- DRY RUN MODE ---' );
		}

		// Count products by type.
		$counts = $this->get_product_type_counts();
		WP_CLI::log( 'Current product distribution:' );
		foreach ( $counts as $type => $count ) {
			WP_CLI::log( sprintf( '  %s: %d', $type, $count ) );
		}

		$total_migrated = 0;
		$offset         = 0;

		while ( true ) {
			// Get a batch of products with post_type = 'product'.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$products = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, COALESCE(
						(SELECT t.slug FROM {$wpdb->terms} t
						 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
						 INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
						 WHERE tr.object_id = p.ID AND tt.taxonomy = 'product_type'
						 LIMIT 1), 'simple'
					) as product_type
					FROM {$wpdb->posts} p
					WHERE p.post_type = 'product'
					ORDER BY p.ID ASC
					LIMIT %d OFFSET %d",
					$batch_size,
					$offset
				)
			);

			if ( empty( $products ) ) {
				break;
			}

			$batch_updates = array();
			foreach ( $products as $product ) {
				$new_post_type = self::TYPE_MAP[ $product->product_type ] ?? 'wc_product_simple';
				$batch_updates[ $new_post_type ][] = (int) $product->ID;
			}

			if ( ! $dry_run ) {
				foreach ( $batch_updates as $new_post_type => $ids ) {
					$id_placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->posts} SET post_type = %s WHERE ID IN ($id_placeholders)",
							array_merge( array( $new_post_type ), $ids )
						)
					);
					$total_migrated += count( $ids );
				}
			} else {
				foreach ( $batch_updates as $new_post_type => $ids ) {
					WP_CLI::log( sprintf( '  Would migrate %d products to %s', count( $ids ), $new_post_type ) );
					$total_migrated += count( $ids );
				}
			}

			$offset += $batch_size;
			WP_CLI::log( sprintf( 'Processed %d products...', $offset ) );
		}

		if ( ! $dry_run ) {
			// Invalidate WordPress object caches.
			wp_cache_flush();
			WP_CLI::log( 'Object cache flushed.' );
		}

		WP_CLI::success( sprintf( '%s %d products.', $dry_run ? 'Would migrate' : 'Migrated', $total_migrated ) );
	}

	/**
	 * Rollback: move all per-type products back to the 'product' post type.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<number>]
	 * : Number of products to process per batch.
	 * ---
	 * default: 500
	 * ---
	 *
	 * [--dry-run]
	 * : Show what would be rolled back without making changes.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function rollback( $args, $assoc_args ) {
		global $wpdb;

		$batch_size = (int) ( $assoc_args['batch-size'] ?? 500 );
		$dry_run    = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			WP_CLI::log( '--- DRY RUN MODE ---' );
		}

		$new_post_types = array_values( self::TYPE_MAP );
		$placeholders   = implode( ', ', array_fill( 0, count( $new_post_types ), '%s' ) );
		$total_rolled   = 0;

		// Count how many exist.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ($placeholders)",
				$new_post_types
			)
		);

		WP_CLI::log( sprintf( 'Found %d products to roll back.', $total ) );

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Would roll back %d products.', $total ) );
			return;
		}

		while ( true ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$affected = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET post_type = 'product' WHERE post_type IN ($placeholders) LIMIT %d",
					array_merge( $new_post_types, array( $batch_size ) )
				)
			);

			if ( 0 === $affected || false === $affected ) {
				break;
			}

			$total_rolled += $affected;
			WP_CLI::log( sprintf( 'Rolled back %d products...', $total_rolled ) );
		}

		wp_cache_flush();
		WP_CLI::log( 'Object cache flushed.' );
		WP_CLI::success( sprintf( 'Rolled back %d products to post_type=product.', $total_rolled ) );
	}

	/**
	 * Show the current migration status.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function status( $args, $assoc_args ) {
		global $wpdb;

		$post_types = array_merge( array( 'product' ), array_values( self::TYPE_MAP ), array( 'product_variation' ) );

		WP_CLI::log( 'Product post type distribution:' );
		WP_CLI::log( '' );

		$total = 0;
		foreach ( $post_types as $pt ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
					$pt
				)
			);
			if ( $count > 0 ) {
				WP_CLI::log( sprintf( '  %-25s %d', $pt, $count ) );
				$total += $count;
			}
		}

		WP_CLI::log( sprintf( '  %-25s %d', '---', $total ) );

		$feature_enabled = \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_type_post_types' );
		WP_CLI::log( '' );
		WP_CLI::log( sprintf( 'Feature flag: %s', $feature_enabled ? 'ENABLED' : 'DISABLED' ) );
	}

	/**
	 * Get product counts by type from the taxonomy.
	 *
	 * @return array<string, int> Type name => count.
	 */
	private function get_product_type_counts(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			"SELECT t.slug as product_type, COUNT(*) as count
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
			LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_type'
			LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
			WHERE p.post_type = 'product'
			GROUP BY t.slug"
		);

		$counts = array();
		foreach ( $results as $row ) {
			$type            = $row->product_type ?: '(unset/simple)';
			$counts[ $type ] = (int) $row->count;
		}

		return $counts;
	}
}
