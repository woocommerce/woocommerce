<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

/**
 * Integration test for core/post-content block rendering
 *
 * This test verifies that the core/post-content block can be rendered multiple times
 * in a single request without content becoming empty. This is critical for MailPoet's
 * batch email processing which renders multiple emails in a single PHP request.
 */
class Post_Content_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Core Initializer instance
	 *
	 * @var Initializer
	 */
	private $initializer;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->initializer = $this->di_container->get( Initializer::class );
		$this->initializer->initialize();

		// Manually swap the core/post-content render callback to simulate email rendering.
		// In production, this is done by Content_Renderer::initialize() when rendering emails.
		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'core/post-content' );
		if ( $block_type ) {
			// Replace with our stateless renderer.
			$post_content_renderer       = new Post_Content();
			$block_type->render_callback = array( $post_content_renderer, 'render_stateless' );
		}
	}

	/**
	 * Test that core/post-content block can be rendered multiple times in a single request.
	 *
	 * This simulates the MailPoet scenario where multiple emails are rendered in a queue
	 * within the same PHP request. WordPress's default render_block_core_post_content()
	 * uses static $seen_ids which causes the second render to return empty content.
	 * Our fix uses a stateless Post_Content renderer to avoid this issue.
	 */
	public function testItRendersPostContentMultipleTimesInSameRequest(): void {
		// Create a test post with some content.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Email Content',
				'post_content' => '<!-- wp:paragraph --><p>This is test email content.</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		$this->assertNotWPError( $post_id, 'Failed to create test post' );

		// Create a block instance simulating core/post-content.
		$block = new \WP_Block(
			array(
				'blockName' => 'core/post-content',
				'attrs'     => array(),
			),
			array(
				'postId' => $post_id,
			)
		);

		// Get the overridden render callback.
		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'core/post-content' );

		$this->assertNotNull( $block_type, 'core/post-content block type should be registered' );
		$this->assertIsCallable( $block_type->render_callback, 'Render callback should be callable' );

		// First render - should return content.
		$first_render = call_user_func( $block_type->render_callback, array(), '', $block );

		$this->assertNotEmpty( $first_render, 'First render should not be empty' );
		$this->assertStringContainsString( 'This is test email content.', $first_render, 'First render should contain post content' );

		// Second render - should also return content (this is the critical test).
		// With WordPress's default implementation using static $seen_ids, this would return empty.
		$second_render = call_user_func( $block_type->render_callback, array(), '', $block );

		$this->assertNotEmpty( $second_render, 'Second render should not be empty' );
		$this->assertStringContainsString( 'This is test email content.', $second_render, 'Second render should contain post content' );

		// Both renders should produce the same content.
		$this->assertEquals( $first_render, $second_render, 'First and second render should produce identical content' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test that the render callback is properly overridden with Post_Content renderer.
	 */
	public function testItOverridesPostContentBlockCallback(): void {
		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'core/post-content' );

		$this->assertNotNull( $block_type, 'core/post-content block type should be registered' );
		$this->assertIsArray( $block_type->render_callback, 'Render callback should be an array (object method)' );
		$this->assertInstanceOf( Post_Content::class, $block_type->render_callback[0], 'Render callback should be from Post_Content class' );
		$this->assertEquals( 'render_stateless', $block_type->render_callback[1], 'Render callback should be render_stateless method' );
	}

	/**
	 * Test that render returns empty when post ID is missing from context.
	 */
	public function testItReturnsEmptyWhenPostIdMissing(): void {
		$block = new \WP_Block(
			array(
				'blockName' => 'core/post-content',
				'attrs'     => array(),
			),
			array() // No postId in context.
		);

		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'core/post-content' );

		$this->assertNotNull( $block_type, 'core/post-content block type should be registered' );
		$this->assertIsCallable( $block_type->render_callback, 'Render callback should be callable' );

		$render = call_user_func( $block_type->render_callback, array(), '', $block );

		$this->assertEmpty( $render, 'Should return empty when postId is missing from context' );
	}

	/**
	 * Test that render returns empty when post doesn't exist.
	 */
	public function testItReturnsEmptyWhenPostDoesNotExist(): void {
		$block = new \WP_Block(
			array(
				'blockName' => 'core/post-content',
				'attrs'     => array(),
			),
			array(
				'postId' => 999999, // Non-existent post ID.
			)
		);

		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'core/post-content' );

		$this->assertNotNull( $block_type, 'core/post-content block type should be registered' );
		$this->assertIsCallable( $block_type->render_callback, 'Render callback should be callable' );

		$render = call_user_func( $block_type->render_callback, array(), '', $block );

		$this->assertEmpty( $render, 'Should return empty when post does not exist' );
	}

	/**
	 * Test that render returns empty when post content is empty.
	 */
	public function testItReturnsEmptyWhenPostContentIsEmpty(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Empty Post',
				'post_content' => '', // Empty content.
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		$this->assertNotWPError( $post_id, 'Failed to create test post' );

		$block = new \WP_Block(
			array(
				'blockName' => 'core/post-content',
				'attrs'     => array(),
			),
			array(
				'postId' => $post_id,
			)
		);

		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'core/post-content' );

		$this->assertNotNull( $block_type, 'core/post-content block type should be registered' );
		$this->assertIsCallable( $block_type->render_callback, 'Render callback should be callable' );

		$render = call_user_func( $block_type->render_callback, array(), '', $block );

		$this->assertEmpty( $render, 'Should return empty when post content is empty' );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test that global post is properly restored after rendering.
	 */
	public function testItRestoresGlobalPostAfterRendering(): void {
		global $post;

		// Create a test post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Email Content',
				'post_content' => '<!-- wp:paragraph --><p>Test content.</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		$this->assertNotWPError( $post_id, 'Failed to create test post' );

		// Set a different post as global.
		$original_post_id = wp_insert_post(
			array(
				'post_title'  => 'Original Post',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);
		$post             = get_post( $original_post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->assertInstanceOf( \WP_Post::class, $post, 'Global post should be set' );
		$this->assertEquals( $original_post_id, $post->ID, 'Global post ID should be the original' );

		// Render the core/post-content block.
		$block = new \WP_Block(
			array(
				'blockName' => 'core/post-content',
				'attrs'     => array(),
			),
			array(
				'postId' => $post_id,
			)
		);

		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'core/post-content' );

		$this->assertNotNull( $block_type, 'core/post-content block type should be registered' );
		$this->assertIsCallable( $block_type->render_callback, 'Render callback should be callable' );

		call_user_func( $block_type->render_callback, array(), '', $block );

		// Verify global post is restored.
		$this->assertEquals( $original_post_id, $post->ID, 'Global post should be restored to original' );

		// Clean up.
		wp_delete_post( $post_id, true );
		wp_delete_post( $original_post_id, true );
	}

	/**
	 * Test that WordPress adds no web-only image attributes to post content while an email renders.
	 */
	public function testItAddsNoWebOnlyImageAttributesDuringEmailRender(): void {
		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => '2026/11/image.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$this->assertIsInt( $attachment_id );
		wp_update_attachment_metadata(
			$attachment_id,
			array(
				'width'  => 1200,
				'height' => 600,
				'file'   => '2026/11/image.jpg',
				'sizes'  => array(
					'medium' => array(
						'file'      => 'image-300x150.jpg',
						'width'     => 300,
						'height'    => 150,
						'mime-type' => 'image/jpeg',
					),
				),
			)
		);
		$image_url = (string) wp_get_attachment_url( $attachment_id );
		$image     = sprintf( '<p><img class="wp-image-%d" src="%s" width="1200" height="600" alt=""></p>', $attachment_id, $image_url );
		$post_id   = self::factory()->post->create( array( 'post_content' => str_repeat( $image, 4 ) ) );
		$block     = new \WP_Block(
			array(
				'blockName' => 'core/post-content',
				'attrs'     => array(),
			),
			array( 'postId' => $post_id )
		);

		do_action( 'woocommerce_email_editor_render_start' );
		$rendered         = ( new Post_Content() )->render_stateless( array(), '', $block );
		$attachment_image = wp_get_attachment_image( $attachment_id, 'full' );
		do_action( 'woocommerce_email_editor_render_end' );

		$this->assertSame( 4, substr_count( $rendered, '<img' ) );
		$this->assertStringContainsString( '<img', $attachment_image );
		$this->assertStringNotContainsString( 'srcset=', $attachment_image );
		$this->assertStringNotContainsString( 'sizes=', $attachment_image );
		$this->assertStringNotContainsString( 'loading=', $attachment_image );
		$this->assertStringNotContainsString( 'loading=', $rendered );
		$this->assertStringNotContainsString( 'decoding=', $rendered );
		$this->assertStringNotContainsString( 'srcset=', $rendered );
		$this->assertStringNotContainsString( 'sizes=', $rendered );

		$after_render = wp_filter_content_tags( sprintf( '<img class="wp-image-%d" src="%s" width="1200" height="600" alt="">', $attachment_id, $image_url ) );
		$this->assertStringContainsString( 'srcset=', $after_render, 'Web pages rendered after the email keep responsive images.' );
		$this->assertStringContainsString( 'decoding=', $after_render, 'Web pages rendered after the email keep loading attributes.' );
	}

	/**
	 * Test that web-only attributes already saved in the post content are removed from every image.
	 */
	public function testItRemovesSavedWebOnlyImageAttributes(): void {
		$image   = '<img src="https://test.com/image.jpg" alt="Alt" width="600" height="300" loading="lazy" decoding="async" fetchpriority="low" srcset="https://test.com/image.jpg 600w" sizes="100vw">';
		$post_id = self::factory()->post->create( array( 'post_content' => $image . $image ) );
		$block   = new \WP_Block(
			array(
				'blockName' => 'core/post-content',
				'attrs'     => array(),
			),
			array( 'postId' => $post_id )
		);

		$rendered = ( new Post_Content() )->render_stateless( array(), '', $block );

		$this->assertSame( 2, substr_count( $rendered, '<img' ) );
		foreach ( array( 'loading=', 'decoding=', 'fetchpriority=', 'srcset=', 'sizes=' ) as $attribute ) {
			$this->assertStringNotContainsString( $attribute, $rendered );
		}
		$this->assertStringContainsString( 'src="https://test.com/image.jpg"', $rendered );
		$this->assertStringContainsString( 'width="600"', $rendered );
	}

	/**
	 * Test that ending the render keeps a filter another plugin registered with a shared WordPress callback.
	 */
	public function testItKeepsAnotherPluginsImageFilterAfterRender(): void {
		add_filter( 'wp_calculate_image_srcset_meta', '__return_empty_array', PHP_INT_MAX );

		do_action( 'woocommerce_email_editor_render_start' );
		do_action( 'woocommerce_email_editor_render_end' );
		$priority = has_filter( 'wp_calculate_image_srcset_meta', '__return_empty_array' );
		remove_filter( 'wp_calculate_image_srcset_meta', '__return_empty_array', PHP_INT_MAX );

		$this->assertSame( PHP_INT_MAX, $priority );
	}
}
