<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Entities;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenInvalidDataException;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Validators\PushTokenValidator;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;

/**
 * PushToken test.
 *
 * @covers PushToken
 */
class PushTokenTest extends WC_Unit_Test_Case {
	use LoggerSpyTrait;

	/**
	 * @testdox Tests it's possible to set and get the ID.
	 */
	public function test_it_can_get_and_set_id() {
		$push_token = new PushToken();
		$push_token->set_id( 1 );

		$this->assertEquals( 1, $push_token->get_id() );
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
	 * @testdox Tests it's possible to set and get the token.
	 */
	public function test_it_can_get_and_set_token() {
		$push_token = new PushToken();
		$push_token->set_token( 'ABCDEF123ABCDEF123ABCDEF123' );

		$this->assertEquals( 'ABCDEF123ABCDEF123ABCDEF123', $push_token->get_token() );
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
		$push_token->set_platform( PushToken::PLATFORM_APPLE );

		$this->assertEquals( PushToken::PLATFORM_APPLE, $push_token->get_platform() );
	}

	/**
	 * @testdox Tests can_be_created returns true when all fields are set except ID.
	 */
	public function test_it_can_be_created_when_all_fields_are_set_except_id() {
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertTrue( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when ID is already set.
	 */
	public function test_it_cannot_be_created_when_id_is_set() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when user ID is missing.
	 */
	public function test_it_cannot_be_created_when_user_id_is_missing() {
		$push_token = new PushToken(
			array(
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when platform is missing.
	 */
	public function test_it_cannot_be_created_when_platform_is_missing() {
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when token is missing.
	 */
	public function test_it_cannot_be_created_when_token_is_missing() {
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_created returns false when device UUID is missing.
	 */
	public function test_it_cannot_be_created_when_device_uuid_is_missing() {
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'test_token',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_updated returns true when all required fields are
	 * set.
	 */
	public function test_it_can_be_updated_when_all_fields_are_set() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertTrue( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when ID is not set.
	 */
	public function test_it_cannot_be_updated_when_id_is_not_set() {
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when user ID is not set.
	 */
	public function test_it_cannot_be_updated_when_user_id_is_not_set() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when platform is not set.
	 */
	public function test_it_cannot_be_updated_when_platform_is_not_set() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when device UUID is not set.
	 */
	public function test_it_cannot_be_updated_when_device_uuid_is_not_set() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'token'         => 'test_token',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when token is not set.
	 */
	public function test_it_cannot_be_updated_when_token_is_not_set() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

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
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'test_token',
				'platform'      => PushToken::PLATFORM_BROWSER,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertTrue( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_updated returns true for browser tokens without
	 * device UUID.
	 */
	public function test_it_can_be_updated_for_browser_without_device_uuid() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'token'         => 'test_token',
				'platform'      => PushToken::PLATFORM_BROWSER,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertTrue( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_created returns false when origin is missing.
	 */
	public function test_it_cannot_be_created_when_origin_is_missing() {
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when origin is missing.
	 */
	public function test_it_cannot_be_updated_when_origin_is_missing() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_created returns false when device_locale is missing.
	 */
	public function test_it_cannot_be_created_when_device_locale_is_missing() {
		$push_token = new PushToken(
			array(
				'user_id'     => 1,
				'token'       => 'test_token',
				'device_uuid' => 'test-device-uuid',
				'platform'    => PushToken::PLATFORM_APPLE,
				'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'metadata'    => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_updated returns false when device_locale is missing.
	 */
	public function test_it_cannot_be_updated_when_device_locale_is_missing() {
		$push_token = new PushToken(
			array(
				'id'          => 1,
				'user_id'     => 1,
				'token'       => 'test_token',
				'device_uuid' => 'test-device-uuid',
				'platform'    => PushToken::PLATFORM_APPLE,
				'origin'      => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'metadata'    => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertFalse( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests can_be_created returns true when metadata is missing.
	 */
	public function test_it_can_be_created_when_metadata_is_missing() {
		$push_token = new PushToken(
			array(
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
			)
		);

		$this->assertTrue( $push_token->can_be_created() );
	}

	/**
	 * @testdox Tests can_be_updated returns true when metadata is missing.
	 */
	public function test_it_can_be_updated_when_metadata_is_missing() {
		$push_token = new PushToken(
			array(
				'id'            => 1,
				'user_id'       => 1,
				'token'         => 'test_token',
				'device_uuid'   => 'test-device-uuid',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
			)
		);

		$this->assertTrue( $push_token->can_be_updated() );
	}

	/**
	 * @testdox Tests set_platform throws exception with invalid platform.
	 */
	public function test_it_throws_exception_when_setting_invalid_platform() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Platform must be one of: apple, android, browser.' );

		$push_token->set_platform( 'invalid' );
	}

	/**
	 * @testdox Tests set_origin throws exception with invalid origin.
	 */
	public function test_it_throws_exception_when_setting_invalid_origin() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );

		$this->expectExceptionMessage(
			'Origin must be one of: browser, com.woocommerce.android, com.woocommerce.android:dev, com.automattic.woocommerce, com.automattic.woocommerce:dev'
		);

		$push_token->set_origin( 'com.invalid.app' );
	}

	/**
	 * @testdox Tests set_origin accepts valid origin values.
	 */
	public function test_it_accepts_valid_origin_values() {
		$push_token = new PushToken();

		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_ANDROID );
		$this->assertEquals( PushToken::ORIGIN_WOOCOMMERCE_ANDROID, $push_token->get_origin() );

		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_ANDROID_DEV );
		$this->assertEquals( PushToken::ORIGIN_WOOCOMMERCE_ANDROID_DEV, $push_token->get_origin() );

		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );
		$this->assertEquals( PushToken::ORIGIN_WOOCOMMERCE_IOS, $push_token->get_origin() );

		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS_DEV );
		$this->assertEquals( PushToken::ORIGIN_WOOCOMMERCE_IOS_DEV, $push_token->get_origin() );
	}

	/**
	 * @testdox Tests set_user_id throws exception with zero.
	 */
	public function test_it_throws_exception_when_setting_user_id_to_zero() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'User ID must be a positive integer.' );

		$push_token->set_user_id( 0 );
	}

	/**
	 * @testdox Tests set_user_id throws exception with negative number.
	 */
	public function test_it_throws_exception_when_setting_negative_user_id() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'User ID must be a positive integer.' );

		$push_token->set_user_id( -1 );
	}

	/**
	 * @testdox Tests set_device_locale throws exception with empty string.
	 */
	public function test_it_throws_exception_when_setting_empty_device_locale() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Device locale cannot be empty.' );

		$push_token->set_device_locale( '' );
	}

	/**
	 * @testdox Tests set_device_locale throws exception with invalid format.
	 */
	public function test_it_throws_exception_when_setting_invalid_device_locale_format() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Device locale is an invalid format.' );

		$push_token->set_device_locale( 'invalid' );
	}

	/**
	 * @testdox Tests set_device_locale throws exception with lowercase region.
	 */
	public function test_it_throws_exception_when_setting_device_locale_with_lowercase_region() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Device locale is an invalid format.' );

		$push_token->set_device_locale( 'en_gb' );
	}

	/**
	 * @testdox Tests set_metadata throws exception with non-scalar values.
	 */
	public function test_it_throws_exception_when_setting_non_scalar_metadata() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Metadata items must be scalar values.' );

		$push_token->set_metadata( array( 'nested' => array( 'a' => 'b' ) ) );
	}

	/**
	 * @testdox Tests set_token throws exception with empty string.
	 */
	public function test_it_throws_exception_when_setting_empty_token() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Token cannot be empty.' );

		$push_token->set_token( '' );
	}

	/**
	 * @testdox Tests set_token throws exception with whitespace-only string.
	 */
	public function test_it_throws_exception_when_setting_whitespace_only_token() {
		$push_token = new PushToken();

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Token cannot be empty.' );

		$push_token->set_token( '   ' );
	}

	/**
	 * @testdox Tests set_token throws exception when exceeding maximum length.
	 */
	public function test_it_throws_exception_when_token_exceeds_max_length() {
		$push_token = new PushToken();
		$long_token = str_repeat( 'A', PushTokenValidator::TOKEN_MAXIMUM_LENGTH + 1 );

		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Token exceeds maximum length of 4096.' );

		$push_token->set_token( $long_token );
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
	 * @testdox Tests device UUID normalization with whitespace.
	 */
	public function test_it_normalizes_whitespace_device_uuid_to_null() {
		$push_token = new PushToken();
		$push_token->set_device_uuid( '   ' );

		$this->assertNull( $push_token->get_device_uuid() );
	}

	/**
	 * @testdox Tests device UUID trims whitespace.
	 */
	public function test_it_trims_whitespace_from_device_uuid() {
		$push_token = new PushToken();
		$push_token->set_device_uuid( '  test_uuid  ' );

		$this->assertEquals( 'test_uuid', $push_token->get_device_uuid() );
	}

	/**
	 * @testdox Tests set_token accepts token at maximum length.
	 */
	public function test_it_accepts_token_at_max_length() {
		$push_token       = new PushToken();
		$max_length_token = str_repeat( 'A', PushTokenValidator::TOKEN_MAXIMUM_LENGTH );

		$push_token->set_token( $max_length_token );

		$this->assertEquals( $max_length_token, $push_token->get_token() );
	}

	/**
	 * @testdox Tests constructor creates a PushToken with all specified properties.
	 */
	public function test_constructor_creates_token_with_all_properties() {
		$push_token = new PushToken(
			array(
				'id'            => 123,
				'user_id'       => 456,
				'token'         => 'test_token_value',
				'device_uuid'   => 'device-uuid-123',
				'platform'      => PushToken::PLATFORM_APPLE,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
				'metadata'      => array( 'app_version' => '1.0' ),
			)
		);

		$this->assertSame( 123, $push_token->get_id() );
		$this->assertSame( 456, $push_token->get_user_id() );
		$this->assertSame( 'test_token_value', $push_token->get_token() );
		$this->assertSame( 'device-uuid-123', $push_token->get_device_uuid() );
		$this->assertSame( PushToken::PLATFORM_APPLE, $push_token->get_platform() );
		$this->assertSame( PushToken::ORIGIN_WOOCOMMERCE_IOS, $push_token->get_origin() );
		$this->assertSame( 'en_US', $push_token->get_device_locale() );
		$this->assertSame( array( 'app_version' => '1.0' ), $push_token->get_metadata() );
	}

	/**
	 * @testdox Tests constructor creates a PushToken with only some properties.
	 */
	public function test_constructor_creates_token_with_partial_properties() {
		$push_token = new PushToken(
			array(
				'user_id'  => 789,
				'token'    => 'partial_token',
				'platform' => PushToken::PLATFORM_ANDROID,
			)
		);

		$this->assertNull( $push_token->get_id() );
		$this->assertSame( 789, $push_token->get_user_id() );
		$this->assertSame( 'partial_token', $push_token->get_token() );
		$this->assertNull( $push_token->get_device_uuid() );
		$this->assertSame( PushToken::PLATFORM_ANDROID, $push_token->get_platform() );
		$this->assertNull( $push_token->get_origin() );
	}

	/**
	 * @testdox Tests constructor throws exception for invalid values.
	 */
	public function test_constructor_throws_exception_for_invalid_platform() {
		$this->expectException( PushTokenInvalidDataException::class );
		$this->expectExceptionMessage( 'Platform must be one of: apple, android, browser.' );

		new PushToken(
			array(
				'platform' => 'invalid_platform',
			)
		);
	}

	/**
	 * @testdox Tests GMT datetimes are held as Y-m-d H:i:s and converted for the response.
	 */
	public function test_it_converts_gmt_datetimes_for_the_response() {
		$push_token = new PushToken(
			array(
				'created_at_gmt'        => '2026-08-01 09:30:00',
				'last_confirmed_at_gmt' => '2026-08-11 14:45:12',
			)
		);

		$this->assertSame( '2026-08-01 09:30:00', $push_token->get_created_at_gmt() );
		$this->assertSame( '2026-08-11 14:45:12', $push_token->get_last_confirmed_at_gmt() );

		$rest_format = $push_token->to_rest_format();

		$this->assertSame( '2026-08-01T09:30:00', $rest_format['created_at_gmt'] );
		$this->assertSame( '2026-08-11T14:45:12', $rest_format['last_confirmed_at_gmt'] );
	}

	/**
	 * @testdox Tests a token can be rebuilt from the timestamps in its own REST output.
	 *
	 * Anything that stores a serialised token and reconstructs it later, such
	 * as a queued payload or a cached fixture, would otherwise get null for
	 * both timestamps with nothing to indicate they were dropped.
	 */
	public function test_it_can_be_rebuilt_from_its_own_rest_output() {
		$original = ( new PushToken(
			array(
				'created_at_gmt'        => '2026-08-01 09:30:00',
				'last_confirmed_at_gmt' => '2026-08-11 14:45:12',
			)
		) )->to_rest_format();

		$rebuilt = new PushToken(
			array(
				'created_at_gmt'        => $original['created_at_gmt'],
				'last_confirmed_at_gmt' => $original['last_confirmed_at_gmt'],
			)
		);

		$this->assertSame( $original['created_at_gmt'], $rebuilt->to_rest_format()['created_at_gmt'] );
		$this->assertSame( $original['last_confirmed_at_gmt'], $rebuilt->to_rest_format()['last_confirmed_at_gmt'] );
	}

	/**
	 * @testdox Tests timestamps use the same shape as every other Woo REST _gmt field.
	 *
	 * `wc_rest_prepare_date_response()` emits `Y-m-d\TH:i:s`, which is what
	 * `date_created_gmt` and `date_modified_gmt` carry on every other endpoint
	 * the consumer parses. A different shape under the same `_gmt` suffix would
	 * be read with the wrong parser.
	 */
	public function test_it_emits_timestamps_in_the_woo_rest_shape() {
		$push_token = new PushToken( array( 'created_at_gmt' => '2026-08-01 09:30:00' ) );

		$this->assertSame(
			wc_rest_prepare_date_response( '2026-08-01 09:30:00' ),
			$push_token->to_rest_format()['created_at_gmt']
		);
	}

	/**
	 * @testdox Tests an unparseable stored value normalizes to null rather than fataling.
	 *
	 * The parse returns false on input it cannot match, and returning that from
	 * a `?string` method under strict types raises a TypeError. TypeError
	 * extends Error, so no catch block on the send path would stop it becoming
	 * a fatal.
	 */
	public function test_it_normalizes_an_unparseable_timestamp_to_null() {
		$push_token = new PushToken( array( 'created_at_gmt' => 'not a date at all' ) );

		$this->assertNull( $push_token->get_created_at_gmt() );
	}

	/**
	 * @testdox Tests timestamps are read as UTC regardless of the store's timezone.
	 *
	 * Stored values are GMT by construction. Parsing them in the site timezone
	 * would shift every value by the store's offset, which on a UK store would
	 * appear only during BST.
	 */
	public function test_it_parses_timestamps_as_utc_not_the_site_timezone() {
		$original = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'Europe/London' );

		$push_token = new PushToken( array( 'created_at_gmt' => '2026-08-01 09:30:00' ) );

		update_option( 'timezone_string', $original );

		$this->assertSame( '2026-08-01T09:30:00', $push_token->to_rest_format()['created_at_gmt'] );
	}

	/**
	 * @testdox Tests an impossible calendar date is rejected rather than rolled forward.
	 *
	 * PHP's date parsers accept 30 February and silently return 2 March. A
	 * rolled-forward date is worse than no date, because it looks plausible and
	 * nothing downstream can tell it was invented.
	 */
	public function test_it_rejects_an_impossible_calendar_date() {
		$push_token = new PushToken( array( 'created_at_gmt' => '2026-02-30 09:30:00' ) );

		$this->assertNull( $push_token->get_created_at_gmt() );
	}

	/**
	 * @testdox Tests an unparseable timestamp is reported to the log.
	 *
	 * A null timestamp on its own is invisible. If the parse starts failing
	 * across every token, the diagnostic tooling loses both fields and nothing
	 * records why.
	 */
	public function test_it_logs_an_unparseable_timestamp() {
		new PushToken(
			array(
				'id'             => 99,
				'created_at_gmt' => 'not a date at all',
			)
		);

		$this->assertLogged(
			'warning',
			'Unparseable push token timestamp.',
			array(
				'source'   => PushNotifications::FEATURE_NAME,
				'token_id' => 99,
				'value'    => 'not a date at all',
			)
		);
	}

	/**
	 * @testdox Tests an unknown timestamp is not reported to the log.
	 *
	 * The empty string and the MySQL zero date both mean the date is unknown,
	 * which is expected rather than a fault. Reporting them would bury the
	 * entries that matter.
	 *
	 * @dataProvider unknown_timestamp_provider
	 * @param string $stored The stored value standing for an unknown date.
	 */
	public function test_it_does_not_log_an_unknown_timestamp( string $stored ) {
		new PushToken( array( 'created_at_gmt' => $stored ) );

		$this->assertEmpty( $this->captured_logs );
	}

	/**
	 * Provides the stored values that mean the date is unknown.
	 *
	 * @return array<string, array<string>>
	 */
	public function unknown_timestamp_provider(): array {
		return array(
			'empty string' => array( '' ),
			'zero date'    => array( '0000-00-00 00:00:00' ),
		);
	}

	/**
	 * @testdox Tests a timestamp carrying its own offset is rejected.
	 *
	 * These fields are documented as GMT `Y-m-d H:i:s`. An offset in the input
	 * overrides the timezone the parser is given, so a value written in local
	 * time would be read as local time and reported as a different instant
	 * rather than refused. The `T` separator the response emits carries no
	 * offset and is accepted, covered by
	 * test_it_can_be_rebuilt_from_its_own_rest_output.
	 */
	public function test_it_rejects_a_timestamp_carrying_its_own_offset() {
		foreach ( array( '2026-08-01T09:30:00+05:00', '2026-08-01 09:30:00 UTC', '2026-08-01T09:30:00Z', '2026-08-01T09:30:00-00:30' ) as $stored ) {
			$push_token = new PushToken( array( 'created_at_gmt' => $stored ) );

			$this->assertNull(
				$push_token->get_created_at_gmt(),
				sprintf( 'Expected null for stored value "%s".', $stored )
			);
		}
	}

	/**
	 * @testdox Tests timestamps default to null so an unknown date is distinguishable from a real one.
	 *
	 * The MySQL zero date and an empty string both mean "we don't know when
	 * this happened", and must not be surfaced as if they were real dates.
	 */
	public function test_it_normalizes_unknown_timestamps_to_null() {
		$this->assertNull( ( new PushToken() )->get_created_at_gmt() );
		$this->assertNull( ( new PushToken() )->get_last_confirmed_at_gmt() );

		$push_token = new PushToken(
			array(
				'created_at_gmt'        => '0000-00-00 00:00:00',
				'last_confirmed_at_gmt' => '',
			)
		);

		$this->assertNull( $push_token->get_created_at_gmt() );
		$this->assertNull( $push_token->get_last_confirmed_at_gmt() );

		$push_token->set_created_at_gmt( null );
		$this->assertNull( $push_token->get_created_at_gmt() );
	}

	/**
	 * @testdox Tests the REST format adds diagnostic fields without altering the WPCOM send payload.
	 *
	 * `to_wpcom_format()` is the per-token payload the dispatcher POSTs to WPCOM
	 * on every notification, so it must stay unchanged by these additions.
	 */
	public function test_rest_format_adds_fields_without_changing_wpcom_format() {
		$push_token = new PushToken(
			array(
				'id'                    => 77,
				'user_id'               => 42,
				'token'                 => 'rest_format_token',
				'platform'              => PushToken::PLATFORM_APPLE,
				'device_uuid'           => 'rest-format-uuid',
				'origin'                => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale'         => 'en_US',
				'metadata'              => array( 'app_version' => '21.1' ),
				'created_at_gmt'        => '2026-08-01 09:30:00',
				'last_confirmed_at_gmt' => '2026-08-11 14:45:12',
			)
		);

		$wpcom_format = $push_token->to_wpcom_format();
		$rest_format  = $push_token->to_rest_format();

		$this->assertSame(
			array( 'user_id', 'token', 'origin', 'device_locale' ),
			array_keys( $wpcom_format )
		);

		$this->assertSame( 77, $rest_format['id'] );
		$this->assertSame( 'rest-format-uuid', $rest_format['device_uuid'] );
		$this->assertSame( PushToken::PLATFORM_APPLE, $rest_format['platform'] );
		$this->assertSame( array( 'app_version' => '21.1' ), $rest_format['metadata'] );
		$this->assertSame( '2026-08-01T09:30:00', $rest_format['created_at_gmt'] );
		$this->assertSame( '2026-08-11T14:45:12', $rest_format['last_confirmed_at_gmt'] );
		$this->assertSame( $wpcom_format, array_intersect_key( $rest_format, $wpcom_format ) );
	}

	/**
	 * @testdox Tests the REST format reports metadata as an array when a token has none.
	 *
	 * Browser tokens and tokens registered before metadata existed have no value
	 * stored, and the tooling should not have to handle both null and an array.
	 */
	public function test_rest_format_reports_absent_metadata_as_an_empty_array() {
		$rest_format = ( new PushToken() )->to_rest_format();

		$this->assertSame( array(), $rest_format['metadata'] );
		$this->assertNull( $rest_format['id'] );
		$this->assertNull( $rest_format['device_uuid'] );
		$this->assertNull( $rest_format['platform'] );
	}
}
