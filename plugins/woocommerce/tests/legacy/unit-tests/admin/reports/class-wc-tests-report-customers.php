<?php
/**
 * Class WC_Tests_Report_Customers file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

declare(strict_types=1);

/**
 * Tests for the WC_Report_Customers class.
 */
class WC_Tests_Report_Customers extends WC_Unit_Test_Case {

	/**
	 * Thousands separator to restore after a test overrides it.
	 *
	 * @var string|null
	 */
	private $original_thousands_sep = null;

	/**
	 * Load the necessary files, as they're not automatically loaded by WooCommerce.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-admin-report.php';
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-report-customers.php';
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		global $wp_locale;

		if ( null !== $this->original_thousands_sep ) {
			$wp_locale->number_format['thousands_sep'] = $this->original_thousands_sep;
			$this->original_thousands_sep              = null;
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should format the signup count with the locale thousands separator.
	 */
	public function test_get_chart_legend_formats_signups_with_locale_separator() {
		global $wp_locale;

		$this->original_thousands_sep              = $wp_locale->number_format['thousands_sep'];
		$wp_locale->number_format['thousands_sep'] = '.';

		$report                = new WC_Report_Customers();
		$report->customers     = array_fill( 0, 1500, 0 );
		$report->chart_colours = array( 'signups' => '#000000' );

		$legend = implode( ' ', wp_list_pluck( $report->get_chart_legend(), 'title' ) );

		$this->assertStringContainsString( '<strong>1.500</strong> signups in this period', $legend );
	}
}
