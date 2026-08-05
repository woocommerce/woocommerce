<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\BooleanValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FloatValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\IntValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ListTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ListValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Location;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NamedTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NameNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeList;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NonNullTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NullValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectFieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\IDType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\LeafType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NullableType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * Various utilities dealing with AST.
 */
class AST
{
    /**
     * Convert representation of AST as an associative array to instance of Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node.
     *
     * For example:
     *
     * ```php
     * AST::fromArray([
     *     'kind' => 'ListValue',
     *     'values' => [
     *         ['kind' => 'StringValue', 'value' => 'my str'],
     *         ['kind' => 'StringValue', 'value' => 'my other str']
     *     ],
     *     'loc' => ['start' => 21, 'end' => 25]
     * ]);
     * ```
     *
     * Will produce instance of `ListValueNode` where `values` prop is a lazily-evaluated `NodeList`
     * returning instances of `StringValueNode` on access.
     *
     * This is a reverse operation for AST::toArray($node)
     *
     * @param array<string, mixed> $node
     *
     * @api
     *
     * @throws \JsonException
     * @throws InvariantViolation
     */
    public static function fromArray(array $node): Node
    {
        $kind = $node['kind'] ?? null;
        if ($kind === null) {
            $safeNode = Utils::printSafeJson($node);
            throw new InvariantViolation("Node is missing kind: {$safeNode}");
        }

        $class = NodeKind::CLASS_MAP[$kind] ?? null;
        if ($class === null) {
            $safeNode = Utils::printSafeJson($node);
            throw new InvariantViolation("Node has unexpected kind: {$safeNode}");
        }

        $instance = new $class([]);

        if (isset($node['loc']['start'], $node['loc']['end'])) {
            $instance->loc = Location::create($node['loc']['start'], $node['loc']['end']);
        }

        foreach ($node as $key => $value) {
            if ($key === 'loc' || $key === 'kind') {
                continue;
            }

            if (is_array($value)) {
                $value = isset($value[0]) || $value === []
                    ? new NodeList($value)
                    : self::fromArray($value);
            }

            $instance->{$key} = $value;
        }

        return $instance;
    }

    /**
     * Convert AST node to serializable array.
     *
     * @return array<string, mixed>
     *
     * @api
     */
    public static function toArray(Node $node): array
    {
        return $node->toArray();
    }

    /**
     * Produces a Automattic\WooCommerce\Vendor\GraphQL Value AST given a PHP value.
     *
     * Optionally, a Automattic\WooCommerce\Vendor\GraphQL type may be provided, which will be used to
     * disambiguate between value primitives.
     *
     * | PHP Value     | Automattic\WooCommerce\Vendor\GraphQL Value        |
     * | ------------- | -------------------- |
     * | Object        | Input Object         |
     * | Assoc Array   | Input Object         |
     * | Array         | List                 |
     * | Boolean       | Boolean              |
     * | String        | String / Enum Value  |
     * | Int           | Int                  |
     * | Float         | Int / Float          |
     * | Mixed         | Enum Value           |
     * | null          | NullValue            |
     *
     * @param mixed $value
     * @param InputType&Type $type
     *
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws SerializationError
     *
     * @return (ValueNode&Node)|null
     *
     * @api
     */
    public static function astFromValue($value, InputType $type): ?ValueNode
    {
        if ($type instanceof NonNull) {
            $wrappedType = $type->getWrappedType();
            assert($wrappedType instanceof InputType);

            $astValue = self::astFromValue($value, $wrappedType);

            return $astValue instanceof NullValueNode
                ? null
                : $astValue;
        }

        if ($value === null) {
            return new NullValueNode([]);
        }

        // Convert PHP iterables to Automattic\WooCommerce\Vendor\GraphQL list. If the GraphQLType is a list, but
        // the value is not an array, convert the value using the list's item type.
        if ($type instanceof ListOfType) {
            $itemType = $type->getWrappedType();
            assert($itemType instanceof InputType, 'proven by schema validation');

            if (is_iterable($value)) {
                $valuesNodes = [];
                foreach ($value as $item) {
                    $itemNode = self::astFromValue($item, $itemType);
                    if ($itemNode !== null) {
                        $valuesNodes[] = $itemNode;
                    }
                }

                return new ListValueNode(['values' => new NodeList($valuesNodes)]);
            }

            return self::astFromValue($value, $itemType);
        }

        // Populate the fields of the input object by creating ASTs from each value
        // in the PHP object according to the fields in the input type.
        if ($type instanceof InputObjectType) {
            $isArray = is_array($value);
            $isArrayLike = $isArray || $value instanceof \ArrayAccess;
            if (! $isArrayLike && ! is_object($value)) {
                return null;
            }

            $fields = $type->getFields();
            $fieldNodes = [];
            foreach ($fields as $fieldName => $field) {
                $fieldValue = $isArrayLike
                    ? $value[$fieldName] ?? null
                    : $value->{$fieldName} ?? null;

                // Have to check additionally if key exists, since we differentiate between
                // "no key" and "value is null":
                if ($fieldValue !== null) {
                    $fieldExists = true;
                } elseif ($isArray) {
                    $fieldExists = array_key_exists($fieldName, $value);
                } elseif ($isArrayLike) {
                    $fieldExists = $value->offsetExists($fieldName);
                } else {
                    $fieldExists = property_exists($value, $fieldName);
                }

                if (! $fieldExists) {
                    continue;
                }

                $fieldNode = self::astFromValue($fieldValue, $field->getType());

                if ($fieldNode === null) {
                    continue;
                }

                $fieldNodes[] = new ObjectFieldNode([
                    'name' => new NameNode(['value' => $fieldName]),
                    'value' => $fieldNode,
                ]);
            }

            return new ObjectValueNode(['fields' => new NodeList($fieldNodes)]);
        }

        assert($type instanceof LeafType, 'other options were exhausted');

        // Since value is an internally represented value, it must be serialized
        // to an externally represented value before converting into an AST.
        $serialized = $type->serialize($value);

        // Others serialize based on their corresponding PHP scalar types.
        if (is_bool($serialized)) {
            return new BooleanValueNode(['value' => $serialized]);
        }

        if (is_int($serialized)) {
            return new IntValueNode(['value' => (string) $serialized]);
        }

        if (is_float($serialized)) {
            /** @phpstan-ignore equal.notAllowed (int cast with == used for performance reasons) */
            if ((int) $serialized == $serialized) {
                return new IntValueNode(['value' => (string) $serialized]);
            }

            return new FloatValueNode(['value' => (string) $serialized]);
        }

        if (is_string($serialized)) {
            // Enum types use Enum literals.
            if ($type instanceof EnumType) {
                return new EnumValueNode(['value' => $serialized]);
            }

            // ID types can use Int literals.
            $asInt = (int) $serialized;
            if ($type instanceof IDType && (string) $asInt === $serialized) {
                return new IntValueNode(['value' => $serialized]);
            }

            // Use json_encode, which uses the same string encoding as GraphQL,
            // then remove the quotes.
            return new StringValueNode(['value' => $serialized]);
        }

        $notConvertible = Utils::printSafe($serialized);
        throw new InvariantViolation("Cannot convert value to AST: {$notConvertible}");
    }

    /**
     * Produces a PHP value given a Automattic\WooCommerce\Vendor\GraphQL Value AST.
     *
     * A Automattic\WooCommerce\Vendor\GraphQL type must be provided, which will be used to interpret different
     * Automattic\WooCommerce\Vendor\GraphQL Value literals.
     *
     * Returns `null` when the value could not be validly coerced according to
     * the provided type.
     *
     * | Automattic\WooCommerce\Vendor\GraphQL Value        | PHP Value     |
     * | -------------------- | ------------- |
     * | Input Object         | Assoc Array   |
     * | List                 | Array         |
     * | Boolean              | Boolean       |
     * | String               | String        |
     * | Int / Float          | Int / Float   |
     * | Enum Value           | Mixed         |
     * | Null Value           | null          |
     *
     * @param (ValueNode&Node)|null $valueNode
     * @param array<string, mixed>|null $variables
     *
     * @throws \Exception
     *
     * @return mixed
     *
     * @api
     */
    public static function valueFromAST(?ValueNode $valueNode, Type $type, ?array $variables = null, ?Schema $schema = null)
    {
        $undefined = Utils::undefined();

        if ($valueNode === null) {
            // When there is no AST, then there is also no value.
            // Importantly, this is different from returning the Automattic\WooCommerce\Vendor\GraphQL null value.
            return $undefined;
        }

        if ($type instanceof NonNull) {
            if ($valueNode instanceof NullValueNode) {
                // Invalid: intentionally return no value.
                return $undefined;
            }

            return self::valueFromAST($valueNode, $type->getWrappedType(), $variables, $schema);
        }

        if ($valueNode instanceof NullValueNode) {
            // This is explicitly returning the value null.
            return null;
        }

        if ($valueNode instanceof VariableNode) {
            $variableName = $valueNode->name->value;

            if ($variables === null || ! array_key_exists($variableName, $variables)) {
                // No valid return value.
                return $undefined;
            }

            // Note: This does no further checking that this variable is correct.
            // This assumes that this query has been validated and the variable
            // usage here is of the correct type.
            return $variables[$variableName];
        }

        if ($type instanceof ListOfType) {
            $itemType = $type->getWrappedType();

            if ($valueNode instanceof ListValueNode) {
                $coercedValues = [];
                $itemNodes = $valueNode->values;
                foreach ($itemNodes as $itemNode) {
                    if (self::isMissingVariable($itemNode, $variables)) {
                        // If an array contains a missing variable, it is either coerced to
                        // null or if the item type is non-null, it considered invalid.
                        if ($itemType instanceof NonNull) {
                            // Invalid: intentionally return no value.
                            return $undefined;
                        }

                        $coercedValues[] = null;
                    } else {
                        $itemValue = self::valueFromAST($itemNode, $itemType, $variables, $schema);
                        if ($undefined === $itemValue) {
                            // Invalid: intentionally return no value.
                            return $undefined;
                        }

                        $coercedValues[] = $itemValue;
                    }
                }

                return $coercedValues;
            }

            $coercedValue = self::valueFromAST($valueNode, $itemType, $variables, $schema);
            if ($undefined === $coercedValue) {
                // Invalid: intentionally return no value.
                return $undefined;
            }

            return [$coercedValue];
        }

        if ($type instanceof InputObjectType) {
            if (! $valueNode instanceof ObjectValueNode) {
                // Invalid: intentionally return no value.
                return $undefined;
            }

            $coercedObj = [];
            $fields = $type->getFields();

            $fieldNodes = [];
            foreach ($valueNode->fields as $field) {
                $fieldNodes[$field->name->value] = $field;
            }

            foreach ($fields as $field) {
                $fieldName = $field->name;
                $fieldNode = $fieldNodes[$fieldName] ?? null;

                if ($fieldNode === null || self::isMissingVariable($fieldNode->value, $variables)) {
                    if ($field->defaultValueExists()) {
                        $coercedObj[$fieldName] = $field->defaultValue;
                    } elseif ($field->getType() instanceof NonNull) {
                        // Invalid: intentionally return no value.
                        return $undefined;
                    }

                    continue;
                }

                $fieldValue = self::valueFromAST(
                    $fieldNode->value,
                    $field->getType(),
                    $variables,
                    $schema,
                );

                if ($undefined === $fieldValue) {
                    // Invalid: intentionally return no value.
                    return $undefined;
                }

                $coercedObj[$fieldName] = $fieldValue;
            }

            return $type->parseValue($coercedObj);
        }

        if ($type instanceof EnumType) {
            try {
                return $type->parseLiteral($valueNode, $variables);
            } catch (\Throwable $error) {
                return $undefined;
            }
        }

        assert($type instanceof ScalarType, 'only remaining option');
        $typeName = $type->name;

        // Account for type loader returning a different scalar instance than
        // the built-in singleton used in field definitions. Resolve the actual
        // type from the schema to ensure the correct parseLiteral() is called.
        if ($schema !== null && Type::isBuiltInScalarName($typeName)) {
            $schemaType = $schema->getType($typeName);
            assert($schemaType instanceof ScalarType, "Schema must provide a ScalarType for built-in scalar \"{$typeName}\".");
            $type = $schemaType;
        }

        // Scalars fulfill parsing a literal value via parseLiteral().
        // Invalid values represent a failure to parse correctly, in which case
        // no value is returned.
        try {
            return $type->parseLiteral($valueNode, $variables);
        } catch (\Throwable $error) {
            return $undefined;
        }
    }

    /**
     * Returns true if the provided valueNode is a variable which is not defined
     * in the set of variables.
     *
     * @param ValueNode&Node $valueNode
     * @param array<string, mixed>|null $variables
     */
    private static function isMissingVariable(ValueNode $valueNode, ?array $variables): bool
    {
        return $valueNode instanceof VariableNode
            && ($variables === null || ! array_key_exists($valueNode->name->value, $variables));
    }

    /**
     * Produces a PHP value given a Automattic\WooCommerce\Vendor\GraphQL Value AST.
     *
     * Unlike `valueFromAST()`, no type is provided. The resulting PHP value
     * will reflect the provided Automattic\WooCommerce\Vendor\GraphQL value AST.
     *
     * | Automattic\WooCommerce\Vendor\GraphQL Value        | PHP Value     |
     * | -------------------- | ------------- |
     * | Input Object         | Assoc Array   |
     * | List                 | Array         |
     * | Boolean              | Boolean       |
     * | String               | String        |
     * | Int / Float          | Int / Float   |
     * | Enum                 | Mixed         |
     * | Null                 | null          |
     *
     * @param array<string, mixed>|null $variables
     *
     * @throws \Exception
     *
     * @return mixed
     *
     * @api
     */
    public static function valueFromASTUntyped(Node $valueNode, ?array $variables = null)
    {
        switch (true) {
            case $valueNode instanceof NullValueNode:
                return null;

            case $valueNode instanceof IntValueNode:
                return (int) $valueNode->value;

            case $valueNode instanceof FloatValueNode:
                return (float) $valueNode->value;

            case $valueNode instanceof StringValueNode:
            case $valueNode instanceof EnumValueNode:
            case $valueNode instanceof BooleanValueNode:
                return $valueNode->value;

            case $valueNode instanceof ListValueNode:
                $values = [];
                foreach ($valueNode->values as $node) {
                    $values[] = self::valueFromASTUntyped($node, $variables);
                }

                return $values;

            case $valueNode instanceof ObjectValueNode:
                $values = [];
                foreach ($valueNode->fields as $field) {
                    $values[$field->name->value] = self::valueFromASTUntyped($field->value, $variables);
                }

                return $values;

            case $valueNode instanceof VariableNode:
                $variableName = $valueNode->name->value;

                return ($variables ?? []) !== [] && isset($variables[$variableName])
                    ? $variables[$variableName]
                    : null;
        }

        throw new Error("Unexpected value kind: {$valueNode->kind}");
    }

    /**
     * Returns type definition for given AST Type node.
     *
     * @param callable(string): ?Type $typeLoader
     * @param NamedTypeNode|ListTypeNode|NonNullTypeNode $inputTypeNode
     *
     * @throws \Exception
     *
     * @api
     */
    public static function typeFromAST(callable $typeLoader, Node $inputTypeNode): ?Type
    {
        if ($inputTypeNode instanceof ListTypeNode) {
            $innerType = self::typeFromAST($typeLoader, $inputTypeNode->type);

            return $innerType === null
                ? null
                : new ListOfType($innerType);
        }

        if ($inputTypeNode instanceof NonNullTypeNode) {
            $innerType = self::typeFromAST($typeLoader, $inputTypeNode->type);
            if ($innerType === null) {
                return null;
            }

            assert($innerType instanceof NullableType, 'proven by schema validation');

            return new NonNull($innerType);
        }

        return $typeLoader($inputTypeNode->name->value);
    }

    /**
     * Returns the operation within a document by name.
     *
     * If a name is not provided, an operation is only returned if the document has exactly one.
     *
     * @api
     */
    public static function getOperationAST(DocumentNode $document, ?string $operationName = null): ?OperationDefinitionNode
    {
        $operation = null;
        foreach ($document->definitions->getIterator() as $node) {
            if (! $node instanceof OperationDefinitionNode) {
                continue;
            }

            if ($operationName === null) {
                // We found a second operation, so we bail instead of returning an ambiguous result.
                if ($operation !== null) {
                    return null;
                }

                $operation = $node;
            } elseif ($node->name instanceof NameNode && $node->name->value === $operationName) {
                return $node;
            }
        }

        return $operation;
    }

    /**
     * Provided a collection of ASTs, presumably each from different files,
     * concatenate the ASTs together into batched AST, useful for validating many
     * Automattic\WooCommerce\Vendor\GraphQL source files which together represent one conceptual application.
     *
     * @param array<DocumentNode> $documents
     *
     * @api
     */
    public static function concatAST(array $documents): DocumentNode
    {
        /** @var array<int, Node&DefinitionNode> $definitions */
        $definitions = [];
        foreach ($documents as $document) {
            foreach ($document->definitions as $definition) {
                $definitions[] = $definition;
            }
        }

        return new DocumentNode(['definitions' => new NodeList($definitions)]);
    }
}
