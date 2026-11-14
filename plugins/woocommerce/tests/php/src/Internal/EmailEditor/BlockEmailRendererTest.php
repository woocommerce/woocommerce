<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Package;
use Automattic\WooCommerce\Internal\EmailEditor\TransactionalEmailPersonalizer;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
/**
 * Tests for the BlockEmailRenderer class.
 */
class BlockEmailRendererTest extends \WC_Unit_Test_Case {
	/**
	 * Fake email IDs the tests register for the block editor. Only registered
	 * emails get the file-template fallback in `maybe_render_block_email()`.
	 *
	 * @var string[]
	 */
	private const FAKE_BLOCK_EDITOR_EMAIL_IDS = array(
		'email_without_mapping',
		'email_with_empty_template',
		// Own id: the renderer instance (and so its request-scoped cache) is
		// shared across tests in the process, and the memoization test needs
		// a cold cache.
		'memoized_email',
		'unpublished_draft_email',
		'unpublished_auto_draft_email',
		'trashed_email',
	);

	/**
	 * @var BlockEmailRenderer $block_email_renderer
	 */
	private BlockEmailRenderer $block_email_renderer;

	/**
	 * @var string $email_post_content
	 */
	private $email_post_content = '<!-- wp:paragraph -->
<p>Test Paragraph. <!--[woocommerce/customer-email]--></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woocommerce-email-content">##WOO_CONTENT##</div>
<!-- /wp:woocommerce/email-content -->';

	/**
	 * @var \WP_Post $email_post
	 */
	private \WP_Post $email_post;

	/**
	 * @var Personalizer $personalizer
	 */
	private Personalizer $personalizer;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// Make sure WC_Email class exists.
		if ( ! class_exists( \WC_Email::class ) ) {
			require_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
		}

		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		add_filter( 'woocommerce_transactional_emails_for_block_editor', array( $this, 'register_fake_block_editor_email_ids' ) );
		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();
		$this->personalizer = Email_Editor_Container::container()->get( Personalizer::class );

		// Published on purpose: only published posts are used for rendering,
		// unpublished states fall back to the file template.
		$this->email_post = $this->factory()->post->create_and_get(
			array(
				'post_title'   => 'Test email',
				'post_name'    => 'test_email',
				'post_type'    => Integration::EMAIL_POST_TYPE,
				'post_content' => $this->email_post_content,
				'post_status'  => 'publish',
			)
		);

		WCTransactionalEmailPostsManager::get_instance()->save_email_template_post_id( 'test_email', $this->email_post->ID );

		$this->block_email_renderer = wc_get_container()->get( BlockEmailRenderer::class );
	}

	/**
	 * Test that the BlockEmailRenderer can render email and replaces Woo Content.
	 */
	public function testItRendersAnEmail(): void {
		$this->skip_if_unsupported_environment();

		$test_woo_content = 'Test Woo Content';
		$wc_mail_mock     = $this->createMock( \WC_Email::class );
		$wc_mail_mock->id = 'test_email';
		$wc_mail_mock->method( 'get_recipient' )->willReturn( 'customer@test.com' );
		$wc_mail_mock->method( 'get_subject' )->willReturn( 'Test Woo Email' );
		$wc_mail_mock->method( 'get_preheader' )->willReturn( 'Test Woo Preheader' );
		$wc_mail_mock->method( 'get_content_html' )->willReturn( $test_woo_content );
		$wc_mail_mock->method( 'get_block_editor_email_template_content' )->willReturn( $test_woo_content );

		$this->personalizer->set_context(
			array(
				'wc_email'        => $wc_mail_mock,
				'recipient_email' => $wc_mail_mock->get_recipient(),
			)
		);

		$rendered_email = $this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );

		// Check that the Woo content placeholder was replaced.
		$this->assertStringContainsString( $test_woo_content, $rendered_email );
		// Check that the email standard block content was rendered correctly.
		$this->assertStringContainsString( 'Test Paragraph.', $rendered_email );
		// Check that the personalized tag was replaced.
		$this->assertStringContainsString( 'customer@test.com', $rendered_email );
	}

	/**
	 * @testdox Should render non-null HTML from the file template when no post mapping exists.
	 */
	public function testItRendersFromFileTemplateWhenNoPostMappingExists(): void {
		$this->skip_if_unsupported_environment();

		$test_woo_content = 'Test Woo Content';
		$wc_mail_mock     = $this->create_wc_email_mock( 'email_without_mapping', $test_woo_content );

		$this->personalizer->set_context(
			array(
				'wc_email'        => $wc_mail_mock,
				'recipient_email' => $wc_mail_mock->get_recipient(),
			)
		);

		$rendered_email = $this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );

		$this->assertNotNull( $rendered_email, 'Rendering must fall back to the file template when no post mapping exists' );
		$this->assertStringContainsString( $test_woo_content, $rendered_email, 'The Woo content placeholder must be replaced in the file template output' );
		$this->assertStringNotContainsString( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER, $rendered_email, 'The raw placeholder must not leak into the rendered email' );
		// Only the `wooemailtemplate` template renders this footer, proving the
		// synthetic post was rendered through the explicitly passed template slug.
		$this->assertStringContainsString( '. All Rights Reserved.', $rendered_email, 'The wooemailtemplate footer must be present, proving the template slug was passed through for the synthetic post' );
	}

	/**
	 * @testdox Should compute the file template content once for repeated sends of the same email type.
	 */
	public function testItComputesFileTemplateContentOnceForRepeatedSends(): void {
		$this->skip_if_unsupported_environment();

		$wc_mail_mock = $this->create_wc_email_mock( 'memoized_email', 'Test Woo Content' );

		$this->personalizer->set_context(
			array(
				'wc_email'        => $wc_mail_mock,
				'recipient_email' => $wc_mail_mock->get_recipient(),
			)
		);

		$compute_count = 0;
		$count_filter  = function ( $post_data ) use ( &$compute_count ) {
			++$compute_count;
			return $post_data;
		};
		add_filter( 'woocommerce_email_content_post_data', $count_filter );

		try {
			$first  = $this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );
			$second = $this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );
		} finally {
			remove_filter( 'woocommerce_email_content_post_data', $count_filter );
		}

		// Full renders are intentionally not compared: block supports generate
		// unique `wp-elements-*` class names per render pass (non-deterministic
		// on WP 7.1+), so only the memoized input is asserted, via the counter.
		$this->assertNotNull( $first );
		$this->assertNotNull( $second );
		$this->assertSame( 1, $compute_count, 'The canonical file template content must be computed once per request for a given email type' );
	}

	/**
	 * @testdox Should return null instead of an empty-bodied email when the file template content is empty.
	 */
	public function testItReturnsNullWhenFileTemplateContentIsEmpty(): void {
		$this->skip_if_unsupported_environment();

		$wc_mail_mock = $this->create_wc_email_mock( 'email_with_empty_template', 'Test Woo Content' );

		// Simulates an unresolvable/empty template (even the default block
		// content fallback yields nothing) — the last safety net before
		// sending must hand back null so the classic pipeline takes over.
		add_filter( 'woocommerce_email_block_template_html', '__return_empty_string' );

		$this->assertNull(
			$this->block_email_renderer->maybe_render_block_email( $wc_mail_mock ),
			'An empty file template must yield null, not an empty-bodied email'
		);
	}

	/**
	 * @testdox Should return null for an email that is not registered for the block editor.
	 */
	public function testItReturnsNullForEmailNotRegisteredForBlockEditor(): void {
		$this->skip_if_unsupported_environment();

		// On purpose NOT registered via the `woocommerce_transactional_emails_for_block_editor` filter.
		$wc_mail_mock = $this->create_wc_email_mock( 'unregistered_third_party_email', 'Test Woo Content' );

		$this->assertNull(
			$this->block_email_renderer->maybe_render_block_email( $wc_mail_mock ),
			'Emails not registered for the block editor must not get the file-template fallback'
		);
	}

	/**
	 * @testdox Should fall back to the file template when the mapped post is an unpublished editing scratchpad.
	 * @dataProvider provide_unpublished_statuses
	 *
	 * @param string $post_status Unpublished post status to test.
	 */
	public function testItFallsBackToFileTemplateWhenMappedPostIsUnpublished( string $post_status ): void {
		$this->skip_if_unsupported_environment();

		$email_id = 'unpublished_' . str_replace( '-', '_', $post_status ) . '_email';
		$marker   = 'UNPUBLISHED_DB_POST_MARKER';

		$unpublished_post = $this->factory()->post->create_and_get(
			array(
				'post_title'   => 'Unpublished email',
				'post_type'    => Integration::EMAIL_POST_TYPE,
				'post_content' => $this->build_marker_post_content( $marker ),
				'post_status'  => $post_status,
			)
		);
		WCTransactionalEmailPostsManager::get_instance()->save_email_template_post_id( $email_id, $unpublished_post->ID );

		$test_woo_content = 'Test Woo Content';
		$wc_mail_mock     = $this->create_wc_email_mock( $email_id, $test_woo_content );

		$this->personalizer->set_context(
			array(
				'wc_email'        => $wc_mail_mock,
				'recipient_email' => $wc_mail_mock->get_recipient(),
			)
		);

		$rendered_email = $this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );

		$this->assertNotNull( $rendered_email, 'Rendering must fall back to the file template for unpublished posts' );
		$this->assertStringNotContainsString( $marker, $rendered_email, 'Content of an unpublished post must not be used for rendering' );
		$this->assertStringContainsString( $test_woo_content, $rendered_email, 'The Woo content placeholder must be replaced in the file template output' );
	}

	/**
	 * Unpublished post statuses that must not be used as the rendering source.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provide_unpublished_statuses(): array {
		return array(
			'draft'      => array( 'draft' ),
			'auto-draft' => array( 'auto-draft' ),
		);
	}

	/**
	 * @testdox Should fall back to the file template when the mapped post is trashed.
	 */
	public function testItFallsBackToFileTemplateWhenMappedPostIsTrashed(): void {
		$this->skip_if_unsupported_environment();

		$email_id = 'trashed_email';
		$marker   = 'TRASHED_DB_POST_MARKER';

		$trashed_post = $this->factory()->post->create_and_get(
			array(
				'post_title'   => 'Trashed email',
				'post_type'    => Integration::EMAIL_POST_TYPE,
				'post_content' => $this->build_marker_post_content( $marker ),
				'post_status'  => 'publish',
			)
		);
		WCTransactionalEmailPostsManager::get_instance()->save_email_template_post_id( $email_id, $trashed_post->ID );

		wp_trash_post( $trashed_post->ID );

		$test_woo_content = 'Test Woo Content';
		$wc_mail_mock     = $this->create_wc_email_mock( $email_id, $test_woo_content );

		$this->personalizer->set_context(
			array(
				'wc_email'        => $wc_mail_mock,
				'recipient_email' => $wc_mail_mock->get_recipient(),
			)
		);

		$rendered_email = $this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );

		$this->assertNotNull( $rendered_email, 'Rendering must fall back to the file template when the mapped post is trashed' );
		$this->assertStringNotContainsString( $marker, $rendered_email, 'Content of a trashed post must not be used for rendering' );
		$this->assertStringContainsString( $test_woo_content, $rendered_email, 'The Woo content placeholder must be replaced in the file template output' );
	}

	/**
	 * @testdox Should render the email with the site language and text direction.
	 * @dataProvider provide_render_sources
	 *
	 * @param string $email_id Email ID that selects the rendering source.
	 */
	public function testItRendersWithSiteLanguageAndDirection( string $email_id ): void {
		$this->skip_if_unsupported_environment();

		$wc_mail_mock = $this->create_wc_email_mock( $email_id, 'Test Woo Content' );

		$this->personalizer->set_context(
			array(
				'wc_email'        => $wc_mail_mock,
				'recipient_email' => $wc_mail_mock->get_recipient(),
			)
		);

		// get_bloginfo( 'language' ) reads this translation, as on a real Arabic site.
		$lang_filter = function ( $translation, $text, $domain ) {
			return 'html_lang_attribute' === $text && 'default' === $domain ? 'ar' : $translation;
		};
		add_filter( 'gettext', $lang_filter, 10, 3 );
		try {
			$rendered_email = $this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );
		} finally {
			remove_filter( 'gettext', $lang_filter, 10 );
		}

		$this->assertNotNull( $rendered_email );
		$this->assertStringContainsString( 'lang="ar"', $rendered_email );
		$this->assertStringContainsString( 'dir="rtl"', $rendered_email );
	}

	/**
	 * Email IDs that render from the saved post and from the file template.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provide_render_sources(): array {
		return array(
			'saved post'    => array( 'test_email' ),
			'file template' => array( 'email_without_mapping' ),
		);
	}

	/**
	 * Build a WC_Email mock whose block template resolves to the default block content file.
	 *
	 * @param string $email_id    The email ID assigned to the mock.
	 * @param string $woo_content The Woo content the mock returns.
	 * @return \WC_Email&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_wc_email_mock( string $email_id, string $woo_content ) {
		$wc_mail_mock                 = $this->createMock( \WC_Email::class );
		$wc_mail_mock->id             = $email_id;
		$wc_mail_mock->template_block = 'emails/block/default-block-content.php';
		$wc_mail_mock->method( 'get_recipient' )->willReturn( 'customer@test.com' );
		$wc_mail_mock->method( 'get_title' )->willReturn( 'Mock email title' );
		$wc_mail_mock->method( 'get_subject' )->willReturn( 'Test Woo Email' );
		$wc_mail_mock->method( 'get_preheader' )->willReturn( 'Test Woo Preheader' );
		$wc_mail_mock->method( 'get_content_html' )->willReturn( $woo_content );
		$wc_mail_mock->method( 'get_block_editor_email_template_content' )->willReturn( $woo_content );
		// The real file template contains store personalization tags whose
		// callbacks read these from the WC_Email instance.
		$wc_mail_mock->method( 'get_from_address' )->willReturn( 'store@test.com' );
		$wc_mail_mock->method( 'get_from_name' )->willReturn( 'Test Store' );

		return $wc_mail_mock;
	}

	/**
	 * Build block post content carrying a distinctive marker paragraph.
	 *
	 * @param string $marker Distinctive marker string.
	 * @return string Post content.
	 */
	private function build_marker_post_content( string $marker ): string {
		return '<!-- wp:paragraph --><p>' . $marker . '</p><!-- /wp:paragraph -->

<!-- wp:woocommerce/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woocommerce-email-content">##WOO_CONTENT##</div>
<!-- /wp:woocommerce/email-content -->';
	}

	/**
	 * Register the fake email IDs used by the tests for the block editor.
	 *
	 * @param string[] $emails Registered transactional email IDs.
	 * @return string[]
	 */
	public function register_fake_block_editor_email_ids( array $emails ): array {
		return array_merge( $emails, self::FAKE_BLOCK_EDITOR_EMAIL_IDS );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'woocommerce_transactional_emails_for_block_editor', array( $this, 'register_fake_block_editor_email_ids' ) );
		WCTransactionalEmailPostsManager::get_instance()->clear_caches();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
	}

	/**
	 * @testdox Multipart emails take their plain text part from the block email, not the legacy plain template.
	 */
	public function testMultipartPlainTextComesFromTheBlockEmail(): void {
		$this->skip_if_unsupported_environment();

		$alt_body = $this->send_multipart_test_email( true );

		$this->assertStringContainsString( 'Test Paragraph.', $alt_body );
		$this->assertStringContainsString( 'Order details', $alt_body );
		$this->assertStringNotContainsString( 'Legacy plain content', $alt_body );
	}

	/**
	 * @testdox The multipart plain text includes content added by the woocommerce_mail_content and wp_mail filters.
	 */
	public function testMultipartPlainTextIncludesFilteredContent(): void {
		$this->skip_if_unsupported_environment();
		$add_content = static function ( $content ) {
			return str_replace( '</body>', '<p>Added by filter</p></body>', $content );
		};
		$add_to_mail = static function ( $atts ) {
			$atts['message'] = str_replace( '</body>', '<p>Added by wp_mail</p></body>', $atts['message'] );
			return $atts;
		};
		add_filter( 'woocommerce_mail_content', $add_content );
		add_filter( 'wp_mail', $add_to_mail );

		$alt_body = $this->send_multipart_test_email( true );
		remove_filter( 'woocommerce_mail_content', $add_content );
		remove_filter( 'wp_mail', $add_to_mail );

		$this->assertStringContainsString( 'Added by filter', $alt_body );
		$this->assertStringContainsString( 'Added by wp_mail', $alt_body );
	}

	/**
	 * @testdox Multipart emails keep the legacy plain template when the block email editor is off.
	 */
	public function testMultipartPlainTextUsesLegacyTemplateWithoutBlockEmails(): void {
		$alt_body = $this->send_multipart_test_email( false );

		$this->assertStringContainsString( 'Legacy plain content', $alt_body );
		$this->assertStringNotContainsString( 'Test Paragraph.', $alt_body );
	}

	/**
	 * Send the test email as a multipart email and return its plain text part.
	 *
	 * @param bool $block_email_editor_enabled Whether the email uses the block email editor.
	 */
	private function send_multipart_test_email( bool $block_email_editor_enabled ): string {
		$email = new class() extends \WC_Email {
			/**
			 * Set up the test email.
			 */
			public function __construct() {
				$this->id             = 'test_email';
				$this->template_block = 'emails/block/default-block-content.php';
				parent::__construct();
				$this->email_type = 'multipart';
			}

			/**
			 * Legacy plain template content.
			 */
			public function get_content_plain() {
				return 'Legacy plain content';
			}

			/**
			 * Legacy HTML template content.
			 */
			public function get_content_html() {
				return 'Legacy HTML content';
			}

			/**
			 * Order details the block email shows.
			 */
			public function get_block_editor_email_template_content() {
				return 'Order details';
			}
		};
		// The feature flag is read when the email is built, so set what the constructor would.
		$email->block_email_editor_enabled = $block_email_editor_enabled;
		$email->personalizer               = wc_get_container()->get( TransactionalEmailPersonalizer::class );
		$this->personalizer->set_context(
			array(
				'wc_email'        => $email,
				'recipient_email' => 'customer@test.com',
			)
		);

		reset_phpmailer_instance();
		$email->send( 'customer@test.com', 'Test subject', $email->get_content(), '', array() );

		// The plain text part is cleared after sending, so read it from the sent message.
		$sent  = tests_retrieve_phpmailer_instance()->get_sent();
		$parts = preg_split( '/^--b\d=_\S+\r?$/m', $sent->body );
		foreach ( $parts as $part ) {
			if ( false !== stripos( $part, 'Content-Type: text/plain' ) ) {
				return quoted_printable_decode( $part );
			}
		}

		return '';
	}

	/**
	 * @testdox Gives blocks the first valid recipient and the order's customer while rendering.
	 */
	public function testItPassesRecipientAndOrderCustomerToBlocks(): void {
		$this->skip_if_unsupported_environment();
		$customer_id          = $this->factory()->user->create();
		$order                = wc_create_order( array( 'customer_id' => $customer_id ) );
		$wc_mail_mock         = $this->create_test_email_mock( 'not-an-email, buyer@test.com, other@test.com' );
		$wc_mail_mock->object = $order;
		$seen_context         = null;
		add_filter(
			'woocommerce_email_editor_rendering_email_context',
			function ( $context ) use ( &$seen_context ) {
				$seen_context = $context;
				return $context;
			},
			20
		);

		$this->block_email_renderer->maybe_render_block_email( $wc_mail_mock );

		$this->assertSame( 'buyer@test.com', $seen_context['recipient_email'] ?? null );
		$this->assertSame( $customer_id, $seen_context['user_id'] ?? null );
	}

	/**
	 * @testdox Falls back to the classic email and removes its context filter when rendering fails.
	 */
	public function testItFallsBackAndCleansUpWhenRenderingFails(): void {
		$this->skip_if_unsupported_environment();
		$thrown = false;
		$throw  = function () use ( &$thrown ) {
			$thrown = true;
			throw new \RuntimeException( 'Render failed.' );
		};
		add_filter( 'woocommerce_email_editor_rendering_email_context', $throw, 20 );
		$filters_before = count( $GLOBALS['wp_filter']['woocommerce_email_editor_rendering_email_context']->callbacks[10] ?? array() );

		$rendered_email = $this->block_email_renderer->maybe_render_block_email( $this->create_test_email_mock( 'customer@test.com' ) );

		$this->assertTrue( $thrown, 'The render should reach the failing filter.' );
		$this->assertNull( $rendered_email );
		$this->assertCount( $filters_before, $GLOBALS['wp_filter']['woocommerce_email_editor_rendering_email_context']->callbacks[10] ?? array() );
	}

	/**
	 * Mock of the test email with the given recipient.
	 *
	 * @param string $recipient Recipient list.
	 * @return \WC_Email&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function create_test_email_mock( string $recipient ) {
		$wc_mail_mock     = $this->createMock( \WC_Email::class );
		$wc_mail_mock->id = 'test_email';
		$wc_mail_mock->method( 'get_recipient' )->willReturn( $recipient );
		$wc_mail_mock->method( 'get_subject' )->willReturn( 'Test Woo Email' );
		$wc_mail_mock->method( 'get_preheader' )->willReturn( 'Test Woo Preheader' );
		$wc_mail_mock->method( 'get_content_html' )->willReturn( 'Woo content' );
		$wc_mail_mock->method( 'get_block_editor_email_template_content' )->willReturn( 'Woo content' );
		return $wc_mail_mock;
	}

	/**
	 * Skip test if the environment doesn't fulfill minimal requirements.
	 */
	private function skip_if_unsupported_environment() {
		if ( ! Email_Editor_Container::container()->get( Dependency_Check::class )->are_dependencies_met() ) {
			$this->markTestSkipped( 'This test because the test environment does not fulfill minimal requirements for the block email editor.' );
		}
	}
}
