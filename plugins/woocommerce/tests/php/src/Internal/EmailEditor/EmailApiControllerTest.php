<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\Internal\EmailEditor\EmailApiController;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
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
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		WCEmailTemplateSyncRegistry::reset_cache();
		delete_transient( 'wc_email_editor_initial_templates_generated' );
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

	/**
	 * @testdox Should return 404 when post ID has no associated email type.
	 */
	public function test_get_default_content_response_returns_404_for_unknown_post(): void {
		$unassociated_post = $this->factory()->post->create_and_get(
			array(
				'post_title'  => 'Unknown Email',
				'post_name'   => 'unknown_email',
				'post_type'   => Integration::EMAIL_POST_TYPE,
				'post_status' => 'draft',
			)
		);

		$request = new \WP_REST_Request( 'GET', '/woocommerce-email-editor/v1/emails/' . $unassociated_post->ID . '/default-content' );
		$request->set_param( 'id', $unassociated_post->ID );

		$result = $this->email_api_controller->get_default_content_response( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'woocommerce_email_not_found', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should return default content for a valid email post.
	 */
	public function test_get_default_content_response_returns_content_for_valid_post(): void {
		$mock_email     = $this->createMock( \WC_Email::class );
		$mock_email->id = $this->email_type;

		$mock_generator = $this->createMock( WCTransactionalEmailPostsGenerator::class );
		$mock_generator->method( 'get_email_template' )
			->willReturn( '<!-- wp:paragraph --><p>Default content</p><!-- /wp:paragraph -->' );

		$controller = $this->getMockBuilder( EmailApiController::class )
			->onlyMethods( array( 'get_emails' ) )
			->getMock();
		$controller->method( 'get_emails' )
			->willReturn( array( $mock_email ) );
		$controller->init();

		$reflection = new \ReflectionClass( EmailApiController::class );
		$property   = $reflection->getProperty( 'posts_generator' );
		$property->setAccessible( true );
		$property->setValue( $controller, $mock_generator );

		$request = new \WP_REST_Request( 'GET', '/woocommerce-email-editor/v1/emails/' . $this->email_post->ID . '/default-content' );
		$request->set_param( 'id', $this->email_post->ID );

		$result = $controller->get_default_content_response( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 200, $result->get_status() );
		$this->assertArrayHasKey( 'content', $result->get_data() );
		$this->assertSame( '<!-- wp:paragraph --><p>Default content</p><!-- /wp:paragraph -->', $result->get_data()['content'] );
	}

	/**
	 * @testdox Should reset post content to canonical core render and refresh sync meta.
	 */
	public function test_reset_response_overwrites_post_content_and_stamps_sync_meta(): void {
		$email_type = 'customer_new_account';

		$generator = new WCTransactionalEmailPostsGenerator();
		$generator->init_default_transactional_emails();

		$post_manager = WCTransactionalEmailPostsManager::get_instance();
		$post_manager->clear_caches();
		$post_manager->delete_email_template( $email_type );
		WCEmailTemplateSyncRegistry::reset_cache();

		$post_id = $generator->generate_email_template_if_not_exists( $email_type );
		$this->assertIsInt( $post_id );

		// Simulate merchant customisation that diverges from the core render.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<!-- wp:paragraph --><p>Customized by merchant</p><!-- /wp:paragraph -->',
			)
		);

		// Backdate the synced_at stamp so we can assert the endpoint refreshes it.
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, '2000-01-01 00:00:00' );
		// Force a non-in_sync status so we can assert the endpoint resets it.
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED
		);

		$request = new \WP_REST_Request( 'POST', '/woocommerce-email-editor/v1/emails/' . $post_id . '/reset' );
		$request->set_param( 'id', $post_id );

		$result = $this->email_api_controller->reset_response( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 200, $result->get_status() );

		$email = $this->resolve_wc_email( $email_type );
		$this->assertNotNull( $email );
		$expected_canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		$response_data = $result->get_data();
		$this->assertSame( $expected_canonical, $response_data['content'], 'Response content must equal canonical core render.' );
		$this->assertSame( sha1( $expected_canonical ), $response_data['source_hash'], 'Response source_hash must equal sha1(canonical).' );
		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC, $response_data['status'], 'Response status must be in_sync.' );
		$this->assertNotEmpty( $response_data['version'], 'Response version must be populated.' );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $response_data['synced_at'], 'Response synced_at must be a GMT timestamp.' );

		$persisted_post = get_post( $post_id );
		$this->assertSame( $expected_canonical, $persisted_post->post_content, 'Persisted post_content must equal canonical core render.' );

		$this->assertSame(
			$response_data['version'],
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'Persisted version meta must match response.'
		);
		$this->assertSame(
			$response_data['source_hash'],
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'Persisted source_hash meta must match response.'
		);
		$this->assertSame(
			$response_data['synced_at'],
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ),
			'Persisted synced_at meta must match response.'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Persisted status meta must be set to in_sync.'
		);
	}

	/**
	 * @testdox Should return 404 when reset post ID has no associated email type.
	 */
	public function test_reset_response_returns_404_for_unknown_post(): void {
		$unassociated_post = $this->factory()->post->create_and_get(
			array(
				'post_title'  => 'Unknown Email',
				'post_name'   => 'unknown_email_for_reset',
				'post_type'   => Integration::EMAIL_POST_TYPE,
				'post_status' => 'draft',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/woocommerce-email-editor/v1/emails/' . $unassociated_post->ID . '/reset' );
		$request->set_param( 'id', $unassociated_post->ID );

		$result = $this->email_api_controller->reset_response( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'woocommerce_email_not_found', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should reset content but skip meta stamping for emails absent from the sync registry.
	 */
	public function test_reset_response_resets_content_without_meta_for_non_sync_enabled_email(): void {
		$email_type = 'customer_new_account';

		$generator = new WCTransactionalEmailPostsGenerator();
		$generator->init_default_transactional_emails();

		$post_manager = WCTransactionalEmailPostsManager::get_instance();
		$post_manager->clear_caches();
		$post_manager->delete_email_template( $email_type );

		$post_id = $generator->generate_email_template_if_not_exists( $email_type );
		$this->assertIsInt( $post_id );

		// Capture meta stamped at generation time so we can assert it is unchanged after reset.
		$baseline_version     = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true );
		$baseline_source_hash = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true );
		$baseline_synced_at   = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true );

		// Simulate a customised post so the content reset is observable.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<!-- wp:paragraph --><p>Customised by merchant</p><!-- /wp:paragraph -->',
			)
		);

		// Forcibly empty the registry so the email is not sync-enabled.
		WCEmailTemplateSyncRegistry::reset_cache();
		add_filter( 'woocommerce_transactional_emails_for_block_editor', '__return_empty_array' );

		$request = new \WP_REST_Request( 'POST', '/woocommerce-email-editor/v1/emails/' . $post_id . '/reset' );
		$request->set_param( 'id', $post_id );

		$result = $this->email_api_controller->reset_response( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 200, $result->get_status() );

		$email = $this->resolve_wc_email( $email_type );
		$this->assertNotNull( $email );
		$expected_canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		$response_data = $result->get_data();
		$this->assertSame( $expected_canonical, $response_data['content'], 'Response content must equal canonical core render.' );
		$this->assertNull( $response_data['version'], 'Response version must be null for non-sync-enabled emails.' );
		$this->assertNull( $response_data['source_hash'], 'Response source_hash must be null for non-sync-enabled emails.' );
		$this->assertNull( $response_data['synced_at'], 'Response synced_at must be null for non-sync-enabled emails.' );
		$this->assertNull( $response_data['status'], 'Response status must be null for non-sync-enabled emails.' );

		$this->assertSame(
			$expected_canonical,
			(string) get_post_field( 'post_content', $post_id ),
			'post_content must be reset to canonical render even when the email is not sync-enabled.'
		);

		// Stamping must NOT have run. Meta values stay at whatever the generator wrote at creation time.
		$this->assertSame(
			$baseline_version,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'_wc_email_template_version must not be touched when the email is not sync-enabled.'
		);
		$this->assertSame(
			$baseline_source_hash,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'_wc_email_template_source_hash must not be touched when the email is not sync-enabled.'
		);
		$this->assertSame(
			$baseline_synced_at,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ),
			'_wc_email_last_synced_at must not be touched when the email is not sync-enabled.'
		);
		$this->assertSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'_wc_email_template_status must not be set when the email is not sync-enabled.'
		);
	}

	/**
	 * @testdox Should return 500 when controller has not been initialized.
	 */
	public function test_reset_response_returns_500_when_uninitialized(): void {
		$controller = new EmailApiController();
		// Intentionally skip init() to leave dependencies null.

		$request = new \WP_REST_Request( 'POST', '/woocommerce-email-editor/v1/emails/0/reset' );
		$request->set_param( 'id', 0 );

		$result = $controller->reset_response( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'woocommerce_email_editor_not_initialized', $result->get_error_code() );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should register a POST /reset route alongside the existing default-content route.
	 */
	public function test_register_routes_registers_reset_endpoint(): void {
		$rest_server = rest_get_server();
		$this->email_api_controller->register_routes();

		$routes = $rest_server->get_routes();
		$this->assertArrayHasKey( '/woocommerce-email-editor/v1/emails/(?P<id>\d+)/reset', $routes );

		$reset_route_handlers = $routes['/woocommerce-email-editor/v1/emails/(?P<id>\d+)/reset'];
		$methods              = array();
		foreach ( $reset_route_handlers as $handler ) {
			foreach ( array_keys( $handler['methods'] ) as $method ) {
				$methods[ $method ] = true;
			}
		}
		$this->assertArrayHasKey( 'POST', $methods, 'Reset endpoint must accept POST.' );
	}

	/**
	 * Helper: resolve a WC_Email instance by email type ID.
	 *
	 * @param string $email_type Email type ID.
	 * @return \WC_Email|null
	 */
	private function resolve_wc_email( string $email_type ): ?\WC_Email {
		foreach ( WC()->mailer()->get_emails() as $email ) {
			if ( $email->id === $email_type ) {
				return $email;
			}
		}
		return null;
	}
}
