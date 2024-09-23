<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductDownloads\ApprovedDirectories;

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Synchronize;
use Automattic\WooCommerce\Proxies\LegacyProxy;
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
	}

	/**
	 * @testdox Ensure basic controls to start and stop synchronization behave as expected.
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

		$this->assertFalse(
			$this->sut->start(),
			'Synchronization process can be cancelled before it completes.'
		);

		$this->sut->stop();
		$this->assertNull(
			wc_get_container()->get( LegacyProxy::class )->get_instance_of( WC_Queue_Interface::class )->get_next( Synchronize::SYNC_TASK ),
			'Once synchronization has been cancelled, any related scheduled actions will also have been cleaned up.'
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
	}
}
