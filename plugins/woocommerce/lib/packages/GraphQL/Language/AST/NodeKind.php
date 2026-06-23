<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

/**
 * Holds constants of possible AST nodes.
 */
class NodeKind
{
    // constants from language/kinds.js:

    public const NAME = 'Name';

    // Document
    public const DOCUMENT = 'Document';
    public const OPERATION_DEFINITION = 'OperationDefinition';
    public const VARIABLE_DEFINITION = 'VariableDefinition';
    public const VARIABLE = 'Variable';
    public const SELECTION_SET = 'SelectionSet';
    public const FIELD = 'Field';
    public const ARGUMENT = 'Argument';

    // Fragments
    public const FRAGMENT_SPREAD = 'FragmentSpread';
    public const INLINE_FRAGMENT = 'InlineFragment';
    public const FRAGMENT_DEFINITION = 'FragmentDefinition';

    // Values
    public const INT = 'IntValue';
    public const FLOAT = 'FloatValue';
    public const STRING = 'StringValue';
    public const BOOLEAN = 'BooleanValue';
    public const ENUM = 'EnumValue';
    public const NULL = 'NullValue';
    public const LST = 'ListValue';
    public const OBJECT = 'ObjectValue';
    public const OBJECT_FIELD = 'ObjectField';

    // Directives
    public const DIRECTIVE = 'Directive';

    // Types
    public const NAMED_TYPE = 'NamedType';
    public const LIST_TYPE = 'ListType';
    public const NON_NULL_TYPE = 'NonNullType';

    // Type System Definitions
    public const SCHEMA_DEFINITION = 'SchemaDefinition';
    public const OPERATION_TYPE_DEFINITION = 'OperationTypeDefinition';

    // Type Definitions
    public const SCALAR_TYPE_DEFINITION = 'ScalarTypeDefinition';
    public const OBJECT_TYPE_DEFINITION = 'ObjectTypeDefinition';
    public const FIELD_DEFINITION = 'FieldDefinition';
    public const INPUT_VALUE_DEFINITION = 'InputValueDefinition';
    public const INTERFACE_TYPE_DEFINITION = 'InterfaceTypeDefinition';
    public const UNION_TYPE_DEFINITION = 'UnionTypeDefinition';
    public const ENUM_TYPE_DEFINITION = 'EnumTypeDefinition';
    public const ENUM_VALUE_DEFINITION = 'EnumValueDefinition';
    public const INPUT_OBJECT_TYPE_DEFINITION = 'InputObjectTypeDefinition';

    // Type Extensions
    public const SCALAR_TYPE_EXTENSION = 'ScalarTypeExtension';
    public const OBJECT_TYPE_EXTENSION = 'ObjectTypeExtension';
    public const INTERFACE_TYPE_EXTENSION = 'InterfaceTypeExtension';
    public const UNION_TYPE_EXTENSION = 'UnionTypeExtension';
    public const ENUM_TYPE_EXTENSION = 'EnumTypeExtension';
    public const INPUT_OBJECT_TYPE_EXTENSION = 'InputObjectTypeExtension';

    // Directive Definitions
    public const DIRECTIVE_DEFINITION = 'DirectiveDefinition';

    // Type System Extensions
    public const SCHEMA_EXTENSION = 'SchemaExtension';

    public const CLASS_MAP = [
        self::NAME => NameNode::class,

        // Document
        self::DOCUMENT => DocumentNode::class,
        self::OPERATION_DEFINITION => OperationDefinitionNode::class,
        self::VARIABLE_DEFINITION => VariableDefinitionNode::class,
        self::VARIABLE => VariableNode::class,
        self::SELECTION_SET => SelectionSetNode::class,
        self::FIELD => FieldNode::class,
        self::ARGUMENT => ArgumentNode::class,

        // Fragments
        self::FRAGMENT_SPREAD => FragmentSpreadNode::class,
        self::INLINE_FRAGMENT => InlineFragmentNode::class,
        self::FRAGMENT_DEFINITION => FragmentDefinitionNode::class,

        // Values
        self::INT => IntValueNode::class,
        self::FLOAT => FloatValueNode::class,
        self::STRING => StringValueNode::class,
        self::BOOLEAN => BooleanValueNode::class,
        self::ENUM => EnumValueNode::class,
        self::NULL => NullValueNode::class,
        self::LST => ListValueNode::class,
        self::OBJECT => ObjectValueNode::class,
        self::OBJECT_FIELD => ObjectFieldNode::class,

        // Directives
        self::DIRECTIVE => DirectiveNode::class,

        // Types
        self::NAMED_TYPE => NamedTypeNode::class,
        self::LIST_TYPE => ListTypeNode::class,
        self::NON_NULL_TYPE => NonNullTypeNode::class,

        // Type System Definitions
        self::SCHEMA_DEFINITION => SchemaDefinitionNode::class,
        self::OPERATION_TYPE_DEFINITION => OperationTypeDefinitionNode::class,

        // Type Definitions
        self::SCALAR_TYPE_DEFINITION => ScalarTypeDefinitionNode::class,
        self::OBJECT_TYPE_DEFINITION => ObjectTypeDefinitionNode::class,
        self::FIELD_DEFINITION => FieldDefinitionNode::class,
        self::INPUT_VALUE_DEFINITION => InputValueDefinitionNode::class,
        self::INTERFACE_TYPE_DEFINITION => InterfaceTypeDefinitionNode::class,
        self::UNION_TYPE_DEFINITION => UnionTypeDefinitionNode::class,
        self::ENUM_TYPE_DEFINITION => EnumTypeDefinitionNode::class,
        self::ENUM_VALUE_DEFINITION => EnumValueDefinitionNode::class,
        self::INPUT_OBJECT_TYPE_DEFINITION => InputObjectTypeDefinitionNode::class,

        // Type Extensions
        self::SCALAR_TYPE_EXTENSION => ScalarTypeExtensionNode::class,
        self::OBJECT_TYPE_EXTENSION => ObjectTypeExtensionNode::class,
        self::INTERFACE_TYPE_EXTENSION => InterfaceTypeExtensionNode::class,
        self::UNION_TYPE_EXTENSION => UnionTypeExtensionNode::class,
        self::ENUM_TYPE_EXTENSION => EnumTypeExtensionNode::class,
        self::INPUT_OBJECT_TYPE_EXTENSION => InputObjectTypeExtensionNode::class,

        // Directive Definitions
        self::DIRECTIVE_DEFINITION => DirectiveDefinitionNode::class,
    ];
}
