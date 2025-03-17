<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Package;

/**
 * Tests for the BlockEmailRenderer class.
 */
class BlockEmailRendererTest extends \WC_Unit_Test_Case {
	/**
	 * @var BlockEmailRenderer $block_email_renderer
	 */
	private BlockEmailRenderer $block_email_renderer;

	/**
	 * @var string $email_post_content
	 */
	private $email_post_content = '<!-- wp:paragraph -->
<p>Test Paragraph. <!--[woocommerce/shopper-email]--></p>
<!-- /wp:paragraph -->

<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
<div class="wp-block-woo-email-content">##WOO_CONTENT##</div>
<!-- /wp:woo/email-content -->';

	/**
	 * @var \WP_Post $email_post
	 */
	private \WP_Post $email_post;

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
		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();

		$this->email_post = $this->factory()->post->create_and_get(
			array(
				'post_title'   => 'Test email',
				'post_name'    => 'test_email',
				'post_type'    => Integration::EMAIL_POST_TYPE,
				'post_content' => $this->email_post_content,
				'post_status'  => 'draft',
			)
		);

		$this->block_email_renderer = wc_get_container()->get( BlockEmailRenderer::class );
	}

	/**
	 * Test that the BlockEmailRenderer can render email and replaces Woo Content.
	 */
	public function testItRendersAnEmail(): void {
		$this->skip_if_unsupported_environment();

		$test_woo_content        = 'Test Woo Content';
		$wc_mail_mock            = new \WC_Email();
		$wc_mail_mock->id        = 'test_email';
		$wc_mail_mock->recipient = 'customer@test.com';
		$rendered_email          = $this->block_email_renderer->render_block_email( $this->email_post, $test_woo_content, $wc_mail_mock );
		// Check that the Woo content placeholder was replaced.
		$this->assertStringContainsString( $test_woo_content, $rendered_email );
		// Check that the email standard block content was rendered correctly.
		$this->assertStringContainsString( 'Test Paragraph.', $rendered_email );
		// Check that the personalized tag was replaced.
		$this->assertStringContainsString( 'customer@test.com', $rendered_email );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
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
