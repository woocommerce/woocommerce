<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\QueryDepthRule;
use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\DocumentValidator;
use WC_Unit_Test_Case;

/**
 * Tests for {@see QueryDepthRule}.
 *
 * Depth is counted the way the stock webonyx rule counts it: the nesting
 * level (root = 0) of the deepest field that itself has a selection set.
 * `{ node { leaf } }` has depth 0, `{ node { node { leaf } } }` has depth 1.
 */
class QueryDepthRuleTest extends WC_Unit_Test_Case {
	/**
	 * Build the test schema: type Query { node: Node, leaf: String } type Node { node: Node, leaf: String }.
	 */
	private function build_schema(): Schema {
		$node = new ObjectType(
			array(
				'name'   => 'Node',
				'fields' => static function () use ( &$node ): array {
					return array(
						'node' => $node,
						'leaf' => Type::string(),
					);
				},
			)
		);

		$query = new ObjectType(
			array(
				'name'   => 'Query',
				'fields' => array(
					'node' => $node,
					'leaf' => Type::string(),
				),
			)
		);

		return new Schema( array( 'query' => $query ) );
	}

	/**
	 * Validate a document with the stock rules plus a QueryDepthRule.
	 *
	 * @param string          $query          The GraphQL document.
	 * @param int             $max_depth      The depth limit.
	 * @param bool            $only_this_rule When true, validate with the depth rule alone (no stock rules).
	 * @param ?QueryDepthRule $rule           A pre-built (e.g. instrumented) rule instance to use instead of a fresh one.
	 * @return Error[] The validation errors.
	 */
	private function validate( string $query, int $max_depth, bool $only_this_rule = false, ?QueryDepthRule $rule = null ): array {
		$sut = $rule ?? new QueryDepthRule( $max_depth );

		$rules   = $only_this_rule ? array() : array_values( DocumentValidator::allRules() );
		$rules[] = $sut;

		return DocumentValidator::validate( $this->build_schema(), Parser::parse( $query ), $rules );
	}

	/**
	 * Assert that a document is exactly at the given depth: accepted with that
	 * limit, rejected with one less.
	 *
	 * @param int    $expected_depth The expected depth (must be >= 2, since a limit of 0 disables the rule).
	 * @param string $query          The GraphQL document.
	 */
	private function assert_depth( int $expected_depth, string $query ): void {
		$this->assertSame( array(), $this->validate( $query, $expected_depth ), "Expected depth {$expected_depth} to be accepted." );

		$errors = $this->validate( $query, $expected_depth - 1 );
		$this->assertCount( 1, $errors, 'Expected depth ' . ( $expected_depth - 1 ) . ' to be rejected.' );
		$this->assertSame( 'Maximum query depth exceeded.', $errors[0]->getMessage() );
	}

	/**
	 * @testdox Duplicate fragment spreads are walked once per fragment, so a document whose fragments spread each other twice is validated in linear work.
	 */
	public function test_duplicate_fragment_spreads_are_walked_once_per_fragment(): void {
		// Each fragment spreads the next one twice.
		$fragment_count = 40;
		$query          = "query Q { ...F0 }\n";
		for ( $i = 0; $i < $fragment_count - 1; $i++ ) {
			$next   = $i + 1;
			$query .= "fragment F{$i} on Query { ...F{$next} ...F{$next} }\n";
		}
		$query .= 'fragment F' . ( $fragment_count - 1 ) . " on Query { leaf }\n";

		$sut = new class( 15 ) extends QueryDepthRule {
			/**
			 * Number of selection trees walked.
			 *
			 * @var int
			 */
			public int $trees_walked = 0;

			/**
			 * Count the call, then walk as usual.
			 *
			 * @param Node $node      The node whose selection set is walked.
			 * @param int  $depth     The depth the node sits at.
			 * @param int  $max_depth The maximum depth seen so far.
			 */
			protected function fieldDepth( Node $node, int $depth = 0, int $max_depth = 0 ): int {
				++$this->trees_walked;
				return parent::fieldDepth( $node, $depth, $max_depth );
			}
		};

		$errors = $this->validate( $query, 15, false, $sut );

		// The operation plus each fragment exactly once, rather than once per spread.
		$this->assertSame( $fragment_count + 1, $sut->trees_walked );
		$this->assertSame( array(), $errors );
	}

	/**
	 * @testdox A fragment's depth is counted relative to the position it is spread at.
	 */
	public function test_fragment_depth_is_relative_to_spread_position(): void {
		$fragment = ' fragment F on Node { node { leaf } }';

		$this->assert_depth( 2, '{ node { node { ...F } } }' . $fragment );
		$this->assert_depth( 3, '{ node { node { node { ...F } } } }' . $fragment );
	}

	/**
	 * @testdox The same fragment spread at two different depths counts at the deeper one.
	 */
	public function test_same_fragment_at_different_depths_counts_the_deepest(): void {
		$query = '{ shallow: node { ...F } deep: node { node { ...F } } } fragment F on Node { node { leaf } }';

		$this->assert_depth( 2, $query );
	}

	/**
	 * @testdox A fragment with no nested selections adds no depth wherever it is spread.
	 */
	public function test_fragment_without_nested_fields_adds_no_depth(): void {
		$query = '{ node { node { node { ...F } } } } fragment F on Node { leaf }';

		$this->assert_depth( 2, $query );
	}

	/**
	 * @testdox Nested fragment spreads compose their relative depths.
	 */
	public function test_nested_fragment_spreads_compose(): void {
		$query = '{ node { ...F } } fragment F on Node { node { ...G } } fragment G on Node { node { node { leaf } } }';

		$this->assert_depth( 3, $query );
	}

	/**
	 * @testdox A fragment cycle terminates and is reported as exceeding the limit, even without the NoFragmentCycles rule.
	 */
	public function test_fragment_cycle_terminates(): void {
		$query = '{ ...A } fragment A on Query { node { ...B } } fragment B on Query { node { ...A } }';

		$errors = $this->validate( $query, 100, true );

		$this->assertCount( 1, $errors );
		$this->assertSame( 'Maximum query depth exceeded.', $errors[0]->getMessage() );
	}
}
