<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Caches\ProductCache;

defined( 'ABSPATH' ) || exit;

/**
 * What each product fails, kept in product meta so the Products list can filter on it.
 * One row per value: a check id, or a status with a channel such as not_listable:chatgpt,
 * plus `checked` on every product the checks cover, so the product total stays live too.
 * A product with no rows is one the checks leave out.
 *
 * @since 11.4.0
 */
class ProductFailures {

	public const META_KEY = '_wc_agent_readiness_failed';

	/**
	 * When a product's rows were last written. Hidden from the editor, and not a check value.
	 */
	public const WRITTEN_META = '_wc_agent_readiness_written_at';

	/**
	 * Products per cleanup delete statement.
	 */
	public const CLEANUP_CHUNK = 100;

	/**
	 * The value every checked product has, passing or not.
	 */
	public const CHECKED = 'checked';

	/**
	 * Counts read in this request, by site id, so a switch_to_blog() reads the other site's; cleared on every write.
	 *
	 * @var array<int, array<string, int>>
	 */
	private static array $counts = array();

	/**
	 * The value for a status on a channel.
	 *
	 * @param string $status  ProductScan status.
	 * @param string $channel Channel id.
	 */
	public static function channel_value( string $status, string $channel ): string {
		return $status . ':' . $channel;
	}

	/**
	 * Store what a product fails, writing only what changed.
	 *
	 * @param int                                                             $product_id   Product id.
	 * @param array<string, array<string, array{field:string,effect:string}>> $failures     Channel map of each failed check, by check id.
	 * @param bool                                                            $mark_written Whether to record the write time. A save does, so cleanup keeps its rows. The scan's own writes don't, so they can be removed.
	 */
	public static function store( int $product_id, array $failures, bool $mark_written = true ): void {
		$values = array_merge( array( self::CHECKED ), array_map( 'strval', array_keys( $failures ) ) );
		foreach ( ProductScan::statuses( array_values( $failures ) ) as $channel => $status ) {
			if ( ProductScan::COMPLETE !== $status ) {
				$values[] = self::channel_value( $status, $channel );
			}
		}
		unset( self::$counts[ get_current_blog_id() ] );
		if ( $mark_written ) {
			// Before the rows change. Cleanup that runs during this save then sees a current write and keeps the rows.
			update_post_meta( $product_id, self::WRITTEN_META, sprintf( '%.6F', microtime( true ) ) );
		}
		$stored = self::stored( $product_id );
		foreach ( array_diff( $stored, $values ) as $value ) {
			delete_post_meta( $product_id, self::META_KEY, $value );
		}
		foreach ( array_diff( $values, $stored ) as $value ) {
			add_post_meta( $product_id, self::META_KEY, $value );
		}
	}

	/**
	 * What a product fails.
	 *
	 * @param int $product_id Product id.
	 * @return array<int, string>
	 */
	public static function for_product( int $product_id ): array {
		return array_values( array_diff( self::stored( $product_id ), array( self::CHECKED ) ) );
	}

	/**
	 * Remove a product's rows, for a product the checks no longer cover.
	 *
	 * @param int $product_id Product id.
	 */
	public static function forget( int $product_id ): void {
		unset( self::$counts[ get_current_blog_id() ] );
		delete_post_meta( $product_id, self::META_KEY );
		delete_post_meta( $product_id, self::WRITTEN_META );
	}

	/**
	 * Every stored value of a product, `checked` included. Empty for a product no scan or save has tested.
	 *
	 * @param int $product_id Product id.
	 * @return array<int, string>
	 */
	public static function stored( int $product_id ): array {
		return array_map( 'strval', (array) get_post_meta( $product_id, self::META_KEY, false ) );
	}

	/**
	 * Product ids that have stored rows right now.
	 *
	 * @return array<int, int>
	 */
	public static function stored_product_ids(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Only this query finds rows of products the scan didn't visit.
		return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META_KEY ) ) );
	}

	/**
	 * Remove the rows of products a scan didn't test, such as those now hidden from the catalog.
	 * Rows written at or after $written_after stay: a product skipped by its batch can be saved again before the scan finishes.
	 *
	 * @param array<int, int> $had_rows      Product ids that had rows when the scan started.
	 * @param array<int, int> $tested        Product ids the scan tested.
	 * @param float|null      $written_after Unix time the scan started, or null to drop every untested id.
	 */
	public static function keep_only( array $had_rows, array $tested, ?float $written_after = null ): void {
		unset( self::$counts[ get_current_blog_id() ] );
		$drop = array_map( 'intval', array_diff( $had_rows, $tested ) );
		if ( array() === $drop ) {
			return;
		}
		self::forget_unless_written_since( $drop, $written_after );
	}

	/**
	 * Remove the rows of these products, except rows whose write time is at or after $written_after.
	 *
	 * The write time is checked in the delete. A list of fresh ids read earlier would miss a save that commits before the loop reaches that product.
	 *
	 * @param array<int, int> $product_ids   Product ids.
	 * @param float|null      $written_after Unix time the scan started, or null to remove them all.
	 */
	public static function forget_unless_written_since( array $product_ids, ?float $written_after ): void {
		$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
		if ( array() === $product_ids ) {
			return;
		}
		if ( null === $written_after ) {
			foreach ( $product_ids as $product_id ) {
				self::forget( $product_id );
			}
			return;
		}
		unset( self::$counts[ get_current_blog_id() ] );
		global $wpdb;
		// A scan can name thousands of products. Each statement stays small, and each one decides from the write time it sees.
		foreach ( array_chunk( $product_ids, self::CLEANUP_CHUNK ) as $chunk ) {
			$ids = implode( ',', $chunk );
			// $ids are absint. The write time is in this delete, so a save that commits after a separate read is not removed.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE pm FROM {$wpdb->postmeta} AS pm
					LEFT JOIN {$wpdb->postmeta} AS fresh
						ON fresh.post_id = pm.post_id
						AND fresh.meta_key = %s
						AND CAST(fresh.meta_value AS DECIMAL(16,6)) >= %f
					WHERE pm.post_id IN ($ids)
					AND pm.meta_key IN (%s, %s)
					AND fresh.meta_id IS NULL",
					self::WRITTEN_META,
					$written_after,
					self::META_KEY,
					self::WRITTEN_META
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $deleted ) {
				continue;
			}
			// The delete bypasses the meta API, so clear what its deleted_post_meta listeners would, also while
			// cache invalidation is suspended: the meta cache, cached post queries like the Products list filter,
			// WooCommerce's product meta cache and the cached product (product instance caching).
			wp_cache_delete_multiple( $chunk, 'post_meta' );
			wp_cache_set_posts_last_changed();
			$product_cache = wc_get_container()->get( ProductCache::class );
			foreach ( $chunk as $product_id ) {
				\WC_Cache_Helper::invalidate_cache_group( 'object_' . $product_id );
				$product_cache->remove( $product_id );
			}
		}
	}

	/**
	 * How many products have each value. Read once per request per site: the report and the settings page both ask.
	 *
	 * @return array<string, int>
	 */
	public static function counts(): array {
		global $wpdb;
		$site = get_current_blog_id();
		if ( isset( self::$counts[ $site ] ) ) {
			return self::$counts[ $site ];
		}
		// Two saves can both add a value before either sees it, so count products, not rows.
		$rows   = (array) $wpdb->get_results( $wpdb->prepare( "SELECT meta_value, COUNT(DISTINCT post_id) AS products FROM {$wpdb->postmeta} WHERE meta_key = %s GROUP BY meta_value", self::META_KEY ), ARRAY_A );
		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ (string) $row['meta_value'] ] = (int) $row['products'];
		}
		self::$counts[ $site ] = $counts;
		return $counts;
	}
}
