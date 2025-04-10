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
				'fontFamily' => 'Test Font Family',
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

		$this->renderer = $this->getServiceWithOverrides(
			Renderer::class,
			array(
				'theme_controller' => $theme_controller_mock,
			)
		);

		$email_post_id    = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
			)
		);
		$this->email_post = get_post( $email_post_id );
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
	 * Test it inlines styles.
	 */
	public function testItInlinesStyles(): void {
		$styles_callback = function ( $styles ) {
			return $styles . 'body { color: pink; }';
		};
		add_filter( 'woocommerce_email_renderer_styles', $styles_callback );
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );
		$style    = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		$this->assertStringContainsString( 'color: pink', $style );
		remove_filter( 'woocommerce_email_renderer_styles', $styles_callback );
	}

	/**
	 * Test it inlines body styles.
	 */
	public function testItInlinesBodyStyles(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );
		$style    = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		$this->assertStringContainsString( 'margin: 0; padding: 0;', $style );
	}

	/**
	 * Test it inlines wrapper styles.
	 */
	public function testItInlinesWrappersStyles(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );

		// Verify body element styles.
		$style = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		$this->assertStringContainsString( 'background-color: #123456', $style );

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
		$this->assertStringContainsString( 'padding-top: 3px;', $style );
		$this->assertStringContainsString( 'padding-bottom: 4px;', $style );
		$this->assertStringContainsString( 'padding-left: 2px;', $style );
		$this->assertStringContainsString( 'padding-right: 1px;', $style );
		$this->assertStringContainsString( 'max-width: 660px;', $style );
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
			return $html->get_attribute( 'style' );
		}
		return null;
	}
}
