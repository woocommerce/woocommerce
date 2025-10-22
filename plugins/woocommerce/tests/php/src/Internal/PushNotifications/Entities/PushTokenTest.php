<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Entities;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use WC_Unit_Test_Case;

/**
 * PushToken test.
 *
 * @covers PushToken
 */
class PushTokenTest extends WC_Unit_Test_Case {
	/**
	 * Tests it's possible to set and get the ID.
	 */
	public function test_it_can_get_and_set_id() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );

		$this->assertEquals( 1, $push_token->get_id() );
	}

	/**
	 * Tests it's possible to set and get the user ID.
	 */
	public function test_it_can_get_and_set_user_id() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );

		$this->assertEquals( 1, $push_token->get_user_id() );
	}

	/**
	 * Tests it's possible to set and get the token.
	 */
	public function test_it_can_get_and_set_token() {
		$push_token = new PushToken();
		$push_token->set_token( 'ABCDEF123ABCDEF123ABCDEF123' );

		$this->assertEquals( 'ABCDEF123ABCDEF123ABCDEF123', $push_token->get_token() );
	}

	/**
	 * Tests it's possible to set and get the device UUID.
	 */
	public function test_it_can_get_and_set_device_uuid() {
		$push_token = new PushToken();
		$push_token->set_device_uuid( 'ABCDEF-123ABC-DEF123-ABCDEF-123' );

		$this->assertEquals( 'ABCDEF-123ABC-DEF123-ABCDEF-123', $push_token->get_device_uuid() );
	}

	/**
	 * Tests it's possible to set and get the platform.
	 */
	public function test_it_can_get_and_set_platform() {
		$push_token = new PushToken();
		$push_token->set_platform( PushToken::PLATFORM_IOS );

		$this->assertEquals( PushToken::PLATFORM_IOS, $push_token->get_platform() );
	}

	/**
	 * Tests can_be_created returns true when all fields are set except ID.
	 */
	public function test_it_can_be_created_when_all_fields_are_set_except_id() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertTrue( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_created returns false when ID is already set.
	 */
	public function test_it_cannot_be_created_when_id_is_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_created returns false when user ID is already set.
	 */
	public function test_it_cannot_be_created_when_user_id_is_missing() {
		$push_token = new PushToken();
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_created returns false when platform is already set.
	 */
	public function test_it_cannot_be_created_when_platform_is_missing() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_created returns false when token is already set.
	 */
	public function test_it_cannot_be_created_when_token_is_missing() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_created returns false when device UUID is already set.
	 */
	public function test_it_cannot_be_created_when_device_uuid_is_missing() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_updated returns true when all required fields are set.
	 */
	public function test_it_can_be_updated_when_all_fields_are_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertTrue( $push_token->can_be_updated() );
	}

	/**
	 * Tests can_be_updated returns false when ID is not set.
	 */
	public function test_it_cannot_be_updated_when_id_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * Tests can_be_updated returns false when user ID is is not set.
	 */
	public function test_it_cannot_be_updated_when_user_id_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * Tests can_be_updated returns false when platform is is not set.
	 */
	public function test_it_cannot_be_updated_when_platform_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * Tests can_be_updated returns false when device UUID is is not set.
	 */
	public function test_it_cannot_be_updated_when_device_uuid_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * Tests can_be_updated returns false when token is is not set.
	 */
	public function test_it_cannot_be_updated_when_token_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( 'com.automattic.woocommerce' );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * Tests can_be_read returns true when ID is set.
	 */
	public function test_it_can_be_read_when_id_is_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );

		$this->assertTrue( $push_token->can_be_read() );
	}

	/**
	 * Tests can_be_read returns false when ID is not set.
	 */
	public function test_it_cannot_be_read_when_id_is_not_set() {
		$push_token = new PushToken();

		$this->assertFalse( $push_token->can_be_read() );
	}

	/**
	 * Tests can_be_deleted returns true when ID is set.
	 */
	public function test_it_can_be_deleted_when_id_is_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );

		$this->assertTrue( $push_token->can_be_deleted() );
	}

	/**
	 * Tests can_be_deleted returns false when ID is not set.
	 */
	public function test_it_cannot_be_deleted_when_id_is_not_set() {
		$push_token = new PushToken();

		$this->assertFalse( $push_token->can_be_deleted() );
	}

	/**
	 * Tests it's possible to set and get the origin.
	 */
	public function test_it_can_get_and_set_origin() {
		$push_token = new PushToken();
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertEquals( PushToken::ORIGIN_WOOCOMMERCE_IOS, $push_token->get_origin() );
	}

	/**
	 * Tests it's possible to set device UUID to null.
	 */
	public function test_it_can_set_device_uuid_to_null() {
		$push_token = new PushToken();
		$push_token->set_device_uuid( 'test-uuid' );
		$push_token->set_device_uuid( null );

		$this->assertNull( $push_token->get_device_uuid() );
	}

	/**
	 * Tests can_be_created returns true for browser tokens without device UUID.
	 */
	public function test_it_can_be_created_for_browser_without_device_uuid() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_BROWSER );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertTrue( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_updated returns true for browser tokens without device UUID.
	 */
	public function test_it_can_be_updated_for_browser_without_device_uuid() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_BROWSER );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertTrue( $push_token->can_be_updated() );
	}

	/**
	 * Tests can_be_created returns false when origin is missing.
	 */
	public function test_it_cannot_be_created_when_origin_is_missing() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * Tests can_be_updated returns false when origin is missing.
	 */
	public function test_it_cannot_be_updated_when_origin_is_missing() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * Tests set_platform throws exception with invalid platform.
	 */
	public function test_it_throws_exception_when_setting_invalid_platform() {
		$push_token = new PushToken();

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Platform for PushToken is invalid.' );

		$push_token->set_platform( 'invalid' );
	}
}
