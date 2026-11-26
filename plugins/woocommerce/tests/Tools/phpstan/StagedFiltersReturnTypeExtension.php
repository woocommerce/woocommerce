<?php
/**
 * StagedFiltersReturnTypeExtension class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools\phpstan;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use SzepeViktor\PHPStan\WordPress\HookDocBlock;

/**
 * Infers the return type of WC_Data::apply_staged_filters() from the type of the first @param tag of the hook
 * docblock, as phpstan-wordpress does for apply_filters().
 */
final class StagedFiltersReturnTypeExtension implements DynamicMethodReturnTypeExtension {

	/**
	 * Docblock reader of phpstan-wordpress.
	 *
	 * @var HookDocBlock
	 */
	private $hook_doc_block;

	/**
	 * Constructor.
	 *
	 * @param HookDocBlock $hook_doc_block Docblock reader of phpstan-wordpress.
	 */
	public function __construct( HookDocBlock $hook_doc_block ) {
		$this->hook_doc_block = $hook_doc_block;
	}

	/**
	 * Class whose method this extension types.
	 *
	 * @return string
	 */
	public function getClass(): string {
		return 'WC_Data';
	}

	/**
	 * Whether the extension types this method.
	 *
	 * @param MethodReflection $method_reflection Method.
	 * @return bool
	 */
	public function isMethodSupported( MethodReflection $method_reflection ): bool {
		return 'apply_staged_filters' === $method_reflection->getName();
	}

	/**
	 * Type of the first @param tag of the hook docblock, or mixed without one.
	 *
	 * @param MethodReflection $method_reflection Method.
	 * @param MethodCall       $method_call       Call.
	 * @param Scope            $scope             Scope of the call.
	 * @return Type
	 */
	public function getTypeFromMethodCall( MethodReflection $method_reflection, MethodCall $method_call, Scope $scope ): Type {
		$doc = $this->hook_doc_block->getNullableHookDocBlock( new FuncCall( new Name( 'apply_filters' ), $method_call->getArgs(), $method_call->getAttributes() ), $scope );

		foreach ( null === $doc ? array() : $doc->getParamTags() as $param ) {
			return $param->getType();
		}

		return new MixedType();
	}
}
