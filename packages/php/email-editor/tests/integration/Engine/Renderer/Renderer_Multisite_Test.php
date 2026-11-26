<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use WP_Theme_JSON_Resolver;

/**
 * Integration test for rendering emails of several sites in one request.
 *
 * @group ms-required
 */
class Renderer_Multisite_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * The renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * ID of the second site.
	 *
	 * @var int
	 */
	private int $other_site_id;

	/**
	 * Set up both sites with different email and site styles.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->skipWithoutMultisite();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->renderer = $this->di_container->get( Renderer::class );

		$this->saveEmailStyles( array( 'color' => array( 'background' => '#a1b2c3' ) ) );
		$this->saveSiteStyles( array( 'color' => array( 'text' => '#1a2b3c' ) ) );
		$this->other_site_id = $this->createSite();
		switch_to_blog( $this->other_site_id );
		$this->saveEmailStyles( array( 'color' => array( 'background' => '#c3b2a1' ) ) );
		$this->saveSiteStyles( array( 'color' => array( 'text' => '#3c2b1a' ) ) );
		restore_current_blog();
	}

	/**
	 * Test each site's email gets that site's styles when both render in one request.
	 */
	public function testItRendersEachSiteWithItsOwnStyles(): void {
		$this->renderEmail();
		switch_to_blog( $this->other_site_id );
		$html = $this->renderEmail();
		restore_current_blog();

		$this->assertStringContainsString( '#c3b2a1', $html, 'The email background should come from the other site.' );
		$this->assertStringContainsString( '#3c2b1a', $html, 'The text color should come from the other site.' );
		$this->assertStringNotContainsString( '#a1b2c3', $html );
		$this->assertStringNotContainsString( '#1a2b3c', $html );
	}

	/**
	 * Test rendering for another site leaves none of that site's theme data in core's cache.
	 */
	public function testItLeavesNoOtherSiteThemeDataInCoreCache(): void {
		switch_to_blog( $this->other_site_id );
		$this->renderEmail();
		restore_current_blog();

		$user_styles = WP_Theme_JSON_Resolver::get_user_data()->get_raw_data()['styles'] ?? array();
		$this->assertSame( '#1a2b3c', $user_styles['color']['text'] ?? null, 'The site that switched should get its own site styles back.' );
	}

	/**
	 * Render an email on the current site.
	 *
	 * @return string Email HTML.
	 */
	private function renderEmail(): string {
		$post_id = self::factory()->post->create( array( 'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->' ) );
		$this->assertIsInt( $post_id );
		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );
		return $this->renderer->render( $post, 'Subject', '', 'en' )['html'];
	}
}
