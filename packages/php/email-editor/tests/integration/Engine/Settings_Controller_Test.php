<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

/**
 * Integration test for Settings_Controller class
 */
class Settings_Controller_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Settings controller instance
	 *
	 * @var Settings_Controller
	 */
	private Settings_Controller $settings_controller;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		// get_settings() collects iframe assets from the global registries, which core reads without initializing them.
		$this->swap_enqueue_registries();
		$this->settings_controller = $this->di_container->get( Settings_Controller::class );
	}

	/**
	 * Tear down after each test
	 */
	public function tearDown(): void {
		$this->restore_enqueue_registries();
		parent::tearDown();
	}

	/**
	 * Test it adds the shared rich text comment stylesheet to the editor styles exactly once.
	 */
	public function testItAddsRichTextCommentStylesToEditorStylesOnce(): void {
		$expected_css = (string) file_get_contents( dirname( __DIR__, 3 ) . '/src/Engine/rich-text-comment.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local package file.

		$settings = $this->settings_controller->get_settings();

		$styles_with_comment_rules = array_filter(
			$settings['styles'],
			function ( array $style ): bool {
				return false !== strpos( $style['css'], '[data-rich-text-comment]' );
			}
		);

		$this->assertCount( 1, $styles_with_comment_rules, 'Rich text comment styles must come from a single package stylesheet' );
		$this->assertSame( $expected_css, reset( $styles_with_comment_rules )['css'], 'The iframe must receive the shared rich-text-comment.css unchanged' );
		$this->assertDoesNotMatchRegularExpression( '/\{[^}]*\{/', $expected_css, 'Nested rules must be flattened so selector prefixing keeps them valid' );
	}
}
