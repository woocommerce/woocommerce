<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Values;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * @phpstan-type QueryPlanOptions array{
 *   groupImplementorFields?: bool,
 * }
 */
class QueryPlan
{
    /**
     * Map from type names to a list of fields referenced of that type.
     *
     * @var array<string, array<string, true>>
     */
    private array $typeToFields = [];

    private Schema $schema;

    /** @var array<string, mixed> */
    private array $queryPlan = [];

    /** @var array<string, mixed> */
    private array $variableValues;

    /** @var array<string, FragmentDefinitionNode> */
    private array $fragments;

    private bool $groupImplementorFields;

    /**
     * @param iterable<FieldNode> $fieldNodes
     * @param array<string, mixed> $variableValues
     * @param array<string, FragmentDefinitionNode> $fragments
     * @param QueryPlanOptions $options
     *
     * @throws \Exception
     * @throws Error
     * @throws InvariantViolation
     */
    public function __construct(ObjectType $parentType, Schema $schema, iterable $fieldNodes, array $variableValues, array $fragments, array $options = [])
    {
        $this->schema = $schema;
        $this->variableValues = $variableValues;
        $this->fragments = $fragments;
        $this->groupImplementorFields = $options['groupImplementorFields'] ?? false;
        $this->analyzeQueryPlan($parentType, $fieldNodes);
    }

    /** @return array<string, mixed> */
    public function queryPlan(): array
    {
        return $this->queryPlan;
    }

    /** @return array<int, string> */
    public function getReferencedTypes(): array
    {
        return array_keys($this->typeToFields);
    }

    public function hasType(string $type): bool
    {
        return isset($this->typeToFields[$type]);
    }

    /**
     * TODO return array<string, true>.
     *
     * @return array<int, string>
     */
    public function getReferencedFields(): array
    {
        $allFields = [];
        foreach ($this->typeToFields as $fields) {
            foreach ($fields as $field => $_) {
                $allFields[$field] = true;
            }
        }

        return array_keys($allFields);
    }

    public function hasField(string $field): bool
    {
        foreach ($this->typeToFields as $fields) {
            if (array_key_exists($field, $fields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * TODO return array<string, true>.
     *
     * @return array<int, string>
     */
    public function subFields(string $typename): array
    {
        return array_keys($this->typeToFields[$typename] ?? []);
    }

    /**
     * @param iterable<FieldNode> $fieldNodes
     *
     * @throws \Exception
     * @throws Error
     * @throws InvariantViolation
     */
    private function analyzeQueryPlan(ObjectType $parentType, iterable $fieldNodes): void
    {
        $queryPlan = [];
        $implementors = [];
        foreach ($fieldNodes as $fieldNode) {
            if ($fieldNode->selectionSet === null) {
                continue;
            }

            $type = Type::getNamedType(
                $parentType->getField($fieldNode->name->value)->getType()
            );

            $subfields = $this->analyzeSelectionSet($fieldNode->selectionSet, $type, $implementors);
            $queryPlan = $this->arrayMergeDeep($queryPlan, $subfields);
        }

        if ($this->groupImplementorFields) {
            $this->queryPlan = ['fields' => $queryPlan];

            if ($implementors !== []) {
                $this->queryPlan['implementors'] = $implementors;
            }
        } else {
            $this->queryPlan = $queryPlan;
        }
    }

    /**
     * @param Type&NamedType $parentType
     * @param array<string, mixed> $implementors
     *
     * @throws \Exception
     * @throws Error
     * @throws InvariantViolation
     *
     * @return array<mixed>
     */
    private function analyzeSelectionSet(SelectionSetNode $selectionSet, Type $parentType, array &$implementors): array
    {
        $fields = [];
        $implementors = [];
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fieldName = $selection->name->value;

                if ($fieldName === Introspection::TYPE_NAME_FIELD_NAME) {
                    continue;
                }

                assert($parentType instanceof HasFieldsType, 'ensured by query validation');

                $type = $parentType->getField($fieldName);
                $selectionType = $type->getType();

                $subImplementors = [];
                $nestedSelectionSet = $selection->selectionSet;
                $subfields = $nestedSelectionSet === null
                    ? []
                    : $this->analyzeSubFields($selectionType, $nestedSelectionSet, $subImplementors);

                $fields[$fieldName] = [
                    'type' => $selectionType,
                    'fields' => $subfields,
                    'args' => Values::getArgumentValues($type, $selection, $this->variableValues),
                ];
                if ($this->groupImplementorFields && $subImplementors !== []) {
                    $fields[$fieldName]['implementors'] = $subImplementors;
                }
            } elseif ($selection instanceof FragmentSpreadNode) {
                $spreadName = $selection->name->value;
                $fragment = $this->fragments[$spreadName] ?? null;
                if ($fragment === null) {
                    continue;
                }

                $type = $this->schema->getType($fragment->typeCondition->name->value);
                assert($type instanceof Type, 'ensured by query validation');

                $subfields = $this->analyzeSubFields($type, $fragment->selectionSet);
                $fields = $this->mergeFields($parentType, $type, $fields, $subfields, $implementors);
            } elseif ($selection instanceof InlineFragmentNode) {
                $typeCondition = $selection->typeCondition;
                $type = $typeCondition === null
                    ? $parentType
                    : $this->schema->getType($typeCondition->name->value);
                assert($type instanceof Type, 'ensured by query validation');

                $subfields = $this->analyzeSubFields($type, $selection->selectionSet);
                $fields = $this->mergeFields($parentType, $type, $fields, $subfields, $implementors);
            }
        }

        $parentTypeName = $parentType->name();

        // TODO evaluate if this line is really necessary.
        // It causes abstract types to appear in getReferencedTypes() even if they do not have any fields directly referencing them.
        $this->typeToFields[$parentTypeName] ??= [];
        foreach ($fields as $fieldName => $_) {
            $this->typeToFields[$parentTypeName][$fieldName] = true;
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $implementors
     *
     * @throws \Exception
     * @throws Error
     *
     * @return array<mixed>
     */
    private function analyzeSubFields(Type $type, SelectionSetNode $selectionSet, array &$implementors = []): array
    {
        $type = Type::getNamedType($type);

        return $type instanceof ObjectType || $type instanceof AbstractType
            ? $this->analyzeSelectionSet($selectionSet, $type, $implementors)
            : [];
    }

    /**
     * @param Type&NamedType $parentType
     * @param Type&NamedType $type
     * @param array<mixed> $fields
     * @param array<mixed> $subfields
     * @param array<string, mixed> $implementors
     *
     * @return array<mixed>
     */
    private function mergeFields(Type $parentType, Type $type, array $fields, array $subfields, array &$implementors): array
    {
        if ($this->groupImplementorFields && $parentType instanceof AbstractType && ! $type instanceof AbstractType) {
            $name = $type->name;
            assert(is_string($name));

            $implementors[$name] = [
                'type' => $type,
                'fields' => $this->arrayMergeDeep(
                    $implementors[$name]['fields'] ?? [],
                    array_diff_key($subfields, $fields)
                ),
            ];

            $fields = $this->arrayMergeDeep(
                $fields,
                array_intersect_key($subfields, $fields)
            );
        } else {
            $fields = $this->arrayMergeDeep($subfields, $fields);
        }

        return $fields;
    }

    /**
     * Merges nested arrays, but handles non array values differently from array_merge_recursive.
     * While array_merge_recursive tries to merge non-array values, in this implementation they will be overwritten.
     *
     * @see https://stackoverflow.com/a/25712428
     *
     * @param array<mixed> $array1
     * @param array<mixed> $array2
     *
     * @return array<mixed>
     */
    private function arrayMergeDeep(array $array1, array $array2): array
    {
        foreach ($array2 as $key => &$value) {
            if (is_numeric($key)) {
                if (! in_array($value, $array1, true)) {
                    $array1[] = $value;
                }
            } elseif (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->arrayMergeDeep($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }
}
