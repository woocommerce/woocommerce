<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\Main;
use Automattic\WooCommerce\Internal\Api\QueryCache;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use WC_Unit_Test_Case;

/**
 * Tests for QueryCache, focused on the OPTION_OBJECT_CACHE_ENABLED toggle.
 */
class QueryCacheTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var QueryCache
	 */
	private $sut;

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
	 * Clean up the option between tests.
	 */
	public function tearDown(): void {
		delete_option( Main::OPTION_OBJECT_CACHE_ENABLED );
		wp_cache_flush();
		parent::tearDown();
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
	 */
	private function cache_key_for( string $query ): string {
		return 'graphql_ast_v15_' . hash( 'sha256', $query );
	}
}
