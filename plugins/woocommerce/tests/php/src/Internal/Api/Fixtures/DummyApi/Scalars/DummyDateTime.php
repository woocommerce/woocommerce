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
		if ( $value instanceof \DateTimeInterface ) {
			return $value->format( \DateTimeInterface::ATOM );
		}
		return (string) $value;
	}

	public static function parse( string $value ): \DateTimeImmutable {
		try {
			return new \DateTimeImmutable( $value );
		} catch ( \Exception $e ) {
			throw new \InvalidArgumentException( 'Invalid ISO 8601 date/time: ' . $e->getMessage(), 0, $e );
		}
	}
}
