<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Package;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use WC_Unit_Test_Case;

/**
 * Tests for the Integration class.
 */
class IntegrationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Integration
	 */
	private $sut;

	/**
	 * Transactional email post manager singleton.
	 *
	 * @var WCTransactionalEmailPostsManager
	 */
	private WCTransactionalEmailPostsManager $posts_manager;

	/**
	 * Keys of WC_Email stubs injected into WC_Emails::$emails, for teardown.
	 *
	 * @var string[]
	 */
	private array $injected_email_keys = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		wc_get_container()->get( Package::class )->init();

		$this->sut           = wc_get_container()->get( Integration::class );
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$this->posts_manager->clear_caches();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( ! empty( $this->injected_email_keys ) ) {
			$emails_container = \WC_Emails::instance();
			$reflection       = new \ReflectionClass( $emails_container );
			$property         = $reflection->getProperty( 'emails' );
			$property->setAccessible( true );
			$current = $property->getValue( $emails_container );
			foreach ( $this->injected_email_keys as $key ) {
				unset( $current[ $key ] );
			}
			$property->setValue( $emails_container, $current );
			$this->injected_email_keys = array();
		}

		$this->posts_manager->clear_caches();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * @testdox Should write the email type mapping when an auto-draft transitions to publish.
	 */
	public function test_auto_draft_to_publish_writes_mapping(): void {
		$post = $this->create_woo_email_post( 'customer_processing_order' );

		$this->sut->save_email_mapping_on_publish( 'publish', 'auto-draft', $post );

		$this->assertSame(
			$post->ID,
			(int) get_option( 'woocommerce_email_templates_customer_processing_order_post_id' ),
			'Publishing an auto-draft must write the option mapping'
		);
	}

	/**
	 * @testdox Should write the email type mapping when a draft transitions to publish.
	 */
	public function test_draft_to_publish_writes_mapping(): void {
		$post = $this->create_woo_email_post( 'customer_completed_order', 'draft' );

		$this->sut->save_email_mapping_on_publish( 'publish', 'draft', $post );

		$this->assertSame(
			$post->ID,
			(int) get_option( 'woocommerce_email_templates_customer_completed_order_post_id' ),
			'Publishing a draft must write the option mapping'
		);
	}

	/**
	 * @testdox Should not touch an existing mapping on a publish-to-publish transition.
	 */
	public function test_publish_to_publish_leaves_existing_mapping_untouched(): void {
		$other_post_id = $this->factory()->post->create();
		$this->posts_manager->save_email_template_post_id( 'customer_new_account', $other_post_id );

		$post = $this->create_woo_email_post( 'customer_new_account', 'publish' );

		$this->sut->save_email_mapping_on_publish( 'publish', 'publish', $post );

		$this->assertSame(
			$other_post_id,
			(int) get_option( 'woocommerce_email_templates_customer_new_account_post_id' ),
			'A publish-to-publish transition (post update) must not rewrite the mapping'
		);
	}

	/**
	 * @testdox Should ignore posts of other post types.
	 */
	public function test_non_woo_email_post_is_ignored(): void {
		$post = $this->factory()->post->create_and_get( array( 'post_status' => 'draft' ) );
		update_post_meta( $post->ID, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, 'customer_note' );

		$this->sut->save_email_mapping_on_publish( 'publish', 'draft', $post );

		$this->assertFalse(
			get_option( 'woocommerce_email_templates_customer_note_post_id' ),
			'Posts of other post types must not produce a mapping'
		);
	}

	/**
	 * @testdox Should not write a mapping when the email type meta is not a registered WC_Email id.
	 */
	public function test_unregistered_email_type_meta_does_not_write_mapping(): void {
		$post = $this->create_woo_email_post( 'not_a_registered_email_type' );

		$this->sut->save_email_mapping_on_publish( 'publish', 'auto-draft', $post );

		$this->assertFalse(
			get_option( 'woocommerce_email_templates_not_a_registered_email_type_post_id' ),
			'An unregistered email type in the meta must not produce a mapping'
		);
	}

	/**
	 * @testdox Should ignore woo_email posts without the email type meta.
	 */
	public function test_post_without_email_type_meta_is_ignored(): void {
		$post = $this->factory()->post->create_and_get(
			array(
				'post_type'   => Integration::EMAIL_POST_TYPE,
				'post_status' => 'auto-draft',
			)
		);

		$this->sut->save_email_mapping_on_publish( 'publish', 'auto-draft', $post );

		$this->assertNull(
			$this->posts_manager->get_email_type_from_post_id( $post->ID, true ),
			'A woo_email post without the email type meta must not produce a mapping'
		);
	}

	/**
	 * @testdox Should write the mapping through the transition_post_status hook when a post is published.
	 */
	public function test_mapping_written_via_transition_hook_on_publish(): void {
		$this->sut->initialize();

		$post = $this->create_woo_email_post( 'new_order' );

		wp_update_post(
			array(
				'ID'          => $post->ID,
				'post_status' => 'publish',
			)
		);

		$this->assertSame(
			$post->ID,
			(int) get_option( 'woocommerce_email_templates_new_order_post_id' ),
			'Publishing via wp_update_post must write the mapping through the transition_post_status hook'
		);
	}

	/**
	 * @testdox Postless send-preview renders the file template and mails it to the recipient.
	 */
	public function test_send_preview_for_email_type_sends_file_template_render(): void {
		$this->bootstrap_email_editor();
		$this->skip_if_unsupported_environment();

		reset_phpmailer_instance();

		$result = $this->sut->send_preview_email_for_email_type(
			array(
				'email'     => 'preview-recipient@example.com',
				'emailType' => 'customer_processing_order',
			)
		);

		$this->assertTrue( $result );

		$mailer = tests_retrieve_phpmailer_instance();
		$sent   = $mailer->get_sent();
		$this->assertNotFalse( $sent, 'A preview email must be sent' );
		$this->assertSame( 'preview-recipient@example.com', $sent->to[0][0] );
		$this->assertStringContainsString( 'is now being processed', (string) $sent->body, 'The body must contain the file template content' );
		$this->assertStringContainsString( 'All Rights Reserved', (string) $sent->body, 'The body must be rendered through the email template chrome' );
	}

	/**
	 * @testdox Send-preview requests with a post ID (or already handled) pass through untouched.
	 */
	public function test_send_preview_passes_post_requests_through(): void {
		$data = array(
			'email'  => 'a@example.com',
			'postId' => 123,
		);

		$this->assertSame( $data, $this->sut->send_preview_email_for_email_type( $data ) );
		$this->assertTrue( $this->sut->send_preview_email_for_email_type( true ) );
	}

	/**
	 * @testdox Postless send-preview throws for an unregistered email type.
	 */
	public function test_send_preview_for_unknown_email_type_throws(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->send_preview_email_for_email_type(
			array(
				'email'     => 'a@example.com',
				'emailType' => 'this_type_is_not_registered',
			)
		);
	}

	/**
	 * @testdox Postless send-preview throws for an invalid recipient address.
	 */
	public function test_send_preview_with_invalid_recipient_throws(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->send_preview_email_for_email_type(
			array(
				'email'     => 'not-an-email',
				'emailType' => 'customer_processing_order',
			)
		);
	}

	/**
	 * @testdox Postless send-preview permission requires manage_woocommerce and a registered email type.
	 */
	public function test_postless_send_preview_permission(): void {
		$request = new \WP_REST_Request( 'POST', '/woocommerce-email-editor/v1/send_preview_email' );
		$request->set_param( 'emailType', 'customer_processing_order' );

		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->assertTrue( $this->sut->authorize_postless_send_preview( false, $request ), 'A shop manager may send a postless preview for a registered email type' );

		$request->set_param( 'emailType', 'this_type_is_not_registered' );
		$this->assertFalse( $this->sut->authorize_postless_send_preview( false, $request ), 'Unregistered email types must be rejected' );

		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );
		$request->set_param( 'emailType', 'customer_processing_order' );
		$this->assertFalse( $this->sut->authorize_postless_send_preview( false, $request ), 'Users without manage_woocommerce must be rejected' );
	}

	/**
	 * Third-party get_subject() implementations may assume send-time state and
	 * throw outside a real send (e.g. WooCommerce Bookings dereferences its
	 * booking object). The preview must survive that and fall back to the title.
	 *
	 * @testdox Postless send-preview survives a get_subject() that throws outside a send.
	 */
	public function test_send_preview_survives_get_subject_throwing(): void {
		$this->bootstrap_email_editor();
		$this->skip_if_unsupported_environment();

		$email_id = 'wc_test_booking_style_email';
		$this->register_third_party_email( $email_id );

		reset_phpmailer_instance();

		$result = $this->sut->send_preview_email_for_email_type(
			array(
				'email'     => 'preview-recipient@example.com',
				'emailType' => $email_id,
			)
		);

		$this->assertTrue( $result );

		$mailer = tests_retrieve_phpmailer_instance();
		$sent   = $mailer->get_sent();
		$this->assertNotFalse( $sent, 'A preview email must be sent despite get_subject() throwing' );
		$this->assertSame( 'Booking style email', $sent->subject, 'The email title must be used as the subject fallback' );
	}

	/**
	 * @testdox Should refresh a never-edited scratchpad from the file template when the editor opens directly.
	 */
	public function test_editor_open_refreshes_untouched_scratchpad(): void {
		$this->bootstrap_email_editor();
		$this->skip_if_unsupported_environment();

		$post_id = $this->create_scratchpad( 'customer_processing_order' );

		$append_marker = static function ( $template_html ) {
			return $template_html . "\n<!-- wp:paragraph --><p>FRESH_TEMPLATE_MARKER</p><!-- /wp:paragraph -->";
		};
		add_filter( 'woocommerce_email_block_template_html', $append_marker );

		try {
			$this->invoke_maybe_refresh_scratchpad( $post_id );
		} finally {
			remove_filter( 'woocommerce_email_block_template_html', $append_marker );
		}

		$refreshed = get_post( $post_id );
		$this->assertStringContainsString( 'FRESH_TEMPLATE_MARKER', $refreshed->post_content, 'Opening the editor must refresh an untouched scratchpad to the current file template' );
		$this->assertSame(
			sha1( (string) $refreshed->post_content ),
			get_post_meta( $post_id, \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'The source hash must be restamped so the scratchpad still counts as never-edited'
		);
	}

	/**
	 * @testdox Should not touch an edited scratchpad when the editor opens directly.
	 */
	public function test_editor_open_leaves_edited_scratchpad_untouched(): void {
		$this->bootstrap_email_editor();
		$this->skip_if_unsupported_environment();

		$post_id        = $this->create_scratchpad( 'customer_completed_order' );
		$edited_content = get_post( $post_id )->post_content . "\n<!-- wp:paragraph --><p>MERCHANT_EDIT</p><!-- /wp:paragraph -->";
		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_content'  => $edited_content,
				'page_template' => '',
			)
		);

		$append_marker = static function ( $template_html ) {
			return $template_html . "\n<!-- wp:paragraph --><p>FRESH_TEMPLATE_MARKER</p><!-- /wp:paragraph -->";
		};
		add_filter( 'woocommerce_email_block_template_html', $append_marker );

		try {
			$this->invoke_maybe_refresh_scratchpad( $post_id );
		} finally {
			remove_filter( 'woocommerce_email_block_template_html', $append_marker );
		}

		$this->assertSame( $edited_content, get_post( $post_id )->post_content, 'An edited scratchpad must never be refreshed' );
	}

	/**
	 * @testdox Should not touch a published email post when the editor opens directly.
	 */
	public function test_editor_open_leaves_published_post_untouched(): void {
		$this->bootstrap_email_editor();
		$this->skip_if_unsupported_environment();

		$post_id = $this->create_scratchpad( 'customer_new_account' );
		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_status'   => 'publish',
				'page_template' => '',
			)
		);
		$published_content = get_post( $post_id )->post_content;

		$append_marker = static function ( $template_html ) {
			return $template_html . "\n<!-- wp:paragraph --><p>FRESH_TEMPLATE_MARKER</p><!-- /wp:paragraph -->";
		};
		add_filter( 'woocommerce_email_block_template_html', $append_marker );

		try {
			$this->invoke_maybe_refresh_scratchpad( $post_id );
		} finally {
			remove_filter( 'woocommerce_email_block_template_html', $append_marker );
		}

		$this->assertSame( $published_content, get_post( $post_id )->post_content, 'A published post must never be refreshed' );
	}

	/**
	 * Create a draft scratchpad for a core transactional email.
	 *
	 * @param string $email_id Core transactional email ID.
	 * @return int The scratchpad post ID.
	 */
	private function create_scratchpad( string $email_id ): int {
		$email = $this->posts_manager->get_email_by_id( $email_id );
		$this->assertNotNull( $email, 'The core transactional email must resolve' );

		return ( new \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator() )->create_draft( $email );
	}

	/**
	 * Invoke the private editor-open refresh hook with a fresh post object.
	 *
	 * @param int $post_id The post to open.
	 */
	private function invoke_maybe_refresh_scratchpad( int $post_id ): void {
		$method = new \ReflectionMethod( Integration::class, 'maybe_refresh_scratchpad' );
		$method->setAccessible( true );
		$method->invoke( $this->sut, get_post( $post_id ) );
	}

	/**
	 * Inject a WC_Email stub mimicking a third-party email whose get_subject()
	 * requires send-time state, and opt it into the block editor.
	 *
	 * @param string $email_id Email ID to inject.
	 * @return \WC_Email The injected stub.
	 */
	private function register_third_party_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_title', 'get_description', 'get_subject' ) )
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Booking style email' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email whose subject requires send-time state.' );
		$stub->method( 'get_subject' )->willThrowException( new \Error( 'Call to a member function get_order() on null' ) );
		$stub->id             = $email_id;
		$stub->template_base  = __DIR__ . '/WCTransactionalEmails/fixtures/';
		$stub->template_block = 'block/third-party-without-version.php';
		$stub->template_plain = 'plain/test-fallback.php';

		$class_key = 'WC_Test_Email_' . $email_id;

		$emails_container = \WC_Emails::instance();
		$reflection       = new \ReflectionClass( $emails_container );
		$property         = $reflection->getProperty( 'emails' );
		$property->setAccessible( true );
		$current               = $property->getValue( $emails_container );
		$current[ $class_key ] = $stub;
		$property->setValue( $emails_container, $current );

		$this->injected_email_keys[] = $class_key;

		add_filter(
			'woocommerce_transactional_emails_for_block_editor',
			static function ( array $emails ) use ( $email_id ): array {
				if ( ! in_array( $email_id, $emails, true ) ) {
					$emails[] = $email_id;
				}
				return $emails;
			}
		);

		return $stub;
	}

	/**
	 * @testdox Preview HTML for an email type renders the file template with the preview context applied.
	 */
	public function test_render_preview_html_for_email_type_returns_full_document(): void {
		$this->bootstrap_email_editor();
		$this->skip_if_unsupported_environment();

		$preview = $this->sut->render_preview_html_for_email_type( 'customer_processing_order' );

		$this->assertNotSame( '', $preview['subject'] );
		$this->assertStringContainsString( 'is now being processed', $preview['html'], 'The preview must contain the file template content' );
		$this->assertStringContainsString( 'All Rights Reserved', $preview['html'], 'The preview must be rendered through the email template chrome' );
		$this->assertStringNotContainsString( 'WOO_CONTENT', $preview['html'], 'The Woo content placeholder must be replaced with preview content' );
	}

	/**
	 * @testdox Preview HTML rendering throws for an unregistered email type.
	 */
	public function test_render_preview_html_for_unknown_email_type_throws(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->render_preview_html_for_email_type( 'this_type_is_not_registered' );
	}

	/**
	 * @testdox The preview page dies on a missing or invalid nonce.
	 */
	public function test_preview_page_requires_valid_nonce(): void {
		$_GET['preview_woo_block_email'] = 'true';
		$_GET['email_id']                = 'customer_processing_order';

		$this->expectException( \WPDieException::class );

		$this->sut->render_block_email_preview_page();
	}

	/**
	 * @testdox The preview page dies for users without manage_woocommerce.
	 */
	public function test_preview_page_requires_manage_woocommerce(): void {
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$_GET['preview_woo_block_email'] = 'true';
		$_GET['email_id']                = 'customer_processing_order';
		$_REQUEST['_wpnonce']            = wp_create_nonce( 'preview-woo-block-email' );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionMessage( 'permission' );

		$this->sut->render_block_email_preview_page();
	}

	/**
	 * @testdox The preview page dies for an unregistered email type.
	 */
	public function test_preview_page_dies_for_unknown_email_type(): void {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$_GET['preview_woo_block_email'] = 'true';
		$_GET['email_id']                = 'this_type_is_not_registered';
		$_REQUEST['_wpnonce']            = wp_create_nonce( 'preview-woo-block-email' );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionMessage( 'cannot be previewed' );

		$this->sut->render_block_email_preview_page();
	}

	/**
	 * Initialize the email editor package so block templates resolve during rendering.
	 */
	private function bootstrap_email_editor(): void {
		// setUp's add_option() no-ops when a previous tearDown left the option
		// at 'no'; the editor bootstrap requires the feature to be enabled.
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		wc_get_container()->get( Integration::class )->initialize();

		// The DI container runs TemplatesController::init() only on first
		// instantiation; when an earlier test resolved it, the WP test
		// framework's per-test filter restoration removed its registration
		// hook, so re-add it for the cached instance.
		$templates_controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplatesController::class );
		if ( false === has_filter( 'woocommerce_email_editor_register_templates', array( $templates_controller, 'register_templates' ) ) ) {
			$templates_controller->init();
		}
		\Automattic\WooCommerce\EmailEditor\Email_Editor_Container::container()->get( \Automattic\WooCommerce\EmailEditor\Bootstrap::class )->initialize();
	}

	/**
	 * Skip the test when the environment doesn't meet the editor's requirements.
	 */
	private function skip_if_unsupported_environment(): void {
		$dependency_check = \Automattic\WooCommerce\EmailEditor\Email_Editor_Container::container()->get( \Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check::class );
		if ( ! $dependency_check->are_dependencies_met() ) {
			$this->markTestSkipped( 'The test environment does not fulfill minimal requirements for the block email editor.' );
		}
	}

	/**
	 * Create a `woo_email` post carrying the email type meta.
	 *
	 * @param string $email_type  Email type to stamp into the meta.
	 * @param string $post_status Post status. Defaults to `auto-draft`.
	 * @return \WP_Post
	 */
	private function create_woo_email_post( string $email_type, string $post_status = 'auto-draft' ): \WP_Post {
		$post = $this->factory()->post->create_and_get(
			array(
				'post_title'  => 'Email post for ' . $email_type,
				'post_type'   => Integration::EMAIL_POST_TYPE,
				'post_status' => $post_status,
			)
		);
		update_post_meta( $post->ID, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, $email_type );

		return $post;
	}
}
