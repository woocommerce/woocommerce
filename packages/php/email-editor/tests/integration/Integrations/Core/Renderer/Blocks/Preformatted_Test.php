<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

/**
 * Integration test for the core/preformatted block, through the whole content renderer.
 */
class Preformatted_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Original registration of the core/preformatted block.
	 *
	 * @var \WP_Block_Type
	 */
	private \WP_Block_Type $original_block;

	/**
	 * Set up the test with the block registered the way the email editor registers it.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$initializer = $this->di_container->get( Initializer::class );
		$initializer->initialize();

		$registry       = \WP_Block_Type_Registry::get_instance();
		$original_block = $registry->get_registered( 'core/preformatted' );
		$this->assertInstanceOf( \WP_Block_Type::class, $original_block );
		$this->original_block = $original_block;

		$settings = $initializer->update_block_settings( array_merge( get_object_vars( $original_block ), array( 'name' => 'core/preformatted' ) ) );
		$registry->unregister( 'core/preformatted' );
		$registry->register( 'core/preformatted', $settings );
	}

	/**
	 * Restore the original block registration.
	 */
	public function tearDown(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		$registry->unregister( 'core/preformatted' );
		$registry->register( $this->original_block );
		parent::tearDown();
	}

	/**
	 * Test the block keeps its line breaks and indentation, wraps long lines, and has no margin.
	 */
	public function testItKeepsWhitespaceAndUsesEmailSafeStyles(): void {
		$html = $this->render_block( '<!-- wp:preformatted --><pre class="wp-block-preformatted">first line' . "\n\t" . 'indented  line</pre><!-- /wp:preformatted -->' );

		$this->assertStringContainsString( "first line\n\tindented  line</pre>", $html );
		$style = $this->get_pre_element( $html )->getAttribute( 'style' );
		$this->assertStringContainsString( 'white-space: pre-wrap', $style );
		$this->assertStringContainsString( 'margin: 0', $style );
		$this->assertStringContainsString( 'font-family:', $style );
		$this->assertStringContainsString( 'monospace', $style );
	}

	/**
	 * Test the settings available in the editor reach the email.
	 */
	public function testItAppliesBlockSettings(): void {
		$rendered = $this->render_block( '<!-- wp:preformatted {"style":{"color":{"text":"#ff0000","background":"#00ff00"},"typography":{"fontSize":"22px"},"spacing":{"padding":{"top":"11px","bottom":"12px","left":"13px","right":"14px"}}}} --><pre class="wp-block-preformatted has-text-color has-background" style="color:#ff0000;background-color:#00ff00;font-size:22px;padding-top:11px;padding-right:14px;padding-bottom:12px;padding-left:13px">Text</pre><!-- /wp:preformatted -->' );

		$this->assertStringContainsString( 'email-text-block', $rendered );
		$this->assertMatchesRegularExpression( '/<td[^>]*email-text-block[^>]*style="[^"]*background-color: #00ff00/', $rendered );
		$this->assertMatchesRegularExpression( '/<td[^>]*email-text-block[^>]*style="[^"]*font-size: 22px/', $rendered );
		$this->assertMatchesRegularExpression( '/<td[^>]*email-text-block[^>]*style="[^"]*padding-top: 11px/', $rendered );
		$this->assertStringContainsString( 'color: #ff0000', $this->get_pre_element( $rendered )->getAttribute( 'style' ) );
	}

	/**
	 * Test a font family picked in the editor replaces the default monospace font.
	 */
	public function testItAppliesAChosenFontFamily(): void {
		$style = $this->get_pre_element( $this->render_block( '<!-- wp:preformatted {"fontFamily":"georgia"} --><pre class="wp-block-preformatted has-georgia-font-family">Text</pre><!-- /wp:preformatted -->' ) )->getAttribute( 'style' );

		$this->assertStringContainsString( 'Georgia', $style );
		$this->assertStringNotContainsString( 'monospace', $style );
	}

	/**
	 * Render an email with the given content and return the HTML.
	 *
	 * @param string $content Block markup.
	 */
	private function render_block( string $content ): string {
		$email_post_id = $this->factory->post->create( array( 'post_content' => $content ) );
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( \WP_Post::class, $email_post );

		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';

		return $this->di_container->get( Content_Renderer::class )->render( $email_post, $template );
	}

	/**
	 * Return the pre element of the rendered HTML.
	 *
	 * @param string $html Rendered HTML.
	 */
	private function get_pre_element( string $html ): \DOMElement {
		$document = new \DOMDocument();
		libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		$pre = $document->getElementsByTagName( 'pre' )->item( 0 );
		$this->assertInstanceOf( \DOMElement::class, $pre );
		return $pre;
	}
}
