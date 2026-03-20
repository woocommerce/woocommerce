<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Extracts a unified query info tree from a GraphQL ResolveInfo.
 *
 * The resulting array captures the full query structure: fields, arguments,
 * sub-selections, and inline fragments.
 *
 * Structure rules:
 * - Leaf field (no args, no sub-selection) => true
 * - Field with sub-selections => nested associative array
 * - Field arguments => '__args' reserved key
 * - Inline fragments => '...TypeName' prefix key
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
		$result = self::extract( $info->fieldNodes[0]->selectionSet ?? null, $info->variableValues );
		if ( ! empty( $args ) ) {
			$result['__args'] = $args;
		}
		return $result;
	}

	/**
	 * Recursively extract query info from a selection set.
	 *
	 * @param ?SelectionSetNode $selection_set    The selection set to process.
	 * @param array             $variable_values  Variable values for resolving arguments.
	 * @return array The query info tree for the selection set.
	 */
	public static function extract( ?SelectionSetNode $selection_set, array $variable_values ): array {
		if ( null === $selection_set ) {
			return array();
		}

		$result = array();

		foreach ( $selection_set->selections as $selection ) {
			if ( $selection instanceof FieldNode ) {
				$field_name            = $selection->name->value;
				$result[ $field_name ] = self::build_field_entry( $selection, $variable_values );
			} elseif ( $selection instanceof InlineFragmentNode ) {
				$type_name      = $selection->typeCondition->name->value;
				$key            = '...' . $type_name;
				$result[ $key ] = self::extract( $selection->selectionSet, $variable_values );
			}
		}

		return $result;
	}

	/**
	 * Build the entry for a single field node.
	 *
	 * @param FieldNode $field           The field node.
	 * @param array     $variable_values Variable values for resolving arguments.
	 * @return array|bool True for leaf fields, associative array otherwise.
	 */
	private static function build_field_entry( FieldNode $field, array $variable_values ): array|bool {
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
			$sub   = self::extract( $field->selectionSet, $variable_values );
			$entry = array_merge( $entry, $sub );
		}

		return $entry;
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

		if ( $value_node instanceof \GraphQL\Language\AST\VariableNode ) {
			return $variable_values[ $value_node->name->value ] ?? null;
		}

		return \GraphQL\Utils\AST::valueFromASTUntyped( $value_node, $variable_values );
	}
}
