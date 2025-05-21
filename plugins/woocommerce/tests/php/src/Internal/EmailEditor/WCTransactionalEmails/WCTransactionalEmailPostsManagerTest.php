<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Tests for the WCTransactionalEmailPostsManager class.
 */
class WCTransactionalEmailPostsManagerTest extends \WC_Unit_Test_Case {
	/**
	 * @var WCTransactionalEmailPostsManager $template_manager
	 */
	private WCTransactionalEmailPostsManager $template_manager;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		$this->template_manager = WCTransactionalEmailPostsManager::get_instance();
	}

	/**
	 * Test that get_instance returns the same instance.
	 */
	public function testGetInstanceReturnsSameInstance(): void {
		$instance1 = WCTransactionalEmailPostsManager::get_instance();
		$instance2 = WCTransactionalEmailPostsManager::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that get_email_post returns null when post ID doesn't exist.
	 */
	public function testGetEmailPostReturnsNullWhenPostIdDoesNotExist(): void {
		$email_post = $this->template_manager->get_email_post( 'non_existent_email' );

		$this->assertNull( $email_post );
	}

	/**
	 * Test that get_email_post returns WP_Post when post exists.
	 */
	public function testGetEmailPostReturnsPostWhenExists(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'test_email', $post_id );

		$email_post = $this->template_manager->get_email_post( 'test_email' );

		$this->assertInstanceOf( \WP_Post::class, $email_post );
		$this->assertEquals( $post_id, $email_post->ID );
	}

	/**
	 * Test that template_exists returns false when template doesn't exist.
	 */
	public function testTemplateExistsReturnsFalseWhenTemplateDoesNotExist(): void {
		$exists = $this->template_manager->template_exists( 'non_existent_email' );

		$this->assertFalse( $exists );
	}

	/**
	 * Test that template_exists returns true when template exists.
	 */
	public function testTemplateExistsReturnsTrueWhenTemplateExists(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'test_email', $post_id );

		$exists = $this->template_manager->template_exists( 'test_email' );

		$this->assertTrue( $exists );
	}

	/**
	 * Test that save_email_template_post_id saves the post ID.
	 */
	public function testSaveEmailTemplatePostIdSavesPostId(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'test_email', $post_id );

		$saved_post_id = $this->template_manager->get_email_template_post_id( 'test_email' );

		$this->assertEquals( $post_id, $saved_post_id );
	}

	/**
	 * Test that delete_email_template deletes the template.
	 */
	public function testDeleteEmailTemplateDeletesTemplate(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'test_email', $post_id );

		$this->template_manager->delete_email_template( 'test_email' );

		$this->assertFalse( $this->template_manager->get_email_template_post_id( 'test_email' ) );
	}

	/**
	 * Test that get_email_type_from_post_id returns the correct email type.
	 */
	public function testGetEmailTypeFromPostIdReturnsCorrectEmailType(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'my_test_email', $post_id );

		$this->assertEquals( 'my_test_email', $this->template_manager->get_email_type_from_post_id( $post_id ) );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
	}
}
