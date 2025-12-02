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
	 * Test that get_email_type_class_name_from_email_id converts correctly.
	 */
	public function testGetEmailTypeClassNameFromEmailIdConvertsCorrectly(): void {
		$test_cases = array(
			'customer_new_account'      => 'WC_Email_Customer_New_Account',
			'new_order'                 => 'WC_Email_New_Order',
			'customer_processing_order' => 'WC_Email_Customer_Processing_Order',
			'customer_cancelled_order'  => 'WC_Email_Customer_Cancelled_Order',
		);

		foreach ( $test_cases as $email_id => $expected_class_name ) {
			$result = $this->template_manager->get_email_type_class_name_from_email_id( $email_id );
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
	 * Test that get_email_type_from_post_id uses in-memory cache on subsequent calls.
	 */
	public function testGetEmailTypeFromPostIdUsesInMemoryCache(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'cached_email', $post_id );

		// First call should hit database and populate cache.
		$result1 = $this->template_manager->get_email_type_from_post_id( $post_id );
		$this->assertEquals( 'cached_email', $result1 );

		// Delete the option to verify cache is used.
		delete_option( 'woocommerce_email_templates_cached_email_post_id' );

		$cache_key = $this->template_manager->get_cache_key_for_post_id( $post_id );
		wp_cache_delete( $cache_key, WCTransactionalEmailPostsManager::CACHE_GROUP );

		// Second call should use in-memory cache, not database.
		$result2 = $this->template_manager->get_email_type_from_post_id( $post_id );
		$this->assertEquals( 'cached_email', $result2 );
	}

	/**
	 * Test that get_email_type_from_post_id uses wp object cache on subsequent calls.
	 */
	public function testGetEmailTypeFromPostIdUsesWpObjectCache(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'wp_cached_email', $post_id );

		// First call should hit database and populate all caches.
		$result1 = $this->template_manager->get_email_type_from_post_id( $post_id );
		$this->assertEquals( 'wp_cached_email', $result1 );

		// Delete the option to verify cache is used.
		delete_option( 'woocommerce_email_templates_wp_cached_email_post_id' );

		// Clear the in-memory cache.
		$this->template_manager->clear_caches();

		// Second call should use wp object cache, not database.
		$result2 = $this->template_manager->get_email_type_from_post_id( $post_id );
		$this->assertEquals( 'wp_cached_email', $result2 );

		// Clear the wp object cache.
		$this->template_manager->clear_caches();
		$cache_key = $this->template_manager->get_cache_key_for_post_id( $post_id );
		wp_cache_delete( $cache_key, WCTransactionalEmailPostsManager::CACHE_GROUP );

		// Third call should hit database and return null.
		$result3 = $this->template_manager->get_email_type_from_post_id( $post_id );
		$this->assertNull( $result3 );
	}

	/**
	 * Test that get_email_type_from_post_id skip_cache parameter bypasses cache.
	 */
	public function testGetEmailTypeFromPostIdSkipCacheBypassesCache(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'skip_cache_email', $post_id );

		// First call populates cache.
		$result1 = $this->template_manager->get_email_type_from_post_id( $post_id );
		$this->assertEquals( 'skip_cache_email', $result1 );

		// Delete the option.
		delete_option( 'woocommerce_email_templates_skip_cache_email_post_id' );

		// Call with skip_cache should hit database and return null.
		$result2 = $this->template_manager->get_email_type_from_post_id( $post_id, true );
		$this->assertNull( $result2 );
	}

	/**
	 * Test that get_email_type_from_post_id returns null for empty post_id.
	 */
	public function testGetEmailTypeFromPostIdReturnsNullForEmptyPostId(): void {
		$this->assertNull( $this->template_manager->get_email_type_from_post_id( '' ) );
		$this->assertNull( $this->template_manager->get_email_type_from_post_id( 0 ) );
		$this->assertNull( $this->template_manager->get_email_type_from_post_id( null ) );
	}

	/**
	 * Test that get_email_template_post_id uses wp object cache on subsequent calls.
	 */
	public function testGetEmailTemplatePostIdUsesWpObjectCache(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'template_wp_cached_email', $post_id );

		// First call populates cache.
		$result1 = $this->template_manager->get_email_template_post_id( 'template_wp_cached_email' );
		$this->assertEquals( $post_id, $result1 );

		// Delete the option to verify cache is used.
		delete_option( 'woocommerce_email_templates_template_wp_cached_email_post_id' );

		// Second call should use in-memory cache, not database.
		$result2 = $this->template_manager->get_email_template_post_id( 'template_wp_cached_email' );
		$this->assertEquals( $post_id, $result2 );

		// Clear the in-memory cache.
		$this->template_manager->clear_caches();

		// Third call should use get_option and return false.
		$result3 = $this->template_manager->get_email_template_post_id( 'template_wp_cached_email' );
		$this->assertFalse( $result3 );
	}

	/**
	 * Test that save_email_template_post_id updates in-memory cache.
	 */
	public function testSaveEmailTemplatePostIdUpdatesInMemoryCache(): void {
		$post_id_1 = $this->factory->post->create();
		$post_id_2 = $this->factory->post->create();

		// Save first post ID.
		$this->template_manager->save_email_template_post_id( 'cache_update_email', $post_id_1 );
		$this->assertEquals( $post_id_1, $this->template_manager->get_email_template_post_id( 'cache_update_email' ) );
		$this->assertEquals( 'cache_update_email', $this->template_manager->get_email_type_from_post_id( $post_id_1 ) );

		// Save second post ID - should update cache.
		$this->template_manager->save_email_template_post_id( 'cache_update_email', $post_id_2 );
		$this->assertEquals( $post_id_2, $this->template_manager->get_email_template_post_id( 'cache_update_email' ) );
		$this->assertEquals( 'cache_update_email', $this->template_manager->get_email_type_from_post_id( $post_id_2 ) );
	}

	/**
	 * Test that delete_email_template invalidates cache.
	 */
	public function testDeleteEmailTemplateInvalidatesCache(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'delete_cache_email', $post_id );

		// Verify the template exists.
		$this->assertEquals( 'delete_cache_email', $this->template_manager->get_email_type_from_post_id( $post_id ) );

		// Delete the template.
		$this->template_manager->delete_email_template( 'delete_cache_email' );

		// Cache should be invalidated - the result should be null.
		$this->assertNull( $this->template_manager->get_email_type_from_post_id( $post_id ) );
	}

	/**
	 * Test that get_email_type_class_name_from_email_id returns null for invalid email_id.
	 */
	public function testGetEmailTypeClassNameFromEmailIdReturnsNullForInvalidEmailId(): void {
		$result = $this->template_manager->get_email_type_class_name_from_email_id( 'non_existent_email_type' );
		$this->assertNull( $result );
	}

	/**
	 * Test caching behavior when post_id is provided as string.
	 */
	public function testCachingWithStringPostId(): void {
		$post_id = $this->factory->post->create();
		$this->template_manager->save_email_template_post_id( 'string_id_email', $post_id );

		// Pass post_id as string.
		$result = $this->template_manager->get_email_type_from_post_id( (string) $post_id );
		$this->assertEquals( 'string_id_email', $result );

		// Second call with int should use the same cache entry.
		$result2 = $this->template_manager->get_email_type_from_post_id( $post_id );
		$this->assertEquals( 'string_id_email', $result2 );
	}

	/**
	 * Test that invalidate_cache_for_template invalidates cache.
	 */
	public function testInvalidateCacheForTemplate(): void {
		$post_id   = $this->factory->post->create();
		$cache_key = $this->template_manager->get_cache_key_for_post_id( $post_id );

		$this->template_manager->save_email_template_post_id( 'custom_test_email', $post_id );
		$this->assertEquals( 'custom_test_email', $this->template_manager->get_email_type_from_post_id( $post_id ) );

		$reflection = new \ReflectionClass( WCTransactionalEmailPostsManager::class );
		$method     = $reflection->getMethod( 'invalidate_cache_for_template' );
		$method->setAccessible( true );
		$method->invoke( $this->template_manager, $post_id, 'post_id' );

		// delete option in database.
		delete_option( 'woocommerce_email_templates_custom_test_email_post_id' );

		$this->assertNull( $this->template_manager->get_email_type_from_post_id( $post_id ) );

		// does nothing if the post_id is not found in the cache.
		$method->invoke( $this->template_manager, 999999, 'post_id' );

		wp_cache_set( $cache_key, 'custom_test_email', WCTransactionalEmailPostsManager::CACHE_GROUP );
		// set post_id_to_email_type_cache using reflection.
		$reflection = new \ReflectionClass( WCTransactionalEmailPostsManager::class );
		$property   = $reflection->getProperty( 'post_id_to_email_type_cache' );
		$property->setAccessible( true );
		$property->setValue( $this->template_manager, array( $post_id => 'custom_test_email' ) );

		// confirm wp cache is set.
		$this->assertEquals( 'custom_test_email', wp_cache_get( $cache_key, WCTransactionalEmailPostsManager::CACHE_GROUP ) );

		// confirm cache is cleared for email_type.
		$method->invoke( $this->template_manager, 'custom_test_email', 'email_type' );
		$this->assertNull( $this->template_manager->get_email_type_from_post_id( $post_id ) );

		// confirm wp cache is cleared.
		$this->assertFalse( wp_cache_get( $cache_key, WCTransactionalEmailPostsManager::CACHE_GROUP ) );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->template_manager->clear_caches();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
	}
}
