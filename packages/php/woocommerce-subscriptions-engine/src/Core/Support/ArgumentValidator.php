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
 * Validate caller argument values and return them normalized, in two forms:
 * - the static `validate_*()` checks throw {@see InvalidInputException} (a
 *   {@see ValidationErrorCode}, the field and params) for a refused value;
 * - an instance collects: each `get_*()` runs the matching check, records its errors and
 *   returns the normalized value or null, and {@see self::throw_errors()} throws them all.
 *
 * A check stops at a field's first failed rule; a list check reports every failed item.
 *
 * @internal Engine implementation detail shared by the `Api\` facades, not part of the public API.
 */
final class ArgumentValidator {

	/**
	 * Format of an engine slug, in JSON-schema syntax: 1-64 lowercase letters, digits, hyphens or
	 * underscores. Extension slugs (the owner), schedule sources, lock operation labels and history
	 * entry types share it; 64 is the width of their columns.
	 */
	public const SLUG_PATTERN = '^[a-z0-9_-]{1,64}$';

	/**
	 * Errors recorded by the collecting form, in recording order.
	 *
	 * @var list<InvalidInputException>
	 */
	private $errors = array();

	/**
	 * Record an error found outside the checks.
	 *
	 * @param InvalidInputException $e Validation error.
	 */
	public function add( InvalidInputException $e ): void {
		foreach ( $e->get_errors() as $error ) {
			$this->errors[] = $error;
		}
	}

	/**
	 * Throw every recorded error at once; do nothing when nothing failed.
	 *
	 * @throws InvalidInputException If an error was recorded.
	 */
	public function throw_errors(): void {
		if ( array() !== $this->errors ) {
			throw InvalidInputException::from_errors( $this->errors );
		}
	}

	/**
	 * Collecting {@see self::validate_currency()}: the value, or null when refused.
	 *
	 * @param mixed $value Caller value.
	 */
	public function get_currency( $value ): ?string {
		try {
			return self::validate_currency( $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_string()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_string( string $key, $value ): ?string {
		try {
			return self::validate_string( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_non_empty_string()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_non_empty_string( string $key, $value ): ?string {
		try {
			return self::validate_non_empty_string( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_bounded_string()}: the value, or null when refused.
	 *
	 * @param string $key        Field name.
	 * @param mixed  $value      Caller value.
	 * @param int    $max_length Most characters allowed.
	 */
	public function get_bounded_string( string $key, $value, int $max_length ): ?string {
		try {
			return self::validate_bounded_string( $key, $value, $max_length );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_extension_slug()}: the slug, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_extension_slug( string $key, $value ): ?string {
		try {
			return self::validate_extension_slug( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_slug()}: the slug, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_slug( string $key, $value ): ?string {
		try {
			return self::validate_slug( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_nullable_string()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_nullable_string( string $key, $value ): ?string {
		try {
			return self::validate_nullable_string( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_nullable_id()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_nullable_id( string $key, $value ): ?int {
		try {
			return self::validate_nullable_id( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_positive_int()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_positive_int( string $key, $value ): ?int {
		try {
			return self::validate_positive_int( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_non_negative_int()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_non_negative_int( string $key, $value ): ?int {
		try {
			return self::validate_non_negative_int( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_id_list()}: the list, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, int>|null
	 */
	public function get_id_list( string $key, $value ): ?array {
		try {
			return self::validate_id_list( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_string_list()}: the list, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, string>|null
	 */
	public function get_string_list( string $key, $value ): ?array {
		try {
			return self::validate_string_list( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_nullable_array()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int|string, mixed>|null
	 */
	public function get_nullable_array( string $key, $value ): ?array {
		try {
			return self::validate_nullable_array( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_nullable_date()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_nullable_date( string $key, $value ): ?string {
		try {
			return self::validate_nullable_date( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_money()}: the value, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 */
	public function get_money( string $key, $value ): ?string {
		try {
			return self::validate_money( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_list_of_arrays()}: the rows, or null when refused.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @return array<int, array<string, mixed>>|null
	 */
	public function get_list_of_arrays( string $key, $value ): ?array {
		try {
			return self::validate_list_of_arrays( $key, $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

	/**
	 * Collecting {@see self::validate_contract_addresses()}: the addresses, or null when refused.
	 *
	 * @param mixed $value Caller value.
	 * @return array<string, array<int|string, mixed>>|null
	 */
	public function get_contract_addresses( $value ): ?array {
		try {
			return self::validate_contract_addresses( $value );
		} catch ( InvalidInputException $e ) {
			$this->add( $e );
			return null;
		}
	}

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
	 * Validate and return a string, trimmed, of 1 to `$max_length` characters, for a value matched
	 * against a column of that width. A blank value is refused as required: the default collations
	 * ignore trailing spaces, so it would match stored empty values.
	 *
	 * @param string $key        Field name.
	 * @param mixed  $value      Caller value.
	 * @param int    $max_length Most characters allowed after trimming.
	 * @throws InvalidInputException If the value is missing, empty, blank, not a string or too long.
	 */
	public static function validate_bounded_string( string $key, $value, int $max_length ): string {
		$string = trim( self::validate_non_empty_string( $key, $value ) );
		if ( '' === $string ) {
			throw self::get_invalid_input( ValidationErrorCode::REQUIRED, $key, 'must not be blank' );
		}
		if ( mb_strlen( $string ) > $max_length ) {
			throw self::get_invalid_input( ValidationErrorCode::TOO_LONG, $key, sprintf( 'must be at most %d characters', $max_length ), array( 'max' => $max_length ) );
		}

		return $string;
	}

	/**
	 * Validate and return an extension slug: a non-empty string matching
	 * {@see self::SLUG_PATTERN}. No trimming or case folding: a slug that differs is
	 * refused, so one owner has one spelling.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is missing, empty, not a string or not a valid slug.
	 */
	public static function validate_extension_slug( string $key, $value ): string {
		return self::validate_slug( $key, $value );
	}

	/**
	 * Validate and return a slug: a non-empty string matching {@see self::SLUG_PATTERN}, not
	 * trimmed or case folded.
	 *
	 * @param string $key   Field name.
	 * @param mixed  $value Caller value.
	 * @throws InvalidInputException If the value is missing, empty, not a string or not a valid slug.
	 */
	public static function validate_slug( string $key, $value ): string {
		$slug = self::validate_non_empty_string( $key, $value );
		if ( ! self::is_slug( $slug ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_PATTERN, $key, 'must be 1-64 lowercase letters, digits, hyphens or underscores', array( 'pattern' => self::SLUG_PATTERN ) );
		}

		return $slug;
	}

	/**
	 * Validate and return a creation key, or null: a string of 1-100 characters. The caller trims
	 * it first, because the binary key columns ignore trailing spaces when comparing.
	 *
	 * @param mixed $value Caller value.
	 * @throws InvalidInputException If the value is not null or a string of 1-100 characters.
	 */
	public static function validate_creation_key( $value ): ?string {
		if ( null === $value ) {
			return null;
		}
		if ( ! is_string( $value ) ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, 'creation_key', 'must be a string', array( 'type' => 'string' ) );
		}
		if ( '' === $value || mb_strlen( $value ) > 100 ) {
			throw self::get_invalid_input( ValidationErrorCode::INVALID_VALUE, 'creation_key', 'must be 1-100 characters' );
		}

		return $value;
	}

	/**
	 * Whether a string matches {@see self::SLUG_PATTERN}.
	 *
	 * @param string $slug Possible slug.
	 */
	public static function is_slug( string $slug ): bool {
		return 1 === preg_match( '/' . self::SLUG_PATTERN . '/D', $slug );
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
	 * Each failed item is reported by its index (`ids.1`).
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

		$items = new self();
		$ids   = array();
		foreach ( $value as $index => $id ) {
			$ids[] = (int) $items->get_positive_int( self::get_item_field( $key, $index ), $id );
		}
		$items->throw_errors();

		return $ids;
	}

	/**
	 * Validate a non-empty string or a list of them and return it as a list. Each failed
	 * item is reported by its index (`status.1`).
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

		$items   = new self();
		$strings = array();
		foreach ( $value as $index => $item ) {
			$strings[] = (string) $items->get_non_empty_string( self::get_item_field( $key, $index ), $item );
		}
		$items->throw_errors();

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
	 * Validate a list of arrays and return it with string-keyed rows. Each failed row is
	 * reported by its index (`items.0`).
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

		$items = new self();
		$rows  = array();
		foreach ( $value as $index => $row ) {
			if ( ! is_array( $row ) ) {
				$items->add( self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, self::get_item_field( $key, $index ), 'must be an array', array( 'type' => 'object' ) ) );
				continue;
			}
			$rows[] = Coercion::coerce_string_keyed( $row );
		}
		$items->throw_errors();

		return $rows;
	}

	/**
	 * Validate the shape of contract addresses: a map keyed `billing` / `shipping` with
	 * array values. Each failed address is reported by its type (`addresses.billing`).
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
		$items     = new self();
		$addresses = array();
		foreach ( $value as $type => $address ) {
			$field = self::get_item_field( 'addresses', $type );
			if ( ! in_array( $type, $types, true ) ) {
				$items->add( self::get_invalid_input( ValidationErrorCode::NOT_IN_ENUM, $field, 'is not an address type; use "billing" or "shipping"', array( 'allowed' => $types ) ) );
			} elseif ( ! is_array( $address ) ) {
				$items->add( self::get_invalid_input( ValidationErrorCode::INVALID_TYPE, $field, 'must be an array', array( 'type' => 'object' ) ) );
			} else {
				$addresses[ $type ] = $address;
			}
		}
		$items->throw_errors();

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
	public static function get_item_field( string $key, $index ): string {
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
