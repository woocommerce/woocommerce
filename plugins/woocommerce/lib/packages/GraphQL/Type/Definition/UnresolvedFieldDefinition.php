<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

/**
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 *
 * @phpstan-type DefinitionResolver callable(): (FieldDefinition|(Type&OutputType)|UnnamedFieldDefinitionConfig)
 */
class UnresolvedFieldDefinition
{
    private string $name;

    /**
     * @var callable
     *
     * @phpstan-var DefinitionResolver
     */
    private $definitionResolver;

    /** @param DefinitionResolver $definitionResolver */
    public function __construct(string $name, callable $definitionResolver)
    {
        $this->name = $name;
        $this->definitionResolver = $definitionResolver;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function resolve(): FieldDefinition
    {
        $field = ($this->definitionResolver)();

        if ($field instanceof FieldDefinition) {
            return $field;
        }

        if ($field instanceof Type) {
            return new FieldDefinition([
                'name' => $this->name,
                'type' => $field,
            ]);
        }

        return new FieldDefinition($field + ['name' => $this->name]);
    }
}
