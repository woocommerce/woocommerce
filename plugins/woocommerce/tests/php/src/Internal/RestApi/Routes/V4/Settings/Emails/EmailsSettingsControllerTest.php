<?php
/**
 * Emails Settings V4 controller unit tests.
 *
 * @package WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\Emails
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\Emails;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Emails\Controller;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Emails\Schema\EmailsSettingsSchema;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WC_Emails;

/**
 * Tests for the Emails Settings REST API controller.
 *
 * @class EmailsSettingsControllerTest
 */
class EmailsSettingsControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Sample email ID for testing.
	 *
	 * @var string
	 */
	const SAMPLE_EMAIL_ID = 'customer_completed_order';

	/**
	 * Sample email instance.
	 *
	 * @var WC_Email
	 */
	private $email;

	/**
	 * User ID with shop_manager permissions.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Feature filter callback.
	 *
	 * @var callable
	 */
	private $feature_filter;

	/**
	 * Previous option values to restore after tests.
	 *
	 * @var array<string, mixed>
	 */
	private $prev_options = array();

	/**
	 * Personalization tags registry instance.
	 *
	 * @var Personalization_Tags_Registry|null
	 */
	private $registry;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		// Enable the v4 REST API feature before bootstrapping.
		$this->feature_filter = function ( $features ) {
			$features[] = 'rest-api-v4';
			return $features;
		};

		add_filter( 'woocommerce_admin_features', $this->feature_filter );

		// Enable block email editor feature.
		$this->prev_options['woocommerce_feature_block_email_editor_enabled'] = get_option( 'woocommerce_feature_block_email_editor_enabled', null );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );

		parent::setUp();

		// Register personalization tags for testing.
		$container      = Email_Editor_Container::container();
		$this->registry = $container->get( Personalization_Tags_Registry::class );
		$this->register_personalization_tag( 'woocommerce', 'customer-first-name' );
		$this->register_personalization_tag( 'woocommerce', 'order-number' );
		$this->register_personalization_tag( 'custom-plugin', 'test-field' );

		// Manually initialize controller with schema that has the registry.
		// We do this to ensure personalization tags wrapping/unwrapping uses the registry.
		$controller = new Controller();
		$schema     = new EmailsSettingsSchema();
		$schema->init();
		$controller->init( $schema );
		$controller->register_routes();

		// Snapshot current option values to restore on tearDown.
		$option_key                        = 'woocommerce_' . self::SAMPLE_EMAIL_ID . '_settings';
		$this->prev_options[ $option_key ] = get_option( $option_key, null );

		// Create a user with permissions.
		$this->user_id = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);

		// Initialize WC_Emails to ensure emails are registered.
		WC_Emails::instance()->init();
		$this->email = WC_Emails::instance()->emails['WC_Email_Customer_Completed_Order'];

		// Generate transactional email template posts.
		$email_generator = new WCTransactionalEmailPostsGenerator();
		$email_generator->initialize();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( isset( $this->feature_filter ) ) {
			remove_filter( 'woocommerce_admin_features', $this->feature_filter );
		}

		// Restore previous option values.
		foreach ( $this->prev_options as $key => $value ) {
			if ( null === $value ) {
				delete_option( (string) $key );
			} else {
				update_option( (string) $key, $value );
			}
		}

		// Clean up email template posts transient.
		delete_transient( 'wc_email_editor_initial_templates_generated' );

		parent::tearDown();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/settings/emails', $routes );
		$this->assertArrayHasKey( '/wc/v4/settings/emails/(?P<email_id>[\w-]+)', $routes );
	}

	/**
	 * Test that get_items returns properly formatted response.
	 */
	public function test_get_items_returns_properly_formatted_response() {
		wp_set_current_user( $this->user_id );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );

		// Verify at least one item has proper structure.
		$item = $data[0];
		$this->assert_valid_email_item_structure( $item );
	}

	/**
	 * Test that get_item returns properly formatted response.
	 */
	public function test_get_single_item_returns_properly_formatted_response() {
		wp_set_current_user( $this->user_id );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::SAMPLE_EMAIL_ID, $data['id'] );
		$this->assert_valid_email_item_structure( $data );

		// Verify values contain email-specific settings.
		$this->assertArrayHasKey( 'values', $data );
		$this->assertIsArray( $data['values'] );
		$this->assertArrayHasKey( 'enabled', $data['values'] );
		$this->assertArrayHasKey( 'subject', $data['values'] );
		$this->assertArrayHasKey( 'heading', $data['values'] );
	}

	/**
	 * Test that get_items can filter by post_id.
	 */
	public function test_get_items_can_filter_by_post_id() {
		wp_set_current_user( $this->user_id );

		// First, get an email and its post_id.
		$request      = new WP_REST_Request( 'GET', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$response     = $this->server->dispatch( $request );
		$email_data   = $response->get_data();
		$test_post_id = $email_data['post_id'];

		// Now filter by that post_id.
		$request = new WP_REST_Request( 'GET', '/wc/v4/settings/emails' );
		$request->set_param( 'post_id', $test_post_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		// If post_id is valid, we should get at least the one email.
		if ( $test_post_id > 0 ) {
			$this->assertNotEmpty( $data );
			foreach ( $data as $item ) {
				$this->assertEquals( $test_post_id, $item['post_id'] );
			}
		}
	}

	/**
	 * Test that filtering by nonexistent post_id returns empty array.
	 */
	public function test_get_items_with_nonexistent_post_id_returns_empty() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'GET', '/wc/v4/settings/emails' );
		$request->set_param( 'post_id', 999999 );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertEmpty( $data );
	}

	/**
	 * Test successfully updating email settings.
	 */
	public function test_update_item_successfully_updates_settings() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'enabled' => true,
						'subject' => 'Test Subject',
						'heading' => 'Test Heading',
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Test Subject', $data['values']['subject'] );
		$this->assertEquals( 'Test Heading', $data['values']['heading'] );
		$this->assertTrue( $data['values']['enabled'] );

		// Verify database was updated.
		$option_key = 'woocommerce_' . self::SAMPLE_EMAIL_ID . '_settings';
		$settings   = get_option( $option_key, array() );
		$this->assertEquals( 'Test Subject', $settings['subject'] );
		$this->assertEquals( 'Test Heading', $settings['heading'] );
		$this->assertEquals( 'yes', $settings['enabled'] );
	}

	/**
	 * Test updating with invalid email ID returns 404.
	 */
	public function test_update_item_with_invalid_email_id_returns_404() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/invalid_email_id' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'values' => array( 'subject' => 'Test' ) ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'Email template not found.', $response->get_data()['message'] );
	}

	/**
	 * Test updating with empty body returns 400.
	 */
	public function test_update_item_with_empty_body_returns_400() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array() ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Invalid or empty request body.', $response->get_data()['message'] );
	}

	/**
	 * Test checkbox field sanitization.
	 */
	public function test_sanitize_checkbox_field() {
		wp_set_current_user( $this->user_id );

		// Test boolean true.
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'values' => array( 'enabled' => true ) ) ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['values']['enabled'] );

		// Test boolean false.
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'values' => array( 'enabled' => false ) ) ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['values']['enabled'] );

		// Verify database storage.
		$option_key = 'woocommerce_' . self::SAMPLE_EMAIL_ID . '_settings';
		$settings   = get_option( $option_key, array() );
		$this->assertEquals( 'no', $settings['enabled'] );
	}

	/**
	 * Test text field sanitization.
	 */
	public function test_sanitize_text_field() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => '<script>alert("xss")</script>Test Subject',
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify HTML tags are stripped in stored value.
		$stored_subject = $this->get_stored_subject( self::SAMPLE_EMAIL_ID );
		$this->assertStringNotContainsString( '<script>', $stored_subject );
		$this->assertStringNotContainsString( '</script>', $stored_subject );
		$this->assertStringContainsString( 'Test Subject', $stored_subject );
	}

	/**
	 * Test unwrapping personalization tags on GET.
	 */
	public function test_unwrap_personalization_tags_on_get() {
		wp_set_current_user( $this->user_id );

		// Store wrapped subject directly in database.
		$wrapped_subject                  = 'Hello <!--[woocommerce/customer-first-name]-->';
		$this->email->settings['subject'] = $wrapped_subject;

		// GET the email settings.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		// Verify subject is unwrapped.
		$this->assertEquals( 'Hello [woocommerce/customer-first-name]', $data['values']['subject'] );
	}

	/**
	 * Test wrapping personalization tags on UPDATE.
	 */
	public function test_wrap_personalization_tags_on_update() {
		wp_set_current_user( $this->user_id );

		$unwrapped_subject = 'Hello [woocommerce/customer-first-name]';

		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => $unwrapped_subject,
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify database stores wrapped version.
		$stored_subject = $this->get_stored_subject( self::SAMPLE_EMAIL_ID );
		$this->assertEquals( 'Hello <!--[woocommerce/customer-first-name]-->', $stored_subject );
	}

	/**
	 * Test personalization tags support multiple prefixes.
	 */
	public function test_personalization_tags_support_multiple_prefixes() {
		wp_set_current_user( $this->user_id );
		$subject_with_multiple_prefixes = 'Hello [woocommerce/customer-first-name] [custom-plugin/test-field]';

		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => $subject_with_multiple_prefixes,
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify all prefixes are wrapped.
		$stored_subject = $this->get_stored_subject( self::SAMPLE_EMAIL_ID );
		$this->assertStringContainsString( '<!--[woocommerce/customer-first-name]-->', $stored_subject );

		// Custom plugin prefix should also be wrapped if registry is available.
		$this->assertStringContainsString( '<!--[custom-plugin/test-field]-->', $stored_subject );

		// GET the settings and verify all are unwrapped.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertStringContainsString( '[woocommerce/customer-first-name]', $data['values']['subject'] );
		$this->assertStringNotContainsString( '<!--', $data['values']['subject'] );
		$this->assertStringNotContainsString( '-->', $data['values']['subject'] );
	}

	/**
	 * Test that personalization tags are not double-wrapped.
	 */
	public function test_personalization_tags_no_double_wrapping() {
		wp_set_current_user( $this->user_id );

		$already_wrapped_subject = 'Hello <!--[woocommerce/customer-first-name]-->';

		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => $already_wrapped_subject,
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify NOT double-wrapped.
		$stored_subject = $this->get_stored_subject( self::SAMPLE_EMAIL_ID );
		$this->assertEquals( 'Hello <!--[woocommerce/customer-first-name]-->', $stored_subject );
		$this->assertStringNotContainsString( '<!--<!--', $stored_subject );
		$this->assertStringNotContainsString( '-->-->', $stored_subject );
	}

	/**
	 * Test mixed wrapped and unwrapped tags.
	 */
	public function test_personalization_tags_mixed_wrapped_unwrapped() {
		wp_set_current_user( $this->user_id );

		$mixed_subject = 'Hello <!--[woocommerce/customer-first-name]--> and [woocommerce/order-number]';

		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => $mixed_subject,
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify only unwrapped tag gets wrapped.
		$stored_subject = $this->get_stored_subject( self::SAMPLE_EMAIL_ID );
		$this->assertEquals( 'Hello <!--[woocommerce/customer-first-name]--> and <!--[woocommerce/order-number]-->', $stored_subject );
	}

	/**
	 * Test personalization tags are case insensitive.
	 */
	public function test_personalization_tags_case_insensitive() {
		wp_set_current_user( $this->user_id );

		$uppercase_subject = 'Hello [WooCommerce/customer-first-name]';

		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => $uppercase_subject,
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify wrapped correctly despite uppercase.
		$stored_subject = $this->get_stored_subject( self::SAMPLE_EMAIL_ID );
		$this->assertStringContainsString( '<!--[WooCommerce/customer-first-name]-->', $stored_subject );
	}

	/**
	 * Test personalization tags with attributes.
	 */
	public function test_personalization_tags_with_attributes() {
		wp_set_current_user( $this->user_id );

		$subject_with_attributes = 'Hello [woocommerce/customer-first-name default="Guest"]';

		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => $subject_with_attributes,
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify wrapped with attributes intact.
		$stored_subject = $this->get_stored_subject( self::SAMPLE_EMAIL_ID );
		$this->assertEquals( 'Hello <!--[woocommerce/customer-first-name default="Guest"]-->', $stored_subject );

		// Verify unwraps back correctly.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'Hello [woocommerce/customer-first-name default="Guest"]', $data['values']['subject'] );
	}

	/**
	 * Test preheader field also supports personalization tags.
	 */
	public function test_personalization_tags_preheader_field_support() {
		wp_set_current_user( $this->user_id );

		$preheader_with_tag = 'Check your order [woocommerce/order-number]';

		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'preheader' => $preheader_with_tag,
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify preheader is wrapped.
		$option_key = 'woocommerce_' . self::SAMPLE_EMAIL_ID . '_settings';
		$settings   = get_option( $option_key, array() );
		$this->assertEquals( 'Check your order <!--[woocommerce/order-number]-->', $settings['preheader'] );

		// Verify GET unwraps preheader.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		if ( isset( $data['values']['preheader'] ) ) {
			$this->assertEquals( 'Check your order [woocommerce/order-number]', $data['values']['preheader'] );
		}
	}

	/**
	 * Test GET without permission returns 401.
	 */
	public function test_get_items_without_permission_returns_401() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test UPDATE without permission returns 401.
	 */
	public function test_update_item_without_permission_returns_401() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'values' => array( 'subject' => 'Test' ) ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test GET with shop_manager permission succeeds.
	 */
	public function test_get_items_with_shop_manager_permission() {
		wp_set_current_user( $this->user_id );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/emails' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test UPDATE with shop_manager permission succeeds.
	 */
	public function test_update_item_with_shop_manager_permission() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/emails/' . self::SAMPLE_EMAIL_ID );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'subject' => 'Test Subject',
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Helper: Assert valid email item structure.
	 *
	 * @param array $item Email item data.
	 */
	private function assert_valid_email_item_structure( array $item ) {
		// Verify required top-level fields.
		$this->assertArrayHasKey( 'id', $item );
		$this->assertArrayHasKey( 'title', $item );
		$this->assertArrayHasKey( 'description', $item );
		$this->assertArrayHasKey( 'post_id', $item );
		$this->assertArrayHasKey( 'link', $item );
		$this->assertArrayHasKey( 'email_group', $item );
		$this->assertArrayHasKey( 'email_group_title', $item );
		$this->assertArrayHasKey( 'is_customer_email', $item );
		$this->assertArrayHasKey( 'is_manual', $item );
		$this->assertArrayHasKey( 'values', $item );
		$this->assertArrayHasKey( 'groups', $item );

		// Verify types.
		$this->assertIsString( $item['id'] );
		$this->assertIsString( $item['title'] );
		$this->assertTrue( is_int( $item['post_id'] ) || is_null( $item['post_id'] ), 'post_id should be int or null' );
		$this->assertIsBool( $item['is_customer_email'] );
		$this->assertIsBool( $item['is_manual'] );
		$this->assertIsArray( $item['values'] );
		$this->assertIsArray( $item['groups'] );

		// Verify groups structure.
		if ( ! empty( $item['groups'] ) ) {
			foreach ( $item['groups'] as $group ) {
				$this->assertArrayHasKey( 'title', $group );
				$this->assertArrayHasKey( 'description', $group );
				$this->assertArrayHasKey( 'order', $group );
				$this->assertArrayHasKey( 'fields', $group );
				$this->assertIsArray( $group['fields'] );

				// Verify fields structure.
				if ( ! empty( $group['fields'] ) ) {
					foreach ( $group['fields'] as $field ) {
						$this->assertArrayHasKey( 'id', $field );
						$this->assertArrayHasKey( 'label', $field );
						$this->assertArrayHasKey( 'type', $field );
						$this->assertArrayHasKey( 'desc', $field );
					}
				}
			}
		}
	}

	/**
	 * Helper: Get stored subject from database.
	 *
	 * @param string $email_id Email ID.
	 * @return string Stored subject.
	 */
	private function get_stored_subject( string $email_id ): string {
		$option_key = 'woocommerce_' . $email_id . '_settings';
		$settings   = get_option( $option_key, array() );
		return $settings['subject'] ?? '';
	}

	/**
	 * Helper: Register personalization tag for testing.
	 *
	 * @param string $prefix Tag prefix.
	 * @param string $name Tag name.
	 */
	private function register_personalization_tag( string $prefix, string $name ) {
		$tag = new Personalization_Tag(
			ucfirst( $name ),
			$prefix . '/' . $name,
			'Custom',
			function () {
				return 'test-value';
			}
		);
		$this->registry->register( $tag );
	}
}
