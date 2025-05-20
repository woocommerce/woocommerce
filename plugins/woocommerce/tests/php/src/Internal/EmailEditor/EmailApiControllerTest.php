<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\Internal\EmailEditor\EmailApiController;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the EmailApiController class.
 */
class EmailApiControllerTest extends \WC_Unit_Test_Case {
	/**
	 * @var EmailApiController
	 */
	private EmailApiController $email_api_controller;

	/**
	 * @var \WP_Post
	 */
	private \WP_Post $email_post;

	/**
	 * @var string
	 */
	private string $email_type = 'test_email';

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		// Create a test email post.
		$this->email_post = $this->factory()->post->create_and_get(
			array(
				'post_title'   => 'Test Email',
				'post_name'    => $this->email_type,
				'post_type'    => Integration::EMAIL_POST_TYPE,
				'post_content' => 'Test content',
				'post_status'  => 'draft',
			)
		);
		// Associate the post with the email type.
		WCTransactionalEmailPostsManager::get_instance()->save_email_template_post_id(
			$this->email_type,
			$this->email_post->ID
		);
		// Initialize the controller.
		$this->email_api_controller = new EmailApiController();
		$this->email_api_controller->init();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'woocommerce_feature_block_email_editor_enabled' );
		delete_option( 'woocommerce_' . $this->email_type . '_settings' );
	}

	/**
	 * Test that the email data is returned correctly for an unsupported email type.
	 */
	public function test_get_email_data_returns_nulls_for_unsupported_email_type(): void {
		// Use a post ID not associated with any email type.
		$unassociated_post = $this->factory()->post->create_and_get(
			array(
				'post_title'   => 'Unassociated Email',
				'post_name'    => 'unassociated_email',
				'post_type'    => Integration::EMAIL_POST_TYPE,
				'post_content' => 'Test content',
				'post_status'  => 'draft',
			)
		);
		$post_data         = array( 'id' => $unassociated_post->ID );
		$result            = $this->email_api_controller->get_email_data( $post_data );
		$this->assertNull( $result['subject'] );
		$this->assertNull( $result['email_type'] );
	}

	/**
	 * Test that the email data is returned correctly for a supported email type.
	 */
	public function test_get_email_data_returns_email_data_for_supported_type(): void {
		// Set up a WC_Email mock and inject it into the controller's emails array.
		$mock_email     = $this->createMock( \WC_Email::class );
		$mock_email->id = $this->email_type;
		$mock_email->method( 'get_option' )->willReturnMap(
			array(
				array( 'subject', null, 'Test Subject' ),
				array( 'subject_full', null, null ),
				array( 'subject_partial', null, null ),
				array( 'preheader', null, 'Test Preheader' ),
				array( 'recipient', get_option( 'admin_email' ), 'admin@example.com' ),
				array( 'cc', null, null ),
				array( 'bcc', null, null ),
			)
		);
		$mock_email->method( 'get_default_subject' )->willReturn( 'Default Subject' );
		$mock_email->method( 'get_form_fields' )->willReturn(
			array(
				'recipient' => array(),
			)
		);
		// Inject the mock into the controller.
		$reflection  = new \ReflectionClass( $this->email_api_controller );
		$emails_prop = $reflection->getProperty( 'emails' );
		$emails_prop->setAccessible( true );
		$emails_prop->setValue( $this->email_api_controller, array( $mock_email ) );

		$post_data = array( 'id' => $this->email_post->ID );
		$result    = $this->email_api_controller->get_email_data( $post_data );
		$this->assertEquals( 'Test Subject', $result['subject'] );
		$this->assertEquals( 'Default Subject', $result['default_subject'] );
		$this->assertEquals( 'Test Preheader', $result['preheader'] );
		$this->assertEquals( $this->email_type, $result['email_type'] );
		$this->assertEquals( 'admin@example.com', $result['recipient'] );
	}

	/**
	 * Test that the email data is saved correctly.
	 */
	public function test_save_email_data_updates_options(): void {
		$data = array(
			'subject'   => 'Updated Subject',
			'preheader' => 'Updated Preheader',
			'recipient' => 'recipient@example.com',
			'cc'        => 'cc@example.com',
			'bcc'       => 'bcc@example.com',
		);
		$this->email_api_controller->save_email_data( $data, $this->email_post );
		$option = get_option( 'woocommerce_' . $this->email_type . '_settings' );
		$this->assertEquals( 'Updated Subject', $option['subject'] );
		$this->assertEquals( 'Updated Preheader', $option['preheader'] );
		$this->assertEquals( 'recipient@example.com', $option['recipient'] );
		$this->assertEquals( 'cc@example.com', $option['cc'] );
		$this->assertEquals( 'bcc@example.com', $option['bcc'] );
	}

	/**
	 * Test that the email data schema returns the expected schema.
	 */
	public function test_get_email_data_schema_returns_expected_schema(): void {
		$schema = $this->email_api_controller->get_email_data_schema();
		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'subject', $schema['properties'] );
		$this->assertArrayHasKey( 'preheader', $schema['properties'] );
		$this->assertArrayHasKey( 'recipient', $schema['properties'] );
	}

	/**
	 * Test that the recipient is null when not in form fields.
	 */
	public function test_get_email_data_recipient_is_null_when_not_in_form_fields(): void {
		$mock_email     = $this->createMock( \WC_Email::class );
		$mock_email->id = $this->email_type;
		$mock_email->method( 'get_option' )->willReturnMap(
			array(
				array( 'subject', null, 'Test Subject' ),
				array( 'subject_full', null, null ),
				array( 'subject_partial', null, null ),
				array( 'preheader', null, 'Test Preheader' ),
				array( 'cc', null, null ),
				array( 'bcc', null, null ),
			)
		);
		$mock_email->method( 'get_default_subject' )->willReturn( 'Default Subject' );
		$mock_email->method( 'get_form_fields' )->willReturn(
			array(
			// No 'recipient' key here.
			)
		);
		$reflection  = new \ReflectionClass( $this->email_api_controller );
		$emails_prop = $reflection->getProperty( 'emails' );
		$emails_prop->setAccessible( true );
		$emails_prop->setValue( $this->email_api_controller, array( $mock_email ) );

		$post_data = array( 'id' => $this->email_post->ID );
		$result    = $this->email_api_controller->get_email_data( $post_data );
		$this->assertNull( $result['recipient'] );
	}
}
