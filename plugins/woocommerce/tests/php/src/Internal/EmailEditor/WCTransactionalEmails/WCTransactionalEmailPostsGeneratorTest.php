<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCTransactionalEmailPostsGenerator class.
 */
class WCTransactionalEmailPostsGeneratorTest extends \WC_Unit_Test_Case {
	/**
	 * @var WCTransactionalEmailPostsGenerator $email_generator
	 */
	private WCTransactionalEmailPostsGenerator $email_generator;

	/**
	 * @var WCTransactionalEmailPostsManager $template_manager
	 */
	private WCTransactionalEmailPostsManager $template_manager;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		$this->email_generator  = new WCTransactionalEmailPostsGenerator();
		$this->template_manager = WCTransactionalEmailPostsManager::get_instance();
	}

	/**
	 * Test that init sets up the transient.
	 */
	public function testInitSetsUpTransient(): void {
		delete_transient( 'wc_email_editor_initial_templates_generated' );

		$this->email_generator->initialize();

		$this->assertEquals( WOOCOMMERCE_VERSION, get_transient( 'wc_email_editor_initial_templates_generated' ) );
	}

	/**
	 * Test that init doesn't run if transient exists.
	 */
	public function testInitDoesNotRunIfTransientExists(): void {
		set_transient( 'wc_email_editor_initial_templates_generated', WOOCOMMERCE_VERSION, WEEK_IN_SECONDS );

		$result = $this->email_generator->initialize();

		$this->assertTrue( $result );
	}

	/**
	 * Test that get_email_template prioritizes template_block property.
	 */
	public function testGetEmailTemplatePrioritizesTemplateBlockProperty(): void {
		$email                 = $this->createMock( \WC_Email::class );
		$email->template_plain = 'emails/plain/customer-note.php';
		$email->template_block = 'emails/block/customer-processing-order.php';

		$template = $this->email_generator->get_email_template( $email );

		$this->assertStringContainsString( 'Thank you for your order', $template );
		$this->assertStringNotContainsString( 'A note has been added to your order', $template );
	}

	/**
	 * Test that get_email_template returns default template when custom template doesn't exist.
	 */
	public function testGetEmailTemplateReturnsDefaultTemplateWhenCustomTemplateDoesNotExist(): void {
		$email                 = $this->createMock( \WC_Email::class );
		$email->template_plain = 'emails/plain/test-email.php';

		$template = $this->email_generator->get_email_template( $email );

		$this->assertStringContainsString( 'Default block content', $template );
	}

	/**
	 * Test that get_email_template returns the correct template.
	 */
	public function testGetEmailTemplateReturnsTheCorrectTemplate(): void {
		$email                 = $this->createMock( \WC_Email::class );
		$email->template_plain = 'emails/plain/customer-note.php';

		$template = $this->email_generator->get_email_template( $email );

		$this->assertStringContainsString( 'A note has been added to your order', $template );
	}

	/**
	 * Test that generate_email_template_if_not_exists generates template.
	 */
	public function testGenerateEmailTemplateIfNotExistsGeneratesTemplate(): void {
		$email_type = 'customer_new_account';
		$email      = $this->createMock( \WC_Email::class );
		$email->id  = $email_type;

		$this->email_generator->init_default_transactional_emails();
		$this->template_manager->delete_email_template( $email_type );
		$post_id = $this->email_generator->generate_email_template_if_not_exists( $email_type );

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );
	}

	/**
	 * Test that generate_email_templates generates multiple templates.
	 */
	public function testGenerateEmailTemplatesGeneratesMultipleTemplates(): void {
		$templates_to_generate = array( 'customer_new_account', 'customer_completed_order' );

		$this->email_generator->init_default_transactional_emails();
		foreach ( $templates_to_generate as $email_type ) {
			// Delete the email template association if it exists.
			$this->template_manager->delete_email_template( $email_type );
		}
		$result = $this->email_generator->generate_email_templates( $templates_to_generate );

		$this->assertTrue( $result );
		foreach ( $templates_to_generate as $email_type ) {
			$this->assertNotFalse( get_option( 'woocommerce_email_templates_' . $email_type . '_post_id' ) );
		}
	}

	/**
	 * Test that generate_email_templates returns false when no templates are generated.
	 */
	public function testGenerateEmailTemplatesReturnsFalseWhenNoTemplatesAreGenerated(): void {
		$templates_to_generate = array( 'invalid_email_type' );

		$this->email_generator->init_default_transactional_emails();
		$result = $this->email_generator->generate_email_templates( $templates_to_generate );

		$this->assertFalse( $result );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
		delete_transient( 'wc_email_editor_initial_templates_generated' );
	}
}
