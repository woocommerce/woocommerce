<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NamedTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeSystemDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeSystemExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\ValidationContext;

/**
 * Known type names.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL document is only valid if referenced types (specifically
 * variable definitions and fragment conditions) are defined by the type schema.
 *
 * @phpstan-import-type VisitorArray from \Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor
 */
class KnownTypeNames extends ValidationRule
{
    public function getVisitor(QueryValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    public function getSDLVisitor(SDLValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    /** @phpstan-return VisitorArray */
    public function getASTVisitor(ValidationContext $context): array
    {
        /** @var array<int, string> $definedTypes */
        $definedTypes = [];
        foreach ($context->getDocument()->definitions as $def) {
            if ($def instanceof TypeDefinitionNode) {
                $definedTypes[] = $def->getName()->value;
            }
        }

        return [
            NodeKind::NAMED_TYPE => static function (NamedTypeNode $node, $_1, $parent, $_2, $ancestors) use ($context, $definedTypes): void {
                $typeName = $node->name->value;
                $schema = $context->getSchema();

                if (in_array($typeName, $definedTypes, true)) {
                    return;
                }

                if ($schema !== null && $schema->hasType($typeName)) {
                    return;
                }

                $definitionNode = $ancestors[2] ?? $parent;
                $isSDL = $definitionNode instanceof TypeSystemDefinitionNode || $definitionNode instanceof TypeSystemExtensionNode;
                if ($isSDL && in_array($typeName, Type::BUILT_IN_TYPE_NAMES, true)) {
                    return;
                }

                $existingTypesMap = $schema !== null
                    ? $schema->getTypeMap()
                    : [];
                $typeNames = [
                    ...array_keys($existingTypesMap),
                    ...$definedTypes,
                ];
                $context->reportError(new Error(
                    static::unknownTypeMessage(
                        $typeName,
                        Utils::suggestionList(
                            $typeName,
                            $isSDL
                                ? [...Type::BUILT_IN_TYPE_NAMES, ...$typeNames]
                                : $typeNames
                        )
                    ),
                    [$node]
                ));
            },
        ];
    }

    /** @param array<string> $suggestedTypes */
    public static function unknownTypeMessage(string $type, array $suggestedTypes): string
    {
        $message = "Unknown type \"{$type}\".";

        if ($suggestedTypes !== []) {
            $suggestionList = Utils::quotedOrList($suggestedTypes);
            $message .= " Did you mean {$suggestionList}?";
        }

        return $message;
    }
}
