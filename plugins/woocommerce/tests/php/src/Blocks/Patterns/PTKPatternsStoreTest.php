<?php

namespace Automattic\WooCommerce\Tests\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\Patterns\PTKClient;
use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;
use PharIo\Version\VersionConstraint;

/**
 * Unit tests for the PTK Patterns Store class.
 */
class PTKPatternsStoreTest extends \WP_UnitTestCase {
	/**
	 * The store instance.
	 *
	 * @var PTKPatternsStore $store
	 */
	private $pattern_store;

	/**
	 * The Patterns Toolkit client instance.
	 *
	 * @var PTKClient $client
	 */
	private $ptk_client;

	/**
	 * Initialize the store and client instances.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ptk_client    = $this->createMock( PTKClient::class );
		$this->pattern_store = new PTKPatternsStore( $this->ptk_client );
	}

	/**
	 * Test get_patterns should come from the cache when the transient is set.
	 */
	public function test_get_patterns_should_come_from_the_cache_when_the_transient_is_set() {
		$expected_patterns = array(
			array(
				'title' => 'My pattern',
				'slug'  => 'my-pattern',
			),
		);

		set_transient( PTKPatternsStore::TRANSIENT_NAME, $expected_patterns );

		$this->ptk_client
			->expects( $this->never() )
			->method( 'fetch_patterns' );

		$patterns = $this->pattern_store->get_patterns();

		$this->assertEquals( $expected_patterns, $patterns );
	}

	/**
	 * Test get_patterns should be empty when fetching patterns return an error.
	 */
	public function test_get_patterns_should_be_empty_when_fetching_patterns_return_an_error() {
		$this->ptk_client
			->expects( $this->once() )
			->method( 'fetch_patterns' )
			->willReturn( new \WP_Error( 'error', 'Request failed.' ) );

		$patterns = $this->pattern_store->get_patterns();

		$this->assertNull( $patterns );
	}

	/**
	 * Test get_patterns should return the patterns from PTK and set the transient.
	 */
	public function test_get_patterns_should_return_the_patterns_from_ptk_and_set_the_transient() {
		$expected_patterns = array(
			array(
				'title' => 'My pattern',
				'slug'  => 'my-pattern',
			),
		);

		$this->ptk_client
			->expects( $this->once() )
			->method( 'fetch_patterns' )
			->willReturn( $expected_patterns );

		$patterns = $this->pattern_store->get_patterns();

		$this->assertEquals( $expected_patterns, $patterns );
		$this->assertEquals( $expected_patterns, get_transient( PTKPatternsStore::TRANSIENT_NAME ) );
	}

	/**
	 * Test get_patterns should filter out the excluded patterns.
	 */
	public function test_get_patterns_should_filter_out_the_excluded_patterns() {
		$expected_patterns = array(
			array(
				'title' => 'My pattern',
				'slug'  => 'my-pattern',
			),
			array(
				'ID'    => PTKPatternsStore::EXCLUDED_PATTERNS[0],
				'title' => 'Excluded pattern',
				'slug'  => 'excluded-pattern',
			),
		);

		$this->ptk_client
			->expects( $this->once() )
			->method( 'fetch_patterns' )
			->willReturn( $expected_patterns );

		$patterns = $this->pattern_store->get_patterns();

		$this->assertEquals( array( $expected_patterns[0] ), $patterns );
		$this->assertEquals( array( $expected_patterns[0] ), get_transient( PTKPatternsStore::TRANSIENT_NAME ) );
	}
}
