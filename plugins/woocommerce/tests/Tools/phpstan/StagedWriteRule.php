<?php
/**
 * StagedWriteRule class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports a method of a WC_Data subclass that writes to the database outside save(), unless it also calls a
 * stage_*() method on $this, which marks it as the saving twin of a stage method. Methods of the save pipeline
 * and deletes are exempt.
 *
 * @implements Rule<InClassMethodNode>
 */
final class StagedWriteRule implements Rule {

	private const ALLOWED_METHODS = array( 'save', 'create', 'update', 'read', 'apply_changes', 'save_items', 'save_meta_data', 'validate_props', 'before_data_store_save_or_update', 'after_data_store_save_or_update' );

	private const DATA_STORE_WRITE_PREFIXES = array( 'update', 'delete', 'increase', 'decrease', 'set', 'sync', 'create', 'save' );

	private const WRITE_FUNCTIONS = array( 'wp_insert_comment', 'wp_update_post', 'update_post_meta', 'wp_insert_post' );

	private const WPDB_WRITE_METHODS = array( 'query', 'insert', 'update', 'delete', 'replace' );

	/**
	 * Node type this rule checks.
	 *
	 * @return string
	 */
	public function getNodeType(): string {
		return InClassMethodNode::class;
	}

	/**
	 * Report the method when it writes and calls no stage method.
	 *
	 * @param Node  $node  Method node.
	 * @param Scope $scope Scope of the method.
	 * @return array
	 */
	public function processNode( Node $node, Scope $scope ): array {
		$class  = $node->getClassReflection();
		$method = $node->getOriginalNode();

		$name = $method->name->toLowerString();
		if ( ! $class->isSubclassOf( 'WC_Data' ) || $method->isStatic() || null === $method->stmts || in_array( $name, self::ALLOWED_METHODS, true ) || 0 === strpos( $name, 'delete' ) ) {
			return array();
		}

		$finder      = new NodeFinder();
		$data_stores = array();
		foreach ( $finder->findInstanceOf( $method->stmts, Assign::class ) as $assign ) {
			if ( $assign->var instanceof Variable && is_string( $assign->var->name ) && $assign->expr instanceof StaticCall
				&& $assign->expr->class instanceof Name && 'WC_Data_Store' === $assign->expr->class->toString()
				&& $assign->expr->name instanceof Identifier && 'load' === $assign->expr->name->toLowerString() ) {
				$data_stores[] = $assign->var->name;
			}
		}

		$writes = false;
		foreach ( $finder->find( $method->stmts, fn( Node $call ) => $call instanceof MethodCall || $call instanceof FuncCall ) as $call ) {
			if ( $call instanceof FuncCall ) {
				$writes = $writes || ( $call->name instanceof Name && in_array( $call->name->toLowerString(), self::WRITE_FUNCTIONS, true ) );
				continue;
			}

			if ( ! $call->name instanceof Identifier ) {
				continue;
			}

			$name = $call->name->toLowerString();
			if ( self::is_variable( $call->var, 'this' ) && 0 === strpos( $name, 'stage_' ) ) {
				return array();
			}

			$on_data_store = ( $call->var instanceof PropertyFetch && self::is_variable( $call->var->var, 'this' ) && $call->var->name instanceof Identifier && 'data_store' === $call->var->name->toString() )
				|| ( $call->var instanceof Variable && in_array( $call->var->name, $data_stores, true ) );

			$writes = $writes
				|| 'save' === $name
				|| ( $on_data_store && self::starts_with_any( $name, self::DATA_STORE_WRITE_PREFIXES ) )
				|| ( self::is_variable( $call->var, 'wpdb' ) && in_array( $name, self::WPDB_WRITE_METHODS, true ) );
		}

		if ( ! $writes ) {
			return array();
		}

		return array(
			RuleErrorBuilder::message( sprintf( 'Method %s::%s() writes outside save(). Move the in-memory change to a stage_*() twin, call it here, then save. See WC_Order::stage_status().', $class->getName(), $method->name->toString() ) )
				->identifier( 'woocommerce.stagedWrite' )
				->build(),
		);
	}

	/**
	 * Whether the expression is the variable with the given name.
	 *
	 * @param Node   $expr Expression.
	 * @param string $name Variable name without the dollar sign.
	 * @return bool
	 */
	private static function is_variable( Node $expr, string $name ): bool {
		return $expr instanceof Variable && $name === $expr->name;
	}

	/**
	 * Whether the name starts with one of the prefixes.
	 *
	 * @param string   $name     Method name in lower case.
	 * @param string[] $prefixes Prefixes.
	 * @return bool
	 */
	private static function starts_with_any( string $name, array $prefixes ): bool {
		foreach ( $prefixes as $prefix ) {
			if ( 0 === strpos( $name, $prefix ) ) {
				return true;
			}
		}
		return false;
	}
}
