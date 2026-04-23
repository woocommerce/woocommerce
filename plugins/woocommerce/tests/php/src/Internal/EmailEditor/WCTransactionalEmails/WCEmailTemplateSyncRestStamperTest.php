<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRestStamper;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for WCEmailTemplateSyncRestStamper.
 */
class WCEmailTemplateSyncRestStamperTest extends \WC_Unit_Test_Case {

	/**
	 * @var WCEmailTemplateSyncRestStamper
	 */
	private WCEmailTemplateSyncRestStamper $stamper;

	/**
	 * @var WCTransactionalEmailPostsGenerator
	 */
	private WCTransactionalEmailPostsGenerator $email_generator;

	/**
	 * @var WCTransactionalEmailPostsManager
	 */
	private WCTransactionalEmailPostsManager $template_manager;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );

		$this->email_generator  = new WCTransactionalEmailPostsGenerator();
		$this->template_manager = WCTransactionalEmailPostsManager::get_instance();

		// The singleton cache survives DB rollback between tests.
		$this->template_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();

		$this->stamper = new WCEmailTemplateSyncRestStamper( $this->template_manager );
	}

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		WCEmailTemplateSyncRegistry::reset_cache();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
		delete_transient( 'wc_email_editor_initial_templates_generated' );
		parent::tearDown();
	}

	/**
	 * Reset scenario: saving content that matches the core template refreshes all sync meta.
	 */
	public function test_reset_refreshes_all_sync_meta(): void {
		$email_type = 'customer_new_account';

		$this->email_generator->init_default_transactional_emails();
		$this->template_manager->delete_email_template( $email_type );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( $email_type );
		$this->assertIsInt( $post_id );

		$email = $this->resolve_wc_email( $email_type );
		$this->assertNotNull( $email );

		// Simulate the client saving canonical core content (reset scenario).
		$canonical_content = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $canonical_content,
			)
		);

		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );

		// Backdate the meta so we can assert the stamper writes a newer value without sleeping.
		$before_synced_at = '2000-01-01 00:00:00';
		update_post_meta( $post_id, '_wc_email_last_synced_at', $before_synced_at );

		$request = new \WP_REST_Request( 'PUT', '/wp/v2/woo_email/' . $post_id );
		$this->stamper->maybe_stamp_after_rest_update( $post, $request, false );

		$version   = (string) get_post_meta( $post_id, '_wc_email_template_version', true );
		$hash      = (string) get_post_meta( $post_id, '_wc_email_template_source_hash', true );
		$synced_at = (string) get_post_meta( $post_id, '_wc_email_last_synced_at', true );
		$status    = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true );

		$this->assertNotSame( '', $version, '_wc_email_template_version must be populated after reset.' );
		$this->assertSame( sha1( $post->post_content ), $hash, '_wc_email_template_source_hash must equal sha1(post_content).' );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $synced_at, '_wc_email_last_synced_at must be a GMT timestamp.' );
		$this->assertGreaterThan( $before_synced_at, $synced_at, '_wc_email_last_synced_at must be refreshed.' );
		$this->assertSame( WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC, $status, '_wc_email_template_status must be set to in_sync.' );
	}

	/**
	 * Ordinary merchant edit: content does not match the core render → meta must NOT be touched.
	 */
	public function test_ordinary_save_does_not_trigger_reset_stamping(): void {
		$email_type = 'customer_new_account';

		$this->email_generator->init_default_transactional_emails();
		$this->template_manager->delete_email_template( $email_type );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( $email_type );
		$this->assertIsInt( $post_id );

		$original_hash      = (string) get_post_meta( $post_id, '_wc_email_template_source_hash', true );
		$original_synced_at = (string) get_post_meta( $post_id, '_wc_email_last_synced_at', true );
		$this->assertNotSame( '', $original_hash, 'Generator must stamp _wc_email_template_source_hash so the no-change assertion is meaningful.' );
		$this->assertNotSame( '', $original_synced_at, 'Generator must stamp _wc_email_last_synced_at so the no-change assertion is meaningful.' );

		// Simulate a merchant edit — content differs from the core render.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<!-- wp:paragraph --><p>Customized by merchant</p><!-- /wp:paragraph -->',
			)
		);

		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );

		$request = new \WP_REST_Request( 'PUT', '/wp/v2/woo_email/' . $post_id );
		$this->stamper->maybe_stamp_after_rest_update( $post, $request, false );

		$this->assertSame(
			$original_hash,
			(string) get_post_meta( $post_id, '_wc_email_template_source_hash', true ),
			'_wc_email_template_source_hash must not change on an ordinary merchant edit.'
		);
		$this->assertSame(
			$original_synced_at,
			(string) get_post_meta( $post_id, '_wc_email_last_synced_at', true ),
			'_wc_email_last_synced_at must not change on an ordinary merchant edit.'
		);
		$this->assertSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'_wc_email_template_status must not be set on an ordinary merchant edit.'
		);
	}

	/**
	 * Post creation (not an update) must be skipped — the generator already stamps on create.
	 */
	public function test_creating_post_is_skipped(): void {
		$email_type = 'customer_new_account';

		$this->email_generator->init_default_transactional_emails();
		$this->template_manager->delete_email_template( $email_type );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( $email_type );
		$this->assertIsInt( $post_id );

		$email = $this->resolve_wc_email( $email_type );
		$this->assertNotNull( $email );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email ),
			)
		);

		// Backdate the meta so a spurious stamp would produce a detectably different value.
		$original_synced_at = '2000-01-01 00:00:00';
		update_post_meta( $post_id, '_wc_email_last_synced_at', $original_synced_at );

		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );

		$request = new \WP_REST_Request( 'POST', '/wp/v2/woo_email' );
		$this->stamper->maybe_stamp_after_rest_update( $post, $request, true );

		$this->assertSame(
			$original_synced_at,
			(string) get_post_meta( $post_id, '_wc_email_last_synced_at', true ),
			'Stamper must not run when $creating is true.'
		);
	}

	/**
	 * Posts for email IDs absent from the sync registry must not be stamped.
	 */
	public function test_email_not_in_registry_is_skipped(): void {
		$email_type = 'customer_new_account';

		$this->email_generator->init_default_transactional_emails();
		$this->template_manager->delete_email_template( $email_type );

		$post_id = $this->email_generator->generate_email_template_if_not_exists( $email_type );
		$this->assertIsInt( $post_id );

		// Forcibly clear the registry so no emails are registered.
		WCEmailTemplateSyncRegistry::reset_cache();
		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		add_filter( 'woocommerce_transactional_emails_for_block_editor', '__return_empty_array' );

		$email = $this->resolve_wc_email( $email_type );
		$this->assertNotNull( $email );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email ),
			)
		);

		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );

		$request = new \WP_REST_Request( 'PUT', '/wp/v2/woo_email/' . $post_id );
		$this->stamper->maybe_stamp_after_rest_update( $post, $request, false );

		$this->assertSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'_wc_email_template_status must not be set for an email absent from the registry.'
		);
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
