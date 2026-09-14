<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\SystemStatusReport;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
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

	/**
	 * @testdox Should warn only when both order metadata thresholds are exceeded.
	 * @testWith [44, 49800000, true, "1,131,818.18"]
	 *           [99, 100000, true, "1,010.10"]
	 *           [100, 100000, false, "1,000.00"]
	 *           [1, 99999, false, "99,999.00"]
	 *           [1000000, 20000000, false, "20.00"]
	 *           [1, 0, false, "0.00"]
	 *
	 * @param int    $orders Estimated order rows.
	 * @param int    $meta Estimated metadata rows.
	 * @param bool   $warning Whether to expect a warning.
	 * @param string $ratio Expected formatted ratio.
	 */
	public function test_render_order_meta_health( int $orders, int $meta, bool $warning, string $ratio ): void {
		global $wpdb;

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$database = array(
			'database_tables' => array(
				'woocommerce' => array(
					$wpdb->prefix . 'wc_orders'      => array( 'rows' => $orders ),
					$wpdb->prefix . 'wc_orders_meta' => array(
						'rows'  => $meta,
						'data'  => '800.00',
						'index' => '52.00',
					),
				),
				'other'       => array(
					'other_site_wc_orders'      => array( 'rows' => 1 ),
					'other_site_wc_orders_meta' => array( 'rows' => 100000000 ),
				),
			),
		);

		ob_start();
		$this->sut->render_order_meta_health( $database );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-export-label="HPOS order metadata"', $output );
		$this->assertStringContainsString( number_format_i18n( $meta ) . ' metadata rows', $output );
		$this->assertStringContainsString( number_format_i18n( $orders ) . ' order rows', $output );
		$this->assertStringContainsString( $ratio . ' metadata rows per order', $output );
		$this->assertStringContainsString( '852.00 MB', $output );
		if ( $warning ) {
			$this->assertStringContainsString( 'Unusually high order metadata volume', $output );
		} else {
			$this->assertStringNotContainsString( 'Unusually high order metadata volume', $output );
		}
	}

	/**
	 * @testdox Should report unavailable or zero order estimates without dividing by zero or declaring the table healthy.
	 * @testWith [null, null]
	 *           [10, null]
	 *           [null, 100000]
	 *           [0, 100000]
	 *           [0, 0]
	 *
	 * @param int|null $orders Estimated order rows.
	 * @param int|null $meta Estimated metadata rows.
	 */
	public function test_render_order_meta_health_without_estimates( ?int $orders, ?int $meta ): void {
		global $wpdb;

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$database = array(
			'database_tables' => array(
				'other' => array(
					$wpdb->prefix . 'wc_orders'      => array( 'rows' => $orders ),
					$wpdb->prefix . 'wc_orders_meta' => array( 'rows' => $meta ),
				),
			),
		);

		ob_start();
		$this->sut->render_order_meta_health( $database );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Unable to check', $output );
		$this->assertStringNotContainsString( 'Unusually high', $output );
	}

	/**
	 * @testdox Should report missing table statistics only when HPOS is authoritative.
	 * @testWith ["yes", "Unable to check"]
	 *           ["no", ""]
	 *
	 * @param string $hpos_enabled Whether HPOS is authoritative.
	 * @param string $expected Expected output text.
	 */
	public function test_render_order_meta_health_without_tables( string $hpos_enabled, string $expected ): void {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $hpos_enabled );

		ob_start();
		$this->sut->render_order_meta_health( array() );
		$output = ob_get_clean();

		if ( '' === $expected ) {
			$this->assertSame( '', $output );
		} else {
			$this->assertStringContainsString( $expected, $output );
		}
	}
}
