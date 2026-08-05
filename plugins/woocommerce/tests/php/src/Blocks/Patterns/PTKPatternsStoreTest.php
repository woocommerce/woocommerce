<?php
declare(strict_types=1);

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
	public function setUp(): void {
		parent::setUp();

		// Clean up any existing actions before each test.
		as_unschedule_all_actions( 'fetch_patterns', array(), 'woocommerce' );

		$this->ptk_client    = $this->createMock( PTKClient::class );
		$this->pattern_store = new PTKPatternsStore( $this->ptk_client );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Unschedule all fetch_patterns actions to avoid test pollution.
		as_unschedule_all_actions( 'fetch_patterns', array(), 'woocommerce' );

		// Clean up options.
		delete_option( PTKPatternsStore::OPTION_NAME );
		delete_option( 'woocommerce_allow_tracking' );

		parent::tearDown();
	}

	/**
	 * Test get_patterns should come from the cache when the transient is set.
	 */
	public function test_get_patterns_should_come_from_the_cache_when_the_transient_is_set() {
		$expected_patterns = array(
			array(
				'ID'           => 14870,
				'site_id'      => 174455321,
				'title'        => 'Review: A quote with scattered images',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'dependencies' => [],
				'categories'   => array(
					'testimonials' => array(
						'slug'  => 'testimonials',
						'title' => 'Testimonials',
					),
				),
			),
		);

		update_option( PTKPatternsStore::OPTION_NAME, $expected_patterns, false );

		$this->ptk_client
			->expects( $this->never() )
			->method( 'fetch_patterns' );

		$this->ptk_client
			->expects( $this->once() )
			->method( 'is_valid_schema' )
			->willReturn( true );

		$patterns = $this->pattern_store->get_patterns();

		$this->assertEquals( $expected_patterns, $patterns );
	}

	/**
	 * Test get_patterns should be empty when the cache is empty.
	 */
	public function test_get_patterns_should_return_an_empty_array_when_the_cache_is_empty() {
		delete_option( PTKPatternsStore::OPTION_NAME );

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
				'ID'           => 14870,
				'site_id'      => 174455321,
				'title'        => 'Review: A quote with scattered images',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'dependencies' => [],
				'categories'   => array(
					'testimonials' => array(
						'slug'        => 'testimonials',
						'title'       => 'Testimonials',
						'description' => 'Share reviews and feedback about your brand/business.',
					),
				),
			),
		);

		update_option( PTKPatternsStore::OPTION_NAME, $expected_patterns, false );

		// Schedule an action so we can verify it gets unscheduled.
		as_schedule_single_action( time(), 'fetch_patterns', array(), 'woocommerce' );
		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ) );

		$this->pattern_store->flush_cached_patterns();

		$patterns = get_option( PTKPatternsStore::OPTION_NAME );
		$this->assertFalse( $patterns );
		$this->assertFalse( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'All fetch_patterns actions should be unscheduled' );
	}

	/**
	 * Test patterns cache is flushed when tracking is not allowed.
	 */
	public function test_patterns_cache_is_flushed_when_tracking_is_not_allowed() {
		update_option( 'woocommerce_allow_tracking', 'no' );
		$expected_patterns = array(
			array(
				'ID'           => 14870,
				'site_id'      => 174455321,
				'title'        => 'Review: A quote with scattered images',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'dependencies' => [],
				'categories'   => array(
					'testimonials' => array(
						'slug'        => 'testimonials',
						'title'       => 'Testimonials',
						'description' => 'Share reviews and feedback about your brand/business.',
					),
				),
			),
		);
		update_option( PTKPatternsStore::OPTION_NAME, $expected_patterns );

		$this->pattern_store->flush_or_fetch_patterns();

		$patterns = get_option( PTKPatternsStore::OPTION_NAME );
		$this->assertFalse( $patterns );
	}

	/**
	 * Test fetching patterns is scheduled when tracking is allowed.
	 */
	public function test_fetching_patterns_is_schedule_when_tracking_is_allowed() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$expected_patterns = array(
			array(
				'ID'         => 14870,
				'site_id'    => 174455321,
				'title'      => 'Review: A quote with scattered images',
				'name'       => 'review-a-quote-with-scattered-images',
				'html'       => '<!-- /wp:spacer -->',
				'categories' => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
		);
		update_option( PTKPatternsStore::OPTION_NAME, $expected_patterns );

		$this->pattern_store->flush_or_fetch_patterns();

		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ) );
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

		$patterns = get_option( PTKPatternsStore::OPTION_NAME );
		$this->assertFalse( $patterns );
	}

	/**
	 * Test fetch patterns should set the patterns cache after fetching patterns if tracking is allowed.
	 */
	public function test_fetch_patterns_should_set_the_patterns_cache_after_fetching_patterns() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$expected_patterns = array(
			array(
				'ID'           => 14870,
				'site_id'      => 174455321,
				'title'        => 'Review: A quote with scattered images',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'categories'   => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
				'dependencies' => [],
			),
		);

		$this->ptk_client
			->expects( $this->once() )
			->method( 'fetch_patterns' )
			->willReturn( $expected_patterns );
		$this->pattern_store->fetch_patterns();

		$patterns = get_option( PTKPatternsStore::OPTION_NAME );

		$this->assertEquals( $expected_patterns, $patterns );
	}

	/**
	 * Test fetch_patterns should register testimonials category as reviews.
	 */
	public function test_fetch_patterns_should_register_testimonials_category_as_reviews() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$ptk_patterns = array(
			array(
				'ID'           => 14870,
				'site_id'      => 174455321,
				'title'        => 'Review: A quote with scattered images',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'categories'   => array(
					'testimonials' => array(
						'slug'  => 'testimonials',
						'title' => 'Testimonials',
					),
				),
				'dependencies' => [],
			),
		);

		$expected_patterns = array(
			array(
				'ID'           => 14870,
				'site_id'      => 174455321,
				'title'        => 'Review: A quote with scattered images',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'categories'   => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
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

		$patterns = get_option( PTKPatternsStore::OPTION_NAME );

		$this->assertEquals( $expected_patterns, $patterns );
		$this->assertEquals( $expected_patterns, get_option( PTKPatternsStore::OPTION_NAME ) );
	}

	/**
	 * Test fetch_patterns should filter out the patterns with dependencies.
	 */
	public function test_fetch_patterns_should_filter_out_the_patterns_with_dependencies_diff_than_woocommerce() {
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$ptk_patterns = array(
			array(
				'ID'         => 1,
				'title'      => 'No deps',
				'name'       => 'review-a-quote-with-scattered-images',
				'html'       => '<!-- /wp:spacer -->',
				'categories' => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
			array(
				'ID'           => 2,
				'title'        => 'Jetpack dep',
				'name'         => 'review-a-quote-with-scattered-images',
				'dependencies' => [ 'jetpack' ],
				'html'         => '<!-- /wp:spacer -->',
				'categories'   => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
			array(
				'ID'           => 3,
				'title'        => 'Jetpack and WooCommerce dep',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'dependencies' => [ 'woocommerce', 'jetpack' ],
				'categories'   => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
			array(
				'ID'           => 4,
				'title'        => 'WooCommerce dep',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'dependencies' => [ 'woocommerce' ],
				'categories'   => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
			array(
				'ID'           => 5,
				'title'        => 'Empty deps',
				'name'         => 'review-a-quote-with-scattered-images',
				'html'         => '<!-- /wp:spacer -->',
				'dependencies' => [],
				'categories'   => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
		);

		$expected_patterns = array(
			array(
				'ID'         => 1,
				'title'      => 'No deps',
				'name'       => 'review-a-quote-with-scattered-images',
				'html'       => '<!-- /wp:spacer -->',
				'categories' => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
			array(
				'ID'           => 4,
				'title'        => 'WooCommerce dep',
				'name'         => 'review-a-quote-with-scattered-images',
				'dependencies' => [ 'woocommerce' ],
				'html'         => '<!-- /wp:spacer -->',
				'categories'   => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
			array(
				'ID'           => 5,
				'title'        => 'Empty deps',
				'name'         => 'review-a-quote-with-scattered-images',
				'dependencies' => [],
				'html'         => '<!-- /wp:spacer -->',
				'categories'   => array(
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

		$patterns = get_option( PTKPatternsStore::OPTION_NAME );

		$this->assertEquals( $expected_patterns, $patterns );
		$this->assertEquals( $expected_patterns, get_option( PTKPatternsStore::OPTION_NAME ) );
	}

	/**
	 * Test ensure_recurring_fetch_patterns_if_enabled schedules recurring action when tracking is enabled.
	 */
	public function test_ensure_recurring_fetch_patterns_schedules_recurring_action_when_tracking_enabled() {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$this->pattern_store->ensure_recurring_fetch_patterns_if_enabled();

		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'fetch_patterns action should be scheduled' );
	}

	/**
	 * Test ensure_recurring_fetch_patterns_if_enabled does not schedule when tracking is disabled.
	 */
	public function test_ensure_recurring_fetch_patterns_does_not_schedule_when_tracking_disabled() {
		update_option( 'woocommerce_allow_tracking', 'no' );

		$this->pattern_store->ensure_recurring_fetch_patterns_if_enabled();

		$this->assertFalse( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'fetch_patterns action should not be scheduled when tracking is disabled' );
	}

	/**
	 * Test flush_cached_patterns unschedules all actions including recurring ones.
	 */
	public function test_flush_cached_patterns_unschedules_all_actions() {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Schedule both single and recurring actions.
		as_schedule_single_action( time(), 'fetch_patterns', array(), 'woocommerce' );
		as_schedule_recurring_action( time(), DAY_IN_SECONDS, 'fetch_patterns', array(), 'woocommerce' );

		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'Actions should be scheduled' );

		$this->pattern_store->flush_cached_patterns();

		$this->assertFalse( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'All fetch_patterns actions should be unscheduled after flush' );
	}

	/**
	 * Test flush_cached_patterns defers unscheduling when called before action_scheduler_init.
	 *
	 * This test simulates the scenario where flush_cached_patterns is called during early
	 * initialization, before Action Scheduler has been initialized.
	 */
	public function test_flush_cached_patterns_defers_unscheduling_before_action_scheduler_init() {
		global $wp_actions;

		// Schedule an action.
		as_schedule_single_action( time(), 'fetch_patterns', array(), 'woocommerce' );
		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'Action should be scheduled initially' );

		// Save the original action_scheduler_init count.
		$original_count = $wp_actions['action_scheduler_init'] ?? 0;

		// Simulate that action_scheduler_init has not yet fired by unsetting it.
		if ( isset( $wp_actions['action_scheduler_init'] ) ) {
			unset( $wp_actions['action_scheduler_init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// Verify did_action returns 0 (not fired).
		$this->assertEquals( 0, did_action( 'action_scheduler_init' ), 'action_scheduler_init should appear not fired' );

		// Call flush_cached_patterns before action_scheduler_init has fired.
		$this->pattern_store->flush_cached_patterns();

		// The option should be deleted immediately.
		$patterns = get_option( PTKPatternsStore::OPTION_NAME );
		$this->assertFalse( $patterns, 'Patterns option should be deleted immediately' );

		// The action should still be scheduled (unscheduling is deferred).
		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'Action should still be scheduled before action_scheduler_init fires' );

		/**
		 * Simulate action_scheduler_init firing.
		 *
		 * @since 10.4.1
		 * @return void
		 */
		do_action( 'action_scheduler_init' );

		// After action_scheduler_init fires, the action should be unscheduled.
		$this->assertFalse( as_has_scheduled_action( 'fetch_patterns', array(), 'woocommerce' ), 'Action should be unscheduled after action_scheduler_init fires' );

		// Restore the original action count.
		$wp_actions['action_scheduler_init'] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
