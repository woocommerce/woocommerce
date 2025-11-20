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
	 * Test that get_email_type_from_post_id returns null for non-existent post ID.
	 */
	public function testGetEmailTypeFromPostIdReturnsNullForNonExistentPostId(): void {
		$this->assertNull( $this->template_manager->get_email_type_from_post_id( 999999 ) );
	}

	/**
	 * Test that get_email_post returns null when post exists in options but not in posts table.
	 */
	public function testGetEmailPostReturnsNullWhenPostExistsInOptionsButNotInPostsTable(): void {
		// Save a post ID that doesn't exist in posts table.
		$this->template_manager->save_email_template_post_id( 'test_email', 999999 );

		$email_post = $this->template_manager->get_email_post( 'test_email' );

		$this->assertNull( $email_post );
	}

	/**
	 * Test that get_email_template_post_id returns false for non-existent email type.
	 */
	public function testGetEmailTemplatePostIdReturnsFalseForNonExistentEmailType(): void {
		$post_id = $this->template_manager->get_email_template_post_id( 'non_existent_email' );

		$this->assertFalse( $post_id );
	}

	/**
	 * Test that get_email_type_class_name_from_template_name converts correctly.
	 */
	public function testGetEmailTypeClassNameFromTemplateNameConvertsCorrectly(): void {
		$test_cases = array(
			'customer_new_account'      => 'WC_Email_Customer_New_Account',
			'admin_new_order'           => 'WC_Email_Admin_New_Order',
			'customer_processing_order' => 'WC_Email_Customer_Processing_Order',
			'simple_name'               => 'WC_Email_Simple_Name',
			'single'                    => 'WC_Email_Single',
		);

		foreach ( $test_cases as $template_name => $expected_class_name ) {
			$result = $this->template_manager->get_email_type_class_name_from_template_name( $template_name );
			$this->assertEquals( $expected_class_name, $result );
		}
	}

	/**
	 * Test that get_email_type_class_name_from_email_id returns correct class name for valid email ID.
	 */
	public function testGetEmailTypeClassNameFromEmailIdReturnsCorrectClassNameForValidEmailId(): void {
		$email_id = 'new_order';

		$result = $this->template_manager->get_email_type_class_name_from_email_id( $email_id );

		$this->assertEquals( 'WC_Email_New_Order', $result );
	}

	/**
	 * Test that get_email_type_class_name_from_post_id returns correct class name for valid post ID.
	 */
	public function testGetEmailTypeClassNameFromPostIdReturnsCorrectClassNameForValidPostId(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'new_order', $post_id );

		$this->assertEquals( 'new_order', $this->template_manager->get_email_type_from_post_id( $post_id ) );

		$result = $this->template_manager->get_email_type_class_name_from_post_id( $post_id );
		$this->assertEquals( 'WC_Email_New_Order', $result );

		// clean up.
		$this->template_manager->delete_email_template( 'new_order' );

		$this->assertNull( $this->template_manager->get_email_type_from_post_id( $post_id ) );
	}

	/**
	 * Test that updating an existing email template works correctly.
	 */
	public function testUpdatingExistingEmailTemplateWorksCorrectly(): void {
		$post_id_1 = $this->factory->post->create();
		$post_id_2 = $this->factory->post->create();

		// Save initial template.
		$this->template_manager->save_email_template_post_id( 'test_email', $post_id_1 );
		$this->assertEquals( $post_id_1, $this->template_manager->get_email_template_post_id( 'test_email' ) );

		// Update with new post ID.
		$this->template_manager->save_email_template_post_id( 'test_email', $post_id_2 );
		$this->assertEquals( $post_id_2, $this->template_manager->get_email_template_post_id( 'test_email' ) );
	}

	/**
	 * Test that the WC_OPTION_NAME constant is used correctly.
	 */
	public function testWCOptionNameConstantIsUsedCorrectly(): void {
		$post_id    = $this->factory->post->create();
		$email_type = 'test_email_constant';

		$this->template_manager->save_email_template_post_id( $email_type, $post_id );

		// Check that the option was saved with the correct name format.
		$expected_option_name = 'woocommerce_email_templates_' . $email_type . '_post_id';
		$this->assertEquals( $post_id, get_option( $expected_option_name ) );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
	}
}
