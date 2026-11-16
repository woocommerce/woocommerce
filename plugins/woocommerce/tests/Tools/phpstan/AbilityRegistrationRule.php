<?php
/**
 * AbilityRegistrationRule class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports a wp_register_ability() call outside AbilitiesLoader. The loader registers
 * every WooCommerce ability with Automattic\WooCommerce\Internal\Abilities\Ability or
 * ActionableAbility, so it gets its input converted to the schema types.
 *
 * @implements Rule<FuncCall>
 */
final class AbilityRegistrationRule implements Rule {

	private const LOADER_CLASS = 'Automattic\\WooCommerce\\Internal\\Abilities\\AbilitiesLoader';

	/**
	 * Node type this rule checks.
	 *
	 * @return string
	 */
	public function getNodeType(): string {
		return FuncCall::class;
	}

	/**
	 * Report the call when it registers an ability outside the loader.
	 *
	 * @param Node  $node  Function call node.
	 * @param Scope $scope Scope of the call.
	 * @return array
	 */
	public function processNode( Node $node, Scope $scope ): array {
		if ( ! $node->name instanceof Name || 'wp_register_ability' !== $node->name->toLowerString() ) {
			return array();
		}

		$class = $scope->getClassReflection();
		if ( null !== $class && self::LOADER_CLASS === $class->getName() ) {
			return array();
		}

		return array(
			RuleErrorBuilder::message( 'Register a WooCommerce ability through AbilitiesLoader, not with wp_register_ability(), so it uses the Ability class and gets its input converted to the schema types.' )
				->identifier( 'woocommerce.abilityRegistration' )
				->build(),
		);
	}
}
