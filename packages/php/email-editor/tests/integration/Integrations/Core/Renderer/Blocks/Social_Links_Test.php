<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration test for Social_Links class
 */
class Social_Links_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Social_Links renderer instance
	 *
	 * @var Social_Links
	 */
	private $social_links_renderer;

	/**
	 * Parsed social links block
	 *
	 * @var array
	 */
	private $parsed_social_links = array(
		'blockName'    => 'core/social-links',
		'attrs'        => array(
			'openInNewTab' => true,
			'showLabels'   => true,
			'size'         => 'has-normal-icon-size',
			'className'    => 'is-style-logos-only',
		),
		'innerBlocks'  => array(
			0 => array(
				'blockName'    => 'core/social-link',
				'attrs'        => array(
					'service' => 'facebook',
					'url'     => 'https://facebook.com',
					'label'   => 'Facebook',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			1 => array(
				'blockName'    => 'core/social-link',
				'attrs'        => array(
					'service' => 'twitter',
					'url'     => 'https://twitter.com',
					'label'   => 'Twitter',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
		),
		'innerHTML'    => '<ul class="wp-block-social-links is-style-logos-only"></ul>',
		'innerContent' => array(
			0 => '<ul class="wp-block-social-links is-style-logos-only">',
			1 => null,
			2 => '</ul>',
		),
	);

	/**
	 * Rendering context instance.
	 *
	 * @var Rendering_Context
	 */
	private $rendering_context;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->social_links_renderer = new Social_Links();
		$theme_controller            = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context     = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Test it renders social links content
	 */
	public function testItRendersSocialLinksContent(): void {
		$rendered = $this->social_links_renderer->render( '', $this->parsed_social_links, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Facebook', $rendered );
		$this->assertStringContainsString( 'Twitter', $rendered );
		$this->assertStringContainsString( 'https://facebook.com', $rendered );
		$this->assertStringContainsString( 'https://twitter.com', $rendered );
	}

	/**
	 * Test it renders social links with custom colors
	 */
	public function testItRendersSocialLinksWithCustomColors(): void {
		$parsed_social_links                                      = $this->parsed_social_links;
		$parsed_social_links['attrs']['iconBackgroundColorValue'] = '#720eec';

		$rendered = $this->social_links_renderer->render( '', $parsed_social_links, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'background-color:#720eec;', $rendered );
	}

	/**
	 * Test it renders social links with pill shape
	 */
	public function testItRendersSocialLinksWithPillShape(): void {
		$parsed_social_links                       = $this->parsed_social_links;
		$parsed_social_links['attrs']['className'] = 'is-style-pill-shape';

		$rendered = $this->social_links_renderer->render( '', $parsed_social_links, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'padding-left:17px;', $rendered );
		$this->assertStringContainsString( 'padding-right:17px;', $rendered );
	}

	/**
	 * Test it renders social links with different sizes
	 */
	public function testItRendersSocialLinksWithDifferentSizes(): void {
		$sizes = array(
			'has-small-icon-size'  => '16px',
			'has-normal-icon-size' => '24px',
			'has-large-icon-size'  => '36px',
			'has-huge-icon-size'   => '48px',
		);

		foreach ( $sizes as $size_class => $expected_size ) {
			$parsed_social_links                  = $this->parsed_social_links;
			$parsed_social_links['attrs']['size'] = $size_class;

			$rendered = $this->social_links_renderer->render( '', $parsed_social_links, $this->rendering_context );
			$this->checkValidHTML( $rendered );
			$this->assertStringContainsString( "width=\"{$expected_size}\"", $rendered );
			$this->assertStringContainsString( "height=\"{$expected_size}\"", $rendered );
		}
	}

	/**
	 * Test it renders social links with email service
	 */
	public function testItRendersSocialLinksWithEmailService(): void {
		$parsed_social_links                                       = $this->parsed_social_links;
		$parsed_social_links['innerBlocks'][0]['attrs']['service'] = 'mail';
		$parsed_social_links['innerBlocks'][0]['attrs']['url']     = 'test@example.com';
		$parsed_social_links['innerBlocks'][0]['attrs']['label']   = 'My email';

		$rendered = $this->social_links_renderer->render( '', $parsed_social_links, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'mailto:', $rendered ); // HTML entities of test@example.com -> because of antispambot.
		$this->assertStringContainsString( 'My email', $rendered );
	}

	/**
	 * Test it renders social links with relative URL
	 */
	public function testItRendersSocialLinksWithRelativeUrl(): void {
		$parsed_social_links                                   = $this->parsed_social_links;
		$parsed_social_links['innerBlocks'][0]['attrs']['url'] = 'example.com';

		$rendered = $this->social_links_renderer->render( '', $parsed_social_links, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'https://example.com', $rendered );
	}

	/**
	 * Test it renders social links with fragment URL
	 */
	public function testItRendersSocialLinksWithFragmentUrl(): void {
		$parsed_social_links                                   = $this->parsed_social_links;
		$parsed_social_links['innerBlocks'][0]['attrs']['url'] = '#section';

		$rendered = $this->social_links_renderer->render( '', $parsed_social_links, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( '#section', $rendered );
	}

	/**
	 * Test it renders social links with open in new tab
	 */
	public function testItRendersSocialLinksWithOpenInNewTab(): void {
		$parsed_social_links                          = $this->parsed_social_links;
		$parsed_social_links['attrs']['openInNewTab'] = true;

		$rendered = $this->social_links_renderer->render( '', $parsed_social_links, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'target="_blank"', $rendered );
		$this->assertStringContainsString( 'rel="noopener nofollow"', $rendered );
	}

	/**
	 * Test it gets service icon URL
	 */
	public function testItGetsServiceIconUrl(): void {
		$service_icon_url_white = $this->social_links_renderer->get_service_icon_url( 'facebook', 'white' );
		$this->assertStringContainsString( 'Renderer/Blocks/icons/facebook/facebook-white.png', $service_icon_url_white );

		$service_icon_url_brand = $this->social_links_renderer->get_service_icon_url( 'facebook', 'brand' );
		$this->assertStringContainsString( 'Renderer/Blocks/icons/facebook/facebook-brand.png', $service_icon_url_brand );

		$default_service_icon_url = $this->social_links_renderer->get_service_icon_url( 'facebook', '' );
		$this->assertStringContainsString( 'Renderer/Blocks/icons/facebook/facebook-white.png', $default_service_icon_url );

		$service_icon_url_black = $this->social_links_renderer->get_service_icon_url( 'facebook', 'black' ); // no support for black image type.
		$this->assertEquals( '', $service_icon_url_black );

		$non_existing_service_icon_url = $this->social_links_renderer->get_service_icon_url( 'non-existing-service' );
		$this->assertEquals( '', $non_existing_service_icon_url );
	}
}
