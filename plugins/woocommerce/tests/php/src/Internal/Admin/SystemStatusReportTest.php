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
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// Clean up scheduled actions and cron events.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'wc_admin_daily_wrapper' );
		}
		wp_clear_scheduled_hook( 'wc_admin_daily' );
		parent::tearDown();
	}

	/**
	 * Test render_daily_cron when scheduled via Action Scheduler.
	 */
	public function test_render_daily_cron_scheduled_via_action_scheduler(): void {
		$next_run = time() + DAY_IN_SECONDS;

		// Schedule the wrapper action.
		if ( function_exists( 'as_schedule_recurring_action' ) ) {
			as_schedule_recurring_action( $next_run, DAY_IN_SECONDS, 'wc_admin_daily_wrapper', array(), 'woocommerce', true );
		}

		ob_start();
		$this->sut->render_daily_cron();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Next scheduled:', $output );
		$this->assertStringContainsString( '<mark class="yes">', $output );
		$this->assertStringNotContainsString( 'Not scheduled', $output );
	}

	/**
	 * Test render_daily_cron when not scheduled.
	 */
	public function test_render_daily_cron_not_scheduled(): void {
		// Ensure no actions are scheduled.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'wc_admin_daily_wrapper' );
		}
		wp_clear_scheduled_hook( 'wc_admin_daily' );

		ob_start();
		$this->sut->render_daily_cron();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Not scheduled', $output );
		$this->assertStringContainsString( '<mark class="error">', $output );
		$this->assertStringNotContainsString( 'Next scheduled:', $output );
	}

	/**
	 * Test render_daily_cron fallback to legacy WP-Cron.
	 */
	public function test_render_daily_cron_legacy_fallback(): void {
		$next_run = time() + DAY_IN_SECONDS;

		// Ensure no AS action scheduled.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'wc_admin_daily_wrapper' );
		}

		// Schedule legacy WP-Cron event.
		wp_schedule_event( $next_run, 'daily', 'wc_admin_daily' );

		ob_start();
		$this->sut->render_daily_cron();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Next scheduled:', $output );
		$this->assertStringContainsString( '<mark class="yes">', $output );
		$this->assertStringNotContainsString( 'Not scheduled', $output );

		// Cleanup.
		wp_clear_scheduled_hook( 'wc_admin_daily' );
	}
}
