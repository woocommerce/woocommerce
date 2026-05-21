<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\MixedStore;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @see EnumValueDefinitionNode
 *
 * @phpstan-type PartialEnumValueConfig array{
 *   name?: string,
 *   value?: mixed,
 *   deprecationReason?: string|null,
 *   description?: string|null,
 *   astNode?: EnumValueDefinitionNode|null
 * }
 * @phpstan-type EnumValues iterable<string, PartialEnumValueConfig>|iterable<string, mixed>|iterable<int, string>
 * @phpstan-type EnumTypeConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   values: EnumValues|callable(): EnumValues,
 *   astNode?: EnumTypeDefinitionNode|null,
 *   extensionASTNodes?: array<EnumTypeExtensionNode>|null
 * }
 */
class EnumType extends Type implements InputType, OutputType, LeafType, NullableType, NamedType
{
    use NamedTypeImplementation;

    public ?EnumTypeDefinitionNode $astNode;

    /** @var array<EnumTypeExtensionNode> */
    public array $extensionASTNodes;

    /** @phpstan-var EnumTypeConfig */
    public array $config;

    /**
     * Lazily initialized.
     *
     * @var array<int, EnumValueDefinition>
     */
    private array $values;

    /**
     * Lazily initialized.
     *
     * @var MixedStore<EnumValueDefinition>
     */
    private MixedStore $valueLookup;

    /** @var array<string, EnumValueDefinition> */
    private array $nameLookup;

    /**
     * @phpstan-param EnumTypeConfig $config
     *
     * @throws InvariantViolation
     */
    public function __construct(array $config)
    {
        $this->name = $config['name'] ?? $this->inferName();
        $this->description = $config['description'] ?? null;
        $this->astNode = $config['astNode'] ?? null;
        $this->extensionASTNodes = $config['extensionASTNodes'] ?? [];

        $this->config = $config;
    }

    /** @throws InvariantViolation */
    public function getValue(string $name): ?EnumValueDefinition
    {
        if (! isset($this->nameLookup)) {
            $this->initializeNameLookup();
        }

        return $this->nameLookup[$name] ?? null;
    }

    /**
     * @throws InvariantViolation
     *
     * @return array<int, EnumValueDefinition>
     */
    public function getValues(): array
    {
        if (! isset($this->values)) {
            $this->values = [];

            $values = $this->config['values'];
            if (is_callable($values)) {
                $values = $values();
            }

            // We are just assuming the config option is set correctly here, validation happens in assertValid()
            foreach ($values as $name => $value) {
                if (is_string($name)) {
                    if (is_array($value)) {
                        $value += ['name' => $name, 'value' => $name];
                    } else {
                        $value = ['name' => $name, 'value' => $value];
                    }
                } elseif (is_string($value)) {
                    $value = ['name' => $value, 'value' => $value];
                } else {
                    throw new InvariantViolation("{$this->name} values must be an array with value names as keys or values.");
                }

                // @phpstan-ignore-next-line assume the config matches
                $this->values[] = new EnumValueDefinition($value);
            }
        }

        return $this->values;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws InvariantViolation
     * @throws SerializationError
     */
    public function serialize($value)
    {
        $lookup = $this->getValueLookup();
        if (isset($lookup[$value])) {
            return $lookup[$value]->name;
        }

        if ($value instanceof \BackedEnum) {
            return $value->name;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        $safeValue = Utils::printSafe($value);
        throw new SerializationError("Cannot serialize value as enum: {$safeValue}");
    }

    /**
     * @throws \InvalidArgumentException
     * @throws InvariantViolation
     *
     * @return MixedStore<EnumValueDefinition>
     */
    private function getValueLookup(): MixedStore
    {
        if (! isset($this->valueLookup)) {
            $this->valueLookup = new MixedStore();

            foreach ($this->getValues() as $value) {
                $this->valueLookup->offsetSet($value->value, $value);
            }
        }

        return $this->valueLookup;
    }

    /**
     * @throws Error
     * @throws InvariantViolation
     */
    public function parseValue($value)
    {
        if (! is_string($value)) {
            $safeValue = Utils::printSafeJson($value);
            throw new Error("Enum \"{$this->name}\" cannot represent non-string value: {$safeValue}.{$this->didYouMean($safeValue)}");
        }

        if (! isset($this->nameLookup)) {
            $this->initializeNameLookup();
        }

        if (! isset($this->nameLookup[$value])) {
            throw new Error("Value \"{$value}\" does not exist in \"{$this->name}\" enum.{$this->didYouMean($value)}");
        }

        return $this->nameLookup[$value]->value;
    }

    /**
     * @throws \JsonException
     * @throws Error
     * @throws InvariantViolation
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        if (! $valueNode instanceof EnumValueNode) {
            $valueStr = Printer::doPrint($valueNode);
            throw new Error("Enum \"{$this->name}\" cannot represent non-enum value: {$valueStr}.{$this->didYouMean($valueStr)}", $valueNode);
        }

        $name = $valueNode->value;

        if (! isset($this->nameLookup)) {
            $this->initializeNameLookup();
        }

        if (isset($this->nameLookup[$name])) {
            return $this->nameLookup[$name]->value;
        }

        $valueStr = Printer::doPrint($valueNode);
        throw new Error("Value \"{$valueStr}\" does not exist in \"{$this->name}\" enum.{$this->didYouMean($valueStr)}", $valueNode);
    }

    /**
     * @throws Error
     * @throws InvariantViolation
     */
    public function assertValid(): void
    {
        Utils::assertValidName($this->name);

        $values = $this->config['values'] ?? null; // @phpstan-ignore nullCoalesce.initializedProperty (unnecessary according to types, but can happen during runtime)
        if (! is_iterable($values) && ! is_callable($values)) {
            $notIterable = Utils::printSafe($values);
            throw new InvariantViolation("{$this->name} values must be an iterable or callable, got: {$notIterable}");
        }

        $this->getValues();
    }

    /** @throws InvariantViolation */
    private function initializeNameLookup(): void
    {
        $this->nameLookup = [];
        foreach ($this->getValues() as $value) {
            $this->nameLookup[$value->name] = $value;
        }
    }

    /** @throws InvariantViolation */
    protected function didYouMean(string $unknownValue): ?string
    {
        $suggestions = Utils::suggestionList(
            $unknownValue,
            array_map(
                static fn (EnumValueDefinition $value): string => $value->name,
                $this->getValues()
            )
        );

        return $suggestions === []
            ? null
            : ' Did you mean the enum value ' . Utils::quotedOrList($suggestions) . '?';
    }

    public function astNode(): ?EnumTypeDefinitionNode
    {
        return $this->astNode;
    }

    /** @return array<EnumTypeExtensionNode> */
    public function extensionASTNodes(): array
    {
        return $this->extensionASTNodes;
    }
}
