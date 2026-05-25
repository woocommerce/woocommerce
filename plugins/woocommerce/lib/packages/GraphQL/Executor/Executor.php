<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ResolveInfo;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * Implements the "Evaluating requests" section of the Automattic\WooCommerce\Vendor\GraphQL specification.
 *
 * @phpstan-type ArgsMapper callable(array<string, mixed>, FieldDefinition, FieldNode, mixed): mixed
 * @phpstan-type FieldResolver callable(mixed, array<string, mixed>, mixed, ResolveInfo): mixed
 * @phpstan-type ImplementationFactory callable(PromiseAdapter, Schema, DocumentNode, mixed, mixed, array<mixed>, ?string, callable, callable): ExecutorImplementation
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Executor\ExecutorTest
 */
class Executor
{
    /**
     * @var callable
     *
     * @phpstan-var FieldResolver
     */
    private static $defaultFieldResolver = [self::class, 'defaultFieldResolver'];

    /**
     * @var callable
     *
     * @phpstan-var ArgsMapper
     */
    private static $defaultArgsMapper = [self::class, 'defaultArgsMapper'];

    private static ?PromiseAdapter $defaultPromiseAdapter;

    /**
     * @var callable
     *
     * @phpstan-var ImplementationFactory
     */
    private static $implementationFactory = [ReferenceExecutor::class, 'create'];

    /** @phpstan-return FieldResolver */
    public static function getDefaultFieldResolver(): callable
    {
        return self::$defaultFieldResolver;
    }

    /**
     * Set a custom default resolve function.
     *
     * @phpstan-param FieldResolver $fieldResolver
     */
    public static function setDefaultFieldResolver(callable $fieldResolver): void
    {
        self::$defaultFieldResolver = $fieldResolver;
    }

    /** @phpstan-return ArgsMapper */
    public static function getDefaultArgsMapper(): callable
    {
        return self::$defaultArgsMapper;
    }

    /** @phpstan-param ArgsMapper $argsMapper */
    public static function setDefaultArgsMapper(callable $argsMapper): void
    {
        self::$defaultArgsMapper = $argsMapper;
    }

    public static function getDefaultPromiseAdapter(): PromiseAdapter
    {
        return self::$defaultPromiseAdapter ??= new SyncPromiseAdapter();
    }

    /** Set a custom default promise adapter. */
    public static function setDefaultPromiseAdapter(?PromiseAdapter $defaultPromiseAdapter = null): void
    {
        self::$defaultPromiseAdapter = $defaultPromiseAdapter;
    }

    /** @phpstan-return ImplementationFactory */
    public static function getImplementationFactory(): callable
    {
        return self::$implementationFactory;
    }

    /**
     * Set a custom executor implementation factory.
     *
     * @phpstan-param ImplementationFactory $implementationFactory
     */
    public static function setImplementationFactory(callable $implementationFactory): void
    {
        self::$implementationFactory = $implementationFactory;
    }

    /**
     * Executes DocumentNode against given $schema.
     *
     * Always returns ExecutionResult and never throws.
     * All errors which occur during operation execution are collected in `$result->errors`.
     *
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param array<string, mixed>|null $variableValues
     *
     * @phpstan-param FieldResolver|null $fieldResolver
     *
     * @api
     *
     * @throws InvariantViolation
     */
    public static function execute(
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue = null,
        $contextValue = null,
        ?array $variableValues = null,
        ?string $operationName = null,
        ?callable $fieldResolver = null
    ): ExecutionResult {
        $promiseAdapter = new SyncPromiseAdapter();

        $result = static::promiseToExecute(
            $promiseAdapter,
            $schema,
            $documentNode,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName,
            $fieldResolver
        );

        return $promiseAdapter->wait($result);
    }

    /**
     * Same as execute(), but requires promise adapter and returns a promise which is always
     * fulfilled with an instance of ExecutionResult and never rejected.
     *
     * Useful for async PHP platforms.
     *
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param array<string, mixed>|null $variableValues
     *
     * @phpstan-param FieldResolver|null $fieldResolver
     * @phpstan-param ArgsMapper|null $argsMapper
     *
     * @api
     */
    public static function promiseToExecute(
        PromiseAdapter $promiseAdapter,
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue = null,
        $contextValue = null,
        ?array $variableValues = null,
        ?string $operationName = null,
        ?callable $fieldResolver = null,
        ?callable $argsMapper = null
    ): Promise {
        $executor = (self::$implementationFactory)(
            $promiseAdapter,
            $schema,
            $documentNode,
            $rootValue,
            $contextValue,
            $variableValues ?? [],
            $operationName,
            $fieldResolver ?? self::$defaultFieldResolver,
            $argsMapper ?? self::$defaultArgsMapper,
        );

        return $executor->doExecute();
    }

    /**
     * If a resolve function is not given, then a default resolve behavior is used
     * which takes the property of the root value of the same name as the field
     * and returns it as the result, or if it's a function, returns the result
     * of calling that function while passing along args and context.
     *
     * @param mixed $objectLikeValue
     * @param array<string, mixed> $args
     * @param mixed $contextValue
     *
     * @return mixed
     */
    public static function defaultFieldResolver($objectLikeValue, array $args, $contextValue, ResolveInfo $info)
    {
        $property = Utils::extractKey($objectLikeValue, $info->fieldName);

        return $property instanceof \Closure
            ? $property($objectLikeValue, $args, $contextValue, $info)
            : $property;
    }

    /**
     * @template T of array<string, mixed>
     *
     * @param T $args
     *
     * @return T
     */
    public static function defaultArgsMapper(array $args): array
    {
        return $args;
    }
}
