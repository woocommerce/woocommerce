<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Logging;

use Automattic\WooCommerce\Internal\Logging\LogsDeletionScheduler;

/**
 * Tests for the LogsDeletionScheduler class.
 */
class LogsDeletionSchedulerTest extends \WC_Unit_Test_Case {
	/**
	 * Fake logger class.
	 *
	 * @var object
	 */
	private static $fake_logger;

	/**
	 * Set to true if the (fake) deletion action has been scheduled.
	 *
	 * @var bool
	 */
	private bool $action_has_been_scheduled;

	/**
	 * The System Under Test.
	 *
	 * @var LogsDeletionScheduler
	 */
	private LogsDeletionScheduler $sut;

	/**
	 * Runs before all the tests in the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public array $sources_cleared = array();

			public function clear( $source = '', $quiet = false ) {
				$this->sources_cleared[] = $source;
			}

			public function reset() {
				$this->sources_cleared = array();
			}
		};
		// phpcs:enable Squiz.Commenting

		self::$fake_logger = $fake_logger;
	}

	/**
	 * Runs before each test in the class.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function () {
					return self::$fake_logger;
				},
			)
		);

		$this->action_has_been_scheduled = false;
		self::$fake_logger->reset();

		add_action( 'pre_as_schedule_single_action', array( $this, 'bypass_action_scheduling' ), 10, 6 );

		add_filter(
			'woocommerce_logs_deletion_scheduler_parameters',
			fn() =>
			array(
				'wait_seconds'       => 34,
				'max_queue_length'   => 5,
				'max_items_per_step' => 2,
			)
		);
		$this->sut = wc_get_container()->get( LogsDeletionScheduler::class );
		remove_all_filters( 'woocommerce_logs_deletion_scheduler_parameters' );
	}

	/**
	 * Runs after all the tests in the class.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		remove_all_actions( LogsDeletionScheduler::ACTION_HOOK_NAME );
	}

	/**
	 * Runs after each test in the class.
	 */
	public function tearDown(): void {
		parent::tearDown();

		self::$fake_logger->reset();
		remove_action( 'pre_as_schedule_single_action', array( $this, 'bypass_action_scheduling' ), 10, 6 );
		delete_option( LogsDeletionScheduler::SOURCES_LIST_OPTION_NAME );
	}

	/**
	 * Handler for the 'pre_as_schedule_single_action' hook.
	 * We use it to prevent the actual scheduling but flagging that it happened.
	 *
	 * @param int|null $pre_option The value to return instead of the option value.
	 * @param int      $timestamp When the action will run.
	 * @param string   $hook Action hook.
	 * @param array    $args Action arguments.
	 * @param string   $group Action group.
	 * @param int      $priority Action priority.
	 * @return int|null Action id if we are bypassing the actual scheduling, null otherwise.
	 */
	public function bypass_action_scheduling( $pre_option, $timestamp, $hook, $args, $group, $priority ) {
		if ( LogsDeletionScheduler::ACTION_HOOK_NAME === $hook && 'woocommerce' === $group ) {
			$this->action_has_been_scheduled = true;
			return 1;
		}

		return null;
	}

	/**
	 * @testdox Test for the operation of the fake logger.
	 */
	public function test_fake_logger() {
		self::$fake_logger->clear( 'foobar' );
		self::$fake_logger->clear( 'fizzbuzz', false );

		$this->assertEquals( array( 'foobar', 'fizzbuzz' ), self::$fake_logger->sources_cleared );

		self::$fake_logger->reset();
		$this->assertEmpty( self::$fake_logger->sources_cleared );
	}

	/**
	 * @testdox Test for the actual scheduling bypassing+flagging mechanism.
	 */
	public function test_bypass_action_scheduling() {
		$this->assert_action_has_not_been_scheduled();
		as_schedule_single_action( time() + YEAR_IN_SECONDS, LogsDeletionScheduler::ACTION_HOOK_NAME, null, 'woocommerce', true );
		$this->assert_action_has_been_scheduled();
		$this->assertFalse( as_next_scheduled_action( LogsDeletionScheduler::ACTION_HOOK_NAME, null, 'woocommerce' ) );
	}

	/**
	 * @testdox Log sources can be registered until the queue is full.
	 */
	public function test_register_sources_until_full() {
		$this->assert_action_has_not_been_scheduled();

		$this->assertTrue( $this->sut->register_source_pending_deletion( 'source1' ) );
		$this->assert_pending_sources_list( array( 'source1' ) );
		$this->assert_action_has_been_scheduled();

		$this->assertTrue( $this->sut->register_source_pending_deletion( 'source2' ) );
		$this->assert_pending_sources_list( array( 'source1', 'source2' ) );
		$this->assert_action_has_been_scheduled();

		$this->assertTrue( $this->sut->register_source_pending_deletion( 'source3' ) );
		$this->assertTrue( $this->sut->register_source_pending_deletion( 'source4' ) );
		$this->assertTrue( $this->sut->register_source_pending_deletion( 'source5' ) );
		$this->assert_action_has_been_scheduled();

		$this->assertFalse( $this->sut->register_source_pending_deletion( 'source6' ) );
		$this->assert_pending_sources_list( array( 'source1', 'source2', 'source3', 'source4', 'source5' ) );
		$this->assert_action_has_not_been_scheduled();

		$this->assertEmpty( self::$fake_logger->sources_cleared );
	}

	/**
	 * @testdox Logs are immediately deleted if the queue is full and $delete_if_cant_register is set to true.
	 */
	public function test_immediate_deletion_if_registration_fails() {
		$this->register_sources_until_full();
		$this->assert_action_has_been_scheduled();

		$this->assertTrue( $this->sut->register_source_pending_deletion( 'source6', true ) );
		$this->assert_action_has_not_been_scheduled();
		$this->assert_sources_cleared( array( 'source6' ) );
		$this->assert_pending_sources_list( array( 'source1', 'source2', 'source3', 'source4', 'source5' ) );
	}

	/**
	 * @testdox The scheduled action deletes logs for as many sources as configured, then reschedules itself if needed.
	 */
	public function test_scheduled_deletion_in_steps() {
		$this->register_sources_until_full();
		$this->assert_action_has_been_scheduled();

		$this->sut->handle_delete_logs_pending_deletion();
		$this->assert_sources_cleared( array( 'source1', 'source2' ) );
		$this->assert_pending_sources_list( array( 'source3', 'source4', 'source5' ) );
		$this->assert_action_has_been_scheduled();

		$this->sut->handle_delete_logs_pending_deletion();
		$this->assert_sources_cleared( array( 'source3', 'source4' ) );
		$this->assert_pending_sources_list( array( 'source5' ) );
		$this->assert_action_has_been_scheduled();

		$this->sut->handle_delete_logs_pending_deletion();
		$this->assert_sources_cleared( array( 'source5' ) );
		$this->assert_pending_sources_list( array() );
		$this->assert_action_has_not_been_scheduled();
	}

	/**
	 * @testdox Either immediate deletion or error returning happens when attempting to register a source if scheduling is disabled.
	 *
	 * @param int $wait_seconds Value to be returned for 'wait_seconds' in the parameters filter.
	 * @param int $max_queue_length Value to be returned for 'max_queue_length' in the parameters filter.
	 * @param int $max_items_per_step Value to be returned for 'max_items_per_step' in the parameters filter.
	 *
	 * @testWith [0, 1, 1]
	 *           [1, 0, 1]
	 *           [1, 1, 0]
	 */
	public function test_no_scheduling_if_disabled( $wait_seconds, $max_queue_length, $max_items_per_step ) {
		$this->reset_container_resolutions();

		add_filter(
			'woocommerce_logs_deletion_scheduler_parameters',
			fn() =>
			array(
				'wait_seconds'       => $wait_seconds,
				'max_queue_length'   => $max_queue_length,
				'max_items_per_step' => $max_items_per_step,
			)
		);
		$this->sut = wc_get_container()->get( LogsDeletionScheduler::class );
		remove_all_filters( 'woocommerce_logs_deletion_scheduler_parameters' );

		$this->assertFalse( $this->sut->register_source_pending_deletion( 'source1', false ) );
		$this->assert_pending_sources_list( array() );
		$this->assert_action_has_not_been_scheduled();
		$this->assert_sources_cleared( array() );

		$this->assertTrue( $this->sut->register_source_pending_deletion( 'source1', true ) );
		$this->assert_pending_sources_list( array() );
		$this->assert_action_has_not_been_scheduled();
		$this->assert_sources_cleared( array( 'source1' ) );
	}

	/**
	 * Auxiliary method to fill up the sources queue.
	 */
	private function register_sources_until_full() {
		$this->sut->register_source_pending_deletion( 'source1' );
		$this->sut->register_source_pending_deletion( 'source2' );
		$this->sut->register_source_pending_deletion( 'source3' );
		$this->sut->register_source_pending_deletion( 'source4' );
		$this->sut->register_source_pending_deletion( 'source5' );
	}

	/**
	 * Auxiliary method to assert that the action has not been scheduled.
	 */
	private function assert_action_has_not_been_scheduled() {
		$this->assertFalse( $this->action_has_been_scheduled );
	}

	/**
	 * Auxiliary method to assert that the action has been scheduled.
	 * It also resets the flag so that if nothing changes the next assertion will fail.
	 */
	private function assert_action_has_been_scheduled() {
		$this->assertTrue( $this->action_has_been_scheduled );
		$this->action_has_been_scheduled = false;
	}

	/**
	 * Auxiliary method to assert the contents of the queued sources list.
	 *
	 * @param array $expected_list List of sources to expect.
	 */
	private function assert_pending_sources_list( array $expected_list ) {
		if ( empty( $expected_list ) ) {
			$this->assertFalse( get_option( LogsDeletionScheduler::SOURCES_LIST_OPTION_NAME ) );
		} else {
			$this->assertEquals( $expected_list, get_option( LogsDeletionScheduler::SOURCES_LIST_OPTION_NAME ) );
		}
	}

	/**
	 * Auxiliary method to assert the contents of the cleared sources list.
	 *
	 * @param array $expected_list List of sources to expect.
	 */
	private function assert_sources_cleared( array $expected_list ) {
		$this->assertEquals( $expected_list, self::$fake_logger->sources_cleared );
		self::$fake_logger->reset();
	}
}
