<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use WC_Unit_Test_Case;

class StatusReportBannerTest extends WC_Unit_Test_Case {

	public function test_status_report_view_renders_site_health_banner() {
		$view_file = WC_ABSPATH . 'includes/admin/views/html-admin-page-status-report.php';
		$this->assertFileExists( $view_file );

		$source = file_get_contents( $view_file );

		$this->assertStringContainsString( 'site-health.php', $source );
		$this->assertStringContainsString( 'WordPress Site Health', $source );
	}
}
