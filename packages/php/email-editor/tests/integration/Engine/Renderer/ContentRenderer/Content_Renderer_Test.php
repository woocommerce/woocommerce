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
	 * Test render() returns an HTML string with inlined styles.
	 */
	public function testItRendersContent(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';
		$content           = $this->renderer->render(
			$this->email_post,
			$template
		);
		$this->assertIsString( $content );
		$this->assertStringContainsString( 'Hello!', $content );
	}

	/**
	 * Test render() inlines content styles into the HTML.
	 */
	public function testRenderInlinesContentStyles(): void {
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
	 * Test render_without_css_inline() returns HTML and collected CSS.
	 */
	public function testRenderWithoutCssInlineReturnsArray(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';
		$result            = $this->renderer->render_without_css_inline( $this->email_post, $template );
		$this->assertArrayHasKey( 'html', $result );
		$this->assertArrayHasKey( 'styles', $result );
		$this->assertStringContainsString( 'Hello!', $result['html'] );
	}

	/**
	 * Test it collects content styles without inlining them.
	 */
	public function testItCollectsContentStyles(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';
		$result            = $this->renderer->render_without_css_inline( $this->email_post, $template );
		$this->assertStringContainsString( 'margin: 0', $result['styles'] );
		$this->assertStringContainsString( 'display: block', $result['styles'] );
	}

	/**
	 * Test render_without_css_inline() returns HTML without inlined styles.
	 */
	public function testRenderWithoutCssInlineDoesNotInlineStyles(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:post-content /-->';
		$result            = $this->renderer->render_without_css_inline( $this->email_post, $template );
		$paragraph_styles  = $this->getStylesValueForTag( $result['html'], 'p' );
		// Content_Renderer no longer inlines CSS; that happens in Renderer.
		$this->assertNull( $paragraph_styles );
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
	 * Test render_block applies root horizontal padding from email_attrs
	 */
	public function testItAppliesRootHorizontalPadding(): void {
		register_block_type(
			'test/padded-block',
			array(
				'render_email_callback' => function () {
					return '<p>padded content</p>';
				},
			)
		);

		$result = $this->renderer->render_block(
			'content',
			array(
				'blockName'   => 'test/padded-block',
				'email_attrs' => array(
					'root-padding-left'  => '24px',
					'root-padding-right' => '24px',
				),
			)
		);

		$this->assertStringContainsString( 'padded content', $result );
		$this->assertStringContainsString( 'email-root-padding', $result );
		$this->assertStringContainsString( 'padding-left:24px', $result );
		$this->assertStringContainsString( 'padding-right:24px', $result );
		\WP_Block_Type_Registry::get_instance()->unregister( 'test/padded-block' );
	}

	/**
	 * Test render_block skips root padding when no root-padding attrs are set
	 */
	public function testItSkipsRootPaddingWhenNotSet(): void {
		register_block_type(
			'test/no-padding-block',
			array(
				'render_email_callback' => function () {
					return '<p>no padding</p>';
				},
			)
		);

		$result = $this->renderer->render_block(
			'content',
			array(
				'blockName'   => 'test/no-padding-block',
				'email_attrs' => array(
					'margin-top' => '10px',
				),
			)
		);

		$this->assertEquals( '<p>no padding</p>', $result );
		$this->assertStringNotContainsString( 'email-root-padding', $result );
		\WP_Block_Type_Registry::get_instance()->unregister( 'test/no-padding-block' );
	}

	/**
	 * Test preprocess_parsed_blocks distributes both root and container padding
	 * in second pass when a container above post-content has own padding
	 * (WooCommerce template pattern).
	 */
	public function testItDistributesBothPaddingsInSecondPassWhenContainerHasPadding(): void {
		// First pass: template blocks with a group wrapping post-content.
		// The group has own padding — it distributes both root padding and
		// container padding per-block to user blocks.
		$template_blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'padding' => array(
								'left'  => '20px',
								'right' => '20px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$first_result = $this->renderer->preprocess_parsed_blocks( $template_blocks );

		// Root group should have suppress-horizontal-padding flag.
		$root_group = $first_result[0];
		$this->assertTrue( $root_group['email_attrs']['suppress-horizontal-padding'] );

		// post-content should have full contentSize width (padding is distributed
		// per-block, not subtracted from the group).
		$post_content = $root_group['innerBlocks'][0];
		$this->assertArrayHasKey( 'width', $post_content['email_attrs'] );

		// Second pass: user blocks (simulating post-content rendering).
		$user_blocks = array(
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(),
				'innerBlocks' => array(),
			),
		);

		$second_result = $this->renderer->preprocess_parsed_blocks( $user_blocks );

		// User blocks should have root padding (delegated, not absorbed).
		$this->assertArrayHasKey( 'root-padding-left', $second_result[0]['email_attrs'] );
		$this->assertArrayHasKey( 'root-padding-right', $second_result[0]['email_attrs'] );

		// User blocks should also have container padding from the template group.
		$this->assertArrayHasKey( 'container-padding-left', $second_result[0]['email_attrs'] );
		$this->assertArrayHasKey( 'container-padding-right', $second_result[0]['email_attrs'] );
		$this->assertEquals( '20px', $second_result[0]['email_attrs']['container-padding-left'] );
		$this->assertEquals( '20px', $second_result[0]['email_attrs']['container-padding-right'] );

		// Width should account for both root and container padding.
		$this->assertArrayHasKey( 'width', $second_result[0]['email_attrs'] );
	}

	/**
	 * Test preprocess_parsed_blocks keeps root padding in second pass when
	 * the template delegates (MailPoet template pattern).
	 */
	public function testItKeepsRootPaddingInSecondPassWhenDelegated(): void {
		// First pass: template blocks with a group that has NO own padding.
		// The group delegates root padding to children.
		$template_blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/post-content',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$this->renderer->preprocess_parsed_blocks( $template_blocks );

		// Second pass: user blocks.
		$user_blocks = array(
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(),
				'innerBlocks' => array(),
			),
		);

		$second_result = $this->renderer->preprocess_parsed_blocks( $user_blocks );

		// User blocks SHOULD have root padding (template delegated, not absorbed).
		$this->assertArrayHasKey( 'root-padding-left', $second_result[0]['email_attrs'] );
		$this->assertArrayHasKey( 'root-padding-right', $second_result[0]['email_attrs'] );
	}

	/**
	 * Test render_block applies combined root + container horizontal padding
	 */
	public function testItAppliesCombinedRootAndContainerPadding(): void {
		register_block_type(
			'test/combined-padding-block',
			array(
				'render_email_callback' => function () {
					return '<p>combined padding</p>';
				},
			)
		);

		$result = $this->renderer->render_block(
			'content',
			array(
				'blockName'   => 'test/combined-padding-block',
				'email_attrs' => array(
					'root-padding-left'       => '10px',
					'root-padding-right'      => '10px',
					'container-padding-left'  => '20px',
					'container-padding-right' => '20px',
				),
			)
		);

		$this->assertStringContainsString( 'combined padding', $result );
		$this->assertStringContainsString( 'email-root-padding', $result );
		// Combined: 10 + 20 = 30px each side.
		$this->assertStringContainsString( 'padding-left:30px', $result );
		$this->assertStringContainsString( 'padding-right:30px', $result );
		\WP_Block_Type_Registry::get_instance()->unregister( 'test/combined-padding-block' );
	}

	/**
	 * Test render_block skips padding wrapper for alignfull blocks (no padding attrs)
	 */
	public function testItSkipsPaddingForAlignfullBlocks(): void {
		register_block_type(
			'test/alignfull-block',
			array(
				'render_email_callback' => function () {
					return '<p>full width content</p>';
				},
			)
		);

		$result = $this->renderer->render_block(
			'content',
			array(
				'blockName'   => 'test/alignfull-block',
				'email_attrs' => array(),
			)
		);

		$this->assertEquals( '<p>full width content</p>', $result );
		$this->assertStringNotContainsString( 'email-root-padding', $result );
		\WP_Block_Type_Registry::get_instance()->unregister( 'test/alignfull-block' );
	}

	/**
	 * Test render_block applies asymmetric combined padding correctly
	 */
	public function testItAppliesAsymmetricCombinedPadding(): void {
		register_block_type(
			'test/asymmetric-padding-block',
			array(
				'render_email_callback' => function () {
					return '<p>asymmetric</p>';
				},
			)
		);

		$result = $this->renderer->render_block(
			'content',
			array(
				'blockName'   => 'test/asymmetric-padding-block',
				'email_attrs' => array(
					'root-padding-left'       => '10px',
					'root-padding-right'      => '15px',
					'container-padding-left'  => '20px',
					'container-padding-right' => '25px',
				),
			)
		);

		// Combined: left = 10 + 20 = 30px, right = 15 + 25 = 40px.
		$this->assertStringContainsString( 'padding-left:30px', $result );
		$this->assertStringContainsString( 'padding-right:40px', $result );
		\WP_Block_Type_Registry::get_instance()->unregister( 'test/asymmetric-padding-block' );
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
