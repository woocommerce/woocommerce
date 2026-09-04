<?php
/**
 * Tests for the Analytics report export email.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\ReportCSVEmail;
use Automattic\WooCommerce\Admin\ReportExporter;
use WC_Unit_Test_Case;

/**
 * Tests for the emailed report download link.
 */
class ReportCSVEmailTest extends WC_Unit_Test_Case {

	/**
	 * Build an email addressed at a generated export.
	 *
	 * @return ReportCSVEmail
	 */
	private function create_email(): ReportCSVEmail {
		$email = new ReportCSVEmail();

		foreach ( array(
			'report_type'  => 'Orders',
			'download_url' => 'https://example.org/?action=woocommerce_admin_download_report_csv&filename=wc-orders-report-export',
		) as $name => $value ) {
			$property = new \ReflectionProperty( ReportCSVEmail::class, $name );
			$property->setAccessible( true );
			$property->setValue( $email, $value );
		}

		return $email;
	}

	/**
	 * @testdox The HTML email says how long the download link stays valid.
	 */
	public function test_html_email_states_the_retention_period(): void {
		$content = $this->create_email()->get_content_html();

		$this->assertStringContainsString(
			'This link is available for ' . human_time_diff( 0, ReportExporter::EXPORT_RETENTION_PERIOD ) . '.',
			$content,
			'The HTML email should tell the merchant how long the link lasts.'
		);
	}

	/**
	 * @testdox The plain text email says how long the download link stays valid.
	 */
	public function test_plain_email_states_the_retention_period(): void {
		$content = $this->create_email()->get_content_plain();

		$this->assertStringContainsString(
			'This link is available for ' . human_time_diff( 0, ReportExporter::EXPORT_RETENTION_PERIOD ) . '.',
			$content,
			'The plain text email should tell the merchant how long the link lasts.'
		);
	}
}
