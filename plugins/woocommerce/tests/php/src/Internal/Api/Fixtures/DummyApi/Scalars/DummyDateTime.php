<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Scalars;

use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Custom scalar for ISO-8601 date/time strings used by the dummy fixture API.
 */
#[Description( 'An ISO 8601 encoded date/time string used by the dummy API' )]
class DummyDateTime {
	public static function serialize( mixed $value ): string {
		if ( ! $value instanceof \DateTimeInterface ) {
			throw new \InvalidArgumentException( 'DummyDateTime::serialize() expects a DateTimeInterface instance.' );
		}
		return $value->format( \DateTimeInterface::ATOM );
	}

	public static function parse( string $value ): \DateTimeImmutable {
		// Reject anything that is not a strict ATOM-formatted string. PHP's
		// free-form date parser would otherwise accept inputs like
		// '2024-06-15 08:30:00' which the scalar's contract disallows.
		$date = \DateTimeImmutable::createFromFormat( \DateTimeInterface::ATOM, $value );
		if ( false === $date || $date->format( \DateTimeInterface::ATOM ) !== $value ) {
			throw new \InvalidArgumentException( 'Invalid ISO 8601 date/time.' );
		}
		return $date;
	}
}
