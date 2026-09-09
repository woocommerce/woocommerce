<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Assets_Manager;

/**
 * Integration test for Assets_Manager class
 */
class Assets_Manager_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->swap_enqueue_registries();
	}

	/**
	 * Tear down after each test
	 */
	public function tearDown(): void {
		$this->restore_enqueue_registries();
		parent::tearDown();
	}

	/**
	 * Test it inlines the shared rich text comment stylesheet into the admin document exactly once.
	 */
	public function testItInlinesRichTextCommentStylesIntoAdminDocumentOnce(): void {
		$expected_css   = (string) file_get_contents( dirname( __DIR__, 3 ) . '/src/Engine/rich-text-comment.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local package file.
		$assets_manager = $this->di_container->get( Assets_Manager::class );

		$assets_manager->enqueue_admin_styles();

		$this->assertTrue( wp_style_is( 'wp-edit-post', 'enqueued' ), 'The inline styles are only printed when wp-edit-post is enqueued' );
		$inline_styles = wp_styles()->get_data( 'wp-edit-post', 'after' );
		$this->assertIsArray( $inline_styles, 'Inline styles must be attached to the wp-edit-post handle' );
		$this->assertCount( 1, array_keys( $inline_styles, $expected_css, true ), 'The admin document must receive the shared rich-text-comment.css unchanged and exactly once' );
	}
}
