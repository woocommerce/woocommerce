<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\DataStores;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenInvalidDataException;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenNotFoundException;
use WC_Unit_Test_Case;

/**
 * Tests for the PushTokensDataStore class.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore
 */
class PushTokensDataStoreTest extends WC_Unit_Test_Case {
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
	 * @testdox Tests the create method of the push tokens data store.
	 */
	public function test_it_can_create_push_token() {
		$data_store = new PushTokensDataStore();

		$data = array(
			'user_id'       => 1,
			'token'         => 'test_token_12345',
			'platform'      => PushToken::PLATFORM_APPLE,
			'device_uuid'   => 'device-uuid-123',
			'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
			'device_locale' => 'en_US',
			'metadata'      => array( 'app_version' => '1.0' ),
		);

		$push_token = $data_store->create( $data );

		$this->assertNotNull( $push_token->get_id() );
		$this->assert_push_token_in_db( $push_token );

		$post = get_post( $push_token->get_id() );
		$this->assertEquals( 'private', $post->post_status );
	}

	/**
	 * @testdox Tests the read method of the push tokens data store.
	 */
	public function test_it_can_read_push_token() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();
		$read_push_token     = $data_store->read( $original_push_token->get_id() );

		$this->assertEquals( $original_push_token->get_id(), $read_push_token->get_id() );
		$this->assertEquals( $original_push_token->get_user_id(), $read_push_token->get_user_id() );
		$this->assertEquals( $original_push_token->get_platform(), $read_push_token->get_platform() );
		$this->assertEquals( $original_push_token->get_token(), $read_push_token->get_token() );
		$this->assertEquals( $original_push_token->get_device_uuid(), $read_push_token->get_device_uuid() );
		$this->assertEquals( $original_push_token->get_origin(), $read_push_token->get_origin() );
		$this->assertEquals( $original_push_token->get_device_locale(), $read_push_token->get_device_locale() );
		$this->assertEquals( $original_push_token->get_metadata(), $read_push_token->get_metadata() );
	}

	/**
	 * @testdox Tests the update method of the push tokens data store.
	 */
	public function test_it_can_update_push_token() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$push_token->set_token( 'updated_token' );
		$push_token->set_device_uuid( 'updated-device-uuid' );
		$data_store->update( $push_token );

		$updated_token = $data_store->read( $push_token->get_id() );

		$this->assertEquals( 'updated_token', $updated_token->get_token() );
		$this->assertEquals( 'updated-device-uuid', $updated_token->get_device_uuid() );

		$post = get_post( $push_token->get_id() );
		$this->assertEquals( 'private', $post->post_status );
	}

	/**
	 * @testdox Tests the update method removes device_uuid meta when updating
	 * it to null.
	 */
	public function test_it_removes_device_uuid_meta_when_updating_to_null() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$this->assertNotNull( $push_token->get_device_uuid() );
		$device_uuid = get_post_meta( $push_token->get_id(), 'device_uuid', true );
		$this->assertNotEmpty( $device_uuid );

		$push_token->set_platform( PushToken::PLATFORM_BROWSER );
		$push_token->set_device_uuid( null );
		$data_store->update( $push_token );

		$device_uuid = get_post_meta( $push_token->get_id(), 'device_uuid', true );
		$this->assertEmpty( $device_uuid );
	}

	/**
	 * @testdox Tests the delete method of the push tokens data store.
	 */
	public function test_it_can_delete_push_token() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();
		$data_store->delete( $push_token->get_id() );

		$this->assertNull( get_post( $push_token->get_id() ) );
	}

	/**
	 * @testdox Tests the create method throws exception when push token data is
	 * incomplete.
	 */
	public function test_it_throws_exception_when_creating_push_token_with_incomplete_data() {
		$data_store = new PushTokensDataStore();

		$data = array(
			'user_id' => 1,
			'token'   => 'test_token',
		);

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Can\'t create push token because the push token data provided is invalid.' );

		$data_store->create( $data );
	}

	/**
	 * @testdox Tests the read method throws exception when push token ID is
	 * invalid.
	 */
	public function test_it_throws_exception_when_reading_push_token_with_invalid_id() {
		$data_store = new PushTokensDataStore();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'ID must be a positive integer.' );

		$data_store->read( 0 );
	}

	/**
	 * @testdox Tests the read method throws exception when push token is not
	 * found.
	 */
	public function test_it_throws_exception_when_reading_push_token_that_does_not_exist() {
		$data_store = new PushTokensDataStore();

		$this->expectException( PushTokenNotFoundException::class );
		$this->expectExceptionMessage( 'Push token could not be found.' );

		$data_store->read( 999999 );
	}

	/**
	 * @testdox Tests the read method throws exception when the post exists but
	 * is not the correct post type.
	 */
	public function test_it_throws_exception_when_reading_push_token_with_wrong_post_type() {
		$data_store = new PushTokensDataStore();

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Test Post',
				'post_type'   => 'post',
				'post_status' => 'private',
			)
		);

		$this->expectException( PushTokenNotFoundException::class );
		$this->expectExceptionMessage( 'Push token could not be found.' );

		$data_store->read( $post_id );
	}

	/**
	 * @testdox Tests the read method throws exception when push token metadata
	 * is malformed/missing.
	 */
	public function test_it_throws_exception_when_reading_push_token_with_malformed_metadata() {
		$data_store = new PushTokensDataStore();

		$post_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array(
					'platform' => PushToken::PLATFORM_APPLE,
					'token'    => 'test_token',
					// Missing device_uuid and origin.
				),
			)
		);

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Can\'t read push token because the push token record is malformed.' );

		$data_store->read( $post_id );
	}

	/**
	 * @testdox Tests the update method throws exception when push token data
	 * would result in invalid state.
	 */
	public function test_it_throws_exception_when_updating_push_token_with_invalid_data() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Can\'t update push token because the push token data provided is invalid.' );

		$push_token->set_platform( PushToken::PLATFORM_APPLE );
		$push_token->set_device_uuid( null );
		$data_store->update( $push_token );
	}

	/**
	 * @testdox Tests the delete method throws exception when push token ID is
	 * invalid.
	 */
	public function test_it_throws_exception_when_deleting_push_token_with_invalid_id() {
		$data_store = new PushTokensDataStore();

		$this->expectException( PushTokenNotFoundException::class );
		$this->expectExceptionMessage( 'Push token could not be found.' );

		$data_store->delete( 0 );
	}

	/**
	 * @testdox Tests the delete method throws exception when the post exists but
	 * is not the correct post type.
	 */
	public function test_it_throws_exception_when_deleting_push_token_with_wrong_post_type() {
		$data_store = new PushTokensDataStore();

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Test Post',
				'post_type'   => 'post',
				'post_status' => 'private',
			)
		);

		$this->expectException( PushTokenNotFoundException::class );
		$this->expectExceptionMessage( 'Push token could not be found.' );

		$data_store->delete( $post_id );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method finds push token by
	 * token when user ID, platform, and origin match.
	 */
	public function test_it_can_get_by_token_if_platform_and_user_id_matches() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$data = array(
			'user_id'     => $original_push_token->get_user_id(),
			'token'       => $original_push_token->get_token(),
			'platform'    => $original_push_token->get_platform(),
			'origin'      => $original_push_token->get_origin(),
			'device_uuid' => 'different-device',
		);

		$found_token = $data_store->get_by_token_or_device_id( $data );

		$this->assertNotNull( $found_token );
		$this->assertEquals( $original_push_token->get_id(), $found_token->get_id() );
		$this->assertEquals( $original_push_token->get_token(), $found_token->get_token() );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method finds push token by
	 * device UUID when user ID, platform, and origin match.
	 */
	public function test_it_can_get_by_device_uuid_if_platform_and_user_id_matches() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$data = array(
			'user_id'     => $original_push_token->get_user_id(),
			'platform'    => $original_push_token->get_platform(),
			'origin'      => $original_push_token->get_origin(),
			'device_uuid' => $original_push_token->get_device_uuid(),
			'token'       => 'different_token',
		);

		$found_token = $data_store->get_by_token_or_device_id( $data );

		$this->assertNotNull( $found_token );
		$this->assertEquals( $original_push_token->get_id(), $found_token->get_id() );
		$this->assertEquals( $original_push_token->get_device_uuid(), $found_token->get_device_uuid() );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method returns null when
	 * user ID, platform, and origin match but token and device UUID don't.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_token_and_device_do_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$data = array(
			'user_id'     => $original_push_token->get_user_id(),
			'platform'    => $original_push_token->get_platform(),
			'origin'      => $original_push_token->get_origin(),
			'device_uuid' => 'different-device',
			'token'       => 'different_token',
		);

		$found_token = $data_store->get_by_token_or_device_id( $data );

		$this->assertNull( $found_token );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method returns null when
	 * user ID does not match.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_user_id_does_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$data = array(
			'user_id'     => 999,
			'platform'    => $original_push_token->get_platform(),
			'origin'      => $original_push_token->get_origin(),
			'device_uuid' => $original_push_token->get_device_uuid(),
			'token'       => $original_push_token->get_token(),
		);

		$found_token = $data_store->get_by_token_or_device_id( $data );

		$this->assertNull( $found_token );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method returns null when
	 * platform does not match.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_platform_does_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$data = array(
			'user_id'     => $original_push_token->get_user_id(),
			'platform'    => PushToken::PLATFORM_ANDROID,
			'origin'      => $original_push_token->get_origin(),
			'device_uuid' => $original_push_token->get_device_uuid(),
			'token'       => $original_push_token->get_token(),
		);

		$found_token = $data_store->get_by_token_or_device_id( $data );

		$this->assertNull( $found_token );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method returns null when
	 * origin does not match.
	 */
	public function test_it_cannot_get_by_token_or_device_id_if_origin_does_not_match() {
		$data_store = new PushTokensDataStore();

		$original_push_token = $this->create_test_push_token();

		$data = array(
			'user_id'     => $original_push_token->get_user_id(),
			'platform'    => $original_push_token->get_platform(),
			'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS_DEV,
			'device_uuid' => $original_push_token->get_device_uuid(),
			'token'       => $original_push_token->get_token(),
		);

		$found_token = $data_store->get_by_token_or_device_id( $data );

		$this->assertNull( $found_token );
	}

	/**
	 * @testdox Tests that browser tokens with null device_uuid don't
	 * incorrectly match each other by empty device_uuid.
	 */
	public function test_it_does_not_match_browser_tokens_by_empty_device_uuid() {
		$data_store = new PushTokensDataStore();

		/**
		 * Create first browser token for user.
		 */
		$browser_token_1 = $data_store->create(
			array(
				'user_id'       => 1,
				'token'         => 'browser_token_1_' . wp_rand(),
				'platform'      => PushToken::PLATFORM_BROWSER,
				'device_uuid'   => null,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		/**
		 * Create second browser token for same user (different browser/tab).
		 */
		$browser_token_2 = $data_store->create(
			array(
				'user_id'       => 1,
				'token'         => 'browser_token_2_' . wp_rand(),
				'platform'      => PushToken::PLATFORM_BROWSER,
				'device_uuid'   => null,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		/**
		 * Try to find browser_token_1 by its token - should only match itself,
		 * not browser_token_2.
		 */
		$data = array(
			'user_id'     => 1,
			'token'       => $browser_token_1->get_token(),
			'platform'    => PushToken::PLATFORM_BROWSER,
			'device_uuid' => null,
			'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
		);

		$found_token = $data_store->get_by_token_or_device_id( $data );

		$this->assertNotNull( $found_token, 'Should find browser_token_1 by its token value' );
		$this->assertEquals( $browser_token_1->get_id(), $found_token->get_id(), 'Should match browser_token_1 ID' );
		$this->assertEquals( $browser_token_1->get_token(), $found_token->get_token(), 'Should match browser_token_1 token' );
		$this->assertNotEquals( $browser_token_2->get_id(), $found_token->get_id(), 'Should not match browser_token_2 ID' );

		/**
		 * Now search with a DIFFERENT token - should return null, not match by
		 * empty device_uuid.
		 */
		$different_data = array(
			'user_id'  => 1,
			'platform' => PushToken::PLATFORM_BROWSER,
			'origin'   => PushToken::ORIGIN_WOOCOMMERCE_IOS,
			'token'    => wp_json_encode(
				array(
					'endpoint' => 'https://example.com/push/subscription3',
					'keys'     => array(
						'auth'   => 'a3',
						'p256dh' => 'p3',
					),
				)
			),
		);

		$found = $data_store->get_by_token_or_device_id( $different_data );
		$this->assertNull( $found, 'Should not match existing tokens by empty device_uuid' );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method throws exception when
	 * user ID is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_user_id() {
		$data_store = new PushTokensDataStore();

		$data = array(
			'platform'    => PushToken::PLATFORM_APPLE,
			'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
			'token'       => 'test_token',
			'device_uuid' => 'test_device',
		);

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token because the push token data provided is invalid.' );

		$data_store->get_by_token_or_device_id( $data );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method throws exception when
	 * platform is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_platform() {
		$data_store = new PushTokensDataStore();

		$data = array(
			'user_id'     => 1,
			'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
			'token'       => 'test_token',
			'device_uuid' => 'test_device',
		);

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token because the push token data provided is invalid.' );

		$data_store->get_by_token_or_device_id( $data );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method throws exception when
	 * origin is missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_origin() {
		$data_store = new PushTokensDataStore();

		$data = array(
			'user_id'     => 1,
			'platform'    => PushToken::PLATFORM_APPLE,
			'token'       => 'test_token',
			'device_uuid' => 'test_device',
		);

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token because the push token data provided is invalid.' );

		$data_store->get_by_token_or_device_id( $data );
	}

	/**
	 * @testdox Tests the get_by_token_or_device_id method throws exception when
	 * both token and device_uuid are missing.
	 */
	public function test_it_throws_exception_when_getting_by_token_or_device_id_without_token_and_device_uuid() {
		$data_store = new PushTokensDataStore();

		$data = array(
			'user_id'  => 1,
			'platform' => PushToken::PLATFORM_APPLE,
			'origin'   => PushToken::ORIGIN_WOOCOMMERCE_IOS,
		);

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Can\'t retrieve push token because the push token data provided is invalid.' );

		$data_store->get_by_token_or_device_id( $data );
	}

	/**
	 * @testdox Tests that browser tokens can be created without device_uuid and
	 * then read back.
	 */
	public function test_it_can_create_and_read_browser_token_without_device_uuid() {
		$data_store = new PushTokensDataStore();

		$data = array(
			'user_id'       => 1,
			'token'         => '{"endpoint":"https://example.com/push","keys":{"auth":"test","p256dh":"test"}}',
			'platform'      => PushToken::PLATFORM_BROWSER,
			'origin'        => PushToken::ORIGIN_BROWSER,
			'device_locale' => 'en_US',
			'metadata'      => array( 'app_version' => '1.0' ),
		);

		$push_token = $data_store->create( $data );

		$this->assertNotNull( $push_token->get_id() );

		$read_token = $data_store->read( $push_token->get_id() );

		$this->assertEquals( $push_token->get_id(), $read_token->get_id() );
		$this->assertEquals( $push_token->get_user_id(), $read_token->get_user_id() );
		$this->assertEquals( $push_token->get_platform(), $read_token->get_platform() );
		$this->assertEquals( $push_token->get_token(), $read_token->get_token() );
		$this->assertEquals( $push_token->get_origin(), $read_token->get_origin() );
		$this->assertNull( $read_token->get_device_uuid() );
	}

	/**
	 * @testdox Tests that a legacy token without device_locale and metadata can
	 * be read with sensible defaults applied.
	 */
	public function test_it_can_read_legacy_token_without_device_locale_and_metadata() {
		$data_store = new PushTokensDataStore();

		$post_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array(
					'platform'    => PushToken::PLATFORM_APPLE,
					'token'       => 'legacy_token_value',
					'device_uuid' => 'legacy-device-uuid',
					'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				),
			)
		);

		$push_token = $data_store->read( $post_id );

		$this->assertEquals( $post_id, $push_token->get_id() );
		$this->assertEquals( 'legacy_token_value', $push_token->get_token() );
		$this->assertEquals( PushToken::DEFAULT_DEVICE_LOCALE, $push_token->get_device_locale() );
		$this->assertEquals( array(), $push_token->get_metadata() );
	}

	/**
	 * @testdox Tests that a legacy token without device_locale and metadata can
	 * be found by get_by_token_or_device_id with defaults applied.
	 */
	public function test_it_can_find_legacy_token_by_token_or_device_id_with_defaults() {
		$data_store = new PushTokensDataStore();

		$post_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array(
					'platform'    => PushToken::PLATFORM_APPLE,
					'token'       => 'legacy_token_value',
					'device_uuid' => 'legacy-device-uuid',
					'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				),
			)
		);

		$found_token = $data_store->get_by_token_or_device_id(
			array(
				'user_id'     => 1,
				'token'       => 'legacy_token_value',
				'platform'    => PushToken::PLATFORM_APPLE,
				'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_uuid' => 'different-device',
			)
		);

		$this->assertNotNull( $found_token );
		$this->assertEquals( $post_id, $found_token->get_id() );
		$this->assertEquals( PushToken::DEFAULT_DEVICE_LOCALE, $found_token->get_device_locale() );
		$this->assertEquals( array(), $found_token->get_metadata() );
	}

	/**
	 * @testdox Tests that a legacy token can be updated with new device_locale
	 * and metadata values.
	 */
	public function test_it_can_update_legacy_token_with_new_locale_and_metadata() {
		$data_store = new PushTokensDataStore();

		$post_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array(
					'platform'    => PushToken::PLATFORM_APPLE,
					'token'       => 'legacy_token_value',
					'device_uuid' => 'legacy-device-uuid',
					'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				),
			)
		);

		$push_token = $data_store->read( $post_id );

		$this->assertEquals( PushToken::DEFAULT_DEVICE_LOCALE, $push_token->get_device_locale() );
		$this->assertEquals( array(), $push_token->get_metadata() );

		$push_token->set_device_locale( 'fr_FR' );
		$push_token->set_metadata( array( 'app_version' => '2.0' ) );
		$data_store->update( $push_token );

		$updated_token = $data_store->read( $post_id );

		$this->assertEquals( 'fr_FR', $updated_token->get_device_locale() );
		$this->assertEquals( array( 'app_version' => '2.0' ), $updated_token->get_metadata() );
	}

	/**
	 * Creates a test push token and saves it to the database.
	 *
	 * @return PushToken The created push token object.
	 */
	private function create_test_push_token(): PushToken {
		$data_store = new PushTokensDataStore();

		$data = array(
			'user_id'       => 1,
			'token'         => 'test_token_' . wp_rand(),
			'platform'      => PushToken::PLATFORM_APPLE,
			'device_uuid'   => 'test-device-uuid-' . wp_rand(),
			'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
			'device_locale' => 'en_US',
			'metadata'      => array( 'app_version' => '1.0' ),
		);

		return $data_store->create( $data );
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

		$platform      = get_post_meta( $push_token->get_id(), 'platform', true );
		$token         = get_post_meta( $push_token->get_id(), 'token', true );
		$device_uuid   = get_post_meta( $push_token->get_id(), 'device_uuid', true );
		$origin        = get_post_meta( $push_token->get_id(), 'origin', true );
		$device_locale = get_post_meta( $push_token->get_id(), 'device_locale', true );
		$metadata      = get_post_meta( $push_token->get_id(), 'metadata', true );

		$this->assertEquals( $push_token->get_platform(), $platform );
		$this->assertEquals( $push_token->get_token(), $token );
		$this->assertEquals( $push_token->get_device_uuid(), $device_uuid );
		$this->assertEquals( $push_token->get_origin(), $origin );
		$this->assertEquals( $push_token->get_device_locale(), $device_locale );
		$this->assertEquals( $push_token->get_metadata(), $metadata );
	}
}
