<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\CacheController;
use WC_Cache_Helper;
use WC_Unit_Test_Case;
use WP_Post;

/**
 * Tests for the CacheController class.
 */
class CacheControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CacheController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( CacheController::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );

		parent::tearDown();
	}

	/**
	 * @testdox Filter-data invalidation fences both sides of the object-cache prefix rotation.
	 */
	public function test_invalidate_filter_data_cache_writes_generations_around_prefix_rotation(): void {
		$version_transient_key      = CacheController::CACHE_GROUP . '-transient-version';
		$prefix_cache_key           = 'wc_' . CacheController::CACHE_GROUP . '_cache_prefix';
		$original_version           = get_transient( $version_transient_key );
		$original_cache_entry_count = get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );
		$original_prefix_found      = false;
		$original_prefix            = wp_cache_get( $prefix_cache_key, CacheController::CACHE_GROUP, false, $original_prefix_found );
		$old_prefix                 = WC_Cache_Helper::get_cache_prefix( CacheController::CACHE_GROUP );
		$generation_writes          = array();
		$record_generation_write    = function ( $generation ) use ( &$generation_writes ) {
			$generation_writes[] = array(
				'generation' => $generation,
				'prefix'     => WC_Cache_Helper::get_cache_prefix( CacheController::CACHE_GROUP ),
			);

			return $generation;
		};
		$version_filter_name        = "pre_set_transient_{$version_transient_key}";

		set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, 5 );
		add_filter( $version_filter_name, $record_generation_write );

		try {
			$this->sut->invalidate_filter_data_cache();

			$final_version                 = get_transient( $version_transient_key );
			$final_prefix                  = WC_Cache_Helper::get_cache_prefix( CacheController::CACHE_GROUP );
			$cache_entry_count_after_clear = get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );
		} finally {
			remove_filter( $version_filter_name, $record_generation_write );

			if ( false === $original_version ) {
				delete_transient( $version_transient_key );
			} else {
				set_transient( $version_transient_key, $original_version );
			}

			if ( false === $original_cache_entry_count ) {
				delete_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT );
			} else {
				set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, $original_cache_entry_count );
			}

			if ( $original_prefix_found ) {
				wp_cache_set( $prefix_cache_key, $original_prefix, CacheController::CACHE_GROUP );
			} else {
				wp_cache_delete( $prefix_cache_key, CacheController::CACHE_GROUP );
			}
		}

		$this->assertCount( 2, $generation_writes, 'Invalidation should persist one generation before and one after rotating the product-ID cache prefix.' );
		$this->assertNotSame( $generation_writes[0]['generation'], $generation_writes[1]['generation'], 'The pre- and post-rotation generations should be distinct.' );
		$this->assertSame( $old_prefix, $generation_writes[0]['prefix'], 'The first generation should be written against the old product-ID cache prefix.' );
		$this->assertNotSame( $old_prefix, $generation_writes[1]['prefix'], 'The second generation should be written after the product-ID cache prefix rotates.' );
		$this->assertSame( $generation_writes[1]['prefix'], $final_prefix, 'The second generation should observe the final product-ID cache prefix.' );
		$this->assertSame( $generation_writes[1]['generation'], $final_version, 'The second generation should remain current after invalidation.' );
		$this->assertFalse( $cache_entry_count_after_clear, 'Invalidation should clear the filter-data cache-entry counter.' );
	}

	/**
	 * @testdox Status transitions invalidate filter data only when a product crosses the publish boundary.
	 * @dataProvider status_transition_provider
	 *
	 * @param string|null $post_type      Post type, or null for a non-WP_Post value.
	 * @param string      $new_status     New post status.
	 * @param string      $old_status     Old post status.
	 * @param false|int   $expected_count Expected cache-entry count transient value.
	 */
	public function test_status_transition_invalidates_cache_when_product_crosses_publish_boundary(
		?string $post_type,
		string $new_status,
		string $old_status,
		$expected_count
	): void {
		if ( null === $post_type ) {
			$post = new \stdClass();
		} else {
			$post = $this->factory->post->create_and_get(
				array(
					'post_type'   => $post_type,
					'post_status' => $old_status,
				)
			);
			$this->assertInstanceOf( WP_Post::class, $post, 'The factory should create a real WP_Post fixture.' );
		}

		set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, 5 );

		$this->sut->handle_transition_post_status( $new_status, $old_status, $post );

		$this->assertSame(
			$expected_count,
			get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ),
			'The cache-entry count should only be deleted for product visibility transitions.'
		);
	}

	/**
	 * Data provider for status transition cache invalidation.
	 *
	 * @return array<string, array{string|null, string, string, false|int}>
	 */
	public static function status_transition_provider(): array {
		return array(
			'product publish to private'           => array( 'product', 'private', 'publish', false ),
			'product publish to draft'             => array( 'product', 'draft', 'publish', false ),
			'product private to publish'           => array( 'product', 'publish', 'private', false ),
			'product variation publish to private' => array( 'product_variation', 'private', 'publish', false ),
			'product variation publish to draft'   => array( 'product_variation', 'draft', 'publish', false ),
			'product variation private to publish' => array( 'product_variation', 'publish', 'private', false ),
			'product private to draft'             => array( 'product', 'draft', 'private', 5 ),
			'product unchanged publish status'     => array( 'product', 'publish', 'publish', 5 ),
			'unrelated post type'                  => array( 'post', 'private', 'publish', 5 ),
			'non-WP_Post value'                    => array( null, 'private', 'publish', 5 ),
		);
	}
}
