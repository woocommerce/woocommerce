<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Values;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryComplexity;

/**
 * QueryComplexity validation rule that returns a generic error message when
 * the complexity is exceeded. Admins can still read both values via debug
 * mode; see {@see GraphQLController} step 8.
 *
 * Unlike the stock webonyx rule, the work done stays proportional to the size
 * of the document: each named fragment is scored once and the result reused
 * for every spread, variable values are coerced once instead of once per
 * directive or complexity callback, field definitions come from the visitor's
 * TypeInfo instead of being re-collected for every selection set, and scores
 * saturate at {@see self::COMPLEXITY_CEILING} instead of overflowing.
 */
class QueryComplexityRule extends QueryComplexity {
	/**
	 * Upper bound for computed complexity scores.
	 *
	 * Far above any configurable limit, so real scores stay exact, while leaving
	 * headroom below PHP_INT_MAX for complexity callbacks to multiply a saturated
	 * child score by a page size without overflowing.
	 */
	public const COMPLEXITY_CEILING = PHP_INT_MAX >> 10;

	/**
	 * Memoized complexity of each named fragment, keyed by fragment name.
	 *
	 * @var array<string, int>
	 */
	private array $fragment_complexities = array();

	/**
	 * Names of the fragments whose complexity is currently being computed;
	 * guards against fragment cycles (which the NoFragmentCycles rule reports).
	 *
	 * @var array<string, true>
	 */
	private array $fragments_in_progress = array();

	/**
	 * Variable values coerced for the current document, or null when not yet computed.
	 *
	 * @var ?array<string, mixed>
	 */
	private ?array $coerced_variable_values = null;

	/**
	 * Schema definition of every field node in the document, keyed by the
	 * node's spl_object_id(). Populated as the visitor enters each field.
	 *
	 * @var array<int, ?FieldDefinition>
	 */
	private array $field_definitions = array();

	/**
	 * Reset the per-document state, then replace the stock SELECTION_SET
	 * callback, which re-collects field definitions through every fragment
	 * reachable from each selection set, with recording the definition that
	 * TypeInfo already resolves as the visitor enters each field.
	 *
	 * @param QueryValidationContext $context The validation context.
	 * @return array The visitor definition.
	 */
	public function getVisitor( QueryValidationContext $context ): array {
		$this->fragment_complexities   = array();
		$this->fragments_in_progress   = array();
		$this->coerced_variable_values = null;
		$this->field_definitions       = array();

		$visitor = parent::getVisitor( $context );
		if ( array() === $visitor ) {
			// The rule is disabled.
			return $visitor;
		}

		unset( $visitor[ NodeKind::SELECTION_SET ] );
		$visitor[ NodeKind::FIELD ] = function ( FieldNode $node ) use ( $context ): void {
			$this->field_definitions[ spl_object_id( $node ) ] = $context->getFieldDef();
		};

		return $visitor;
	}

	/**
	 * Look up the schema definition recorded for a field node.
	 *
	 * @param FieldNode $field The field node.
	 * @return ?FieldDefinition The definition, or null when the field doesn't exist on its parent type.
	 */
	protected function fieldDefinition( FieldNode $field ): ?FieldDefinition {
		return $this->field_definitions[ spl_object_id( $field ) ] ?? null;
	}

	/**
	 * Sum the complexity of a selection set's selections, saturating at
	 * {@see self::COMPLEXITY_CEILING}.
	 *
	 * @param SelectionSetNode $selection_set The selection set to score.
	 * @return int The (possibly saturated) complexity.
	 * @throws \Exception When variable or argument coercion fails.
	 */
	protected function fieldComplexity( SelectionSetNode $selection_set ): int {
		$complexity = 0;

		foreach ( $selection_set->selections as $selection ) {
			$complexity = $this->add_saturating( $complexity, $this->nodeComplexity( $selection ) );
		}

		return $complexity;
	}

	/**
	 * Score a single selection. Named fragments are scored once and the result
	 * reused for every spread; everything else is delegated to the stock rule.
	 *
	 * @param SelectionNode $node The selection to score.
	 * @return int The complexity of the selection.
	 * @throws \Exception When variable or argument coercion fails.
	 */
	protected function nodeComplexity( SelectionNode $node ): int {
		if ( ! $node instanceof FragmentSpreadNode ) {
			return parent::nodeComplexity( $node );
		}

		$fragment = $this->getFragment( $node );
		if ( is_null( $fragment ) ) {
			return 0;
		}

		$name = $fragment->name->value;
		if ( array_key_exists( $name, $this->fragment_complexities ) ) {
			return $this->fragment_complexities[ $name ];
		}

		// A fragment that (transitively) spreads itself has unbounded
		// complexity. NoFragmentCycles reports the actual error.
		if ( isset( $this->fragments_in_progress[ $name ] ) ) {
			return self::COMPLEXITY_CEILING;
		}

		$this->fragments_in_progress[ $name ] = true;
		try {
			$complexity = $this->fieldComplexity( $fragment->selectionSet );
		} finally {
			unset( $this->fragments_in_progress[ $name ] );
		}

		$this->fragment_complexities[ $name ] = $complexity;

		return $complexity;
	}

	/**
	 * Whether `@include` / `@skip` directives exclude the field from execution.
	 *
	 * Same semantics as the stock rule, but variable values are coerced once
	 * per document (see {@see self::get_coerced_variable_values()}).
	 *
	 * @param FieldNode $node The field node.
	 * @return bool True when the field will not be executed.
	 * @throws \Exception When variable coercion fails.
	 */
	protected function directiveExcludesField( FieldNode $node ): bool {
		foreach ( $node->directives as $directive_node ) {
			$directive_name = $directive_node->name->value;

			if ( Directive::INCLUDE_NAME === $directive_name ) {
				$include_arguments = Values::getArgumentValues(
					Directive::includeDirective(),
					$directive_node,
					$this->get_coerced_variable_values()
				);
				if ( false === $include_arguments['if'] ) {
					return true;
				}
			} elseif ( Directive::SKIP_NAME === $directive_name ) {
				$skip_arguments = Values::getArgumentValues(
					Directive::skipDirective(),
					$directive_node,
					$this->get_coerced_variable_values()
				);
				if ( true === $skip_arguments['if'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Build the argument values handed to a field's complexity callback.
	 *
	 * Same semantics as the stock rule, but variable values are coerced once
	 * per document (see {@see self::get_coerced_variable_values()}).
	 *
	 * @param FieldNode $node The field node.
	 * @return array<string, mixed> The coerced argument values.
	 * @throws \Exception When variable or argument coercion fails.
	 */
	protected function buildFieldArguments( FieldNode $node ): array {
		$field_definition = $this->fieldDefinition( $node );

		return $field_definition instanceof FieldDefinition
			? Values::getArgumentValues( $field_definition, $node, $this->get_coerced_variable_values() )
			: array();
	}

	/**
	 * Coerce the document's variable values against their definitions,
	 * once per document.
	 *
	 * @return array<string, mixed> The coerced variable values.
	 * @throws Error When the provided variables don't satisfy their definitions (same error the stock rule throws).
	 */
	private function get_coerced_variable_values(): array {
		if ( ! is_null( $this->coerced_variable_values ) ) {
			return $this->coerced_variable_values;
		}

		list( $errors, $variable_values ) = Values::getVariableValues(
			$this->context->getSchema(),
			$this->variableDefs,
			$this->getRawVariableValues()
		);

		if ( ! empty( $errors ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON by the GraphQL error formatter.
			throw new Error(
				implode(
					"\n\n",
					array_map( static fn( Error $error ): string => $error->getMessage(), $errors )
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$this->coerced_variable_values = $variable_values ?? array();

		return $this->coerced_variable_values;
	}

	/**
	 * Add two complexity scores, saturating at {@see self::COMPLEXITY_CEILING}.
	 *
	 * @param int $a First score.
	 * @param int $b Second score.
	 * @return int The saturated sum.
	 */
	private function add_saturating( int $a, int $b ): int {
		$sum = $a + $b;

		// An int overflow turns the sum into a float, which is also above the ceiling.
		return $sum > self::COMPLEXITY_CEILING ? self::COMPLEXITY_CEILING : (int) $sum;
	}

	/**
	 * Override webonyx's default ("Max query complexity should be {max} but
	 * got {count}.").
	 *
	 * @param int $max   The configured maximum complexity (unused).
	 * @param int $count The computed query complexity (unused).
	 */
	public static function maxQueryComplexityErrorMessage( int $max, int $count ): string {
		return 'Maximum query complexity exceeded.';
	}
}
