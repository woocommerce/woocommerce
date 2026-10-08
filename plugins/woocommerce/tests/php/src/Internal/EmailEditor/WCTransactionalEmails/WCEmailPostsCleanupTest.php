<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailPostsCleanup;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WOOPLUG-6171 one-shot email posts cleanup migration.
 */
class WCEmailPostsCleanupTest extends \WC_Unit_Test_Case {
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

		// Eagerly boot \WC_Emails so the \WC_Email class is autoloaded before any
		// test reflects on it via getMockBuilder() / onlyMethods().
		\WC_Emails::instance();

		$this->fixtures_base = __DIR__ . '/fixtures/';
		$this->posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$this->posts_manager->clear_caches();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		$this->cleanup_injected_emails();

		remove_all_filters( 'woocommerce_email_block_template_html' );

		$this->posts_manager->clear_caches();
		delete_transient( WCEmailPostsCleanup::LEGACY_GENERATION_TRANSIENT );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * @testdox Should delete a published post whose content matches the current canonical render, together with its mapping.
	 */
	public function test_deletes_post_matching_current_canonical_render(): void {
		$email_id = 'wc_cleanup_canonical';
		$email    = $this->register_fixture_email( $email_id );

		$canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		// Edited timestamps on purpose: the canonical match must win regardless of timestamps.
		$post_id = $this->create_mapped_email_post( $email_id, $canonical, false );

		$result = WCEmailPostsCleanup::run();

		$this->assertFalse( $result, 'run() must return false (one-shot).' );
		$this->assertNull( get_post( $post_id ), 'A never-customized post must be hard-deleted' );
		$this->assertFalse( get_option( $this->option_name( $email_id ) ), 'The option mapping must be deleted with the post' );
	}

	/**
	 * @testdox Should delete a post still matching its stored source hash even when the canonical render moved on.
	 */
	public function test_deletes_post_matching_stored_source_hash_when_canonical_moved(): void {
		$email_id = 'wc_cleanup_hash';
		$email    = $this->register_fixture_email( $email_id );

		$canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$post_id   = $this->create_mapped_email_post( $email_id, $canonical, false );
		// Hash the persisted content (as the generator does) so the stamp matches what WordPress actually saved.
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, sha1( (string) get_post( $post_id )->post_content ) );

		// Core template moves after the post was stamped: canonical no longer matches
		// the stored content, but the source hash still does — untouched by the merchant.
		add_filter(
			'woocommerce_email_block_template_html',
			static function ( $template_html ) {
				return $template_html . "\n<!-- wp:paragraph --><p>Core template moved on.</p><!-- /wp:paragraph -->";
			}
		);

		WCEmailPostsCleanup::run();

		$this->assertNull( get_post( $post_id ), 'A post still matching its stored source hash must be deleted' );
		$this->assertFalse( get_option( $this->option_name( $email_id ) ) );
	}

	/**
	 * @testdox Should delete a never-edited post without sync meta based on identical creation and modification timestamps.
	 */
	public function test_deletes_timestamp_never_edited_post_without_sync_meta(): void {
		$email_id = 'wc_cleanup_timestamps';
		$this->register_fixture_email( $email_id );

		$legacy_body = "<!-- wp:paragraph -->\n<p>Legacy content from an older core version.</p>\n<!-- /wp:paragraph -->";
		$post_id     = $this->create_mapped_email_post( $email_id, $legacy_body, true );

		WCEmailPostsCleanup::run();

		$this->assertNull( get_post( $post_id ), 'A post with identical creation/modification timestamps and no sync meta must be deleted' );
		$this->assertFalse( get_option( $this->option_name( $email_id ) ) );
	}

	/**
	 * @testdox Should keep a customized post, its mapping, and stamp the email type meta.
	 */
	public function test_keeps_customized_post_and_stamps_email_type_meta(): void {
		$email_id = 'wc_cleanup_customized';
		$this->register_fixture_email( $email_id );

		$merchant_body = "<!-- wp:paragraph -->\n<p>Merchant-authored customisations must survive the cleanup.</p>\n<!-- /wp:paragraph -->";
		$post_id       = $this->create_mapped_email_post( $email_id, $merchant_body, false );

		WCEmailPostsCleanup::run();

		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post, 'A customized post must be kept' );
		$this->assertSame( $merchant_body, $post->post_content, 'Customized content must not be touched' );
		$this->assertSame(
			$email_id,
			get_post_meta( $post_id, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, true ),
			'Kept posts must be stamped with the email type meta'
		);
		$this->assertSame(
			$post_id,
			(int) get_option( $this->option_name( $email_id ) ),
			'The mapping of a kept post must be preserved'
		);
	}

	/**
	 * @testdox Should keep a customized sync-covered post even when its timestamps claim it was never edited.
	 */
	public function test_keeps_customized_sync_covered_post_despite_never_edited_timestamps(): void {
		$email_id = 'wc_cleanup_hash_customized';
		$this->register_fixture_email( $email_id );

		$merchant_body = "<!-- wp:paragraph -->\n<p>Merchant content diverging from the stamped source hash.</p>\n<!-- /wp:paragraph -->";
		// Never-edited timestamps on purpose: the hash verdict must win over the timestamp signal.
		$post_id = $this->create_mapped_email_post( $email_id, $merchant_body, true );
		// Valid source hash that does not match the stored content — the merchant edited after stamping.
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, sha1( 'content the post no longer matches' ) );

		// The canonical render moves too, so a canonical match cannot save the post either.
		add_filter(
			'woocommerce_email_block_template_html',
			static function ( $template_html ) {
				return $template_html . "\n<!-- wp:paragraph --><p>Core template moved on.</p><!-- /wp:paragraph -->";
			}
		);

		WCEmailPostsCleanup::run();

		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post, 'A sync-covered post whose content diverged from the source hash must be kept' );
		$this->assertSame( $merchant_body, $post->post_content, 'Customized content must not be touched' );
		$this->assertSame(
			$email_id,
			get_post_meta( $post_id, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, true ),
			'Kept posts must be stamped with the email type meta'
		);
		$this->assertSame(
			$post_id,
			(int) get_option( $this->option_name( $email_id ) ),
			'The mapping of a kept post must be preserved'
		);
	}

	/**
	 * The local date pair is computed with the site offset at write time, so a
	 * timezone change between creation and an edit can make it match
	 * coincidentally on an edited post. Only the GMT pair may vouch for
	 * "never edited".
	 *
	 * @testdox Should keep an edited post whose local timestamps coincide while GMT timestamps differ.
	 */
	public function test_keeps_post_with_coincidentally_matching_local_timestamps(): void {
		$email_id = 'wc_cleanup_tz_change';
		$this->register_fixture_email( $email_id );

		$merchant_body = '<!-- wp:paragraph --><p>Edited after a timezone change.</p><!-- /wp:paragraph -->';
		$post_id       = $this->create_mapped_email_post( $email_id, $merchant_body, false );

		// GMT pair differs (the real edit signal); make the local pair match.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'     => '2023-01-01 12:00:00',
				'post_modified' => '2023-01-01 12:00:00',
			),
			array( 'ID' => $post_id )
		);
		clean_post_cache( $post_id );

		WCEmailPostsCleanup::run();

		$this->assertInstanceOf(
			\WP_Post::class,
			get_post( $post_id ),
			'An edited post must be kept even when its local timestamps coincide'
		);
	}

	/**
	 * @testdox Should keep the post and stamp the email type meta when the email type is not registered.
	 */
	public function test_keeps_post_for_unresolvable_email_type(): void {
		$email_id = 'wc_cleanup_unregistered';

		// No fixture email registered for this type on purpose.
		$post_id = $this->create_mapped_email_post( $email_id, '<!-- wp:paragraph --><p>Content.</p><!-- /wp:paragraph -->', true );

		WCEmailPostsCleanup::run();

		$this->assertInstanceOf( \WP_Post::class, get_post( $post_id ), 'A post for an unresolvable email type must be kept' );
		$this->assertSame(
			$email_id,
			get_post_meta( $post_id, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, true )
		);
		$this->assertSame( $post_id, (int) get_option( $this->option_name( $email_id ) ) );
	}

	/**
	 * @testdox Should delete orphaned mappings pointing at missing posts or posts of other types.
	 */
	public function test_deletes_orphaned_mappings(): void {
		update_option( $this->option_name( 'wc_cleanup_orphan' ), 999999 );

		$regular_post_id = $this->factory()->post->create();
		update_option( $this->option_name( 'wc_cleanup_wrong_type' ), $regular_post_id );

		WCEmailPostsCleanup::run();

		$this->assertFalse( get_option( $this->option_name( 'wc_cleanup_orphan' ) ), 'A mapping without a post must be deleted' );
		$this->assertFalse( get_option( $this->option_name( 'wc_cleanup_wrong_type' ) ), 'A mapping pointing at a non-woo_email post must be deleted' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $regular_post_id ), 'The non-woo_email post itself must not be deleted' );
	}

	/**
	 * A corrupt mapping value casts to a non-positive post ID; it must be
	 * removed without ever reaching get_post(), which reads the global $post
	 * when given 0.
	 *
	 * @testdox Should delete mappings with corrupt values without resolving the global post.
	 */
	public function test_deletes_mappings_with_corrupt_values(): void {
		update_option( $this->option_name( 'wc_cleanup_corrupt' ), 'not-a-post-id' );
		update_option( $this->option_name( 'wc_cleanup_negative' ), -5 );

		$global_post_id = $this->create_mapped_email_post( 'wc_cleanup_global', '<!-- wp:paragraph --><p>Customized content.</p><!-- /wp:paragraph -->', false );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulating a request with a global post to prove the cleanup never resolves it for corrupt mappings.
		$GLOBALS['post'] = get_post( $global_post_id );
		delete_option( $this->option_name( 'wc_cleanup_global' ) );

		try {
			WCEmailPostsCleanup::run();
		} finally {
			unset( $GLOBALS['post'] );
		}

		$this->assertFalse( get_option( $this->option_name( 'wc_cleanup_corrupt' ) ), 'A mapping with a non-numeric value must be deleted' );
		$this->assertFalse( get_option( $this->option_name( 'wc_cleanup_negative' ) ), 'A mapping with a negative value must be deleted' );
		$this->assertInstanceOf( \WP_Post::class, get_post( $global_post_id ), 'The global post must never be resolved and deleted in place of a corrupt mapping' );
	}

	/**
	 * @testdox Should hard-delete a trashed post and its mapping.
	 */
	public function test_deletes_trashed_post_and_mapping(): void {
		$email_id = 'wc_cleanup_trashed';

		$post_id = $this->create_mapped_email_post( $email_id, '<!-- wp:paragraph --><p>Trashed content.</p><!-- /wp:paragraph -->', false );
		wp_trash_post( $post_id );

		WCEmailPostsCleanup::run();

		$this->assertNull( get_post( $post_id ), 'A trashed post must be hard-deleted' );
		$this->assertFalse( get_option( $this->option_name( $email_id ) ), 'The mapping of a trashed post must be deleted' );
	}

	/**
	 * @testdox Should delete the legacy bulk-generation transient.
	 */
	public function test_deletes_legacy_generation_transient(): void {
		set_transient( WCEmailPostsCleanup::LEGACY_GENERATION_TRANSIENT, 'yes' );

		WCEmailPostsCleanup::run();

		$this->assertFalse( get_transient( WCEmailPostsCleanup::LEGACY_GENERATION_TRANSIENT ) );
	}

	/**
	 * @testdox Should be a clean no-op on a second run.
	 */
	public function test_second_run_is_a_clean_noop(): void {
		$email_id = 'wc_cleanup_rerun_kept';
		$this->register_fixture_email( $email_id );

		$merchant_body = '<!-- wp:paragraph --><p>Customized content kept across runs.</p><!-- /wp:paragraph -->';
		$kept_post_id  = $this->create_mapped_email_post( $email_id, $merchant_body, false );

		$deleted_email_id = 'wc_cleanup_rerun_orphan';
		update_option( $this->option_name( $deleted_email_id ), 999999 );

		$this->assertFalse( WCEmailPostsCleanup::run() );
		$this->assertFalse( WCEmailPostsCleanup::run(), 'A second run must also return false' );

		$post = get_post( $kept_post_id );
		$this->assertInstanceOf( \WP_Post::class, $post, 'The kept post must survive a second run' );
		$this->assertSame( $merchant_body, $post->post_content );
		$this->assertSame( $kept_post_id, (int) get_option( $this->option_name( $email_id ) ) );
		$this->assertFalse( get_option( $this->option_name( $deleted_email_id ) ), 'A mapping deleted by the first run must stay deleted' );
	}

	/**
	 * @testdox Should log and continue with the remaining mappings when processing one mapping throws.
	 */
	public function test_continues_after_throwing_mapping_and_logs_error(): void {
		// Sorted before the healthy mapping (mappings are processed in
		// option_name order), so the throw must not abort the rest of the run.
		$throwing_email_id = 'wc_cleanup_aa_throws';
		$healthy_email_id  = 'wc_cleanup_zz_healthy';
		$this->register_fixture_email( $throwing_email_id );
		$healthy_email = $this->register_fixture_email( $healthy_email_id );

		$throwing_post_id = $this->create_mapped_email_post( $throwing_email_id, '<!-- wp:paragraph --><p>Content.</p><!-- /wp:paragraph -->', true );
		$healthy_post_id  = $this->create_mapped_email_post( $healthy_email_id, WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $healthy_email ), false );

		// The canonical render for the broken email throws (e.g. a fataling
		// third-party filter); render_block_template_html() does not catch
		// filter exceptions.
		add_filter(
			'woocommerce_email_block_template_html',
			static function ( $template_html, $email ) use ( $throwing_email_id ) {
				if ( $email instanceof \WC_Email && $throwing_email_id === $email->id ) {
					throw new \RuntimeException( 'Broken third-party template filter.' );
				}
				return $template_html;
			},
			10,
			2
		);

		$logger = $this->createMock( Email_Editor_Logger_Interface::class );
		$logger->expects( $this->once() )
			->method( 'error' )
			->with( $this->stringContains( $this->option_name( $throwing_email_id ) ) );

		WCEmailPostsCleanup::run( $logger );

		$this->assertInstanceOf( \WP_Post::class, get_post( $throwing_post_id ), 'The mapping that threw must be left untouched' );
		$this->assertNull( get_post( $healthy_post_id ), 'Mappings after the throwing one must still be processed' );
		$this->assertFalse( get_option( $this->option_name( $healthy_email_id ) ) );
	}

	/**
	 * @testdox Should not delete options that only resemble the mapping shape via LIKE wildcards.
	 */
	public function test_ignores_options_only_resembling_the_mapping_shape(): void {
		// `_` is a single-character wildcard in SQL LIKE, so this dashed
		// third-party option matches the scan pattern but is not a mapping.
		$lookalike_option = 'woocommerce-email-templates-foo-post-id';
		update_option( $lookalike_option, 999999 );

		WCEmailPostsCleanup::run();

		$this->assertSame(
			999999,
			(int) get_option( $lookalike_option ),
			'Options merely matching the LIKE pattern must never be deleted'
		);

		delete_option( $lookalike_option );
	}

	/**
	 * Build a WC_Email stub backed by the fixture template and inject it into
	 * \WC_Emails::$emails so the cleanup can resolve it by email ID.
	 *
	 * @param string $email_id Email ID to assign to the stub.
	 * @return \WC_Email Registered fixture email instance.
	 */
	private function register_fixture_email( string $email_id ): \WC_Email {
		$stub = $this->getMockBuilder( \WC_Email::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_title', 'get_description' ) )
			->getMock();
		$stub->method( 'get_title' )->willReturn( 'Fixture email for cleanup tests' );
		$stub->method( 'get_description' )->willReturn( 'Fixture email used to cover WOOPLUG-6171 cleanup scenarios.' );
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

		return $stub;
	}

	/**
	 * Create a mapped `woo_email` post with controlled timestamps.
	 *
	 * @param string $email_id     Email ID to link the post to via the manager option.
	 * @param string $post_content Post content.
	 * @param bool   $never_edited When true, creation and modification timestamps are identical.
	 * @return int The created post ID.
	 */
	private function create_mapped_email_post( string $email_id, string $post_content, bool $never_edited ): int {
		$created_at  = '2023-01-01 00:00:00';
		$modified_at = $never_edited ? $created_at : '2024-06-15 12:34:56';

		$inserted = wp_insert_post(
			array(
				'post_type'         => Integration::EMAIL_POST_TYPE,
				'post_status'       => 'publish',
				'post_title'        => 'Cleanup fixture for ' . $email_id,
				'post_content'      => $post_content,
				'post_date'         => $created_at,
				'post_date_gmt'     => $created_at,
				'post_modified'     => $modified_at,
				'post_modified_gmt' => $modified_at,
			),
			true
		);

		if ( is_wp_error( $inserted ) ) {
			throw new \RuntimeException( 'wp_insert_post failed: ' . esc_html( $inserted->get_error_message() ) );
		}

		$post_id = (int) $inserted;
		$this->assertGreaterThan( 0, $post_id );

		// wp_insert_post overwrites post_modified* with `now` — force the timestamps via the DB.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'         => $created_at,
				'post_date_gmt'     => $created_at,
				'post_modified'     => $modified_at,
				'post_modified_gmt' => $modified_at,
			),
			array( 'ID' => $post_id )
		);
		clean_post_cache( $post_id );

		$this->posts_manager->save_email_template_post_id( $email_id, $post_id );

		return $post_id;
	}

	/**
	 * Build the mapping option name for an email type.
	 *
	 * @param string $email_id The email type.
	 * @return string Option name.
	 */
	private function option_name( string $email_id ): string {
		return 'woocommerce_email_templates_' . $email_id . '_post_id';
	}

	/**
	 * Remove any stubs we injected into \WC_Emails::$emails during the test.
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
