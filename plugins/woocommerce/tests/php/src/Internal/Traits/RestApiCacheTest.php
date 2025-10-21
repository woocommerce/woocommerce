<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\EntityVersionsCache;
use Automattic\WooCommerce\Internal\Traits\RestApiCache;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use WC_Unit_Test_Case;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Tests for the RestApiCache trait.
 */
class RestApiCacheTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var object
	 */
	private $sut;

	/**
	 * Mock EntityVersionsCache instance.
	 *
	 * @var object
	 */
	private $mock_entity_cache;

	/**
	 * REST API server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Fixed timestamp for testing.
	 *
	 * @var int
	 */
	private $fixed_time = 1234567890;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock time() function after parent::setUp() to avoid being reset.
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'time' => function () {
					return $this->fixed_time;
				},
			)
		);

		// Create and register mock EntityVersionsCache.
		$this->mock_entity_cache = $this->create_mock_entity_versions_cache();
		wc_get_container()->replace( EntityVersionsCache::class, $this->mock_entity_cache );

		// Create test controller.
		$this->sut = $this->create_test_controller();

		// Set up REST server.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		// Register controller routes.
		$this->sut->register_routes();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * @testdox First request returns MISS and caches response, second request returns HIT with cached data
	 */
	public function test_caching_workflow_miss_then_hit() {
		// First request - should be a cache MISS.
		$response1 = $this->query_endpoint( 'get2' );

		// Verify response has MISS header.
		$this->assertCacheMissHeader( $response1 );
		$this->assertEquals( 200, $response1->get_status() );
		$this->assertEquals( $this->sut->responses['get2'], $response1->get_data() );

		// Verify response was cached.
		$this->assertCount( 1, $this->sut->cache );
		$cache_key    = array_key_first( $this->sut->cache );
		$cached_entry = $this->sut->cache[ $cache_key ];

		// Verify cached entry structure.
		$this->assertArrayHasKey( 'data', $cached_entry );
		$this->assertArrayHasKey( 'entity_versions', $cached_entry );
		$this->assertArrayHasKey( 'created_at', $cached_entry );

		// Verify cached data matches response.
		$this->assertEquals( $this->sut->responses['get2'], $cached_entry['data'] );

		// Verify created_at is the fixed time.
		$this->assertEquals( $this->fixed_time, $cached_entry['created_at'] );

		// Verify entity versions were stored for entities with IDs (2 and 3).
		$this->assertArrayHasKey( 2, $cached_entry['entity_versions'] );
		$this->assertArrayHasKey( 3, $cached_entry['entity_versions'] );
		$this->assertCount( 2, $cached_entry['entity_versions'] );

		// Verify versions were created in entity cache.
		$this->assertNotEmpty( $this->mock_entity_cache->get_entity_version( 'product', 2 ) );
		$this->assertNotEmpty( $this->mock_entity_cache->get_entity_version( 'product', 3 ) );

		// Modify the cached response data.
		$modified_data                          = array(
			array(
				'id'   => 999,
				'name' => 'Modified Product',
			),
		);
		$this->sut->cache[ $cache_key ]['data'] = $modified_data;

		// Second request - should be a cache HIT with modified data.
		$response2 = $this->query_endpoint( 'get2' );

		// Verify response has HIT header.
		$this->assertCacheHitHeader( $response2 );
		$this->assertEquals( 200, $response2->get_status() );

		// Verify response contains modified cached data, not original data.
		$this->assertEquals( $modified_data, $response2->get_data() );
		$this->assertNotEquals( $this->sut->responses['get2'], $response2->get_data() );
	}

	/**
	 * @testdox Expired cache entries are rejected and deleted
	 */
	public function test_expired_cache_entries_are_rejected() {
		// First request - cache MISS, creates cache entry.
		$response1 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response2 );

		// Advance time beyond TTL (default is HOUR_IN_SECONDS = 3600).
		$this->fixed_time += HOUR_IN_SECONDS + 1;

		// Third request after expiration - should be cache MISS.
		$response3 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created).
		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );
	}

	/**
	 * @testdox Cache is invalidated when relevant hooks change
	 */
	public function test_cache_invalidated_when_hooks_change() {
		// Configure get6 endpoint with relevant_hooks.
		$this->reconfigure_get6_endpoint(
			array(
				'relevant_hooks' => array( 'test_hook_for_caching' ),
			)
		);

		// First request - cache MISS, creates cache entry.
		$response1 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get6' );

		$this->assertCacheHitHeader( $response2 );

		// Add a filter to the relevant hook to change the hooks hash.
		add_filter( 'test_hook_for_caching', '__return_true' );

		// Advance time to ensure new cache entry has different timestamp.
		$this->fixed_time += 1;

		// Third request after hooks changed - should be cache MISS.
		$response3 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created).
		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Clean up.
		remove_filter( 'test_hook_for_caching', '__return_true' );
	}

	/**
	 * @testdox Cache is invalidated when controller-level hooks change
	 */
	public function test_cache_invalidated_when_controller_hooks_change() {
		// First request to get1 - cache MISS, creates cache entry.
		// get1 uses controller-level hooks from get_hooks_relevant_to_caching().
		$response1 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response2 );

		// Add a filter to the controller-level hook to change the hooks hash.
		add_filter( 'test_controller_hook_for_caching', '__return_false' );

		// Advance time to ensure new cache entry has different timestamp.
		$this->fixed_time += 1;

		// Third request after hooks changed - should be cache MISS.
		$response3 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created).
		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Clean up.
		remove_filter( 'test_controller_hook_for_caching', '__return_false' );
	}

	/**
	 * @testdox Entity ID extraction works for single entity responses
	 */
	public function test_entity_id_extraction_for_single_entity() {
		// First request to get1 - cache MISS, creates cache entry.
		// get1 returns single entity with id=1.
		$response1 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response2 );

		// Modify the entity version for entity 1 (the entity in the response).
		$this->mock_entity_cache->modify_entity_version( 'product', 1 );

		// Advance time to ensure new cache entry has different timestamp.
		$this->fixed_time += 1;

		// Third request after entity 1 version changed - should be cache MISS.
		$response3 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created).
		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Verify that modifying a different entity (e.g., entity 2) does NOT invalidate cache.
		$old_cache_info2 = $this->get_cache_info();

		$this->mock_entity_cache->modify_entity_version( 'product', 2 );

		$this->fixed_time += 1;

		// Fourth request - should still be cache HIT (entity 2 change doesn't affect entity 1 cache).
		$response4 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response4 );

		// Verify cache entry was not recreated.
		$new_cache_info2 = $this->get_cache_info();
		$this->assertCacheInfoEqual( $old_cache_info2, $new_cache_info2 );
	}

	/**
	 * @testdox Custom entity ID extraction via controller method works correctly
	 */
	public function test_custom_entity_id_extraction_via_controller_method() {
		// Enable custom entity ID extraction (IDs multiplied by 10).
		$this->sut->use_custom_entity_id_extraction = true;

		// First request to get1 - cache MISS, creates cache entry.
		// get1 returns entity with id=1, but custom extraction returns [10].
		$response1 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Verify cache entry stores modified entity ID (10 instead of 1).
		$cache_entry = array_values( $this->sut->cache )[0];
		$entity_ids  = array_keys( $cache_entry['entity_versions'] );
		$this->assertEquals( array( 10 ), $entity_ids, 'Cache should store custom extracted entity ID (1 * 10 = 10)' );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response2 );

		// Modify entity version for ID 10 (the custom extracted ID).
		$this->mock_entity_cache->modify_entity_version( 'product', 10 );

		$this->fixed_time += 1;

		// Third request - should be cache MISS (entity 10 changed).
		$response3 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response3 );

		// Verify cache was recreated.
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Verify that modifying the original entity ID (1) does NOT invalidate cache.
		$old_cache_info2 = $this->get_cache_info();

		$this->mock_entity_cache->modify_entity_version( 'product', 1 );

		$this->fixed_time += 1;

		// Fourth request - should still be cache HIT (entity 1 change doesn't affect cache tracking entity 10).
		$response4 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response4 );

		// Verify cache entry was not recreated.
		$new_cache_info2 = $this->get_cache_info();
		$this->assertCacheInfoEqual( $old_cache_info2, $new_cache_info2 );

		// Reset custom extraction.
		$this->sut->use_custom_entity_id_extraction = false;
	}

	/**
	 * @testdox Custom entity ID extraction via config callback works correctly
	 */
	public function test_custom_entity_id_extraction_via_config_callback() {
		// Configure get6 endpoint with custom entity_id_extractor callback.
		$this->reconfigure_get6_endpoint(
			array(
				'extract_entity_ids' => function ( $data, $request ) {
					// Custom extraction: extract IDs and multiply by 10.
					$ids = array();

					// Check if it's a collection (array of items).
					if ( isset( $data[0] ) && is_array( $data[0] ) ) {
						foreach ( $data as $item ) {
							if ( isset( $item['id'] ) ) {
								$ids[] = $item['id'] * 10;
							}
						}
					} elseif ( isset( $data['id'] ) ) {
						// Single item.
						$ids[] = $data['id'] * 10;
					}

					return array_unique( array_filter( $ids ) );
				},
			)
		);

		// First request to get6 - cache MISS, creates cache entry.
		// get6 returns entity with id=6, but custom extraction returns [60].
		$response1 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Verify cache entry stores modified entity ID (60 instead of 6).
		$cache_entry = array_values( $this->sut->cache )[0];
		$entity_ids  = array_keys( $cache_entry['entity_versions'] );
		$this->assertEquals( array( 60 ), $entity_ids, 'Cache should store custom extracted entity ID (6 * 10 = 60)' );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get6' );

		$this->assertCacheHitHeader( $response2 );

		// Modify entity version for ID 60 (the custom extracted ID).
		$this->mock_entity_cache->modify_entity_version( 'product', 60 );

		$this->fixed_time += 1;

		// Third request - should be cache MISS (entity 60 changed).
		$response3 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response3 );

		// Verify cache was recreated.
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Verify that modifying the original entity ID (6) does NOT invalidate cache.
		$old_cache_info2 = $this->get_cache_info();

		$this->mock_entity_cache->modify_entity_version( 'product', 6 );

		$this->fixed_time += 1;

		// Fourth request - should still be cache HIT (entity 6 change doesn't affect cache tracking entity 60).
		$response4 = $this->query_endpoint( 'get6' );

		$this->assertCacheHitHeader( $response4 );

		// Verify cache entry was not recreated.
		$new_cache_info2 = $this->get_cache_info();
		$this->assertCacheInfoEqual( $old_cache_info2, $new_cache_info2 );
	}

	/**
	 * @testdox Cache is invalidated when entity versions change for collection responses
	 * @testWith [1, false]
	 *           [2, true]
	 *           [3, true]
	 *           [999, false]
	 *
	 * @param int  $entity_id                   Entity ID to modify.
	 * @param bool $cache_invalidation_expected Whether cache invalidation is expected.
	 */
	public function test_cache_invalidated_when_entity_version_changes( int $entity_id, bool $cache_invalidation_expected ) {
		// First request to get2 - cache MISS, creates cache entry.
		// get2 returns collection with entities 2, 3, and one without ID.
		$response1 = $this->query_endpoint( 'get2' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get2' );

		$this->assertCacheHitHeader( $response2 );

		// Modify the entity version for the specified entity.
		$this->mock_entity_cache->modify_entity_version( 'product', $entity_id );

		// Advance time to ensure new cache entry has different timestamp.
		$this->fixed_time += 1;

		// Third request after entity version changed.
		$response3 = $this->query_endpoint( 'get2' );

		if ( $cache_invalidation_expected ) {
			// Cache should be invalidated (MISS).
			$this->assertCacheMissHeader( $response3 );

			// Verify exactly one cache entry exists (old deleted, new created).
			$this->assertCount( 1, $this->sut->cache );

			// Verify the cache entry is new (created_at timestamp changed).
			$new_cache_info = $this->get_cache_info();
			$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );
		} else {
			// Cache should still be valid (HIT).
			$this->assertCacheHitHeader( $response3 );

			// Verify cache entry was not recreated (same created_at timestamp).
			$new_cache_info = $this->get_cache_info();
			$this->assertCacheInfoEqual( $old_cache_info, $new_cache_info );
		}
	}

	/**
	 * @testdox Custom cache TTL via controller method is respected
	 */
	public function test_custom_cache_ttl_via_controller_method() {
		// Set custom TTL to 10 seconds via controller property.
		$this->sut->custom_cache_ttl = 10;

		// First request - cache MISS, creates cache entry with custom TTL.
		$response1 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response2 );

		// Advance time by 5 seconds (within TTL) - cache should still be valid.
		$this->fixed_time += 5;

		$response3 = $this->query_endpoint( 'get1' );

		$this->assertCacheHitHeader( $response3 );

		// Advance time by another 6 seconds (total 11 seconds, beyond 10 second TTL).
		$this->fixed_time += 6;

		// Fourth request after custom TTL expiration - should be cache MISS.
		$response4 = $this->query_endpoint( 'get1' );

		$this->assertCacheMissHeader( $response4 );

		// Verify exactly one cache entry exists (old deleted, new created).
		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Reset custom TTL.
		$this->sut->custom_cache_ttl = null;
	}

	/**
	 * @testdox Custom cache TTL via with_cache config is respected
	 */
	public function test_custom_cache_ttl_via_with_cache_config() {
		// Configure get6 endpoint with custom cache_ttl.
		$this->reconfigure_get6_endpoint(
			array(
				'cache_ttl' => 20,
			)
		);

		// First request to get6 - cache MISS, creates cache entry with custom TTL from config (20 seconds).
		$response1 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get6' );

		$this->assertCacheHitHeader( $response2 );

		// Advance time by 15 seconds (within 20 second TTL) - cache should still be valid.
		$this->fixed_time += 15;

		$response3 = $this->query_endpoint( 'get6' );

		$this->assertCacheHitHeader( $response3 );

		// Advance time by another 6 seconds (total 21 seconds, beyond 20 second TTL).
		$this->fixed_time += 6;

		// Fourth request after custom TTL expiration - should be cache MISS.
		$response4 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response4 );

		// Verify exactly one cache entry exists (old deleted, new created).
		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );
	}

	/**
	 * @testdox Custom entity type via with_cache config is respected
	 */
	public function test_custom_entity_type_via_with_cache_config() {
		// Configure get6 endpoint with custom entity_type.
		$this->reconfigure_get6_endpoint(
			array(
				'entity_type' => 'custom_entity',
			)
		);

		// First request - cache MISS, creates cache entry.
		$response1 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.
		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.
		$response2 = $this->query_endpoint( 'get6' );

		$this->assertCacheHitHeader( $response2 );

		// Modify entity version for 'custom_entity' type (not 'product').
		$this->mock_entity_cache->modify_entity_version( 'custom_entity', 6 );

		// Advance time to ensure new cache entry has different timestamp.
		$this->fixed_time += 1;

		// Third request after custom entity version changed - should be cache MISS.
		$response3 = $this->query_endpoint( 'get6' );

		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created).
		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Verify that modifying 'product' entity does NOT invalidate cache for 'custom_entity'.
		$old_cache_info2 = $this->get_cache_info();

		$this->mock_entity_cache->modify_entity_version( 'product', 6 );

		$this->fixed_time += 1;

		// Fourth request - should still be cache HIT (product entity change doesn't affect custom_entity cache).
		$response4 = $this->query_endpoint( 'get6' );

		$this->assertCacheHitHeader( $response4 );

		// Verify cache entry was not recreated.
		$new_cache_info2 = $this->get_cache_info();
		$this->assertCacheInfoEqual( $old_cache_info2, $new_cache_info2 );
	}

	/**
	 * @testdox Cache keys differ based on query string parameters
	 */
	public function test_cache_key_depends_on_query_string() {
		// First request without query string - should be a cache MISS.
		$response1 = $this->query_endpoint( 'get1' );

		// Verify response has MISS header and original data.
		$this->assertCacheMissHeader( $response1 );
		$this->assertEquals( 200, $response1->get_status() );
		$this->assertEquals( $this->sut->responses['get1'], $response1->get_data() );

		// Store original response for later comparison.
		$original_data = $response1->get_data();

		// Verify response was cached.
		$this->assertCount( 1, $this->sut->cache );

		// Modify the response in the controller for subsequent requests.
		$modified_data                = array(
			'id'   => 999,
			'name' => 'Modified Product',
		);
		$this->sut->responses['get1'] = $modified_data;

		// Second request WITH query string - should be a cache MISS with modified data.
		$response2 = $this->query_endpoint( 'get1', array( 'foo' => 'bar'  ) );

		// Verify response has MISS header and modified data.
		$this->assertCacheMissHeader( $response2 );
		$this->assertEquals( 200, $response2->get_status() );
		$this->assertEquals( $modified_data, $response2->get_data() );

		// Verify we now have two cache entries (different query strings = different cache keys).
		$this->assertCount( 2, $this->sut->cache );

		// Third request without query string - should be a cache HIT with original data.
		$response3 = $this->query_endpoint( 'get1' );

		// Verify response has HIT header and original cached data.
		$this->assertCacheHitHeader( $response3 );
		$this->assertEquals( 200, $response3->get_status() );
		$this->assertEquals( $original_data, $response3->get_data() );
		$this->assertNotEquals( $modified_data, $response3->get_data() );

		// Fourth request WITH same query string - should be a cache HIT with modified data.
		$response4 = $this->query_endpoint( 'get1', array( 'foo' => 'bar'  ) );

		// Verify response has HIT header and modified cached data.
		$this->assertCacheHitHeader( $response4 );
		$this->assertEquals( 200, $response4->get_status() );
		$this->assertEquals( $modified_data, $response4->get_data() );
		$this->assertNotEquals( $original_data, $response4->get_data() );

		// Verify we still have exactly two cache entries (no new entries created).
		$this->assertCount( 2, $this->sut->cache );
	}

	/**
	 * @testdox Caching is skipped when _skip_cache parameter is set
	 * @testWith ["1"]
	 *           ["true"]
	 *
	 * @param string $skip_cache_value Value for _skip_cache parameter ("1" or "true").
	 */
	public function test_skip_cache_parameter_bypasses_caching( $skip_cache_value ) {
		// First request with _skip_cache - should be a cache SKIP.
		$response1 = $this->query_endpoint( 'get1', array( '_skip_cache' => $skip_cache_value  ) );

		// Verify caching was skipped.
		$this->assertCachingSkipped( $response1, $this->sut->responses['get1'] );

		// Store original response for later comparison.
		$original_data = $response1->get_data();

		// Modify the response in the controller for subsequent requests.
		$modified_data                = array(
			'id'   => 999,
			'name' => 'Modified Product',
		);
		$this->sut->responses['get1'] = $modified_data;

		// Second request with _skip_cache - should still be a cache SKIP with modified data.
		$response2 = $this->query_endpoint( 'get1', array( '_skip_cache' => $skip_cache_value  ) );

		// Verify caching was still skipped with modified data.
		$this->assertCachingSkipped( $response2, $modified_data );
		$this->assertNotEquals( $original_data, $response2->get_data() );
	}

	/**
	 * @testdox Caching is skipped when entity versions cache is disabled
	 */
	public function test_caching_skipped_when_entity_cache_disabled() {
		// Disable the entity versions cache.
		$this->mock_entity_cache->is_enabled = false;

		// Re-initialize the cache in the controller to pick up the disabled state.
		$this->sut->reinitialize_cache();

		// Request get1.
		$response = $this->query_endpoint( 'get1' );

		// Verify caching was skipped.
		$this->assertCachingSkipped( $response, $this->sut->responses['get1'] );
	}

	/**
	 * @testdox Caching is skipped when woocommerce_rest_api_enable_response_caching filter returns false
	 */
	public function test_caching_skipped_when_filter_returns_false() {
		// Track filter calls and arguments.
		$filter_called = false;
		$filter_args   = array();

		// Add filter that returns false and captures arguments.
		add_filter(
			'woocommerce_rest_api_enable_response_caching',
			function ( $enabled, $controller ) use ( &$filter_called, &$filter_args ) {
				$filter_called = true;
				$filter_args   = array(
					'enabled'    => $enabled,
					'controller' => $controller,
				);
				return false;
			},
			10,
			2
		);

		// Request get1.
		$response = $this->query_endpoint( 'get1' );

		// Verify filter was called with correct arguments.
		$this->assertTrue( $filter_called, 'Filter should have been called' );
		$this->assertTrue( $filter_args['enabled'], 'Default enabled value should be true' );
		$this->assertSame( $this->sut, $filter_args['controller'], 'Controller should be passed to filter' );

		// Verify caching was skipped.
		$this->assertCachingSkipped( $response, $this->sut->responses['get1'] );

		// Clean up filter.
		remove_all_filters( 'woocommerce_rest_api_enable_response_caching' );
	}

	/**
	 * @testdox Caching is skipped when with_cache must_cache argument returns false
	 */
	public function test_caching_skipped_when_with_cache_must_cache_returns_false() {
		// Request get5 endpoint which has a custom must_cache callback that returns false.
		$response = $this->query_endpoint( 'get5' );

		// Verify caching was skipped.
		$this->assertCachingSkipped( $response, $this->sut->responses['get5'] );
	}

	/**
	 * @testdox Caching is skipped when controller must_cache method returns false
	 */
	public function test_caching_skipped_when_controller_must_cache_returns_false() {
		// Set controller's must_cache return value to false.
		$this->sut->must_cache_return_value = false;

		// Request get1 endpoint.
		$response = $this->query_endpoint( 'get1' );

		// Verify caching was skipped.
		$this->assertCachingSkipped( $response, $this->sut->responses['get1'] );
	}

	/**
	 * @testdox Caching is skipped when no entity type is available and wc_doing_it_wrong is called
	 */
	public function test_caching_skipped_when_no_entity_type_available() {
		// Track wc_doing_it_wrong calls via LegacyProxy.
		$doing_it_wrong_args = null;
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_doing_it_wrong' => function ( $function, $message, $version ) use ( &$doing_it_wrong_args ) {
					$doing_it_wrong_args = array(
						'function' => $function,
						'message'  => $message,
						'version'  => $version,
					);
				},
			)
		);

		// Override controller's get_default_entity_type to return null.
		$this->sut->default_entity_type = null;

		// Request get1 endpoint (which doesn't specify entity_type in config).
		$response = $this->query_endpoint( 'get1' );

		// Verify wc_doing_it_wrong was called with correct arguments.
		$this->assertNotNull( $doing_it_wrong_args, 'wc_doing_it_wrong should be called when no entity type is available' );
		$this->assertStringContainsString( 'build_cache_config', $doing_it_wrong_args['function'] );
		$this->assertStringContainsString( 'No entity type provided', $doing_it_wrong_args['message'] );
		$this->assertEquals( '10.4.0', $doing_it_wrong_args['version'] );

		// Verify caching was skipped.
		$this->assertCachingSkipped( $response, $this->sut->responses['get1'] );
	}

	/**
	 * @testdox Endpoints return expected data from responses array
	 * @testWith ["get1"]
	 *           ["get2"]
	 *           ["get3"]
	 *           ["get4"]
	 *
	 * @param string $endpoint_name Endpoint name (get1, get2, etc).
	 */
	public function test_endpoint_returns_expected_data( $endpoint_name ) {
		$request  = new WP_REST_Request( 'GET', "/wc/v3/rest_api_cache_test/{$endpoint_name}" );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->sut->responses[ $endpoint_name ], $response->get_data() );
	}

	/**
	 * @testdox Endpoints store the request object
	 */
	public function test_endpoints_store_request_object() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/get1' );
		$request->set_query_params( array( 'foo' => 'bar' ) );
		$this->server->dispatch( $request );

		$this->assertInstanceOf( WP_REST_Request::class, $this->sut->requests['get1'] );
		$this->assertEquals( 'bar', $this->sut->requests['get1']->get_param( 'foo' ) );
	}

	/**
	 * @testdox Endpoint returns WP_Error when response is null
	 */
	public function test_endpoint_returns_error_when_response_is_null() {
		$this->sut->responses['get1'] = null;

		$response = $this->query_endpoint( 'get1' );

		$this->assertInstanceOf( WP_Error::class, $response->as_error() );
		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * @testdox Mock EntityVersionsCache stores and retrieves versions
	 */
	public function test_mock_entity_cache_stores_and_retrieves_versions() {
		$version = $this->mock_entity_cache->modify_entity_version( 'product', 123 );

		$this->assertNotEmpty( $version );
		$this->assertEquals( $version, $this->mock_entity_cache->get_entity_version( 'product', 123 ) );
	}

	/**
	 * @testdox Mock EntityVersionsCache stores TTL
	 */
	public function test_mock_entity_cache_stores_ttl() {
		$this->mock_entity_cache->modify_entity_version( 'product', 123 );

		$key = 'wc_entity_version_product_123';
		$this->assertArrayHasKey( $key, $this->mock_entity_cache->cache );
		$this->assertEquals( HOUR_IN_SECONDS, $this->mock_entity_cache->cache[ $key ]['ttl'] );
	}

	/**
	 * @testdox Mock EntityVersionsCache deletes versions
	 */
	public function test_mock_entity_cache_deletes_versions() {
		$version = $this->mock_entity_cache->modify_entity_version( 'product', 123 );
		$this->mock_entity_cache->forget_entity_version( 'product', 123 );

		// After deletion, the cache should not have the entry.
		$key = 'wc_entity_version_product_123';
		$this->assertArrayNotHasKey( $key, $this->mock_entity_cache->cache );
	}

	/**
	 * @testdox Mock EntityVersionsCache auto-creates versions for non-existent entities
	 */
	public function test_mock_entity_cache_auto_creates_versions_for_nonexistent_entities() {
		// get_entity_version should auto-create a version if one doesn't exist.
		$version = $this->mock_entity_cache->get_entity_version( 'product', 999 );
		$this->assertNotEmpty( $version );

		// Calling again should return the same version.
		$version2 = $this->mock_entity_cache->get_entity_version( 'product', 999 );
		$this->assertEquals( $version, $version2 );
	}

	/**
	 * Create mock EntityVersionsCache.
	 *
	 * @return object Mock EntityVersionsCache instance.
	 */
	private function create_mock_entity_versions_cache() {
		return new class() extends EntityVersionsCache {
			/**
			 * Cache storage.
			 *
			 * @var array
			 */
			public $cache = array();

			/**
			 * Whether the cache is enabled.
			 *
			 * @var bool
			 */
			public $is_enabled = true;

			/**
			 * Check if cache is enabled.
			 *
			 * @return bool
			 */
			public function is_enabled(): bool {
				return $this->is_enabled;
			}

			/**
			 * Get entity version.
			 *
			 * @param string $entity_type Entity type.
			 * @param int    $entity_id   Entity ID.
			 * @return string Entity version.
			 */
			public function get_entity_version( string $entity_type, int $entity_id ): string {
				$key = "wc_entity_version_{$entity_type}_{$entity_id}";
				if ( ! isset( $this->cache[ $key ] ) ) {
					// Auto-create version if it doesn't exist (matching real EntityVersionsCache behavior).
					return $this->modify_entity_version( $entity_type, $entity_id );
				}
				return $this->cache[ $key ]['value'] ?? '';
			}

			/**
			 * Modify entity version (generate new version).
			 *
			 * @param string $entity_type Entity type.
			 * @param int    $entity_id   Entity ID.
			 * @return string New version.
			 */
			public function modify_entity_version( string $entity_type, int $entity_id ): string {
				$key                 = "wc_entity_version_{$entity_type}_{$entity_id}";
				$version             = wp_generate_uuid4();
				$this->cache[ $key ] = array(
					'value' => $version,
					'ttl'   => HOUR_IN_SECONDS,
				);
				return $version;
			}

			/**
			 * Forget entity version (delete from cache).
			 *
			 * @param string $entity_type Entity type.
			 * @param int    $entity_id   Entity ID.
			 * @return bool True on success.
			 */
			public function forget_entity_version( string $entity_type, int $entity_id ): bool {
				$key = "wc_entity_version_{$entity_type}_{$entity_id}";
				unset( $this->cache[ $key ] );
				return true;
			}
		};
	}

	/**
	 * Create test controller.
	 *
	 * @return object Test controller instance.
	 */
	private function create_test_controller() {
		return new class() extends WP_REST_Controller {
			use RestApiCache;

			/**
			 * Response data for each endpoint.
			 *
			 * @var array
			 */
			public $responses = array(
				'get1' => array(
					'id'   => 1,
					'name' => 'Product 1',
				),
				'get2' => array(
					array(
						'id'   => 2,
						'name' => 'Product 2',
					),
					array(
						'id'   => 3,
						'name' => 'Product 3',
					),
					array( 'name' => 'Product without ID' ),
				),
				'get3' => array( 'name' => 'Product without ID' ),
				'get4' => array(
					'id'   => 4,
					'name' => 'Product 4',
				),
				'get5' => array(
					'id'   => 5,
					'name' => 'Product 5',
				),
				'get6' => array(
					'id'   => 6,
					'name' => 'Product 6',
				),
			);

			/**
			 * Stored requests for each endpoint.
			 *
			 * @var array
			 */
			public $requests = array();

			/**
			 * Local cache storage for testing.
			 *
			 * @var array
			 */
			public $cache = array();

			/**
			 * Return value for must_cache method.
			 *
			 * @var bool
			 */
			public $must_cache_return_value = true;

			/**
			 * Default entity type to return from get_default_entity_type.
			 *
			 * @var string|null
			 */
			public $default_entity_type = 'product';

			/**
			 * Custom cache TTL to return from get_cache_ttl.
			 *
			 * @var int|null
			 */
			public $custom_cache_ttl = null;

			/**
			 * Configuration for get6 endpoint with_cache call.
			 *
			 * @var array
			 */
			public $get6_cache_config = array();

			/**
			 * Whether to use custom entity ID extraction (multiply IDs by 10).
			 *
			 * @var bool
			 */
			public $use_custom_entity_id_extraction = false;

			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->namespace = 'wc/v3';
				$this->rest_base = 'rest_api_cache_test';
				$this->initialize_rest_api_cache();
			}

			/**
			 * Register routes.
			 */
			public function register_routes() {
				// get1 - cacheable, returns single entity.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/get1',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache( array( $this, 'handle_get1' ) ),
						'permission_callback' => '__return_true',
					)
				);

				// get2 - cacheable, returns collection.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/get2',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache( array( $this, 'handle_get2' ) ),
						'permission_callback' => '__return_true',
					)
				);

				// get3 - cacheable, returns entity without ID.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/get3',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache( array( $this, 'handle_get3' ) ),
						'permission_callback' => '__return_true',
					)
				);

				// get4 - not cacheable.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/get4',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'handle_get4' ),
						'permission_callback' => '__return_true',
					)
				);

				// get5 - cacheable but with must_cache callback that returns false.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/get5',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							array( $this, 'handle_get5' ),
							array(
								'must_cache' => '__return_false',
							)
						),
						'permission_callback' => '__return_true',
					)
				);

				// get6 - cacheable with configurable per-endpoint settings.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/get6',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							array( $this, 'handle_get6' ),
							$this->get6_cache_config
						),
						'permission_callback' => '__return_true',
					)
				);
			}

			/**
			 * Get default entity type.
			 *
			 * @return string|null
			 */
			protected function get_default_entity_type(): ?string {
				return $this->default_entity_type;
			}

			/**
			 * Override must_cache to use configurable return value.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return bool
			 */
			protected function must_cache( $request ) {
				return $this->must_cache_return_value;
			}

			/**
			 * Override get_hooks_relevant_to_caching to return test hooks.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return array Array of filter names.
			 */
			protected function get_hooks_relevant_to_caching( $request ) {
				return array( 'test_controller_hook_for_caching' );
			}

			/**
			 * Override get_cache_ttl to return custom TTL.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return int Cache TTL in seconds.
			 */
			protected function get_cache_ttl( $request ) {
				return $this->custom_cache_ttl ?? HOUR_IN_SECONDS;
			}

			/**
			 * Override extract_entity_ids to use custom extraction logic.
			 *
			 * @param array           $data    Response data.
			 * @param WP_REST_Request $request Request object.
			 * @return array Array of entity IDs.
			 */
			protected function extract_entity_ids( $data, $request ) {
				if ( ! $this->use_custom_entity_id_extraction ) {
					// Use default extraction logic from RestApiCache trait.
					$ids = array();

					// Check if it's a collection (array of items).
					if ( isset( $data[0] ) && is_array( $data[0] ) ) {
						foreach ( $data as $item ) {
							if ( isset( $item['id'] ) ) {
								$ids[] = $item['id'];
							}
						}
					} elseif ( isset( $data['id'] ) ) {
						// Single item.
						$ids[] = $data['id'];
					}

					return array_unique( array_filter( $ids ) );
				}

				// Custom extraction: get default IDs and multiply each by 10.
				$ids = array();

				// Check if it's a collection (array of items).
				if ( isset( $data[0] ) && is_array( $data[0] ) ) {
					foreach ( $data as $item ) {
						if ( isset( $item['id'] ) ) {
							$ids[] = $item['id'] * 10;
						}
					}
				} elseif ( isset( $data['id'] ) ) {
					// Single item.
					$ids[] = $data['id'] * 10;
				}

				return array_unique( array_filter( $ids ) );
			}

			/**
			 * Handle get1 request.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			public function handle_get1( $request ) {
				return $this->handle_request( 'get1', $request );
			}

			/**
			 * Handle get2 request.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			public function handle_get2( $request ) {
				return $this->handle_request( 'get2', $request );
			}

			/**
			 * Handle get3 request.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			public function handle_get3( $request ) {
				return $this->handle_request( 'get3', $request );
			}

			/**
			 * Handle get4 request.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			public function handle_get4( $request ) {
				return $this->handle_request( 'get4', $request );
			}

			/**
			 * Handle get5 request.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			public function handle_get5( $request ) {
				return $this->handle_request( 'get5', $request );
			}

			/**
			 * Handle get6 request.
			 *
			 * @param WP_REST_Request $request Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			public function handle_get6( $request ) {
				return $this->handle_request( 'get6', $request );
			}

			/**
			 * Generic request handler.
			 *
			 * @param string          $endpoint Endpoint name.
			 * @param WP_REST_Request $request  Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			private function handle_request( string $endpoint, WP_REST_Request $request ) {
				// Store the request.
				$this->requests[ $endpoint ] = $request;

				// Return error if response is null.
				if ( is_null( $this->responses[ $endpoint ] ) ) {
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					return new WP_Error( 'server_error', 'Internal server error', array( 'status' => 500 ) );
				}

				// Return the response.
				return new WP_REST_Response( $this->responses[ $endpoint ], 200 );
			}

			/**
			 * Public wrapper to reinitialize the REST API cache.
			 *
			 * This allows tests to reinitialize the cache after changing mock state.
			 */
			public function reinitialize_cache() {
				$this->initialize_rest_api_cache();
			}

			/**
			 * Get a value from the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @return mixed|false The cached value or false if not found.
			 */
			protected function get_cached( string $cache_key ) {
				return $this->cache[ $cache_key ] ?? false;
			}

			/**
			 * Set a value in the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @param mixed  $value     The value to cache.
			 * @param int    $ttl       Time to live in seconds.
			 * @return bool True on success, false on failure.
			 */
			protected function set_cached( string $cache_key, $value, int $ttl ): bool {
				$this->cache[ $cache_key ] = $value;
				return true;
			}

			/**
			 * Delete a value from the cache.
			 *
			 * @param string $cache_key The cache key.
			 * @return bool True on success, false on failure.
			 */
			protected function delete_cached( string $cache_key ): bool {
				unset( $this->cache[ $cache_key ] );
				return true;
			}
		};
	}

	/**
	 * Reconfigure get6 endpoint and re-register routes.
	 *
	 * @param array $config Configuration array for get6 endpoint.
	 */
	private function reconfigure_get6_endpoint( array $config ) {
		// Set the configuration.
		$this->sut->get6_cache_config = $config;

		// Recreate REST server to clear routes.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		// Trigger rest_api_init to register core routes.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		// Reinitialize cache and register routes with new configuration.
		$this->sut->reinitialize_cache();
		$this->sut->register_routes();
	}

	/**
	 * Query an endpoint and return the response.
	 *
	 * @param string     $endpoint_name Endpoint name (e.g., 'get1', 'get2').
	 * @param array|null $query_params  Optional query parameters.
	 * @return WP_REST_Response The response from the endpoint.
	 */
	private function query_endpoint( $endpoint_name, $query_params = null ) {
		$request = new WP_REST_Request( 'GET', "/wc/v3/rest_api_cache_test/{$endpoint_name}" );
		if ( ! is_null( $query_params ) ) {
			$request->set_query_params( $query_params );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Get current cache information (key, entry, created_at timestamp).
	 *
	 * @return array|null Array with 'key', 'entry', and 'created_at', or null if cache is empty.
	 */
	private function get_cache_info() {
		if ( empty( $this->sut->cache ) ) {
			return null;
		}

		$cache_key   = array_key_first( $this->sut->cache );
		$cache_entry = $this->sut->cache[ $cache_key ];
		$created_at  = $cache_entry['created_at'];

		return array(
			'key'        => $cache_key,
			'entry'      => $cache_entry,
			'created_at' => $created_at,
		);
	}

	/**
	 * Assert that two cache info sets are equal (same key and created_at timestamp).
	 *
	 * @param array  $expected Expected cache info.
	 * @param array  $actual   Actual cache info.
	 * @param string $message  Optional message for assertion failure.
	 */
	private function assertCacheInfoEqual( array $expected, array $actual, string $message = '' ) {
		$this->assertEquals( $expected['key'], $actual['key'], $message ? "{$message}: Cache key should be the same" : 'Cache key should be the same' );
		$this->assertEquals( $expected['created_at'], $actual['created_at'], $message ? "{$message}: Cache entry should not have been recreated" : 'Cache entry should not have been recreated' );
	}

	/**
	 * Assert that two cache info sets are different (same key but different created_at timestamp).
	 *
	 * @param array  $expected       Expected cache info.
	 * @param array  $actual         Actual cache info.
	 * @param int    $expected_time  Expected new created_at timestamp.
	 * @param string $message        Optional message for assertion failure.
	 */
	private function assertCacheInfoDifferent( array $expected, array $actual, int $expected_time, string $message = '' ) {
		$this->assertEquals( $expected['key'], $actual['key'], $message ? "{$message}: Cache key should be the same" : 'Cache key should be the same' );
		$this->assertNotEquals( $expected['created_at'], $actual['created_at'], $message ? "{$message}: Cache entry should have new created_at timestamp" : 'Cache entry should have new created_at timestamp' );
		$this->assertEquals( $expected_time, $actual['created_at'], $message ? "{$message}: New cache entry should use current time" : 'New cache entry should use current time' );
	}

	/**
	 * Assert that response has X-WC-Cache: HIT header.
	 *
	 * @param WP_REST_Response $response The response to check.
	 */
	private function assertCacheHitHeader( $response ) {
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 'HIT', $response->get_headers()['X-WC-Cache'] );
	}

	/**
	 * Assert that response has X-WC-Cache: MISS header.
	 *
	 * @param WP_REST_Response $response The response to check.
	 */
	private function assertCacheMissHeader( $response ) {
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 'MISS', $response->get_headers()['X-WC-Cache'] );
	}

	/**
	 * Assert that response has X-WC-Cache: SKIP header.
	 *
	 * @param WP_REST_Response $response The response to check.
	 */
	private function assertCacheSkipHeader( $response ) {
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 'SKIP', $response->get_headers()['X-WC-Cache'] );
	}

	/**
	 * Assert that response has no X-WC-Cache header.
	 *
	 * @param WP_REST_Response $response The response to check.
	 */
	private function assertNoCacheHeader( $response ) {
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertArrayNotHasKey( 'X-WC-Cache', $response->get_headers() );
	}

	/**
	 * Assert that caching was skipped (SKIP header, nothing cached, no entity versions).
	 *
	 * @param WP_REST_Response $response      The response to check.
	 * @param array            $expected_data Expected response data.
	 */
	private function assertCachingSkipped( $response, $expected_data ) {
		// Verify response has SKIP header.
		$this->assertCacheSkipHeader( $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_data, $response->get_Data() );

		// Verify nothing was cached.
		$this->assertCount( 0, $this->sut->cache );

		// Verify no entity versions were created.
		$this->assertCount( 0, $this->mock_entity_cache->cache );
	}
}
