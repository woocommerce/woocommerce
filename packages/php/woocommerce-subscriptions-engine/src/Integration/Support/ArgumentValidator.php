<?php
/**
 * Argument validators shared by the public write facades.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Support
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\MoneyScale;

defined( 'ABSPATH' ) || exit;

/**
 * Validate caller argument values and return them normalized.
 *
 * @internal Engine implementation detail shared by the `Api\` write facades, not part of the public API.
 */
final class ArgumentValidator {

	/**
	 * Keep the keys in `$allowed`; each other key raises a `_doing_it_wrong()` notice and is dropped.
	 *
	 * @param string                   $method  Public facade method, for the notice.
	 * @param array<int|string, mixed> $args    Caller arguments.
	 * @param array<string, true>      $allowed Accepted keys, as a key map.
	 * @param string                   $what    What the keys belong to, for the notice.
	 * @return array<string, mixed> The arguments with known keys only.
	 */
	public static function filter_known_keys( string $method, array $args, array $allowed, string $what = 'key' ): array {
		$filtered = array();
		foreach ( $args as $key => $value ) {
			if ( isset( $allowed[ $key ] ) ) {
				$filtered[ (string) $key ] = $value;
				continue;
			}

			_doing_it_wrong(
				esc_html( $method ),
				sprintf( 'Unknown %s "%s" ignored.', esc_html( $what ), esc_html( (string) $key ) ),
				'0.0.1'
			);
		}

		return $filtered;
	}

	/**
	 * Validate and return a currency code, or null.
	 *
	 * @param mixed $value Caller value.
	 * @throws InvalidArgumentException If the value is not null or a three-letter uppercase code.
	 */
	public static function validate_currency( $value ): ?string {
		if ( null !== $value && ( ! is_string( $value ) || 1 !== preg_match( '/^[A-Z]{3}$/', $value ) ) ) {
			throw new InvalidArgumentException( '"currency" must be null or a three-letter uppercase ISO-4217 code.' );
		}

		return $value;
	}

	/**
	 * Validate and return a string.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidArgumentException If the value is not a string.
	 */
	public static function validate_string( string $key, $value ): string {
		if ( ! is_string( $value ) ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be a string.', esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Validate and return a string, or null.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidArgumentException If the value is not null or a string.
	 */
	public static function validate_nullable_string( string $key, $value ): ?string {
		if ( null !== $value && ! is_string( $value ) ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be null or a string.', esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Validate and return a positive integer id (a digit string is cast), or null.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidArgumentException If the value is not null or a positive integer.
	 */
	public static function validate_nullable_id( string $key, $value ): ?int {
		if ( null === $value ) {
			return null;
		}

		if ( is_string( $value ) && 1 === preg_match( '/^[0-9]+$/', $value ) ) {
			$value = (int) $value;
		}

		if ( ! is_int( $value ) || $value <= 0 ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be null or a positive integer.', esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Validate and return a non-negative integer (a digit string is cast).
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidArgumentException If the value is not a non-negative integer.
	 */
	public static function validate_non_negative_int( string $key, $value ): int {
		if ( is_string( $value ) && 1 === preg_match( '/^[0-9]+$/', $value ) ) {
			$value = (int) $value;
		}

		if ( ! is_int( $value ) || $value < 0 ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be a non-negative integer.', esc_html( $key ) ) );
		}

		return $value;
	}

	/**
	 * Validate a list of positive integer ids (digit strings are cast) and return it.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, int>
	 * @throws InvalidArgumentException If the value is not a list of positive integers.
	 */
	public static function validate_id_list( string $key, $value ): array {
		if ( ! is_array( $value ) || ( array() !== $value && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be a list of positive integers.', esc_html( $key ) ) );
		}

		$ids = array();
		foreach ( $value as $id ) {
			if ( is_string( $id ) && 1 === preg_match( '/^[0-9]+$/', $id ) ) {
				$id = (int) $id;
			}
			if ( ! is_int( $id ) || $id <= 0 ) {
				throw new InvalidArgumentException( sprintf( '"%s" must be a list of positive integers.', esc_html( $key ) ) );
			}
			$ids[] = $id;
		}

		return $ids;
	}

	/**
	 * Validate a non-empty string or a list of them and return it as a list.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, string>
	 * @throws InvalidArgumentException If the value is not a non-empty string or a list of them.
	 */
	public static function validate_string_list( string $key, $value ): array {
		$values = is_string( $value ) ? array( $value ) : $value;
		if ( ! is_array( $values ) || ( array() !== $values && array_keys( $values ) !== range( 0, count( $values ) - 1 ) ) ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be a non-empty string or a list of them.', esc_html( $key ) ) );
		}

		$strings = array();
		foreach ( $values as $item ) {
			if ( ! is_string( $item ) || '' === $item ) {
				throw new InvalidArgumentException( sprintf( '"%s" must be a non-empty string or a list of them.', esc_html( $key ) ) );
			}
			$strings[] = $item;
		}

		return $strings;
	}

	/**
	 * Validate a GMT datetime, or null, and return it as a UTC `Y-m-d H:i:s` string.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value `DateTimeInterface`, GMT `Y-m-d H:i:s` string, or null.
	 * @throws InvalidArgumentException If the value is not a valid datetime.
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

		throw new InvalidArgumentException( sprintf( '"%s" must be null, a DateTimeInterface, or a GMT "Y-m-d H:i:s" string.', esc_html( $key ) ) );
	}

	/**
	 * Validate a money value and return it normalized to the storage scale; null is 0.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Number, numeric string, or null.
	 * @throws InvalidArgumentException If the value is not numeric.
	 */
	public static function validate_money( string $key, $value ): string {
		if ( null === $value ) {
			return MoneyScale::normalize_money( 0 );
		}

		if ( ! is_int( $value ) && ! is_float( $value ) && ! ( is_string( $value ) && is_numeric( $value ) ) ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be a number or a numeric string.', esc_html( $key ) ) );
		}

		return MoneyScale::normalize_money( $value );
	}

	/**
	 * Validate a list of arrays and return it with string-keyed rows.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidArgumentException If the value is not a list of arrays.
	 */
	public static function validate_list_of_arrays( string $key, $value ): array {
		if ( ! is_array( $value ) || ( array() !== $value && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) ) {
			throw new InvalidArgumentException( sprintf( '"%s" must be a list of arrays.', esc_html( $key ) ) );
		}

		$rows = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				throw new InvalidArgumentException( sprintf( '"%s" must be a list of arrays.', esc_html( $key ) ) );
			}
			$rows[] = Coercion::coerce_string_keyed( $row );
		}

		return $rows;
	}

	/**
	 * Validate contract item rows; unknown row keys are dropped with a notice.
	 *
	 * @param mixed  $value         Caller value.
	 * @param string $function_name Function named in the notice; defaults to this method.
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidArgumentException If the value is not a list of item rows.
	 */
	public static function validate_contract_items( $value, string $function_name = __METHOD__ ): array {
		$allowed = array_fill_keys( Contract::ITEM_FIELDS, true );
		$rows    = array();
		foreach ( self::validate_list_of_arrays( 'items', $value ) as $row ) {
			$rows[] = self::filter_known_keys( $function_name, $row, $allowed, 'item key' );
		}

		return $rows;
	}

	/**
	 * Validate contract addresses keyed `billing` / `shipping`; unknown address keys are
	 * dropped with a notice.
	 *
	 * @param mixed  $value         Caller value.
	 * @param string $function_name Function named in the notice; defaults to this method.
	 * @return array<string, array<string, mixed>>
	 * @throws InvalidArgumentException If the map is not keyed `billing` / `shipping` with array values.
	 */
	public static function validate_contract_addresses( $value, string $function_name = __METHOD__ ): array {
		if ( ! is_array( $value ) ) {
			throw new InvalidArgumentException( '"addresses" must be an array keyed "billing" / "shipping".' );
		}

		$allowed   = array_fill_keys( Contract::ADDRESS_FIELDS, true );
		$addresses = array();
		foreach ( $value as $type => $address ) {
			if ( ! in_array( $type, array( Contract::ADDRESS_BILLING, Contract::ADDRESS_SHIPPING ), true ) || ! is_array( $address ) ) {
				throw new InvalidArgumentException( '"addresses" must be an array keyed "billing" / "shipping" with array values.' );
			}

			$addresses[ $type ] = self::filter_known_keys( $function_name, $address, $allowed, 'address key' );
		}

		return $addresses;
	}
}
