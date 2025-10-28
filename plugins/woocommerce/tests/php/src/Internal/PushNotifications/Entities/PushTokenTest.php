<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Entities;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * PushToken test.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken
 */
class PushTokenTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Tests it's possible to set and get the ID.
	 */
	public function test_it_can_get_and_set_id() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );

		$this->assertEquals( 1, $push_token->get_id() );
	}

	/**
	 * @testdox Tests set_id throws exception with zero value.
	 */
	public function test_it_throws_exception_when_setting_id_to_zero() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'ID must be a positive integer.' );

		$push_token->set_id( 0 );
	}

	/**
	 * @testdox Tests set_id throws exception with negative value.
	 */
	public function test_it_throws_exception_when_setting_id_to_negative() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'ID must be a positive integer.' );

		$push_token->set_id( -1 );
	}

	/**
	 * @testdox Tests it's possible to set and get the user ID.
	 */
	public function test_it_can_get_and_set_user_id() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );

		$this->assertEquals( 1, $push_token->get_user_id() );
	}

	/**
	 * @testdox Tests set_user_id throws exception with zero value.
	 */
	public function test_it_throws_exception_when_setting_user_id_to_zero() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'User ID must be a positive integer.' );

		$push_token->set_user_id( 0 );
	}

	/**
	 * @testdox Tests set_user_id throws exception with negative value.
	 */
	public function test_it_throws_exception_when_setting_user_id_to_negative() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'User ID must be a positive integer.' );

		$push_token->set_user_id( -1 );
	}

	/**
	 * @testdox Tests it's possible to set and get the token.
	 */
	public function test_it_can_get_and_set_token() {
		$push_token = new PushToken();
		$push_token->set_token( 'ABCDEF123ABCDEF123ABCDEF123' );

		$this->assertEquals( 'ABCDEF123ABCDEF123ABCDEF123', $push_token->get_token() );
	}

	/**
	 * @testdox Tests set_token throws exception with empty string.
	 */
	public function test_it_throws_exception_when_setting_empty_token() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Token cannot be empty.' );

		$push_token->set_token( '' );
	}

	/**
	 * @testdox Tests set_token throws exception with whitespace-only string.
	 */
	public function test_it_throws_exception_when_setting_whitespace_token() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Token cannot be empty.' );

		$push_token->set_token( '   ' );
	}

	/**
	 * @testdox Tests set_token throws exception when token exceeds 4096 characters.
	 */
	public function test_it_throws_exception_when_setting_token_too_long() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Token is too long.' );

		$push_token->set_token( str_repeat( 'A', 4097 ) );
	}

	/**
	 * @testdox Tests set_token accepts token at maximum length of 4096 characters.
	 */
	public function test_it_accepts_token_at_maximum_length() {
		$push_token = new PushToken();
		$max_token  = str_repeat( 'A', 4096 );

		$push_token->set_token( $max_token );

		$this->assertEquals( $max_token, $push_token->get_token() );
	}

	/**
	 * @testdox Tests set_token trims whitespace from token.
	 */
	public function test_it_trims_whitespace_from_token() {
		$push_token = new PushToken();
		$push_token->set_token( '  test_token  ' );

		$this->assertEquals( 'test_token', $push_token->get_token() );
	}

	/**
	 * @testdox Tests it's possible to set and get the device UUID.
	 */
	public function test_it_can_get_and_set_device_uuid() {
		$push_token = new PushToken();
		$push_token->set_device_uuid( 'ABCDEF-123ABC-DEF123-ABCDEF-123' );

		$this->assertEquals( 'ABCDEF-123ABC-DEF123-ABCDEF-123', $push_token->get_device_uuid() );
	}

	/**
	 * @testdox Tests it's possible to set and get the platform.
	 */
	public function test_it_can_get_and_set_platform() {
		$push_token = new PushToken();
		$push_token->set_platform( PushToken::PLATFORM_IOS );

		$this->assertEquals( PushToken::PLATFORM_IOS, $push_token->get_platform() );
	}

	/**
	 * @testdox Tests can_be_created returns true when all fields are set except ID.
	 */
	public function test_it_can_be_created_when_all_fields_are_set_except_id() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertTrue( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when ID is already set.
	 */
	public function test_it_cannot_be_created_when_id_is_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when user ID is missing.
	 */
	public function test_it_cannot_be_created_when_user_id_is_missing() {
		$push_token = new PushToken();
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when platform is missing.
	 */
	public function test_it_cannot_be_created_when_platform_is_missing() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when token is missing.
	 */
	public function test_it_cannot_be_created_when_token_is_missing() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when device UUID is missing.
	 */
	public function test_it_cannot_be_created_when_device_uuid_is_missing() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_updated returns true when all required fields are
	 * set.
	 */
	public function test_it_can_be_updated_when_all_fields_are_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertTrue( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when ID is not set.
	 */
	public function test_it_cannot_be_updated_when_id_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when user ID is not set.
	 */
	public function test_it_cannot_be_updated_when_user_id_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when platform is not set.
	 */
	public function test_it_cannot_be_updated_when_platform_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when device UUID is not set.
	 */
	public function test_it_cannot_be_updated_when_device_uuid_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_token( 'test_token' );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when token is not set.
	 */
	public function test_it_cannot_be_updated_when_token_is_not_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );
		$push_token->set_user_id( 1 );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'test-device-uuid' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_read returns true when ID is set.
	 */
	public function test_it_can_be_read_when_id_is_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );

		$this->assertTrue( $push_token->can_be_read() );
	}

	/**
	 * @testdox Tests can_be_read returns false when ID is not set.
	 */
	public function test_it_cannot_be_read_when_id_is_not_set() {
		$push_token = new PushToken();

		$this->assertFalse( $push_token->can_be_read() );
	}

	/**
	 * @testdox Tests can_be_deleted returns true when ID is set.
	 */
	public function test_it_can_be_deleted_when_id_is_set() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );

		$this->assertTrue( $push_token->can_be_deleted() );
	}

	/**
	 * @testdox Tests can_be_deleted returns false when ID is not set.
	 */
	public function test_it_cannot_be_deleted_when_id_is_not_set() {
		$push_token = new PushToken();

		$this->assertFalse( $push_token->can_be_deleted() );
	}

	/**
	 * @testdox Tests it's possible to set and get the origin.
	 */
	public function test_it_can_get_and_set_origin() {
		$push_token = new PushToken();
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$this->assertEquals( PushToken::ORIGIN_WOOCOMMERCE_IOS, $push_token->get_origin() );
	}

	/**
	 * @testdox Tests it's possible to set device UUID to null.
	 */
	public function test_it_can_set_device_uuid_to_null() {
		$push_token = new PushToken();
		$push_token->set_device_uuid( 'test-uuid' );
		$push_token->set_device_uuid( null );

		$this->assertNull( $push_token->get_device_uuid() );
	}

	/**
	 * @testdox Tests can_be_created returns true for browser tokens without
	 * device UUID.
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
	 * @testdox Tests can_be_updated returns true for browser tokens without
	 * device UUID.
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
	 * @testdox Tests can_be_created returns false when origin is missing.
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
	 * @testdox Tests can_be_updated returns false when origin is missing.
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
	 * @testdox Tests set_platform throws exception with invalid platform.
	 */
	public function test_it_throws_exception_when_setting_invalid_platform() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Platform for PushToken is invalid.' );

		$push_token->set_platform( 'invalid' );
	}

	/**
	 * @testdox Tests set_origin throws exception with invalid origin.
	 */
	public function test_it_throws_exception_when_setting_invalid_origin() {
		$push_token = new PushToken();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Origin for PushToken is invalid.' );

		$push_token->set_origin( 'com.invalid.app' );
	}

	/**
	 * @testdox Tests set_origin accepts valid origin values.
	 *
	 * @param string $origin The origin to test.
	 * @dataProvider valid_origins_provider
	 */
	public function test_it_accepts_valid_origin_values( string $origin ) {
		$push_token = new PushToken();
		$push_token->set_origin( $origin );

		$this->assertEquals( $origin, $push_token->get_origin() );
	}

	/**
	 * Data provider for valid origin values.
	 *
	 * @return array
	 */
	public function valid_origins_provider(): array {
		return array(
			'Android'     => array( PushToken::ORIGIN_WOOCOMMERCE_ANDROID ),
			'Android Dev' => array( PushToken::ORIGIN_WOOCOMMERCE_ANDROID_DEV ),
			'iOS'         => array( PushToken::ORIGIN_WOOCOMMERCE_IOS ),
			'iOS Dev'     => array( PushToken::ORIGIN_WOOCOMMERCE_IOS_DEV ),
		);
	}
}
