<?php
/**
 * DataStoreHasCallableExtension class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\ObjectWithoutClassType;

/**
 * Treats WC_Data_Store::has_callable( 'name' ) like method_exists(): where it returns true, the data store is known to
 * have that method. This lets code call an optional data store method, which a custom store may not implement, after
 * checking for it, and still reports the call when the check is missing.
 */
final class DataStoreHasCallableExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension {

	/**
	 * Type specifier.
	 *
	 * @var TypeSpecifier
	 */
	private $type_specifier;

	/**
	 * Set the type specifier.
	 *
	 * @param TypeSpecifier $type_specifier Type specifier.
	 */
	public function setTypeSpecifier( TypeSpecifier $type_specifier ): void {
		$this->type_specifier = $type_specifier;
	}

	/**
	 * Class whose method this extension handles.
	 *
	 * @return string
	 */
	public function getClass(): string {
		return 'WC_Data_Store';
	}

	/**
	 * Handle has_callable() calls with an argument, where the call is true.
	 *
	 * @param MethodReflection     $method_reflection Called method.
	 * @param MethodCall           $node              Method call node.
	 * @param TypeSpecifierContext $context           Context of the call.
	 * @return bool
	 */
	public function isMethodSupported( MethodReflection $method_reflection, MethodCall $node, TypeSpecifierContext $context ): bool {
		return 'has_callable' === $method_reflection->getName() && $context->true() && count( $node->getArgs() ) > 0;
	}

	/**
	 * Add the named method to the type of the data store, when the name is a single known string.
	 *
	 * @param MethodReflection     $method_reflection Called method.
	 * @param MethodCall           $node              Method call node.
	 * @param Scope                $scope             Scope of the call.
	 * @param TypeSpecifierContext $context           Context of the call.
	 * @return SpecifiedTypes
	 */
	public function specifyTypes( MethodReflection $method_reflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context ): SpecifiedTypes {
		$method_names = $scope->getType( $node->getArgs()[0]->value )->getConstantStrings();
		if ( 1 !== count( $method_names ) ) {
			return new SpecifiedTypes();
		}

		return $this->type_specifier->create(
			$node->var,
			new IntersectionType( array( new ObjectWithoutClassType(), new HasMethodType( $method_names[0]->getValue() ) ) ),
			$context,
			$scope
		);
	}
}
