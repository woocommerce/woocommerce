<?php

namespace Automattic\WooCommerce\Tests\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\Patterns\PTKClient;
use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;
use WP_Error;

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
	 * Test get_patterns should be empty when the cache is empty.
	 */
	public function test_get_patterns_should_return_an_empty_array_when_the_cache_is_empty() {
		delete_transient( PTKPatternsStore::TRANSIENT_NAME );

		$this->ptk_client
			->expects( $this->never() )
			->method( 'fetch_patterns' );

		$patterns = $this->pattern_store->get_patterns();

		$this->assertEmpty( $patterns );
	}

	/**
	 * Test patterns cache is empty after flushing it.
	 */
	public function test_patterns_cache_is_empty_after_flushing_it() {
		$expected_patterns = array(
			array(
				'title' => 'My pattern',
				'slug'  => 'my-pattern',
			),
		);

		set_transient( PTKPatternsStore::TRANSIENT_NAME, $expected_patterns );

		$this->pattern_store->flush_cached_patterns();

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertFalse( $patterns );
	}

	/**
	 * Test patterns cache is flushed when tracking is not allowed.
	 */
	public function test_patterns_cache_is_flushed_when_tracking_is_not_allowed() {
		update_option( 'woocommerce_allow_tracking', 'no' );
		$expected_patterns = array(
			array(
				'title' => 'My pattern',
				'slug'  => 'my-pattern',
			),
		);
		set_transient( PTKPatternsStore::TRANSIENT_NAME, $expected_patterns );

		$this->pattern_store->flush_or_fetch_patterns();

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertFalse( $patterns );
	}

	/**
	 * Test fetching patterns is scheduled when tracking is allowed.
	 */
	public function test_fetching_patterns_is_schedule_when_tracking_is_allowed() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$expected_patterns = array(
			array(
				'title' => 'My pattern',
				'slug'  => 'my-pattern',
			),
		);
		set_transient( PTKPatternsStore::TRANSIENT_NAME, $expected_patterns );

		$this->pattern_store->flush_or_fetch_patterns();

		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns' ) );
	}

	/**
	 * Test fetch patterns should not set the patterns cache when fetching patterns fails.
	 */
	public function test_fetch_patterns_should_not_set_the_patterns_cache_when_fetching_patterns_fails() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$this->ptk_client
			->expects( $this->once() )
			->method( 'fetch_patterns' )
			->willReturn( new WP_Error( 'error', 'Request failed.' ) );

		$this->pattern_store->fetch_patterns();

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertFalse( $patterns );
	}

	/**
	 * Test fetch patterns should set the patterns cache after fetching patterns if tracking is allowed.
	 */
	public function test_fetch_patterns_should_set_the_patterns_cache_after_fetching_patterns() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
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
		$this->pattern_store->fetch_patterns();

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );

		$this->assertEquals( $expected_patterns, $patterns );
	}

	/**
	 * Test fetch_patterns should register testimonials category as reviews.
	 */
	public function test_fetch_patterns_should_register_testimonials_category_as_reviews() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$ptk_patterns = array(
			array(
				'title'      => 'My pattern',
				'slug'       => 'my-pattern',
				'categories' => array(
					'testimonials' => array(
						'slug'  => 'testimonials',
						'title' => 'Testimonials',
					),
				),
			),
		);

		$expected_patterns = array(
			array(
				'title'      => 'My pattern',
				'slug'       => 'my-pattern',
				'categories' => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
		);

		$this->ptk_client
			->expects( $this->once() )
			->method( 'fetch_patterns' )
			->willReturnOnConsecutiveCalls(
				$ptk_patterns,
				array()
			);

		$this->pattern_store->fetch_patterns();

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );

		$this->assertEquals( $expected_patterns, $patterns );
		$this->assertEquals( $expected_patterns, get_transient( PTKPatternsStore::TRANSIENT_NAME ) );
	}

	/**
	 * Test fetch_patterns should filter out the patterns with dependencies.
	 */
	public function test_fetch_patterns_should_filter_out_the_patterns_with_dependencies_diff_than_woocommerce() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$ptk_patterns = array(
			array(
				'ID'    => 1,
				'title' => 'No deps',
			),
			array(
				'ID'           => 2,
				'title'        => 'Jetpack dep',
				'dependencies' => [ 'jetpack' ],
			),
			array(
				'ID'           => 3,
				'title'        => 'Jetpack and WooCommerce dep',
				'dependencies' => [ 'woocommerce', 'jetpack' ],
			),
			array(
				'ID'           => 4,
				'title'        => 'WooCommerce dep',
				'dependencies' => [ 'woocommerce' ],
			),
			array(
				'ID'           => 5,
				'title'        => 'Empty deps',
				'dependencies' => [],
			),
		);

		$expected_patterns = array(
			array(
				'ID'    => 1,
				'title' => 'No deps',
			),
			array(
				'ID'           => 4,
				'title'        => 'WooCommerce dep',
				'dependencies' => [ 'woocommerce' ],
			),
			array(
				'ID'           => 5,
				'title'        => 'Empty deps',
				'dependencies' => [],
			),
		);

		$this->ptk_client
			->expects( $this->once() )
			->method( 'fetch_patterns' )
			->willReturnOnConsecutiveCalls(
				$ptk_patterns,
				array()
			);

		$this->pattern_store->fetch_patterns();

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );

		$this->assertEquals( $expected_patterns, $patterns );
		$this->assertEquals( $expected_patterns, get_transient( PTKPatternsStore::TRANSIENT_NAME ) );
	}

	/**
	 * Asserts that the response is an error with the expected error code and message.
	 *
	 * @param array|WP_Error $response The response to assert.
	 * @param string         $expected_error_message The expected error message.
	 * @return void
	 */
	private function assertErrorResponse( $response, $expected_error_message ) {
		$this->assertInstanceOf( WP_Error::class, $response );

		$error_code = $response->get_error_code();
		$this->assertEquals( 'patterns_store_error', $error_code );

		$error_message = $response->get_error_message();
		$this->assertEquals( $expected_error_message, $error_message );
	}
}
