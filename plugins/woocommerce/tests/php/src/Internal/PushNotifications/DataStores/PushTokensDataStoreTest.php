<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\DataStores;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Exception;
use InvalidArgumentException;

/**
 * Tests for the PushTokensDataStore class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class PushTokensDataStoreTest extends \WC_Unit_Test_Case {
	/**
	 * Tear down the test case.
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
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'device-uuid-123' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$data_store->create( $push_token );

		$this->assertNotNull( $push_token->get_id() );
		$this->assert_push_token_in_db( $push_token );

		$post = get_post( $push_token->get_id() );
		$this->assertEquals( 'private', $post->post_status );
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
		$this->assertEquals( $original_push_token->get_origin(), $new_push_token->get_origin() );
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

		$post = get_post( $push_token->get_id() );
		$this->assertEquals( 'private', $post->post_status );
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
		$this->expectExceptionMessage( 'Can\'t create push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->create( $push_token );
	}

	/**
	 * Tests the read method throws exception when push token has no ID.
	 */
	public function test_it_throws_exception_when_reading_push_token_without_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t read push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->read( $push_token );
	}

	/**
	 * Tests the read method throws exception when push token is not found.
	 */
	public function test_it_throws_exception_when_reading_push_token_that_does_not_exist() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_id( 999999 );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Push token could not be found.' );
		$this->expectExceptionCode( 404 );

		$data_store->read( $push_token );
	}

	/**
	 * Tests the read method throws exception when the post exists but is not the correct post type.
	 */
	public function test_it_throws_exception_when_reading_push_token_with_wrong_post_type() {
		$data_store = new PushTokensDataStore();

		// Create a regular post instead of a push_token.
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Test Post',
				'post_type'   => 'post',
				'post_status' => 'private',
			)
		);

		$push_token = new PushToken();
		$push_token->set_id( $post_id );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Push token could not be found.' );
		$this->expectExceptionCode( 404 );

		$data_store->read( $push_token );
	}

	/**
	 * Tests the read method throws exception when push token metadata is malformed/missing.
	 */
	public function test_it_throws_exception_when_reading_push_token_with_malformed_metadata() {
		$data_store = new PushTokensDataStore();

		// Create a push_token post but with missing metadata.
		$post_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array(
					'platform' => PushToken::PLATFORM_IOS,
					'token'    => 'test_token',
					// Missing device_uuid and origin.
				),
			)
		);

		$push_token = new PushToken();
		$push_token->set_id( $post_id );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t read push token because the push token record is malformed.' );
		$this->expectExceptionCode( 400 );

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
		$this->expectExceptionMessage( 'Can\'t update push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->update( $push_token );
	}

	/**
	 * Tests the delete method throws exception when push token has no ID.
	 */
	public function test_it_throws_exception_when_deleting_push_token_without_id() {
		$data_store = new PushTokensDataStore();
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t delete push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

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
		$new_push_token->set_origin( $original_push_token->get_origin() );
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
		$new_push_token->set_origin( $original_push_token->get_origin() );
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
		$new_push_token->set_origin( $original_push_token->get_origin() );
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
		$new_push_token->set_origin( $original_push_token->get_origin() );
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
		$new_push_token->set_origin( $original_push_token->get_origin() );
		$new_push_token->set_device_uuid( $original_push_token->get_device_uuid() );
		$new_push_token->set_token( $original_push_token->get_token() );

		$found_token = $data_store->get_by_token_or_device_id( $new_push_token );

		$this->assertNull( $found_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method returns null when origin
	 * does not match.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_origin_does_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$new_push_token = new PushToken();
		$new_push_token->set_user_id( $original_push_token->get_user_id() );
		$new_push_token->set_platform( $original_push_token->get_platform() );
		$new_push_token->set_origin( 'com.automattic.woocommerce:dev' );
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
			array(
				'platform'    => $push_token->get_platform(),
				'token'       => $push_token->get_token(),
				'device_uuid' => $push_token->get_device_uuid(),
				'origin'      => $push_token->get_origin(),
			),
			$meta
		);
	}

	/**
	 * Tests the read_meta method throws exception when push token ID is not set.
	 */
	public function test_it_throws_exception_when_reading_meta_without_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t read meta for push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->read_meta( $push_token );
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => 'platform',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => PushToken::PLATFORM_IOS,
			)
		);

		$meta_value = get_post_meta( $push_token->get_id(), 'platform', true );

		$this->assertEquals( PushToken::PLATFORM_IOS, $meta_value );
	}

	/**
	 * Tests the update_meta method of the push tokens data store.
	 */
	public function test_it_can_update_meta() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		add_post_meta( $push_token->get_id(), 'platform', PushToken::PLATFORM_IOS );

		$data_store->update_meta(
			$push_token,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => 'platform',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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

		add_post_meta( $push_token->get_id(), 'platform', PushToken::PLATFORM_IOS );

		$data_store->delete_meta(
			$push_token,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
		$this->expectExceptionMessage( 'Can\'t add meta for push token because the meta key was not provided.' );
		$this->expectExceptionCode( 400 );

		$data_store->add_meta(
			$push_token,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
		$this->expectExceptionMessage( 'Can\'t add meta for push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->add_meta(
			$push_token,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => 'test_key',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
		$this->expectExceptionMessage( 'Can\'t update meta for push token because the meta key was not provided.' );
		$this->expectExceptionCode( 400 );

		$data_store->update_meta(
			$push_token,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
		$this->expectExceptionMessage( 'Can\'t update meta for push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->update_meta(
			$push_token,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => 'test_key',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
		$this->expectExceptionMessage( 'Can\'t delete meta for push token because the meta key was not provided.' );
		$this->expectExceptionCode( 400 );

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
		$this->expectExceptionMessage( 'Can\'t delete meta for push token because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->delete_meta(
			$push_token,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			array( 'meta_key' => 'test_key' )
		);
	}

	/**
	 * Tests the get_by_token_or_device_id method throws exception when user ID is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_user_id() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_origin( 'com.automattic.woocommerce' );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test_device' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token using token or device UUID because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->get_by_token_or_device_id( $push_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method throws exception when platform is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_platform() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_origin( 'com.automattic.woocommerce' );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test_device' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token using token or device UUID because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->get_by_token_or_device_id( $push_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method throws exception when origin is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_origin() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test_device' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token using token or device UUID because the push token data provided is invalid.' );
		$this->expectExceptionCode( 400 );

		$data_store->get_by_token_or_device_id( $push_token );
	}

	/**
	 * Tests the get_by_token_or_device_id method throws exception when both token and device_uuid are missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_token_and_device_uuid() {
		$data_store = new PushTokensDataStore();

		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token: token or device UUID must be provided.' );
		$this->expectExceptionCode( 400 );

		$data_store->get_by_token_or_device_id( $push_token );
	}

	/**
	 * Tests that browser tokens can be created without device_uuid and then read back.
	 */
	public function test_it_can_create_and_read_browser_token_without_device_uuid() {
		$data_store = new PushTokensDataStore();

		// Create a browser token without device_uuid.
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( '{"endpoint":"https://example.com/push","keys":{"auth":"test","p256dh":"test"}}' );
		$push_token->set_platform( PushToken::PLATFORM_BROWSER );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$data_store->create( $push_token );

		$this->assertNotNull( $push_token->get_id() );

		// Now try to read it back.
		$read_token = new PushToken();
		$read_token->set_id( $push_token->get_id() );

		$data_store->read( $read_token );

		$this->assertEquals( $push_token->get_id(), $read_token->get_id() );
		$this->assertEquals( $push_token->get_user_id(), $read_token->get_user_id() );
		$this->assertEquals( $push_token->get_platform(), $read_token->get_platform() );
		$this->assertEquals( $push_token->get_token(), $read_token->get_token() );
		$this->assertEquals( $push_token->get_origin(), $read_token->get_origin() );
		$this->assertNull( $read_token->get_device_uuid() );
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
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid-' . wp_rand() );
		$push_token->set_origin( 'com.automattic.woocommerce' );

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

		$platform    = get_post_meta( $push_token->get_id(), 'platform', true );
		$token       = get_post_meta( $push_token->get_id(), 'token', true );
		$device_uuid = get_post_meta( $push_token->get_id(), 'device_uuid', true );
		$origin      = get_post_meta( $push_token->get_id(), 'origin', true );

		$this->assertEquals( $push_token->get_platform(), $platform );
		$this->assertEquals( $push_token->get_token(), $token );
		$this->assertEquals( $push_token->get_device_uuid(), $device_uuid );
		$this->assertEquals( $push_token->get_origin(), $origin );
	}
}
