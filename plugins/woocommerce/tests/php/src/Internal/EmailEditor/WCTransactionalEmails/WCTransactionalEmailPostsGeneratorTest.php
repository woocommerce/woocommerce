<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\WooEmailTemplate;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
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
	 * Absolute path to the fixtures directory used for sync-stamping tests.
	 *
	 * @var string
	 */
	private string $fixtures_base;

	/**
	 * Keys of WC_Emails::$emails entries injected during the current test.
	 *
	 * @var string[]
	 */
	private array $injected_email_keys = array();

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		$this->email_generator  = new WCTransactionalEmailPostsGenerator();
		$this->template_manager = WCTransactionalEmailPostsManager::get_instance();
		$this->fixtures_base    = __DIR__ . '/fixtures/';

		// WCTransactionalEmailPostsManager is a process-wide singleton; its in-memory
		// post_id <-> email_type cache survives DB transaction rollback between tests
		// and would otherwise leak stale mappings whose backing posts were rolled back.
		$this->template_manager->clear_caches();

		WCEmailTemplateSyncRegistry::reset_cache();
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
	 * @testdox Should create a draft post carrying the email type and page template meta.
	 */
	public function test_create_draft_creates_draft_with_identity_meta(): void {
		$email_type = 'customer_new_account';
		$email      = $this->template_manager->get_email_by_id( $email_type );
		$this->assertInstanceOf( \WC_Email::class, $email );

		$post_id = $this->email_generator->create_draft( $email );

		$this->assertGreaterThan( 0, $post_id );
		$this->assertSame( 'draft', get_post_status( $post_id ), 'Lazily created email posts must stay drafts until published.' );
		$this->assertSame(
			$email_type,
			(string) get_post_meta( $post_id, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, true ),
			'_wc_email_type meta must link the draft to its email type.'
		);
		$this->assertSame(
			( new WooEmailTemplate() )->get_slug(),
			(string) get_post_meta( $post_id, '_wp_page_template', true ),
			'_wp_page_template meta must point at the Woo email template.'
		);
	}

	/**
	 * @testdox Should not write the email_type to post_id option mapping when creating a draft.
	 */
	public function test_create_draft_does_not_write_option_mapping(): void {
		$email_type = 'customer_new_account';
		$email      = $this->template_manager->get_email_by_id( $email_type );
		$this->assertInstanceOf( \WC_Email::class, $email );

		$post_id = $this->email_generator->create_draft( $email );

		$this->assertGreaterThan( 0, $post_id );
		$this->assertEmpty(
			$this->template_manager->get_email_template_post_id( $email_type ),
			'The option mapping must only be written on publish, not when the draft is created.'
		);
	}

	/**
	 * @testdox Should force draft status even when the content-post-data filter sets publish.
	 */
	public function test_create_draft_forces_draft_status_over_filter(): void {
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( array $post_data ): array {
				$post_data['post_status'] = 'publish';
				return $post_data;
			}
		);

		$email = $this->template_manager->get_email_by_id( 'customer_new_account' );
		$this->assertInstanceOf( \WC_Email::class, $email );

		$post_id = $this->email_generator->create_draft( $email );

		$this->assertGreaterThan( 0, $post_id );
		$this->assertSame(
			'draft',
			get_post_status( $post_id ),
			'The post status is system-owned and must not be overridable by the woocommerce_email_content_post_data filter.'
		);
	}

	/**
	 * Core email is stamped with all sync meta keys, and the hash is self-consistent with post_content.
	 */
	public function test_core_email_is_stamped_with_all_sync_meta_keys(): void {
		$email = $this->template_manager->get_email_by_id( 'customer_new_account' );
		$this->assertInstanceOf( \WC_Email::class, $email );

		$post_id = $this->email_generator->create_draft( $email );

		$this->assertGreaterThan( 0, $post_id );

		$version   = (string) get_post_meta( $post_id, '_wc_email_template_version', true );
		$hash      = (string) get_post_meta( $post_id, '_wc_email_template_source_hash', true );
		$synced_at = (string) get_post_meta( $post_id, '_wc_email_last_synced_at', true );

		$this->assertNotSame( '', $version, '_wc_email_template_version should be populated for a core email.' );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+(?:\.\d+)?/', $version, 'Version should be semver-ish.' );

		$post_content = (string) get_post( $post_id )->post_content;
		$this->assertSame(
			sha1( $post_content ),
			$hash,
			'_wc_email_template_source_hash must equal sha1() of the stored post_content.'
		);

		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
			$synced_at,
			'_wc_email_last_synced_at should be a GMT MySQL-format timestamp.'
		);

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Freshly created posts must be stamped in_sync.'
		);
	}

	/**
	 * @testdox Should stamp _wc_email_template_last_core_render meta with the canonical post_content at creation time.
	 */
	public function test_create_draft_stamps_last_core_render_meta(): void {
		$email = $this->template_manager->get_email_by_id( 'customer_on_hold_order' );
		$this->assertInstanceOf( \WC_Email::class, $email );

		$post_id = $this->email_generator->create_draft( $email );

		$this->assertGreaterThan( 0, $post_id );

		$stored_render = (string) get_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY,
			true
		);

		$this->assertNotSame(
			'',
			$stored_render,
			'_wc_email_template_last_core_render should be populated at creation time.'
		);

		$this->assertSame(
			WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email ),
			$stored_render,
			'last_core_render must equal compute_canonical_post_content() output.'
		);
	}

	/**
	 * Emails that are opted in for the block editor but whose templates lack a parseable @version
	 * header are absent from the sync registry: they get no version/synced-at meta, but the
	 * source hash is still stamped — `was_never_edited()` checks depend on it for every email.
	 */
	public function test_email_absent_from_registry_gets_hash_but_no_version_meta(): void {
		$email_id = 'wc_test_email_no_version';
		$email    = $this->register_third_party_email_without_version( $email_id );

		WCEmailTemplateSyncRegistry::reset_cache();

		$post_id = $this->email_generator->create_draft( $email );

		$this->assertGreaterThan( 0, $post_id );

		$this->assertSame( '', (string) get_post_meta( $post_id, '_wc_email_template_version', true ) );
		$this->assertSame( '', (string) get_post_meta( $post_id, '_wc_email_last_synced_at', true ) );

		$post_content = (string) get_post( $post_id )->post_content;
		$this->assertSame(
			sha1( $post_content ),
			(string) get_post_meta( $post_id, '_wc_email_template_source_hash', true ),
			'The source hash must be stamped even for emails outside the sync registry.'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * Regression guard: every core transactional email that is actually loaded by WC_Emails
	 * must expose a parseable @version header through the sync registry.
	 *
	 * This replaces a runtime throw in the registry and catches template drift (missing or
	 * malformed @version) at CI time. Feature-gated emails may not be loaded in the test
	 * process, so we intersect against the actually-registered email IDs.
	 */
	public function test_every_loaded_core_template_has_parseable_version(): void {
		WCEmailTemplateSyncRegistry::reset_cache();

		$registered_email_ids = array_map(
			static fn ( \WC_Email $email ): string => (string) $email->id,
			array_values( \WC_Emails::instance()->get_emails() )
		);

		$core_emails_to_check = array_intersect(
			WCTransactionalEmails::get_core_transactional_emails(),
			$registered_email_ids
		);

		$this->assertNotEmpty(
			$core_emails_to_check,
			'Expected at least one core transactional email to be registered with WC_Emails during the test run.'
		);

		foreach ( $core_emails_to_check as $email_id ) {
			$config = WCEmailTemplateSyncRegistry::get_email_sync_config( (string) $email_id );

			$this->assertNotNull(
				$config,
				sprintf( 'Core email "%s" must be resolvable through the sync registry.', $email_id )
			);
			$this->assertIsArray( $config );
			$this->assertArrayHasKey( 'version', $config );
			$this->assertMatchesRegularExpression(
				'/^\d+\.\d+(?:\.\d+)?/',
				(string) $config['version'],
				sprintf( 'Core email "%s" must expose a semver-ish @version header.', $email_id )
			);
		}
	}

	/**
	 * Inject a WC_Email stub whose block template has no parseable @version header into
	 * WC_Emails::$emails and opt it in via the block-editor filter.
	 *
	 * @param string $email_id Email ID to inject.
	 * @return \WC_Email The injected stub.
	 */
	private function register_third_party_email_without_version( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Third-party test email' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email without a parseable @version header.' );
		$stub->id             = $email_id;
		$stub->template_base  = $this->fixtures_base;
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
	 * @testdox Deprecated generation methods are no-ops that trigger a deprecation notice.
	 */
	public function test_deprecated_generation_methods_are_noops(): void {
		$generator_class = WCTransactionalEmailPostsGenerator::class;
		$this->setExpectedDeprecated( $generator_class . '::initialize' );
		$this->setExpectedDeprecated( $generator_class . '::init_default_transactional_emails' );
		$this->setExpectedDeprecated( $generator_class . '::generate_initial_email_templates' );
		$this->setExpectedDeprecated( $generator_class . '::generate_email_templates' );

		$this->email_generator->initialize();
		$this->email_generator->init_default_transactional_emails();
		$this->assertFalse( $this->email_generator->generate_initial_email_templates() );
		$this->assertFalse( $this->email_generator->generate_email_templates( array( 'customer_processing_order' ) ) );

		$this->assertFalse(
			$this->template_manager->get_email_template_post_id( 'customer_processing_order' ),
			'The deprecated no-ops must not create posts or mappings'
		);
	}

	/**
	 * @testdox Deprecated generate_email_template_if_not_exists() creates a published, mapped post and is idempotent.
	 */
	public function test_deprecated_generate_email_template_if_not_exists_creates_published_mapped_post(): void {
		$this->setExpectedDeprecated( WCTransactionalEmailPostsGenerator::class . '::generate_email_template_if_not_exists' );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( 'customer_processing_order' );

		$this->assertIsInt( $post_id );
		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );
		$this->assertSame( 'publish', $post->post_status );
		$this->assertSame( $post_id, $this->template_manager->get_email_template_post_id( 'customer_processing_order' ) );

		$this->assertSame(
			$post_id,
			$this->email_generator->generate_email_template_if_not_exists( 'customer_processing_order' ),
			'A second call must return the existing post instead of creating another one'
		);

		$this->assertFalse(
			$this->email_generator->generate_email_template_if_not_exists( 'this_email_type_is_not_registered' ),
			'Unregistered email types must not create posts'
		);
	}

	/**
	 * @testdox Deprecated generate_email_template_if_not_exists() replaces a stale mapping pointing at a deleted post.
	 */
	public function test_deprecated_generate_email_template_if_not_exists_replaces_stale_mapping(): void {
		$this->setExpectedDeprecated( WCTransactionalEmailPostsGenerator::class . '::generate_email_template_if_not_exists' );

		// Stale mapping: the post behind it no longer exists.
		$this->template_manager->save_email_template_post_id( 'customer_processing_order', 999999 );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( 'customer_processing_order' );

		$this->assertIsInt( $post_id );
		$this->assertNotSame( 999999, $post_id, 'A stale mapping must not be returned as a usable post ID' );
		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );
		$this->assertSame( 'publish', $post->post_status );
		$this->assertSame(
			$post_id,
			$this->template_manager->get_email_template_post_id( 'customer_processing_order' ),
			'The stale mapping must be replaced with the fresh post'
		);
	}

	/**
	 * Cleanup after test.
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

		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_filters( 'woocommerce_email_content_post_data' );

		WCEmailTemplateSyncRegistry::reset_cache();

		parent::tearDown();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
	}
}
