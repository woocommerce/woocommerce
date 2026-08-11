<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Api\Infrastructure\BoundedOverlappingFieldsCanBeMerged;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\DocumentValidator;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\OverlappingFieldsCanBeMerged;
use WC_Unit_Test_Case;

/**
 * Tests for {@see BoundedOverlappingFieldsCanBeMerged} and for the parser
 * recursion limit that arrived with the same vendored graphql-php update.
 *
 * Both guard security properties the vendored library only half-provides: the
 * upstream comparison cap invalidates the query but keeps allocating a conflict
 * per remaining field pair, and the parser depth limit has to stay switched on
 * by default for deeply nested documents to be rejected before they recurse.
 */
class BoundedOverlappingFieldsCanBeMergedTest extends WC_Unit_Test_Case {
	/**
	 * A schema whose single field takes enough arguments to build many
	 * structurally distinct — but semantically equal — copies of one field.
	 *
	 * @var Schema
	 */
	private Schema $schema;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->schema = new Schema(
			array(
				'query' => new ObjectType(
					array(
						'name'   => 'Query',
						'fields' => array(
							'thing' => array(
								'type' => Type::string(),
								'args' => array(
									'a' => Type::int(),
									'b' => Type::int(),
									'c' => Type::int(),
									'd' => Type::int(),
								),
							),
						),
					)
				),
			)
		);
	}

	/**
	 * @testdox The stock rule keeps allocating a conflict for every field pair past its comparison cap.
	 */
	public function test_stock_rule_allocates_a_conflict_per_pair_past_the_cap(): void {
		$errors = $this->validate( new OverlappingFieldsCanBeMerged( 5 ), 20 );

		// 20 fields under one response name is 190 pairs; everything past the
		// 5th comparison is reported, which is what exhausts memory at scale.
		$this->assertGreaterThan(
			100,
			count( $errors ),
			'The stock rule is expected to report a conflict for every pair past the cap. If this no longer holds, upstream fixed the allocation and BoundedOverlappingFieldsCanBeMerged can be dropped.'
		);
	}

	/**
	 * @testdox The bounded rule reports the cap once and stops comparing.
	 */
	public function test_bounded_rule_reports_the_cap_once(): void {
		$errors = $this->validate( new BoundedOverlappingFieldsCanBeMerged( 5 ), 20 );

		$this->assertCount( 1, $errors );
		$this->assertStringContainsString( 'Too many field comparisons', $errors[0]->getMessage() );
	}

	/**
	 * @testdox The bounded rule leaves queries below the cap untouched.
	 */
	public function test_bounded_rule_accepts_queries_below_the_cap(): void {
		$this->assertSame( array(), $this->validate( new BoundedOverlappingFieldsCanBeMerged(), 20 ) );
	}

	/**
	 * @testdox The bounded rule still reports genuine field conflicts.
	 */
	public function test_bounded_rule_still_reports_real_conflicts(): void {
		$document = Parser::parse( '{ f: thing(a: 1) f: thing(a: 2) }' );
		$errors   = DocumentValidator::validate( $this->schema, $document, array( new BoundedOverlappingFieldsCanBeMerged() ) );

		$this->assertCount( 1, $errors );
		$this->assertStringContainsString( 'they have differing arguments', $errors[0]->getMessage() );
	}

	/**
	 * @testdox The vendored parser rejects documents nested past its default recursion limit.
	 */
	public function test_parser_enforces_the_default_recursion_limit(): void {
		$depth = Parser::DEFAULT_RECURSION_LIMIT + 1;
		$query = str_repeat( '{ thing ', $depth ) . str_repeat( '}', $depth );

		$this->expectException( SyntaxError::class );
		$this->expectExceptionMessage( 'Recursion depth limit of ' . Parser::DEFAULT_RECURSION_LIMIT . ' exceeded' );

		Parser::parse( $query );
	}

	/**
	 * Validate a query of $field_count copies of the same field, written with the
	 * arguments in a different order each time.
	 *
	 * Distinct argument order means distinct fingerprints, so the rule's
	 * deduplication does not collapse them, but the fields are semantically
	 * equal, so nothing but the comparison cap can produce an error.
	 *
	 * @param OverlappingFieldsCanBeMerged $rule        The rule instance to validate with.
	 * @param int                          $field_count How many copies of the field to emit.
	 * @return array The validation errors.
	 */
	private function validate( OverlappingFieldsCanBeMerged $rule, int $field_count ): array {
		$orders = array(
			array( 'a', 'b', 'c', 'd' ),
			array( 'a', 'b', 'd', 'c' ),
			array( 'a', 'c', 'b', 'd' ),
			array( 'a', 'c', 'd', 'b' ),
			array( 'a', 'd', 'b', 'c' ),
			array( 'a', 'd', 'c', 'b' ),
			array( 'b', 'a', 'c', 'd' ),
			array( 'b', 'a', 'd', 'c' ),
			array( 'b', 'c', 'a', 'd' ),
			array( 'b', 'c', 'd', 'a' ),
			array( 'b', 'd', 'a', 'c' ),
			array( 'b', 'd', 'c', 'a' ),
			array( 'c', 'a', 'b', 'd' ),
			array( 'c', 'a', 'd', 'b' ),
			array( 'c', 'b', 'a', 'd' ),
			array( 'c', 'b', 'd', 'a' ),
			array( 'c', 'd', 'a', 'b' ),
			array( 'c', 'd', 'b', 'a' ),
			array( 'd', 'a', 'b', 'c' ),
			array( 'd', 'a', 'c', 'b' ),
		);
		$this->assertGreaterThanOrEqual( $field_count, count( $orders ), 'Not enough distinct argument orders for the requested field count.' );

		$fields = array();
		foreach ( array_slice( $orders, 0, $field_count ) as $order ) {
			$arguments = array();
			foreach ( $order as $argument_name ) {
				$arguments[] = $argument_name . ': 1';
			}
			$fields[] = 'f: thing(' . implode( ', ', $arguments ) . ')';
		}

		return DocumentValidator::validate(
			$this->schema,
			Parser::parse( '{ ' . implode( ' ', $fields ) . ' }' ),
			array( $rule )
		);
	}
}
