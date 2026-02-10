<?php
/**
 * RestAbilityFactoryTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\REST;

use Automattic\WooCommerce\Internal\Abilities\REST\RestAbilityFactory;

/**
 * Tests for the RestAbilityFactory class.
 *
 * Focuses on schema sanitization logic in sanitize_args_to_schema().
 */
class RestAbilityFactoryTest extends \WC_Unit_Test_Case {

	/**
	 * Helper to invoke the private sanitize_args_to_schema method.
	 *
	 * @param array $args WordPress REST API arguments array.
	 * @return array Sanitized JSON Schema.
	 */
	private function invoke_sanitize_args_to_schema( array $args ): array {
		$reflection = new \ReflectionClass( RestAbilityFactory::class );
		$method     = $reflection->getMethod( 'sanitize_args_to_schema' );
		$method->setAccessible( true );

		return $method->invoke( null, $args );
	}

	/**
	 * Test that date-time type is converted to string type with date-time format.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/62764
	 */
	public function test_converts_date_time_type_to_string_with_format(): void {
		$args = array(
			'date_created' => array(
				'type'        => 'date-time',
				'description' => 'The date the resource was created.',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertArrayHasKey( 'date_created', $schema['properties'] );
		$this->assertSame( 'string', $schema['properties']['date_created']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['date_created']['format'] );
	}

	/**
	 * Test that date-time conversion does not overwrite an existing format.
	 */
	public function test_date_time_conversion_preserves_explicit_format(): void {
		$args = array(
			'date_field' => array(
				'type'   => 'date-time',
				'format' => 'date-time',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( 'string', $schema['properties']['date_field']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['date_field']['format'] );
	}

	/**
	 * Test that non-date-time types are passed through unchanged.
	 */
	public function test_preserves_standard_types(): void {
		$args = array(
			'name'   => array( 'type' => 'string' ),
			'count'  => array( 'type' => 'integer' ),
			'price'  => array( 'type' => 'number' ),
			'active' => array( 'type' => 'boolean' ),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( 'string', $schema['properties']['name']['type'] );
		$this->assertSame( 'integer', $schema['properties']['count']['type'] );
		$this->assertSame( 'number', $schema['properties']['price']['type'] );
		$this->assertSame( 'boolean', $schema['properties']['active']['type'] );
	}

	/**
	 * Test that duplicate enum values are removed.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/62034
	 */
	public function test_deduplicates_enum_values(): void {
		$args = array(
			'orderby' => array(
				'type' => 'string',
				'enum' => array( 'date', 'id', 'title', 'price', 'popularity', 'rating', 'price', 'popularity', 'rating' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$enum = $schema['properties']['orderby']['enum'];

		$this->assertSame( array_values( array_unique( $enum ) ), $enum, 'Enum should not contain duplicate values' );
		$this->assertCount( 6, $enum );
	}

	/**
	 * Test that enum values are re-indexed after deduplication.
	 */
	public function test_enum_values_are_reindexed(): void {
		$args = array(
			'status' => array(
				'type' => 'string',
				'enum' => array( 'draft', 'published', 'draft' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$enum = $schema['properties']['status']['enum'];

		// Keys should be sequential (0, 1) not (0, 1, 2) with gaps.
		$this->assertSame( array( 'draft', 'published' ), $enum );
	}

	/**
	 * Test that enum without duplicates is unchanged.
	 */
	public function test_enum_without_duplicates_unchanged(): void {
		$args = array(
			'orderby' => array(
				'type' => 'string',
				'enum' => array( 'date', 'id', 'title' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( array( 'date', 'id', 'title' ), $schema['properties']['orderby']['enum'] );
	}

	/**
	 * Test that required fields are collected correctly.
	 */
	public function test_collects_required_fields(): void {
		$args = array(
			'name'  => array(
				'type'     => 'string',
				'required' => true,
			),
			'price' => array(
				'type'     => 'string',
				'required' => true,
			),
			'sku'   => array(
				'type'     => 'string',
				'required' => false,
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertArrayHasKey( 'required', $schema );
		$this->assertContains( 'name', $schema['required'] );
		$this->assertContains( 'price', $schema['required'] );
		$this->assertNotContains( 'sku', $schema['required'] );
	}

	/**
	 * Test that realistic collection params with multiple issues are sanitized correctly.
	 *
	 * Simulates a WooCommerce products-list controller where date-time types
	 * and duplicate enum values co-exist.
	 */
	public function test_sanitizes_realistic_collection_params(): void {
		$args = array(
			'after'    => array(
				'type'        => 'date-time',
				'description' => 'Limit response to resources published after a given date.',
			),
			'before'   => array(
				'type'        => 'date-time',
				'description' => 'Limit response to resources published before a given date.',
			),
			'per_page' => array(
				'type'    => 'integer',
				'default' => 10,
				'minimum' => 1,
				'maximum' => 100,
			),
			'orderby'  => array(
				'type'    => 'string',
				'default' => 'date',
				'enum'    => array( 'date', 'id', 'title', 'slug', 'price', 'popularity', 'rating', 'menu_order', 'price', 'popularity', 'rating' ),
			),
			'status'   => array(
				'type'    => 'string',
				'default' => 'any',
				'enum'    => array( 'any', 'draft', 'pending', 'private', 'publish' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		// Date-time fields should be converted.
		$this->assertSame( 'string', $schema['properties']['after']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['after']['format'] );
		$this->assertSame( 'string', $schema['properties']['before']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['before']['format'] );

		// Standard types should be unchanged.
		$this->assertSame( 'integer', $schema['properties']['per_page']['type'] );
		$this->assertArrayNotHasKey( 'format', $schema['properties']['per_page'] );

		// Enum duplicates should be removed.
		$orderby_enum = $schema['properties']['orderby']['enum'];
		$this->assertCount( count( array_unique( $orderby_enum ) ), $orderby_enum, 'orderby enum should have no duplicates' );
		$this->assertCount( 8, $orderby_enum );

		// Clean enum should be unchanged.
		$this->assertCount( 5, $schema['properties']['status']['enum'] );
	}
}
