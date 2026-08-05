<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Scalars;

use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Custom scalar for ISO 8601 date/time values.
 */
#[Description( 'An ISO 8601 encoded date and time string.' )]
class DateTime {
	/**
	 * Serialize a PHP value to the scalar's transport format.
	 *
	 * @param mixed $value The value to serialize.
	 * @return string
	 */
	public static function serialize( mixed $value ): string {
		if ( $value instanceof \DateTimeInterface ) {
			return $value->format( \DateTimeInterface::ATOM );
		}
		return (string) $value;
	}

	/**
	 * Parse a value received from a client (variable or literal).
	 *
	 * @param string $value The raw string value from the client.
	 * @return \DateTimeImmutable
	 * @throws \InvalidArgumentException When the value cannot be parsed as an ISO 8601 date/time string.
	 */
	public static function parse( string $value ): \DateTimeImmutable {
		try {
			return new \DateTimeImmutable( $value );
		} catch ( \Exception $e ) {
			// PHP 8.3+ throws \DateMalformedStringException; earlier versions
			// throw a plain \Exception. Both extend \Exception, so a single
			// catch captures them.
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML output; serialized as JSON in the GraphQL error response.
			throw new \InvalidArgumentException(
				sprintf( 'Invalid ISO 8601 date/time: %s', $e->getMessage() ),
				0,
				$e
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}
}
