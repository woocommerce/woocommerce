<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Validators;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Validators\PushTokenValidator;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the PushTokenValidator class.
 */
class PushTokenValidatorTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should return a WP_Error when validating all keys where the last
	 * value is invalid.
	 */
	public function test_it_validates_all_keys(): void {
		$result = PushTokenValidator::validate(
			array(
				'id'            => 1,
				'user_id'       => 42,
				'origin'        => PushToken::ORIGINS[0],
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'valid-uuid-123',
				'device_locale' => 'en_US',
				'token'         => null,
				'metadata'      => array(),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true when validating all keys with valid data.
	 */
	public function test_it_validates_all_keys_with_valid_data(): void {
		$result = PushTokenValidator::validate(
			array(
				'id'            => 1,
				'user_id'       => 42,
				'origin'        => PushToken::ORIGINS[0],
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'valid-uuid-123',
				'device_locale' => 'en_US',
				'token'         => str_repeat( 'a', 64 ),
				'metadata'      => array(),
			)
		);

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Should return WP_Error when validating an unknown key.
	 */
	public function test_validate_rejects_unknown_key(): void {
		$result = PushTokenValidator::validate(
			array( 'unknown_field' => 'value' ),
			array( 'unknown_field' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'unknown_field', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true when validating a subset of keys.
	 */
	public function test_validate_accepts_subset_of_keys(): void {
		$result = PushTokenValidator::validate(
			array(
				'id'      => 1,
				'user_id' => 'hello',
			),
			array( 'id' )
		);

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Should return true for a valid positive ID.
	 */
	public function test_validate_id_accepts_positive_integer(): void {
		$this->assertTrue( PushTokenValidator::validate( array( 'id' => 1 ), array( 'id' ) ) );
	}

	/**
	 * @testdox Should return WP_Error when ID is missing.
	 */
	public function test_validate_id_rejects_missing_id(): void {
		$result = PushTokenValidator::validate( array(), array( 'id' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'ID is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when ID is not numeric.
	 */
	public function test_validate_id_rejects_non_numeric(): void {
		$result = PushTokenValidator::validate( array( 'id' => 'abc' ), array( 'id' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'ID must be numeric.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when ID is zero.
	 */
	public function test_validate_id_rejects_zero(): void {
		$result = PushTokenValidator::validate( array( 'id' => 0 ), array( 'id' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'ID must be a positive integer.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when ID is negative.
	 */
	public function test_validate_id_rejects_negative(): void {
		$result = PushTokenValidator::validate( array( 'id' => -5 ), array( 'id' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'ID must be a positive integer.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for a valid positive user ID.
	 */
	public function test_validate_user_id_accepts_positive_integer(): void {
		$this->assertTrue( PushTokenValidator::validate( array( 'user_id' => 42 ), array( 'user_id' ) ) );
	}

	/**
	 * @testdox Should return WP_Error when user ID is missing.
	 */
	public function test_validate_user_id_rejects_missing(): void {
		$result = PushTokenValidator::validate( array(), array( 'user_id' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'User ID is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when user ID is not numeric.
	 */
	public function test_validate_user_id_rejects_non_numeric(): void {
		$result = PushTokenValidator::validate( array( 'user_id' => 'xyz' ), array( 'user_id' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'User ID must be numeric.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when user ID is zero.
	 */
	public function test_validate_user_id_rejects_zero(): void {
		$result = PushTokenValidator::validate( array( 'user_id' => 0 ), array( 'user_id' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'User ID must be a positive integer.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for each valid origin.
	 * @param string $origin The origin to test.
	 * @dataProvider valid_origins_provider
	 */
	public function test_validate_origin_accepts_valid_origins( string $origin ): void {
		$this->assertTrue(
			PushTokenValidator::validate( array( 'origin' => $origin ), array( 'origin' ) )
		);
	}

	/**
	 * @testdox Should return WP_Error when origin is missing.
	 */
	public function test_validate_origin_rejects_missing(): void {
		$result = PushTokenValidator::validate( array(), array( 'origin' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Origin is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when origin is not a string.
	 */
	public function test_validate_origin_rejects_non_string(): void {
		$result = PushTokenValidator::validate( array( 'origin' => 123 ), array( 'origin' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Origin must be a string.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when origin is empty.
	 */
	public function test_validate_origin_rejects_empty_string(): void {
		$result = PushTokenValidator::validate( array( 'origin' => '' ), array( 'origin' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Origin cannot be empty.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when origin is whitespace only.
	 */
	public function test_validate_origin_rejects_whitespace_only(): void {
		$result = PushTokenValidator::validate( array( 'origin' => '   ' ), array( 'origin' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Origin cannot be empty.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when origin is not in the allowed list.
	 */
	public function test_validate_origin_rejects_invalid_value(): void {
		$result = PushTokenValidator::validate( array( 'origin' => 'com.invalid.app' ), array( 'origin' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Origin must be one of:', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for each valid platform.
	 * @param string $platform The platform to test.
	 * @dataProvider valid_platforms_provider
	 */
	public function test_validate_platform_accepts_valid_platforms( string $platform ): void {
		$this->assertTrue(
			PushTokenValidator::validate( array( 'platform' => $platform ), array( 'platform' ) )
		);
	}

	/**
	 * @testdox Should return WP_Error when platform is missing.
	 */
	public function test_validate_platform_rejects_missing(): void {
		$result = PushTokenValidator::validate( array(), array( 'platform' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Platform is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when platform is not a string.
	 */
	public function test_validate_platform_rejects_non_string(): void {
		$result = PushTokenValidator::validate( array( 'platform' => 42 ), array( 'platform' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Platform must be a string.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when platform is empty.
	 */
	public function test_validate_platform_rejects_empty(): void {
		$result = PushTokenValidator::validate( array( 'platform' => '' ), array( 'platform' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Platform cannot be empty.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when platform is not in the allowed list.
	 */
	public function test_validate_platform_rejects_invalid_value(): void {
		$result = PushTokenValidator::validate( array( 'platform' => 'windows' ), array( 'platform' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Platform must be one of:', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for a valid device UUID on Apple platform.
	 */
	public function test_validate_device_uuid_accepts_valid_uuid_for_apple(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array(
					'device_uuid' => 'ABC-123.def_456:789',
					'platform'    => PushToken::PLATFORM_APPLE,
				),
				array( 'device_uuid' )
			)
		);
	}

	/**
	 * @testdox Should return true for a valid device UUID on Android platform.
	 */
	public function test_validate_device_uuid_accepts_valid_uuid_for_android(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array(
					'device_uuid' => 'device-uuid-123',
					'platform'    => PushToken::PLATFORM_ANDROID,
				),
				array( 'device_uuid' )
			)
		);
	}

	/**
	 * @testdox Should return true when device UUID is missing for browser platform.
	 */
	public function test_validate_device_uuid_accepts_missing_uuid_for_browser(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array( 'platform' => PushToken::PLATFORM_BROWSER ),
				array( 'device_uuid' )
			)
		);
	}

	/**
	 * @testdox Should return true when both device UUID and platform are missing.
	 */
	public function test_validate_device_uuid_accepts_when_both_missing(): void {
		$this->assertTrue(
			PushTokenValidator::validate( array(), array( 'device_uuid' ) )
		);
	}

	/**
	 * @testdox Should return WP_Error when device UUID is missing for Apple platform.
	 */
	public function test_validate_device_uuid_rejects_missing_uuid_for_apple(): void {
		$result = PushTokenValidator::validate(
			array( 'platform' => PushToken::PLATFORM_APPLE ),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device UUID is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device UUID is missing for Android platform.
	 */
	public function test_validate_device_uuid_rejects_missing_uuid_for_android(): void {
		$result = PushTokenValidator::validate(
			array( 'platform' => PushToken::PLATFORM_ANDROID ),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device UUID is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device UUID is empty for Apple platform.
	 */
	public function test_validate_device_uuid_rejects_empty_for_apple(): void {
		$result = PushTokenValidator::validate(
			array(
				'device_uuid' => '',
				'platform'    => PushToken::PLATFORM_APPLE,
			),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device UUID cannot be empty.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device UUID is whitespace only for Apple platform.
	 */
	public function test_validate_device_uuid_rejects_whitespace_only_for_apple(): void {
		$result = PushTokenValidator::validate(
			array(
				'device_uuid' => '   ',
				'platform'    => PushToken::PLATFORM_APPLE,
			),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device UUID cannot be empty.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device UUID contains invalid characters.
	 */
	public function test_validate_device_uuid_rejects_invalid_characters(): void {
		$result = PushTokenValidator::validate(
			array(
				'device_uuid' => 'invalid uuid with spaces',
				'platform'    => PushToken::PLATFORM_APPLE,
			),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device UUID is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device UUID exceeds maximum length.
	 */
	public function test_validate_device_uuid_rejects_exceeding_max_length(): void {
		$result = PushTokenValidator::validate(
			array(
				'device_uuid' => str_repeat( 'a', PushTokenValidator::DEVICE_UUID_MAXIMUM_LENGTH + 1 ),
				'platform'    => PushToken::PLATFORM_APPLE,
			),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Device UUID exceeds maximum length', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device UUID exceeds max length even for browser platform.
	 */
	public function test_validate_device_uuid_rejects_exceeding_max_length_for_browser(): void {
		$result = PushTokenValidator::validate(
			array(
				'device_uuid' => str_repeat( 'a', PushTokenValidator::DEVICE_UUID_MAXIMUM_LENGTH + 1 ),
				'platform'    => PushToken::PLATFORM_BROWSER,
			),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Device UUID exceeds maximum length', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for device UUID at exactly maximum length.
	 */
	public function test_validate_device_uuid_accepts_at_max_length(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array(
					'device_uuid' => str_repeat( 'a', PushTokenValidator::DEVICE_UUID_MAXIMUM_LENGTH ),
					'platform'    => PushToken::PLATFORM_APPLE,
				),
				array( 'device_uuid' )
			)
		);
	}

	/**
	 * @testdox Should return WP_Error when device UUID is not a string for Apple platform.
	 */
	public function test_validate_device_uuid_rejects_non_string_for_apple(): void {
		$result = PushTokenValidator::validate(
			array(
				'device_uuid' => 12345,
				'platform'    => PushToken::PLATFORM_APPLE,
			),
			array( 'device_uuid' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device UUID must be a string.', $result->get_error_message() );
	}

	/**
	 * @testdox Should skip format validation for browser platform with a provided device UUID.
	 */
	public function test_validate_device_uuid_skips_format_check_for_browser(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array(
					'device_uuid' => 'any-value',
					'platform'    => PushToken::PLATFORM_BROWSER,
				),
				array( 'device_uuid' )
			)
		);
	}

	/**
	 * @testdox Should return true for valid locale formats.
	 * @dataProvider valid_locales_provider
	 * @param string $locale The locale to test.
	 */
	public function test_validate_accepts_valid_device_locale_formats( string $locale ): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array( 'device_locale' => $locale ),
				array( 'device_locale' )
			)
		);
	}

	/**
	 * @testdox Should return WP_Error when device locale is missing.
	 */
	public function test_validate_rejects_missing_device_locale(): void {
		$result = PushTokenValidator::validate(
			array(),
			array( 'device_locale' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device locale is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device locale is not a string.
	 */
	public function test_validate_rejects_non_string_for_device_locale(): void {
		$result = PushTokenValidator::validate(
			array( 'device_locale' => 123 ),
			array( 'device_locale' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device locale must be a string.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when device locale is empty.
	 */
	public function test_validate_rejects_empty_device_locale(): void {
		$result = PushTokenValidator::validate(
			array( 'device_locale' => '' ),
			array( 'device_locale' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device locale cannot be empty.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for invalid locale formats.
	 * @dataProvider invalid_locales_provider
	 * @param string $locale The locale to test.
	 */
	public function test_validate_rejects_invalid_formats_for_device_locale( string $locale ): void {
		$result = PushTokenValidator::validate(
			array( 'device_locale' => $locale ),
			array( 'device_locale' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Device locale is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for a valid Apple token.
	 */
	public function test_validate_token_accepts_valid_apple_token(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array(
					'token'    => str_repeat( 'a', 64 ),
					'platform' => PushToken::PLATFORM_APPLE,
				),
				array( 'token' )
			)
		);
	}

	/**
	 * @testdox Should return true for a valid Android token.
	 */
	public function test_validate_token_accepts_valid_android_token(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array(
					'token'    => 'dGVzdF90b2tlbl92YWx1ZQ==:APA91b',
					'platform' => PushToken::PLATFORM_ANDROID,
				),
				array( 'token' )
			)
		);
	}

	/**
	 * @testdox Should return true for a valid browser token.
	 */
	public function test_validate_token_accepts_valid_browser_token(): void {
		$token = wp_json_encode(
			array(
				'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
				'keys'     => array(
					'auth'   => 'test-auth-key',
					'p256dh' => 'test-p256dh-key',
				),
			)
		);

		$this->assertTrue(
			PushTokenValidator::validate(
				array(
					'token'    => $token,
					'platform' => PushToken::PLATFORM_BROWSER,
				),
				array( 'token' )
			)
		);
	}

	/**
	 * @testdox Should return WP_Error when token is missing.
	 */
	public function test_validate_token_rejects_missing(): void {
		$result = PushTokenValidator::validate( array(), array( 'token' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when token is not a string.
	 */
	public function test_validate_token_rejects_non_string(): void {
		$result = PushTokenValidator::validate( array( 'token' => 123 ), array( 'token' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token must be a string.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when token is empty.
	 */
	public function test_validate_token_rejects_empty(): void {
		$result = PushTokenValidator::validate( array( 'token' => '' ), array( 'token' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token cannot be empty.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when token exceeds maximum length.
	 */
	public function test_validate_token_rejects_exceeding_max_length(): void {
		$result = PushTokenValidator::validate(
			array( 'token' => str_repeat( 'A', PushTokenValidator::TOKEN_MAXIMUM_LENGTH + 1 ) ),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Token exceeds maximum length', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for token at exactly maximum length without platform.
	 */
	public function test_validate_token_accepts_at_max_length(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array( 'token' => str_repeat( 'A', PushTokenValidator::TOKEN_MAXIMUM_LENGTH ) ),
				array( 'token' )
			)
		);
	}

	/**
	 * @testdox Should skip format validation when platform is not provided.
	 */
	public function test_validate_token_skips_format_check_without_platform(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array( 'token' => 'any-valid-string' ),
				array( 'token' )
			)
		);
	}

	/**
	 * @testdox Should return WP_Error for Apple token with non-hex characters.
	 */
	public function test_validate_token_rejects_apple_token_with_non_hex(): void {
		$result = PushTokenValidator::validate(
			array(
				'token'    => str_repeat( 'g', 64 ),
				'platform' => PushToken::PLATFORM_APPLE,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for Apple token with wrong length.
	 */
	public function test_validate_token_rejects_apple_token_with_wrong_length(): void {
		$result = PushTokenValidator::validate(
			array(
				'token'    => str_repeat( 'a', 32 ),
				'platform' => PushToken::PLATFORM_APPLE,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for Android token with spaces.
	 */
	public function test_validate_token_rejects_android_token_with_spaces(): void {
		$result = PushTokenValidator::validate(
			array(
				'token'    => 'invalid token with spaces',
				'platform' => PushToken::PLATFORM_ANDROID,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for browser token with invalid JSON.
	 */
	public function test_validate_token_rejects_browser_token_with_invalid_json(): void {
		$result = PushTokenValidator::validate(
			array(
				'token'    => 'not-valid-json',
				'platform' => PushToken::PLATFORM_BROWSER,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for browser token that is valid JSON null.
	 */
	public function test_validate_token_rejects_browser_token_with_json_null(): void {
		$result = PushTokenValidator::validate(
			array(
				'token'    => 'null',
				'platform' => PushToken::PLATFORM_BROWSER,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for browser token missing required keys.
	 */
	public function test_validate_token_rejects_browser_token_missing_keys(): void {
		$token = wp_json_encode(
			array( 'endpoint' => 'https://example.com/push' )
		);

		$result = PushTokenValidator::validate(
			array(
				'token'    => $token,
				'platform' => PushToken::PLATFORM_BROWSER,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for browser token with non-HTTPS endpoint.
	 */
	public function test_validate_token_rejects_browser_token_with_http_endpoint(): void {
		$token = wp_json_encode(
			array(
				'endpoint' => 'http://example.com/push',
				'keys'     => array(
					'auth'   => 'test-auth',
					'p256dh' => 'test-p256dh',
				),
			)
		);

		$result = PushTokenValidator::validate(
			array(
				'token'    => $token,
				'platform' => PushToken::PLATFORM_BROWSER,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error for browser token with missing endpoint.
	 */
	public function test_validate_token_rejects_browser_token_missing_endpoint(): void {
		$token = wp_json_encode(
			array(
				'keys' => array(
					'auth'   => 'test-auth',
					'p256dh' => 'test-p256dh',
				),
			)
		);

		$result = PushTokenValidator::validate(
			array(
				'token'    => $token,
				'platform' => PushToken::PLATFORM_BROWSER,
			),
			array( 'token' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Token is an invalid format.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return true for a valid metadata array with values.
	 */
	public function test_validate_accepts_valid_array_for_metadata(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array( 'metadata' => array( 'app_version' => '1.0.0' ) ),
				array( 'metadata' )
			)
		);
	}

	/**
	 * @testdox Should return true for an empty metadata array.
	 */
	public function test_validate_accepts_empty_array_for_metadata(): void {
		$this->assertTrue(
			PushTokenValidator::validate(
				array( 'metadata' => array() ),
				array( 'metadata' )
			)
		);
	}

	/**
	 * @testdox Should return WP_Error when metadata is missing.
	 */
	public function test_validate_rejects_missing_metadata(): void {
		$result = PushTokenValidator::validate( array(), array( 'metadata' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Metadata is required.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when metadata is not an array.
	 */
	public function test_validate_rejects_non_array_for_metadata(): void {
		$result = PushTokenValidator::validate(
			array( 'metadata' => 'not an array' ),
			array( 'metadata' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Metadata must be an array.', $result->get_error_message() );
	}

	/**
	 * @testdox Should return WP_Error when metadata contains non-scalar values.
	 */
	public function test_validate_rejects_non_scalar_metadata_items(): void {
		$result = PushTokenValidator::validate(
			array( 'metadata' => array( 'nested' => array( 'a' => 'b' ) ) ),
			array( 'metadata' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Metadata items must be scalar values.', $result->get_error_message() );
	}

	/**
	 * @testdox Should use the standard error code for all validation errors.
	 * @dataProvider validatable_fields_provider
	 * @param string $field The field to validate.
	 */
	public function test_all_errors_use_standard_error_code( string $field ): void {
		/**
		 * If field isn't platform, pass the platform in so we can check token
		 * and device_uuid.
		 */
		$data  = 'platform' === $field ? array() : array( 'platform' => PushToken::PLATFORM_APPLE );
		$error = PushTokenValidator::validate( $data, array( $field ) );

		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertSame( PushTokenValidator::ERROR_CODE, $error->get_error_code() );
	}

	/**
	 * Data provider for valid locale formats.
	 *
	 * @return array
	 */
	public function valid_locales_provider(): array {
		return array(
			'English US'   => array( 'en_US' ),
			'French'       => array( 'fr_FR' ),
			'Chinese'      => array( 'zh_CN' ),
			'Portuguese'   => array( 'pt_BR' ),
			'Three-letter' => array( 'ast_ES' ),
		);
	}

	/**
	 * Data provider for invalid locale formats.
	 *
	 * @return array
	 */
	public function invalid_locales_provider(): array {
		return array(
			'no underscore'    => array( 'enUS' ),
			'lowercase region' => array( 'en_gb' ),
			'uppercase lang'   => array( 'EN_US' ),
			'just language'    => array( 'en' ),
			'with hyphen'      => array( 'en-US' ),
			'too long lang'    => array( 'engl_US' ),
		);
	}

	/**
	 * Data provider for valid platforms.
	 *
	 * @return array
	 */
	public function valid_platforms_provider(): array {
		return array_map( fn ( $value ) => array( $value ), PushToken::PLATFORMS );
	}

	/**
	 * Data provider for valid origins.
	 *
	 * @return array
	 */
	public function valid_origins_provider(): array {
		return array_map( fn ( $value ) => array( $value ), PushToken::ORIGINS );
	}

	/**
	 * Data provider for validatable fields.
	 *
	 * @return array
	 */
	public function validatable_fields_provider(): array {
		return array_map( fn ( $value ) => array( $value ), PushTokenValidator::VALIDATABLE_FIELDS );
	}
}
