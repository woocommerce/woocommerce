<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\Internal\EmailEditor\EmailApiController;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

require_once 'EmailStub.php';

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
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
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
		// Set up a WC_Email mock.
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

		// Create a partial mock of the controller to override get_emails().
		$controller = $this->getMockBuilder( EmailApiController::class )
			->onlyMethods( array( 'get_emails' ) )
			->getMock();
		$controller->method( 'get_emails' )
			->willReturn( array( $mock_email ) );
		$controller->init();

		$post_data = array( 'id' => $this->email_post->ID );
		$result    = $controller->get_email_data( $post_data );
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
		// Set up a real WC_Email instance for testing.
		$email = new EmailStub();

		// Create a partial mock of the controller to override get_emails().
		$controller = $this->getMockBuilder( EmailApiController::class )
			->onlyMethods( array( 'get_emails' ) )
			->getMock();
		$controller->method( 'get_emails' )
			->willReturn( array( $email ) );
		$controller->init();

		$data = array(
			'subject'   => 'Updated Subject',
			'preheader' => 'Updated Preheader',
			'recipient' => 'recipient@example.com',
			'cc'        => 'cc@example.com',
			'bcc'       => 'bcc@example.com',
		);
		$controller->save_email_data( $data, $this->email_post );
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
	 * Test that save_email_data returns WP_Error for invalid email addresses.
	 */
	public function test_save_email_data_returns_error_for_invalid_emails(): void {
		$test_cases = array(
			array(
				'data'  => array(
					'recipient' => 'invalid-email',
				),
				'field' => 'recipient',
			),
			array(
				'data'  => array(
					'recipient' => 'valid.email@example.com,invalid-email',
				),
				'field' => 'recipient',
			),
			array(
				'data'  => array(
					'cc' => 'invalid-email',
				),
				'field' => 'cc',
			),
			array(
				'data'  => array(
					'bcc' => 'invalid-email',
				),
				'field' => 'bcc',
			),
		);

		foreach ( $test_cases as $test_case ) {
			$result = $this->email_api_controller->save_email_data( $test_case['data'], $this->email_post );
			$this->assertTrue( is_wp_error( $result ), "Expected WP_Error for invalid {$test_case['field']}" );
			$this->assertEquals( 'invalid_email_data', $result->get_error_code(), "Expected invalid_email_address error code for {$test_case['field']}" );
		}
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

		// Create a partial mock of the controller to override get_emails().
		$controller = $this->getMockBuilder( EmailApiController::class )
			->onlyMethods( array( 'get_emails' ) )
			->getMock();
		$controller->method( 'get_emails' )
			->willReturn( array( $mock_email ) );
		$controller->init();

		$post_data = array( 'id' => $this->email_post->ID );
		$result    = $controller->get_email_data( $post_data );
		$this->assertNull( $result['recipient'] );
	}

	/**
	 * Test that the email data can be retrieved immediately after updating.
	 */
	public function test_get_email_data_returns_updated_values_immediately_after_save(): void {
		// Set up a real WC_Email instance for testing.
		$email = new EmailStub();

		// Create a partial mock of the controller to override get_emails().
		$controller = $this->getMockBuilder( EmailApiController::class )
			->onlyMethods( array( 'get_emails' ) )
			->getMock();
		$controller->method( 'get_emails' )
			->willReturn( array( $email ) );
		$controller->init();

		// Save new email data.
		$data = array(
			'subject'   => 'Immediately Updated Subject',
			'preheader' => 'Immediately Updated Preheader',
			'recipient' => 'immediate@example.com',
			'cc'        => 'immediate-cc@example.com',
			'bcc'       => 'immediate-bcc@example.com',
		);
		$controller->save_email_data( $data, $this->email_post );

		// Immediately retrieve the data.
		$post_data = array( 'id' => $this->email_post->ID );
		$result    = $controller->get_email_data( $post_data );

		// Verify that the retrieved data matches what was saved.
		$this->assertEquals( 'Immediately Updated Subject', $result['subject'] );
		$this->assertEquals( 'Immediately Updated Preheader', $result['preheader'] );
		$this->assertEquals( 'immediate@example.com', $result['recipient'] );
		$this->assertEquals( 'immediate-cc@example.com', $result['cc'] );
		$this->assertEquals( 'immediate-bcc@example.com', $result['bcc'] );
		$this->assertEquals( $this->email_type, $result['email_type'] );
		$this->assertEquals( 'Default Subject', $result['default_subject'] );
	}
}
