<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Emails;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Emails\EmailListingRestController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\WooEmailTemplate;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the recreate-email-post endpoint of the EmailListingRestController class.
 */
class EmailListingRestControllerTest extends WC_Unit_Test_Case {

	/**
	 * Email type used throughout the tests. Registered by core and covered by the sync registry.
	 *
	 * @var string
	 */
	const EMAIL_ID = 'customer_processing_order';

	/**
	 * The System Under Test.
	 *
	 * @var EmailListingRestController
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

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );

		// Eagerly boot \WC_Emails so registered transactional emails resolve.
		\WC_Emails::instance();

		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();

		// Pre-seed the flush marker so tests don't pay for flush_rewrite_rules().
		update_option( EmailListingRestController::REWRITE_FLUSH_OPTION, (string) Constants::get_constant( 'WC_VERSION' ), false );

		$this->sut = new EmailListingRestController();
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

		remove_all_filters( 'woocommerce_email_block_template_html' );
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * @testdox Should create a draft with email type, template and sync metas, without writing the option mapping.
	 */
	public function test_creates_draft_with_metas_and_sync_stamps(): void {
		$response = $this->call_recreate_email_post( self::EMAIL_ID );

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'message', $response );
		$this->assertIsString( $response['message'] );
		$this->assertArrayHasKey( 'post_id', $response );
		$this->assertIsString( $response['post_id'] );

		$post = get_post( (int) $response['post_id'] );
		$this->assertInstanceOf( \WP_Post::class, $post );
		$this->assertSame( 'draft', $post->post_status, 'The scratchpad must be created as a draft' );
		$this->assertSame(
			self::EMAIL_ID,
			get_post_meta( $post->ID, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, true ),
			'The draft must carry the email type meta'
		);
		$this->assertSame(
			( new WooEmailTemplate() )->get_slug(),
			get_post_meta( $post->ID, '_wp_page_template', true ),
			'The draft must carry the email template slug meta'
		);

		$this->assertSame(
			sha1( (string) $post->post_content ),
			get_post_meta( $post->ID, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'The source hash must match the persisted post content'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			get_post_meta( $post->ID, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
		$this->assertNotSame(
			'',
			(string) get_post_meta( $post->ID, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'Registry-covered emails must be stamped with the template version'
		);

		$this->assertFalse(
			get_option( 'woocommerce_email_templates_' . self::EMAIL_ID . '_post_id' ),
			'The option mapping must not be written for a draft — only publishing writes it'
		);
	}

	/**
	 * An edited scratchpad is reused as-is, never refreshed: refreshing would
	 * destroy the merchant's unsaved work. Customers are unaffected — drafts
	 * never render (sends use the file template until the post is published),
	 * and after publishing, core template updates surface through the regular
	 * update-propagation flow.
	 *
	 * @testdox Should reuse the same scratchpad post when it was edited since creation.
	 */
	public function test_second_call_reuses_scratchpad_after_edit(): void {
		$first_response = $this->call_recreate_email_post( self::EMAIL_ID );
		$this->assertIsArray( $first_response );
		$post_id = (int) $first_response['post_id'];

		$edited_content = '<!-- wp:paragraph --><p>MERCHANT_EDITED_MARKER</p><!-- /wp:paragraph -->';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $edited_content,
			)
		);

		$second_response = $this->call_recreate_email_post( self::EMAIL_ID );

		$this->assertIsArray( $second_response );
		$this->assertSame( (string) $post_id, $second_response['post_id'], 'An edited scratchpad must be reused, keeping the same post ID' );

		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );
		$this->assertStringContainsString( 'MERCHANT_EDITED_MARKER', $post->post_content, 'The edited content must survive the second call' );
	}

	/**
	 * @testdox Should refresh a never-edited scratchpad in place so it reflects the current file template.
	 */
	public function test_second_call_refreshes_untouched_scratchpad_in_place(): void {
		$first_response = $this->call_recreate_email_post( self::EMAIL_ID );
		$this->assertIsArray( $first_response );
		$first_post_id = (int) $first_response['post_id'];

		// The file template moves between the two calls; a never-edited scratchpad must pick that up.
		add_filter(
			'woocommerce_email_block_template_html',
			static function ( $template_html ) {
				return $template_html . "\n<!-- wp:paragraph --><p>FRESH_TEMPLATE_MARKER</p><!-- /wp:paragraph -->";
			}
		);
		// The title is system-owned and must move with the content; the same
		// post-data filter that customizes creation also drives the refresh.
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( $post_data ) {
				$post_data['post_title'] = 'Fresh Template Title';
				return $post_data;
			}
		);

		// Simulate a template-version bump since creation: the refresh must
		// restamp the version meta along with the content.
		update_post_meta( $first_post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, '0.0.1' );

		$second_response = $this->call_recreate_email_post( self::EMAIL_ID );

		$this->assertIsArray( $second_response );
		$second_post_id = (int) $second_response['post_id'];

		// The post ID stays stable — another admin may have the editor open on it.
		$this->assertSame( $first_post_id, $second_post_id, 'A never-edited scratchpad must be reused under the same post ID' );

		$refreshed_post = get_post( $second_post_id );
		$this->assertInstanceOf( \WP_Post::class, $refreshed_post );
		$this->assertStringContainsString( 'FRESH_TEMPLATE_MARKER', $refreshed_post->post_content, 'The refreshed scratchpad must contain the current file template content' );
		$this->assertSame( 'Fresh Template Title', $refreshed_post->post_title, 'The system-owned title must be refreshed along with the content' );

		// The refresh must move the sync baseline along with the content, so the
		// scratchpad still counts as never-edited on subsequent calls.
		$stored_hash = get_post_meta( $second_post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true );
		$this->assertSame( sha1( (string) $refreshed_post->post_content ), $stored_hash, 'The source hash must be restamped for the refreshed content' );

		$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( self::EMAIL_ID );
		$this->assertNotNull( $sync_config );
		$this->assertSame(
			(string) $sync_config['version'],
			get_post_meta( $second_post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'The template version must be restamped along with the refreshed content'
		);

		// The refresh passes `page_template => ''` so core skips template
		// handling; this guards against a core behavior change that would
		// clear the association on an empty value.
		$this->assertSame(
			( new WooEmailTemplate() )->get_slug(),
			get_post_meta( $second_post_id, '_wp_page_template', true ),
			'The template meta must survive a content refresh'
		);
	}

	/**
	 * @testdox Should refresh the scratchpad when only the title changed, leaving the content identical.
	 */
	public function test_second_call_refreshes_scratchpad_on_title_only_change(): void {
		$first_response = $this->call_recreate_email_post( self::EMAIL_ID );
		$this->assertIsArray( $first_response );
		$first_post_id = (int) $first_response['post_id'];

		// Only the title moves — e.g. a plugin update renaming the email; the
		// content-equality early return must not skip the refresh.
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( $post_data ) {
				$post_data['post_title'] = 'Renamed Email Title';
				return $post_data;
			}
		);

		$second_response = $this->call_recreate_email_post( self::EMAIL_ID );
		$this->assertIsArray( $second_response );

		$refreshed_post = get_post( $first_post_id );
		$this->assertSame( 'Renamed Email Title', $refreshed_post->post_title, 'A title-only change must still refresh the scratchpad' );
	}

	/**
	 * An email is outside the sync registry when its block template file has no
	 * parseable `@version` header — typically a third-party email that opted in
	 * via `woocommerce_transactional_emails_for_block_editor` without adopting
	 * the version-header convention. Such emails get no template-version meta
	 * and no update propagation, but scratchpad handling must work the same.
	 *
	 * @testdox Should preserve merchant edits on a scratchpad for an email outside the sync registry.
	 */
	public function test_non_sync_email_edited_scratchpad_is_preserved(): void {
		$email_id = 'wc_test_listing_email_no_version';
		$this->register_non_sync_email( $email_id );

		$first_response = $this->call_recreate_email_post( $email_id );
		$this->assertIsArray( $first_response );
		$post_id = (int) $first_response['post_id'];

		$edited_content = '<!-- wp:paragraph --><p>NON_SYNC_EDITED_MARKER</p><!-- /wp:paragraph -->';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $edited_content,
			)
		);

		$second_response = $this->call_recreate_email_post( $email_id );

		$this->assertIsArray( $second_response );
		$this->assertSame( (string) $post_id, $second_response['post_id'] );
		$this->assertStringContainsString(
			'NON_SYNC_EDITED_MARKER',
			(string) get_post( $post_id )->post_content,
			'A refresh must never overwrite merchant edits, also for emails outside the sync registry'
		);
	}

	/**
	 * @testdox Should keep refreshing an untouched scratchpad for an email outside the sync registry across repeated calls.
	 */
	public function test_non_sync_email_untouched_scratchpad_is_refreshed_repeatedly(): void {
		$email_id = 'wc_test_listing_email_no_version';
		$this->register_non_sync_email( $email_id );

		$first_response = $this->call_recreate_email_post( $email_id );
		$this->assertIsArray( $first_response );
		$post_id = (int) $first_response['post_id'];

		// A refresh bumps `post_modified`, so recognizing the scratchpad as
		// untouched afterwards depends on the source hash being stamped for
		// non-registry emails too. Two refresh rounds pin that.
		foreach ( array( 'FIRST_REFRESH_MARKER', 'SECOND_REFRESH_MARKER' ) as $marker ) {
			remove_all_filters( 'woocommerce_email_block_template_html' );
			add_filter(
				'woocommerce_email_block_template_html',
				static function ( $template_html ) use ( $marker ) {
					return $template_html . "\n<!-- wp:paragraph --><p>" . $marker . '</p><!-- /wp:paragraph -->';
				}
			);

			$response = $this->call_recreate_email_post( $email_id );

			$this->assertIsArray( $response );
			$this->assertSame( (string) $post_id, $response['post_id'] );
			$this->assertStringContainsString(
				$marker,
				(string) get_post( $post_id )->post_content,
				'An untouched non-sync scratchpad must pick up the current file template on every open'
			);
		}
	}

	/**
	 * @testdox Should return the existing post ID when a published mapped post exists.
	 */
	public function test_returns_existing_published_mapped_post_id(): void {
		$email = $this->posts_manager->get_email_by_id( self::EMAIL_ID );
		$this->assertInstanceOf( \WC_Email::class, $email );

		$generator = new WCTransactionalEmailPostsGenerator();
		$post_id   = $generator->create_draft( $email );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		// The mapping is normally written by the publish transition hook; write it explicitly
		// because Integration::register_hooks() did not run in this test.
		$this->posts_manager->save_email_template_post_id( self::EMAIL_ID, $post_id );

		$response = $this->call_recreate_email_post( self::EMAIL_ID );

		$this->assertIsArray( $response );
		$this->assertSame( (string) $post_id, $response['post_id'], 'A published mapped post must be returned as-is' );
		$this->assertIsString( $response['message'] );
	}

	/**
	 * @testdox Should stamp the rewrite-flush option with the current WC version when it is missing.
	 */
	public function test_endpoint_stamps_rewrite_flush_option(): void {
		delete_option( EmailListingRestController::REWRITE_FLUSH_OPTION );

		$response = $this->call_recreate_email_post( self::EMAIL_ID );
		$this->assertIsArray( $response );

		$this->assertSame(
			(string) Constants::get_constant( 'WC_VERSION' ),
			get_option( EmailListingRestController::REWRITE_FLUSH_OPTION ),
			'The endpoint must flush rewrite rules once per WC version and stamp the option'
		);
	}

	/**
	 * @testdox Should create a fresh draft when the mapped post was trashed.
	 */
	public function test_trashed_mapped_post_gets_fresh_draft(): void {
		$email = $this->posts_manager->get_email_by_id( self::EMAIL_ID );
		$this->assertInstanceOf( \WC_Email::class, $email );

		$generator = new WCTransactionalEmailPostsGenerator();
		$post_id   = $generator->create_draft( $email );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		$this->posts_manager->save_email_template_post_id( self::EMAIL_ID, $post_id );
		wp_trash_post( $post_id );

		$response = $this->call_recreate_email_post( self::EMAIL_ID );

		$this->assertIsArray( $response );
		$this->assertNotSame( (string) $post_id, $response['post_id'], 'A trashed mapped post must not be returned' );

		$new_post = get_post( (int) $response['post_id'] );
		$this->assertInstanceOf( \WP_Post::class, $new_post );
		$this->assertSame( 'draft', $new_post->post_status, 'A fresh draft must be created instead of the trashed post' );
	}

	/**
	 * @testdox Should return an error when the email type is not registered.
	 */
	public function test_returns_error_for_unregistered_email_type(): void {
		$response = $this->call_recreate_email_post( 'this_email_type_does_not_exist' );

		$this->assertWPError( $response );
		$this->assertSame( 'woocommerce_rest_email_post_generation_failed', $response->get_error_code() );
	}

	/**
	 * @testdox Deprecated initialize_template_generator() is a no-op that triggers a deprecation notice.
	 */
	public function test_deprecated_initialize_template_generator_is_noop(): void {
		$this->setExpectedDeprecated( EmailListingRestController::class . '::initialize_template_generator' );

		$this->sut->initialize_template_generator();
	}

	/**
	 * Inject a WC_Email stub whose block template lacks a parseable @version
	 * header, so the email is absent from the sync registry.
	 *
	 * @param string $email_id Email ID to inject.
	 * @return \WC_Email The injected stub.
	 */
	private function register_non_sync_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Third-party listing test email' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email without a parseable @version header.' );
		$stub->id             = $email_id;
		$stub->template_base  = dirname( __DIR__, 2 ) . '/EmailEditor/WCTransactionalEmails/fixtures/';
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

		WCEmailTemplateSyncRegistry::reset_cache();

		return $stub;
	}

	/**
	 * Call the recreate_email_post handler with a crafted request.
	 *
	 * @param string $email_id The email ID request parameter.
	 * @return array|\WP_Error The handler response.
	 */
	private function call_recreate_email_post( string $email_id ) {
		$request = new WP_REST_Request( 'POST', '/wc-admin-email/settings/email/listing/recreate-email-post' );
		$request->set_param( 'email_id', $email_id );

		return $this->sut->recreate_email_post( $request );
	}
}
