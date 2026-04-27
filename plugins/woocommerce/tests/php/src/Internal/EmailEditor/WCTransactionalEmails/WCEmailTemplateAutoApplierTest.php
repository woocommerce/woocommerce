<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateAutoApplier;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCEmailTemplateAutoApplier class.
 */
class WCEmailTemplateAutoApplierTest extends \WC_Unit_Test_Case {
	/**
	 * Absolute path to the fixtures directory shared with the divergence detector tests.
	 *
	 * @var string
	 */
	private string $fixtures_base;

	/**
	 * Keys injected into \WC_Emails::$emails during the current test.
	 *
	 * @var string[]
	 */
	private array $injected_email_keys = array();

	/**
	 * Transactional email post manager singleton.
	 *
	 * @var WCTransactionalEmailPostsManager
	 */
	private WCTransactionalEmailPostsManager $posts_manager;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		update_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION, 'yes' );

		// Reuse the divergence-detector fixture file — same shape, same @version header.
		$this->fixtures_base = dirname( __DIR__ ) . '/WCTransactionalEmails/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();

		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();

		// In integration runtime the email editor's Templates_Registry registers
		// the `wooemailtemplate` slug via register_block_template() during admin
		// bootstrap. Unit tests skip that bootstrap, so wp_update_post( $wp_error = true )
		// would reject the slug as `invalid_page_template`. Whitelist it via the
		// theme_{post_type}_templates filter for the duration of the test.
		add_filter( 'theme_woo_email_templates', array( $this, 'whitelist_email_page_template' ) );
		add_filter( 'theme_templates', array( $this, 'whitelist_email_page_template' ) );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		remove_filter( 'theme_woo_email_templates', array( $this, 'whitelist_email_page_template' ) );
		remove_filter( 'theme_templates', array( $this, 'whitelist_email_page_template' ) );
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_filters( 'woocommerce_email_content_post_data' );

		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateAutoApplier::set_logger( null );

		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * Filter callback — register the `wooemailtemplate` slug so wp_update_post
	 * with `$wp_error = true` does not bail with `invalid_page_template`.
	 *
	 * @param array $templates Existing page templates keyed by slug.
	 * @return array
	 */
	public function whitelist_email_page_template( $templates ): array {
		$templates                       = is_array( $templates ) ? $templates : array();
		$templates['wooemailtemplate']   = 'Woo Email Template';
		return $templates;
	}

	/**
	 * apply_to_post() on a sync-enabled post that still matches its stamp must
	 * rewrite content to the canonical render and stamp all four sync meta keys.
	 */
	public function test_apply_to_post_writes_canonical_content_and_stamps_meta(): void {
		$email_id = 'wc_test_auto_apply_happy_path';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		// Simulate a core-template change by mutating the canonical content via the
		// woocommerce_email_content_post_data filter for the duration of this test —
		// keeps the stored hash from RSM-137 stamping intact while making the
		// auto-applier's recomputed canonical hash differ.
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( array $post_data ) use ( $email_id ): array {
				if ( ( $post_data['post_name'] ?? '' ) === $email_id ) {
					$post_data['post_content'] = (string) ( $post_data['post_content'] ?? '' ) . "\n<!-- new core release -->";
				}
				return $post_data;
			}
		);

		$expected_canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$expected_hash      = sha1( $expected_canonical );
		$expected_version   = (string) WCEmailTemplateSyncRegistry::get_email_sync_config( $email_id )['version'];

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertIsArray( $result, 'Atom must return an array on success.' );
		$this->assertArrayHasKey( 'content', $result );
		$this->assertSame( $expected_canonical, $result['content'] );
		$this->assertSame( $expected_version, $result['version'] );
		$this->assertSame( $expected_hash, $result['source_hash'] );
		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC, $result['status'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $result['synced_at'] );

		$post = get_post( $post_id );
		$this->assertSame( $expected_canonical, (string) $post->post_content, 'Post content must be the new canonical render.' );

		$this->assertSame( $expected_hash, (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ) );
		$this->assertSame( $expected_version, (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ) );
		$this->assertNotSame( '', (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ) );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * apply_to_post() with require_uncustomized=true must return a WP_Error and
	 * leave the post untouched when the merchant has edited it since stamping.
	 */
	public function test_apply_to_post_with_require_uncustomized_returns_wp_error_when_post_modified(): void {
		$email_id = 'wc_test_auto_apply_modified_since_stamp';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		// Simulate a merchant edit: rewrite post_content directly so its hash no longer
		// matches the stored stamp, but leave the meta keys in place.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<p>Merchant-edited content</p>',
			)
		);

		$pre_call_meta = array(
			'source_hash'    => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ),
			'version'        => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ),
			'last_synced_at' => (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ),
		);

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'post_modified_since_stamp', $result->get_error_code() );

		$post = get_post( $post_id );
		$this->assertSame( '<p>Merchant-edited content</p>', (string) $post->post_content, 'Atom must not rewrite content when hash gate fails.' );

		$this->assertSame( $pre_call_meta['source_hash'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true ) );
		$this->assertSame( $pre_call_meta['version'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, true ) );
		$this->assertSame( $pre_call_meta['last_synced_at'], (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, true ) );
	}

	/**
	 * apply_to_post() must return WP_Error('no_stored_hash') when the source-hash
	 * meta is missing, even if the post itself looks valid.
	 */
	public function test_apply_to_post_returns_wp_error_when_no_stored_hash(): void {
		$email_id = 'wc_test_auto_apply_no_stored_hash';
		$post_id  = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		delete_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY );
		$pre_call_content = (string) get_post( $post_id )->post_content;

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'no_stored_hash', $result->get_error_code() );

		$this->assertSame( $pre_call_content, (string) get_post( $post_id )->post_content );
	}

	/**
	 * apply_to_post() with require_uncustomized=true on a non-sync-enabled email
	 * must return WP_Error('not_sync_enabled') and not write anything.
	 */
	public function test_apply_to_post_for_non_sync_enabled_email_with_require_uncustomized_true(): void {
		$email_id = 'wc_test_auto_apply_non_sync_enabled_strict';

		// Generate a stamped post, then nuke its registry membership so the email is
		// no longer sync-enabled at apply time. Using a registry-cache reset keeps the
		// post itself intact (with all four meta keys) so we can assert no writes.
		$post_id = $this->generate_stamped_post( $email_id );

		$emails_by_id = $this->posts_manager->get_emails_by_id();
		$email        = $emails_by_id[ $email_id ];

		// Drop the email out of the block-editor opt-in filter for the rest of the test.
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		WCEmailTemplateSyncRegistry::reset_cache();

		$pre_call_content = (string) get_post( $post_id )->post_content;
		$pre_call_status  = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true );

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, $post_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'not_sync_enabled', $result->get_error_code() );

		$this->assertSame( $pre_call_content, (string) get_post( $post_id )->post_content );
		$this->assertSame( $pre_call_status, (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ) );
	}

	/**
	 * apply_to_post() must return WP_Error('post_not_found') when the post ID
	 * doesn't resolve to a woo_email post.
	 */
	public function test_apply_to_post_returns_wp_error_when_post_not_found(): void {
		// Register a sync-enabled fixture email but do NOT generate a post for it.
		$email_id = 'wc_test_auto_apply_post_not_found';
		$email    = $this->register_fixture_email( $email_id );

		$result = WCEmailTemplateAutoApplier::apply_to_post( $email, 999999999 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'post_not_found', $result->get_error_code() );
	}

	/**
	 * Build a WC_Email stub backed by the third-party-with-version.php fixture, inject it
	 * into WC_Emails::$emails, and opt the email ID into the block-editor filter so the
	 * sync registry picks it up.
	 *
	 * @param string $email_id Email ID to assign to the stub.
	 * @return \WC_Email Registered fixture email instance.
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for auto-applier tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover auto-apply scenarios.' );
		$stub->id             = $email_id;
		$stub->template_base  = $this->fixtures_base;
		$stub->template_block = 'block/third-party-with-version.php';
		$stub->template_plain = null;

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
	 * Drive the real generator flow to produce a stamped woo_email post for the given fixture.
	 *
	 * @param string $email_id Email ID to generate a post for.
	 * @return int The generated post ID.
	 */
	private function generate_stamped_post( string $email_id ): int {
		$this->register_fixture_email( $email_id );

		$generator = new WCTransactionalEmailPostsGenerator();
		$generator->init_default_transactional_emails();
		$this->posts_manager->delete_email_template( $email_id );

		$post_id = $generator->generate_email_template_if_not_exists( $email_id );

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		return $post_id;
	}

	/**
	 * Remove any stubs we injected into WC_Emails::$emails during the test.
	 */
	private function cleanup_injected_emails(): void {
		if ( empty( $this->injected_email_keys ) ) {
			return;
		}

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
}
