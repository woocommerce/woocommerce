<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Schedulers;

use Automattic\WooCommerce\Admin\Schedulers\BackgroundAction;
use Automattic\WooCommerce\Admin\Schedulers\BackgroundScheduler;
use PHPUnit\Framework\TestCase;

/**
 * BackgroundSchedulerTest
 */
class BackgroundSchedulerTest extends \WC_Unit_Test_Case {
	/**
	 * BackgroundScheduler instance.
	 *
	 * @var BackgroundScheduler
	 */
	private $background_scheduler;

	/**
	 * Setup the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->background_scheduler = new BackgroundScheduler();
	}

	/**
	 * Test that the background scheduler initializes hooks.
	 */
	public function test_background_scheduler_initializes_hooks() {
		$this->background_scheduler->init();
		$this->assertTrue( as_has_scheduled_action( BackgroundScheduler::BACKGROUND_HOOK_NAME ) );
	}
} 
