<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Executor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @see Executor
 *
 * @phpstan-import-type FieldResolver from Executor
 * @phpstan-import-type ArgsMapper from Executor
 * @phpstan-import-type ArgumentListConfig from Argument
 *
 * @phpstan-type FieldType (Type&OutputType)|callable(): (Type&OutputType)
 * @phpstan-type ComplexityFn callable(int, array<string, mixed>): int
 * @phpstan-type VisibilityFn callable(): bool
 * @phpstan-type FieldDefinitionConfig array{
 *     name: string,
 *     type: FieldType,
 *     resolve?: FieldResolver|null,
 *     args?: ArgumentListConfig|null,
 *     argsMapper?: ArgsMapper|null,
 *     description?: string|null,
 *     visible?: VisibilityFn|bool,
 *     deprecationReason?: string|null,
 *     astNode?: FieldDefinitionNode|null,
 *     complexity?: ComplexityFn|null
 * }
 * @phpstan-type UnnamedFieldDefinitionConfig array{
 *     type: FieldType,
 *     resolve?: FieldResolver|null,
 *     args?: ArgumentListConfig|null,
 *     argsMapper?: ArgsMapper|null,
 *     description?: string|null,
 *     visible?: VisibilityFn|bool,
 *     deprecationReason?: string|null,
 *     astNode?: FieldDefinitionNode|null,
 *     complexity?: ComplexityFn|null
 * }
 * @phpstan-type FieldsConfig iterable<mixed>|callable(): iterable<mixed>
 */
/*
 * TODO check if newer versions of PHPStan can handle the full definition, it currently crashes when it is used
 * @phpstan-type EagerListEntry FieldDefinitionConfig|(Type&OutputType)
 * @phpstan-type EagerMapEntry UnnamedFieldDefinitionConfig|FieldDefinition
 * @phpstan-type FieldsList iterable<EagerListEntry|(callable(): EagerListEntry)>
 * @phpstan-type FieldsMap iterable<string, EagerMapEntry|(callable(): EagerMapEntry)>
 * @phpstan-type FieldsIterable FieldsList|FieldsMap
 * @phpstan-type FieldsConfig FieldsIterable|(callable(): FieldsIterable)
 */
class FieldDefinition
{
    public string $name;

    /** @var array<int, Argument> */
    public array $args;

    /**
     * Callback to transform args to value object.
     *
     * @var callable|null
     *
     * @phpstan-var ArgsMapper|null
     */
    public $argsMapper;

    /**
     * Callback for resolving field value given parent value.
     *
     * @var callable|null
     *
     * @phpstan-var FieldResolver|null
     */
    public $resolveFn;

    public ?string $description;

    /**
     * @var callable|bool
     *
     * @phpstan-var VisibilityFn|bool
     */
    public $visible;

    public ?string $deprecationReason;

    public ?FieldDefinitionNode $astNode;

    /**
     * @var callable|null
     *
     * @phpstan-var ComplexityFn|null
     */
    public $complexityFn;

    /**
     * Original field definition config.
     *
     * @phpstan-var FieldDefinitionConfig
     */
    public array $config;

    /** @var Type&OutputType */
    private Type $type;

    /** @param FieldDefinitionConfig $config */
    public function __construct(array $config)
    {
        $this->name = $config['name'];
        $this->resolveFn = $config['resolve'] ?? null;
        $this->args = isset($config['args'])
            ? Argument::listFromConfig($config['args'])
            : [];
        $this->argsMapper = $config['argsMapper'] ?? null;
        $this->description = $config['description'] ?? null;
        $this->visible = $config['visible'] ?? true;
        $this->deprecationReason = $config['deprecationReason'] ?? null;
        $this->astNode = $config['astNode'] ?? null;
        $this->complexityFn = $config['complexity'] ?? null;

        $this->config = $config;
    }

    /**
     * @param ObjectType|InterfaceType $parentType
     * @param callable|iterable $fields
     *
     * @phpstan-param FieldsConfig $fields
     *
     * @throws InvariantViolation
     *
     * @return array<string, self|UnresolvedFieldDefinition>
     */
    public static function defineFieldMap(Type $parentType, $fields): array
    {
        if (is_callable($fields)) {
            $fields = $fields();
        }

        if (! is_iterable($fields)) {
            throw new InvariantViolation("{$parentType->name} fields must be an iterable or a callable which returns such an iterable.");
        }

        $map = [];
        foreach ($fields as $maybeName => $field) {
            if (is_array($field)) {
                if (! isset($field['name'])) {
                    if (! is_string($maybeName)) {
                        throw new InvariantViolation("{$parentType->name} fields must be an associative array with field names as keys or a function which returns such an array.");
                    }

                    $field['name'] = $maybeName;
                }

                // @phpstan-ignore-next-line PHPStan won't let us define the whole type
                $fieldDef = new self($field);
            } elseif ($field instanceof self) {
                $fieldDef = $field;
            } elseif (is_callable($field)) {
                if (! is_string($maybeName)) {
                    throw new InvariantViolation("{$parentType->name} lazy fields must be an associative array with field names as keys.");
                }

                $fieldDef = new UnresolvedFieldDefinition($maybeName, $field);
            } elseif ($field instanceof Type) {
                // @phpstan-ignore-next-line PHPStan won't let us define the whole type
                $fieldDef = new self([
                    'name' => $maybeName,
                    'type' => $field,
                ]);
            } else {
                $invalidFieldConfig = Utils::printSafe($field);
                throw new InvariantViolation("{$parentType->name}.{$maybeName} field config must be an array, but got: {$invalidFieldConfig}");
            }

            $map[$fieldDef->getName()] = $fieldDef;
        }

        return $map;
    }

    public function getArg(string $name): ?Argument
    {
        foreach ($this->args as $arg) {
            if ($arg->name === $name) {
                return $arg;
            }
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return Type&OutputType */
    public function getType(): Type
    {
        return $this->type ??= Schema::resolveType($this->config['type']);
    }

    public function isVisible(): bool
    {
        if (is_bool($this->visible)) {
            return $this->visible;
        }

        return $this->visible = ($this->visible)();
    }

    public function isDeprecated(): bool
    {
        return (bool) $this->deprecationReason;
    }

    /**
     * @param Type&NamedType $parentType
     *
     * @throws InvariantViolation
     */
    public function assertValid(Type $parentType): void
    {
        $error = Utils::isValidNameError($this->name);
        if ($error !== null) {
            throw new InvariantViolation("{$parentType->name}.{$this->name}: {$error->getMessage()}");
        }

        $type = Type::getNamedType($this->getType());

        if (! $type instanceof OutputType) {
            $safeType = Utils::printSafe($this->type);
            throw new InvariantViolation("{$parentType->name}.{$this->name} field type must be Output Type but got: {$safeType}.");
        }

        // @phpstan-ignore-next-line unnecessary according to types, but can happen during runtime
        if ($this->resolveFn !== null && ! is_callable($this->resolveFn)) {
            $safeResolveFn = Utils::printSafe($this->resolveFn);
            throw new InvariantViolation("{$parentType->name}.{$this->name} field resolver must be a function if provided, but got: {$safeResolveFn}.");
        }

        foreach ($this->args as $fieldArgument) {
            $fieldArgument->assertValid($this, $type);
        }
    }
}
