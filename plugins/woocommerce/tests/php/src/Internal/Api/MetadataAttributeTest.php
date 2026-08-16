<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Api\Attributes\Internal;
use Automattic\WooCommerce\Api\Attributes\Metadata;
use WC_Unit_Test_Case;

/**
 * Unit tests for the {@see Metadata} base attribute and the {@see Internal}
 * convenience subclass. ApiBuilder identifies metadata-bearing attributes via
 * `instanceof Metadata`, so the contract these tests pin is the
 * `get_name()` / `get_value()` pair on the base class.
 */
class MetadataAttributeTest extends WC_Unit_Test_Case {
	/**
	 * @return array<string, array{bool|int|float|string|null}>
	 */
	public function provider_scalar_values(): array {
		return array(
			'bool true'  => array( true ),
			'bool false' => array( false ),
			'int'        => array( 42 ),
			'float'      => array( 3.14 ),
			'string'     => array( 'core-team' ),
			'null'       => array( null ),
		);
	}

	/**
	 * @testdox Metadata round-trips name and value for every supported scalar type.
	 *
	 * @dataProvider provider_scalar_values
	 * @param bool|int|float|string|null $value Value to round-trip.
	 */
	public function test_round_trip_for_scalar_values( bool|int|float|string|null $value ): void {
		$metadata = new Metadata( 'sample', $value );

		$this->assertSame( 'sample', $metadata->get_name() );
		$this->assertSame( $value, $metadata->get_value() );
	}

	/**
	 * @testdox Internal subclass produces a Metadata entry named "internal" with value true.
	 */
	public function test_internal_subclass_carries_internal_true(): void {
		$internal = new Internal();

		$this->assertInstanceOf( Metadata::class, $internal );
		$this->assertSame( 'internal', $internal->get_name() );
		$this->assertTrue( $internal->get_value() );
	}

	/**
	 * @testdox Metadata is repeatable so multiple distinct names can decorate one element.
	 */
	public function test_metadata_attribute_is_repeatable(): void {
		$reflection = new \ReflectionClass( Metadata::class );
		$attributes = $reflection->getAttributes( \Attribute::class );

		$this->assertNotEmpty( $attributes, 'Metadata should be decorated with #[Attribute].' );

		$attribute = $attributes[0]->newInstance();
		$this->assertNotSame( 0, $attribute->flags & \Attribute::IS_REPEATABLE );
	}

	/**
	 * @testdox Metadata::shows_in_metadata_query() defaults to true so existing entries surface through `_apiMetadata`.
	 */
	public function test_shows_in_metadata_query_defaults_to_true(): void {
		$metadata = new Metadata( 'sample', 'value' );
		$this->assertTrue( $metadata->shows_in_metadata_query() );
	}

	/**
	 * @testdox A Metadata subclass can override shows_in_metadata_query() to opt out of `_apiMetadata` exposure.
	 */
	public function test_shows_in_metadata_query_can_be_overridden_to_false(): void {
		$hidden = new class('hidden', 'value') extends Metadata {
			/**
			 * Opt the carrying target out of the `_apiMetadata` query.
			 */
			public function shows_in_metadata_query(): bool {
				return false;
			}
		};

		$this->assertFalse( $hidden->shows_in_metadata_query() );
	}
}
