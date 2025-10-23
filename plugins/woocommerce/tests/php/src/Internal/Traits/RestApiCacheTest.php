<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\EntityVersionsCache;
use Automattic\WooCommerce\Internal\Traits\RestApiCache;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Tests for the RestApiCache trait.
 *
 * Worth knowing:
 *
 * - A trait can't be used directly, so for each test we instantiate
 *   a test controller class that uses it and has customization points
 *   (see create_test_controller).
 * - We use a mock version of EntityVersionsCache that stores cached entries
 *   in a local array instead of transients, for easier testing.
 *
 * IMPORTANT: If you add new tests, find the "TESTS END HERE" comment
 * and add them before that point.
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

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'time' => function () {
					return $this->fixed_time;
				},
			)
		);

		$this->mock_entity_cache = $this->create_mock_entity_versions_cache();
		wc_get_container()->replace( EntityVersionsCache::class, $this->mock_entity_cache );

		$this->sut = $this->create_test_controller();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );
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
	 * @testdox First request returns MISS and caches response, second request returns HIT with cached data.
	 */
	public function test_caching_workflow_miss_then_hit() {
		$response1 = $this->query_endpoint( 'multiple_entities' );

		// Verify response.

		$this->assertCacheMissHeader( $response1 );
		$this->assertEquals( $this->sut->responses['multiple_entities'], $response1->get_data() );

		// Verify the structure and contents of the cache entry for the response.

		$this->assertCount( 1, $this->sut->cache );
		$cache_key    = array_key_first( $this->sut->cache );
		$cached_entry = $this->sut->cache[ $cache_key ];

		$this->assertArrayHasKey( 'data', $cached_entry );
		$this->assertArrayHasKey( 'entity_versions', $cached_entry );
		$this->assertArrayHasKey( 'created_at', $cached_entry );
		$this->assertEquals( $this->sut->responses['multiple_entities'], $cached_entry['data'] );
		$this->assertEquals( $this->fixed_time, $cached_entry['created_at'] );
		$this->assertCount( 2, $cached_entry['entity_versions'] );
		$this->assertArrayHasKey( 2, $cached_entry['entity_versions'] );
		$this->assertArrayHasKey( 3, $cached_entry['entity_versions'] );

		// Verify versions were created in entity cache.

		$this->assertNotEmpty( $this->mock_entity_cache->get_entity_version( 'product', 2 ) );
		$this->assertNotEmpty( $this->mock_entity_cache->get_entity_version( 'product', 3 ) );

		// Modify the cached response data and query again,
		// the response should be a cache HIT with the modified data.

		$modified_data                          = array(
			array(
				'id'   => 999,
				'name' => 'Modified Product',
			),
		);
		$this->sut->cache[ $cache_key ]['data'] = $modified_data;

		$response2 = $this->query_endpoint( 'multiple_entities' );

		$this->assertCacheHitHeader( $response2 );
		$this->assertEquals( $modified_data, $response2->get_data() );
		$this->assertNotEquals( $this->sut->responses['multiple_entities'], $response2->get_data() );
	}

	/**
	 * @testdox Expired cache entries are rejected and deleted.
	 */
	public function test_expired_cache_entries_are_rejected() {

		// First request - cache MISS, creates cache entry.

		$response1 = $this->query_endpoint( 'single_entity' );

		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHitHeader( $response2 );

		// Advance time beyond TTL (default is HOUR_IN_SECONDS = 3600).

		$this->fixed_time += HOUR_IN_SECONDS + 1;

		// Third request after expiration - should be cache MISS.

		$response3 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created)
		// and the cache entry is new (created_at timestamp changed).
		$this->assertCount( 1, $this->sut->cache );
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );
	}

	/**
	 * @testdox Cache is invalidated when relevant hooks change.
	 */
	public function test_cache_invalidated_when_hooks_change() {

		// Configure custom_endpoint_config endpoint with relevant_hooks.

		$this->reconfigure_custom_endpoint_config_endpoint(
			array(
				'relevant_hooks' => array( 'test_hook_for_caching' ),
			)
		);

		// First request - cache MISS, creates cache entry.

		$response1 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheHitHeader( $response2 );

		// Add a filter to the relevant hook to change the hooks hash.

		add_filter( 'test_hook_for_caching', '__return_true' );

		// Advance time to ensure new cache entry has different timestamp.

		$this->fixed_time += 1;

		// Third request after hooks changed - should be cache MISS.

		$response3 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created).
		// and the cache entry is new (created_at timestamp changed).

		$new_cache_info = $this->get_cache_info();
		$this->assertCount( 1, $this->sut->cache );
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Clean up.

		remove_filter( 'test_hook_for_caching', '__return_true' );
	}

	/**
	 * @testdox Cache is invalidated when controller-level hooks change.
	 */
	public function test_cache_invalidated_when_controller_hooks_change() {
		// First request to single_entity - cache MISS, creates cache entry.
		// single_entity uses controller-level hooks from get_hooks_relevant_to_caching().

		$response1 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHitHeader( $response2 );

		// Add a filter to the controller-level hook to change the hooks hash.

		add_filter( 'test_controller_hook_for_caching', '__return_false' );

		// Advance time to ensure new cache entry has different timestamp.

		$this->fixed_time += 1;

		// Third request after hooks changed - should be cache MISS.

		$response3 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created),
		// and the cache entry is new (created_at timestamp changed).
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );
		$this->assertCount( 1, $this->sut->cache );

		// Clean up.

		remove_filter( 'test_controller_hook_for_caching', '__return_false' );
	}

	/**
	 * @testdox Entity ID extraction works for single entity responses.
	 */
	public function test_entity_id_extraction_for_single_entity() {
		// First request to single_entity - cache MISS, creates cache entry.
		// single_entity returns single entity with id=1.

		$response1 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHitHeader( $response2 );

		// Modify the entity version for entity 1 (the entity in the response).

		$this->mock_entity_cache->modify_entity_version( 'product', 1 );

		// Advance time to ensure new cache entry has different timestamp.

		$this->fixed_time += 1;

		// Third request after entity 1 version changed - should be cache MISS.

		$response3 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheMissHeader( $response3 );

		// Verify exactly one cache entry exists (old deleted, new created),
		// and the cache entry is new (created_at timestamp changed).
		$this->assertCount( 1, $this->sut->cache );
		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Verify that modifying a different entity (e.g., entity 2) does NOT invalidate cache.

		$old_cache_info2 = $this->get_cache_info();
		$this->mock_entity_cache->modify_entity_version( 'product', 2 );
		$this->fixed_time += 1;

		// Fourth request - should still be cache HIT (entity 2 change doesn't affect entity 1 cache).

		$response4 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHitHeader( $response4 );

		// Verify cache entry was not recreated.

		$new_cache_info2 = $this->get_cache_info();
		$this->assertCacheInfoEqual( $old_cache_info2, $new_cache_info2 );
	}

	/**
	 * @testdox Custom entity ID extraction works correctly.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $use_with_cache_config Whether to use with_cache config (true) or controller method override (false).
	 */
	public function test_custom_entity_id_extraction( bool $use_with_cache_config ) {

		if ( $use_with_cache_config ) {
			// Configure custom_endpoint_config endpoint with custom extract_entity_ids callback.
			$this->reconfigure_custom_endpoint_config_endpoint(
				array(
					// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
					'extract_entity_ids' => fn( $data, $request ) => array( $data['id'] * 10 ),
				)
			);
			$endpoint           = 'custom_endpoint_config';
			$original_entity_id = 6;
			$extracted_id       = 60;
		} else {
			// Enable custom entity ID extraction via controller method (IDs multiplied by 10).
			$this->sut->use_custom_entity_id_extraction = true;
			$endpoint                                   = 'single_entity';
			$original_entity_id                         = 1;
			$extracted_id                               = 10;
		}

		// First request - cache MISS, creates cache entry with custom extracted ID.

		$response1 = $this->query_endpoint( $endpoint );
		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Verify cache entry stores modified entity ID (original * 10).

		$cache_entry = array_values( $this->sut->cache )[0];
		$entity_ids  = array_keys( $cache_entry['entity_versions'] );
		$this->assertEquals( array( $extracted_id ), $entity_ids, "Cache should store custom extracted entity ID ({$original_entity_id} * 10 = {$extracted_id})" );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( $endpoint );
		$this->assertCacheHitHeader( $response2 );

		// Modify entity version for the extracted ID.

		$this->mock_entity_cache->modify_entity_version( 'product', $extracted_id );
		$this->fixed_time += 1;

		// Third request - should be cache MISS (extracted entity changed).

		$response3 = $this->query_endpoint( $endpoint );
		$this->assertCacheMissHeader( $response3 );

		// Verify cache was recreated.

		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Verify that modifying the original entity ID does NOT invalidate cache.

		$old_cache_info2 = $this->get_cache_info();
		$this->mock_entity_cache->modify_entity_version( 'product', $original_entity_id );
		$this->fixed_time += 1;

		// Fourth request - should still be cache HIT (original entity change doesn't affect cache tracking extracted entity).

		$response4 = $this->query_endpoint( $endpoint );
		$this->assertCacheHitHeader( $response4 );

		// Verify cache entry was not recreated.

		$new_cache_info2 = $this->get_cache_info();
		$this->assertCacheInfoEqual( $old_cache_info2, $new_cache_info2 );

		// Reset custom extraction if using controller method.

		if ( ! $use_with_cache_config ) {
			$this->sut->use_custom_entity_id_extraction = false;
		}
	}

	/**
	 * @testdox Cache is invalidated when entity versions change for collection responses.
	 * @testWith [1, false]
	 *           [2, true]
	 *           [3, true]
	 *           [999, false]
	 *
	 * @param int  $entity_id                   Entity ID to modify.
	 * @param bool $cache_invalidation_expected Whether cache invalidation is expected.
	 */
	public function test_cache_invalidated_when_entity_version_changes( int $entity_id, bool $cache_invalidation_expected ) {

		// First request to multiple_entities - cache MISS, creates cache entry.
		// multiple_entities returns collection with entities 2, 3, and one without ID.

		$response1 = $this->query_endpoint( 'multiple_entities' );
		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( 'multiple_entities' );

		$this->assertCacheHitHeader( $response2 );

		// Modify the entity version for the specified entity.

		$this->mock_entity_cache->modify_entity_version( 'product', $entity_id );

		// Advance time to ensure new cache entry has different timestamp.

		$this->fixed_time += 1;

		// Third request after entity version changed.

		$response3 = $this->query_endpoint( 'multiple_entities' );

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
	 * @testdox Custom cache TTL is respected.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $use_with_cache_config Whether to use with_cache config (true) or controller method override (false).
	 */
	public function test_custom_cache_ttl( bool $use_with_cache_config ) {

		if ( $use_with_cache_config ) {
			// Configure custom_endpoint_config endpoint with custom cache_ttl (20 seconds).
			$this->reconfigure_custom_endpoint_config_endpoint(
				array(
					'cache_ttl' => 20,
				)
			);
			$endpoint    = 'custom_endpoint_config';
			$ttl         = 20;
			$time_within = 15;
			$time_beyond = 6;
		} else {
			// Set custom TTL to 10 seconds via controller property.
			$this->sut->custom_cache_ttl = 10;
			$endpoint                    = 'single_entity';
			$ttl                         = 10;
			$time_within                 = 5;
			$time_beyond                 = 6;
		}

		// First request - cache MISS, creates cache entry with custom TTL.

		$response1 = $this->query_endpoint( $endpoint );
		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( $endpoint );
		$this->assertCacheHitHeader( $response2 );

		// Advance time (within TTL) - cache should still be valid.

		$this->fixed_time += $time_within;

		$response3 = $this->query_endpoint( $endpoint );
		$this->assertCacheHitHeader( $response3 );

		// Advance time beyond TTL.

		$this->fixed_time += $time_beyond;

		// Fourth request after custom TTL expiration - should be cache MISS.

		$response4 = $this->query_endpoint( $endpoint );

		$this->assertCacheMissHeader( $response4 );

		// Verify exactly one cache entry exists (old deleted, new created).

		$this->assertCount( 1, $this->sut->cache );

		// Verify the cache entry is new (created_at timestamp changed).

		$new_cache_info = $this->get_cache_info();
		$this->assertCacheInfoDifferent( $old_cache_info, $new_cache_info, $this->fixed_time );

		// Reset custom TTL if using controller method.

		if ( ! $use_with_cache_config ) {
			$this->sut->custom_cache_ttl = null;
		}
	}

	/**
	 * @testdox Custom entity type via with_cache config is respected.
	 */
	public function test_custom_entity_type_via_with_cache_config() {

		// Configure custom_endpoint_config endpoint with custom entity_type.

		$this->reconfigure_custom_endpoint_config_endpoint(
			array(
				'entity_type' => 'custom_entity',
			)
		);

		// First request - cache MISS, creates cache entry.

		$response1 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheMissHeader( $response1 );
		$this->assertCount( 1, $this->sut->cache );

		// Store the old cache info for verification.

		$old_cache_info = $this->get_cache_info();

		// Second request immediately after - cache HIT.

		$response2 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheHitHeader( $response2 );

		// Modify entity version for 'custom_entity' type (not 'product').

		$this->mock_entity_cache->modify_entity_version( 'custom_entity', 6 );

		// Advance time to ensure new cache entry has different timestamp.

		$this->fixed_time += 1;

		// Third request after custom entity version changed - should be cache MISS.

		$response3 = $this->query_endpoint( 'custom_endpoint_config' );
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

		$response4 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheHitHeader( $response4 );

		// Verify cache entry was not recreated.

		$new_cache_info2 = $this->get_cache_info();
		$this->assertCacheInfoEqual( $old_cache_info2, $new_cache_info2 );
	}

	/**
	 * @testdox Cache keys differ based on query string parameters.
	 */
	public function test_cache_key_depends_on_query_string() {

		// First request without query string - should be a cache MISS.

		$response1 = $this->query_endpoint( 'single_entity' );

		// Verify response has MISS header and original data.

		$this->assertCacheMissHeader( $response1 );
		$this->assertEquals( 200, $response1->get_status() );
		$this->assertEquals( $this->sut->responses['single_entity'], $response1->get_data() );

		// Store original response for later comparison.

		$original_data = $response1->get_data();

		// Verify response was cached.

		$this->assertCount( 1, $this->sut->cache );

		// Modify the response in the controller for subsequent requests.

		$modified_data                         = array(
			'id'   => 999,
			'name' => 'Modified Product',
		);
		$this->sut->responses['single_entity'] = $modified_data;

		// Second request WITH query string - should be a cache MISS with modified data.

		$response2 = $this->query_endpoint( 'single_entity', array( 'foo' => 'bar' ) );

		// Verify response has MISS header and modified data.

		$this->assertCacheMissHeader( $response2 );
		$this->assertEquals( 200, $response2->get_status() );
		$this->assertEquals( $modified_data, $response2->get_data() );

		// Verify we now have two cache entries (different query strings = different cache keys).

		$this->assertCount( 2, $this->sut->cache );

		// Third request without query string - should be a cache HIT with original data.

		$response3 = $this->query_endpoint( 'single_entity' );

		// Verify response has HIT header and original cached data.

		$this->assertCacheHitHeader( $response3 );
		$this->assertEquals( 200, $response3->get_status() );
		$this->assertEquals( $original_data, $response3->get_data() );
		$this->assertNotEquals( $modified_data, $response3->get_data() );

		// Fourth request WITH same query string - should be a cache HIT with modified data.

		$response4 = $this->query_endpoint( 'single_entity', array( 'foo' => 'bar' ) );

		// Verify response has HIT header and modified cached data.

		$this->assertCacheHitHeader( $response4 );
		$this->assertEquals( 200, $response4->get_status() );
		$this->assertEquals( $modified_data, $response4->get_data() );
		$this->assertNotEquals( $original_data, $response4->get_data() );

		// Verify we still have exactly two cache entries (no new entries created).

		$this->assertCount( 2, $this->sut->cache );
	}

	/**
	 * @testdox Caching is skipped when _skip_cache parameter is set.
	 * @testWith ["1"]
	 *           ["true"]
	 *
	 * @param string $skip_cache_value Value for _skip_cache parameter ("1" or "true").
	 */
	public function test_skip_cache_parameter_bypasses_caching( $skip_cache_value ) {

		// First request with _skip_cache - should be a cache SKIP.

		$response1 = $this->query_endpoint( 'single_entity', array( '_skip_cache' => $skip_cache_value ) );

		// Verify caching was skipped.

		$this->assertCachingSkipped( $response1, $this->sut->responses['single_entity'] );

		// Store original response for later comparison.

		$original_data = $response1->get_data();

		// Modify the response in the controller for subsequent requests.

		$modified_data                         = array(
			'id'   => 999,
			'name' => 'Modified Product',
		);
		$this->sut->responses['single_entity'] = $modified_data;

		// Second request with _skip_cache - should still be a cache SKIP with modified data.

		$response2 = $this->query_endpoint( 'single_entity', array( '_skip_cache' => $skip_cache_value ) );

		// Verify caching was still skipped with modified data.

		$this->assertCachingSkipped( $response2, $modified_data );
		$this->assertNotEquals( $original_data, $response2->get_data() );
	}

	/**
	 * @testdox Caching is skipped (without X-WC-Cache header) when entity versions cache is disabled.
	 */
	public function test_caching_skipped_when_entity_cache_disabled() {

		// Disable the entity versions cache.

		$this->mock_entity_cache->is_enabled = false;

		// Re-initialize the cache in the controller to pick up the disabled state.

		$this->sut->reinitialize_cache();

		// Request single_entity.

		$response = $this->query_endpoint( 'single_entity' );

		// Verify caching was skipped.

		$this->assertArrayNotHasKey( 'X-WC-Cache', $response->get_headers() );
	}

	/**
	 * @testdox Caching is skipped when woocommerce_rest_api_enable_response_caching filter returns false.
	 */
	public function test_caching_skipped_when_filter_returns_false() {

		// Track filter calls and arguments.

		$filter_called = false;
		$filter_args   = array();

		// Add filter that returns false and captures arguments.

		add_filter(
			'woocommerce_rest_api_enable_response_caching',
			function ( $enabled, $controller, $request ) use ( &$filter_called, &$filter_args ) {
				$filter_called = true;
				$filter_args   = array(
					'enabled'    => $enabled,
					'controller' => $controller,
					'request'    => $request,
				);
				return false;
			},
			10,
			3
		);

		// Request single_entity.

		$response = $this->query_endpoint( 'single_entity' );

		// Verify filter was called with correct arguments.

		$this->assertTrue( $filter_called, 'Filter should have been called' );
		$this->assertTrue( $filter_args['enabled'], 'Default enabled value should be true' );
		$this->assertSame( $this->sut, $filter_args['controller'], 'Controller should be passed to filter' );
		$this->assertInstanceOf( WP_REST_Request::class, $filter_args['request'], 'Request should be passed to filter' );

		// Verify caching was skipped.

		$this->assertCachingSkipped( $response, $this->sut->responses['single_entity'] );

		// Clean up filter.

		remove_all_filters( 'woocommerce_rest_api_enable_response_caching' );
	}

	/**
	 * @testdox Caching is skipped when must_cache returns false.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $use_with_cache_config Whether to use with_cache config (true) or controller method override (false).
	 */
	public function test_caching_skipped_when_must_cache_returns_false( bool $use_with_cache_config ) {

		if ( $use_with_cache_config ) {
			// Request false_must_cache endpoint which has a custom must_cache callback that returns false.
			$endpoint = 'false_must_cache';
		} else {
			// Set controller's must_cache return value to false.
			$this->sut->must_cache_return_value = false;
			$endpoint                           = 'single_entity';
		}

		// Request endpoint.

		$response = $this->query_endpoint( $endpoint );

		// Verify caching was skipped.

		$this->assertCachingSkipped( $response, $this->sut->responses[ $endpoint ] );
	}

	/**
	 * @testdox Caching is skipped when no entity type is available and wc_doing_it_wrong is called.
	 */
	public function test_caching_skipped_when_no_entity_type_available() {

		// Track wc_doing_it_wrong calls via LegacyProxy.

		$doing_it_wrong_args = null;
		$this->register_legacy_proxy_function_mocks(
			array(
				// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames
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

		// Request single_entity endpoint (which doesn't specify entity_type in config).

		$response = $this->query_endpoint( 'single_entity' );

		// Verify wc_doing_it_wrong was called with correct arguments.

		$this->assertNotNull( $doing_it_wrong_args, 'wc_doing_it_wrong should be called when no entity type is available' );
		$this->assertStringContainsString( 'build_cache_config', $doing_it_wrong_args['function'] );
		$this->assertStringContainsString( 'No entity type provided', $doing_it_wrong_args['message'] );
		$this->assertEquals( '10.4.0', $doing_it_wrong_args['version'] );

		// Verify caching was skipped.

		$this->assertCachingSkipped( $response, $this->sut->responses['single_entity'] );
	}

	// TESTS END HERE. Below there's auxiliary methods only.

	/**
	 * Reconfigure custom_endpoint_config endpoint and re-register routes.
	 *
	 * @param array $config Configuration array for custom_endpoint_config endpoint.
	 */
	private function reconfigure_custom_endpoint_config_endpoint( array $config ) {
		$this->sut->custom_endpoint_config_cache_config = $config;

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
	 * @param string     $endpoint_name Endpoint name (e.g., 'single_entity', 'multiple_entities').
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

	/**
	 * Create a mock EntityVersionsCache that stores cache entries in an array.
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
			 * @param string     $entity_type Entity type.
			 * @param string|int $entity_id   Entity ID.
			 * @return string Entity version.
			 */
			public function get_entity_version( string $entity_type, $entity_id ): string {
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
			 * @param string     $entity_type Entity type.
			 * @param string|int $entity_id   Entity ID.
			 * @return string New version.
			 */
			public function modify_entity_version( string $entity_type, $entity_id ): string {
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
			 * @param string     $entity_type Entity type.
			 * @param string|int $entity_id   Entity ID.
			 * @return bool True on success.
			 */
			public function forget_entity_version( string $entity_type, $entity_id ): bool {
				$key = "wc_entity_version_{$entity_type}_{$entity_id}";
				unset( $this->cache[ $key ] );
				return true;
			}
		};
	}

	/**
	 * Create a test controller.
	 *
	 * @return object Test controller instance.
	 */
	private function create_test_controller() {
		return new class() extends WP_REST_Controller {
			use RestApiCache {
				extract_entity_ids as parent_extract_entity_ids;
			}

			/**
			 * Response data for each endpoint.
			 *
			 * @var array
			 */
			public $responses = array(
				'single_entity'          => array(
					'id'   => 1,
					'name' => 'Product 1',
				),
				'multiple_entities'      => array(
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
				'entity_without_id'      => array( 'name' => 'Product without ID' ),
				'not_with_cache'         => array(
					'id'   => 4,
					'name' => 'Product 4',
				),
				'false_must_cache'       => array(
					'id'   => 5,
					'name' => 'Product 5',
				),
				'custom_endpoint_config' => array(
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
			 * Status codes for each endpoint (defaults to 200).
			 *
			 * @var array
			 */
			public $status_codes = array();

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
			 * Configuration for custom_endpoint_config endpoint with_cache call.
			 *
			 * @var array
			 */
			public $custom_endpoint_config_cache_config = array();

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
				// single_entity - cacheable, returns single entity.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/single_entity',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) {
								return $this->handle_request( 'single_entity', $request );
							}
						),
						'permission_callback' => '__return_true',
					)
				);

				// multiple_entities - cacheable, returns collection.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/multiple_entities',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) {
								return $this->handle_request( 'multiple_entities', $request );
							}
						),
						'permission_callback' => '__return_true',
					)
				);

				// entity_without_id - cacheable, returns entity without ID.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/entity_without_id',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) {
								return $this->handle_request( 'entity_without_id', $request );
							}
						),
						'permission_callback' => '__return_true',
					)
				);

				// not_with_cache - not cacheable.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/not_with_cache',
					array(
						'methods'             => 'GET',
						'callback'            => function ( $request ) {
							return $this->handle_request( 'not_with_cache', $request );
						},
						'permission_callback' => '__return_true',
					)
				);

				// false_must_cache - cacheable but with must_cache callback that returns false.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/false_must_cache',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) {
								return $this->handle_request( 'false_must_cache', $request );
							},
							array(
								'must_cache' => '__return_false',
							)
						),
						'permission_callback' => '__return_true',
					)
				);

				// custom_endpoint_config - cacheable with configurable per-endpoint settings.
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/custom_endpoint_config',
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) {
								return $this->handle_request( 'custom_endpoint_config', $request );
							},
							$this->custom_endpoint_config_cache_config
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
				$ids = $this->parent_extract_entity_ids( $data, $request );

				if ( $this->use_custom_entity_id_extraction ) {
					$ids = array_map( fn( $id ) => $id * 10, $ids );
				}

				return $ids;
			}

			/**
			 * Generic request handler.
			 *
			 * @param string          $endpoint Endpoint name.
			 * @param WP_REST_Request $request  Request object.
			 * @return WP_REST_Response|WP_Error
			 */
			private function handle_request( string $endpoint, WP_REST_Request $request ) {
				$this->requests[ $endpoint ] = $request;

				if ( is_null( $this->responses[ $endpoint ] ) ) {
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					return new WP_Error( 'server_error', 'Internal server error', array( 'status' => 500 ) );
				}

				$status_code = $this->status_codes[ $endpoint ] ?? 200;
				return new WP_REST_Response( $this->responses[ $endpoint ], $status_code );
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
}
