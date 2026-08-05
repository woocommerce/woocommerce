<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ArgumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeList;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NullValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ScalarTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ScalarTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\UnionTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\UnionTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\AST;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Value;

/**
 * @see ArgumentNode - force IDE import
 *
 * @phpstan-import-type ArgumentNodeValue from ArgumentNode
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Executor\ValuesTest
 */
class Values
{
    /**
     * Prepares an object map of variables of the correct type based on the provided
     * variable definitions and arbitrary input. If the input cannot be coerced
     * to match the variable definitions, an Error will be thrown.
     *
     * @param NodeList<VariableDefinitionNode> $varDefNodes
     * @param array<string, mixed> $rawVariableValues
     *
     * @throws \Exception
     *
     * @return array{array<int, Error>, null}|array{null, array<string, mixed>}
     */
    public static function getVariableValues(Schema $schema, NodeList $varDefNodes, array $rawVariableValues): array
    {
        $errors = [];
        $coercedValues = [];
        foreach ($varDefNodes as $varDefNode) {
            $varName = $varDefNode->variable->name->value;
            $varType = AST::typeFromAST([$schema, 'getType'], $varDefNode->type);

            if (! Type::isInputType($varType)) {
                // Must use input types for variables. This should be caught during
                // validation, however is checked again here for safety.
                $typeStr = Printer::doPrint($varDefNode->type);
                $errors[] = new Error(
                    "Variable \"\${$varName}\" expected value of type \"{$typeStr}\" which cannot be used as an input type.",
                    [$varDefNode->type]
                );
            } else {
                $hasValue = array_key_exists($varName, $rawVariableValues);
                $value = $hasValue
                    ? $rawVariableValues[$varName]
                    : Utils::undefined();

                if (! $hasValue && ($varDefNode->defaultValue !== null)) {
                    // If no value was provided to a variable with a default value,
                    // use the default value.
                    $coercedValues[$varName] = AST::valueFromAST($varDefNode->defaultValue, $varType);
                } elseif ((! $hasValue || $value === null) && ($varType instanceof NonNull)) {
                    // If no value or a nullish value was provided to a variable with a
                    // non-null type (required), produce an error.
                    $safeVarType = Utils::printSafe($varType);
                    $message = $hasValue
                        ? "Variable \"\${$varName}\" of non-null type \"{$safeVarType}\" must not be null."
                        : "Variable \"\${$varName}\" of required type \"{$safeVarType}\" was not provided.";
                    $errors[] = new Error($message, [$varDefNode]);
                } elseif ($hasValue) {
                    if ($value === null) {
                        // If the explicit value `null` was provided, an entry in the coerced
                        // values must exist as the value `null`.
                        $coercedValues[$varName] = null;
                    } else {
                        // Otherwise, a non-null value was provided, coerce it to the expected
                        // type or report an error if coercion fails.
                        $coerced = Value::coerceInputValue($value, $varType, null, $schema);

                        $coercionErrors = $coerced['errors'];
                        if ($coercionErrors !== null) {
                            foreach ($coercionErrors as $coercionError) {
                                $invalidValue = $coercionError->printInvalidValue();

                                $inputPath = $coercionError->printInputPath();
                                $pathMessage = $inputPath !== null
                                    ? " at \"{$varName}{$inputPath}\""
                                    : '';

                                $errors[] = new Error(
                                    "Variable \"\${$varName}\" got invalid value {$invalidValue}{$pathMessage}; {$coercionError->getMessage()}",
                                    $varDefNode,
                                    $coercionError->getSource(),
                                    $coercionError->getPositions(),
                                    $coercionError->getPath(),
                                    $coercionError,
                                    $coercionError->getExtensions()
                                );
                            }
                        } else {
                            $coercedValues[$varName] = $coerced['value'];
                        }
                    }
                }
            }
        }

        return $errors === []
            ? [null, $coercedValues]
            : [$errors, null];
    }

    /**
     * Prepares an object map of argument values given a directive definition
     * and an AST node which may contain directives. Optionally also accepts a map
     * of variable values.
     *
     * If the directive does not exist on the node, returns undefined.
     *
     * @param EnumTypeDefinitionNode|EnumTypeExtensionNode|EnumValueDefinitionNode|FieldDefinitionNode|FieldNode|FragmentDefinitionNode|FragmentSpreadNode|InlineFragmentNode|InputObjectTypeDefinitionNode|InputObjectTypeExtensionNode|InputValueDefinitionNode|InterfaceTypeDefinitionNode|InterfaceTypeExtensionNode|ObjectTypeDefinitionNode|ObjectTypeExtensionNode|OperationDefinitionNode|ScalarTypeDefinitionNode|ScalarTypeExtensionNode|SchemaExtensionNode|UnionTypeDefinitionNode|UnionTypeExtensionNode|VariableDefinitionNode $node
     * @param array<string, mixed>|null $variableValues
     *
     * @throws \Exception
     * @throws Error
     *
     * @return array<string, mixed>|null
     */
    public static function getDirectiveValues(Directive $directiveDef, Node $node, ?array $variableValues = null, ?Schema $schema = null): ?array
    {
        $directiveDefName = $directiveDef->name;

        foreach ($node->directives as $directive) {
            if ($directive->name->value === $directiveDefName) {
                return self::getArgumentValues($directiveDef, $directive, $variableValues, $schema);
            }
        }

        return null;
    }

    /**
     * Prepares an object map of argument values given a list of argument
     * definitions and list of argument AST nodes.
     *
     * @param FieldDefinition|Directive $def
     * @param FieldNode|DirectiveNode $node
     * @param array<string, mixed>|null $variableValues
     *
     * @throws \Exception
     * @throws Error
     *
     * @return array<string, mixed>
     */
    public static function getArgumentValues($def, Node $node, ?array $variableValues = null, ?Schema $schema = null): array
    {
        if ($def->args === []) {
            return [];
        }

        /** @var array<string, ArgumentNodeValue> $argumentValueMap */
        $argumentValueMap = [];

        // Might not be defined when an AST from JS is used
        if (isset($node->arguments)) {
            foreach ($node->arguments as $argumentNode) {
                $argumentValueMap[$argumentNode->name->value] = $argumentNode->value;
            }
        }

        return static::getArgumentValuesForMap($def, $argumentValueMap, $variableValues, $node, $schema);
    }

    /**
     * @param FieldDefinition|Directive $def
     * @param array<string, ArgumentNodeValue> $argumentValueMap
     * @param array<string, mixed>|null $variableValues
     *
     * @throws \Exception
     * @throws Error
     *
     * @return array<string, mixed>
     */
    public static function getArgumentValuesForMap($def, array $argumentValueMap, ?array $variableValues = null, ?Node $referenceNode = null, ?Schema $schema = null): array
    {
        /** @var array<string, mixed> $coercedValues */
        $coercedValues = [];

        foreach ($def->args as $argumentDefinition) {
            $name = $argumentDefinition->name;
            $argType = $argumentDefinition->getType();
            $argumentValueNode = $argumentValueMap[$name] ?? null;

            if ($argumentValueNode instanceof VariableNode) {
                $variableName = $argumentValueNode->name->value;
                $hasValue = $variableValues !== null && array_key_exists($variableName, $variableValues);
                $isNull = $hasValue && $variableValues[$variableName] === null;
            } else {
                $hasValue = $argumentValueNode !== null;
                $isNull = $argumentValueNode instanceof NullValueNode;
            }

            if (! $hasValue && $argumentDefinition->defaultValueExists()) {
                // If no argument was provided where the definition has a default value,
                // use the default value.
                $coercedValues[$name] = $argumentDefinition->defaultValue;
            } elseif ((! $hasValue || $isNull) && ($argType instanceof NonNull)) {
                // If no argument or a null value was provided to an argument with a
                // non-null type (required), produce a field error.
                $safeArgType = Utils::printSafe($argType);

                if ($isNull) {
                    throw new Error("Argument \"{$name}\" of non-null type \"{$safeArgType}\" must not be null.", $referenceNode);
                }

                if ($argumentValueNode instanceof VariableNode) {
                    throw new Error("Argument \"{$name}\" of required type \"{$safeArgType}\" was provided the variable \"\${$argumentValueNode->name->value}\" which was not provided a runtime value.", [$argumentValueNode]);
                }

                throw new Error("Argument \"{$name}\" of required type \"{$safeArgType}\" was not provided.", $referenceNode);
            } elseif ($hasValue) {
                assert($argumentValueNode instanceof Node);

                if ($argumentValueNode instanceof NullValueNode) {
                    // If the explicit value `null` was provided, an entry in the coerced
                    // values must exist as the value `null`.
                    $coercedValues[$name] = null;
                } elseif ($argumentValueNode instanceof VariableNode) {
                    $variableName = $argumentValueNode->name->value;
                    // Note: This does no further checking that this variable is correct.
                    // This assumes that this query has been validated and the variable
                    // usage here is of the correct type.
                    $coercedValues[$name] = $variableValues[$variableName] ?? null;
                } else {
                    $coercedValue = AST::valueFromAST($argumentValueNode, $argType, $variableValues, $schema);
                    if (Utils::undefined() === $coercedValue) {
                        // Note: ValuesOfCorrectType validation should catch this before
                        // execution. This is a runtime check to ensure execution does not
                        // continue with an invalid argument value.
                        $invalidValue = Printer::doPrint($argumentValueNode);
                        throw new Error("Argument \"{$name}\" has invalid value {$invalidValue}.", [$argumentValueNode]);
                    }

                    $coercedValues[$name] = $coercedValue;
                }
            }
        }

        return $coercedValues;
    }
}
