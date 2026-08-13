<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ListTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NamedTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeList;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NonNullTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\UnionTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\UnionTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\DirectiveLocation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Argument;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumValueDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ImplementingType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectField;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Validation\InputObjectCircularRefs;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\TypeComparators;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

class SchemaValidationContext
{
    /** @var list<Error> */
    private array $errors = [];

    private Schema $schema;

    private InputObjectCircularRefs $inputObjectCircularRefs;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->inputObjectCircularRefs = new InputObjectCircularRefs($this);
    }

    /** @return list<Error> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function validateRootTypes(): void
    {
        if ($this->schema->getQueryType() === null) {
            $this->reportError('Query root type must be provided.', $this->schema->astNode);
        }

        // Triggers a type error if wrong
        $this->schema->getMutationType();
        $this->schema->getSubscriptionType();
    }

    /** @param array<Node|null>|Node|null $nodes */
    public function reportError(string $message, $nodes = null): void
    {
        $nodes = array_filter(is_array($nodes) ? $nodes : [$nodes]);
        $this->addError(new Error($message, $nodes));
    }

    private function addError(Error $error): void
    {
        $this->errors[] = $error;
    }

    /** @throws InvariantViolation */
    public function validateDirectives(): void
    {
        $this->validateDirectiveDefinitions();

        // Validate directives that are used on the schema
        $this->validateDirectivesAtLocation(
            $this->getDirectives($this->schema),
            DirectiveLocation::SCHEMA
        );
    }

    /** @throws InvariantViolation */
    public function validateDirectiveDefinitions(): void
    {
        $directiveDefinitions = [];

        $directives = $this->schema->getDirectives();
        foreach ($directives as $directive) {
            // Ensure all directives are in fact Automattic\WooCommerce\Vendor\GraphQL directives.
            // @phpstan-ignore-next-line The generic type says this should not happen, but a user may use it wrong nonetheless
            if (! $directive instanceof Directive) {
                $notDirective = Utils::printSafe($directive);
                // @phpstan-ignore-next-line The generic type says this should not happen, but a user may use it wrong nonetheless
                $nodes = is_object($directive) && property_exists($directive, 'astNode')
                    ? $directive->astNode
                    : null;

                $this->reportError(
                    "Expected directive but got: {$notDirective}.",
                    $nodes
                );
                continue;
            }

            $existingDefinitions = $directiveDefinitions[$directive->name] ?? [];
            $existingDefinitions[] = $directive;
            $directiveDefinitions[$directive->name] = $existingDefinitions;

            // Ensure they are named correctly.
            $this->validateName($directive);

            // TODO: Ensure proper locations.

            $argNames = [];
            foreach ($directive->args as $arg) {
                // Ensure they are named correctly.
                $this->validateName($arg);

                $argName = $arg->name;

                if (isset($argNames[$argName])) {
                    $this->reportError(
                        "Argument @{$directive->name}({$argName}:) can only be defined once.",
                        $this->getAllDirectiveArgNodes($directive, $argName)
                    );
                    continue;
                }

                $argNames[$argName] = true;

                // Ensure the type is an input type.
                // @phpstan-ignore-next-line necessary until PHP supports union types
                if (! Type::isInputType($arg->getType())) {
                    $type = Utils::printSafe($arg->getType());
                    $this->reportError(
                        "The type of @{$directive->name}({$argName}:) must be Input Type but got: {$type}.",
                        $this->getDirectiveArgTypeNode($directive, $argName)
                    );
                }
            }
        }

        foreach ($directiveDefinitions as $directiveName => $directiveList) {
            if (count($directiveList) > 1) {
                $nodes = [];
                foreach ($directiveList as $dir) {
                    if (isset($dir->astNode)) {
                        $nodes[] = $dir->astNode;
                    }
                }

                $this->reportError(
                    "Directive @{$directiveName} defined multiple times.",
                    $nodes
                );
            }
        }
    }

    /** @param (Type&NamedType)|Directive|FieldDefinition|EnumValueDefinition|InputObjectField|Argument $object */
    private function validateName(object $object): void
    {
        // Ensure names are valid, however introspection types opt out.
        $error = Utils::isValidNameError($object->name, $object->astNode);
        if (
            $error === null
            || ($object instanceof Type && Introspection::isIntrospectionType($object))
        ) {
            return;
        }

        $this->addError($error);
    }

    /** @return array<int, InputValueDefinitionNode> */
    private function getAllDirectiveArgNodes(Directive $directive, string $argName): array
    {
        $astNode = $directive->astNode;
        if ($astNode === null) {
            return [];
        }

        $matchingSubnodes = [];
        foreach ($astNode->arguments as $subNode) {
            if ($subNode->name->value === $argName) {
                $matchingSubnodes[] = $subNode;
            }
        }

        return $matchingSubnodes;
    }

    /** @return NamedTypeNode|ListTypeNode|NonNullTypeNode|null */
    private function getDirectiveArgTypeNode(Directive $directive, string $argName): ?TypeNode
    {
        $argNode = $this->getAllDirectiveArgNodes($directive, $argName)[0] ?? null;

        return $argNode === null
            ? null
            : $argNode->type;
    }

    /** @throws InvariantViolation */
    public function validateTypes(): void
    {
        $typeMap = $this->schema->getTypeMap();
        foreach ($typeMap as $type) {
            // Ensure all provided types are in fact Automattic\WooCommerce\Vendor\GraphQL type.
            // @phpstan-ignore-next-line The generic type says this should not happen, but a user may use it wrong nonetheless
            if (! $type instanceof NamedType) {
                $notNamedType = Utils::printSafe($type);
                // @phpstan-ignore-next-line The generic type says this should not happen, but a user may use it wrong nonetheless
                $node = $type instanceof Type
                    ? $type->astNode
                    : null;

                $this->reportError("Expected Automattic\WooCommerce\Vendor\GraphQL named type but got: {$notNamedType}.", $node);
                continue;
            }

            $this->validateName($type);

            if ($type instanceof ObjectType) {
                $this->validateFields($type);
                $this->validateInterfaces($type);
                $this->validateDirectivesAtLocation($this->getDirectives($type), DirectiveLocation::OBJECT);
            } elseif ($type instanceof InterfaceType) {
                $this->validateFields($type);
                $this->validateInterfaces($type);
                $this->validateDirectivesAtLocation($this->getDirectives($type), DirectiveLocation::IFACE);
            } elseif ($type instanceof UnionType) {
                $this->validateUnionMembers($type);
                $this->validateDirectivesAtLocation($this->getDirectives($type), DirectiveLocation::UNION);
            } elseif ($type instanceof EnumType) {
                $this->validateEnumValues($type);
                $this->validateDirectivesAtLocation($this->getDirectives($type), DirectiveLocation::ENUM);
            } elseif ($type instanceof InputObjectType) {
                $this->validateInputFields($type);
                $this->validateDirectivesAtLocation($this->getDirectives($type), DirectiveLocation::INPUT_OBJECT);
                $this->inputObjectCircularRefs->validate($type);
            } else {
                assert($type instanceof ScalarType, 'only remaining option');
                $this->validateDirectivesAtLocation($this->getDirectives($type), DirectiveLocation::SCALAR);
            }
        }
    }

    /**
     * @param NodeList<DirectiveNode> $directives
     *
     * @throws InvariantViolation
     */
    private function validateDirectivesAtLocation(NodeList $directives, string $location): void
    {
        /** @var array<string, array<int, DirectiveNode>> $potentiallyDuplicateDirectives */
        $potentiallyDuplicateDirectives = [];
        $schema = $this->schema;
        foreach ($directives as $directiveNode) {
            $directiveName = $directiveNode->name->value;

            // Ensure directive used is also defined
            $schemaDirective = $schema->getDirective($directiveName);
            if ($schemaDirective === null) {
                $this->reportError("No directive @{$directiveName} defined.", $directiveNode);
                continue;
            }

            if (! in_array($location, $schemaDirective->locations, true)) {
                $this->reportError(
                    "Directive @{$directiveName} not allowed at {$location} location.",
                    array_filter([$directiveNode, $schemaDirective->astNode])
                );
            }

            if (! $schemaDirective->isRepeatable) {
                $potentiallyDuplicateDirectives[$directiveName][] = $directiveNode;
            }
        }

        foreach ($potentiallyDuplicateDirectives as $directiveName => $directiveList) {
            if (count($directiveList) > 1) {
                $this->reportError("Non-repeatable directive @{$directiveName} used more than once at the same location.", $directiveList);
            }
        }
    }

    /**
     * @param ObjectType|InterfaceType $type
     *
     * @throws InvariantViolation
     */
    private function validateFields(Type $type): void
    {
        $fieldMap = $type->getFields();

        if ($fieldMap === []) {
            $this->reportError(
                "Type {$type->name} must define one or more fields.",
                $this->getAllNodes($type)
            );
        }

        foreach ($fieldMap as $fieldName => $field) {
            $this->validateName($field);

            $fieldNodes = $this->getAllFieldNodes($type, $fieldName);
            if (count($fieldNodes) > 1) {
                $this->reportError("Field {$type->name}.{$fieldName} can only be defined once.", $fieldNodes);
                continue;
            }

            $fieldType = $field->getType();
            // @phpstan-ignore-next-line not statically provable until we can use union types
            if (! Type::isOutputType($fieldType)) {
                $safeFieldType = Utils::printSafe($fieldType);
                $this->reportError(
                    "The type of {$type->name}.{$fieldName} must be Output Type but got: {$safeFieldType}.",
                    $this->getFieldTypeNode($type, $fieldName)
                );
            }

            $this->validateTypeIsSingleton($fieldType, "{$type->name}.{$fieldName}");

            $argNames = [];
            foreach ($field->args as $arg) {
                $argName = $arg->name;
                $argPath = "{$type->name}.{$fieldName}({$argName}:)";

                $this->validateName($arg);

                if (isset($argNames[$argName])) {
                    $this->reportError(
                        "Field argument {$argPath} can only be defined once.",
                        $this->getAllFieldArgNodes($type, $fieldName, $argName)
                    );
                }

                $argNames[$argName] = true;

                $argType = $arg->getType();

                // @phpstan-ignore-next-line the type of $arg->getType() says it is an input type, but it might not always be true
                if (! Type::isInputType($argType)) {
                    $safeType = Utils::printSafe($argType);
                    $this->reportError(
                        "The type of {$argPath} must be Input Type but got: {$safeType}.",
                        $this->getFieldArgTypeNode($type, $fieldName, $argName)
                    );
                }

                $this->validateTypeIsSingleton($argType, $argPath);

                if (isset($arg->astNode->directives)) {
                    $this->validateDirectivesAtLocation($arg->astNode->directives, DirectiveLocation::ARGUMENT_DEFINITION);
                }
            }

            if (isset($field->astNode->directives)) {
                $this->validateDirectivesAtLocation($field->astNode->directives, DirectiveLocation::FIELD_DEFINITION);
            }
        }
    }

    /**
     * @param Schema|ObjectType|InterfaceType|UnionType|EnumType|InputObjectType|Directive $obj
     *
     * @return list<SchemaDefinitionNode|SchemaExtensionNode>|list<ObjectTypeDefinitionNode|ObjectTypeExtensionNode>|list<InterfaceTypeDefinitionNode|InterfaceTypeExtensionNode>|list<UnionTypeDefinitionNode|UnionTypeExtensionNode>|list< EnumTypeDefinitionNode|EnumTypeExtensionNode>|list<InputObjectTypeDefinitionNode|InputObjectTypeExtensionNode>|list<DirectiveDefinitionNode>
     */
    private function getAllNodes(object $obj): array
    {
        $astNode = $obj->astNode;

        if ($obj instanceof Schema) {
            $extensionNodes = $obj->extensionASTNodes;
        } elseif ($obj instanceof Directive) {
            $extensionNodes = [];
        } else {
            $extensionNodes = $obj->extensionASTNodes;
        }

        $allNodes = $astNode === null
            ? []
            : [$astNode];
        foreach ($extensionNodes as $extensionNode) {
            $allNodes[] = $extensionNode;
        }

        return $allNodes;
    }

    /**
     * @param ObjectType|InterfaceType $type
     *
     * @return list<FieldDefinitionNode>
     */
    private function getAllFieldNodes(Type $type, string $fieldName): array
    {
        $allNodes = array_filter([$type->astNode, ...$type->extensionASTNodes]);

        $matchingFieldNodes = [];

        foreach ($allNodes as $node) {
            foreach ($node->fields as $field) {
                if ($field->name->value === $fieldName) {
                    $matchingFieldNodes[] = $field;
                }
            }
        }

        return $matchingFieldNodes;
    }

    /**
     * @param ObjectType|InterfaceType $type
     *
     * @return NamedTypeNode|ListTypeNode|NonNullTypeNode|null
     */
    private function getFieldTypeNode(Type $type, string $fieldName): ?TypeNode
    {
        $fieldNode = $this->getFieldNode($type, $fieldName);

        return $fieldNode === null
            ? null
            : $fieldNode->type;
    }

    /** @param ObjectType|InterfaceType $type */
    private function getFieldNode(Type $type, string $fieldName): ?FieldDefinitionNode
    {
        $nodes = $this->getAllFieldNodes($type, $fieldName);

        return $nodes[0] ?? null;
    }

    /**
     * @param ObjectType|InterfaceType $type
     *
     * @return array<int, InputValueDefinitionNode>
     */
    private function getAllFieldArgNodes(Type $type, string $fieldName, string $argName): array
    {
        $argNodes = [];
        $fieldNode = $this->getFieldNode($type, $fieldName);
        if ($fieldNode !== null) {
            foreach ($fieldNode->arguments as $node) {
                if ($node->name->value === $argName) {
                    $argNodes[] = $node;
                }
            }
        }

        return $argNodes;
    }

    /**
     * @param ObjectType|InterfaceType $type
     *
     * @return NamedTypeNode|ListTypeNode|NonNullTypeNode|null
     */
    private function getFieldArgTypeNode(Type $type, string $fieldName, string $argName): ?TypeNode
    {
        $fieldArgNode = $this->getFieldArgNode($type, $fieldName, $argName);

        return $fieldArgNode === null
            ? null
            : $fieldArgNode->type;
    }

    /** @param ObjectType|InterfaceType $type */
    private function getFieldArgNode(Type $type, string $fieldName, string $argName): ?InputValueDefinitionNode
    {
        $nodes = $this->getAllFieldArgNodes($type, $fieldName, $argName);

        return $nodes[0] ?? null;
    }

    /**
     * @param ObjectType|InterfaceType $type
     *
     * @throws InvariantViolation
     */
    private function validateInterfaces(ImplementingType $type): void
    {
        $ifaceTypeNames = [];
        foreach ($type->getInterfaces() as $interface) {
            // @phpstan-ignore-next-line The generic type says this should not happen, but a user may use it wrong nonetheless
            if (! $interface instanceof InterfaceType) {
                $notInterface = Utils::printSafe($interface);
                $this->reportError(
                    "Type {$type->name} must only implement Interface types, it cannot implement {$notInterface}.",
                    $this->getImplementsInterfaceNode($type, $interface)
                );
                continue;
            }

            if ($type === $interface) {
                $this->reportError(
                    "Type {$type->name} cannot implement itself because it would create a circular reference.",
                    $this->getImplementsInterfaceNode($type, $interface)
                );
                continue;
            }

            if (isset($ifaceTypeNames[$interface->name])) {
                $this->reportError(
                    "Type {$type->name} can only implement {$interface->name} once.",
                    $this->getAllImplementsInterfaceNodes($type, $interface)
                );
                continue;
            }

            $ifaceTypeNames[$interface->name] = true;

            $this->validateTypeImplementsAncestors($type, $interface);
            $this->validateTypeImplementsInterface($type, $interface);
        }
    }

    /**
     * @param Schema|(Type&NamedType) $object
     *
     * @return NodeList<DirectiveNode>
     */
    private function getDirectives(object $object): NodeList
    {
        $directives = [];
        /**
         * Excluding directiveNode, since $object is not Directive.
         *
         * @var SchemaDefinitionNode|SchemaExtensionNode|ObjectTypeDefinitionNode|ObjectTypeExtensionNode|InterfaceTypeDefinitionNode|InterfaceTypeExtensionNode|UnionTypeDefinitionNode|UnionTypeExtensionNode|EnumTypeDefinitionNode|EnumTypeExtensionNode|InputObjectTypeDefinitionNode|InputObjectTypeExtensionNode $node
         */
        // @phpstan-ignore-next-line union types are not pervasive
        foreach ($this->getAllNodes($object) as $node) {
            foreach ($node->directives as $directive) {
                $directives[] = $directive;
            }
        }

        return new NodeList($directives);
    }

    /**
     * @param ObjectType|InterfaceType $type
     * @param Type&NamedType $shouldBeInterface
     */
    private function getImplementsInterfaceNode(ImplementingType $type, NamedType $shouldBeInterface): ?NamedTypeNode
    {
        $nodes = $this->getAllImplementsInterfaceNodes($type, $shouldBeInterface);

        return $nodes[0] ?? null;
    }

    /**
     * @param ObjectType|InterfaceType $type
     * @param Type&NamedType $shouldBeInterface
     *
     * @return list<NamedTypeNode>
     */
    private function getAllImplementsInterfaceNodes(ImplementingType $type, NamedType $shouldBeInterface): array
    {
        $allNodes = array_filter([$type->astNode, ...$type->extensionASTNodes]);

        $shouldBeInterfaceName = $shouldBeInterface->name;
        $matchingInterfaceNodes = [];

        foreach ($allNodes as $node) {
            foreach ($node->interfaces as $interface) {
                if ($interface->name->value === $shouldBeInterfaceName) {
                    $matchingInterfaceNodes[] = $interface;
                }
            }
        }

        return $matchingInterfaceNodes;
    }

    /**
     * @param ObjectType|InterfaceType $type
     *
     * @throws InvariantViolation
     */
    private function validateTypeImplementsInterface(ImplementingType $type, InterfaceType $iface): void
    {
        $typeFieldMap = $type->getFields();
        $ifaceFieldMap = $iface->getFields();

        foreach ($ifaceFieldMap as $fieldName => $ifaceField) {
            $typeField = $typeFieldMap[$fieldName] ?? null;

            if ($typeField === null) {
                $this->reportError(
                    "Interface field {$iface->name}.{$fieldName} expected but {$type->name} does not provide it.",
                    array_merge(
                        [$this->getFieldNode($iface, $fieldName)],
                        $this->getAllNodes($type)
                    )
                );
                continue;
            }

            $typeFieldType = $typeField->getType();
            $ifaceFieldType = $ifaceField->getType();
            if (! TypeComparators::isTypeSubTypeOf($this->schema, $typeFieldType, $ifaceFieldType)) {
                $this->reportError(
                    "Interface field {$iface->name}.{$fieldName} expects type {$ifaceFieldType} but {$type->name}.{$fieldName} is type {$typeFieldType}.",
                    [
                        $this->getFieldTypeNode($iface, $fieldName),
                        $this->getFieldTypeNode($type, $fieldName),
                    ]
                );
            }

            foreach ($ifaceField->args as $ifaceArg) {
                $argName = $ifaceArg->name;
                $typeArg = $typeField->getArg($argName);

                if ($typeArg === null) {
                    $this->reportError(
                        "Interface field argument {$iface->name}.{$fieldName}({$argName}:) expected but {$type->name}.{$fieldName} does not provide it.",
                        [
                            $this->getFieldArgNode($iface, $fieldName, $argName),
                            $this->getFieldNode($type, $fieldName),
                        ]
                    );
                    continue;
                }

                $ifaceArgType = $ifaceArg->getType();
                $typeArgType = $typeArg->getType();
                if (! TypeComparators::isEqualType($ifaceArgType, $typeArgType)) {
                    $this->reportError(
                        "Interface field argument {$iface->name}.{$fieldName}({$argName}:) expects type {$ifaceArgType} but {$type->name}.{$fieldName}({$argName}:) is type {$typeArgType}.",
                        [
                            $this->getFieldArgTypeNode($iface, $fieldName, $argName),
                            $this->getFieldArgTypeNode($type, $fieldName, $argName),
                        ]
                    );
                }

                // TODO: validate default values?
            }

            foreach ($typeField->args as $typeArg) {
                $argName = $typeArg->name;
                $ifaceArg = $ifaceField->getArg($argName);

                if ($typeArg->isRequired() && $ifaceArg === null) {
                    $this->reportError(
                        "Object field {$type->name}.{$fieldName} includes required argument {$argName} that is missing from the Interface field {$iface->name}.{$fieldName}.",
                        [
                            $this->getFieldArgNode($type, $fieldName, $argName),
                            $this->getFieldNode($iface, $fieldName),
                        ]
                    );
                }
            }
        }
    }

    /** @param ObjectType|InterfaceType $type */
    private function validateTypeImplementsAncestors(ImplementingType $type, InterfaceType $iface): void
    {
        $typeInterfaces = $type->getInterfaces();
        foreach ($iface->getInterfaces() as $transitive) {
            if (! in_array($transitive, $typeInterfaces, true)) {
                $this->reportError(
                    $transitive === $type
                        ? "Type {$type->name} cannot implement {$iface->name} because it would create a circular reference."
                        : "Type {$type->name} must implement {$transitive->name} because it is implemented by {$iface->name}.",
                    array_merge(
                        $this->getAllImplementsInterfaceNodes($iface, $transitive),
                        $this->getAllImplementsInterfaceNodes($type, $iface)
                    )
                );
            }
        }
    }

    /** @throws InvariantViolation */
    private function validateUnionMembers(UnionType $union): void
    {
        $memberTypes = $union->getTypes();

        if ($memberTypes === []) {
            $this->reportError(
                "Union type {$union->name} must define one or more member types.",
                $this->getAllNodes($union)
            );
        }

        $includedTypeNames = [];

        foreach ($memberTypes as $memberType) {
            // @phpstan-ignore-next-line The generic type says this should not happen, but a user may use it wrong nonetheless
            if (! $memberType instanceof ObjectType) {
                $notObjectType = Utils::printSafe($memberType);
                $this->reportError(
                    "Union type {$union->name} can only include Object types, it cannot include {$notObjectType}.",
                    $this->getUnionMemberTypeNodes($union, $notObjectType)
                );
                continue;
            }

            if (isset($includedTypeNames[$memberType->name])) {
                $this->reportError(
                    "Union type {$union->name} can only include type {$memberType->name} once.",
                    $this->getUnionMemberTypeNodes($union, $memberType->name)
                );
                continue;
            }

            $includedTypeNames[$memberType->name] = true;
        }
    }

    /** @return list<NamedTypeNode> */
    private function getUnionMemberTypeNodes(UnionType $union, string $typeName): array
    {
        $allNodes = array_filter([$union->astNode, ...$union->extensionASTNodes]);

        $types = [];
        foreach ($allNodes as $node) {
            foreach ($node->types as $type) {
                if ($type->name->value === $typeName) {
                    $types[] = $type;
                }
            }
        }

        return $types;
    }

    /** @throws InvariantViolation */
    private function validateEnumValues(EnumType $enumType): void
    {
        $enumValues = $enumType->getValues();

        if ($enumValues === []) {
            $this->reportError(
                "Enum type {$enumType->name} must define one or more values.",
                $this->getAllNodes($enumType)
            );
        }

        foreach ($enumValues as $enumValue) {
            $valueName = $enumValue->name;

            // Ensure valid name.
            $this->validateName($enumValue);
            if (in_array($valueName, ['true', 'false', 'null'], true)) {
                $this->reportError(
                    "Enum type {$enumType->name} cannot include value: {$valueName}.",
                    $enumValue->astNode
                );
            }

            // Ensure valid directives
            if (isset($enumValue->astNode, $enumValue->astNode->directives)) {
                $this->validateDirectivesAtLocation(
                    $enumValue->astNode->directives,
                    DirectiveLocation::ENUM_VALUE
                );
            }
        }
    }

    /** @throws InvariantViolation */
    private function validateInputFields(InputObjectType $inputObj): void
    {
        $fieldMap = $inputObj->getFields();

        if ($fieldMap === []) {
            $this->reportError(
                "Input Object type {$inputObj->name} must define one or more fields.",
                $this->getAllNodes($inputObj)
            );
        }

        // Ensure the arguments are valid
        foreach ($fieldMap as $fieldName => $field) {
            // Ensure they are named correctly.
            $this->validateName($field);

            // TODO: Ensure they are unique per field.

            // Ensure the type is an input type.
            $type = $field->getType();
            // @phpstan-ignore-next-line The generic type says this should not happen, but a user may use it wrong nonetheless
            if (! Type::isInputType($type)) {
                $notInputType = Utils::printSafe($type);
                $this->reportError(
                    "The type of {$inputObj->name}.{$fieldName} must be Input Type but got: {$notInputType}.",
                    $field->astNode->type ?? null
                );
            }

            // Ensure valid directives
            if (isset($field->astNode, $field->astNode->directives)) {
                $this->validateDirectivesAtLocation(
                    $field->astNode->directives,
                    DirectiveLocation::INPUT_FIELD_DEFINITION
                );
            }
        }
    }

    /** @throws InvariantViolation */
    private function validateTypeIsSingleton(Type $type, string $path): void
    {
        $schemaConfig = $this->schema->getConfig();
        if (! isset($schemaConfig->typeLoader)) {
            return;
        }

        $namedType = Type::getNamedType($type);
        if ($namedType->isBuiltInType()) {
            return;
        }

        $name = $namedType->name;
        if ($namedType !== ($schemaConfig->typeLoader)($name)) {
            throw new InvariantViolation(static::duplicateType($this->schema, $path, $name));
        }
    }

    public static function duplicateType(Schema $schema, string $path, string $name): string
    {
        $hint = isset($schema->getConfig()->typeLoader)
            ? 'Ensure the type loader returns the same instance. '
            : '';

        return "Found duplicate type in schema at {$path}: {$name}. {$hint}See https://webonyx.github.io/graphql-php/type-definitions/#type-registry.";
    }
}
