<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCEmailTemplateDivergenceDetector class.
 */
class WCEmailTemplateDivergenceDetectorTest extends \WC_Unit_Test_Case {
	/**
	 * Absolute path to the fixtures directory.
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

		$this->fixtures_base = __DIR__ . '/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();

		// Singleton caches survive test transaction rollback and would otherwise leak
		// stale post_id <-> email_type mappings into subsequent tests.
		$this->posts_manager->clear_caches();
		WCEmailTemplateSyncRegistry::reset_cache();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		remove_all_filters( 'woocommerce_transactional_emails_for_block_editor' );
		remove_all_filters( 'woocommerce_email_content_post_data' );

		WCEmailTemplateSyncRegistry::reset_cache();
		WCEmailTemplateDivergenceDetector::set_logger( null );

		delete_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * classify_post() returns each of the three ladder states when fed the corresponding inputs.
	 *
	 * This is the pure unit-level coverage of the classifier; integration coverage is
	 * provided by the remaining tests. Kept as a single method rather than a data provider
	 * because all three scenarios derive from the same fixture-email setup and benefit
	 * from a shared `currentCoreHash`.
	 *
	 * Scenarios exercise the two independent classification axes:
	 *   - has core moved since stamping? (compare currentCoreHash vs. storedSourceHash)
	 *   - has the merchant edited the post since stamping? (compare currentPostHash vs. storedSourceHash)
	 */
	public function test_classification_ladder_covers_all_three_outcomes(): void {
		$email             = $this->register_fixture_email( 'wc_test_divergence_ladder' );
		$canonical_content = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$current_core_hash = sha1( $canonical_content );

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			WCEmailTemplateDivergenceDetector::classify_post(
				1,
				$email,
				array(
					'post_content'       => $canonical_content,
					'stored_source_hash' => $current_core_hash,
				)
			),
			'Core unchanged and post matches stamp must be in_sync.'
		);

		// Core has moved (stored !== current core) and merchant has not edited the post
		// (post_content still matches the stored stamp) → safe to auto-apply new core.
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
			WCEmailTemplateDivergenceDetector::classify_post(
				1,
				$email,
				array(
					'post_content'       => 'stamped-content-from-older-version',
					'stored_source_hash' => sha1( 'stamped-content-from-older-version' ),
				)
			),
			'Core moved but post still matches stamp must be core_updated_uncustomized.'
		);

		// Core has moved AND merchant edited the post (post_content no longer matches
		// the stored stamp) → merchant customisations would be overwritten by auto-apply.
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			WCEmailTemplateDivergenceDetector::classify_post(
				1,
				$email,
				array(
					'post_content'       => 'merchant-edited content that diverges from the stamp',
					'stored_source_hash' => sha1( 'stamped-content-from-older-version' ),
				)
			),
			'Core moved and merchant edited post must be core_updated_customized.'
		);
	}

	/**
	 * Running the sweep twice on unchanged state must write the status meta at most once.
	 *
	 * Guards the equality-gated upsert in run_sweep(): the second sweep observes the status
	 * meta written by the first run and short-circuits, so no second write reaches the DB.
	 */
	public function test_second_sweep_on_unchanged_state_writes_zero_rows(): void {
		$email_id = 'wc_test_divergence_idempotency';
		$post_id  = $this->generate_stamped_post( $email_id );

		$write_count = 0;
		$counter     = static function ( $check, int $object_id, string $meta_key ) use ( &$write_count, $post_id ) {
			if ( $object_id === $post_id && WCEmailTemplateDivergenceDetector::STATUS_META_KEY === $meta_key ) {
				++$write_count;
			}
			return $check;
		};
		add_filter( 'update_post_metadata', $counter, 10, 3 );

		WCEmailTemplateDivergenceDetector::run_sweep();
		$writes_after_first_sweep = $write_count;

		WCEmailTemplateDivergenceDetector::run_sweep();
		$writes_after_second_sweep = $write_count;

		remove_filter( 'update_post_metadata', $counter, 10 );

		$this->assertSame( 1, $writes_after_first_sweep, 'First sweep should write the status meta exactly once.' );
		$this->assertSame( 1, $writes_after_second_sweep, 'Second sweep on unchanged state must be a no-op.' );
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true )
		);
	}

	/**
	 * Posts lacking _wc_email_template_source_hash must be skipped without writing status.
	 */
	public function test_post_with_missing_source_hash_is_skipped(): void {
		$email_id = 'wc_test_divergence_missing_hash';
		$post_id  = $this->generate_stamped_post( $email_id );

		// Simulate a legacy (pre-RSM-137) post by removing the source-hash stamp.
		delete_post_meta( $post_id, '_wc_email_template_source_hash' );

		WCEmailTemplateDivergenceDetector::run_sweep();

		$this->assertSame(
			'',
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Legacy posts without a stored source hash must not be classified.'
		);
	}

	/**
	 * When the stamping path captures post-filter content and the same filter stays active
	 * during the sweep, the detector must classify the post as in_sync by construction.
	 *
	 * This is the cross-issue contract between RSM-137 (stamp) and RSM-138 (detect):
	 * both sides route through {@see WCTransactionalEmailPostsGenerator::compute_canonical_post_content()}
	 * so any filter mutation that shifts post_content shifts both hashes identically.
	 */
	public function test_contract_equivalence_under_woocommerce_email_content_post_data_filter(): void {
		add_filter(
			'woocommerce_email_content_post_data',
			static function ( array $post_data ): array {
				$post_data['post_content'] = (string) ( $post_data['post_content'] ?? '' ) . "\n<!-- filter mutation -->";
				return $post_data;
			}
		);

		$email_id = 'wc_test_divergence_filter_contract';
		$post_id  = $this->generate_stamped_post( $email_id );

		WCEmailTemplateDivergenceDetector::run_sweep();

		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC,
			(string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true ),
			'Stamping hash and detection hash must agree when the content-post-data filter is active.'
		);
	}

	/**
	 * Build a WC_Email stub backed by the third-party-with-version.php fixture, inject it
	 * into WC_Emails::$emails, and opt the email ID into the block-editor filter so the
	 * sync registry picks it up. Resets the registry cache so subsequent reads see the
	 * fixture.
	 *
	 * @param string $email_id Email ID to assign to the stub.
	 * @return \WC_Email Registered fixture email instance.
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for divergence tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover divergence scenarios.' );
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
	 * Using the live generator ensures RSM-137's stamping logic actually runs — the detector
	 * then observes precisely what the generator would persist in production.
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

		// Sanity check: RSM-137 stamped all three sync meta keys.
		$this->assertNotSame( '', (string) get_post_meta( $post_id, '_wc_email_template_source_hash', true ) );
		$this->assertNotSame( '', (string) get_post_meta( $post_id, '_wc_email_template_version', true ) );
		$this->assertNotSame( '', (string) get_post_meta( $post_id, '_wc_email_last_synced_at', true ) );

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
