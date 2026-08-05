<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @phpstan-type ArgumentType (Type&InputType)|callable(): (Type&InputType)
 * @phpstan-type UnnamedArgumentConfig array{
 *     name?: string,
 *     type: ArgumentType,
 *     defaultValue?: mixed,
 *     description?: string|null,
 *     deprecationReason?: string|null,
 *     astNode?: InputValueDefinitionNode|null
 * }
 * @phpstan-type ArgumentConfig array{
 *     name: string,
 *     type: ArgumentType,
 *     defaultValue?: mixed,
 *     description?: string|null,
 *     deprecationReason?: string|null,
 *     astNode?: InputValueDefinitionNode|null
 * }
 * @phpstan-type ArgumentListConfig iterable<ArgumentConfig|ArgumentType>|iterable<UnnamedArgumentConfig>
 */
class Argument
{
    public string $name;

    /** @var mixed */
    public $defaultValue;

    public ?string $description;

    public ?string $deprecationReason;

    /** @var Type&InputType */
    private Type $type;

    public ?InputValueDefinitionNode $astNode;

    /** @phpstan-var ArgumentConfig */
    public array $config;

    /** @phpstan-param ArgumentConfig $config */
    public function __construct(array $config)
    {
        $this->name = $config['name'];
        $this->defaultValue = $config['defaultValue'] ?? null;
        $this->description = $config['description'] ?? null;
        $this->deprecationReason = $config['deprecationReason'] ?? null;
        // Do nothing for type, it is lazy loaded in getType()
        $this->astNode = $config['astNode'] ?? null;

        $this->config = $config;
    }

    /**
     * @phpstan-param ArgumentListConfig $config
     *
     * @return array<int, self>
     */
    public static function listFromConfig(iterable $config): array
    {
        $list = [];

        foreach ($config as $name => $argConfig) {
            if (! is_array($argConfig)) {
                $argConfig = ['type' => $argConfig];
            }

            /** @phpstan-var ArgumentConfig $argConfigWithName */
            $argConfigWithName = $argConfig + ['name' => $name];

            $list[] = new self($argConfigWithName);
        }

        return $list;
    }

    /** @return Type&InputType */
    public function getType(): Type
    {
        if (! isset($this->type)) {
            $this->type = Schema::resolveType($this->config['type']);
        }

        return $this->type;
    }

    public function defaultValueExists(): bool
    {
        return array_key_exists('defaultValue', $this->config);
    }

    public function isRequired(): bool
    {
        return $this->getType() instanceof NonNull
            && ! $this->defaultValueExists();
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
    public function assertValid(FieldDefinition $parentField, Type $parentType): void
    {
        $error = Utils::isValidNameError($this->name);
        if ($error !== null) {
            throw new InvariantViolation("{$parentType->name}.{$parentField->name}({$this->name}:) {$error->getMessage()}");
        }

        $type = Type::getNamedType($this->getType());

        if (! $type instanceof InputType) {
            $notInputType = Utils::printSafe($this->type);
            throw new InvariantViolation("{$parentType->name}.{$parentField->name}({$this->name}): argument type must be Input Type but got: {$notInputType}");
        }

        if ($this->isRequired() && $this->isDeprecated()) {
            throw new InvariantViolation("Required argument {$parentType->name}.{$parentField->name}({$this->name}:) cannot be deprecated.");
        }
    }
}
