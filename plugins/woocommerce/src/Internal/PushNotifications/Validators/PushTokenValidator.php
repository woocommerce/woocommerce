<?php
/**
 * PushTokenValidator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Validators;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use WP_Error;

/**
 * Validator class for push tokens.
 *
 * @since 10.6.0
 */
class PushTokenValidator {
	/**
	 * The error code to return in WP_Errors.
	 *
	 * @since 10.6.0
	 */
	const ERROR_CODE = 'woocommerce_invalid_data';

	/**
	 * The regex to use when validating device UUID format.
	 *
	 * @since 10.6.0
	 */
	const DEVICE_UUID_FORMAT = '/^[A-Za-z0-9._:-]+$/';

	/**
	 * The length to validate the device UUID against.
	 *
	 * @since 10.6.0
	 */
	const DEVICE_UUID_MAXIMUM_LENGTH = 255;

	/**
	 * The length to validate the token against.
	 *
	 * @since 10.6.0
	 */
	const TOKEN_MAXIMUM_LENGTH = 4096;

	/**
	 * The regex to use when validating Apple token format.
	 *
	 * @since 10.6.0
	 */
	const TOKEN_FORMAT_APPLE = '/^[A-Fa-f0-9]{64}$/';

	/**
	 * The regex to use when validating Android token format.
	 *
	 * @since 10.6.0
	 */
	const TOKEN_FORMAT_ANDROID = '/^[A-Za-z0-9=:_\-+\/]+$/';

	/**
	 * Validates ID.
	 *
	 * @since 10.6.0
	 *
	 * @param array $data An array of data to use for validation.
	 * @return bool|WP_Error
	 */
	public static function validate_id( array $data ) {
		if ( ! isset( $data['id'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'ID is required.' );
		}

		if ( ! is_numeric( $data['id'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'ID must be numeric.' );
		}

		if ( $data['id'] <= 0 ) {
			return new WP_Error( self::ERROR_CODE, 'ID must be a positive integer.' );
		}

		return true;
	}

	/**
	 * Validates user ID.
	 *
	 * @since 10.6.0
	 *
	 * @param array $data An array of data to use for validation.
	 * @return bool|WP_Error
	 */
	public static function validate_user_id( array $data ) {
		if ( ! isset( $data['user_id'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'User ID is required.' );
		}

		if ( ! is_numeric( $data['user_id'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'User ID must be numeric.' );
		}

		if ( $data['user_id'] <= 0 ) {
			return new WP_Error( self::ERROR_CODE, 'User ID must be a positive integer.' );
		}

		return true;
	}

	/**
	 * Validates origin.
	 *
	 * @since 10.6.0
	 *
	 * @param array $data An array of data to use for validation.
	 * @return bool|WP_Error
	 */
	public static function validate_origin( array $data ) {
		if ( ! isset( $data['origin'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'Origin is required.' );
		}

		if ( ! is_string( $data['origin'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'Origin must be a string.' );
		}

		$origin = trim( $data['origin'] );

		if ( '' === $origin ) {
			return new WP_Error( self::ERROR_CODE, 'Origin cannot be empty.' );
		}

		if ( ! in_array( $origin, PushToken::ORIGINS, true ) ) {
			return new WP_Error(
				self::ERROR_CODE,
				sprintf( 'Origin must be one of: %s.', implode( ', ', PushToken::ORIGINS ) )
			);
		}

		return true;
	}

	/**
	 * Validates device UUID.
	 *
	 * @since 10.6.0
	 *
	 * @param array $data An array of data to use for validation.
	 * @return bool|WP_Error
	 */
	public static function validate_device_uuid( array $data ) {
		/**
		 * We may or may not have platform; if we don't have it, we can skip the
		 * platform-specific checks and allow the platform validation to trigger
		 * the failure.
		 */
		$maybe_platform = $data['platform'] ?? null;

		if (
			PushToken::PLATFORM_APPLE === $maybe_platform
			|| PushToken::PLATFORM_ANDROID === $maybe_platform
		) {
			/**
			 * The browser platform doesn't use a device UUID, so we don't need
			 * to check truthiness or format unless the platform is not browser.
			 */
			if ( ! isset( $data['device_uuid'] ) ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID is required.' );
			}

			if ( ! is_string( $data['device_uuid'] ) ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID must be a string.' );
			}

			$device_uuid = trim( $data['device_uuid'] );

			if ( '' === $device_uuid ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID cannot be empty.' );
			}

			if ( ! preg_match( self::DEVICE_UUID_FORMAT, $device_uuid ) ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID is an invalid format.' );
			}
		}

		if (
			isset( $data['device_uuid'] )
			&& strlen( $data['device_uuid'] ) > self::DEVICE_UUID_MAXIMUM_LENGTH
		) {
			/**
			 * Check maximum length for all device UUIDs sent, regardless of
			 * platform.
			 */
			return new WP_Error(
				self::ERROR_CODE,
				sprintf( 'Device UUID exceeds maximum length of %s.', self::DEVICE_UUID_MAXIMUM_LENGTH )
			);
		}

		return true;
	}

	/**
	 * Validates platform.
	 *
	 * @since 10.6.0
	 *
	 * @param array $data An array of data to use for validation.
	 * @return bool|WP_Error
	 */
	public static function validate_platform( array $data ) {
		if ( ! isset( $data['platform'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'Platform is required.' );
		}

		if ( ! is_string( $data['platform'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'Platform must be a string.' );
		}

		$platform = trim( $data['platform'] );

		if ( '' === $platform ) {
			return new WP_Error( self::ERROR_CODE, 'Platform cannot be empty.' );
		}

		if ( ! in_array( $platform, PushToken::PLATFORMS, true ) ) {
			return new WP_Error(
				self::ERROR_CODE,
				sprintf( 'Platform must be one of: %s.', implode( ', ', PushToken::PLATFORMS ) )
			);
		}

		return true;
	}

	/**
	 * Validates token value.
	 *
	 * @since 10.6.0
	 *
	 * @param array $data An array of data to use for validation.
	 * @return bool|WP_Error
	 */
	public static function validate_token( array $data ) {
		if ( ! isset( $data['token'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'Token is required.' );
		}

		if ( ! is_string( $data['token'] ) ) {
			return new WP_Error( self::ERROR_CODE, 'Token must be a string.' );
		}

		$token = trim( $data['token'] );

		if ( '' === $token ) {
			return new WP_Error( self::ERROR_CODE, 'Token cannot be empty.' );
		}

		if ( strlen( $token ) > self::TOKEN_MAXIMUM_LENGTH ) {
			return new WP_Error(
				self::ERROR_CODE,
				sprintf( 'Token exceeds maximum length of %s.', self::TOKEN_MAXIMUM_LENGTH )
			);
		}

		if ( ! isset( $data['platform'] ) ) {
			/**
			 * We don't know how to validate the format as we don't know the
			 * platform, so let the platform validation handle the failure.
			 */
			return true;
		}

		if (
			PushToken::PLATFORM_APPLE === $data['platform']
			&& ! preg_match( self::TOKEN_FORMAT_APPLE, $token )
		) {
			return new WP_Error( self::ERROR_CODE, 'Token is an invalid format.' );
		}

		if (
			PushToken::PLATFORM_ANDROID === $data['platform']
			&& ! preg_match( self::TOKEN_FORMAT_ANDROID, $token )
		) {
			return new WP_Error( self::ERROR_CODE, 'Token is an invalid format.' );
		}

		if ( PushToken::PLATFORM_BROWSER === $data['platform'] ) {
			$token_object = json_decode( $token, true );
			$endpoint     = $token_object['endpoint'] ?? null;

			if (
				json_last_error()
				|| ! isset( $token_object['keys']['auth'] )
				|| ! isset( $token_object['keys']['p256dh'] )
				|| ! $endpoint
				|| ! wp_http_validate_url( (string) $endpoint )
				|| ( wp_parse_url( (string) $endpoint, PHP_URL_SCHEME ) !== 'https' )
			) {
				return new WP_Error( self::ERROR_CODE, 'Token is an invalid format.' );
			}
		}

		return true;
	}
}
