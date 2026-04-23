<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

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
		// and would otherwise make generate_email_template_if_not_exists() return a
		// stale post ID whose backing post was rolled back.
		$this->template_manager->clear_caches();

		WCEmailTemplateSyncRegistry::reset_cache();
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
	 * Core email is stamped with all three sync meta keys, and the hash is self-consistent with post_content.
	 */
	public function test_core_email_is_stamped_with_all_three_meta_keys(): void {
		$email_type = 'customer_new_account';

		$this->email_generator->init_default_transactional_emails();
		$this->template_manager->delete_email_template( $email_type );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( $email_type );

		$this->assertIsInt( $post_id );
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
	}

	/**
	 * Emails that are opted in for the block editor but whose templates lack a parseable @version
	 * header are absent from the sync registry and must not be stamped.
	 */
	public function test_email_absent_from_registry_is_not_stamped(): void {
		$email_id = 'wc_test_email_no_version';
		$this->register_third_party_email_without_version( $email_id );

		WCEmailTemplateSyncRegistry::reset_cache();

		$this->email_generator->init_default_transactional_emails();
		$this->template_manager->delete_email_template( $email_id );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( $email_id );

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$this->assertSame( '', (string) get_post_meta( $post_id, '_wc_email_template_version', true ) );
		$this->assertSame( '', (string) get_post_meta( $post_id, '_wc_email_template_source_hash', true ) );
		$this->assertSame( '', (string) get_post_meta( $post_id, '_wc_email_last_synced_at', true ) );
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
	 */
	private function register_third_party_email_without_version( string $email_id ): void {
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

		WCEmailTemplateSyncRegistry::reset_cache();

		parent::tearDown();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
		delete_transient( 'wc_email_editor_initial_templates_generated' );
	}
}
