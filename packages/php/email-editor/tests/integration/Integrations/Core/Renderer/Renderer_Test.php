<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

/**
 * Integration test for Renderer class
 */
class Renderer_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Instance of Renderer
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->renderer = $this->di_container->get( Renderer::class );
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->di_container->get( Initializer::class )->initialize();
	}

	/**
	 * Test it inlines button default styles
	 */
	public function testItInlinesButtonDefaultStyles(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button">Button</a></div><!-- /wp:button -->',
			)
		);
		$email_post    = get_post( $email_post_id );
		$rendered      = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$button_html   = $this->extractBlockHtml( $rendered['html'], 'wp-block-button', 'td' );
		$this->assertStringContainsString( 'color: #fff', $button_html );
		$this->assertStringContainsString( 'padding-bottom: .7em;', $button_html );
		$this->assertStringContainsString( 'padding-left: 1.4em;', $button_html );
		$this->assertStringContainsString( 'padding-right: 1.4em;', $button_html );
		$this->assertStringContainsString( 'padding-top: .7em;', $button_html );
		$this->assertStringContainsString( 'background-color: #32373c', $button_html );
	}

	/**
	 * Test it overrides button default styles with user set styles
	 */
	public function testButtonDefaultStylesDontOverwriteUserSetStyles(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:button {"backgroundColor":"white","textColor":"vivid-cyan-blue"} --><div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button">Button</a></div><!-- /wp:button -->',
			)
		);
		$email_post    = get_post( $email_post_id );
		$rendered      = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$button_html   = $this->extractBlockHtml( $rendered['html'], 'wp-block-button', 'td' );
		$this->assertStringContainsString( 'color: #0693e3', $button_html );
		$this->assertStringContainsString( 'background-color: #ffffff', $button_html );
	}

	/**
	 * Test it inlines heading font size
	 */
	public function testItInlinesHeadingFontSize(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"large"}}} --><h1 class="wp-block-heading">Hello</h1><!-- /wp:heading -->',
			)
		);
		$email_post    = get_post( $email_post_id );
		$rendered      = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$this->assertStringContainsString( 'Hello', $rendered['text'] );
	}

	/**
	 * Test it inlines heading colors
	 */
	public function testItInlinesHeadingColors(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:heading {"level":1, "backgroundColor":"black", "textColor":"luminous-vivid-orange"} --><h1 class="wp-block-heading has-luminous-vivid-orange-color has-black-background-color">Hello</h1><!-- /wp:heading -->',
			)
		);
		$email_post    = get_post( $email_post_id );

		$rendered              = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$heading_wrapper_style = $this->extractBlockStyle( $rendered['html'], 'has-luminous-vivid-orange-color', 'td' );
		$this->assertStringContainsString( 'color: #ff6900', $heading_wrapper_style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $heading_wrapper_style ); // black is #000.
	}

	/**
	 * Test it inlines paragraph colors
	 */
	public function testItInlinesParagraphColors() {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:paragraph {style":{"color":{"background":"black", "text":"luminous-vivid-orange"}}} --><p class="has-luminous-vivid-orange-color has-black-background-color">Hello</p><!-- /wp:paragraph -->',
			)
		);
		$email_post    = get_post( $email_post_id );

		$rendered                = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$paragraph_wrapper_style = $this->extractBlockStyle( $rendered['html'], 'has-luminous-vivid-orange-color', 'td' );
		$this->assertStringContainsString( 'color: #ff6900', $paragraph_wrapper_style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $paragraph_wrapper_style ); // black is #000.
	}

	/**
	 * Test it inlines list colors
	 */
	public function testItInlinesListColors() {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:list {"backgroundColor":"black","textColor":"luminous-vivid-orange","style":{"elements":{"link":{"color":{"text":"var:preset|color|vivid-red"}}}}} -->
        <ul class="has-black-background-color has-luminous-vivid-orange-color has-text-color has-background has-link-color"><!-- wp:list-item -->
        <li>Item 1</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>Item 2</li>
        <!-- /wp:list-item --></ul>
        <!-- /wp:list -->',
			)
		);
		$email_post    = get_post( $email_post_id );

		$rendered   = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$list_style = $this->extractBlockStyle( $rendered['html'], 'has-luminous-vivid-orange-color', 'ul' );
		$this->assertStringContainsString( 'color: #ff6900', $list_style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $list_style ); // black is #000.
	}

	/**
	 * Test it inlines columns background color
	 */
	public function testItInlinesColumnsColors(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:columns {"backgroundColor":"vivid-green-cyan", "textColor":"black"} -->
        <div class="wp-block-columns has-black-background-color has-luminous-vivid-orange-color"><!-- wp:column --><!-- /wp:column --></div>
        <!-- /wp:columns -->',
			)
		);
		$email_post    = get_post( $email_post_id );
		$rendered      = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$style         = $this->extractBlockStyle( $rendered['html'], 'wp-block-columns', 'table' );
		$this->assertStringContainsString( 'color: #ff6900', $style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $style ); // black is #000.
	}

	/**
	 * Test it renders text version
	 */
	public function testItRendersTextVersion(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:columns {"backgroundColor":"vivid-green-cyan", "textColor":"black"} -->
        <div class="wp-block-columns has-black-background-color has-luminous-vivid-orange-color"><!-- wp:column --><!-- /wp:column --></div>
        <!-- /wp:columns -->',
			)
		);
		$email_post    = get_post( $email_post_id );
		$rendered      = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$style         = $this->extractBlockStyle( $rendered['html'], 'wp-block-columns', 'table' );
		$this->assertStringContainsString( 'color: #ff6900', $style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $style ); // black is #000.
	}

	/**
	 * Test it inlines column colors
	 */
	public function testItInlinesColumnColors() {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '
      <!-- wp:column {"verticalAlignment":"stretch","backgroundColor":"black","textColor":"luminous-vivid-orange"} -->
      <div class="wp-block-column-test wp-block-column is-vertically-aligned-stretch has-luminous-vivid-orange-color has-black-background-color has-text-color has-background"></div>
      <!-- /wp:column -->',
			)
		);
		$email_post    = get_post( $email_post_id );
		$rendered      = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$style         = $this->extractBlockStyle( $rendered['html'], 'wp-block-column-test', 'td' );
		$this->assertStringContainsString( 'color: #ff6900', $style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $style ); // black is #000.
	}

	/**
	 * Extracts the HTML of a block
	 *
	 * @param string $html HTML content.
	 * @param string $block_class Block class.
	 * @param string $tag Tag name.
	 * @return string
	 */
	private function extractBlockHtml( string $html, string $block_class, string $tag ): string {
		$doc = new \DOMDocument();
		$doc->loadHTML( $html );
		$xpath = new \DOMXPath( $doc );
		$nodes = $xpath->query( '//' . $tag . '[contains(@class, "' . $block_class . '")]' );
		$block = null;
		if ( ( $nodes instanceof \DOMNodeList ) && $nodes->length > 0 ) {
			$block = $nodes->item( 0 );
		}
		$this->assertInstanceOf( \DOMElement::class, $block );
		$this->assertInstanceOf( \DOMDocument::class, $block->ownerDocument ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return (string) $block->ownerDocument->saveHTML( $block ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Extracts the style attribute of a block
	 *
	 * @param string $html HTML content.
	 * @param string $block_class Block class.
	 * @param string $tag Tag name.
	 */
	private function extractBlockStyle( string $html, string $block_class, string $tag ): string {
		$doc = new \DOMDocument();
		$doc->loadHTML( $html );
		$xpath = new \DOMXPath( $doc );
		$nodes = $xpath->query( '//' . $tag . '[contains(@class, "' . $block_class . '")]' );
		$block = null;
		if ( ( $nodes instanceof \DOMNodeList ) && $nodes->length > 0 ) {
			$block = $nodes->item( 0 );
		}
		$this->assertInstanceOf( \DOMElement::class, $block );
		return $block->getAttribute( 'style' );
	}
}
