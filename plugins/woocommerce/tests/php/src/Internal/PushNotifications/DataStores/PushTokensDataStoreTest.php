<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\DataStores;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use InvalidArgumentException;

/**
 * Tests for the PushTokensDataStore class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class PushTokensDataStoreTest extends \WC_Unit_Test_Case {
	/**
	 * The instance of the push tokens data store to use.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $data_store;

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->posts} WHERE post_type = %s",
				PushToken::POST_TYPE
			)
		);

		$wpdb->query(
			"DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
		);

		parent::tearDown();
	}

	/**
	 * Tests the create method of the push tokens data store.
	 */
	public function test_it_can_create_push_token() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token_12345' );
		$push_token->set_platform( PushToken::PLATFORM_APPLE );
		$push_token->set_device_uuid( 'device-uuid-123' );

		$data_store->create( $push_token );

		$this->assertNotNull( $push_token->get_id() );
		$this->assert_push_token_in_db( $push_token );
	}

	/**
	 * Tests the read method of the push tokens data store.
	 */
	public function test_it_can_read_push_token() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();
		$new_push_token      = new PushToken();
		$new_push_token->set_id( $original_push_token->get_id() );

		$data_store->read( $new_push_token );

		$this->assertEquals( $original_push_token->get_id(), $new_push_token->get_id() );
		$this->assertEquals( $original_push_token->get_user_id(), $new_push_token->get_user_id() );
		$this->assertEquals( $original_push_token->get_platform(), $new_push_token->get_platform() );
		$this->assertEquals( $original_push_token->get_token(), $new_push_token->get_token() );
		$this->assertEquals( $original_push_token->get_device_uuid(), $new_push_token->get_device_uuid() );
	}

	/**
	 * Tests the update method of the push tokens data store.
	 */
	public function test_it_can_update_push_token() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();
		$push_token->set_token( 'updated_token' );
		$push_token->set_device_uuid( 'updated-device-uuid' );

		$data_store->update( $push_token );

		$this->assert_push_token_in_db( $push_token );
	}

	/**
	 * Tests the delete method of the push tokens data store.
	 */
	public function test_it_can_delete_push_token() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();
		$data_store->delete( $push_token );

		$this->assertNull( get_post( $push_token->get_id() ) );
	}

	/**
	 * Tests the create method throws exception when push token data is
	 * incomplete.
	 */
	public function test_it_throws_exception_when_creating_push_token_with_incomplete_data() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Push token data is incorrect and and can\'t be created.' );

		$data_store->create( $push_token );
	}

	/**
	 * Tests the read method throws exception when push token has no ID.
	 */
	public function test_it_throws_exception_when_reading_push_token_without_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Push token object is incomplete and and can\'t be read.' );

		$data_store->read( $push_token );
	}

	/**
	 * Tests the update method throws exception when push token data is incomplete.
	 */
	public function test_it_throws_exception_when_updating_push_token_with_incomplete_data() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Push token object is incomplete and and can\'t be updated.' );

		$data_store->update( $push_token );
	}

	/**
	 * Tests the delete method throws exception when push token has no ID.
	 */
	public function test_it_throws_exception_when_deleting_push_token_without_id() {
		$data_store = new PushTokensDataStore();
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Push token object is incomplete and and can\'t be deleted.' );

		$data_store->delete( $push_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method finds push token by token when
	 * platform and user ID match.
	 */
	public function test_it_can_get_by_token_if_platform_and_user_id_matches() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$new_push_token = new PushToken();
		$new_push_token->set_user_id( $original_push_token->get_user_id() );
		$new_push_token->set_token( $original_push_token->get_token() );
		$new_push_token->set_platform( $original_push_token->get_platform() );
		$new_push_token->set_device_uuid( 'different-device' );

		$found_token = $data_store->get_by_token_or_device_id( $new_push_token );

		$this->assertNotNull( $found_token );
		$this->assertEquals( $original_push_token->get_id(), $found_token->get_id() );
		$this->assertEquals( $original_push_token->get_token(), $found_token->get_token() );
	}

	/**
	 * Tests the get_by_token_or_device_id method finds push token by device
	 * UUID when platform and user ID match.
	 */
	public function test_it_can_get_by_device_uuid_if_platform_and_user_id_matches() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$new_push_token = new PushToken();
		$new_push_token->set_user_id( $original_push_token->get_user_id() );
		$new_push_token->set_platform( $original_push_token->get_platform() );
		$new_push_token->set_device_uuid( $original_push_token->get_device_uuid() );
		$new_push_token->set_token( 'different_token' );

		$found_token = $data_store->get_by_token_or_device_id( $new_push_token );

		$this->assertNotNull( $found_token );
		$this->assertEquals( $original_push_token->get_id(), $found_token->get_id() );
		$this->assertEquals( $original_push_token->get_device_uuid(), $found_token->get_device_uuid() );
	}

	/**
	 * Tests the get_by_token_or_device_id method returns null when user ID
	 * and platform match but token and device UUID don't.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_token_and_device_do_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$new_push_token = new PushToken();
		$new_push_token->set_user_id( $original_push_token->get_user_id() );
		$new_push_token->set_platform( $original_push_token->get_platform() );
		$new_push_token->set_device_uuid( 'different-device' );
		$new_push_token->set_token( 'different_token' );

		$found_token = $data_store->get_by_token_or_device_id( $new_push_token );

		$this->assertNull( $found_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method returns null when user ID
	 * does not match.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_user_id_does_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$new_push_token = new PushToken();
		$new_push_token->set_user_id( 999 );
		$new_push_token->set_platform( $original_push_token->get_platform() );
		$new_push_token->set_device_uuid( $original_push_token->get_device_uuid() );
		$new_push_token->set_token( $original_push_token->get_token() );

		$found_token = $data_store->get_by_token_or_device_id( $new_push_token );

		$this->assertNull( $found_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method returns null when platform
	 * does not match.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_platform_does_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$new_push_token = new PushToken();
		$new_push_token->set_user_id( $original_push_token->get_user_id() );
		$new_push_token->set_platform( PushToken::PLATFORM_ANDROID );
		$new_push_token->set_device_uuid( $original_push_token->get_device_uuid() );
		$new_push_token->set_token( $original_push_token->get_token() );

		$found_token = $data_store->get_by_token_or_device_id( $new_push_token );

		$this->assertNull( $found_token );
	}

	/**
	 * Tests the read_meta method of the push tokens data store.
	 */
	public function test_it_can_read_meta() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();

		$meta = $data_store->read_meta( $push_token );

		$this->assertIsArray( $meta );

		$this->assertEquals(
			[
				'platform' => $push_token->get_platform(),
				'token' => $push_token->get_token(),
				'device_uuid' => $push_token->get_device_uuid(),
			],
			$meta
		);
	}

	/**
	 * Tests the add_meta method of the push tokens data store.
	 */
	public function test_it_can_add_meta() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();

		$data_store->add_meta(
			$push_token,
			array(
				'meta_key'   => 'platform',
				'meta_value' => PushToken::PLATFORM_APPLE,
			)
		);

		$meta_value = get_post_meta( $push_token->get_id(), 'platform', true );

		$this->assertEquals( PushToken::PLATFORM_APPLE, $meta_value );
	}

	/**
	 * Tests the update_meta method of the push tokens data store.
	 */
	public function test_it_can_update_meta() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();

		add_post_meta( $push_token->get_id(), 'platform', PushToken::PLATFORM_APPLE );

		$data_store->update_meta(
			$push_token,
			array(
				'meta_key'   => 'platform',
				'meta_value' => PushToken::PLATFORM_ANDROID,
			)
		);

		$meta_value = get_post_meta( $push_token->get_id(), 'platform', true );

		$this->assertEquals( PushToken::PLATFORM_ANDROID, $meta_value );
	}

	/**
	 * Tests the delete_meta method of the push tokens data store.
	 */
	public function test_it_can_delete_meta() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();

		add_post_meta( $push_token->get_id(), 'platform', PushToken::PLATFORM_APPLE );

		$data_store->delete_meta(
			$push_token,
			array( 'meta_key' => 'platform' )
		);

		$meta_value = get_post_meta( $push_token->get_id(), 'platform', true );

		$this->assertEmpty( $meta_value );
	}

	/**
	 * Tests the add_meta method throws exception when meta_key is missing.
	 */
	public function test_it_throws_exception_when_adding_meta_without_key() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t add meta for push token without meta key.' );

		$data_store->add_meta(
			$push_token,
			array( 'meta_value' => 'test_value' )
		);
	}

	/**
	 * Tests the add_meta method throws exception when push token ID is not set.
	 */
	public function test_it_throws_exception_when_adding_meta_without_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t add meta for push token without ID.' );

		$data_store->add_meta(
			$push_token,
			array(
				'meta_key'   => 'test_key',
				'meta_value' => 'test_value',
			)
		);
	}

	/**
	 * Tests the update_meta method throws exception when meta_key is missing.
	 */
	public function test_it_throws_exception_when_updating_meta_without_key() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t update meta for push token without meta key.' );

		$data_store->update_meta(
			$push_token,
			array( 'meta_value' => 'test_value' )
		);
	}

	/**
	 * Tests the update_meta method throws exception when push token ID is not set.
	 */
	public function test_it_throws_exception_when_updating_meta_without_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t update meta for push token without ID.' );

		$data_store->update_meta(
			$push_token,
			array(
				'meta_key'   => 'test_key',
				'meta_value' => 'test_value',
			)
		);
	}

	/**
	 * Tests the delete_meta method throws exception when meta_key is missing.
	 */
	public function test_it_throws_exception_when_deleting_meta_without_key() {
		$data_store = new PushTokensDataStore();

		$push_token = $this->create_test_push_token();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t delete meta for push token without meta key.' );

		$data_store->delete_meta(
			$push_token,
			array()
		);
	}

	/**
	 * Tests the delete_meta method throws exception when push token ID is not set.
	 */
	public function test_it_throws_exception_when_deleting_meta_without_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t delete meta for push token without ID.' );

		$data_store->delete_meta(
			$push_token,
			array( 'meta_key' => 'test_key' )
		);
	}

	/**
	 * Tests the get_by_token_or_device_id method throws exception when user ID is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_user_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_platform( PushToken::PLATFORM_APPLE );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test_device' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t get_by_token_or_device_id without user ID and platform.' );

		$data_store->get_by_token_or_device_id( $push_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method throws exception when platform is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_platform() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test_device' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t get_by_token_or_device_id without user ID and platform.' );

		$data_store->get_by_token_or_device_id( $push_token );
	}

	/**
	 * Creates a test push token and saves it to the database.
	 *
	 * @return PushToken The created push token object.
	 */
	private function create_test_push_token(): PushToken {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token_' . wp_rand() );
		$push_token->set_platform( PushToken::PLATFORM_APPLE );
		$push_token->set_device_uuid( 'test-device-uuid-' . wp_rand() );

		$data_store->create( $push_token );

		return $push_token;
	}

	/**
	 * Asserts that a push token record exists in the database.
	 *
	 * @param PushToken $push_token The push token object.
	 */
	private function assert_push_token_in_db( PushToken $push_token ) {
		$post = get_post( $push_token->get_id() );

		$this->assertNotNull( $post );
		$this->assertEquals( PushToken::POST_TYPE, $post->post_type );
		$this->assertEquals( $push_token->get_user_id(), $post->post_author );

		$platform     = get_post_meta( $push_token->get_id(), 'platform', true );
		$token        = get_post_meta( $push_token->get_id(), 'token', true );
		$device_uuid  = get_post_meta( $push_token->get_id(), 'device_uuid', true );

		$this->assertEquals( $push_token->get_platform(), $platform );
		$this->assertEquals( $push_token->get_token(), $token );
		$this->assertEquals( $push_token->get_device_uuid(), $device_uuid );
	}
}
