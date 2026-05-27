<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;

/**
 * @see HasFieldsType
 */
trait HasFieldsTypeImplementation
{
    /**
     * Lazily initialized.
     *
     * @var array<string, FieldDefinition|UnresolvedFieldDefinition>
     */
    private array $fields;

    /** @throws InvariantViolation */
    private function initializeFields(): void
    {
        if (isset($this->fields)) {
            return;
        }

        $this->fields = FieldDefinition::defineFieldMap($this, $this->config['fields']);
    }

    /** @throws InvariantViolation */
    public function getField(string $name): FieldDefinition
    {
        $field = $this->findField($name);

        if ($field === null) {
            throw new InvariantViolation("Field \"{$name}\" is not defined for type \"{$this->name}\"");
        }

        return $field;
    }

    /** @throws InvariantViolation */
    public function findField(string $name): ?FieldDefinition
    {
        $this->initializeFields();

        if (! isset($this->fields[$name])) {
            return null;
        }

        $field = $this->fields[$name];
        if ($field instanceof UnresolvedFieldDefinition) {
            return $this->fields[$name] = $field->resolve();
        }

        return $field;
    }

    /** @throws InvariantViolation */
    public function hasField(string $name): bool
    {
        $this->initializeFields();

        return isset($this->fields[$name]);
    }

    /**
     * @throws InvariantViolation
     *
     * @return array<string, FieldDefinition>
     */
    public function getFields(): array
    {
        $this->initializeFields();

        foreach ($this->fields as $name => $field) {
            if ($field instanceof UnresolvedFieldDefinition) {
                $this->fields[$name] = $field->resolve();
            }
        }

        // @phpstan-ignore-next-line all field definitions are now resolved
        return $this->fields;
    }

    /** @return array<string, FieldDefinition> */
    public function getVisibleFields(): array
    {
        return array_filter(
            $this->getFields(),
            fn (FieldDefinition $fieldDefinition): bool => $fieldDefinition->isVisible()
        );
    }

    /** @throws InvariantViolation */
    public function getFieldNames(): array
    {
        $this->initializeFields();

        $visibleFieldNames = array_map(
            fn (FieldDefinition $fieldDefinition): string => $fieldDefinition->getName(),
            $this->getVisibleFields()
        );

        return array_values($visibleFieldNames);
    }
}
