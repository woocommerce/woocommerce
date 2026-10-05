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

		try {
			$this->assertNull(
				$this->block_email_renderer->maybe_render_block_email( $wc_mail_mock ),
				'An empty file template must yield null, not an empty-bodied email'
			);
		} finally {
			remove_filter( 'woocommerce_email_block_template_html', '__return_empty_string' );
		}
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
	 * Skip test if the environment doesn't fulfill minimal requirements.
	 */
	private function skip_if_unsupported_environment() {
		if ( ! Email_Editor_Container::container()->get( Dependency_Check::class )->are_dependencies_met() ) {
			$this->markTestSkipped( 'This test because the test environment does not fulfill minimal requirements for the block email editor.' );
		}
	}
}
