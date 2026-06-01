<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;

/**
 * Possible type extensions.
 *
 * A type extension is only valid if the type is defined and has the same kind.
 */
class PossibleTypeExtensions extends ValidationRule
{
    public function getSDLVisitor(SDLValidationContext $context): array
    {
        $schema = $context->getSchema();

        /** @var array<string, TypeDefinitionNode&Node> $definedTypes */
        $definedTypes = [];
        foreach ($context->getDocument()->definitions as $def) {
            if ($def instanceof TypeDefinitionNode) {
                $name = $def->getName()->value;
                $definedTypes[$name] = $def;
            }
        }

        $checkTypeExtension = static function ($node) use ($context, $schema, &$definedTypes): ?VisitorOperation {
            $typeName = $node->name->value;
            $defNode = $definedTypes[$typeName] ?? null;
            $existingType = $schema !== null
                ? $schema->getType($typeName)
                : null;

            $expectedKind = null;
            if ($defNode !== null) {
                $expectedKind = self::defKindToExtKind($defNode->kind);
            } elseif ($existingType !== null) {
                $expectedKind = self::typeToExtKind($existingType);
            }

            if ($expectedKind !== null) {
                if ($expectedKind !== $node->kind) {
                    $kindStr = self::extensionKindToTypeName($node->kind);
                    $context->reportError(
                        new Error(
                            "Cannot extend non-{$kindStr} type \"{$typeName}\".",
                            $defNode !== null
                                ? [$defNode, $node]
                                : $node,
                        ),
                    );
                }
            } else {
                $existingTypesMap = $schema !== null
                    ? $schema->getTypeMap()
                    : [];
                $allTypeNames = [
                    ...array_keys($definedTypes),
                    ...array_keys($existingTypesMap),
                ];
                $suggestedTypes = Utils::suggestionList($typeName, $allTypeNames);
                $didYouMean = $suggestedTypes === []
                    ? ''
                    : ' Did you mean ' . Utils::quotedOrList($suggestedTypes) . '?';
                $context->reportError(
                    new Error(
                        "Cannot extend type \"{$typeName}\" because it is not defined.{$didYouMean}",
                        $node->name,
                    ),
                );
            }

            return null;
        };

        return [
            NodeKind::SCALAR_TYPE_EXTENSION => $checkTypeExtension,
            NodeKind::OBJECT_TYPE_EXTENSION => $checkTypeExtension,
            NodeKind::INTERFACE_TYPE_EXTENSION => $checkTypeExtension,
            NodeKind::UNION_TYPE_EXTENSION => $checkTypeExtension,
            NodeKind::ENUM_TYPE_EXTENSION => $checkTypeExtension,
            NodeKind::INPUT_OBJECT_TYPE_EXTENSION => $checkTypeExtension,
        ];
    }

    /** @throws InvariantViolation */
    private static function defKindToExtKind(string $kind): string
    {
        switch ($kind) {
            case NodeKind::SCALAR_TYPE_DEFINITION:
                return NodeKind::SCALAR_TYPE_EXTENSION;
            case NodeKind::OBJECT_TYPE_DEFINITION:
                return NodeKind::OBJECT_TYPE_EXTENSION;
            case NodeKind::INTERFACE_TYPE_DEFINITION:
                return NodeKind::INTERFACE_TYPE_EXTENSION;
            case NodeKind::UNION_TYPE_DEFINITION:
                return NodeKind::UNION_TYPE_EXTENSION;
            case NodeKind::ENUM_TYPE_DEFINITION:
                return NodeKind::ENUM_TYPE_EXTENSION;
            case NodeKind::INPUT_OBJECT_TYPE_DEFINITION:
                return NodeKind::INPUT_OBJECT_TYPE_EXTENSION;
            default:
                throw new InvariantViolation("Unexpected definition kind: {$kind}.");
        }
    }

    /** @throws InvariantViolation */
    private static function typeToExtKind(NamedType $type): string
    {
        switch (true) {
            case $type instanceof ScalarType:
                return NodeKind::SCALAR_TYPE_EXTENSION;
            case $type instanceof ObjectType:
                return NodeKind::OBJECT_TYPE_EXTENSION;
            case $type instanceof InterfaceType:
                return NodeKind::INTERFACE_TYPE_EXTENSION;
            case $type instanceof UnionType:
                return NodeKind::UNION_TYPE_EXTENSION;
            case $type instanceof EnumType:
                return NodeKind::ENUM_TYPE_EXTENSION;
            case $type instanceof InputObjectType:
                return NodeKind::INPUT_OBJECT_TYPE_EXTENSION;
            default:
                $unexpectedType = Utils::printSafe($type);
                throw new InvariantViolation("Unexpected type: {$unexpectedType}.");
        }
    }

    /** @throws InvariantViolation */
    private static function extensionKindToTypeName(string $kind): string
    {
        switch ($kind) {
            case NodeKind::SCALAR_TYPE_EXTENSION:
                return 'scalar';
            case NodeKind::OBJECT_TYPE_EXTENSION:
                return 'object';
            case NodeKind::INTERFACE_TYPE_EXTENSION:
                return 'interface';
            case NodeKind::UNION_TYPE_EXTENSION:
                return 'union';
            case NodeKind::ENUM_TYPE_EXTENSION:
                return 'enum';
            case NodeKind::INPUT_OBJECT_TYPE_EXTENSION:
                return 'input object';
            default:
                throw new InvariantViolation("Unexpected extension kind: {$kind}.");
        }
    }
}
