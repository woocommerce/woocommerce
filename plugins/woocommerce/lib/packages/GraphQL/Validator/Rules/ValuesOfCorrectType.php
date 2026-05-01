<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\BooleanValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FloatValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\IntValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ListValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NullValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectFieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\LeafType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

/**
 * Value literals of correct type.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL document is only valid if all value literals are of the type
 * expected at their position.
 */
class ValuesOfCorrectType extends ValidationRule
{
    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::NULL => static function (NullValueNode $node) use ($context): void {
                $type = $context->getInputType();
                if ($type instanceof NonNull) {
                    $typeStr = Utils::printSafe($type);
                    $nodeStr = Printer::doPrint($node);
                    $context->reportError(
                        new Error(
                            "Expected value of type \"{$typeStr}\", found {$nodeStr}.",
                            $node
                        )
                    );
                }
            },
            NodeKind::LST => function (ListValueNode $node) use ($context): ?VisitorOperation {
                // Note: TypeInfo will traverse into a list's item type, so look to the
                // parent input type to check if it is a list.
                $parentType = $context->getParentInputType();
                $type = $parentType === null
                    ? null
                    : Type::getNullableType($parentType);
                if (! $type instanceof ListOfType) {
                    $this->isValidValueNode($context, $node);

                    return Visitor::skipNode();
                }

                return null;
            },
            NodeKind::OBJECT => function (ObjectValueNode $node) use ($context): ?VisitorOperation {
                $type = Type::getNamedType($context->getInputType());
                if (! $type instanceof InputObjectType) {
                    $this->isValidValueNode($context, $node);

                    return Visitor::skipNode();
                }

                // Ensure every required field exists.
                $inputFields = $type->getFields();

                $fieldNodeMap = [];
                foreach ($node->fields as $field) {
                    $fieldNodeMap[$field->name->value] = $field;
                }

                foreach ($inputFields as $inputFieldName => $fieldDef) {
                    if (! isset($fieldNodeMap[$inputFieldName]) && $fieldDef->isRequired()) {
                        $fieldType = Utils::printSafe($fieldDef->getType());
                        $context->reportError(
                            new Error(
                                "Field {$type->name}.{$inputFieldName} of required type {$fieldType} was not provided.",
                                $node
                            )
                        );
                    }
                }

                return null;
            },
            NodeKind::OBJECT_FIELD => static function (ObjectFieldNode $node) use ($context): void {
                $parentType = Type::getNamedType($context->getParentInputType());
                if (! $parentType instanceof InputObjectType) {
                    return;
                }

                if ($context->getInputType() !== null) {
                    return;
                }

                $suggestions = Utils::suggestionList(
                    $node->name->value,
                    array_keys($parentType->getFields())
                );
                $didYouMean = $suggestions === []
                    ? null
                    : ' Did you mean ' . Utils::quotedOrList($suggestions) . '?';

                $context->reportError(
                    new Error(
                        "Field \"{$node->name->value}\" is not defined by type \"{$parentType->name}\".{$didYouMean}",
                        $node
                    )
                );
            },
            NodeKind::ENUM => function (EnumValueNode $node) use ($context): void {
                $this->isValidValueNode($context, $node);
            },
            NodeKind::INT => function (IntValueNode $node) use ($context): void {
                $this->isValidValueNode($context, $node);
            },
            NodeKind::FLOAT => function (FloatValueNode $node) use ($context): void {
                $this->isValidValueNode($context, $node);
            },
            NodeKind::STRING => function (StringValueNode $node) use ($context): void {
                $this->isValidValueNode($context, $node);
            },
            NodeKind::BOOLEAN => function (BooleanValueNode $node) use ($context): void {
                $this->isValidValueNode($context, $node);
            },
        ];
    }

    /**
     * @param VariableNode|NullValueNode|IntValueNode|FloatValueNode|StringValueNode|BooleanValueNode|EnumValueNode|ListValueNode|ObjectValueNode $node
     *
     * @throws \JsonException
     */
    protected function isValidValueNode(QueryValidationContext $context, ValueNode $node): void
    {
        // Report any error at the full type expected by the location.
        $locationType = $context->getInputType();
        if ($locationType === null) {
            return;
        }

        $type = Type::getNamedType($locationType);

        if (! $type instanceof LeafType) {
            $typeStr = Utils::printSafe($type);
            $nodeStr = Printer::doPrint($node);
            $context->reportError(
                new Error(
                    "Expected value of type \"{$typeStr}\", found {$nodeStr}.",
                    $node
                )
            );

            return;
        }

        // Scalars determine if a literal value is valid via parseLiteral() which
        // may throw to indicate failure.
        try {
            $type->parseLiteral($node);
        } catch (\Throwable $error) {
            if ($error instanceof Error) {
                $context->reportError($error);
            } else {
                $typeStr = Utils::printSafe($type);
                $nodeStr = Printer::doPrint($node);
                $context->reportError(
                    new Error(
                        "Expected value of type \"{$typeStr}\", found {$nodeStr}; {$error->getMessage()}",
                        $node,
                        null,
                        [],
                        null,
                        $error // Ensure a reference to the original error is maintained.
                    )
                );
            }
        }
    }
}
