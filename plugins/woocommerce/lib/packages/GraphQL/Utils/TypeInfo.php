<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ArgumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ListValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectFieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Argument;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\CompositeType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\HasFieldsType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ImplementingType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\WrappingType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

class TypeInfo
{
    private Schema $schema;

    /** @var array<int, Type|null> */
    private array $typeStack = [];

    /** @var array<int, (CompositeType&Type)|null> */
    private array $parentTypeStack = [];

    /** @var array<int, (InputType&Type)|null> */
    private array $inputTypeStack = [];

    /** @var array<int, FieldDefinition|null> */
    private array $fieldDefStack = [];

    /** @var array<int, mixed> */
    private array $defaultValueStack = [];

    private ?Directive $directive = null;

    private ?Argument $argument = null;

    /** @var mixed */
    private $enumValue;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /** @return array<int, (CompositeType&Type)|null> */
    public function getParentTypeStack(): array
    {
        return $this->parentTypeStack;
    }

    /** @return array<int, FieldDefinition|null> */
    public function getFieldDefStack(): array
    {
        return $this->fieldDefStack;
    }

    /**
     * Given root type scans through all fields to find nested types.
     *
     * Returns array where keys are for type name
     * and value contains corresponding type instance.
     *
     * Example output:
     * [
     *     'String' => $instanceOfStringType,
     *     'MyType' => $instanceOfMyType,
     *     ...
     * ]
     *
     * @param (Type&NamedType)|(Type&WrappingType) $type
     * @param array<string, Type&NamedType> $typeMap
     *
     * @throws InvariantViolation
     */
    public static function extractTypes(Type $type, array &$typeMap): void
    {
        if ($type instanceof WrappingType) {
            self::extractTypes($type->getInnermostType(), $typeMap);

            return;
        }

        $name = $type->name;
        assert(is_string($name));

        if (isset($typeMap[$name])) {
            if ($typeMap[$name] !== $type) {
                throw new InvariantViolation("Schema must contain unique named types but contains multiple types named \"{$type}\" (see https://webonyx.github.io/graphql-php/type-definitions/#type-registry).");
            }

            return;
        }

        $typeMap[$name] = $type;

        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $member) {
                self::extractTypes($member, $typeMap);
            }

            return;
        }

        if ($type instanceof InputObjectType) {
            foreach ($type->getFields() as $field) {
                $fieldType = $field->getType();
                assert($fieldType instanceof NamedType || $fieldType instanceof WrappingType);
                self::extractTypes($fieldType, $typeMap);
            }

            return;
        }

        if ($type instanceof ImplementingType) {
            foreach ($type->getInterfaces() as $interface) {
                self::extractTypes($interface, $typeMap);
            }
        }

        if ($type instanceof HasFieldsType) {
            foreach ($type->getFields() as $field) {
                foreach ($field->args as $arg) {
                    $argType = $arg->getType();
                    assert($argType instanceof NamedType || $argType instanceof WrappingType);
                    self::extractTypes($argType, $typeMap);
                }

                $fieldType = $field->getType();
                assert($fieldType instanceof NamedType || $fieldType instanceof WrappingType);
                self::extractTypes($fieldType, $typeMap);
            }
        }
    }

    /**
     * @param array<string, Type&NamedType> $typeMap
     *
     * @throws InvariantViolation
     */
    public static function extractTypesFromDirectives(Directive $directive, array &$typeMap): void
    {
        foreach ($directive->args as $arg) {
            $argType = $arg->getType();
            assert($argType instanceof NamedType || $argType instanceof WrappingType);
            self::extractTypes($argType, $typeMap);
        }
    }

    /** @return (Type&InputType)|null */
    public function getParentInputType(): ?InputType
    {
        return $this->inputTypeStack[count($this->inputTypeStack) - 2] ?? null;
    }

    public function getArgument(): ?Argument
    {
        return $this->argument;
    }

    /** @return mixed */
    public function getEnumValue()
    {
        return $this->enumValue;
    }

    /**
     * @throws \Exception
     * @throws InvariantViolation
     */
    public function enter(Node $node): void
    {
        $schema = $this->schema;

        // Note: many of the types below are explicitly typed as "mixed" to drop
        // any assumptions of a valid schema to ensure runtime types are properly
        // checked before continuing since TypeInfo is used as part of validation
        // which occurs before guarantees of schema and document validity.
        switch (true) {
            case $node instanceof SelectionSetNode:
                $namedType = Type::getNamedType($this->getType());
                $this->parentTypeStack[] = Type::isCompositeType($namedType) ? $namedType : null;
                break;

            case $node instanceof FieldNode:
                $parentType = $this->getParentType();

                $fieldDef = $parentType === null
                    ? null
                    : self::getFieldDefinition($schema, $parentType, $node);

                $fieldType = $fieldDef === null
                    ? null
                    : $fieldDef->getType();

                $this->fieldDefStack[] = $fieldDef;
                $this->typeStack[] = $fieldType;
                break;

            case $node instanceof DirectiveNode:
                $this->directive = $schema->getDirective($node->name->value);
                break;

            case $node instanceof OperationDefinitionNode:
                if ($node->operation === 'query') {
                    $type = $schema->getQueryType();
                } elseif ($node->operation === 'mutation') {
                    $type = $schema->getMutationType();
                } else {
                    // Only other option
                    $type = $schema->getSubscriptionType();
                }

                $this->typeStack[] = Type::isOutputType($type)
                    ? $type
                    : null;
                break;

            case $node instanceof InlineFragmentNode:
            case $node instanceof FragmentDefinitionNode:
                $typeConditionNode = $node->typeCondition;
                $outputType = $typeConditionNode === null
                    ? Type::getNamedType($this->getType())
                    : AST::typeFromAST([$schema, 'getType'], $typeConditionNode);
                $this->typeStack[] = Type::isOutputType($outputType) ? $outputType : null;
                break;

            case $node instanceof VariableDefinitionNode:
                $inputType = AST::typeFromAST([$schema, 'getType'], $node->type);
                $this->inputTypeStack[] = Type::isInputType($inputType) ? $inputType : null; // push
                break;

            case $node instanceof ArgumentNode:
                $fieldOrDirective = $this->getDirective() ?? $this->getFieldDef();
                $argDef = null;
                $argType = null;
                if ($fieldOrDirective !== null) {
                    foreach ($fieldOrDirective->args as $arg) {
                        if ($arg->name === $node->name->value) {
                            $argDef = $arg;
                            $argType = $arg->getType();
                        }
                    }
                }

                $this->argument = $argDef;
                $this->defaultValueStack[] = $argDef !== null && $argDef->defaultValueExists()
                    ? $argDef->defaultValue
                    : Utils::undefined();
                $this->inputTypeStack[] = Type::isInputType($argType) ? $argType : null;
                break;

            case $node instanceof ListValueNode:
                $type = $this->getInputType();
                $listType = $type instanceof NonNull
                    ? $type->getWrappedType()
                    : $type;
                $itemType = $listType instanceof ListOfType
                    ? $listType->getWrappedType()
                    : $listType;
                // List positions never have a default value.
                $this->defaultValueStack[] = Utils::undefined();
                $this->inputTypeStack[] = Type::isInputType($itemType) ? $itemType : null;
                break;

            case $node instanceof ObjectFieldNode:
                $objectType = Type::getNamedType($this->getInputType());
                $inputField = null;
                $inputFieldType = null;
                if ($objectType instanceof InputObjectType) {
                    $tmp = $objectType->getFields();
                    $inputField = $tmp[$node->name->value] ?? null;
                    $inputFieldType = $inputField === null
                        ? null
                        : $inputField->getType();
                }

                $this->defaultValueStack[] = $inputField !== null && $inputField->defaultValueExists()
                    ? $inputField->defaultValue
                    : Utils::undefined();
                $this->inputTypeStack[] = Type::isInputType($inputFieldType)
                    ? $inputFieldType
                    : null;
                break;

            case $node instanceof EnumValueNode:
                $enumType = Type::getNamedType($this->getInputType());

                $this->enumValue = $enumType instanceof EnumType
                    ? $enumType->getValue($node->value)
                    : null;
                break;
        }
    }

    public function getType(): ?Type
    {
        return $this->typeStack[count($this->typeStack) - 1] ?? null;
    }

    /** @return (CompositeType&Type)|null */
    public function getParentType(): ?CompositeType
    {
        return $this->parentTypeStack[count($this->parentTypeStack) - 1] ?? null;
    }

    /**
     * Not exactly the same as the executor's definition of getFieldDef, in this
     * statically evaluated environment we do not always have an Object type,
     * and need to handle Interface and Union types.
     *
     * @throws InvariantViolation
     */
    private static function getFieldDefinition(Schema $schema, Type $parentType, FieldNode $fieldNode): ?FieldDefinition
    {
        $name = $fieldNode->name->value;
        $schemaMeta = Introspection::schemaMetaFieldDef();
        if ($name === $schemaMeta->name && $schema->getQueryType() === $parentType) {
            return $schemaMeta;
        }

        $typeMeta = Introspection::typeMetaFieldDef();
        if ($name === $typeMeta->name && $schema->getQueryType() === $parentType) {
            return $typeMeta;
        }

        $typeNameMeta = Introspection::typeNameMetaFieldDef();
        if ($name === $typeNameMeta->name && $parentType instanceof CompositeType) {
            return $typeNameMeta;
        }

        if (
            $parentType instanceof ObjectType
            || $parentType instanceof InterfaceType
        ) {
            return $parentType->findField($name);
        }

        return null;
    }

    public function getDirective(): ?Directive
    {
        return $this->directive;
    }

    public function getFieldDef(): ?FieldDefinition
    {
        return $this->fieldDefStack[count($this->fieldDefStack) - 1] ?? null;
    }

    /** @return mixed any value is possible */
    public function getDefaultValue()
    {
        return $this->defaultValueStack[count($this->defaultValueStack) - 1] ?? null;
    }

    /** @return (InputType&Type)|null */
    public function getInputType(): ?InputType
    {
        return $this->inputTypeStack[count($this->inputTypeStack) - 1] ?? null;
    }

    public function leave(Node $node): void
    {
        switch ($node->kind) {
            case NodeKind::SELECTION_SET:
                array_pop($this->parentTypeStack);
                break;

            case NodeKind::FIELD:
                array_pop($this->fieldDefStack);
                array_pop($this->typeStack);
                break;

            case NodeKind::DIRECTIVE:
                $this->directive = null;
                break;

            case NodeKind::OPERATION_DEFINITION:
            case NodeKind::INLINE_FRAGMENT:
            case NodeKind::FRAGMENT_DEFINITION:
                array_pop($this->typeStack);
                break;

            case NodeKind::VARIABLE_DEFINITION:
                array_pop($this->inputTypeStack);
                break;

            case NodeKind::ARGUMENT:
                $this->argument = null;
                array_pop($this->defaultValueStack);
                array_pop($this->inputTypeStack);
                break;

            case NodeKind::LST:
            case NodeKind::OBJECT_FIELD:
                array_pop($this->defaultValueStack);
                array_pop($this->inputTypeStack);
                break;

            case NodeKind::ENUM:
                $this->enumValue = null;
                break;
        }
    }
}
