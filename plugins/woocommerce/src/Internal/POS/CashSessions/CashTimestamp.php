<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Cash session timestamps: explicit-offset RFC 3339 input, UTC storage, and UTC output with a Z suffix.
 *
 * @since 11.3.0
 */
final class CashTimestamp {

	/**
	 * Storage format for UTC datetime columns.
	 */
	private const STORAGE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Convert an RFC 3339 timestamp with an explicit offset to a UTC storage value.
	 *
	 * Fractional seconds are dropped because storage has second precision.
	 *
	 * @since 11.3.0
	 *
	 * @param string $value Timestamp such as "2026-09-25T14:32:10+02:00".
	 * @return string UTC value in "Y-m-d H:i:s" format.
	 * @throws InvalidArgumentException When the value is malformed or has no explicit offset.
	 */
	public static function to_gmt( string $value ): string {
		$pattern = '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.\d+)?(Z|([+-])(\d{2}):(\d{2}))$/D';
		if ( ! preg_match( $pattern, $value, $m ) ) {
			throw new InvalidArgumentException( 'The timestamp must be RFC 3339 with an explicit offset, such as "2026-09-25T12:32:10Z".' );
		}

		$valid = checkdate( (int) $m[2], (int) $m[3], (int) $m[1] )
			&& (int) $m[4] < 24 && (int) $m[5] < 60 && (int) $m[6] < 60
			&& ( 'Z' === $m[7] || ( (int) $m[9] < 24 && (int) $m[10] < 60 ) );
		if ( ! $valid ) {
			throw new InvalidArgumentException( 'The timestamp is not a valid date and time.' );
		}

		$offset = 'Z' === $m[7] ? '+00:00' : $m[7];
		$date   = new DateTimeImmutable( "{$m[1]}-{$m[2]}-{$m[3]}T{$m[4]}:{$m[5]}:{$m[6]}{$offset}" );

		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( self::STORAGE_FORMAT );
	}

	/**
	 * Format a stored UTC value for responses.
	 *
	 * @since 11.3.0
	 *
	 * @param string|null $gmt UTC value in "Y-m-d H:i:s" format.
	 * @return string|null ISO 8601 UTC value with a Z suffix, or null.
	 */
	public static function format( ?string $gmt ): ?string {
		if ( null === $gmt || '' === $gmt ) {
			return null;
		}

		$date = DateTimeImmutable::createFromFormat( self::STORAGE_FORMAT, $gmt, new DateTimeZone( 'UTC' ) );

		return false === $date ? null : $date->format( 'Y-m-d\TH:i:s\Z' );
	}
}
