<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Infrastructure;

use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\OverlappingFieldsCanBeMerged;

/**
 * The `OverlappingFieldsCanBeMerged` validation rule with a memory-safe comparison cap.
 *
 * Upstream graphql-php 15.32.2 added a field-comparison cap to the rule
 * (GHSA-fc86-6rv6-2jpm) to bound the quadratic cost of comparing many fields
 * that share one response name. Past the cap the upstream rule keeps
 * traversing and returns a freshly allocated conflict for every remaining
 * pair, so the nested loops in `collectConflictsWithin()` and
 * `collectConflictsBetween()` accumulate O(n^2) conflicts and exhaust memory —
 * a harder failure than the CPU cost the cap was meant to bound.
 *
 * This subclass stops comparing once the cap is exceeded. The first over-limit
 * comparison still yields the upstream "Too many field comparisons" conflict,
 * which invalidates the query; every later comparison short-circuits to null,
 * so no further comparison work is done and no further conflicts are
 * allocated. The cap therefore stays effective without the memory blow-up.
 *
 * The comparison limit is not raised: it has to be reachable before memory or
 * request time runs out, and neither `QueryDepth` nor `QueryComplexity` can
 * bound this rule — `DocumentValidator::validate()` runs every rule in a
 * single parallel traversal, and both of those report only once the traversal
 * has finished.
 */
final class BoundedOverlappingFieldsCanBeMerged extends OverlappingFieldsCanBeMerged {
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Signature and properties are inherited from the vendored parent class.

	/**
	 * Determine whether two fields conflict, unless the comparison cap is already exceeded.
	 *
	 * @param QueryValidationContext $context                          Validation context.
	 * @param bool                   $parentFieldsAreMutuallyExclusive Whether the parent fields are known to be mutually exclusive.
	 * @param string                 $responseName                     The response name the two fields share.
	 * @param array                  $field1                           First field, as [parent type, AST node, field definition].
	 * @param array                  $field2                           Second field, as [parent type, AST node, field definition].
	 * @return array|null The conflict, or null when there is none or the cap is already exceeded.
	 */
	protected function findConflict(
		QueryValidationContext $context,
		bool $parentFieldsAreMutuallyExclusive,
		string $responseName,
		array $field1,
		array $field2
	): ?array {
		// The parent increments the counter and returns the "too many field
		// comparisons" conflict on the first call past the cap, so this only ever
		// short-circuits once that error has already been reported.
		if ( $this->comparisonCount > $this->comparisonLimit ) {
			return null;
		}

		return parent::findConflict( $context, $parentFieldsAreMutuallyExclusive, $responseName, $field1, $field2 );
	}

	// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
}
