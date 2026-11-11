<?php
/**
 * AbilityClassRule class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports a class that extends WP_Ability directly. A WooCommerce ability extends
 * Automattic\WooCommerce\Internal\Abilities\Ability or ActionableAbility, so it gets its input
 * converted to the schema types.
 *
 * @implements Rule<InClassNode>
 */
final class AbilityClassRule implements Rule {

	private const ALLOWED_CLASSES = array(
		'Automattic\\WooCommerce\\Internal\\Abilities\\Ability',
		'Automattic\\WooCommerce\\Internal\\Abilities\\REST\\RestAbility',
	);

	/**
	 * Node type this rule checks.
	 *
	 * @return string
	 */
	public function getNodeType(): string {
		return InClassNode::class;
	}

	/**
	 * Report the class when its parent is WP_Ability.
	 *
	 * @param Node  $node  Class node.
	 * @param Scope $scope Scope of the class.
	 * @return array
	 */
	public function processNode( Node $node, Scope $scope ): array {
		$class  = $node->getClassReflection();
		$parent = $class->getParentClass();

		if ( null === $parent || 'WP_Ability' !== $parent->getName() || in_array( $class->getName(), self::ALLOWED_CLASSES, true ) ) {
			return array();
		}

		return array(
			RuleErrorBuilder::message( sprintf( 'Class %s extends WP_Ability. Extend Automattic\WooCommerce\Internal\Abilities\Ability, or ActionableAbility for an ability that saves, so the input is converted to the schema types.', $class->getName() ) )
				->identifier( 'woocommerce.abilityClass' )
				->build(),
		);
	}
}
