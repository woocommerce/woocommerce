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
	 * @param string $date_range Optional. Date range the report covers, formatted for display.
	 * @return ReportCSVEmail
	 */
	private function create_email( string $date_range = '' ): ReportCSVEmail {
		$email = new ReportCSVEmail();
		$email->set_report_date_range( $date_range );

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

	/**
	 * @testdox The email says which period the report covers.
	 *
	 * @testWith ["get_content_html"]
	 *           ["get_content_plain"]
	 *
	 * @param string $method Method that renders the email body.
	 */
	public function test_email_states_the_date_range( string $method ): void {
		$content = $this->create_email( 'June 1, 2025 - June 30, 2025' )->$method();

		$this->assertStringContainsString(
			'Date range: June 1, 2025 - June 30, 2025',
			$content,
			'The email should say which period the report covers.'
		);
	}

	/**
	 * @testdox A report without a date range says nothing about one.
	 *
	 * @testWith ["get_content_html"]
	 *           ["get_content_plain"]
	 *
	 * @param string $method Method that renders the email body.
	 */
	public function test_email_omits_an_empty_date_range( string $method ): void {
		$content = $this->create_email()->$method();

		$this->assertStringNotContainsString(
			'Date range:',
			$content,
			'Reports that are not limited to a period should not mention a date range.'
		);
	}

	/**
	 * @testdox The subject says which period the report covers, so two exports of the same report can be told apart unopened.
	 */
	public function test_subject_states_the_date_range(): void {
		$subject = $this->trigger_and_get_subject( 'June 1, 2025 - June 30, 2025' );

		$this->assertStringContainsString(
			'Your Orders Report for June 1, 2025 - June 30, 2025 is ready',
			$subject,
			'The subject should name the period the report covers.'
		);
	}

	/**
	 * @testdox A report without a date range keeps the plain subject.
	 */
	public function test_subject_without_a_date_range(): void {
		$subject = $this->trigger_and_get_subject();

		$this->assertStringContainsString(
			'Your Orders Report download is ready',
			$subject,
			'Reports that are not limited to a period should keep the original subject.'
		);
	}

	/**
	 * @testdox trigger() keeps the parameters it shipped with, so a subclass overriding it still loads.
	 */
	public function test_trigger_keeps_its_original_parameters(): void {
		$parameters = ( new \ReflectionMethod( ReportCSVEmail::class, 'trigger' ) )->getParameters();

		$this->assertCount(
			3,
			$parameters,
			'trigger() is public and overridable. Adding a parameter, even an optional one, fatals every subclass that overrides the original three. Pass anything new through a setter instead.'
		);
	}

	/**
	 * Send the email to a user and return the subject it went out with.
	 *
	 * @param string $date_range Optional. Date range the report covers, formatted for display.
	 * @return string
	 */
	private function trigger_and_get_subject( string $date_range = '' ): string {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$mailer  = tests_retrieve_phpmailer_instance();
		$email   = new ReportCSVEmail();

		$email->set_report_date_range( $date_range );
		$email->trigger(
			$user_id,
			'orders',
			'https://example.org/?action=woocommerce_admin_download_report_csv&filename=wc-orders-report-export'
		);

		$sent = end( $mailer->mock_sent );

		return $sent ? $sent['subject'] : '';
	}

	/**
	 * @testdox A failed export is emailed as a failure, with no download link, in HTML and plain text.
	 */
	public function test_failed_export_email_says_so(): void {
		reset_phpmailer_instance();
		$mailer = tests_retrieve_phpmailer_instance();

		( new ReportCSVEmail() )->trigger_failed( 1, 'orders' );

		$this->assertCount( 1, $mailer->mock_sent, 'A failed export should send exactly one email.' );
		$sent = $mailer->mock_sent[0];

		$this->assertSame( get_userdata( 1 )->user_email, $sent['to'][0][0], 'The email should go to the user who requested the export.' );
		$this->assertStringContainsString( 'Your Orders Report export did not complete', $sent['subject'], 'The subject should name the report and say it failed.' );
		$this->assertStringContainsString( 'Your Orders Report could not be exported', $sent['body'], 'The HTML body should say the export failed.' );
		$this->assertStringNotContainsString( 'Download your', $sent['body'], 'A failed export has no link to download.' );

		$email = new ReportCSVEmail();
		$email->trigger_failed( 1, 'orders' );
		$plain = $email->get_content_plain();

		$this->assertStringContainsString( 'Your Orders Report could not be exported', $plain, 'The plain text body should say the export failed.' );
		$this->assertStringNotContainsString( 'Download your', $plain, 'A failed export has no link to download.' );
	}

	/**
	 * @testdox A failed export email says which period the report covered, when it had one.
	 */
	public function test_failed_export_email_states_the_date_range(): void {
		reset_phpmailer_instance();
		$mailer = tests_retrieve_phpmailer_instance();

		$email = new ReportCSVEmail();
		$email->set_report_date_range( 'June 1, 2025 - June 30, 2025' );
		$email->trigger_failed( 1, 'orders' );

		$sent = end( $mailer->mock_sent );

		$this->assertIsArray( $sent, 'A failed export should be emailed.' );
		$this->assertStringContainsString(
			'Your Orders Report export for June 1, 2025 - June 30, 2025 did not complete',
			$sent['subject'],
			'The subject should name the period the failed export covered.'
		);
		$this->assertStringContainsString( 'Date range: June 1, 2025 - June 30, 2025', $sent['body'], 'The HTML body should name the period the failed export covered.' );
		$this->assertStringContainsString( 'Date range: June 1, 2025 - June 30, 2025', $email->get_content_plain(), 'The plain text body should name the period the failed export covered.' );
	}
}
