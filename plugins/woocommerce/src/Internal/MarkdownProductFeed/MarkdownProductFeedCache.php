<?php
/**
 * MarkdownProductFeedCache class file.
 *
 * @package Automattic\WooCommerce\Internal\MarkdownProductFeed
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MarkdownProductFeed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Cache layer for the Markdown Product Feed feature.
 *
 * Uses wp_cache (object cache) with group `wc_markdown_feed` to avoid
 * re-rendering markdown on every request. Single product caches are keyed
 * per product ID; archive caches include a version counter so that any
 * product change invalidates all archive pages at once.
 *
 * @since 10.6.0
 */
class MarkdownProductFeedCache implements RegisterHooksInterface {

	/**
	 * Cache group name.
	 *
	 * @since 10.6.0
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'wc_markdown_feed';

	/**
	 * Cache key for the archive version counter.
	 *
	 * @since 10.6.0
	 *
	 * @var string
	 */
	private const ARCHIVE_VERSION_KEY = 'md_archive_version';

	/**
	 * Register hooks for cache invalidation.
	 *
	 * Bails early if the `markdown_product_feed` feature is not enabled.
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! FeaturesUtil::feature_is_enabled( 'markdown_product_feed' ) ) {
			return;
		}

		add_action( 'woocommerce_update_product', array( $this, 'handle_product_change' ) );
		add_action( 'woocommerce_new_product', array( $this, 'handle_product_change' ) );
		add_action( 'woocommerce_trash_product', array( $this, 'handle_product_change' ) );
		add_action( 'woocommerce_before_delete_product', array( $this, 'handle_product_change' ) );
		add_action( 'woocommerce_update_product_variation', array( $this, 'handle_product_change' ) );
		add_action( 'woocommerce_new_product_variation', array( $this, 'handle_product_change' ) );
	}

	/**
	 * Get cached markdown for a single product.
	 *
	 * @since 10.6.0
	 *
	 * @param int $product_id Product ID.
	 * @return string|null Cached markdown string or null on cache miss.
	 */
	public function get_single( int $product_id ): ?string {
		$cached = wp_cache_get( "md_single_{$product_id}", self::CACHE_GROUP );

		return false === $cached ? null : $cached;
	}

	/**
	 * Cache markdown for a single product.
	 *
	 * @since 10.6.0
	 *
	 * @param int    $product_id Product ID.
	 * @param string $content    Markdown content.
	 * @return void
	 */
	public function set_single( int $product_id, string $content ): void {
		wp_cache_set( "md_single_{$product_id}", $content, self::CACHE_GROUP, $this->get_ttl() );
	}

	/**
	 * Get cached markdown for an archive page.
	 *
	 * The cache key includes the current archive version so that bumping the
	 * version automatically causes all old archive keys to miss.
	 *
	 * @since 10.6.0
	 *
	 * @param string $type    Archive type (e.g. 'category', 'tag').
	 * @param int    $term_id Term ID.
	 * @param int    $page    Page number.
	 * @return string|null Cached markdown string or null on cache miss.
	 */
	public function get_archive( string $type, int $term_id, int $page ): ?string {
		$key    = $this->get_archive_cache_key( $type, $term_id, $page );
		$cached = wp_cache_get( $key, self::CACHE_GROUP );

		return false === $cached ? null : $cached;
	}

	/**
	 * Cache markdown for an archive page.
	 *
	 * @since 10.6.0
	 *
	 * @param string $type    Archive type (e.g. 'category', 'tag').
	 * @param int    $term_id Term ID.
	 * @param int    $page    Page number.
	 * @param string $content Markdown content.
	 * @return void
	 */
	public function set_archive( string $type, int $term_id, int $page, string $content ): void {
		$key = $this->get_archive_cache_key( $type, $term_id, $page );
		wp_cache_set( $key, $content, self::CACHE_GROUP, $this->get_ttl() );
	}

	/**
	 * Invalidate cache for a single product and bump the archive version.
	 *
	 * Deleting the single-product cache key and bumping the archive version
	 * counter ensures that both the individual product page and all archive
	 * pages are effectively invalidated.
	 *
	 * @since 10.6.0
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function invalidate_product( int $product_id ): void {
		wp_cache_delete( "md_single_{$product_id}", self::CACHE_GROUP );
		$this->bump_archive_version();
	}

	/**
	 * Hook callback for product change actions.
	 *
	 * @internal
	 *
	 * @since 10.6.0
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function handle_product_change( int $product_id ): void {
		$this->invalidate_product( $product_id );
	}

	/**
	 * Get the current archive version counter.
	 *
	 * @since 10.6.0
	 *
	 * @return int Current archive version, defaults to 1 if not set.
	 */
	private function get_archive_version(): int {
		$version = wp_cache_get( self::ARCHIVE_VERSION_KEY, self::CACHE_GROUP );

		return false === $version ? 1 : (int) $version;
	}

	/**
	 * Bump the archive version counter by one.
	 *
	 * This causes all existing archive cache keys (which embed the old version)
	 * to become stale on the next read.
	 *
	 * @since 10.6.0
	 *
	 * @return void
	 */
	private function bump_archive_version(): void {
		$result = wp_cache_incr( self::ARCHIVE_VERSION_KEY, 1, self::CACHE_GROUP );

		if ( false === $result ) {
			// Key doesn't exist yet; initialize it.
			wp_cache_set( self::ARCHIVE_VERSION_KEY, 2, self::CACHE_GROUP );
		}
	}

	/**
	 * Build a cache key for an archive page including the current version.
	 *
	 * @since 10.6.0
	 *
	 * @param string $type    Archive type.
	 * @param int    $term_id Term ID.
	 * @param int    $page    Page number.
	 * @return string Cache key.
	 */
	private function get_archive_cache_key( string $type, int $term_id, int $page ): string {
		$version = $this->get_archive_version();

		return "md_archive_{$type}_{$term_id}_{$page}_{$version}";
	}

	/**
	 * Get the cache TTL in seconds.
	 *
	 * @since 10.6.0
	 *
	 * @return int Cache TTL in seconds.
	 */
	private function get_ttl(): int {
		/**
		 * Filter the cache TTL for the markdown product feed.
		 *
		 * @since 10.6.0
		 *
		 * @param int $ttl Cache TTL in seconds. Defaults to HOUR_IN_SECONDS.
		 */
		return (int) apply_filters( 'woocommerce_markdown_feed_cache_ttl', HOUR_IN_SECONDS );
	}
}
