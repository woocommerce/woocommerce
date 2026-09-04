<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\QueryComplexityRule;
use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\CustomScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\DocumentValidator;
use WC_Unit_Test_Case;

/**
 * Tests for {@see QueryComplexityRule}.
 *
 * The rule is exercised through DocumentValidator against a small hand-built
 * schema, the same way GraphQLControllerBase wires it (stock rules plus ours).
 */
class QueryComplexityRuleTest extends WC_Unit_Test_Case {
	/**
	 * Number of times the Counted scalar's parseValue callback ran.
	 *
	 * @var int
	 */
	private int $parse_value_calls = 0;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->parse_value_calls = 0;
	}

	/**
	 * Build the test schema.
	 *
	 * type Query { a: String, b: String, c: String, item: Item, items(first: Int): [Item], counted(values: [Counted!]): String }
	 * type Item { id: Int, name: String }
	 * scalar Counted (parseValue is instrumented)
	 *
	 * `items` carries a complexity callback multiplying the children's score
	 * by `first`, like WooCommerce's connection fields do.
	 */
	private function build_schema(): Schema {
		$counted = new CustomScalarType(
			array(
				'name'         => 'Counted',
				'serialize'    => static fn( $value ) => $value,
				'parseValue'   => function ( $value ) {
					++$this->parse_value_calls;
					return $value;
				},
				'parseLiteral' => static fn( $node ) => $node->value ?? null,
			)
		);

		$item = new ObjectType(
			array(
				'name'   => 'Item',
				'fields' => array(
					'id'   => Type::int(),
					'name' => Type::string(),
				),
			)
		);

		$query = new ObjectType(
			array(
				'name'   => 'Query',
				'fields' => array(
					'a'       => Type::string(),
					'b'       => Type::string(),
					'c'       => Type::string(),
					'item'    => $item,
					'items'   => array(
						'type'       => Type::listOf( $item ),
						'args'       => array( 'first' => Type::int() ),
						'complexity' => static fn( int $children, array $args ): int => ( $args['first'] ?? 1 ) * ( $children + 1 ),
					),
					'counted' => array(
						'type' => Type::string(),
						'args' => array( 'values' => Type::listOf( Type::nonNull( $counted ) ) ),
					),
				),
			)
		);

		return new Schema( array( 'query' => $query ) );
	}

	/**
	 * Validate a document with the stock rules plus a QueryComplexityRule.
	 *
	 * @param string               $query          The GraphQL document.
	 * @param int                  $max_complexity The complexity limit.
	 * @param array                $variables      Raw variable values, as sent by the client.
	 * @param bool                 $only_this_rule When true, validate with the complexity rule alone (no stock rules).
	 * @param ?QueryComplexityRule $rule           A pre-built (e.g. instrumented) rule instance to use instead of a fresh one.
	 * @return array{0: Error[], 1: QueryComplexityRule} The validation errors and the rule instance.
	 */
	private function validate( string $query, int $max_complexity, array $variables = array(), bool $only_this_rule = false, ?QueryComplexityRule $rule = null ): array {
		$sut = $rule ?? new QueryComplexityRule( $max_complexity );
		$sut->setRawVariableValues( $variables );

		$rules   = $only_this_rule ? array() : array_values( DocumentValidator::allRules() );
		$rules[] = $sut;

		$errors = DocumentValidator::validate( $this->build_schema(), Parser::parse( $query ), $rules );

		return array( $errors, $sut );
	}

	/**
	 * Build a document in which each named fragment spreads the next one twice,
	 * so the number of spreads reachable from the root doubles with every fragment.
	 *
	 * @param int    $fragment_count Number of chained fragments.
	 * @param string $leaf           Selection set body of the last fragment.
	 */
	private function build_duplicate_spread_chain( int $fragment_count, string $leaf = 'a' ): string {
		$document = "query Q { ...F0 }\n";
		for ( $i = 0; $i < $fragment_count - 1; $i++ ) {
			$next      = $i + 1;
			$document .= "fragment F{$i} on Query { ...F{$next} ...F{$next} }\n";
		}
		$last = $fragment_count - 1;

		return $document . "fragment F{$last} on Query { {$leaf} }\n";
	}

	/**
	 * @testdox Duplicate fragment spreads are scored once per fragment, so a document whose fragments spread each other twice is scored in linear work.
	 */
	public function test_duplicate_fragment_spreads_are_scored_once_per_fragment(): void {
		$fragment_count = 40;
		$query          = $this->build_duplicate_spread_chain( $fragment_count );

		$sut = new class( 1000 ) extends QueryComplexityRule {
			/**
			 * Number of selection sets scored.
			 *
			 * @var int
			 */
			public int $selection_sets_scored = 0;

			/**
			 * Count the call, then score as usual.
			 *
			 * @param SelectionSetNode $selection_set The selection set to score.
			 */
			protected function fieldComplexity( SelectionSetNode $selection_set ): int {
				++$this->selection_sets_scored;
				return parent::fieldComplexity( $selection_set );
			}
		};

		list( $errors ) = $this->validate( $query, 1000, array(), false, $sut );

		// The operation's selection set plus each fragment's exactly once, rather than once per spread.
		$this->assertSame( $fragment_count + 1, $sut->selection_sets_scored );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'Maximum query complexity exceeded.', $errors[0]->getMessage() );
		// Memoization must not change the score: the leaf still counts once per spread.
		$this->assertSame( 2 ** 39, $sut->getQueryComplexity() );
	}

	/**
	 * @testdox Fragment spreads are scored exactly as if the fragment had been written inline.
	 */
	public function test_fragment_spread_scores_match_inline_expansion(): void {
		$with_fragments = '{ ...F0 } fragment F0 on Query { ...F1 ...F1 } fragment F1 on Query { a b }';
		$inline         = '{ a b a b }';

		list( $errors, $sut ) = $this->validate( $with_fragments, 4 );
		$this->assertSame( array(), $errors );
		$this->assertSame( 4, $sut->getQueryComplexity() );

		list( , $inline_sut ) = $this->validate( $inline, 4 );
		$this->assertSame( $inline_sut->getQueryComplexity(), $sut->getQueryComplexity() );

		list( $errors ) = $this->validate( $with_fragments, 3 );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'Maximum query complexity exceeded.', $errors[0]->getMessage() );
	}

	/**
	 * @testdox The computed score saturates at COMPLEXITY_CEILING instead of overflowing PHP's int.
	 */
	public function test_score_saturates_instead_of_overflowing(): void {
		// 2^69 would overflow a 64-bit int (and surface as a TypeError).
		$query = $this->build_duplicate_spread_chain( 70 );

		list( $errors, $sut ) = $this->validate( $query, 1000 );

		$this->assertCount( 1, $errors );
		$this->assertSame( 'Maximum query complexity exceeded.', $errors[0]->getMessage() );
		$this->assertSame( QueryComplexityRule::COMPLEXITY_CEILING, $sut->getQueryComplexity() );
	}

	/**
	 * @testdox A fragment cycle terminates and is scored as exceeding the limit, even without the NoFragmentCycles rule.
	 */
	public function test_fragment_cycle_terminates(): void {
		$query = '{ ...A } fragment A on Query { a ...B } fragment B on Query { b ...A }';

		list( $errors ) = $this->validate( $query, 1000, array(), true );

		$this->assertCount( 1, $errors );
		$this->assertSame( 'Maximum query complexity exceeded.', $errors[0]->getMessage() );
	}

	/**
	 * @testdox Variable values are coerced once per document, not once per @include/@skip directive.
	 */
	public function test_variables_are_coerced_once_per_document(): void {
		$query = 'query Q($values: [Counted!], $flag: Boolean!) {
			a @include(if: $flag)
			b @include(if: $flag)
			c @skip(if: $flag)
			counted(values: $values)
		}';

		list( $errors, $sut ) = $this->validate(
			$query,
			1000,
			array(
				'values' => array( 1, 2, 3 ),
				'flag'   => true,
			)
		);

		$this->assertSame( array(), $errors );
		// a, b, counted (c is skipped).
		$this->assertSame( 3, $sut->getQueryComplexity() );
		// One parseValue call per list element, regardless of how many directives the document carries.
		$this->assertSame( 3, $this->parse_value_calls );
	}

	/**
	 * @testdox Fields excluded by @include / @skip (literal or variable-driven) don't count, including when both directives are present.
	 */
	public function test_include_and_skip_directives_exclude_fields(): void {
		list( $errors, $sut ) = $this->validate( '{ a @include(if: false) b @skip(if: true) c @include(if: true) @skip(if: true) item { id } }', 1000 );
		$this->assertSame( array(), $errors );
		$this->assertSame( 2, $sut->getQueryComplexity() );

		list( $errors, $sut ) = $this->validate(
			'query Q($show: Boolean!) { a @include(if: $show) b @skip(if: $show) }',
			1000,
			array( 'show' => false )
		);
		$this->assertSame( array(), $errors );
		$this->assertSame( 1, $sut->getQueryComplexity() );
	}

	/**
	 * @testdox Complexity callbacks receive the coerced field arguments, for fields both in operations and inside fragments.
	 */
	public function test_complexity_callback_receives_arguments(): void {
		list( $errors, $sut ) = $this->validate( 'query Q($n: Int) { items(first: $n) { id name } }', 1000, array( 'n' => 10 ) );
		$this->assertSame( array(), $errors );
		// 10 * (2 children + 1).
		$this->assertSame( 30, $sut->getQueryComplexity() );

		list( $errors, $sut ) = $this->validate( '{ ...F } fragment F on Query { items(first: 5) { id } }', 1000 );
		$this->assertSame( array(), $errors );
		$this->assertSame( 10, $sut->getQueryComplexity() );

		list( $errors ) = $this->validate( '{ items(first: 100) { id name } }', 100 );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'Maximum query complexity exceeded.', $errors[0]->getMessage() );
	}

	/**
	 * @testdox Missing required variables surface as a coercion error rather than as a crash.
	 */
	public function test_missing_required_variable_is_reported(): void {
		$this->expectException( Error::class );
		$this->expectExceptionMessageMatches( '/\$flag/' );

		$this->validate( 'query Q($flag: Boolean!) { a @include(if: $flag) }', 1000, array() );
	}
}
