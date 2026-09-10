<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Internal\Admin\Analytics;
use Automattic\WooCommerce\Internal\Admin\Notes\RefundDoubleCountToolNotice;
use WC_Unit_Test_Case;

/**
 * Tests for the RefundDoubleCountToolNotice class.
 */
class RefundDoubleCountToolNoticeTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );
		update_option( \WC_Install::INITIAL_INSTALLED_VERSION, '10.5.0' );
	}

	/**
	 * @testdox Applies to stores installed before 11.1.0 that have not run the tool yet.
	 */
	public function test_is_applicable_before_the_tool_has_run(): void {
		$this->assertTrue( RefundDoubleCountToolNotice::is_applicable() );
		$this->assertInstanceOf( Note::class, RefundDoubleCountToolNotice::get_note() );
	}

	/**
	 * @testdox Does not apply once a run has started, or when the tool does not apply to the store.
	 * @testWith ["run started"]
	 *           ["installed on 11.1.0"]
	 *           ["old refund data"]
	 *           ["analytics disabled"]
	 *
	 * @param string $scenario Scenario name.
	 */
	public function test_is_not_applicable( string $scenario ): void {
		switch ( $scenario ) {
			case 'run started':
				update_option( Analytics::REFUND_DOUBLE_COUNT_OPTION, array( 'status' => 'running' ) );
				break;
			case 'installed on 11.1.0':
				update_option( \WC_Install::INITIAL_INSTALLED_VERSION, '11.1.0' );
				break;
			case 'old refund data':
				update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );
				break;
			case 'analytics disabled':
				update_option( 'woocommerce_analytics_enabled', 'no' );
				break;
		}

		$this->assertFalse( RefundDoubleCountToolNotice::is_applicable() );
		$this->assertNull( RefundDoubleCountToolNotice::get_note() );
	}

	/**
	 * @testdox Starting a run from the tool removes the note.
	 */
	public function test_starting_a_run_deletes_the_note(): void {
		RefundDoubleCountToolNotice::possibly_add_note();
		$this->assertTrue( RefundDoubleCountToolNotice::note_exists(), 'The note should be added first' );

		Analytics::get_instance()->run_refund_double_count_tool();
		as_unschedule_all_actions( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK );

		$this->assertFalse( RefundDoubleCountToolNotice::note_exists() );
	}
}
