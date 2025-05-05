<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;

/**
 * Tests for the WCTransactionalEmails class.
 */
class WCTransactionalEmailsTest extends \WC_Unit_Test_Case {
	/**
	 * @var WCTransactionalEmails $transactional_emails
	 */
	private WCTransactionalEmails $transactional_emails;

	/**
	 * @var WCTransactionalEmailPostsGenerator|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_email_generator;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );

		// Create a mock for the email generator.
		$this->mock_email_generator = $this->getMockBuilder( WCTransactionalEmailPostsGenerator::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'initialize' ) )
			->getMock();

		// Create a reflection of the WCTransactionalEmails class.
		$reflection = new \ReflectionClass( WCTransactionalEmails::class );

		// Create an instance and set the mocked generator.
		$this->transactional_emails = new WCTransactionalEmails();
		$property                   = $reflection->getProperty( 'email_template_generator' );
		$property->setAccessible( true );
		$property->setValue( $this->transactional_emails, $this->mock_email_generator );
	}

	/**
	 * Test that get_transactional_emails returns the core transactional emails.
	 */
	public function testGetTransactionalEmailsReturnsDefaultEmails(): void {
		$emails = WCTransactionalEmails::get_transactional_emails();

		$this->assertIsArray( $emails );
		$this->assertContains( 'customer_new_account', $emails );
		$this->assertContains( 'customer_completed_order', $emails );
		$this->assertContains( 'customer_processing_order', $emails );
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
	 * Test that init_email_templates is not called on non-WooCommerce admin pages.
	 */
	public function testInitEmailTemplatesNotCalledOnNonWooCommercePages(): void {
		set_current_screen( 'front' );

		// Set expectation that initialize should not be called.
		$this->mock_email_generator->expects( $this->never() )
			->method( 'initialize' );

		$this->transactional_emails->init_email_templates();
	}

	/**
	 * Test that init_email_templates is called on WooCommerce admin pages.
	 */
	public function testInitEmailTemplatesCalledOnWooCommercePages(): void {
		set_current_screen( 'woocommerce_page_wc-admin' );

		// Set expectation that initialize should be called exactly once.
		$this->mock_email_generator->expects( $this->once() )
			->method( 'initialize' );

		$this->transactional_emails->init_email_templates();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
	}
}
