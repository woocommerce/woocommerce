<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\Main;
use Automattic\WooCommerce\Internal\Api\QueryCache;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use WC_Unit_Test_Case;

/**
 * Tests for {@see QueryCache} — covers the AST cache backing both the
 * standard "parse + cache" path and the Apollo Automatic Persisted Queries
 * (APQ) protocol, as well as the OPTION_OBJECT_CACHE_ENABLED toggle.
 */
class QueryCacheTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var QueryCache
	 */
	private QueryCache $sut;

	/**
	 * Set up before each test.
	 *
	 * Skips on PHP < 8.1 because the GraphQL stack (vendor parser, QueryCache
	 * dependencies) is only autoloaded after {@see Main::is_enabled()} gates
	 * on PHP 8.1+. Replicate that gate here so the autoload never triggers a
	 * parse error on older PHP.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'QueryCache tests require PHP 8.1+.' );
		}

		wp_cache_flush();
		$this->sut = new QueryCache();
	}

	/**
	 * Clean up the option and cache between tests.
	 */
	public function tearDown(): void {
		delete_option( Main::OPTION_OBJECT_CACHE_ENABLED );
		wp_cache_flush();
		parent::tearDown();
	}

	/**
	 * @testdox resolve parses a plain query and returns a DocumentNode.
	 */
	public function test_resolve_parses_a_plain_query(): void {
		$result = $this->sut->resolve( '{ widget { id } }', array() );

		$this->assertInstanceOf( DocumentNode::class, $result );
	}

	/**
	 * @testdox resolve returns the cached AST on the second call for the same query.
	 */
	public function test_resolve_returns_cached_document_on_second_call(): void {
		$first  = $this->sut->resolve( '{ widget { id } }', array() );
		$second = $this->sut->resolve( '{ widget { id } }', array() );

		$this->assertInstanceOf( DocumentNode::class, $first );
		$this->assertInstanceOf( DocumentNode::class, $second );
		// Distinct instances are fine; both must represent the same parsed query.
		$this->assertEquals( $first->toArray(), $second->toArray() );
	}

	/**
	 * @testdox resolve returns a BAD_REQUEST error when called with a null query and no APQ.
	 */
	public function test_resolve_rejects_null_query_without_apq(): void {
		$result = $this->sut->resolve( null, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertSame( 'No query provided.', $result['errors'][0]['message'] ?? null );
		$this->assertSame( 'BAD_REQUEST', $result['errors'][0]['extensions']['code'] ?? null );
	}

	/**
	 * @testdox resolve surfaces a syntax error as GRAPHQL_PARSE_ERROR.
	 */
	public function test_resolve_returns_parse_error_for_invalid_syntax(): void {
		$result = $this->sut->resolve( '{ widget { id', array() );

		$this->assertIsArray( $result );
		$this->assertSame( 'GRAPHQL_PARSE_ERROR', $result['errors'][0]['extensions']['code'] ?? null );
	}

	/**
	 * @testdox apq registers a query when both the query and matching hash are provided.
	 */
	public function test_apq_registers_when_query_and_matching_hash_are_provided(): void {
		$query      = '{ widget { id } }';
		$hash       = hash( 'sha256', $query );
		$extensions = array(
			'persistedQuery' => array(
				'version'    => 1,
				'sha256Hash' => $hash,
			),
		);

		$first = $this->sut->resolve( $query, $extensions );
		$this->assertInstanceOf( DocumentNode::class, $first );

		// Subsequent hash-only request must hit the cache.
		$second = $this->sut->resolve( null, $extensions );
		$this->assertInstanceOf( DocumentNode::class, $second );
	}

	/**
	 * @testdox apq returns PERSISTED_QUERY_HASH_MISMATCH when the supplied hash doesn't match the query.
	 */
	public function test_apq_rejects_query_when_hash_does_not_match(): void {
		$extensions = array(
			'persistedQuery' => array(
				'version'    => 1,
				'sha256Hash' => str_repeat( 'a', 64 ),
			),
		);

		$result = $this->sut->resolve( '{ widget { id } }', $extensions );

		$this->assertIsArray( $result );
		$this->assertSame( 'PERSISTED_QUERY_HASH_MISMATCH', $result['errors'][0]['extensions']['code'] ?? null );
	}

	/**
	 * @testdox apq returns PERSISTED_QUERY_NOT_FOUND when the hash is unknown.
	 */
	public function test_apq_returns_not_found_when_hash_is_unknown(): void {
		$extensions = array(
			'persistedQuery' => array(
				'version'    => 1,
				'sha256Hash' => str_repeat( 'b', 64 ),
			),
		);

		$result = $this->sut->resolve( null, $extensions );

		$this->assertIsArray( $result );
		$this->assertSame( 'PERSISTED_QUERY_NOT_FOUND', $result['errors'][0]['extensions']['code'] ?? null );
	}

	/**
	 * @testdox apq is ignored when the version is not 1 — falls through to the standard path.
	 */
	public function test_apq_falls_through_when_version_is_not_one(): void {
		$extensions = array(
			'persistedQuery' => array(
				'version'    => 2,
				'sha256Hash' => str_repeat( 'c', 64 ),
			),
		);

		$result = $this->sut->resolve( '{ widget { id } }', $extensions );

		$this->assertInstanceOf( DocumentNode::class, $result );
	}

	/**
	 * @testdox get_cache_ttl exposes the configured TTL.
	 */
	public function test_get_cache_ttl_is_a_day(): void {
		$this->assertSame( DAY_IN_SECONDS, QueryCache::get_cache_ttl() );
	}

	/**
	 * @testdox resolve writes the parsed document to the object cache when the toggle is on.
	 */
	public function test_resolve_writes_to_cache_when_toggle_on(): void {
		update_option( Main::OPTION_OBJECT_CACHE_ENABLED, 'yes' );

		$result = $this->sut->resolve( '{ __typename }', array() );

		$this->assertInstanceOf( DocumentNode::class, $result );
		$this->assertNotFalse(
			wp_cache_get( $this->cache_key_for( '{ __typename }' ), 'wc-graphql' ),
			'Standard parse should persist the AST in the object cache.'
		);
	}

	/**
	 * @testdox resolve does not write to the object cache when the toggle is off.
	 */
	public function test_resolve_does_not_write_to_cache_when_toggle_off(): void {
		update_option( Main::OPTION_OBJECT_CACHE_ENABLED, 'no' );

		$result = $this->sut->resolve( '{ __typename }', array() );

		$this->assertInstanceOf( DocumentNode::class, $result );
		$this->assertFalse(
			wp_cache_get( $this->cache_key_for( '{ __typename }' ), 'wc-graphql' ),
			'No cache entry should be written when the ObjectCache toggle is off.'
		);
	}

	/**
	 * Build the QueryCache cache key for a query string. Prefix kept in sync
	 * with QueryCache::CACHE_KEY_PREFIX.
	 *
	 * @param string $query The GraphQL query string.
	 */
	private function cache_key_for( string $query ): string {
		return 'graphql_ast_v15_' . hash( 'sha256', $query );
	}
}
