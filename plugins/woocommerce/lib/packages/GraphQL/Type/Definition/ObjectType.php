<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Deferred;
use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Executor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * Object Type Definition.
 *
 * Most Automattic\WooCommerce\Vendor\GraphQL types you define will be object types.
 * Object types have a name, but most importantly describe their fields.
 *
 * Example:
 *
 *     $AddressType = new ObjectType([
 *         'name' => 'Address',
 *         'fields' => [
 *             'street' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type::string(),
 *             'number' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type::int(),
 *             'formatted' => [
 *                 'type' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type::string(),
 *                 'resolve' => fn (AddressModel $address): string => "{$address->number} {$address->street}",
 *             ],
 *         ],
 *     ]);
 *
 * When two types need to refer to each other, or a type needs to refer to
 * itself in a field, you can use a function expression (aka a closure or a
 * thunk) to supply the fields lazily.
 *
 * Example:
 *
 *     $PersonType = null;
 *     $PersonType = new ObjectType([
 *         'name' => 'Person',
 *         'fields' => fn (): array => [
 *             'name' => Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type::string(),
 *             'bestFriend' => $PersonType,
 *         ],
 *     ]);
 *
 * @phpstan-import-type FieldResolver from Executor
 * @phpstan-import-type ArgsMapper from Executor
 *
 * @phpstan-type InterfaceTypeReference InterfaceType|callable(): InterfaceType
 * @phpstan-type ObjectConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   resolveField?: FieldResolver|null,
 *   argsMapper?: ArgsMapper|null,
 *   fields: (callable(): iterable<mixed>)|iterable<mixed>,
 *   interfaces?: iterable<InterfaceTypeReference>|callable(): iterable<InterfaceTypeReference>,
 *   isTypeOf?: (callable(mixed $objectValue, mixed $context, ResolveInfo $resolveInfo): (bool|Deferred|null))|null,
 *   astNode?: ObjectTypeDefinitionNode|null,
 *   extensionASTNodes?: array<ObjectTypeExtensionNode>|null
 * }
 */
class ObjectType extends Type implements OutputType, CompositeType, NullableType, HasFieldsType, NamedType, ImplementingType
{
    use HasFieldsTypeImplementation;
    use NamedTypeImplementation;
    use ImplementingTypeImplementation;

    public ?ObjectTypeDefinitionNode $astNode;

    /** @var array<ObjectTypeExtensionNode> */
    public array $extensionASTNodes;

    /**
     * @var callable|null
     *
     * @phpstan-var FieldResolver|null
     */
    public $resolveFieldFn;

    /**
     * @var callable|null
     *
     * @phpstan-var ArgsMapper|null
     */
    public $argsMapper;

    /** @phpstan-var ObjectConfig */
    public array $config;

    /**
     * @phpstan-param ObjectConfig $config
     *
     * @throws InvariantViolation
     */
    public function __construct(array $config)
    {
        $this->name = $config['name'] ?? $this->inferName();
        $this->description = $config['description'] ?? null;
        $this->resolveFieldFn = $config['resolveField'] ?? null;
        $this->argsMapper = $config['argsMapper'] ?? null;
        $this->astNode = $config['astNode'] ?? null;
        $this->extensionASTNodes = $config['extensionASTNodes'] ?? [];

        $this->config = $config;
    }

    /**
     * @param mixed $type
     *
     * @throws InvariantViolation
     */
    public static function assertObjectType($type): self
    {
        if (! $type instanceof self) {
            $notObjectType = Utils::printSafe($type);
            throw new InvariantViolation("Expected {$notObjectType} to be a Automattic\WooCommerce\Vendor\GraphQL Object type.");
        }

        return $type;
    }

    /**
     * @param mixed $objectValue The resolved value for the object type
     * @param mixed $context The context that was passed to GraphQL::execute()
     *
     * @return bool|Deferred|null
     */
    public function isTypeOf($objectValue, $context, ResolveInfo $info)
    {
        return isset($this->config['isTypeOf'])
            ? $this->config['isTypeOf'](
                $objectValue,
                $context,
                $info
            )
            : null;
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

        $isTypeOf = $this->config['isTypeOf'] ?? null;
        // @phpstan-ignore-next-line unnecessary according to types, but can happen during runtime
        if (isset($isTypeOf) && ! is_callable($isTypeOf)) {
            $notCallable = Utils::printSafe($isTypeOf);
            throw new InvariantViolation("{$this->name} must provide \"isTypeOf\" as null or a callable, but got: {$notCallable}.");
        }

        foreach ($this->getFields() as $field) {
            $field->assertValid($this);
        }

        $this->assertValidInterfaces();
    }

    public function astNode(): ?ObjectTypeDefinitionNode
    {
        return $this->astNode;
    }

    /** @return array<ObjectTypeExtensionNode> */
    public function extensionASTNodes(): array
    {
        return $this->extensionASTNodes;
    }
}
