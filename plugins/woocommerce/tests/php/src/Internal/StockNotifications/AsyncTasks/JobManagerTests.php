<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\JobManager;

/**
 * JobManagerTests data tests.
 */
class JobManagerTests extends \WC_Unit_Test_Case {

	/**
	 * @var JobManager
	 */
	private $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new JobManager();
	}

	/**
	 * @after
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );
	}

	/**
	 * Test schedule_initial_job_for_product method.
	 */
	public function test_schedule_initial_job_for_product() {
		$product_id = 123;
		$this->sut->schedule_initial_job_for_product( $product_id );

		$this->assertNotFalse(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product_id ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test schedule_initial_job_for_product method with a product that already has a job scheduled.
	 */
	public function test_schedule_initial_job_for_product_with_existing_job() {
		$product_id = 123;
		$result     = $this->sut->schedule_initial_job_for_product( $product_id );

		$this->assertTrue( $result );

		$result = $this->sut->schedule_initial_job_for_product( $product_id );

		$this->assertFalse( $result );
	}

	/**
	 * Test schedule_next_batch_for_product method.
	 */
	public function test_schedule_next_batch_for_product() {
		$product_id = 123;
		$this->sut->schedule_next_batch_for_product( $product_id );

		$this->assertNotFalse(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product_id ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test schedule_next_batch_for_product method with a product that already has a job scheduled.
	 */
	public function test_schedule_next_batch_for_product_with_existing_job() {
		$product_id = 123;
		$this->sut->schedule_next_batch_for_product( $product_id );

		$result = $this->sut->schedule_next_batch_for_product( $product_id );

		$this->assertFalse( $result );
	}
}
