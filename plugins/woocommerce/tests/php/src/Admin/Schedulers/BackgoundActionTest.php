<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Schedulers;

use Automattic\WooCommerce\Admin\Schedulers\BackgroundAction;
use PHPUnit\Framework\TestCase;

/**
 * BackgroundActionTest
 */
class BackgroundActionTest extends \WC_Unit_Test_Case {
	/**
	 * Test that BackgroundAction correctly stores and retrieves action data.
	 */
	public function test_background_action_stores_and_retrieves_data() {
		$action = new BackgroundAction(
			array(
				'hook'                => 'test_action',
				'interval_in_seconds' => 21600,
				'callback'            => function() {
					return 'test_callback';
				}
			)
		);

		$this->assertEquals( 'test_action', $action->get_hook() );
		$this->assertEquals( 21600, $action->get_interval() );
	}

	/**
	 * Test that BackgroundAction throws an exception if the id is missing.
	 */
	public function test_background_action_missing_required_hook_name() {
		$this->expectException(\InvalidArgumentException::class);

		new BackgroundAction(
			array(
				'interval_in_seconds' => 21600,
				'callback' => function() {
					return 'test_callback';
				}
			)
		);
	}

	/**
	 * Test that BackgroundAction throws an exception if the callback is missing.
	 */
	public function test_background_action_missing_required_callback() {
		$this->expectException(\InvalidArgumentException::class);

		new BackgroundAction(
			array(
				'hook_name' => 'test_action',
				'interval_in_seconds' => 21600,
			)
		);
	}

	/**
	 * Test that BackgroundAction runs the callback.
	 */
	public function test_background_action_runs_callback() {
		$mock_callback = $this
			->getMockBuilder( 'stdClass' )
			->setMethods( array( 'callback' ) )
			->getMock();

		$mock_callback->expects( $this->once() )
			->method( 'callback' )
			->will( $this->returnValue( true ) );

		$action = new BackgroundAction(
			array(
				'hook'                => 'test_action',
				'interval_in_seconds' => 21600,
				'callback'            => array( $mock_callback, 'callback' ),
			)
		);

		$action->run_callback();
	}

	/**
	 * Test that BackgroundAction schedules an action.
	 */
	public function test_background_action_schedules_action() {
		$action = new BackgroundAction(
			array(
				'hook' => 'test_action',
				'interval_in_seconds' => 21600,
				'callback' => function() {
					return 'test_callback';
				}
			)
		);

		$action->schedule();

		$this->assertTrue( as_has_scheduled_action( 'test_action' ) );
	}
} 
