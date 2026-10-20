<?php
/**
 * Argument checks shared by the public facades. WordPress-free Core zone.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;

defined( 'ABSPATH' ) || exit;

/**
 * Validate caller argument values and return them normalized. A refused value throws
 * {@see InvalidInputException} with a {@see ValidationErrorCode}, the field and params.
 *
 * @internal Engine implementation detail shared by the `Api\` facades, not part of the public API.
 */
final class ArgumentValidator {

	/**
	 * Static helper only.
	 */
	private function __construct() {}

	/**
	 * Validate and return a currency code, or null.
	 *
	 * @param mixed $value Caller value.
	 * @throws InvalidInputException If the value is not null or a three-letter uppercase code.
	 */
	public static function validate_currency( $value ): ?string {
		if ( null !== $value && ( ! is_string( $value ) || 1 !== preg_match( '/^[A-Z]{3}\z/', $value ) ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_VALUE, 'currency', 'must be null or a three-letter uppercase ISO-4217 code' );
		}

		return $value;
	}

	/**
	 * Validate and return a string.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is not a string.
	 */
	public static function validate_string( string $key, $value ): string {
		if ( ! is_string( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be a string', array( 'type' => 'string' ) );
		}

		return $value;
	}

	/**
	 * Validate and return a non-empty string. Null (a missing argument) and '' are reported
	 * as required.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is missing, empty or not a string.
	 */
	public static function validate_non_empty_string( string $key, $value ): string {
		if ( null === $value || '' === $value ) {
			throw self::get_invalid_input( ValidationErrorCode::REQUIRED, $key, 'must be a non-empty string' );
		}
		if ( ! is_string( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be a non-empty string', array( 'type' => 'string' ) );
		}

		return $value;
	}

	/**
	 * Validate and return a string, or null.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is not null or a string.
	 */
	public static function validate_nullable_string( string $key, $value ): ?string {
		if ( null !== $value && ! is_string( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be null or a string', array( 'type' => 'string' ) );
		}

		return $value;
	}

	/**
	 * Validate and return a positive integer id (a digit string is cast), or null.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is not null or a positive integer.
	 */
	public static function validate_nullable_id( string $key, $value ): ?int {
		if ( null === $value ) {
			return null;
		}

		return self::validate_int_at_least( $key, $value, 1, 'must be null or a positive integer' );
	}

	/**
	 * Validate and return a positive integer (a digit string is cast).
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is not a positive integer.
	 */
	public static function validate_positive_int( string $key, $value ): int {
		return self::validate_int_at_least( $key, $value, 1, 'must be a positive integer' );
	}

	/**
	 * Validate and return a non-negative integer (a digit string is cast).
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is not a non-negative integer.
	 */
	public static function validate_non_negative_int( string $key, $value ): int {
		return self::validate_int_at_least( $key, $value, 0, 'must be a non-negative integer' );
	}

	/**
	 * Validate a list of positive integer ids (digit strings are cast) and return it.
	 * An item is reported by its index (`ids.1`).
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, int>
	 * @throws InvalidInputException If the value is not a list of positive integers.
	 */
	public static function validate_id_list( string $key, $value ): array {
		if ( ! self::is_list( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be a list of positive integers', array( 'type' => 'array' ) );
		}

		$ids = array();
		foreach ( $value as $index => $id ) {
			$ids[] = self::validate_positive_int( self::get_item_field( $key, $index ), $id );
		}

		return $ids;
	}

	/**
	 * Validate a non-empty string or a list of them and return it as a list. An item is
	 * reported by its index (`status.1`).
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, string>
	 * @throws InvalidInputException If the value is not a non-empty string or a list of them.
	 */
	public static function validate_string_list( string $key, $value ): array {
		if ( is_string( $value ) ) {
			return array( self::validate_non_empty_string( $key, $value ) );
		}
		if ( ! self::is_list( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be a non-empty string or a list of them', array( 'type' => 'array' ) );
		}

		$strings = array();
		foreach ( $value as $index => $item ) {
			$strings[] = self::validate_non_empty_string( self::get_item_field( $key, $index ), $item );
		}

		return $strings;
	}

	/**
	 * Validate and return an array, or null.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int|string, mixed>|null
	 * @throws InvalidInputException If the value is not null or an array.
	 */
	public static function validate_nullable_array( string $key, $value ): ?array {
		if ( null !== $value && ! is_array( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be null or an array', array( 'type' => 'object' ) );
		}

		return $value;
	}

	/**
	 * Validate a GMT datetime, or null, and return it as a UTC `Y-m-d H:i:s` string.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value `DateTimeInterface`, GMT `Y-m-d H:i:s` string, or null.
	 * @throws InvalidInputException If the value is not a valid datetime.
	 */
	public static function validate_nullable_date( string $key, $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		if ( $value instanceof DateTimeInterface ) {
			return ( new DateTimeImmutable( '@' . $value->getTimestamp() ) )->format( 'Y-m-d H:i:s' );
		}

		if ( is_string( $value ) ) {
			$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
			if ( false !== $parsed && $parsed->format( 'Y-m-d H:i:s' ) === $value ) {
				return $value;
			}
		}

		throw self::get_invalid_input( ValidationErrorCode::INVALID_VALUE, $key, 'must be null, a DateTimeInterface, or a GMT "Y-m-d H:i:s" string' );
	}

	/**
	 * Validate a money value and return it normalized to the storage scale; null is 0.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Number, numeric string, or null.
	 * @throws InvalidInputException If the value is not numeric.
	 */
	public static function validate_money( string $key, $value ): string {
		if ( null === $value ) {
			return MoneyScale::normalize_money( 0 );
		}

		if ( ! is_int( $value ) && ! is_float( $value ) && ! ( is_string( $value ) && is_numeric( $value ) ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be a number or a numeric string', array( 'type' => 'number' ) );
		}

		return MoneyScale::normalize_money( $value );
	}

	/**
	 * Validate a list of arrays and return it with string-keyed rows. A row is reported
	 * by its index (`items.0`).
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidInputException If the value is not a list of arrays.
	 */
	public static function validate_list_of_arrays( string $key, $value ): array {
		if ( ! self::is_list( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, 'must be a list of arrays', array( 'type' => 'array' ) );
		}

		$rows = array();
		foreach ( $value as $index => $row ) {
			if ( ! is_array( $row ) ) {
				throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, self::get_item_field( $key, $index ), 'must be an array', array( 'type' => 'object' ) );
			}
			$rows[] = Coercion::coerce_string_keyed( $row );
		}

		return $rows;
	}

	/**
	 * Validate the shape of contract addresses: a map keyed `billing` / `shipping` with
	 * array values. An address is reported by its type (`addresses.billing`).
	 *
	 * @param mixed $value Caller value.
	 * @return array<string, array<int|string, mixed>>
	 * @throws InvalidInputException If the value is not such a map.
	 */
	public static function validate_contract_addresses( $value ): array {
		if ( ! is_array( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, 'addresses', 'must be an array keyed "billing" / "shipping"', array( 'type' => 'object' ) );
		}

		$types     = array( Contract::ADDRESS_BILLING, Contract::ADDRESS_SHIPPING );
		$addresses = array();
		foreach ( $value as $type => $address ) {
			$field = self::get_item_field( 'addresses', $type );
			if ( ! in_array( $type, $types, true ) ) {
				throw self::get_invalid_input( ValidationErrorCode::NOT_IN_ENUM, $field, 'is not an address type; use "billing" or "shipping"', array( 'allowed' => $types ) );
			}
			if ( ! is_array( $address ) ) {
				throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $field, 'must be an array', array( 'type' => 'object' ) );
			}

			$addresses[ $type ] = $address;
		}

		return $addresses;
	}

	/**
	 * Validate an integer (a digit string is cast) of at least `$min`.
	 *
	 * @param string $key         Field name.
	 * @param mixed  $value       Caller value.
	 * @param int    $min         Lowest allowed value.
	 * @param string $requirement What the value must be, for the message.
	 * @throws InvalidInputException If the value is not an integer, or is below `$min`.
	 */
	private static function validate_int_at_least( string $key, $value, int $min, string $requirement ): int {
		$value = self::cast_digit_string( $value );
		if ( ! is_int( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $key, $requirement, array( 'type' => 'integer' ) );
		}
		if ( $value < $min ) {
			throw self::get_invalid_input( ValidationErrorCode::OUT_OF_BOUNDS, $key, $requirement, array( 'min' => $min ) );
		}

		return $value;
	}

	/**
	 * Build the validation error for a refused value; the message quotes the field.
	 *
	 * @param string               $code        Error code.
	 * @param string               $field       Field, in dot notation.
	 * @param string               $requirement What the value must be, for the message.
	 * @param array<string, mixed> $params      Message values.
	 */
	private static function get_invalid_input( string $code, string $field, string $requirement, array $params = array() ): InvalidInputException {
		return new InvalidInputException( $code, $field, sprintf( '"%s" %s.', $field, $requirement ), $params );
	}

	/**
	 * Dot path of a list item or map entry.
	 *
	 * @param string     $key   Field name.
	 * @param int|string $index Item index or map key.
	 */
	private static function get_item_field( string $key, $index ): string {
		return $key . '.' . $index;
	}

	/**
	 * Whether a value is a list: an array with consecutive int keys from 0 (an empty array is one).
	 *
	 * @param mixed $value Caller value.
	 * @phpstan-assert-if-true array<int, mixed> $value
	 */
	private static function is_list( $value ): bool {
		return is_array( $value ) && ( array() === $value || array_keys( $value ) === range( 0, count( $value ) - 1 ) );
	}

	/**
	 * Cast a digit string to an int; any other value is returned unchanged.
	 *
	 * @param mixed $value Caller value.
	 * @return mixed
	 */
	private static function cast_digit_string( $value ) {
		if ( is_string( $value ) && ctype_digit( $value ) ) {
			return (int) $value;
		}

		return $value;
	}
}
