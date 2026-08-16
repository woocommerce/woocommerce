<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;

/**
 * Tests for the WCTransactionalEmails class.
 */
class WCTransactionalEmailsTest extends \WC_Unit_Test_Case {
	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
	}

	/**
	 * Test that get_transactional_emails returns the core transactional emails.
	 */
	public function testGetTransactionalEmailsReturnsDefaultEmails(): void {
		$emails = WCTransactionalEmails::get_transactional_emails();

		$this->assertIsArray( $emails );
		$this->assertContains( 'customer_new_account', $emails );
		$this->assertContains( 'customer_verify_email', $emails );
		$this->assertContains( 'customer_completed_order', $emails );
		$this->assertContains( 'customer_processing_order', $emails );
		$this->assertContains( 'customer_pos_completed_order', $emails );
		$this->assertContains( 'customer_pos_refunded_order', $emails );
	}

	/**
	 * Test that get_transactional_emails can be filtered.
	 */
	public function testGetTransactionalEmailsCanBeFiltered(): void {
		add_filter(
			'woocommerce_transactional_emails_for_block_editor',
			function ( $emails ) {
				$emails[] = 'custom_email';
				return $emails;
			}
		);

		$emails = WCTransactionalEmails::get_transactional_emails();

		$this->assertContains( 'custom_email', $emails );
	}

	/**
	 * Test that get_core_transactional_emails returns the unfiltered core list.
	 */
	public function testGetCoreTransactionalEmailsIgnoresBlockEditorFilter(): void {
		add_filter(
			'woocommerce_transactional_emails_for_block_editor',
			function ( $emails ) {
				$emails[] = 'custom_email';
				return $emails;
			}
		);

		$emails = WCTransactionalEmails::get_core_transactional_emails();

		$this->assertIsArray( $emails );
		$this->assertContains( 'customer_new_account', $emails );
		$this->assertNotContains( 'custom_email', $emails );
	}

	/**
	 * @testdox Deprecated init_email_templates() is a no-op that triggers a deprecation notice.
	 */
	public function testDeprecatedInitEmailTemplatesIsNoop(): void {
		$this->setExpectedDeprecated( WCTransactionalEmails::class . '::init_email_templates' );

		( new WCTransactionalEmails() )->init_email_templates();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
	}
}
