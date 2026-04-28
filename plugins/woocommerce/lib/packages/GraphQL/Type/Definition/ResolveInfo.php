<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Values;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * Structure containing information useful for field resolution process.
 *
 * Passed as 4th argument to every field resolver. See [docs on field resolving (data fetching)](data-fetching.md).
 *
 * @phpstan-import-type QueryPlanOptions from QueryPlan
 *
 * @phpstan-type Path list<string|int>
 */
class ResolveInfo
{
    /**
     * The definition of the field being resolved.
     *
     * @api
     */
    public FieldDefinition $fieldDefinition;

    /**
     * The name of the field being resolved.
     *
     * @api
     */
    public string $fieldName;

    /**
     * Expected return type of the field being resolved.
     *
     * @api
     */
    public Type $returnType;

    /**
     * AST of all nodes referencing this field in the query.
     *
     * @api
     *
     * @var \ArrayObject<int, FieldNode>
     */
    public \ArrayObject $fieldNodes;

    /**
     * Parent type of the field being resolved.
     *
     * @api
     */
    public ObjectType $parentType;

    /**
     * Path to this field from the very root value. When fields are aliased, the path includes aliases.
     *
     * @api
     *
     * @var list<string|int>
     *
     * @phpstan-var Path
     */
    public array $path;

    /**
     * Path to this field from the very root value. This will never include aliases.
     *
     * @api
     *
     * @var list<string|int>
     *
     * @phpstan-var Path
     */
    public array $unaliasedPath;

    /**
     * Instance of a schema used for execution.
     *
     * @api
     */
    public Schema $schema;

    /**
     * AST of all fragments defined in query.
     *
     * @api
     *
     * @var array<string, FragmentDefinitionNode>
     */
    public array $fragments = [];

    /**
     * Root value passed to query execution.
     *
     * @api
     *
     * @var mixed
     */
    public $rootValue;

    /**
     * AST of operation definition node (query, mutation).
     *
     * @api
     */
    public OperationDefinitionNode $operation;

    /**
     * Array of variables passed to query execution.
     *
     * @api
     *
     * @var array<string, mixed>
     */
    public array $variableValues = [];

    /**
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param array<string, FragmentDefinitionNode> $fragments
     * @param mixed|null $rootValue
     * @param array<string, mixed> $variableValues
     * @param list<string|int> $unaliasedPath
     *
     * @phpstan-param Path $path
     * @phpstan-param Path $unaliasedPath
     */
    public function __construct(
        FieldDefinition $fieldDefinition,
        \ArrayObject $fieldNodes,
        ObjectType $parentType,
        array $path,
        Schema $schema,
        array $fragments,
        $rootValue,
        OperationDefinitionNode $operation,
        array $variableValues,
        array $unaliasedPath = []
    ) {
        $this->fieldDefinition = $fieldDefinition;
        $this->fieldName = $fieldDefinition->name;
        $this->returnType = $fieldDefinition->getType();
        $this->fieldNodes = $fieldNodes;
        $this->parentType = $parentType;
        $this->path = $path;
        $this->unaliasedPath = $unaliasedPath;
        $this->schema = $schema;
        $this->fragments = $fragments;
        $this->rootValue = $rootValue;
        $this->operation = $operation;
        $this->variableValues = $variableValues;
    }

    /**
     * Returns names of all fields selected in query for `$this->fieldName` up to `$depth` levels.
     *
     * Example:
     * {
     *   root {
     *     id
     *     nested {
     *       nested1
     *       nested2 {
     *         nested3
     *       }
     *     }
     *   }
     * }
     *
     * Given this ResolveInfo instance is a part of root field resolution, and $depth === 1,
     * this method will return:
     * [
     *     'id' => true,
     *     'nested' => [
     *         'nested1' => true,
     *         'nested2' => true,
     *     ],
     * ]
     *
     * This method does not consider conditional typed fragments.
     * Use it with care for fields of interface and union types.
     *
     * @param int $depth How many levels to include in the output beyond the first
     *
     * @return array<string, mixed>
     *
     * @api
     */
    public function getFieldSelection(int $depth = 0): array
    {
        $fields = [];

        foreach ($this->fieldNodes as $fieldNode) {
            $selectionSet = $fieldNode->selectionSet;
            if ($selectionSet !== null) {
                $fields = array_merge_recursive(
                    $fields,
                    $this->foldSelectionSet($selectionSet, $depth)
                );
            }
        }

        return $fields;
    }

    /**
     * Returns names and args of all fields selected in query for `$this->fieldName` up to `$depth` levels, including aliases.
     *
     * The result maps original field names to a map of selections for that field, including aliases.
     * For each of those selections, you can find the following keys:
     * - "args" contains the passed arguments for this field/alias (not on an union inline fragment)
     * - "type" contains the related Type instance found (will be the same for all aliases of a field)
     * - "selectionSet" contains potential nested fields of this field/alias (only on ObjectType). The structure is recursive from here.
     * - "unions" contains potential object types contained in an UnionType (only on UnionType). The structure is recursive from here and will go through the selectionSet of the object types.
     *
     * Example:
     * {
     *   root {
     *     id
     *     nested {
     *      nested1(myArg: 1)
     *      nested1Bis: nested1
     *     }
     *     alias1: nested {
     *       nested1(myArg: 2, mySecondAg: "test")
     *     }
     *     myUnion(myArg: 3) {
     *       ...on Nested {
     *         nested1(myArg: 4)
     *       }
     *       ...on MyCustomObject {
     *         nested3
     *       }
     *     }
     *   }
     * }
     *
     * Given this ResolveInfo instance is a part of root field resolution,
     * $depth === 1,
     * and fields "nested" represents an ObjectType named "Nested",
     * this method will return:
     * [
     *     'id' => [
     *         'id' => [
     *              'args' => [],
     *              'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\IntType Object ( ... )),
     *         ],
     *     ],
     *     'nested' => [
     *         'nested' => [
     *             'args' => [],
     *             'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType Object ( ... )),
     *             'selectionSet' => [
     *                 'nested1' => [
     *                     'nested1' => [
     *                          'args' => [
     *                              'myArg' => 1,
     *                          ],
     *                          'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\StringType Object ( ... )),
     *                      ],
     *                      'nested1Bis' => [
     *                          'args' => [],
     *                          'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\StringType Object ( ... )),
     *                      ],
     *                 ],
     *             ],
     *         ],
     *     ],
     *     'alias1' => [
     *         'alias1' => [
     *             'args' => [],
     *             'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType Object ( ... )),
     *             'selectionSet' => [
     *                 'nested1' => [
     *                     'nested1' => [
     *                          'args' => [
     *                              'myArg' => 2,
     *                              'mySecondAg' => "test",
     *                          ],
     *                          'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\StringType Object ( ... )),
     *                      ],
     *                 ],
     *             ],
     *         ],
     *     ],
     *     'myUnion' => [
     *         'myUnion' => [
     *              'args' => [
     *                  'myArg' => 3,
     *              ],
     *              'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType Object ( ... )),
     *              'unions' => [
     *                  'Nested' => [
     *                      'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType Object ( ... )),
     *                      'selectionSet' => [
     *                          'nested1' => [
     *                              'nested1' => [
     *                                  'args' => [
     *                                      'myArg' => 4,
     *                                  ],
     *                                  'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\StringType Object ( ... )),
     *                              ],
     *                          ],
     *                      ],
     *                  ],
     *                  'MyCustomObject' => [
     *                       'type' => Automattic\WooCommerce\Vendor\GraphQL\Tests\Type\TestClasses\MyCustomType Object ( ... )),
     *                       'selectionSet' => [
     *                           'nested3' => [
     *                               'nested3' => [
     *                                   'args' => [],
     *                                   'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\StringType Object ( ... )),
     *                               ],
     *                           ],
     *                       ],
     *                   ],
     *              ],
     *          ],
     *      ],
     * ]
     *
     * @param int $depth How many levels to include in the output beyond the first
     *
     * @throws \Exception
     * @throws Error
     * @throws InvariantViolation
     *
     * @return array<string, mixed>
     *
     * @api
     */
    public function getFieldSelectionWithAliases(int $depth = 0): array
    {
        $fields = [];

        foreach ($this->fieldNodes as $fieldNode) {
            $selectionSet = $fieldNode->selectionSet;
            if ($selectionSet !== null) {
                $field = $this->parentType->getField($fieldNode->name->value);
                $fieldType = $field->getType();

                $fields = array_merge_recursive(
                    $fields,
                    $this->foldSelectionWithAlias($selectionSet, $depth, $fieldType)
                );
            }
        }

        return $fields;
    }

    /**
     * @param QueryPlanOptions $options
     *
     * @throws \Exception
     * @throws Error
     * @throws InvariantViolation
     */
    public function lookAhead(array $options = []): QueryPlan
    {
        return new QueryPlan(
            $this->parentType,
            $this->schema,
            $this->fieldNodes,
            $this->variableValues,
            $this->fragments,
            $options
        );
    }

    /** @return array<string, bool> */
    private function foldSelectionSet(SelectionSetNode $selectionSet, int $descend): array
    {
        /** @var array<string, bool> $fields */
        $fields = [];

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fields[$selection->name->value] = $descend > 0 && $selection->selectionSet !== null
                    ? array_merge_recursive(
                        $fields[$selection->name->value] ?? [],
                        $this->foldSelectionSet($selection->selectionSet, $descend - 1)
                    )
                    : true;
            } elseif ($selection instanceof FragmentSpreadNode) {
                $spreadName = $selection->name->value;
                $fragment = $this->fragments[$spreadName] ?? null;
                if ($fragment === null) {
                    continue;
                }

                $fields = array_merge_recursive(
                    $this->foldSelectionSet($fragment->selectionSet, $descend),
                    $fields
                );
            } elseif ($selection instanceof InlineFragmentNode) {
                $fields = array_merge_recursive(
                    $this->foldSelectionSet($selection->selectionSet, $descend),
                    $fields
                );
            }
        }

        return $fields;
    }

    /**
     * @throws \Exception
     * @throws Error
     * @throws InvariantViolation
     *
     * @return array<string>
     */
    private function foldSelectionWithAlias(SelectionSetNode $selectionSet, int $descend, Type $parentType): array
    {
        /** @var array<string, bool> $fields */
        $fields = [];

        if ($parentType instanceof WrappingType) {
            $parentType = $parentType->getInnermostType();
        }

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fieldName = $selection->name->value;
                $aliasName = $selection->alias->value ?? $fieldName;

                if ($fieldName === Introspection::TYPE_NAME_FIELD_NAME) {
                    continue;
                }
                assert($parentType instanceof HasFieldsType, 'ensured by query validation');

                $aliasInfo = &$fields[$fieldName][$aliasName];

                $fieldDef = $parentType->getField($fieldName);

                $aliasInfo['args'] = Values::getArgumentValues($fieldDef, $selection, $this->variableValues);

                $fieldType = $fieldDef->getType();

                $namedFieldType = $fieldType;
                if ($namedFieldType instanceof WrappingType) {
                    $namedFieldType = $namedFieldType->getInnermostType();
                }

                $aliasInfo['type'] = $namedFieldType;

                if ($descend <= 0) {
                    continue;
                }

                $nestedSelectionSet = $selection->selectionSet;
                if ($nestedSelectionSet === null) {
                    continue;
                }

                if ($namedFieldType instanceof UnionType) {
                    $aliasInfo['unions'] = $this->foldSelectionWithAlias($nestedSelectionSet, $descend, $fieldType);
                    continue;
                }

                $aliasInfo['selectionSet'] = $this->foldSelectionWithAlias($nestedSelectionSet, $descend - 1, $fieldType);
            } elseif ($selection instanceof FragmentSpreadNode) {
                $spreadName = $selection->name->value;
                $fragment = $this->fragments[$spreadName] ?? null;
                if ($fragment === null) {
                    continue;
                }

                $fieldType = $this->schema->getType($fragment->typeCondition->name->value);
                assert($fieldType instanceof Type, 'ensured by query validation');

                $fields = array_merge_recursive(
                    $this->foldSelectionWithAlias($fragment->selectionSet, $descend, $fieldType),
                    $fields
                );
            } elseif ($selection instanceof InlineFragmentNode) {
                $typeCondition = $selection->typeCondition;
                $fieldType = $typeCondition === null
                    ? $parentType
                    : $this->schema->getType($typeCondition->name->value);
                assert($fieldType instanceof Type, 'ensured by query validation');

                if ($parentType instanceof UnionType) {
                    assert($fieldType instanceof NamedType, 'ensured by query validation');
                    $fieldTypeInfo = &$fields[$fieldType->name()];
                    $fieldTypeInfo['type'] = $fieldType;
                    $fieldTypeInfo['selectionSet'] = $this->foldSelectionWithAlias($selection->selectionSet, $descend, $fieldType);
                    continue;
                }

                $fields = array_merge_recursive(
                    $this->foldSelectionWithAlias($selection->selectionSet, $descend, $fieldType),
                    $fields
                );
            }
        }

        return $fields;
    }
}
