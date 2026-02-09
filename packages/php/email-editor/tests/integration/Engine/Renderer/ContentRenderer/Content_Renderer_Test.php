<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Fallback;

require_once __DIR__ . '/Dummy_Block_Renderer.php';

/**
 * Integration test for Content_Renderer
 */
class Content_Renderer_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Instance of the renderer.
	 *
	 * @var Content_Renderer
	 */
	private Content_Renderer $renderer;
	/**
	 * Instance of the email post.
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $email_post;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->renderer = $this->di_container->get( Content_Renderer::class );
		$email_post_id  = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
			)
		);
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( \WP_Post::class, $email_post );
		$this->email_post = $email_post;
	}

	/**
	 * Test it renders the content.
	 */
	public function testItRendersContent(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';
		$content           = $this->renderer->render(
			$this->email_post,
			$template
		);
		$this->assertStringContainsString( 'Hello!', $content );
	}

	/**
	 * Test it inlines content styles.
	 */
	public function testItInlinesContentStyles(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';
		$rendered          = $this->renderer->render( $this->email_post, $template );
		$paragraph_styles  = $this->getStylesValueForTag( $rendered, 'p' );
		$this->assertIsString( $paragraph_styles );
		$this->assertStringContainsString( 'margin: 0', $paragraph_styles );
		$this->assertStringContainsString( 'display: block', $paragraph_styles );
	}

	/**
	 * Test It Renders Block With Fallback Renderer
	 */
	public function testItRendersBlockWithFallbackRenderer(): void {
		$fallback_renderer = $this->createMock( Fallback::class );
		$fallback_renderer->expects( $this->once() )->method( 'render' );
		$renderer = $this->getServiceWithOverrides(
			Content_Renderer::class,
			array(
				'fallback_renderer' => $fallback_renderer,
			)
		);

		$renderer->render_block( 'content', array( 'blockName' => 'block' ) );
	}

	/**
	 * Test It Renders Block and calls render_email_callback
	 */
	public function testItRendersBlockWithBlockRenderer(): void {
		register_block_type(
			'test/block',
			array(
				'render_email_callback' => function () {
					return '<p>rendered block</p>';
				},
			)
		);

		$result = $this->renderer->render_block( 'content', array( 'blockName' => 'test/block' ) );
		$this->assertEquals( '<p>rendered block</p>', $result );
		\WP_Block_Type_Registry::get_instance()->unregister( 'test/block' );
	}

	/**
	 * Get the value of the style attribute for a given tag in the HTML.
	 *
	 * @param string $html HTML content.
	 * @param string $tag Tag name.
	 */
	private function getStylesValueForTag( $html, $tag ): ?string {
		$html = new \WP_HTML_Tag_Processor( $html );
		if ( $html->next_tag( $tag ) ) {
			$attribute = $html->get_attribute( 'style' );
			return is_string( $attribute ) ? $attribute : null;
		}
		return null;
	}
}
