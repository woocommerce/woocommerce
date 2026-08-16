<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @phpstan-import-type UnnamedInputObjectFieldConfig from InputObjectField
 *
 * @phpstan-type EagerFieldConfig InputObjectField|(Type&InputType)|UnnamedInputObjectFieldConfig
 * @phpstan-type LazyFieldConfig callable(): EagerFieldConfig
 * @phpstan-type FieldConfig EagerFieldConfig|LazyFieldConfig
 * @phpstan-type ParseValueFn callable(array<string, mixed>): mixed
 * @phpstan-type InputObjectConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   isOneOf?: bool|null,
 *   fields: iterable<FieldConfig>|callable(): iterable<FieldConfig>,
 *   parseValue?: ParseValueFn|null,
 *   astNode?: InputObjectTypeDefinitionNode|null,
 *   extensionASTNodes?: array<InputObjectTypeExtensionNode>|null
 * }
 */
class InputObjectType extends Type implements InputType, NullableType, NamedType
{
    use NamedTypeImplementation;

    public bool $isOneOf;

    /**
     * Lazily initialized.
     *
     * @var array<string, InputObjectField>
     */
    private array $fields;

    /** @var ParseValueFn|null */
    private $parseValue;

    public ?InputObjectTypeDefinitionNode $astNode;

    /** @var array<InputObjectTypeExtensionNode> */
    public array $extensionASTNodes;

    /** @phpstan-var InputObjectConfig */
    public array $config;

    /**
     * @phpstan-param InputObjectConfig $config
     *
     * @throws InvariantViolation
     * @throws InvariantViolation
     */
    public function __construct(array $config)
    {
        $this->name = $config['name'] ?? $this->inferName();
        $this->description = $config['description'] ?? null;
        $this->isOneOf = $config['isOneOf'] ?? false;
        // $this->fields is initialized lazily
        $this->parseValue = $config['parseValue'] ?? null;
        $this->astNode = $config['astNode'] ?? null;
        $this->extensionASTNodes = $config['extensionASTNodes'] ?? [];

        $this->config = $config;
    }

    /** @throws InvariantViolation */
    public function getField(string $name): InputObjectField
    {
        $field = $this->findField($name);

        if ($field === null) {
            throw new InvariantViolation("Field \"{$name}\" is not defined for type \"{$this->name}\"");
        }

        return $field;
    }

    /** @throws InvariantViolation */
    public function findField(string $name): ?InputObjectField
    {
        if (! isset($this->fields)) {
            $this->initializeFields();
        }

        return $this->fields[$name] ?? null;
    }

    /** @throws InvariantViolation */
    public function hasField(string $name): bool
    {
        if (! isset($this->fields)) {
            $this->initializeFields();
        }

        return isset($this->fields[$name]);
    }

    /** Returns true if this is a oneOf input object type. */
    public function isOneOf(): bool
    {
        return $this->isOneOf;
    }

    /**
     * @throws InvariantViolation
     *
     * @return array<string, InputObjectField>
     */
    public function getFields(): array
    {
        if (! isset($this->fields)) {
            $this->initializeFields();
        }

        return $this->fields;
    }

    /** @throws InvariantViolation */
    protected function initializeFields(): void
    {
        $fields = $this->config['fields'];
        if (is_callable($fields)) {
            $fields = $fields();
        }

        $this->fields = [];
        foreach ($fields as $nameOrIndex => $field) {
            $this->initializeField($nameOrIndex, $field);
        }
    }

    /**
     * @param string|int $nameOrIndex
     *
     * @phpstan-param FieldConfig $field
     *
     * @throws InvariantViolation
     */
    protected function initializeField($nameOrIndex, $field): void
    {
        if (is_callable($field)) {
            $field = $field();
        }
        assert($field instanceof Type || is_array($field) || $field instanceof InputObjectField);

        if ($field instanceof Type) {
            $field = ['type' => $field];
        }
        assert(is_array($field) || $field instanceof InputObjectField); // @phpstan-ignore-line TODO remove when using actual union types

        if (is_array($field)) {
            $field['name'] ??= $nameOrIndex;

            if (! is_string($field['name'])) {
                throw new InvariantViolation("{$this->name} fields must be an associative array with field names as keys, an array of arrays with a name attribute, or a callable which returns one of those.");
            }

            $field = new InputObjectField($field); // @phpstan-ignore-line array type is wrongly inferred
        }
        assert($field instanceof InputObjectField); // @phpstan-ignore-line TODO remove when using actual union types

        $this->fields[$field->name] = $field;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     * Should throw an exception with a client-friendly message on invalid values, @see ClientAware.
     *
     * @param array<string, mixed> $value
     *
     * @return mixed
     */
    public function parseValue(array $value)
    {
        if (isset($this->parseValue)) {
            return ($this->parseValue)($value);
        }

        return $value;
    }

    /**
     * Validates type config and throws if one of the type options is invalid.
     * Note: this method is shallow, it won't validate object fields and their arguments.
     *
     * @throws Error
     * @throws InvariantViolation
     */
    public function assertValid(): void
    {
        Utils::assertValidName($this->name);

        $fields = $this->config['fields'] ?? null; // @phpstan-ignore nullCoalesce.initializedProperty (unnecessary according to types, but can happen during runtime)
        if (is_callable($fields)) {
            $fields = $fields();
        }

        if (! is_iterable($fields)) {
            $invalidFields = Utils::printSafe($fields);
            throw new InvariantViolation("{$this->name} fields must be an iterable or a callable which returns an iterable, got: {$invalidFields}.");
        }

        $resolvedFields = $this->getFields();

        foreach ($resolvedFields as $field) {
            $field->assertValid($this);
        }

        // Additional validation for oneOf input objects
        if ($this->isOneOf()) {
            $this->validateOneOfConstraints($resolvedFields);
        }
    }

    /**
     * Validates that oneOf input object constraints are met.
     *
     * @param array<string, InputObjectField> $fields
     *
     * @throws InvariantViolation
     */
    private function validateOneOfConstraints(array $fields): void
    {
        if (count($fields) === 0) {
            throw new InvariantViolation("OneOf input object type {$this->name} must define one or more fields.");
        }

        foreach ($fields as $fieldName => $field) {
            $fieldType = $field->getType();

            // OneOf fields must be nullable (not wrapped in NonNull)
            if ($fieldType instanceof NonNull) {
                throw new InvariantViolation("OneOf input object type {$this->name} field {$fieldName} must be nullable.");
            }

            // OneOf fields cannot have default values
            if ($field->defaultValueExists()) {
                throw new InvariantViolation("OneOf input object type {$this->name} field {$fieldName} cannot have a default value.");
            }
        }
    }

    public function astNode(): ?InputObjectTypeDefinitionNode
    {
        return $this->astNode;
    }

    /** @return array<InputObjectTypeExtensionNode> */
    public function extensionASTNodes(): array
    {
        return $this->extensionASTNodes;
    }
}
