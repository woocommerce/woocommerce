<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Utils;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration test for Renderer
 */
class Renderer_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * The renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;
	/**
	 * The email post.
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
		$this->renderer  = $this->di_container->get( Renderer::class );
		$styles          = array(
			'spacing'    => array(
				'padding' => array(
					'bottom' => '4px',
					'top'    => '3px',
					'left'   => '2px',
					'right'  => '1px',
				),
			),
			'typography' => array(
				'fontFamily'    => 'Test Font Family',
				'fontWeight'    => '300',
				'fontStyle'     => 'italic',
				'letterSpacing' => '-0.1px',
			),
			'color'      => array(
				'background' => '#123456',
			),
		);
		$theme_json_mock = $this->createMock( \WP_Theme_JSON::class );
		$theme_json_mock->method( 'get_data' )->willReturn(
			array(
				'styles' => $styles,
			)
		);
		$theme_controller_mock = $this->createMock( Theme_Controller::class );
		$theme_controller_mock->method( 'get_theme' )->willReturn( $theme_json_mock );
		$theme_controller_mock->method( 'get_styles' )->willReturn( $styles );
		$theme_controller_mock->method( 'get_layout_settings' )->willReturn( array( 'contentSize' => '660px' ) );

		// Create a mock for Personalization_Tags_Registry.
		$personalization_tags_registry_mock = $this->createMock( \Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry::class );
		$personalization_tags_registry_mock->method( 'get_all' )->willReturn( array() );

		$this->renderer = $this->getServiceWithOverrides(
			Renderer::class,
			array(
				'theme_controller'              => $theme_controller_mock,
				'personalization_tags_registry' => $personalization_tags_registry_mock,
			)
		);

		$email_post_id = $this->factory->post->create(
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
	 * Test it renders template with content.
	 */
	public function testItRendersTemplateWithContent(): void {
		$rendered = $this->renderer->render(
			$this->email_post,
			'Subject',
			'Preheader content',
			'en',
			'<meta name="robots" content="noindex, nofollow" />'
		);

		$this->assertStringContainsString( 'Subject', $rendered['html'] );
		$this->assertStringContainsString( 'Preheader content', $rendered['html'] );
		$this->assertStringContainsString( 'noindex, nofollow', $rendered['html'] );
		$this->assertStringContainsString( 'Hello!', $rendered['html'] );

		$this->assertStringContainsString( 'Preheader content', $rendered['text'] );
		$this->assertStringContainsString( 'Hello!', $rendered['text'] );
	}

	/**
	 * Test it renders LTR direction by default.
	 */
	public function testItRendersLtrDirectionByDefault(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en_US' );

		$html_tag_position = strpos( $rendered['html'], '<html ' );
		$head_tag_position = strpos( $rendered['html'], '<head>' );

		$this->assertNotFalse( $html_tag_position );
		$this->assertNotFalse( $head_tag_position );
		$this->assertLessThan( $head_tag_position, $html_tag_position );
		$html_opening_tag = substr( $rendered['html'], $html_tag_position, $head_tag_position - $html_tag_position );
		$this->assertStringContainsString( 'lang="', $html_opening_tag );
		$this->assertStringContainsString( 'dir="ltr"', $html_opening_tag );
		$this->assertStringContainsString( 'dir="ltr"', $rendered['html'] );
		$this->assertStringContainsString( 'direction: ltr', $rendered['html'] );
		$this->assertStringContainsString( 'text-align: left', $rendered['html'] );
	}

	/**
	 * Test it renders RTL direction from language fallback.
	 */
	public function testItRendersRtlDirectionFromLanguage(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'ar_SA' );

		$this->assertStringContainsString( 'lang="ar-SA"', $rendered['html'] );
		$this->assertStringContainsString( 'dir="rtl"', $rendered['html'] );
		$this->assertStringContainsString( 'direction: rtl', $rendered['html'] );
		$this->assertStringContainsString( 'text-align: right', $rendered['html'] );
	}

	/**
	 * Test explicit LTR context takes precedence over RTL language.
	 */
	public function testExplicitLtrContextTakesPrecedenceOverRtlLanguage(): void {
		$context_filter = function () {
			return array( 'is_rtl' => false );
		};
		add_filter( 'woocommerce_email_editor_rendering_email_context', $context_filter );

		try {
			$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'ar' );
		} finally {
			remove_filter( 'woocommerce_email_editor_rendering_email_context', $context_filter );
		}

		$this->assertStringContainsString( 'dir="ltr"', $rendered['html'] );
		$this->assertStringContainsString( 'direction: ltr', $rendered['html'] );
	}

	/**
	 * Test explicit RTL context takes precedence over LTR language.
	 */
	public function testExplicitRtlContextTakesPrecedenceOverLtrLanguage(): void {
		$context_filter = function () {
			return array( 'is_rtl' => true );
		};
		add_filter( 'woocommerce_email_editor_rendering_email_context', $context_filter );

		try {
			$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en_US' );
		} finally {
			remove_filter( 'woocommerce_email_editor_rendering_email_context', $context_filter );
		}

		$this->assertStringContainsString( 'dir="rtl"', $rendered['html'] );
		$this->assertStringContainsString( 'direction: rtl', $rendered['html'] );
	}

	/**
	 * Test it applies the rendering email context filter once per full render.
	 */
	public function testItAppliesRenderingContextFilterOncePerRender(): void {
		$filter_calls   = 0;
		$context_filter = function () use ( &$filter_calls ) {
			++$filter_calls;
			return array( 'is_rtl' => true );
		};
		add_filter( 'woocommerce_email_editor_rendering_email_context', $context_filter );

		try {
			$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en_US' );
		} finally {
			remove_filter( 'woocommerce_email_editor_rendering_email_context', $context_filter );
		}

		$this->assertSame( 1, $filter_calls );
		$this->assertStringContainsString( 'dir="rtl"', $rendered['html'] );
	}

	/**
	 * Test render restores a previously active rendering context.
	 */
	public function testRenderRestoresPreviousRenderingContext(): void {
		$content_renderer_property = new \ReflectionProperty( Renderer::class, 'content_renderer' );
		$content_renderer_property->setAccessible( true );
		$content_renderer = $content_renderer_property->getValue( $this->renderer );
		$this->assertInstanceOf( \Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer::class, $content_renderer );

		$previous_context = new \Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context(
			$this->createMock( \WP_Theme_JSON::class ),
			array( 'is_rtl' => true ),
			'ar_SA'
		);
		$content_renderer->set_rendering_context( $previous_context );

		try {
			$this->renderer->render( $this->email_post, 'Subject', '', 'en_US' );
			$this->assertSame( $previous_context, $content_renderer->get_current_rendering_context() );
		} finally {
			$content_renderer->restore_rendering_context( null );
		}
	}

	/**
	 * Test base template CSS resets both physical flex padding sides on mobile.
	 */
	public function testTemplateCssResetsBothFlexGapSides(): void {
		$template_styles = (string) file_get_contents( __DIR__ . '/../../../../src/Engine/Renderer/template-canvas.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.

		$this->assertStringContainsString( 'padding-left: 0 !important;', $template_styles );
		$this->assertStringContainsString( 'padding-right: 0 !important;', $template_styles );
	}

	/**
	 * Test it inlines styles.
	 */
	public function testItInlinesStyles(): void {
		$styles_callback = function ( $styles ) {
			return $styles . 'body { color: pink; }';
		};
		add_filter( 'woocommerce_email_renderer_styles', $styles_callback );
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );
		$style    = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		$this->assertIsString( $style );
		$this->assertStringContainsString( 'color: pink', $style );
		remove_filter( 'woocommerce_email_renderer_styles', $styles_callback );
	}

	/**
	 * Test it inlines body styles.
	 */
	public function testItInlinesBodyStyles(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );
		$style    = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		$this->assertIsString( $style );
		$this->assertStringContainsString( 'margin: 0; padding: 0;', $style );
	}

	/**
	 * Test it inlines wrapper styles.
	 */
	public function testItInlinesWrappersStyles(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );

		// Verify body element styles.
		$style = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		$this->assertIsString( $style );
		$this->assertStringContainsString( 'background-color: #123456', $style );
		$this->assertStringContainsString( 'font-weight: 300;', $style );
		$this->assertStringContainsString( 'font-style: italic;', $style );
		$this->assertStringContainsString( 'letter-spacing: -.1px;', $style );

		// Verify layout element styles.
		$doc = new \DOMDocument();
		$doc->loadHTML( $rendered['html'] );
		$xpath   = new \DOMXPath( $doc );
		$wrapper = null;
		$nodes   = $xpath->query( '//div[contains(@class, "email_layout_wrapper")]' );
		if ( ( $nodes instanceof \DOMNodeList ) && $nodes->length > 0 ) {
			$wrapper = $nodes->item( 0 );
		}
		$this->assertInstanceOf( \DOMElement::class, $wrapper );
		$style = $wrapper->getAttribute( 'style' );
		$this->assertStringContainsString( 'background-color: #123456', $style );
		$this->assertStringContainsString( 'font-family: Test Font Family;', $style );
		$this->assertStringContainsString( 'font-weight: 300;', $style );
		$this->assertStringContainsString( 'font-style: italic;', $style );
		$this->assertStringContainsString( 'letter-spacing: -.1px;', $style );
		$this->assertStringContainsString( 'padding-top: 3px;', $style );
		$this->assertStringContainsString( 'padding-bottom: 4px;', $style );
		// Horizontal padding is now distributed to individual block wrappers via Spacing_Preprocessor.
		$this->assertStringNotContainsString( 'padding-left:', $style );
		$this->assertStringNotContainsString( 'padding-right:', $style );
		$this->assertStringContainsString( 'max-width: 660px;', $style );
	}

	/**
	 * Test it renders post wrapped withing a template associated with the post via _wp_page_template post meta.
	 */
	public function testItRendersPostWithinAssociatedTemplate(): void {
		// @phpstan-ignore-next-line PHPStan is not aware of the register_block_template function's side effects.
		register_block_template(
			'renderer-tests//test-email-template',
			array(
				'title'       => 'Test Email Template',
				'description' => 'A test email template.',
				'content'     => '<!-- wp:group --><div class="wp-block-group test-template-class"><!-- wp:post-content /--></div><!-- /wp:group -->',
			)
		);
		update_post_meta( $this->email_post->ID, '_wp_page_template', 'test-email-template' );

		$rendered = $this->renderer->render(
			$this->email_post,
			'Subject',
			'Preheader content',
			'en'
		);
		$this->assertStringContainsString( 'test-template-class', $rendered['html'] );
	}

	/**
	 * Test it renders post wrapped withing a template passed as parameter.
	 */
	public function testItRendersPostWithinTemplatePassedAsParameter(): void {
		// @phpstan-ignore-next-line PHPStan is not aware of the register_block_template function's side effects.
		register_block_template(
			'renderer-tests//test-email-template-extra',
			array(
				'title'       => 'Test Email Template',
				'description' => 'A test email template.',
				'content'     => '<!-- wp:group --><div class="wp-block-group test-template-class-extra"><!-- wp:post-content /--></div><!-- /wp:group -->',
			)
		);
		update_post_meta( $this->email_post->ID, '_wp_page_template', 'test-email-template-extra' );

		$rendered = $this->renderer->render(
			$this->email_post,
			'Subject',
			'Preheader content',
			'en',
			'',
			'test-email-template-extra'
		);
		$this->assertStringContainsString( 'test-template-class-extra', $rendered['html'] );
	}

	/**
	 * Test that rendering preserves personalization tags.
	 */
	public function testItPreservesPersonalizationTags(): void {
		$registry = new \Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry(
			$this->di_container->get( \Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger::class )
		);
		$registry->register(
			new \Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag(
				'Customer Username',
				'[woocommerce/customer-username]',
				'Customer',
				function () {
					return '';
				}
			)
		);

		$this->renderer = $this->getServiceWithOverrides(
			Renderer::class,
			array(
				'personalization_tags_registry' => $registry,
			)
		);

		$this->email_post->post_content = '<!-- wp:paragraph --><p><!--[woocommerce/customer-username]--><!--[woocommerce/customer-username default="john"]--></p><!-- /wp:paragraph -->';
		wp_update_post(
			array(
				'ID'           => $this->email_post->ID,
				'post_content' => $this->email_post->post_content,
			)
		);
		$rendered = $this->renderer->render(
			$this->email_post,
			'Subject',
			'Preheader content',
			'en'
		);
		$this->assertStringContainsString( '<!--[woocommerce/customer-username]-->', $rendered['html'] );
		$this->assertStringContainsString( '<!--[woocommerce/customer-username default="john"]-->', $rendered['html'] );
		$this->assertStringContainsString( '<!--[woocommerce/customer-username]-->', $rendered['text'] );
		$this->assertStringContainsString( '<!--[woocommerce/customer-username default="john"]-->', $rendered['text'] );
	}

	/**
	 * Test HTML to text conversion with various tags
	 *
	 * @dataProvider html_to_text_conversion_provider
	 * @group html2text
	 * @param string $html_content HTML content to test.
	 * @param string $expected_text_contains Expected text that should be found.
	 * @param string $description Test case description.
	 */
	public function testItConvertsHtmlToTextCorrectly( string $html_content, string $expected_text_contains, string $description ): void {
		// Create email post with specific HTML content.
		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => $html_content,
			)
		);
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( \WP_Post::class, $email_post );

		$rendered = $this->renderer->render(
			$email_post,
			'Test Subject',
			'Test Preheader',
			'en'
		);

		$this->assertStringContainsString(
			$expected_text_contains,
			$rendered['text'],
			sprintf( 'Failed test: %s. Expected text to contain: "%s"', $description, $expected_text_contains )
		);
	}

	/**
	 * Data provider for HTML to text conversion tests
	 *
	 * @return array<string, array<int, string>>
	 */
	public function html_to_text_conversion_provider(): array {
		return array(
			'simple_paragraph'    => array(
				'<!-- wp:paragraph --><p>Hello world!</p><!-- /wp:paragraph -->',
				'Hello world!',
				'Simple paragraph conversion',
			),
			'heading_tags'        => array(
				'<!-- wp:heading {"level":1} --><h1>Main Title</h1><!-- /wp:heading --><!-- wp:heading {"level":2} --><h2>Subtitle</h2><!-- /wp:heading -->',
				"Main Title\n\nSubtitle",
				'Heading tags conversion with proper spacing',
			),
			'link_conversion'     => array(
				'<!-- wp:paragraph --><p>Visit our <a href="https://example.com">website</a> for more info.</p><!-- /wp:paragraph -->',
				'[website](https://example.com)',
				'Links converted to markdown format',
			),
			'div_elements'        => array(
				'<!-- wp:group --><div class="wp-block-group"><div class="wp-block-group__inner-container"><!-- wp:paragraph --><p>Nested content</p><!-- /wp:paragraph --></div></div><!-- /wp:group -->',
				'Nested content',
				'Div elements converted properly',
			),
			'list_items'          => array(
				'<!-- wp:list --><ul><li>First item</li><li>Second item</li><li>Third item</li></ul><!-- /wp:list -->',
				"- First item\n- Second item\n- Third item",
				'List items converted with bullet points',
			),
			'ordered_list'        => array(
				'<!-- wp:list {"ordered":true} --><ol><li>First step</li><li>Second step</li></ol><!-- /wp:list -->',
				"- First step\n- Second step",
				'Ordered lists converted to dashes',
			),
			'blockquote'          => array(
				'<!-- wp:quote --><blockquote class="wp-block-quote"><p>This is a quoted text</p></blockquote><!-- /wp:quote -->',
				'> This is a quoted text',
				'Blockquotes converted with > prefix',
			),
			'image_with_alt'      => array(
				'<!-- wp:image --><div class="wp-block-image"><img src="example.jpg" alt="Product image"/></div><!-- /wp:image -->',
				'[Product image]',
				'Images with alt text converted to brackets',
			),
			'preformatted_text'   => array(
				'<!-- wp:preformatted --><pre class="wp-block-preformatted">Code example
  with indentation</pre><!-- /wp:preformatted -->',
				'Code example',
				'Preformatted text preserves formatting',
			),
			'table_content'       => array(
				'<!-- wp:table --><div class="wp-block-table"><table><thead><tr><th>Header 1</th><th>Header 2</th></tr></thead><tbody><tr><td>Cell 1</td><td>Cell 2</td></tr></tbody></table></div><!-- /wp:table -->',
				"Header 1\tHeader 2",
				'Table content converted with tab separation',
			),
			'strong_emphasis'     => array(
				'<!-- wp:paragraph --><p>This is <strong>important</strong> and <em>emphasized</em> text.</p><!-- /wp:paragraph -->',
				'This is important and emphasized text.',
				'Strong and emphasis tags converted to plain text',
			),
			'line_breaks'         => array(
				'<!-- wp:paragraph --><p>Line one<br>Line two<br/>Line three</p><!-- /wp:paragraph -->',
				"Line one\nLine two\nLine three",
				'Line breaks converted correctly',
			),
			'special_entities'    => array(
				'<!-- wp:paragraph --><p>Special chars: &amp; &lt; &gt; &quot; &#039;</p><!-- /wp:paragraph -->',
				"Special chars: & < > \" '",
				'HTML entities converted to regular characters',
			),
			'non_breaking_spaces' => array(
				'<!-- wp:paragraph --><p>Word&nbsp;spacing&nbsp;test</p><!-- /wp:paragraph -->',
				'Word spacing test',
				'Non-breaking spaces converted to regular spaces',
			),
			'nested_structure'    => array(
				'<!-- wp:group --><div class="wp-block-group"><!-- wp:heading --><h2>Section Title</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Content with <a href="http://test.com">link</a> and <strong>bold text</strong>.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li>List item</li></ul><!-- /wp:list --></div><!-- /wp:group -->',
				'Section Title',
				'Complex nested structure handled correctly',
			),
		);
	}

	/**
	 * Test HTML to text conversion preserves essential content
	 */
	public function testItPreservesEssentialContentInTextVersion(): void {
		$html_content = '
			<!-- wp:heading --><h1>Welcome Email</h1><!-- /wp:heading -->
			<!-- wp:paragraph --><p>Dear Customer,</p><!-- /wp:paragraph -->
			<!-- wp:paragraph --><p>Thank you for joining us! Here are your next steps:</p><!-- /wp:paragraph -->
			<!-- wp:list -->
			<ul>
				<li>Verify your email address</li>
				<li>Complete your profile</li>
				<li>Explore our <a href="https://example.com/features">features</a></li>
			</ul>
			<!-- /wp:list -->
			<!-- wp:quote --><blockquote><p>We\'re excited to have you on board!</p></blockquote><!-- /wp:quote -->
			<!-- wp:paragraph --><p>Best regards,<br>The Team</p><!-- /wp:paragraph -->
		';

		$email_post_id = $this->factory->post->create(
			array(
				'post_content' => $html_content,
			)
		);
		$this->assertIsInt( $email_post_id );
		$email_post = get_post( $email_post_id );
		$this->assertInstanceOf( \WP_Post::class, $email_post );

		$rendered = $this->renderer->render(
			$email_post,
			'Welcome!',
			'Join us today',
			'en'
		);

		// Verify all key content elements are preserved.
		$text_content = $rendered['text'];
		$this->assertStringContainsString( 'Welcome Email', $text_content );
		$this->assertStringContainsString( 'Dear Customer,', $text_content );
		$this->assertStringContainsString( 'Thank you for joining us!', $text_content );
		$this->assertStringContainsString( '- Verify your email address', $text_content );
		$this->assertStringContainsString( '- Complete your profile', $text_content );
		$this->assertStringContainsString( '[features](https://example.com/features)', $text_content );
		$this->assertStringContainsString( '> ', $text_content ); // Check blockquote format.
		$this->assertStringContainsString( 'We’re excited to have you on board!', $text_content );
		$this->assertStringContainsString( "Best regards,\nThe Team", $text_content );
	}

	/**
	 * Test HTML to text conversion handles edge cases
	 */
	public function testItHandlesEdgeCasesInHtmlToText(): void {
		$test_cases = array(
			'empty_content'         => array(
				'<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->',
				'',
			),
			'only_whitespace'       => array(
				'<!-- wp:paragraph --><p>   </p><!-- /wp:paragraph -->',
				'',
			),
			'nested_empty_elements' => array(
				'<!-- wp:group --><div><div><p></p></div></div><!-- /wp:group -->',
				'',
			),
		);

		foreach ( $test_cases as $case_name => $case_data ) {
			$email_post_id = $this->factory->post->create(
				array(
					'post_content' => $case_data[0],
				)
			);
			$this->assertIsInt( $email_post_id );
			$email_post = get_post( $email_post_id );
			$this->assertInstanceOf( \WP_Post::class, $email_post );

			$rendered = $this->renderer->render( $email_post, 'Test', '', 'en' );

			// All edge cases should result in empty or minimal text content.
			$this->assertEmpty( trim( $rendered['text'] ), "Failed edge case: {$case_name}" );
		}
	}

	/**
	 * Returns the value of the style attribute for the first tag that matches the query.
	 *
	 * @param string $html HTML content.
	 * @param array  $query Query to find the tag.
	 * @return string|null
	 */
	private function getStylesValueForTag( string $html, array $query ): ?string {
		$html = new \WP_HTML_Tag_Processor( $html );
		if ( $html->next_tag( $query ) ) {
			$result = $html->get_attribute( 'style' );
			return is_string( $result ) ? $result : null;
		}
		return null;
	}
}
