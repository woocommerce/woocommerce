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
 * Integration test for the core/code and core/verse blocks, through the whole content renderer.
 *
 * Neither block has an email renderer, so both reach the email as the `<pre>` element core saves
 * and take their styles from the content stylesheet.
 */
class Code_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * A word no email client can break on whitespace, which is what widens the email.
	 */
	private const UNBREAKABLE_WORD = 'EACMP_BREAKING_UNRECOVERABLE_INVARIANT_WITH_A_NAME_FAR_WIDER_THAN_THE_EMAIL';

	/**
	 * Set up the renderer.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->di_container->get( Initializer::class )->initialize();
	}

	/**
	 * Test a long unbreakable word in a code block wraps instead of widening the email.
	 */
	public function testItWrapsLongWordsInACodeBlock(): void {
		$html = $this->render_block( '<!-- wp:code --><pre class="wp-block-code"><code>' . self::UNBREAKABLE_WORD . '</code></pre><!-- /wp:code -->' );

		$style = $this->get_pre_element( $html )->getAttribute( 'style' );
		$this->assertStringContainsString( 'word-break: break-word', $style );
		$this->assertStringContainsString( 'white-space: pre-wrap', $style );
	}

	/**
	 * Test a long unbreakable word in a verse block wraps instead of widening the email.
	 */
	public function testItWrapsLongWordsInAVerseBlock(): void {
		$html = $this->render_block( '<!-- wp:verse --><pre class="wp-block-verse">' . self::UNBREAKABLE_WORD . '</pre><!-- /wp:verse -->' );

		$style = $this->get_pre_element( $html )->getAttribute( 'style' );
		$this->assertStringContainsString( 'word-break: break-word', $style );
		$this->assertStringContainsString( 'white-space: pre-wrap', $style );
	}

	/**
	 * Test wrapping a long word does not cost the line breaks a code block exists to keep.
	 */
	public function testItKeepsLineBreaksInACodeBlock(): void {
		$html = $this->render_block( '<!-- wp:code --><pre class="wp-block-code"><code>first line' . "\n\t" . 'indented  line</code></pre><!-- /wp:code -->' );

		$this->assertStringContainsString( "first line\n\tindented  line", $html );
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
