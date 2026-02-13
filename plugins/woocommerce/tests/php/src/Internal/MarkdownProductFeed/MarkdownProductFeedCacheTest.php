<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MarkdownProductFeed;

use Automattic\WooCommerce\Internal\MarkdownProductFeed\MarkdownProductFeedCache;
use WC_Unit_Test_Case;

/**
 * Tests for the MarkdownProductFeedCache class.
 */
class MarkdownProductFeedCacheTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MarkdownProductFeedCache
	 */
	private MarkdownProductFeedCache $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new MarkdownProductFeedCache();
	}

	/**
	 * @testdox Should return null when no single product cache exists.
	 */
	public function test_get_single_returns_null_on_cache_miss(): void {
		$result = $this->sut->get_single( 99999 );

		$this->assertNull( $result, 'Fresh cache should return null for get_single' );
	}

	/**
	 * @testdox Should store and retrieve single product cache correctly.
	 */
	public function test_set_and_get_single(): void {
		$product_id = 12345;
		$content    = '# Test Product Markdown';

		$this->sut->set_single( $product_id, $content );
		$result = $this->sut->get_single( $product_id );

		$this->assertSame( $content, $result, 'Cached single product content should match what was stored' );
	}

	/**
	 * @testdox Should return null when no archive cache exists.
	 */
	public function test_get_archive_returns_null_on_cache_miss(): void {
		$result = $this->sut->get_archive( 'category', 1, 1 );

		$this->assertNull( $result, 'Fresh cache should return null for get_archive' );
	}

	/**
	 * @testdox Should store and retrieve archive cache correctly.
	 */
	public function test_set_and_get_archive(): void {
		$content = '# Archive Page Markdown';

		$this->sut->set_archive( 'category', 5, 1, $content );
		$result = $this->sut->get_archive( 'category', 5, 1 );

		$this->assertSame( $content, $result, 'Cached archive content should match what was stored' );
	}

	/**
	 * @testdox Should return null for single product cache after invalidation.
	 */
	public function test_invalidate_product_clears_single_cache(): void {
		$product_id = 777;
		$this->sut->set_single( $product_id, '# Cached content' );

		$this->sut->invalidate_product( $product_id );
		$result = $this->sut->get_single( $product_id );

		$this->assertNull( $result, 'Single product cache should be null after invalidation' );
	}

	/**
	 * @testdox Should cause archive cache miss after product invalidation bumps version.
	 */
	public function test_invalidate_product_bumps_archive_version(): void {
		$this->sut->set_archive( 'shop', 0, 1, '# Archive before invalidation' );

		$this->sut->invalidate_product( 1 );
		$result = $this->sut->get_archive( 'shop', 0, 1 );

		$this->assertNull( $result, 'Archive cache should miss after product invalidation bumps the version' );
	}
}
