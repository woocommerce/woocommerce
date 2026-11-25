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
use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\EmailEditor\Package;
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
	 * @testdox The Stock notifications report export names its report in the subject.
	 */
	public function test_subject_names_the_stock_notifications_report(): void {
		$subject = $this->trigger_and_get_subject( '', 'stock-notifications' );

		$this->assertStringContainsString( 'Your Stock notifications Report download is ready', $subject );
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
	 * @param string $date_range  Optional. Date range the report covers, formatted for display.
	 * @param string $report_type Optional. Report type being exported.
	 * @return string
	 */
	private function trigger_and_get_subject( string $date_range = '', string $report_type = 'orders' ): string {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$mailer  = tests_retrieve_phpmailer_instance();
		$email   = new ReportCSVEmail();

		$email->set_report_date_range( $date_range );
		$email->trigger(
			$user_id,
			$report_type,
			"https://example.org/?action=woocommerce_admin_download_report_csv&filename=wc-{$report_type}-report-export"
		);

		$sent = end( $mailer->mock_sent );

		return $sent ? $sent['subject'] : '';
	}

	/**
	 * @testdox The email is listed with the other emails only when the block email editor is on.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $block_editor_enabled Value of the block email editor feature option.
	 * @param bool   $expected_listed      Whether the email should be listed.
	 */
	public function test_email_is_listed_only_with_the_block_editor( string $block_editor_enabled, bool $expected_listed ): void {
		update_option( 'woocommerce_feature_block_email_editor_enabled', $block_editor_enabled );

		$emails = new \WC_Emails();

		$this->assertSame( $expected_listed, isset( $emails->emails['WC_Email_Admin_Report_Export_Download'] ) );
	}

	/**
	 * @testdox With the block email editor on, the email is sent in the block design with the download link, date range and retention period.
	 */
	public function test_email_is_sent_in_block_design(): void {
		$this->enable_block_email_editor();

		$content = $this->create_email( 'June 1, 2026 - June 30, 2026' )->get_content();

		$this->assertStringContainsString( 'Your Report Download', $content );
		$this->assertStringContainsString( 'Download your Orders Report', $content );
		$this->assertStringContainsString( 'filename=wc-orders-report-export', $content );
		$this->assertStringContainsString( 'Date range: June 1, 2026 - June 30, 2026', $content );
		$this->assertStringContainsString( 'This link is available for ' . human_time_diff( 0, ReportExporter::EXPORT_RETENTION_PERIOD ) . '.', $content );
		$this->assertStringContainsString( '. All Rights Reserved.', $content, 'Only the block email template renders this footer.' );
	}

	/**
	 * @testdox The email is always sent, even with an "off" value saved, because emailed exports have no other way to reach the merchant.
	 * @testWith ["yes"]
	 *           ["no"]
	 *
	 * @param string $block_editor_enabled Value of the block email editor feature option.
	 */
	public function test_email_is_always_sent( string $block_editor_enabled ): void {
		update_option( 'woocommerce_feature_block_email_editor_enabled', $block_editor_enabled );
		update_option( 'woocommerce_admin_report_export_download_settings', array( 'enabled' => 'no' ) );
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$mailer  = tests_retrieve_phpmailer_instance();
		$before  = count( $mailer->mock_sent );

		( new ReportCSVEmail() )->trigger( $user_id, 'orders', 'https://example.org/?action=woocommerce_admin_download_report_csv&filename=wc-orders-report-export' );

		$this->assertSame( 1, count( $mailer->mock_sent ) - $before );
	}

	/**
	 * @testdox The email reports itself enabled, and extensions can still change that through the woocommerce_email_enabled_ filter.
	 */
	public function test_is_enabled_runs_the_email_enabled_filter(): void {
		$email = new ReportCSVEmail();
		$this->assertTrue( $email->is_enabled() );

		add_filter( 'woocommerce_email_enabled_admin_report_export_download', '__return_false' );
		$enabled = $email->is_enabled();
		remove_filter( 'woocommerce_email_enabled_admin_report_export_download', '__return_false' );

		$this->assertFalse( $enabled );
	}

	/**
	 * @testdox The email preview shows a sample Orders report instead of an empty report name.
	 */
	public function test_preview_shows_a_sample_report(): void {
		$this->enable_block_email_editor();
		$this->reset_mailer();

		try {
			$preview = new EmailPreview();
			$preview->set_email_type( ReportCSVEmail::class );

			$this->assertStringNotContainsString( '{report_name}', $preview->get_subject() );
			$this->assertStringContainsString( 'Download your Orders Report', $preview->get_email()->get_block_editor_email_template_content() );
		} finally {
			$this->reset_mailer();
		}
	}

	/**
	 * Turn on the block email editor and set it up, or skip when the environment can't run it.
	 */
	private function enable_block_email_editor(): void {
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();
		if ( ! Email_Editor_Container::container()->get( Dependency_Check::class )->are_dependencies_met() ) {
			$this->markTestSkipped( 'The test environment does not meet the block email editor requirements.' );
		}
	}

	/**
	 * Reset the WC_Emails singleton, so the next WC()->mailer() call builds its list of emails again.
	 */
	private function reset_mailer(): void {
		$instance = new \ReflectionProperty( \WC_Emails::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}
}
