<?php
declare( strict_types=1 );
/**
 * Unit tests for WooCommerce uninstall routines.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WC_Uninstall_Test
 *
 * Static-analysis style tests for `plugins/woocommerce/uninstall.php`. The uninstall
 * script is a procedural file that aborts unless `WP_UNINSTALL_PLUGIN` is defined,
 * which makes it impractical to execute directly from PHPUnit. These tests instead
 * verify that the file contains the expected guards and bootstrap logic so that
 * Action Scheduler jobs are reliably cleared when WooCommerce is uninstalled —
 * including the scenario where WooCommerce is the only plugin using Action
 * Scheduler (so the library is not loaded by the time `uninstall.php` runs).
 */
class WC_Uninstall_Test extends WC_Unit_Test_Case {

	/**
	 * Absolute path to the uninstall.php file under test.
	 *
	 * @var string
	 */
	private $uninstall_file;

	/**
	 * Cached contents of uninstall.php.
	 *
	 * @var string
	 */
	private $uninstall_source;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->uninstall_file = dirname( __DIR__, 3 ) . '/uninstall.php';
		$this->assertFileExists( $this->uninstall_file, 'uninstall.php must exist in the plugin root' );

		// Local file read for static analysis of the uninstall script.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( $this->uninstall_file );
		$this->assertNotFalse( $source, 'uninstall.php must be readable' );
		$this->uninstall_source = (string) $source;
	}

	/**
	 * @testdox Should bootstrap Action Scheduler before invoking as_unschedule_all_actions.
	 */
	public function test_bootstraps_action_scheduler_before_cleanup(): void {
		$cleanup_position = strpos( $this->uninstall_source, 'as_unschedule_all_actions' );
		$this->assertNotFalse( $cleanup_position, 'uninstall.php must reference as_unschedule_all_actions' );

		$bootstrap_position = strpos( $this->uninstall_source, 'packages/action-scheduler/action-scheduler.php' );
		$this->assertNotFalse(
			$bootstrap_position,
			'uninstall.php must require the Action Scheduler bootstrap so jobs are cleared even when WC is the only plugin using AS'
		);

		$this->assertLessThan(
			$cleanup_position,
			$bootstrap_position,
			'Action Scheduler must be loaded before the scheduled-action cleanup runs'
		);
	}

	/**
	 * @testdox Should guard Action Scheduler bootstrap with a class_exists check to avoid double loading.
	 */
	public function test_action_scheduler_bootstrap_is_guarded(): void {
		$bootstrap_position = strpos( $this->uninstall_source, 'packages/action-scheduler/action-scheduler.php' );
		$this->assertNotFalse( $bootstrap_position, 'uninstall.php must reference the AS bootstrap path' );

		$preceding_window = substr( $this->uninstall_source, max( 0, $bootstrap_position - 200 ), 200 );

		$this->assertStringContainsString(
			"class_exists( 'ActionScheduler', false )",
			$preceding_window,
			'Action Scheduler bootstrap must be guarded by a class_exists check to avoid loading it twice'
		);
	}

	/**
	 * @testdox Should still guard the unschedule calls behind class_exists and is_initialized checks.
	 */
	public function test_unschedule_calls_remain_guarded(): void {
		$this->assertStringContainsString(
			'class_exists( ActionScheduler::class )',
			$this->uninstall_source,
			'Cleanup block must keep its class_exists guard to stay safe if the bootstrap could not be loaded'
		);

		$this->assertStringContainsString(
			'ActionScheduler::is_initialized()',
			$this->uninstall_source,
			'Cleanup block must keep its is_initialized guard'
		);

		$this->assertStringContainsString(
			"function_exists( 'as_unschedule_all_actions' )",
			$this->uninstall_source,
			'Cleanup block must keep its function_exists guard for as_unschedule_all_actions'
		);
	}

	/**
	 * @testdox Should unschedule every WooCommerce recurring action that uses Action Scheduler.
	 */
	public function test_unschedules_all_known_woocommerce_actions(): void {
		$expected_hooks = array(
			'woocommerce_scheduled_sales',
			'woocommerce_cancel_unpaid_orders',
			'woocommerce_cleanup_sessions',
			'woocommerce_cleanup_personal_data',
			'woocommerce_cleanup_logs',
			'woocommerce_geoip_updater',
			'woocommerce_tracker_send_event',
			'woocommerce_cleanup_rate_limits',
			'wc_admin_daily',
			'generate_category_lookup_table',
			'wc_admin_unsnooze_admin_notes',
		);

		foreach ( $expected_hooks as $hook ) {
			$this->assertStringContainsString(
				"as_unschedule_all_actions( '{$hook}' )",
				$this->uninstall_source,
				"uninstall.php must unschedule the {$hook} action"
			);
		}
	}
}
