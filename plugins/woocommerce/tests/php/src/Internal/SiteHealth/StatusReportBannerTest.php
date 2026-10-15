<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use WC_Unit_Test_Case;

/**
 * Verifies the System Report view includes the link banner pointing users to WordPress Site Health.
 */
class StatusReportBannerTest extends WC_Unit_Test_Case {

	/** Verifies the System Report view source includes the Site Health link banner. */
	public function test_status_report_view_renders_site_health_banner() {
		$view_file = WC_ABSPATH . 'includes/admin/views/html-admin-page-status-report.php';
		$this->assertFileExists( $view_file );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local rendered template output for assertion.
		$source = file_get_contents( $view_file );

		$this->assertStringContainsString( 'site-health.php', $source );
		$this->assertStringContainsString( 'WordPress Site Health', $source );
	}
}
