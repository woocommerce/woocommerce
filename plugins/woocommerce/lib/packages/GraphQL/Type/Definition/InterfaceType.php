<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @phpstan-import-type ResolveType from AbstractType
 * @phpstan-import-type ResolveValue from AbstractType
 * @phpstan-import-type FieldsConfig from FieldDefinition
 *
 * @phpstan-type InterfaceTypeReference InterfaceType|callable(): InterfaceType
 * @phpstan-type InterfaceConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   fields: FieldsConfig,
 *   interfaces?: iterable<InterfaceTypeReference>|callable(): iterable<InterfaceTypeReference>,
 *   resolveType?: ResolveType|null,
 *   resolveValue?: ResolveValue|null,
 *   astNode?: InterfaceTypeDefinitionNode|null,
 *   extensionASTNodes?: array<InterfaceTypeExtensionNode>|null
 * }
 */
class InterfaceType extends Type implements AbstractType, OutputType, CompositeType, NullableType, HasFieldsType, NamedType, ImplementingType
{
    use HasFieldsTypeImplementation;
    use NamedTypeImplementation;
    use ImplementingTypeImplementation;

    public ?InterfaceTypeDefinitionNode $astNode;

    /** @var array<InterfaceTypeExtensionNode> */
    public array $extensionASTNodes;

    /** @phpstan-var InterfaceConfig */
    public array $config;

    /**
     * @phpstan-param InterfaceConfig $config
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

    /**
     * @param mixed $type
     *
     * @throws InvariantViolation
     */
    public static function assertInterfaceType($type): self
    {
        if (! $type instanceof self) {
            $notInterfaceType = Utils::printSafe($type);
            throw new InvariantViolation("Expected {$notInterfaceType} to be a Automattic\WooCommerce\Vendor\GraphQL Interface type.");
        }

        return $type;
    }

    public function resolveValue($objectValue, $context, ResolveInfo $info)
    {
        if (isset($this->config['resolveValue'])) {
            return ($this->config['resolveValue'])($objectValue, $context, $info);
        }

        return $objectValue;
    }

    public function resolveType($objectValue, $context, ResolveInfo $info)
    {
        if (isset($this->config['resolveType'])) {
            return ($this->config['resolveType'])($objectValue, $context, $info);
        }

        return null;
    }

    /**
     * @throws Error
     * @throws InvariantViolation
     */
    public function assertValid(): void
    {
        Utils::assertValidName($this->name);

        $resolveType = $this->config['resolveType'] ?? null;
        // @phpstan-ignore-next-line unnecessary according to types, but can happen during runtime
        if ($resolveType !== null && ! is_callable($resolveType)) {
            $notCallable = Utils::printSafe($resolveType);
            throw new InvariantViolation("{$this->name} must provide \"resolveType\" as null or a callable, but got: {$notCallable}.");
        }

        $this->assertValidInterfaces();
    }

    public function astNode(): ?InterfaceTypeDefinitionNode
    {
        return $this->astNode;
    }

    /** @return array<InterfaceTypeExtensionNode> */
    public function extensionASTNodes(): array
    {
        return $this->extensionASTNodes;
    }
}
