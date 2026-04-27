<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ArgumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ResolveInfo;

/**
 * Extracts a unified query info tree from a GraphQL ResolveInfo.
 *
 * The resulting array captures the full query structure: fields, arguments,
 * sub-selections, inline fragments, and named fragment spreads.
 *
 * Structure rules:
 * - Leaf field (no args, no sub-selection) => true
 * - Field with sub-selections => nested associative array
 * - Field arguments => '__args' reserved key
 * - Inline fragments => '...TypeName' prefix key
 * - Named fragment spreads => expanded inline (merged into the parent as
 *   siblings of the other selections), matching how GraphQL evaluates them
 * - Top-level query args included via '__args'
 */
class QueryInfoExtractor {
	/**
	 * Extract query info from a resolver's ResolveInfo and top-level args.
	 *
	 * @param ResolveInfo $info The GraphQL resolve info.
	 * @param array       $args The top-level query arguments.
	 * @return array The unified query info tree.
	 */
	public static function extract_from_info( ResolveInfo $info, array $args ): array {
		$result = self::extract( $info->fieldNodes[0]->selectionSet ?? null, $info->variableValues, $info->fragments );
		if ( ! empty( $args ) ) {
			$result['__args'] = $args;
		}
		return $result;
	}

	/**
	 * Recursively extract query info from a selection set.
	 *
	 * @param ?SelectionSetNode                     $selection_set   The selection set to process.
	 * @param array                                 $variable_values Variable values for resolving arguments.
	 * @param array<string, FragmentDefinitionNode> $fragments       Named fragment definitions from the document.
	 * @return array The query info tree for the selection set.
	 */
	public static function extract( ?SelectionSetNode $selection_set, array $variable_values, array $fragments = array() ): array {
		if ( null === $selection_set ) {
			return array();
		}

		$result = array();

		foreach ( $selection_set->selections as $selection ) {
			if ( $selection instanceof FieldNode ) {
				$field_name            = $selection->name->value;
				$result[ $field_name ] = self::build_field_entry( $selection, $variable_values, $fragments );
			} elseif ( $selection instanceof InlineFragmentNode ) {
				$type_name      = $selection->typeCondition->name->value;
				$key            = '...' . $type_name;
				$result[ $key ] = self::extract( $selection->selectionSet, $variable_values, $fragments );
			} elseif ( $selection instanceof FragmentSpreadNode ) {
				// Expand named fragment spreads inline: their fields become
				// siblings of the other selections, matching how GraphQL
				// evaluates them. Consumers of _query_info (mappers that
				// check array_key_exists for specific fields) see them the
				// same as if the fragment had been written inline. Use a
				// recursive merge so overlapping selections are unioned
				// rather than replaced — `array_merge` would drop the
				// existing sub-selection under the same field name.
				$fragment = $fragments[ $selection->name->value ] ?? null;
				if ( null === $fragment ) {
					continue;
				}
				$spread = self::extract( $fragment->selectionSet, $variable_values, $fragments );
				$result = self::merge_selections( $result, $spread );
			}
		}

		return $result;
	}

	/**
	 * Build the entry for a single field node.
	 *
	 * @param FieldNode                             $field           The field node.
	 * @param array                                 $variable_values Variable values for resolving arguments.
	 * @param array<string, FragmentDefinitionNode> $fragments       Named fragment definitions from the document.
	 * @return array|bool True for leaf fields, associative array otherwise.
	 */
	private static function build_field_entry( FieldNode $field, array $variable_values, array $fragments ): array|bool {
		$has_args          = ! empty( $field->arguments ) && count( $field->arguments ) > 0;
		$has_sub_selection = null !== $field->selectionSet;

		if ( ! $has_args && ! $has_sub_selection ) {
			return true;
		}

		$entry = array();

		if ( $has_args ) {
			$args = array();
			foreach ( $field->arguments as $arg ) {
				$args[ $arg->name->value ] = self::resolve_argument_value( $arg, $variable_values );
			}
			$entry['__args'] = $args;
		}

		if ( $has_sub_selection ) {
			$sub   = self::extract( $field->selectionSet, $variable_values, $fragments );
			$entry = self::merge_selections( $entry, $sub );
		}

		return $entry;
	}

	/**
	 * Recursively merge two selection trees produced by extract()/build_field_entry().
	 *
	 * Used wherever selections from different sources are combined under
	 * the same key (notably: named fragment spreads expanded inline). Matches
	 * GraphQL's selection-set merge semantics — overlapping fields have their
	 * sub-selections unioned rather than one replacing the other, which a
	 * shallow `array_merge` would do.
	 *
	 * Rules:
	 * - Key only in one side: kept verbatim.
	 * - Both sides arrays: recurse, unioning children.
	 * - One array, one `true` (leaf): keep the array — it carries the
	 *   sub-selection detail, and its presence already implies the field
	 *   was requested.
	 * - Both `true`: keep `true`.
	 * - `__args` collisions (same field with different argument values):
	 *   the second operand wins. Conflicting field args are a GraphQL
	 *   validation error upstream of us, so this path is defensive.
	 *
	 * @param array $a First selection tree.
	 * @param array $b Second selection tree, merged into $a.
	 * @return array The merged tree.
	 */
	private static function merge_selections( array $a, array $b ): array {
		foreach ( $b as $key => $value ) {
			if ( ! array_key_exists( $key, $a ) ) {
				$a[ $key ] = $value;
				continue;
			}
			$existing = $a[ $key ];
			if ( is_array( $existing ) && is_array( $value ) ) {
				$a[ $key ] = self::merge_selections( $existing, $value );
			} elseif ( is_array( $value ) ) {
				// One side is `true`, the other is a sub-selection array — keep the array.
				$a[ $key ] = $value;
			}
			// Both true, or existing-array + new-true: keep existing.
		}
		return $a;
	}

	/**
	 * Resolve the value of a single argument node, handling variables.
	 *
	 * @param ArgumentNode $arg             The argument node.
	 * @param array        $variable_values Variable values.
	 * @return mixed The resolved argument value.
	 */
	private static function resolve_argument_value( ArgumentNode $arg, array $variable_values ): mixed {
		$value_node = $arg->value;

		if ( $value_node instanceof \Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableNode ) {
			return $variable_values[ $value_node->name->value ] ?? null;
		}

		return \Automattic\WooCommerce\Vendor\GraphQL\Utils\AST::valueFromASTUntyped( $value_node, $variable_values );
	}
}
