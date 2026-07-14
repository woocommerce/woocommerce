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
use WP_Post;

/**
 * Integration test for Renderer class
 */
class Renderer_Test extends \Email_Editor_Integration_Test_Case {

	private const USED_CORE_BLOCKS = array(
		'core/button',
		'core/columns',
		'core/column',
		'core/heading',
		'core/paragraph',
		'core/list',
		'core/list-item',
	);

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
		$initializer    = $this->di_container->get( Initializer::class );

		$this->di_container->get( Email_Editor::class )->initialize();
		$initializer->initialize();

		// Because we use a custom callback for rendering blocks, it is necessary to re-register blocks with this callback.
		foreach ( self::USED_CORE_BLOCKS as $block ) {
			$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block );
			$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
			$settings = array(
				'title'    => $block_type->title,
				'name'     => $block_type->name,
				'category' => $block_type->category,
				'supports' => $block_type->supports ?? array(),
			);
			\WP_Block_Type_Registry::get_instance()->unregister( $block );
			$settings = $initializer->update_block_settings( $settings );
			register_block_type( $block, $settings );
		}
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
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
		$rendered    = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$button_html = $this->extractBlockHtml( $rendered['html'], 'wp-block-button', 'td' );
		$this->assertStringContainsString( 'color: #fff', $button_html );
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
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
		$rendered    = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$button_html = $this->extractBlockHtml( $rendered['html'], 'wp-block-button', 'td' );
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
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
		$rendered = $this->renderer->render( $email_post, 'Subject', '', 'en' );
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
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
		$rendered              = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$heading_wrapper_style = $this->extractBlockStyle( $rendered['html'], 'has-luminous-vivid-orange-color', 'td' );
		$this->assertStringContainsString( 'color: #ff6900', $heading_wrapper_style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $heading_wrapper_style ); // black is #000.
	}

	/**
	 * Test it inlines paragraph colors
	 */
	public function testItInlinesParagraphColors(): void {
		// Both background and text are defined as custom colors.
		$paragraph_1 = '<!-- wp:paragraph {"style":{"color":{"text":"#ff6900", "background": "#000"}}} --><p class="test1">Hello</p><!-- /wp:paragraph -->';
		// Text is defined as custom color background is preset.
		$paragraph_2 = '<!-- wp:paragraph {"style":{"color":{"text":"#ff6900"}, "backgroundColor":"black"}} --><p class="test2 has-black-background-color">Hello</p><!-- /wp:paragraph -->';
		// Text is preset color background is custom.
		$paragraph_3 = '<!-- wp:paragraph {"style":{"color":{"background":"#000"}, "textColor":"luminous-vivid-orange"}} --><p class="test3 has-luminous-vivid-orange-color">Hello</p><!-- /wp:paragraph -->';
		// Text and background are both preset.
		$paragraph_4 = '<!-- wp:paragraph {"style":{"textColor":"luminous-vivid-orange", "backgroundColor":"black"}} --><p class="test4 has-luminous-vivid-orange-color has-black-background-color">Hello</p><!-- /wp:paragraph -->';
		// No text defined - fallback to email styles color text #1e1e1e.
		$paragraph_5 = '<!-- wp:paragraph {"style":{} --><p class="test5">Hello</p><!-- /wp:paragraph -->';

		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => $paragraph_1 . $paragraph_2 . $paragraph_3 . $paragraph_4 . $paragraph_5,
			)
		);
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );

		$rendered                  = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$paragraph_1_wrapper_style = $this->extractBlockStyle( $rendered['html'], 'test1', 'td' );
		$this->assertStringContainsString( 'color: #ff6900', $paragraph_1_wrapper_style );
		$this->assertStringContainsString( 'background-color: #000', $paragraph_1_wrapper_style );

		$paragraph_2_wrapper_style = $this->extractBlockStyle( $rendered['html'], 'test2', 'td' );
		$this->assertStringContainsString( 'color: #ff6900', $paragraph_2_wrapper_style );
		$this->assertStringContainsString( 'background-color: #000', $paragraph_2_wrapper_style );

		$paragraph_3_wrapper_style          = $this->extractBlockStyle( $rendered['html'], 'test3', 'td' );
		$paragraph_3_nested_paragraph_style = $this->extractBlockStyle( $rendered['html'], 'test3', 'p' );
		$this->assertStringContainsString( 'color: #ff6900', $paragraph_3_nested_paragraph_style );
		$this->assertStringContainsString( 'background-color: #000', $paragraph_3_wrapper_style );

		$paragraph_4_wrapper_style          = $this->extractBlockStyle( $rendered['html'], 'test4', 'td' );
		$paragraph_4_nested_paragraph_style = $this->extractBlockStyle( $rendered['html'], 'test4', 'p' );
		$this->assertStringContainsString( 'color: #ff6900', $paragraph_4_nested_paragraph_style );
		$this->assertStringContainsString( 'background-color: #000', $paragraph_4_wrapper_style );

		$paragraph_5_wrapper_style = $this->extractBlockStyle( $rendered['html'], 'test5', 'td' );
		$this->assertStringContainsString( 'color: #1e1e1e', $paragraph_5_wrapper_style );
	}

	/**
	 * Test it inlines list colors
	 */
	public function testItInlinesListColors(): void {
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
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
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
				'post_content' => '<!-- wp:columns {"backgroundColor":"black", "textColor":"luminous-vivid-orange"} -->
        <div class="wp-block-columns has-black-background-color has-luminous-vivid-orange-color"><!-- wp:column --><!-- /wp:column --></div>
        <!-- /wp:columns -->',
			)
		);
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
		$rendered = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$style    = $this->extractBlockStyle( $rendered['html'], 'wp-block-columns', 'table' );
		$this->assertStringContainsString( 'color: #ff6900', $style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $style ); // black is #000.
	}

	/**
	 * Test it renders text version
	 */
	public function testItRendersTextVersion(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:columns {"backgroundColor":"black", "textColor":"luminous-vivid-orange"} -->
        <div class="wp-block-columns has-black-background-color has-luminous-vivid-orange-color"><!-- wp:column --><!-- /wp:column --></div>
        <!-- /wp:columns -->',
			)
		);
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
		$rendered = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$style    = $this->extractBlockStyle( $rendered['html'], 'wp-block-columns', 'table' );
		$this->assertStringContainsString( 'color: #ff6900', $style ); // luminous-vivid-orange is #ff6900.
		$this->assertStringContainsString( 'background-color: #000', $style ); // black is #000.
	}

	/**
	 * Test it inlines column colors
	 */
	public function testItInlinesColumnColors(): void {
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => '
      <!-- wp:column {"verticalAlignment":"stretch","backgroundColor":"black","textColor":"luminous-vivid-orange"} -->
      <div class="wp-block-column-test wp-block-column is-vertically-aligned-stretch has-luminous-vivid-orange-color has-black-background-color has-text-color has-background"></div>
      <!-- /wp:column -->',
			)
		);
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( WP_Post::class, $email_post );
		$rendered = $this->renderer->render( $email_post, 'Subject', '', 'en' );
		$style    = $this->extractBlockStyle( $rendered['html'], 'wp-block-column-test', 'td' );
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
