<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Services\UserDataCleanupService;
use WC_Unit_Test_Case;

/**
 * Tests for the UserDataCleanupService class.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\Services\UserDataCleanupService
 */
class UserDataCleanupServiceTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var UserDataCleanupService
	 */
	private UserDataCleanupService $sut;

	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $push_tokens_data_store;

	/**
	 * The notification preferences data store.
	 *
	 * @var NotificationPreferencesDataStore
	 */
	private NotificationPreferencesDataStore $preferences_data_store;

	/**
	 * User IDs created by the test, deleted in tearDown.
	 *
	 * @var int[]
	 */
	private array $user_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->push_tokens_data_store = new PushTokensDataStore();
		$this->preferences_data_store = new NotificationPreferencesDataStore();
		$this->sut                    = wc_get_container()->get( UserDataCleanupService::class );

		// Registering here keeps the hooks live for the tests that exercise them
		// through WordPress rather than by calling the handlers directly.
		$this->sut->register();
	}

	/**
	 * Remove every push token and test user created during the test.
	 */
	public function tearDown(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE postmeta FROM {$wpdb->postmeta} postmeta
				LEFT JOIN {$wpdb->posts} posts ON postmeta.post_id = posts.ID
				WHERE posts.post_type = %s",
				PushToken::POST_TYPE
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->posts} WHERE post_type = %s",
				PushToken::POST_TYPE
			)
		);

		foreach ( $this->user_ids as $user_id ) {
			if ( get_userdata( $user_id ) ) {
				wp_delete_user( $user_id );
			}
		}

		$this->user_ids = array();

		parent::tearDown();
	}

	/**
	 * @testdox Should register both the user deletion and the site removal hooks.
	 */
	public function test_register_adds_both_hooks(): void {
		$this->sut->register();

		$this->assertNotFalse( has_action( 'delete_user', array( $this->sut, 'handle_delete_user' ) ) );
		$this->assertNotFalse( has_action( 'remove_user_from_blog', array( $this->sut, 'handle_remove_user_from_blog' ) ) );
	}

	/**
	 * @testdox Should delete the user's push tokens and preferences when they are deleted.
	 */
	public function test_handle_delete_user_deletes_tokens_and_preferences(): void {
		$user_id = $this->create_user();
		$this->create_token_for( $user_id );
		$this->preferences_data_store->write( $user_id, $this->preferences_envelope() );

		$this->sut->handle_delete_user( $user_id );

		$this->assertSame( 0, $this->count_tokens_for( $user_id ) );
		$this->assertNull( $this->preferences_data_store->read( $user_id ) );
	}

	/**
	 * @testdox Should delete the user's push tokens and preferences when they are removed from the site.
	 */
	public function test_handle_remove_user_from_blog_deletes_tokens_and_preferences(): void {
		$user_id = $this->create_user();
		$this->create_token_for( $user_id );
		$this->preferences_data_store->write( $user_id, $this->preferences_envelope() );

		$this->sut->handle_remove_user_from_blog( $user_id );

		$this->assertSame( 0, $this->count_tokens_for( $user_id ) );
		$this->assertNull( $this->preferences_data_store->read( $user_id ) );
	}

	/**
	 * @testdox Should leave other users' push tokens and preferences alone.
	 */
	public function test_handle_delete_user_leaves_other_users_untouched(): void {
		$deleted_user_id  = $this->create_user();
		$retained_user_id = $this->create_user();

		$this->create_token_for( $deleted_user_id );
		$this->create_token_for( $retained_user_id );
		$this->preferences_data_store->write( $retained_user_id, $this->preferences_envelope() );

		$this->sut->handle_delete_user( $deleted_user_id );

		$this->assertSame( 1, $this->count_tokens_for( $retained_user_id ) );
		$this->assertNotNull( $this->preferences_data_store->read( $retained_user_id ) );
	}

	/**
	 * The reassignment branch of `wp_delete_user()` moves every post the user
	 * owns to the reassignee regardless of `delete_with_user`, so without the
	 * cleanup the tokens would survive under an eligible owner.
	 *
	 * @testdox Should delete push tokens rather than reassign them when content is attributed to another user.
	 */
	public function test_tokens_are_not_reassigned_when_a_deleted_user_has_content_attributed(): void {
		$deleted_user_id    = $this->create_user();
		$reassigned_user_id = $this->create_user();

		$this->create_token_for( $deleted_user_id );

		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $deleted_user_id, $reassigned_user_id );

		$this->assertSame( 0, $this->count_tokens_for( $deleted_user_id ) );
		$this->assertSame( 0, $this->count_tokens_for( $reassigned_user_id ) );
	}

	/**
	 * @testdox Should not fail when the user has no push tokens or preferences stored.
	 */
	public function test_handle_delete_user_is_a_no_op_for_a_user_with_no_data(): void {
		$user_id = $this->create_user();

		$this->sut->handle_delete_user( $user_id );

		$this->assertSame( 0, $this->count_tokens_for( $user_id ) );
	}

	/**
	 * Creates an administrator and records it for removal in tearDown.
	 *
	 * @return int The new user ID.
	 */
	private function create_user(): int {
		$user_id          = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->user_ids[] = $user_id;

		return $user_id;
	}

	/**
	 * Creates a push token owned by the given user.
	 *
	 * @param int $user_id The owning user ID.
	 * @return PushToken The created token.
	 */
	private function create_token_for( int $user_id ): PushToken {
		return $this->push_tokens_data_store->create(
			array(
				'user_id'       => $user_id,
				'token'         => 'test_token_' . wp_rand(),
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'test-device-uuid-' . wp_rand(),
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);
	}

	/**
	 * Counts the push token records owned by a user.
	 *
	 * @param int $user_id The owning user ID.
	 * @return int The number of records.
	 */
	private function count_tokens_for( int $user_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d",
				PushToken::POST_TYPE,
				$user_id
			)
		);
	}

	/**
	 * A minimal valid preferences envelope.
	 *
	 * @return array
	 */
	private function preferences_envelope(): array {
		return array(
			'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
			'preferences'    => array( 'store_order' => array( 'enabled' => false ) ),
		);
	}
}
