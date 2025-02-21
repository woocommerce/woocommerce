<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\BackgroundCache;
use Automattic\WooCommerce\Caching\CacheAction;
use PHPUnit\Framework\TestCase;

/**
 * BackgroundCacheTest
 */
class BackgroundCacheTest extends \WC_Unit_Test_Case {
	/**
	 * BackgroundCache instance.
	 *
	 * @var BackgroundCache
	 */
	private $background_cache;

	/**
	 * Setup the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->background_cache = new BackgroundCache();
	}

	/**
	 * Test that the background cache schedules an action in action scheduler.
	 */
	public function test_background_cache_schedules_action() {
		$action = new CacheAction(
			array(
				'id' => 'test_action',
				'interval_in_seconds' => 21600,
				'callback' => function() {
					return 'test_callback';
				}
			)
		);

		$this->background_cache->schedule_action( $action );

		$next_scheduled_action = as_next_scheduled_action( BackgroundCache::BACKGROUND_HOOK_NAME, array( $action->get_id() ) );
		$this->assertNotFalse( $next_scheduled_action, 'Action should be scheduled' );
	}

	/**
	 * Test that the background cache throws an exception if the action is not a CacheAction.
	 */
	public function test_background_cache_schedules_action_validates_input() {
		$this->expectException( \InvalidArgumentException::class );
		$this->background_cache->schedule_action( null );
	}

	/**
	 * Test that run_callback correctly processes a cache action.
	 */
	public function test_background_cache_runs_action_callback() {
		$mock_callback = $this
			->getMockBuilder( 'stdClass' )
			->setMethods( array( 'callback' ) )
			->getMock();

		$mock_callback->expects( $this->once() )
			->method( 'callback' )
			->will( $this->returnValue( true ) );

		$action = new CacheAction(
			array(
				'id' => 'test_action',
				'interval_in_seconds' => 21600,
				'callback' => array( $mock_callback, 'callback' ),
				'force_refresh' => true,
			)
		);

		$this->background_cache->schedule_action( $action );
		$this->background_cache->run_callback( $action->get_id() );
	}
} 
