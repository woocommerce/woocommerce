<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Validation;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectField;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaValidationContext;

class InputObjectCircularRefs
{
    private SchemaValidationContext $schemaValidationContext;

    /**
     * Tracks already visited types to maintain O(N) and to ensure that cycles
     * are not redundantly reported.
     *
     * @var array<string, bool>
     */
    private array $visitedTypes = [];

    /** @var array<int, InputObjectField> */
    private array $fieldPath = [];

    /**
     * Position in the type path.
     *
     * @var array<string, int>
     */
    private array $fieldPathIndexByTypeName = [];

    public function __construct(SchemaValidationContext $schemaValidationContext)
    {
        $this->schemaValidationContext = $schemaValidationContext;
    }

    /**
     * This does a straight-forward DFS to find cycles.
     * It does not terminate when a cycle was found but continues to explore
     * the graph to find all possible cycles.
     *
     * @throws InvariantViolation
     */
    public function validate(InputObjectType $inputObj): void
    {
        if (isset($this->visitedTypes[$inputObj->name])) {
            return;
        }

        $this->visitedTypes[$inputObj->name] = true;
        $this->fieldPathIndexByTypeName[$inputObj->name] = count($this->fieldPath);

        $fieldMap = $inputObj->getFields();
        foreach ($fieldMap as $field) {
            $type = $field->getType();

            if ($type instanceof NonNull) {
                $fieldType = $type->getWrappedType();

                // If the type of the field is anything else then a non-nullable input object,
                // there is no chance of an unbreakable cycle
                if ($fieldType instanceof InputObjectType) {
                    $this->fieldPath[] = $field;

                    if (! isset($this->fieldPathIndexByTypeName[$fieldType->name])) {
                        $this->validate($fieldType);
                    } else {
                        $cycleIndex = $this->fieldPathIndexByTypeName[$fieldType->name];
                        $cyclePath = array_slice($this->fieldPath, $cycleIndex);
                        $fieldNames = implode(
                            '.',
                            array_map(
                                static fn (InputObjectField $field): string => $field->name,
                                $cyclePath
                            )
                        );
                        $fieldNodes = array_map(
                            static fn (InputObjectField $field): ?InputValueDefinitionNode => $field->astNode,
                            $cyclePath
                        );

                        $this->schemaValidationContext->reportError(
                            "Cannot reference Input Object \"{$fieldType->name}\" within itself through a series of non-null fields: \"{$fieldNames}\".",
                            $fieldNodes
                        );
                    }
                }
            }

            array_pop($this->fieldPath);
        }

        unset($this->fieldPathIndexByTypeName[$inputObj->name]);
    }
}
