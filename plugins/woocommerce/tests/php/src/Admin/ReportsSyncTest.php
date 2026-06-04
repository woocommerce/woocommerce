<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\ReportsSync;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use WC_Unit_Test_Case;

/**
 * Tests for the ReportsSync class.
 */
class ReportsSyncTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		ReportsSync::clear_queued_actions();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		ReportsSync::clear_queued_actions();
		delete_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION );
		parent::tearDown();
	}

	/**
	 * @testdox delete_report_data deletes the failed order imports option.
	 */
	public function test_delete_report_data_clears_failed_order_imports(): void {
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => array( 11 ),
				'overflow' => 3,
			),
			false
		);

		ReportsSync::delete_report_data();

		$this->assertFalse( get_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION ) );
	}

	/**
	 * @testdox regenerate_report_data resets the failed imports overflow counter but keeps stored IDs.
	 */
	public function test_regenerate_report_data_resets_overflow(): void {
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => array( 11 ),
				'overflow' => 3,
			),
			false
		);

		$result = ReportsSync::regenerate_report_data( false, false );

		$this->assertNotWPError( $result, 'Import guard unexpectedly fired; ensure no import is in progress.' );
		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array( 11 ), $failed['ids'] );
		$this->assertSame( 0, $failed['overflow'] );
	}

	/**
	 * @testdox regenerate_report_data keeps the overflow counter for a windowed import.
	 */
	public function test_regenerate_report_data_keeps_overflow_for_windowed_import(): void {
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => array( 11 ),
				'overflow' => 3,
			),
			false
		);

		$result = ReportsSync::regenerate_report_data( 30, false );

		$this->assertNotWPError( $result, 'Import guard unexpectedly fired; ensure no import is in progress.' );
		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array( 11 ), $failed['ids'], 'Stored failed IDs must survive a windowed import' );
		$this->assertSame( 3, $failed['overflow'] );
	}
}
