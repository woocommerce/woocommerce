<?php
/**
 * StagedHookDocsRule class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use SzepeViktor\PHPStan\WordPress\HookDocBlock;
use SzepeViktor\PHPStan\WordPress\HookDocsRule;

/**
 * Checks the docblock of a hook that WC_Data::do_staged_action() or apply_staged_filters() fires, with the hook
 * docblock rule of phpstan-wordpress. The rule gets the do_action() or apply_filters() call that the wrapper makes,
 * with $staged as the last argument and the same docblock.
 *
 * @implements Rule<MethodCall>
 */
final class StagedHookDocsRule implements Rule {

	private const WRAPPERS = array(
		'do_staged_action'     => 'do_action',
		'apply_staged_filters' => 'apply_filters',
	);

	/**
	 * Hook docblock rule of phpstan-wordpress.
	 *
	 * @var HookDocsRule
	 */
	private $hook_docs_rule;

	/**
	 * Constructor.
	 *
	 * @param HookDocBlock    $hook_doc_block    Docblock reader of phpstan-wordpress.
	 * @param RuleLevelHelper $rule_level_helper Type helper.
	 */
	public function __construct( HookDocBlock $hook_doc_block, RuleLevelHelper $rule_level_helper ) {
		$this->hook_docs_rule = new HookDocsRule( $hook_doc_block, $rule_level_helper );
	}

	/**
	 * Node type this rule checks.
	 *
	 * @return string
	 */
	public function getNodeType(): string {
		return MethodCall::class;
	}

	/**
	 * Check the docblock of a wrapper call as the docblock of the hook call it makes.
	 *
	 * @param Node  $node  Method call.
	 * @param Scope $scope Scope of the call.
	 * @return list<\PHPStan\Rules\IdentifierRuleError>
	 */
	public function processNode( Node $node, Scope $scope ): array {
		if ( ! $node->name instanceof Identifier || ! isset( self::WRAPPERS[ $node->name->toString() ] ) ) {
			return array();
		}

		$args   = $node->getArgs();
		$args[] = new Arg( new ConstFetch( new Name( 'false' ) ) );
		$call   = new FuncCall( new Name( self::WRAPPERS[ $node->name->toString() ] ), $args, $node->getAttributes() );

		return $this->hook_docs_rule->processNode( $call, $scope );
	}
}
