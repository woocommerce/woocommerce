<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\CacheAction;
use PHPUnit\Framework\TestCase;

/**
 * CacheActionTest
 */
class CacheActionTest extends \WC_Unit_Test_Case {
	/**
	 * Test that CacheAction correctly stores and retrieves action data.
	 */
	public function test_cache_action_stores_and_retrieves_data() {
		$action = new CacheAction(
			array(
				'id' => 'test_action',
				'interval_in_seconds' => 21600,
				'callback' => function() {
					return 'test_callback';
				}
			)
		);

		$this->assertEquals( 'test_action', $action->get_id() );
		$this->assertEquals( 21600, $action->get_interval() );
	}

	/**
	 * Test that CacheAction throws an exception if the id is missing.
	 */
	public function test_cache_action_missing_required_id() {
		$this->expectException(\InvalidArgumentException::class);

		new CacheAction(
			array(
				'interval_in_seconds' => 21600,
				'callback' => function() {
					return 'test_callback';
				}
			)
		);
	}

	/**
	 * Test that CacheAction throws an exception if the callback is missing.
	 */
	public function test_cache_action_missing_required_callback() {
		$this->expectException(\InvalidArgumentException::class);

		new CacheAction(
			array(
				'id' => 'test_action',
				'interval_in_seconds' => 21600,
			)
		);
	}

	/**
	 * Test that CacheAction returns false if the cache is not set.
	 */
	public function test_cache_action_default_is_cached_returns_false() {
		$action = new CacheAction(
			array(
				'id'                  => 'test_action',
				'interval_in_seconds' => 21600,
				'cache_key'           => 'test_key',
				'callback'            => function() {
					return 'test_callback';
				}
			)
		);

		$this->assertFalse( $action->is_cached() );
	}

	/**
	 * Test that CacheAction returns true if the cache is set.
	 */
	public function test_cache_action_default_is_cached_returns_true() {
		wp_cache_set( 'test_key', 'test_value' );
		$action = new CacheAction(
			array(
				'id'                  => 'test_action',
				'interval_in_seconds' => 21600,
				'cache_key'           => 'test_key',
				'callback'            => function() {
					return 'test_callback';
				}
			)
		);

		$this->assertTrue( $action->is_cached() );
		wp_cache_delete( 'test_key' );
	}

	/**
	 * Test that CacheAction returns false if the custom is_cached function returns false.
	 */
	public function test_cache_action_custom_is_cached_returns_false() {
		$action = new CacheAction(
			array(
				'id'                  => 'test_action',
				'interval_in_seconds' => 21600,
				'is_cached'           => function() {
					return false;
				},
				'callback'            => function() {
					return 'test_callback';
				}
			)
		);

		$this->assertFalse( $action->is_cached() );
	}

	/**
	 * Test that CacheAction returns true if the custom is_cached function returns true.
	 */
	public function test_cache_action_custom_is_cached_returns_true() {
		$action = new CacheAction(
			array(
				'id' => 'test_action',
				'interval_in_seconds' => 21600,
				'is_cached' => function() {
					return true;
				},
				'callback' => function() {
					return 'test_callback';
				}
			)
		);

		$this->assertTrue( $action->is_cached() );
	}

	/**
	 * Test that CacheAction runs the callback if force refresh is true.
	 */
	public function test_cache_action_maybe_run_callback_runs_callback_if_force_refresh_is_true() {
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
				'force_refresh' => true,
				'callback' => array( $mock_callback, 'callback' ),
				'is_cached' => function() {
					return true;
				}
			)
		);
		
		$action->maybe_run_callback();
	}

	/**
	 * Test that CacheAction runs the callback if the cache is not set.
	 */
	public function test_cache_action_maybe_run_callback_runs_callback_if_cache_is_not_set() {
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
				'force_refresh' => false,
				'callback' => array( $mock_callback, 'callback' ),
				'is_cached' => function() {
					return false;
				}
			)
		);

		$action->maybe_run_callback();
	}

	/**
	 * Test that CacheAction does not run the callback if the cache is set.
	 */
	public function test_cache_action_maybe_run_callback_does_not_run_callback_if_cache_is_set() {
		$mock_callback = $this
			->getMockBuilder( 'stdClass' )
			->setMethods( array( 'callback' ) )
			->getMock();

		$mock_callback->expects( $this->never() )
			->method( 'callback' );

		$action = new CacheAction(
			array(
				'id' => 'test_action',
				'force_refresh' => false,
				'callback' => array( $mock_callback, 'callback' ),
				'is_cached' => function() {
					return true;
				}
			)
		);

		$action->maybe_run_callback();
	}
} 
