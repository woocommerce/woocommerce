<?php
/**
 * PushTokenValidator class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Validators;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use WP_Error;

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

/**
 * Validator class for push tokens.
 *
 * @since 10.6.0
 */
class PushTokenValidator {
	const VALIDATABLE_FIELDS = array(
		'id',
		'user_id',
		'origin',
		'device_uuid',
		'device_locale',
		'platform',
		'token',
		'metadata',
	);

	/**
	 * The error code to return in WP_Errors.
	 *
	 * @since 10.6.0
	 */
	const ERROR_CODE = 'woocommerce_invalid_data';

	/**
	 * Validates device locale format:
	 * - language code (2–3 lowercase letters)
	 * - underscore
	 * - region code (2 uppercase letters).
	 */
	const DEVICE_LOCALE_FORMAT = '/^(?<language>[a-z]{2,3})_(?<region>[A-Z]{2})$/';

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
	 * Validates the fields defined in `$fields`, or all the list of known
	 * fields if `$fields` is empty.
	 *
	 * @since 10.6.0
	 *
	 * @param array $data The data to be validated.
	 * @param array $fields The fields to validate.
	 * @return bool|WP_Error
	 */
	public static function validate( array $data, ?array $fields = array() ) {
		$fields = empty( $fields ) ? self::VALIDATABLE_FIELDS : $fields;

		foreach ( $fields as $field ) {
			$method = 'validate_' . $field;

			if ( ! method_exists( self::class, $method ) ) {
				return new WP_Error(
					'woocommerce_invalid_data',
					sprintf( 'Can\'t validate param \'%s\' as a validator does not exist for it.', $field )
				);
			}

			$result = self::$method( $data[ $field ] ?? null, $data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Validates ID.
	 *
	 * @since 10.6.0
	 *
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 */
	private static function validate_id( $value, ?array $context = array() ) {
		if ( is_null( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'ID is required.' );
		}

		if ( ! is_numeric( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'ID must be numeric.' );
		}

		if ( $value <= 0 ) {
			return new WP_Error( self::ERROR_CODE, 'ID must be a positive integer.' );
		}

		return true;
	}

	/**
	 * Validates user ID.
	 *
	 * @since 10.6.0
	 *
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 */
	private static function validate_user_id( $value, ?array $context = array() ) {
		if ( is_null( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'User ID is required.' );
		}

		if ( ! is_numeric( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'User ID must be numeric.' );
		}

		if ( $value <= 0 ) {
			return new WP_Error( self::ERROR_CODE, 'User ID must be a positive integer.' );
		}

		return true;
	}

	/**
	 * Validates origin.
	 *
	 * @since 10.6.0
	 *
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 */
	private static function validate_origin( $value, ?array $context = array() ) {
		if ( is_null( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Origin is required.' );
		}

		if ( ! is_string( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Origin must be a string.' );
		}

		$value = trim( $value );

		if ( '' === $value ) {
			return new WP_Error( self::ERROR_CODE, 'Origin cannot be empty.' );
		}

		if ( ! in_array( $value, PushToken::ORIGINS, true ) ) {
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
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 */
	private static function validate_device_uuid( $value, ?array $context = array() ) {
		/**
		 * We may or may not have platform; if we don't have it, we can skip the
		 * platform-specific checks and allow the platform validation to trigger
		 * the failure.
		 */
		$maybe_platform = $context['platform'] ?? null;

		if (
			PushToken::PLATFORM_APPLE === $maybe_platform
			|| PushToken::PLATFORM_ANDROID === $maybe_platform
		) {
			/**
			 * The browser platform doesn't use a device UUID, so we don't need
			 * to check truthiness or format unless the platform is not browser.
			 */
			if ( is_null( $value ) ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID is required.' );
			}

			if ( ! is_string( $value ) ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID must be a string.' );
			}

			$value = trim( $value );

			if ( '' === $value ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID cannot be empty.' );
			}

			if ( ! preg_match( self::DEVICE_UUID_FORMAT, $value ) ) {
				return new WP_Error( self::ERROR_CODE, 'Device UUID is an invalid format.' );
			}
		}

		if (
			is_string( $value )
			&& strlen( $value ) > self::DEVICE_UUID_MAXIMUM_LENGTH ) {
			/**
			 * Check maximum length for all device UUIDs sent, regardless of
			 * platform. We don't know for sure the value is a string as the
			 * check above isn't guaranteed to have run, so ensure it is a
			 * string before evaluating this validation rule.
			 */
			return new WP_Error(
				self::ERROR_CODE,
				sprintf( 'Device UUID exceeds maximum length of %s.', self::DEVICE_UUID_MAXIMUM_LENGTH )
			);
		}

		return true;
	}

	/**
	 * Validates device locale.
	 *
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 *
	 * @since 10.6.0
	 */
	private static function validate_device_locale( $value, ?array $context = array() ) {
		if ( ! isset( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Device locale is required.' );
		}

		if ( ! is_string( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Device locale must be a string.' );
		}

		$value = trim( $value );

		if ( '' === $value ) {
			return new WP_Error( self::ERROR_CODE, 'Device locale cannot be empty.' );
		}

		if ( ! preg_match( self::DEVICE_LOCALE_FORMAT, $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Device locale is an invalid format.' );
		}

		return true;
	}

	/**
	 * Validates platform.
	 *
	 * @since 10.6.0
	 *
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 */
	private static function validate_platform( $value, ?array $context = array() ) {
		if ( is_null( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Platform is required.' );
		}

		if ( ! is_string( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Platform must be a string.' );
		}

		$value = trim( $value );

		if ( '' === $value ) {
			return new WP_Error( self::ERROR_CODE, 'Platform cannot be empty.' );
		}

		if ( ! in_array( $value, PushToken::PLATFORMS, true ) ) {
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
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 */
	private static function validate_token( $value, ?array $context = array() ) {
		if ( is_null( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Token is required.' );
		}

		if ( ! is_string( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Token must be a string.' );
		}

		$value = trim( $value );

		if ( '' === $value ) {
			return new WP_Error( self::ERROR_CODE, 'Token cannot be empty.' );
		}

		if ( strlen( $value ) > self::TOKEN_MAXIMUM_LENGTH ) {
			return new WP_Error(
				self::ERROR_CODE,
				sprintf( 'Token exceeds maximum length of %s.', self::TOKEN_MAXIMUM_LENGTH )
			);
		}

		if ( ! isset( $context['platform'] ) ) {
			/**
			 * We don't know how to validate the format as we don't know the
			 * platform, so let the platform validation handle the failure.
			 */
			return true;
		}

		if (
			PushToken::PLATFORM_APPLE === $context['platform']
			&& ! preg_match( self::TOKEN_FORMAT_APPLE, $value )
		) {
			return new WP_Error( self::ERROR_CODE, 'Token is an invalid format.' );
		}

		if (
			PushToken::PLATFORM_ANDROID === $context['platform']
			&& ! preg_match( self::TOKEN_FORMAT_ANDROID, $value )
		) {
			return new WP_Error( self::ERROR_CODE, 'Token is an invalid format.' );
		}

		if ( PushToken::PLATFORM_BROWSER === $context['platform'] ) {
			$token_object = json_decode( $value, true );
			$endpoint     = $token_object['endpoint'] ?? null;

			if (
				is_null( $token_object )
				|| json_last_error()
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

	/**
	 * Validates metadata.
	 *
	 * @param mixed      $value The value to validate.
	 * @param array|null $context An array of other values included as context for the validation.
	 * @return bool|WP_Error
	 *
	 * @since 10.6.0
	 */
	private static function validate_metadata( $value, ?array $context = array() ) {
		if ( ! isset( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Metadata is required.' );
		}

		if ( ! is_array( $value ) ) {
			return new WP_Error( self::ERROR_CODE, 'Metadata must be an array.' );
		}

		foreach ( $value as $key => $item ) {
			if ( ! is_scalar( $item ) ) {
				return new WP_Error( self::ERROR_CODE, 'Metadata items must be scalar values.' );
			}
		}

		return true;
	}
}

// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
