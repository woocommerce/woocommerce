<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductDownloads\ApprovedDirectories;

use ActionScheduler_Store;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Synchronize;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Admin_Notices;
use WC_Queue_Interface;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Downloads Allowed Directories synchronization utility.
 */
class SynchronizeTest extends WC_Unit_Test_Case {
	/**
	 * Option used to mark a synchronization as cancelled.
	 */
	private const SYNC_TASK_CANCELLED = 'wc_product_download_dir_sync_cancelled';

	/**
	 * @var Synchronize
	 */
	private $sut;

	/**
	 * Create subject under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( Synchronize::class );
		delete_option( self::SYNC_TASK_CANCELLED );
		WC_Admin_Notices::remove_notice( 'download_directories_sync_complete', true );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		$this->sut->cancel();
		delete_option( self::SYNC_TASK_CANCELLED );
		WC_Admin_Notices::remove_notice( 'download_directories_sync_complete', true );
		parent::tearDown();
	}

	/**
	 * Clean up after all tests have run.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		wc_get_container()->get( \Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register::class )->delete_all();
	}

	/**
	 * @testdox Ensure basic controls to start and cancel synchronization behave as expected.
	 */
	public function test_basic_synchronization_controls(): void {
		$this->sut->start();
		$this->assertTrue(
			$this->sut->in_progress(),
			'We can successfully start synchronizing and verify it is in progress.'
		);

		$this->assertFalse(
			$this->sut->start(),
			'If a download directory synchronization process is already in progress, additional concurrent sync processes cannot be created.'
		);

		$this->sut->cancel();
		$this->assertFalse(
			$this->sut->in_progress(),
			'Synchronization can be cancelled before it completes.'
		);
		$this->assertNull(
			wc_get_container()->get( LegacyProxy::class )->get_instance_of( WC_Queue_Interface::class )->get_next( Synchronize::SYNC_TASK ),
			'Once synchronization has been cancelled, any related scheduled actions will also have been cleaned up.'
		);
		$this->assertFalse(
			WC_Admin_Notices::has_notice( 'download_directories_sync_complete' ),
			'Cancelling synchronization should not create a completed synchronization review notice.'
		);

		$this->assertTrue( $this->sut->start(), 'A new synchronization should be able to start after cancellation.' );
		$this->sut->run();
		$this->assertTrue(
			WC_Admin_Notices::has_notice( 'download_directories_sync_complete' ),
			'A new synchronization should complete normally after a previous synchronization was cancelled.'
		);
	}

	/**
	 * @testdox Scheduled task detection uses scalar queue statuses supported by the queue interface.
	 */
	public function test_scheduled_task_detection_uses_scalar_statuses(): void {
		$searched_statuses = array();
		$queue             = $this->createMock( WC_Queue_Interface::class );
		$queue->method( 'search' )
			->willReturnCallback(
				function ( array $args, string $return_format ) use ( &$searched_statuses ): array {
					$searched_statuses[] = $args['status'];
					$this->assertSame( 'ids', $return_format );

					return ActionScheduler_Store::STATUS_RUNNING === $args['status'] ? array( 123 ) : array();
				}
			);

		$sut            = new Synchronize();
		$queue_property = new \ReflectionProperty( $sut, 'queue' );
		$queue_property->setAccessible( true );
		$queue_property->setValue( $sut, $queue );

		$this->assertFalse( $sut->start(), 'A running synchronization task should prevent another synchronization from starting.' );
		$this->assertSame(
			array( ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING ),
			$searched_statuses,
			'Scheduled task detection should query each status as a scalar value.'
		);
	}

	/**
	 * @testdox Verify expected logging and clean-up take place during and following synchronization of download directories.
	 */
	public function test_sync_process(): void {
		$logged_messages = array();

		$log_watcher = function ( string $logged_message ) use ( &$logged_messages ) {
			$logged_messages[] = $logged_message;
		};

		add_filter( 'woocommerce_logger_log_message', $log_watcher );

		$this->sut->start();
		$this->sut->run();

		remove_filter( 'woocommerce_logger_log_message', $log_watcher );

		$this->assertTrue(
			! get_option( Synchronize::SYNC_TASK_PAGE ) && ! get_option( Synchronize::SYNC_TASK_PROGRESS ),
			'Once synchronization has completed, any temporary options used to hold state will have been deleted.'
		);

		$this->assertContains(
			'Approved Download Directories sync: scan is complete!',
			$logged_messages,
			'We expect that completion of the synchronization process will have been recorded in the log.'
		);

		$this->assertTrue(
			WC_Admin_Notices::has_notice( 'download_directories_sync_complete' ),
			'Completed synchronization should create a persistent review notice.'
		);
	}

	/**
	 * @testdox Cancelling synchronization preserves a review notice that was already pending.
	 */
	public function test_cancellation_preserves_pending_review_notice(): void {
		WC_Admin_Notices::add_notice( 'download_directories_sync_complete', true );

		$this->sut->start();
		$this->sut->cancel();

		$this->assertTrue(
			WC_Admin_Notices::has_notice( 'download_directories_sync_complete' ),
			'Cancelling a later synchronization should not clear an existing pending review state.'
		);
	}

	/**
	 * @testdox Cancelling an active batch does not mark synchronization as complete.
	 */
	public function test_cancellation_during_active_batch_does_not_mark_sync_complete(): void {
		$cancelled           = false;
		$restart_result      = null;
		$cancel_during_batch = function () use ( &$cancelled, &$restart_result ): void {
			if ( ! $cancelled ) {
				$cancelled = true;
				$this->sut->cancel();
				$restart_result = $this->sut->start();
			}
		};

		$this->sut->start();
		$store           = ActionScheduler_Store::instance();
		$claim           = $store->stake_claim( 1, null, array( Synchronize::SYNC_TASK ) );
		$claimed_actions = $claim->get_actions();
		$this->assertCount( 1, $claimed_actions, 'The synchronization action should be marked as running for the test.' );
		$store->log_execution( $claimed_actions[0] );
		add_action( 'update_option_' . Synchronize::SYNC_TASK_PAGE, $cancel_during_batch, 10, 0 );

		try {
			$this->sut->run();
		} finally {
			remove_action( 'update_option_' . Synchronize::SYNC_TASK_PAGE, $cancel_during_batch, 10 );
			$store->mark_complete( $claimed_actions[0] );
			$store->release_claim( $claim );
		}

		$this->assertTrue( $cancelled, 'The test should cancel synchronization while a batch is active.' );
		$this->assertFalse( $restart_result, 'A new synchronization should not start before the cancelled active batch returns.' );
		$this->assertFalse( $this->sut->in_progress(), 'An active batch should clean up synchronization state after cancellation.' );
		$this->assertFalse(
			WC_Admin_Notices::has_notice( 'download_directories_sync_complete' ),
			'An active batch should not create a completion notice after cancellation.'
		);
		$this->assertNull(
			wc_get_container()->get( LegacyProxy::class )->get_instance_of( WC_Queue_Interface::class )->get_next( Synchronize::SYNC_TASK ),
			'An active batch should not schedule more synchronization work after cancellation.'
		);
		$this->assertTrue( $this->sut->start(), 'A new synchronization should start after the cancelled active batch has returned.' );
	}
}
