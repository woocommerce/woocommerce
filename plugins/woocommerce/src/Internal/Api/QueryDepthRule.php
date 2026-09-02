<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryDepth;

/**
 * QueryDepth validation rule that returns a generic error message when the
 * depth is exceeded. Admins can still read both values via debug mode; see
 * {@see GraphQLController} step 8.
 *
 * Unlike the stock webonyx rule, which walks a named fragment again on every
 * spread, each fragment's depth is computed once, relative to the position it
 * is spread at, and reused.
 */
class QueryDepthRule extends QueryDepth {
	/**
	 * Sentinel for a selection tree with no nested selection sets, which adds
	 * no depth wherever it is spread. The stock walk only ever raises the
	 * running maximum, so seeding it with -1 makes the same walk report either
	 * the relative depth (>= 0) or this sentinel.
	 */
	private const NO_NESTED_FIELDS = -1;

	/**
	 * Memoized relative depth of each named fragment, keyed by fragment name.
	 *
	 * @var array<string, int>
	 */
	private array $fragment_depths = array();

	/**
	 * Reset the per-document memoization before delegating to the stock visitor.
	 *
	 * @param QueryValidationContext $context The validation context.
	 * @return array The visitor definition.
	 */
	public function getVisitor( QueryValidationContext $context ): array {
		$this->fragment_depths = array();

		return parent::getVisitor( $context );
	}

	/**
	 * Compute the depth reached below a selection. Named fragment spreads use
	 * the fragment's relative depth, computed once; everything else is
	 * delegated to the stock rule.
	 *
	 * @param Node $node      The selection node.
	 * @param int  $depth     The depth the selection sits at.
	 * @param int  $max_depth The maximum depth seen so far.
	 * @return int The updated maximum depth.
	 */
	protected function nodeDepth( Node $node, int $depth = 0, int $max_depth = 0 ): int {
		if ( ! $node instanceof FragmentSpreadNode ) {
			return parent::nodeDepth( $node, $depth, $max_depth );
		}

		$fragment = $this->getFragment( $node );
		if ( is_null( $fragment ) ) {
			return $max_depth;
		}

		$name = $fragment->name->value;
		if ( ! array_key_exists( $name, $this->fragment_depths ) ) {
			// Same cycle guard as the stock rule: a fragment that (transitively)
			// spreads itself is reported as exceeding the limit.
			if ( isset( $this->calculatedFragments[ $name ] ) ) {
				return $this->maxQueryDepth + 1;
			}

			$this->calculatedFragments[ $name ] = true;
			try {
				$this->fragment_depths[ $name ] = $this->fieldDepth( $fragment, 0, self::NO_NESTED_FIELDS );
			} finally {
				unset( $this->calculatedFragments[ $name ] );
			}
		}

		$relative_depth = $this->fragment_depths[ $name ];

		return self::NO_NESTED_FIELDS === $relative_depth
			? $max_depth
			: max( $max_depth, $depth + $relative_depth );
	}

	/**
	 * Override webonyx's default ("Max query depth should be {max} but
	 * got {count}.").
	 *
	 * @param int $max   The configured maximum depth (unused).
	 * @param int $count The computed query depth (unused).
	 */
	public static function maxQueryDepthErrorMessage( int $max, int $count ): string {
		return 'Maximum query depth exceeded.';
	}
}
