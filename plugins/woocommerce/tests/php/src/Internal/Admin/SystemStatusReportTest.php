<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\SystemStatusReport;
use WC_Unit_Test_Case;

/**
 * Tests for the SystemStatusReport class.
 */
class SystemStatusReportTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var SystemStatusReport
	 */
	private SystemStatusReport $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = SystemStatusReport::get_instance();
		as_unschedule_all_actions( 'wc_admin_daily_wrapper' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( 'wc_admin_daily_wrapper' );
		parent::tearDown();
	}

	/**
	 * Test render_daily_cron across scheduled and not-scheduled Action Scheduler states.
	 *
	 * @testWith ["scheduled"]
	 *           ["not-scheduled"]
	 *
	 * @param string $scenario Either 'scheduled' or 'not-scheduled'.
	 */
	public function test_render_daily_cron( string $scenario ): void {
		$expected_date = '';

		if ( 'scheduled' === $scenario ) {
			$timestamp     = time() + DAY_IN_SECONDS;
			$expected_date = esc_html( date_i18n( 'Y-m-d H:i:s P', $timestamp ) );

			as_schedule_recurring_action( $timestamp, DAY_IN_SECONDS, 'wc_admin_daily_wrapper', array(), 'woocommerce', true );
		}

		ob_start();
		$this->sut->render_daily_cron();
		$output = ob_get_clean();

		if ( 'scheduled' === $scenario ) {
			$this->assertStringContainsString( 'Next scheduled:', $output );
			$this->assertStringContainsString( $expected_date, $output );
			$this->assertStringNotContainsString( 'Not scheduled', $output );
		} else {
			$this->assertStringContainsString( 'Not scheduled', $output );
			$this->assertStringNotContainsString( 'Next scheduled:', $output );
		}
	}
}
