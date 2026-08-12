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
	 * @testdox Should return tokens for users with matching roles.
	 */
	public function test_get_tokens_for_roles_returns_tokens_for_matching_users(): void {
		$admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$data_store = new PushTokensDataStore();

		$data_store->create(
			array(
				'user_id'       => $admin_id,
				'token'         => 'admin_token_' . wp_rand(),
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'admin-device-' . wp_rand(),
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$tokens = $data_store->get_tokens_for_roles( array( 'administrator' ) );

		$this->assertCount( 1, $tokens );
		$this->assertInstanceOf( PushToken::class, $tokens[0] );
		$this->assertSame( $admin_id, $tokens[0]->get_user_id() );
	}

	/**
	 * @testdox Should return empty array when no users have the specified role.
	 */
	public function test_get_tokens_for_roles_returns_empty_when_no_users_have_role(): void {
		$data_store = new PushTokensDataStore();

		$tokens = $data_store->get_tokens_for_roles( array( 'shop_manager' ) );

		$this->assertSame( array(), $tokens );
	}

	/**
	 * @testdox Should return empty array when users have the role but no tokens.
	 */
	public function test_get_tokens_for_roles_returns_empty_when_users_have_no_tokens(): void {
		$this->factory->user->create( array( 'role' => 'administrator' ) );
		$data_store = new PushTokensDataStore();

		$tokens = $data_store->get_tokens_for_roles( array( 'administrator' ) );

		$this->assertSame( array(), $tokens );
	}

	/**
	 * @testdox Should skip malformed tokens and return only valid ones.
	 */
	public function test_get_tokens_for_roles_skips_malformed_tokens(): void {
		$admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$data_store = new PushTokensDataStore();

		wp_insert_post(
			array(
				'post_author' => $admin_id,
				'post_type'   => PushToken::POST_TYPE,
				'post_status' => 'private',
				'meta_input'  => array(
					'platform' => PushToken::PLATFORM_APPLE,
					'token'    => 'partial_token',
				),
			)
		);

		$data_store->create(
			array(
				'user_id'       => $admin_id,
				'token'         => 'valid_token',
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'valid-device',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$tokens = $data_store->get_tokens_for_roles( array( 'administrator' ) );

		$this->assertCount( 1, $tokens );
		$this->assertSame( 'valid-device', $tokens[0]->get_device_uuid() );
	}

	/**
	 * @testdox Should return tokens from multiple users with different matching roles.
	 */
	public function test_get_tokens_for_roles_returns_tokens_from_multiple_roles(): void {
		$admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$manager_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		$data_store = new PushTokensDataStore();

		$data_store->create(
			array(
				'user_id'       => $admin_id,
				'token'         => 'admin_token',
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'admin-device',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$data_store->create(
			array(
				'user_id'       => $manager_id,
				'token'         => 'manager_token',
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'manager-device',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$tokens     = $data_store->get_tokens_for_roles( array( 'administrator', 'shop_manager' ) );
		$device_ids = array_map( fn ( PushToken $t ) => $t->get_device_uuid(), $tokens );

		$this->assertCount( 2, $tokens );
		$this->assertContains( 'admin-device', $device_ids );
		$this->assertContains( 'manager-device', $device_ids );
	}

	/**
	 * @testdox Should not return tokens for users without the specified role.
	 */
	public function test_get_tokens_for_roles_excludes_users_without_role(): void {
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$data_store    = new PushTokensDataStore();

		$data_store->create(
			array(
				'user_id'       => $subscriber_id,
				'token'         => 'subscriber_token_' . wp_rand(),
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'subscriber-device-' . wp_rand(),
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$tokens = $data_store->get_tokens_for_roles( array( 'administrator' ) );

		$this->assertSame( array(), $tokens );
	}

	/**
	 * @testdox Should exclude tokens whose owner no longer exists.
	 */
	public function test_get_tokens_for_roles_excludes_tokens_of_deleted_users(): void {
		$admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$data_store = new PushTokensDataStore();

		$this->create_push_token_for_user( $data_store, $admin_id );

		wp_delete_user( $admin_id );

		$tokens = $data_store->get_tokens_for_roles( array( 'administrator' ) );

		$this->assertSame( array(), $tokens );
	}

	/**
	 * @testdox Should paginate tokens and report totals.
	 */
	public function test_get_tokens_for_roles_supports_pagination(): void {
		$admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$data_store = new PushTokensDataStore();

		for ( $i = 0; $i < 3; $i++ ) {
			$this->create_push_token_for_user( $data_store, $admin_id );
		}

		$page_one = $data_store->get_tokens_for_roles( array( 'administrator' ), 1, 2 );

		$this->assertCount( 2, $page_one['tokens'] );
		$this->assertSame( 3, $page_one['total'] );
		$this->assertSame( 2, $page_one['total_pages'] );

		$page_two = $data_store->get_tokens_for_roles( array( 'administrator' ), 2, 2 );

		$this->assertCount( 1, $page_two['tokens'] );
	}

	/**
	 * @testdox Should not run any user query when no tokens exist.
	 */
	public function test_get_tokens_for_roles_skips_user_query_when_no_tokens_exist(): void {
		$this->factory->user->create( array( 'role' => 'administrator' ) );
		$data_store = new PushTokensDataStore();

		$user_queries = 0;
		$count        = function () use ( &$user_queries ) {
			++$user_queries;
		};
		add_action( 'pre_get_users', $count );

		$tokens = $data_store->get_tokens_for_roles( array( 'administrator' ) );

		remove_action( 'pre_get_users', $count );

		$this->assertSame( array(), $tokens );
		$this->assertSame( 0, $user_queries, 'With no stored tokens there is nothing to look up: an empty include must short-circuit before get_users(), because WP_User_Query ignores an empty include argument and would fall back to the unrestricted role scan.' );
	}

	/**
	 * @testdox Should only run user queries restricted to token owners.
	 */
	public function test_get_tokens_for_roles_only_queries_users_owning_tokens(): void {
		$admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$data_store = new PushTokensDataStore();

		$this->create_push_token_for_user( $data_store, $admin_id );

		$includes = array();
		$capture  = function ( $query ) use ( &$includes ) {
			$includes[] = $query->query_vars['include'];
		};
		add_action( 'pre_get_users', $capture );

		$tokens = $data_store->get_tokens_for_roles( array( 'administrator' ) );

		remove_action( 'pre_get_users', $capture );

		$this->assertCount( 1, $tokens );
		$this->assertNotEmpty( $includes, 'The role lookup should run through WP_User_Query.' );
		foreach ( $includes as $include ) {
			$this->assertNotEmpty( $include, 'User queries on this path must be restricted to token owners: an unrestricted role__in query scans the capabilities meta of every user and does not scale on large sites.' );
		}
	}

	/**
	 * Creates a push token owned by the given user.
	 *
	 * @param PushTokensDataStore $data_store The data store instance.
	 * @param int                 $user_id    The owner user ID.
	 * @return PushToken The created push token object.
	 */
	private function create_push_token_for_user( PushTokensDataStore $data_store, int $user_id ): PushToken {
		return $data_store->create(
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
	 * @testdox Tests reading a push token populates the registration and confirmation timestamps.
	 */
	public function test_read_populates_timestamps_from_the_post_record() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$post = get_post( $push_token->get_id() );
		$read = $data_store->read( $push_token->get_id() );

		$this->assertSame( $post->post_date_gmt, $read->get_created_at_gmt() );
		$this->assertSame( $post->post_modified_gmt, $read->get_last_confirmed_at_gmt() );
	}

	/**
	 * @testdox Tests re-registering a device advances the confirmation timestamp past the registration one.
	 */
	public function test_read_reflects_a_re_registered_token_as_a_later_confirmation_time() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		/**
		 * `post_modified_gmt` only advances once a second has elapsed, so the
		 * original post date is backdated rather than waiting on the clock.
		 */
		wp_update_post(
			array(
				'ID'                => $push_token->get_id(),
				'post_date_gmt'     => '2026-01-01 00:00:00',
				'post_date'         => '2026-01-01 00:00:00',
				'post_modified_gmt' => '2026-01-01 00:00:00',
				'post_modified'     => '2026-01-01 00:00:00',
			)
		);

		$push_token->set_device_locale( 'fr_FR' );
		$data_store->update( $push_token );

		$read = $data_store->read( $push_token->get_id() );

		$this->assertSame( '2026-01-01 00:00:00', $read->get_created_at_gmt() );
		$this->assertGreaterThan( $read->get_created_at_gmt(), $read->get_last_confirmed_at_gmt() );
	}

	/**
	 * @testdox Tests a token has no last send time until it has actually been sent.
	 */
	public function test_last_send_at_is_null_for_a_token_that_has_never_been_sent() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$this->assertNull( $data_store->read( $push_token->get_id() )->get_last_send_at_gmt() );
	}

	/**
	 * @testdox Tests recording a send stamps every supplied token with the same time.
	 */
	public function test_record_last_send_stamps_all_supplied_tokens() {
		$data_store = new PushTokensDataStore();
		$first      = $this->create_test_push_token();
		$second     = $this->create_test_push_token();

		$data_store->record_last_send( array( $first, $second ) );
		$data_store->flush_last_send();

		$first_send_at  = $data_store->read( $first->get_id() )->get_last_send_at_gmt();
		$second_send_at = $data_store->read( $second->get_id() )->get_last_send_at_gmt();

		$this->assertNotNull( $first_send_at );
		$this->assertSame( $first_send_at, $second_send_at );
	}

	/**
	 * @testdox Tests recording a send twice replaces the stamp rather than accumulating rows.
	 *
	 * The batched write bypasses the meta API, so it has to leave exactly one
	 * row per token behind — a duplicate would make `get_post_meta( …, true )`
	 * return an arbitrary one of them.
	 */
	public function test_record_last_send_replaces_the_previous_stamp() {
		global $wpdb;

		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();
		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();

		$row_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
				$push_token->get_id(),
				PushTokensDataStore::LAST_SEND_AT_META_KEY
			)
		);

		$this->assertSame( 1, $row_count );
	}

	/**
	 * @testdox Tests recording a send leaves the rest of the token record untouched.
	 */
	public function test_record_last_send_does_not_disturb_other_token_data() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();
		$read = $data_store->read( $push_token->get_id() );

		$this->assertSame( $push_token->get_token(), $read->get_token() );
		$this->assertSame( $push_token->get_device_uuid(), $read->get_device_uuid() );
		$this->assertSame( $push_token->get_device_locale(), $read->get_device_locale() );
		$this->assertSame( $push_token->get_metadata(), $read->get_metadata() );
	}

	/**
	 * @testdox Tests updating a token preserves its last send time.
	 *
	 * The app re-registers a device whenever its locale or metadata changes,
	 * which must not wipe the send history that update path knows nothing about.
	 */
	public function test_updating_a_token_preserves_its_last_send_time() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();
		$recorded = $data_store->read( $push_token->get_id() )->get_last_send_at_gmt();

		$push_token->set_device_locale( 'fr_FR' );
		$data_store->update( $push_token );

		$this->assertSame( $recorded, $data_store->read( $push_token->get_id() )->get_last_send_at_gmt() );
	}

	/**
	 * @testdox Tests recording a send defers the write until the buffer is flushed.
	 *
	 * A request can process many notifications against the same few tokens, so
	 * the write is buffered and happens once rather than once per notification.
	 */
	public function test_record_last_send_defers_the_write_until_flushed() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );

		$this->assertNull( $data_store->read( $push_token->get_id() )->get_last_send_at_gmt() );

		$data_store->flush_last_send();

		$this->assertNotNull( $data_store->read( $push_token->get_id() )->get_last_send_at_gmt() );
	}

	/**
	 * @testdox Tests repeated recording before a flush results in a single write.
	 */
	public function test_repeated_recording_before_a_flush_writes_once() {
		global $wpdb;

		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		for ( $i = 0; $i < 5; $i++ ) {
			$data_store->record_last_send( array( $push_token ) );
		}

		$data_store->flush_last_send();

		$row_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
				$push_token->get_id(),
				PushTokensDataStore::LAST_SEND_AT_META_KEY
			)
		);

		$this->assertSame( 1, $row_count );
	}

	/**
	 * @testdox Tests flushing an already flushed buffer is a no-op.
	 */
	public function test_flushing_twice_does_not_write_again() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();

		$recorded = $data_store->read( $push_token->get_id() )->get_last_send_at_gmt();

		$data_store->flush_last_send();

		$this->assertSame( $recorded, $data_store->read( $push_token->get_id() )->get_last_send_at_gmt() );
	}

	/**
	 * @testdox Tests a second send updates the existing row rather than replacing it.
	 *
	 * The row is updated in place so that repeatedly sending to the same device
	 * does not consume `meta_id` values or churn the primary key on what is the
	 * busiest write path in the feature.
	 */
	public function test_a_second_send_updates_the_row_in_place() {
		global $wpdb;

		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();

		$meta_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
				$push_token->get_id(),
				PushTokensDataStore::LAST_SEND_AT_META_KEY
			)
		);

		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();

		$this->assertSame(
			$meta_id,
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
					$push_token->get_id(),
					PushTokensDataStore::LAST_SEND_AT_META_KEY
				)
			)
		);
	}

	/**
	 * @testdox Tests every token is stamped when the buffer spans more than one chunk.
	 */
	public function test_flush_stamps_every_token_across_chunks() {
		$data_store = new PushTokensDataStore();
		$tokens     = array();

		for ( $i = 0; $i < PushTokensDataStore::LAST_SEND_CHUNK_SIZE + 5; $i++ ) {
			$tokens[] = $this->create_test_push_token();
		}

		$data_store->record_last_send( $tokens );
		$data_store->flush_last_send();

		foreach ( $tokens as $token ) {
			$this->assertNotNull( $data_store->read( $token->get_id() )->get_last_send_at_gmt() );
		}
	}

	/**
	 * @testdox Tests the Action Scheduler hook flushes buffered stamps.
	 *
	 * The safety net and retry paths run under a queue runner that can be killed
	 * before shutdown, so the buffer is also flushed after each action. This
	 * exists only for a path a hook creates, so nothing else would catch its
	 * removal.
	 */
	public function test_the_action_scheduler_hook_flushes_buffered_stamps() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Firing Action Scheduler's hook, not declaring one.
		do_action( 'action_scheduler_after_execute', 1, null, '' );

		$this->assertNotNull( $data_store->read( $push_token->get_id() )->get_last_send_at_gmt() );
	}

	/**
	 * @testdox Tests a failed read of existing stamps does not insert duplicates.
	 *
	 * `wpdb::query()` returns false without setting `last_error` when the `query`
	 * filter empties the statement, so a failed read must not be taken as "no
	 * rows exist". `wp_postmeta` has no unique key to catch the duplicates that
	 * would follow.
	 */
	public function test_a_failed_read_of_existing_stamps_does_not_insert_duplicates() {
		global $wpdb;

		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();

		$empty_the_select = function ( $query ) {
			return false !== strpos( $query, 'SELECT post_id' ) && false !== strpos( $query, 'last_send_at_gmt' )
				? ''
				: $query;
		};

		add_filter( 'query', $empty_the_select );
		$data_store->record_last_send( array( $push_token ) );
		$data_store->flush_last_send();
		remove_filter( 'query', $empty_the_select );

		$row_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
				$push_token->get_id(),
				PushTokensDataStore::LAST_SEND_AT_META_KEY
			)
		);

		$this->assertSame( 1, $row_count );
	}

	/**
	 * @testdox Tests an error raised while writing stamps does not escape the flush.
	 *
	 * The flush runs on `action_scheduler_after_execute`, which fires between an
	 * action completing and being marked complete, so anything escaping here
	 * records a delivered notification's action as failed. Errors do not extend
	 * Exception, which is why the catch is on Throwable.
	 */
	public function test_an_error_while_writing_stamps_does_not_escape_the_flush() {
		$data_store = new PushTokensDataStore();
		$push_token = $this->create_test_push_token();

		$raise_an_error = function ( $query ) {
			if ( false !== strpos( $query, 'last_send_at_gmt' ) ) {
				throw new \Error( 'Raised for testing.' );
			}

			return $query;
		};

		add_filter( 'query', $raise_an_error );

		try {
			$data_store->record_last_send( array( $push_token ) );
			$data_store->flush_last_send();
		} finally {
			remove_filter( 'query', $raise_an_error );
		}

		$this->assertNull( $data_store->read( $push_token->get_id() )->get_last_send_at_gmt() );
	}

	/**
	 * @testdox Tests recording a send with no tokens is a no-op.
	 */
	public function test_record_last_send_ignores_an_empty_token_list() {
		global $wpdb;

		$data_store = new PushTokensDataStore();
		$data_store->record_last_send( array() );
		$data_store->flush_last_send();

		$row_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
				PushTokensDataStore::LAST_SEND_AT_META_KEY
			)
		);

		$this->assertSame( 0, $row_count );
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
