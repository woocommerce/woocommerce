<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\GraphQL;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\AbstractType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ImplementingType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\InterfaceImplementations;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\TypeInfo;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * Schema Definition (see [schema definition docs](schema-definition.md)).
 *
 * A Schema is created by supplying the root types of each type of operation:
 * query, mutation (optional) and subscription (optional). A schema definition is
 * then supplied to the validator and executor. Usage Example:
 *
 *     $schema = new Automattic\WooCommerce\Vendor\GraphQL\Type\Schema([
 *       'query' => $MyAppQueryRootType,
 *       'mutation' => $MyAppMutationRootType,
 *     ]);
 *
 * Or using Schema Config instance:
 *
 *     $config = Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaConfig::create()
 *         ->setQuery($MyAppQueryRootType)
 *         ->setMutation($MyAppMutationRootType);
 *
 *     $schema = new Automattic\WooCommerce\Vendor\GraphQL\Type\Schema($config);
 *
 * @phpstan-import-type SchemaConfigOptions from SchemaConfig
 * @phpstan-import-type OperationType from OperationDefinitionNode
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Type\SchemaTest
 */
class Schema
{
    private SchemaConfig $config;

    /**
     * Contains currently resolved schema types.
     *
     * @var array<string, Type&NamedType>
     */
    private array $resolvedTypes = [];

    /**
     * Lazily initialised.
     *
     * @var array<string, InterfaceImplementations>
     */
    private array $implementationsMap;

    /** True when $resolvedTypes contains all possible schema types. */
    private bool $fullyLoaded = false;

    /** @var array<string, ScalarType>|null Lazily initialised by getScalarOverrides(). */
    private ?array $scalarOverrides = null;

    /** @var array<int, Error> */
    private array $validationErrors;

    public ?string $description;

    public ?SchemaDefinitionNode $astNode;

    /** @var array<SchemaExtensionNode> */
    public array $extensionASTNodes = [];

    /**
     * @param SchemaConfig|array<string, mixed> $config
     *
     * @phpstan-param SchemaConfig|SchemaConfigOptions $config
     *
     * @throws InvariantViolation
     *
     * @api
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            $config = SchemaConfig::create($config);
        }

        // If this schema was built from a source known to be valid, then it may be
        // marked with assumeValid to avoid an additional type system validation.
        if ($config->getAssumeValid()) {
            $this->validationErrors = [];
        }

        $this->description = $config->description;
        $this->astNode = $config->astNode;
        $this->extensionASTNodes = $config->extensionASTNodes;

        $this->config = $config;
    }

    /**
     * Returns all types in this schema.
     *
     * This operation requires a full schema scan. Do not use in production environment.
     *
     * @throws InvariantViolation
     *
     * @return array<string, Type&NamedType> Keys represent type names, values are instances of corresponding type definitions
     *
     * @api
     */
    public function getTypeMap(): array
    {
        if (! $this->fullyLoaded) {
            // Reset order of user provided types, since calls to getType() may have loaded them
            $this->resolvedTypes = [];

            $scalarOverrides = $this->getScalarOverrides();

            foreach ($this->materializeTypes() as $typeOrLazyType) {
                /** @var Type|callable(): Type $typeOrLazyType */
                $type = self::resolveType($typeOrLazyType);
                assert($type instanceof NamedType);

                /** @var string $typeName Necessary assertion for PHPStan + PHP 8.2 */
                $typeName = $type->name;

                if (isset($scalarOverrides[$typeName])) {
                    continue;
                }

                assert(
                    ! isset($this->resolvedTypes[$typeName]) || $type === $this->resolvedTypes[$typeName],
                    "Schema must contain unique named types but contains multiple types named \"{$type}\" (see https://webonyx.github.io/graphql-php/type-definitions/#type-registry).",
                );

                $this->resolvedTypes[$typeName] = $type;
            }

            // To preserve order of user-provided types, we add first to add them to
            // the set of "collected" types, so `collectReferencedTypes` ignore them.
            /** @var array<string, Type&NamedType> $allReferencedTypes */
            $allReferencedTypes = [];
            foreach ($this->resolvedTypes as $type) {
                // When we ready to process this type, we remove it from "collected" types
                // and then add it together with all dependent types in the correct position.
                unset($allReferencedTypes[$type->name]);
                TypeInfo::extractTypes($type, $allReferencedTypes);
            }

            foreach ([$this->getQueryType(), $this->getMutationType(), $this->getSubscriptionType()] as $rootType) {
                if ($rootType instanceof ObjectType) {
                    TypeInfo::extractTypes($rootType, $allReferencedTypes);
                }
            }

            foreach ($this->getDirectives() as $directive) {
                // @phpstan-ignore-next-line generics are not strictly enforceable, error will be caught during schema validation
                if ($directive instanceof Directive) {
                    TypeInfo::extractTypesFromDirectives($directive, $allReferencedTypes);
                }
            }
            TypeInfo::extractTypes(Introspection::_schema(), $allReferencedTypes);

            // Apply scalar overrides after all extractions, replacing the
            // global singletons with user-provided instances.
            foreach ($scalarOverrides as $name => $override) {
                $allReferencedTypes[$name] = $override;
            }

            $this->resolvedTypes = $allReferencedTypes;
            $this->fullyLoaded = true;
        }

        return $this->resolvedTypes;
    }

    /**
     * Returns a list of directives supported by this schema.
     *
     * @throws InvariantViolation
     *
     * @return array<Directive>
     *
     * @api
     */
    public function getDirectives(): array
    {
        return $this->config->directives ?? GraphQL::getStandardDirectives();
    }

    /** @param mixed $typeLoaderReturn could be anything */
    public static function typeLoaderNotType($typeLoaderReturn): string
    {
        $typeClass = Type::class;
        $notType = Utils::printSafe($typeLoaderReturn);

        return "Type loader is expected to return an instanceof {$typeClass}, but it returned {$notType}";
    }

    public static function typeLoaderWrongTypeName(string $expectedTypeName, string $actualTypeName): string
    {
        return "Type loader is expected to return type {$expectedTypeName}, but it returned type {$actualTypeName}.";
    }

    /** Returns root type by operation name. */
    public function getOperationType(string $operation): ?ObjectType
    {
        switch ($operation) {
            case 'query': return $this->getQueryType();
            case 'mutation': return $this->getMutationType();
            case 'subscription': return $this->getSubscriptionType();
            default: return null;
        }
    }

    /**
     * Returns root query type.
     *
     * @api
     */
    public function getQueryType(): ?ObjectType
    {
        $query = $this->config->query;

        if ($query === null) {
            return null;
        }

        if (is_callable($query)) {
            return $this->config->query = $query();
        }

        return $query;
    }

    /**
     * Returns root mutation type.
     *
     * @api
     */
    public function getMutationType(): ?ObjectType
    {
        $mutation = $this->config->mutation;

        if ($mutation === null) {
            return null;
        }

        if (is_callable($mutation)) {
            return $this->config->mutation = $mutation();
        }

        return $mutation;
    }

    /**
     * Returns schema subscription.
     *
     * @api
     */
    public function getSubscriptionType(): ?ObjectType
    {
        $subscription = $this->config->subscription;

        if ($subscription === null) {
            return null;
        }

        if (is_callable($subscription)) {
            return $this->config->subscription = $subscription();
        }

        return $subscription;
    }

    /** @api */
    public function getConfig(): SchemaConfig
    {
        return $this->config;
    }

    /**
     * Returns a type by name.
     *
     * @throws InvariantViolation
     *
     * @return (Type&NamedType)|null
     *
     * @api
     */
    public function getType(string $name): ?Type
    {
        if (isset($this->resolvedTypes[$name])) {
            return $this->resolvedTypes[$name];
        }

        $introspectionTypes = Introspection::getTypes();
        if (isset($introspectionTypes[$name])) {
            return $introspectionTypes[$name];
        }

        $type = $this->loadType($name);
        if ($type !== null) {
            return $this->resolvedTypes[$name] = self::resolveType($type);
        }

        $scalarOverrides = $this->getScalarOverrides();
        if (isset($scalarOverrides[$name])) {
            return $this->resolvedTypes[$name] = $scalarOverrides[$name];
        }

        $builtInScalars = Type::builtInScalars();
        if (isset($builtInScalars[$name])) {
            return $this->resolvedTypes[$name] = $builtInScalars[$name];
        }

        return null;
    }

    /** @throws InvariantViolation */
    public function hasType(string $name): bool
    {
        return $this->getType($name) !== null;
    }

    /**
     * @throws InvariantViolation
     *
     * @return (Type&NamedType)|null
     */
    private function loadType(string $typeName): ?Type
    {
        $typeLoader = $this->config->typeLoader;

        if (! isset($typeLoader)) {
            return $this->getTypeMap()[$typeName] ?? null;
        }

        // TODO https://github.com/webonyx/graphql-php/issues/1874 - reconsider supporting typeLoader-based scalar overrides in the next major version
        if (Type::isBuiltInScalarName($typeName)) {
            return null;
        }

        $type = $typeLoader($typeName);
        if ($type === null) {
            return null;
        }

        // @phpstan-ignore-next-line not strictly enforceable unless PHP gets function types
        if (! $type instanceof Type) {
            throw new InvariantViolation(self::typeLoaderNotType($type));
        }

        if ($typeName !== $type->name) {
            throw new InvariantViolation(self::typeLoaderWrongTypeName($typeName, $type->name));
        }

        return $type;
    }

    /** @return array<string, ScalarType> */
    private function getScalarOverrides(): array
    {
        if ($this->scalarOverrides === null) {
            $this->scalarOverrides = [];

            $builtInScalars = Type::builtInScalars();
            foreach ($this->materializeTypes() as $typeOrLazyType) {
                /** @var Type|callable(): Type $typeOrLazyType */
                $type = self::resolveType($typeOrLazyType);
                if ($type instanceof ScalarType
                    && isset($builtInScalars[$type->name])
                    && $type !== $builtInScalars[$type->name]
                ) {
                    $this->scalarOverrides[$type->name] = $type;
                }
            }
        }

        return $this->scalarOverrides;
    }

    /**
     * Resolve config->types to an array, materializing callables and generators.
     *
     * @return array<Type|callable(): Type>
     */
    private function materializeTypes(): array
    {
        $types = $this->config->types;
        if (is_callable($types)) {
            $types = $types();
        }

        if (! is_array($types)) {
            $types = iterator_to_array($types);
            $this->config->types = $types;
        }

        return $types;
    }

    /**
     * @template T of Type
     *
     * @param Type|callable $type
     *
     * @phpstan-param T|callable():T $type
     *
     * @phpstan-return T
     */
    public static function resolveType($type): Type
    {
        if ($type instanceof Type) {
            return $type;
        }

        return $type();
    }

    /**
     * Returns all possible concrete types for given abstract type
     * (implementations for interfaces and members of union type for unions).
     *
     * This operation requires full schema scan. Do not use in production environment.
     *
     * @param AbstractType&Type $abstractType
     *
     * @throws InvariantViolation
     *
     * @return array<ObjectType>
     *
     * @api
     */
    public function getPossibleTypes(AbstractType $abstractType): array
    {
        if ($abstractType instanceof UnionType) {
            return $abstractType->getTypes();
        }

        assert($abstractType instanceof InterfaceType, 'only other option');

        return $this->getImplementations($abstractType)->objects();
    }

    /**
     * Returns all types that implement a given interface type.
     *
     * This operation requires full schema scan. Do not use in production environment.
     *
     * @api
     *
     * @throws InvariantViolation
     */
    public function getImplementations(InterfaceType $abstractType): InterfaceImplementations
    {
        return $this->collectImplementations()[$abstractType->name];
    }

    /**
     * @throws InvariantViolation
     *
     * @return array<string, InterfaceImplementations>
     */
    private function collectImplementations(): array
    {
        if (! isset($this->implementationsMap)) {
            $this->implementationsMap = [];

            /**
             * @var array<
             *     string,
             *     array{
             *         objects: array<int, ObjectType>,
             *         interfaces: array<int, InterfaceType>,
             *     }
             * > $foundImplementations
             */
            $foundImplementations = [];
            foreach ($this->getTypeMap() as $type) {
                if ($type instanceof InterfaceType) {
                    if (! isset($foundImplementations[$type->name])) {
                        $foundImplementations[$type->name] = ['objects' => [], 'interfaces' => []];
                    }

                    foreach ($type->getInterfaces() as $iface) {
                        if (! isset($foundImplementations[$iface->name])) {
                            $foundImplementations[$iface->name] = ['objects' => [], 'interfaces' => []];
                        }

                        $foundImplementations[$iface->name]['interfaces'][] = $type;
                    }
                } elseif ($type instanceof ObjectType) {
                    foreach ($type->getInterfaces() as $iface) {
                        if (! isset($foundImplementations[$iface->name])) {
                            $foundImplementations[$iface->name] = ['objects' => [], 'interfaces' => []];
                        }

                        $foundImplementations[$iface->name]['objects'][] = $type;
                    }
                }
            }

            foreach ($foundImplementations as $name => $implementations) {
                $this->implementationsMap[$name] = new InterfaceImplementations($implementations['objects'], $implementations['interfaces']);
            }
        }

        return $this->implementationsMap;
    }

    /**
     * Returns true if the given type is a sub type of the given abstract type.
     *
     * @param AbstractType&Type $abstractType
     * @param ImplementingType&Type $maybeSubType
     *
     * @api
     *
     * @throws InvariantViolation
     */
    public function isSubType(AbstractType $abstractType, ImplementingType $maybeSubType): bool
    {
        if ($abstractType instanceof InterfaceType) {
            return $maybeSubType->implementsInterface($abstractType);
        }

        assert($abstractType instanceof UnionType, 'only other option');

        return $abstractType->isPossibleType($maybeSubType);
    }

    /**
     * Returns instance of directive by name.
     *
     * @api
     *
     * @throws InvariantViolation
     */
    public function getDirective(string $name): ?Directive
    {
        foreach ($this->getDirectives() as $directive) {
            if ($directive->name === $name) {
                return $directive;
            }
        }

        return null;
    }

    /**
     * Throws if the schema is not valid.
     *
     * This operation requires a full schema scan. Do not use in production environment.
     *
     * @throws Error
     * @throws InvariantViolation
     *
     * @api
     */
    public function assertValid(): void
    {
        $errors = $this->validate();

        if ($errors !== []) {
            throw new InvariantViolation(implode("\n\n", $this->validationErrors));
        }

        $internalTypes = Type::builtInScalars() + Introspection::getTypes();
        foreach ($this->getTypeMap() as $name => $type) {
            if (isset($internalTypes[$name])) {
                continue;
            }

            $type->assertValid();

            // Make sure type loader returns the same instance as registered in other places of schema
            if (isset($this->config->typeLoader) && $this->loadType($name) !== $type) {
                throw new InvariantViolation("Type loader returns different instance for {$name} than field/argument definitions. Make sure you always return the same instance for the same type name.");
            }
        }
    }

    /**
     * Validate the schema and return any errors.
     *
     * This operation requires a full schema scan. Do not use in production environment.
     *
     * @throws InvariantViolation
     *
     * @return array<int, Error>
     *
     * @api
     */
    public function validate(): array
    {
        // If this Schema has already been validated, return the previous results.
        if (isset($this->validationErrors)) {
            return $this->validationErrors;
        }

        // Validate the schema, producing a list of errors.
        $context = new SchemaValidationContext($this);
        $context->validateRootTypes();
        $context->validateDirectives();
        $context->validateTypes();

        // Persist the results of validation before returning to ensure validation
        // does not run multiple times for this schema.
        $this->validationErrors = $context->getErrors();

        return $this->validationErrors;
    }
}
