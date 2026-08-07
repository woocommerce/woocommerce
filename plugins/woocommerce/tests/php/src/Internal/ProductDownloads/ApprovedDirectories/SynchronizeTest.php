<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductDownloads\ApprovedDirectories;

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
	 * @var Synchronize
	 */
	private $sut;

	/**
	 * Create subject under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( Synchronize::class );
		WC_Admin_Notices::remove_notice( 'download_directories_sync_complete', true );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
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
	public function test_basic_synchronization_controls() {
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
	}

	/**
	 * @testdox Verify expected logging and clean-up take place during and following synchronization of download directories.
	 */
	public function test_sync_process() {
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
	public function test_cancellation_preserves_pending_review_notice() {
		WC_Admin_Notices::add_notice( 'download_directories_sync_complete', true );

		$this->sut->start();
		$this->sut->cancel();

		$this->assertTrue(
			WC_Admin_Notices::has_notice( 'download_directories_sync_complete' ),
			'Cancelling a later synchronization should not clear an existing pending review state.'
		);
	}
}
