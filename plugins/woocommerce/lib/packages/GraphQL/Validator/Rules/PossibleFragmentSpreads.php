<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\AbstractType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\CompositeType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\AST;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

class PossibleFragmentSpreads extends ValidationRule
{
    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::INLINE_FRAGMENT => function (InlineFragmentNode $node) use ($context): void {
                $fragType = $context->getType();
                $parentType = $context->getParentType();

                if (
                    ! $fragType instanceof CompositeType
                    || ! $parentType instanceof CompositeType
                    || $this->doTypesOverlap($context->getSchema(), $fragType, $parentType)
                ) {
                    return;
                }

                $context->reportError(new Error(
                    static::typeIncompatibleAnonSpreadMessage($parentType->toString(), $fragType->toString()),
                    [$node]
                ));
            },
            NodeKind::FRAGMENT_SPREAD => function (FragmentSpreadNode $node) use ($context): void {
                $fragName = $node->name->value;
                $fragType = $this->getFragmentType($context, $fragName);
                $parentType = $context->getParentType();

                if (
                    $fragType === null
                    || $parentType === null
                    || $this->doTypesOverlap($context->getSchema(), $fragType, $parentType)
                ) {
                    return;
                }

                $context->reportError(new Error(
                    static::typeIncompatibleSpreadMessage($fragName, $parentType->toString(), $fragType->toString()),
                    [$node]
                ));
            },
        ];
    }

    /**
     * @param CompositeType&Type $fragType
     * @param CompositeType&Type $parentType
     *
     * @throws InvariantViolation
     */
    protected function doTypesOverlap(Schema $schema, CompositeType $fragType, CompositeType $parentType): bool
    {
        // Checking in the order of the most frequently used scenarios:
        // Parent type === fragment type
        if ($parentType === $fragType) {
            return true;
        }

        // Parent type is interface or union, fragment type is object type
        if ($parentType instanceof AbstractType && $fragType instanceof ObjectType) {
            return $schema->isSubType($parentType, $fragType);
        }

        // Parent type is object type, fragment type is interface (or rather rare - union)
        if ($parentType instanceof ObjectType && $fragType instanceof AbstractType) {
            return $schema->isSubType($fragType, $parentType);
        }

        // Both are object types:
        if ($parentType instanceof ObjectType && $fragType instanceof ObjectType) {
            return $parentType === $fragType;
        }

        // Both are interfaces
        // This case may be assumed valid only when implementations of two interfaces intersect
        // But we don't have information about all implementations at runtime
        // (getting this information via $schema->getPossibleTypes() requires scanning through whole schema
        // which is very costly to do at each request due to PHP "shared nothing" architecture)
        //
        // So in this case we just make it pass - invalid fragment spreads will be simply ignored during execution
        // See also https://github.com/webonyx/graphql-php/issues/69#issuecomment-283954602
        if ($parentType instanceof InterfaceType && $fragType instanceof InterfaceType) {
            return true;

            // Note that there is one case when we do have information about all implementations:
            // When schema descriptor is defined ($schema->hasDescriptor())
            // BUT we must avoid situation when some query that worked in development had suddenly stopped
            // working in production. So staying consistent and always validate.
        }

        // Interface within union
        if ($parentType instanceof UnionType && $fragType instanceof InterfaceType) {
            foreach ($parentType->getTypes() as $type) {
                if ($type->implementsInterface($fragType)) {
                    return true;
                }
            }
        }

        if ($parentType instanceof InterfaceType && $fragType instanceof UnionType) {
            foreach ($fragType->getTypes() as $type) {
                if ($type->implementsInterface($parentType)) {
                    return true;
                }
            }
        }

        if ($parentType instanceof UnionType && $fragType instanceof UnionType) {
            foreach ($fragType->getTypes() as $type) {
                if ($parentType->isPossibleType($type)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function typeIncompatibleAnonSpreadMessage(string $parentType, string $fragType): string
    {
        return "Fragment cannot be spread here as objects of type \"{$parentType}\" can never be of type \"{$fragType}\".";
    }

    /**
     * @throws \Exception
     *
     * @return (CompositeType&Type)|null
     */
    protected function getFragmentType(QueryValidationContext $context, string $name): ?Type
    {
        $frag = $context->getFragment($name);
        if ($frag === null) {
            return null;
        }

        $type = AST::typeFromAST([$context->getSchema(), 'getType'], $frag->typeCondition);

        return $type instanceof CompositeType
            ? $type
            : null;
    }

    public static function typeIncompatibleSpreadMessage(string $fragName, string $parentType, string $fragType): string
    {
        return "Fragment \"{$fragName}\" cannot be spread here as objects of type \"{$parentType}\" can never be of type \"{$fragType}\".";
    }
}
