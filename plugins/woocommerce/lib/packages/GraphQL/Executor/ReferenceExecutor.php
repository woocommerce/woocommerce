<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Error\Warning;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\AbstractType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\LeafType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\OutputType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ResolveInfo;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\AST;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @phpstan-import-type FieldResolver from Executor
 * @phpstan-import-type Path from ResolveInfo
 * @phpstan-import-type ArgsMapper from Executor
 *
 * @phpstan-type Fields \ArrayObject<string, \ArrayObject<int, FieldNode>>
 */
class ReferenceExecutor implements ExecutorImplementation
{
    protected static \stdClass $UNDEFINED;

    protected ExecutionContext $exeContext;

    /**
     * @var \SplObjectStorage<
     *     ObjectType,
     *     \SplObjectStorage<
     *         \ArrayObject<int, FieldNode>,
     *         \ArrayObject<
     *             string,
     *             \ArrayObject<int, FieldNode>
     *         >
     *     >
     * >
     */
    protected \SplObjectStorage $subFieldCache;

    /**
     * @var \SplObjectStorage<
     *     FieldDefinition,
     *     \SplObjectStorage<FieldNode, mixed>
     * >
     */
    protected \SplObjectStorage $fieldArgsCache;

    protected FieldDefinition $schemaMetaFieldDef;

    protected FieldDefinition $typeMetaFieldDef;

    protected FieldDefinition $typeNameMetaFieldDef;

    protected function __construct(ExecutionContext $context)
    {
        if (! isset(static::$UNDEFINED)) {
            static::$UNDEFINED = Utils::undefined();
        }

        $this->exeContext = $context;
        $this->subFieldCache = new \SplObjectStorage();
        $this->fieldArgsCache = new \SplObjectStorage();
    }

    /**
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param array<string, mixed> $variableValues
     *
     * @phpstan-param FieldResolver $fieldResolver
     * @phpstan-param ArgsMapper $argsMapper
     *
     * @throws \Exception
     */
    public static function create(
        PromiseAdapter $promiseAdapter,
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue,
        $contextValue,
        array $variableValues,
        ?string $operationName,
        callable $fieldResolver,
        ?callable $argsMapper = null // TODO make non-optional in next major release
    ): ExecutorImplementation {
        $exeContext = static::buildExecutionContext(
            $schema,
            $documentNode,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName,
            $fieldResolver,
            $argsMapper ?? Executor::getDefaultArgsMapper(),
            $promiseAdapter,
        );

        if (is_array($exeContext)) {
            $executionResult = new ExecutionResult(null, $exeContext);
            $fulfilledPromise = $promiseAdapter->createFulfilled($executionResult);

            return new PromiseExecutor($fulfilledPromise);
        }

        return new static($exeContext);
    }

    /**
     * Constructs an ExecutionContext object from the arguments passed to execute,
     * which we will pass throughout the other execution methods.
     *
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param array<string, mixed> $rawVariableValues
     *
     * @phpstan-param FieldResolver $fieldResolver
     *
     * @throws \Exception
     *
     * @return ExecutionContext|list<Error>
     */
    protected static function buildExecutionContext(
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue,
        $contextValue,
        array $rawVariableValues,
        ?string $operationName,
        callable $fieldResolver,
        callable $argsMapper,
        PromiseAdapter $promiseAdapter
    ) {
        /** @var list<Error> $errors */
        $errors = [];

        /** @var array<string, FragmentDefinitionNode> $fragments */
        $fragments = [];

        /** @var OperationDefinitionNode|null $operation */
        $operation = null;

        /** @var bool $hasMultipleAssumedOperations */
        $hasMultipleAssumedOperations = false;

        foreach ($documentNode->definitions as $definition) {
            switch (true) {
                case $definition instanceof OperationDefinitionNode:
                    if ($operationName === null && $operation !== null) {
                        $hasMultipleAssumedOperations = true;
                    }

                    if (
                        $operationName === null
                        || (isset($definition->name) && $definition->name->value === $operationName)
                    ) {
                        $operation = $definition;
                    }

                    break;
                case $definition instanceof FragmentDefinitionNode:
                    $fragments[$definition->name->value] = $definition;
                    break;
            }
        }

        if ($operation === null) {
            $message = $operationName === null
                ? 'Must provide an operation.'
                : "Unknown operation named \"{$operationName}\".";
            $errors[] = new Error($message);
        } elseif ($hasMultipleAssumedOperations) {
            $errors[] = new Error(
                'Must provide operation name if query contains multiple operations.'
            );
        }

        $variableValues = null;
        if ($operation !== null) {
            [$coercionErrors, $coercedVariableValues] = Values::getVariableValues(
                $schema,
                $operation->variableDefinitions,
                $rawVariableValues
            );
            if ($coercionErrors === null) {
                $variableValues = $coercedVariableValues;
            } else {
                $errors = array_merge($errors, $coercionErrors);
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        assert($operation instanceof OperationDefinitionNode, 'Has operation if no errors.');
        assert(is_array($variableValues), 'Has variables if no errors.');

        return new ExecutionContext(
            $schema,
            $fragments,
            $rootValue,
            $contextValue,
            $operation,
            $variableValues,
            $errors,
            $fieldResolver,
            $argsMapper,
            $promiseAdapter
        );
    }

    /**
     * @throws \Exception
     * @throws Error
     */
    public function doExecute(): Promise
    {
        // Return a Promise that will eventually resolve to the data described by
        // the "Response" section of the Automattic\WooCommerce\Vendor\GraphQL specification.
        //
        // If errors are encountered while executing a Automattic\WooCommerce\Vendor\GraphQL field, only that
        // field and its descendants will be omitted, and sibling fields will still
        // be executed. An execution which encounters errors will still result in a
        // resolved Promise.
        $data = $this->executeOperation($this->exeContext->operation, $this->exeContext->rootValue);
        $result = $this->buildResponse($data);

        // Note: we deviate here from the reference implementation a bit by always returning promise
        // But for the "sync" case it is always fulfilled

        $promise = $this->getPromise($result);
        if ($promise !== null) {
            return $promise;
        }

        return $this->exeContext->promiseAdapter->createFulfilled($result);
    }

    /**
     * @param mixed $data
     *
     * @return ExecutionResult|Promise
     */
    protected function buildResponse($data)
    {
        if ($data instanceof Promise) {
            return $data->then(fn ($resolved) => $this->buildResponse($resolved));
        }

        $promiseAdapter = $this->exeContext->promiseAdapter;
        if ($promiseAdapter->isThenable($data)) {
            return $promiseAdapter->convertThenable($data)
                ->then(fn ($resolved) => $this->buildResponse($resolved));
        }

        if ($data !== null) {
            $data = (array) $data;
        }

        return new ExecutionResult($data, $this->exeContext->errors);
    }

    /**
     * Implements the "Evaluating operations" section of the spec.
     *
     * @param mixed $rootValue
     *
     * @throws \Exception
     *
     * @return array<mixed>|Promise|\stdClass|null
     */
    protected function executeOperation(OperationDefinitionNode $operation, $rootValue)
    {
        $type = $this->getOperationRootType($this->exeContext->schema, $operation);
        $fields = $this->collectFields($type, $operation->selectionSet, new \ArrayObject(), new \ArrayObject());
        $path = [];
        $unaliasedPath = [];
        // Errors from sub-fields of a NonNull type may propagate to the top level,
        // at which point we still log the error and null the parent field, which
        // in this case is the entire response.
        //
        // Similar to completeValueCatchingError.
        try {
            $result = $operation->operation === 'mutation'
                ? $this->executeFieldsSerially($type, $rootValue, $path, $unaliasedPath, $fields, $this->exeContext->contextValue)
                : $this->executeFields($type, $rootValue, $path, $unaliasedPath, $fields, $this->exeContext->contextValue);

            $promise = $this->getPromise($result);
            if ($promise !== null) {
                return $promise->then(null, [$this, 'onError']);
            }

            return $result;
        } catch (Error $error) {
            $this->exeContext->addError($error);

            return null;
        }
    }

    /** @param mixed $error */
    public function onError($error): ?Promise
    {
        if ($error instanceof Error) {
            $this->exeContext->addError($error);

            return $this->exeContext->promiseAdapter->createFulfilled();
        }

        return null;
    }

    /**
     * Extracts the root type of the operation from the schema.
     *
     * @throws \Exception
     * @throws Error
     */
    protected function getOperationRootType(Schema $schema, OperationDefinitionNode $operation): ObjectType
    {
        switch ($operation->operation) {
            case 'query':
                $queryType = $schema->getQueryType();
                if ($queryType === null) {
                    throw new Error('Schema does not define the required query root type.', [$operation]);
                }

                return $queryType;

            case 'mutation':
                $mutationType = $schema->getMutationType();
                if ($mutationType === null) {
                    throw new Error('Schema is not configured for mutations.', [$operation]);
                }

                return $mutationType;

            case 'subscription':
                $subscriptionType = $schema->getSubscriptionType();
                if ($subscriptionType === null) {
                    throw new Error('Schema is not configured for subscriptions.', [$operation]);
                }

                return $subscriptionType;

            default:
                throw new Error('Can only execute queries, mutations and subscriptions.', [$operation]);
        }
    }

    /**
     * Given a selectionSet, adds all fields in that selection to
     * the passed in map of fields, and returns it at the end.
     *
     * CollectFields requires the "runtime type" of an object. For a field which
     * returns an Interface or Union type, the "runtime type" will be the actual
     * Object type returned by that field.
     *
     * @param \ArrayObject<string, true> $visitedFragmentNames
     *
     * @phpstan-param Fields $fields
     *
     * @throws \Exception
     * @throws Error
     *
     * @phpstan-return Fields
     */
    protected function collectFields(
        ObjectType $runtimeType,
        SelectionSetNode $selectionSet,
        \ArrayObject $fields,
        \ArrayObject $visitedFragmentNames
    ): \ArrayObject {
        $exeContext = $this->exeContext;
        foreach ($selectionSet->selections as $selection) {
            switch (true) {
                case $selection instanceof FieldNode:
                    if (! $this->shouldIncludeNode($selection)) {
                        break;
                    }

                    $name = static::getFieldEntryKey($selection);
                    $fields[$name] ??= new \ArrayObject();
                    $fields[$name][] = $selection;
                    break;
                case $selection instanceof InlineFragmentNode:
                    if (
                        ! $this->shouldIncludeNode($selection)
                        || ! $this->doesFragmentConditionMatch($selection, $runtimeType)
                    ) {
                        break;
                    }

                    $this->collectFields(
                        $runtimeType,
                        $selection->selectionSet,
                        $fields,
                        $visitedFragmentNames
                    );
                    break;
                case $selection instanceof FragmentSpreadNode:
                    $fragName = $selection->name->value;

                    if (isset($visitedFragmentNames[$fragName]) || ! $this->shouldIncludeNode($selection)) {
                        break;
                    }

                    $visitedFragmentNames[$fragName] = true;

                    if (! isset($exeContext->fragments[$fragName])) {
                        break;
                    }

                    $fragment = $exeContext->fragments[$fragName];
                    if (! $this->doesFragmentConditionMatch($fragment, $runtimeType)) {
                        break;
                    }

                    $this->collectFields(
                        $runtimeType,
                        $fragment->selectionSet,
                        $fields,
                        $visitedFragmentNames
                    );
                    break;
            }
        }

        return $fields;
    }

    /**
     * Determines if a field should be included based on the @include and @skip
     * directives, where @skip has higher precedence than @include.
     *
     * @param FragmentSpreadNode|FieldNode|InlineFragmentNode $node
     *
     * @throws \Exception
     * @throws Error
     */
    protected function shouldIncludeNode(SelectionNode $node): bool
    {
        $variableValues = $this->exeContext->variableValues;

        $schema = $this->exeContext->schema;

        $skip = Values::getDirectiveValues(
            Directive::skipDirective(),
            $node,
            $variableValues,
            $schema,
        );
        if (isset($skip['if']) && $skip['if'] === true) {
            return false;
        }

        $include = Values::getDirectiveValues(
            Directive::includeDirective(),
            $node,
            $variableValues,
            $schema,
        );

        return ! isset($include['if']) || $include['if'] !== false;
    }

    /** Implements the logic to compute the key of a given fields entry. */
    protected static function getFieldEntryKey(FieldNode $node): string
    {
        return $node->alias->value
            ?? $node->name->value;
    }

    /**
     * Determines if a fragment is applicable to the given type.
     *
     * @param FragmentDefinitionNode|InlineFragmentNode $fragment
     *
     * @throws \Exception
     */
    protected function doesFragmentConditionMatch(Node $fragment, ObjectType $type): bool
    {
        $typeConditionNode = $fragment->typeCondition;
        if ($typeConditionNode === null) {
            return true;
        }

        $conditionalType = AST::typeFromAST([$this->exeContext->schema, 'getType'], $typeConditionNode);
        if ($conditionalType === $type) {
            return true;
        }

        if ($conditionalType instanceof AbstractType) {
            return $this->exeContext->schema->isSubType($conditionalType, $type);
        }

        return false;
    }

    /**
     * Implements the "Evaluating selection sets" section of the spec for "write" mode.
     *
     * @param mixed $rootValue
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $contextValue
     *
     * @phpstan-param Fields $fields
     *
     * @return array<mixed>|Promise|\stdClass
     */
    protected function executeFieldsSerially(ObjectType $parentType, $rootValue, array $path, array $unaliasedPath, \ArrayObject $fields, $contextValue)
    {
        $result = $this->promiseReduce(
            array_keys($fields->getArrayCopy()),
            function ($results, $responseName) use ($contextValue, $path, $unaliasedPath, $parentType, $rootValue, $fields) {
                $fieldNodes = $fields[$responseName];
                assert($fieldNodes instanceof \ArrayObject, 'The keys of $fields populate $responseName');

                $result = $this->resolveField(
                    $parentType,
                    $rootValue,
                    $fieldNodes,
                    $responseName,
                    $path,
                    $unaliasedPath,
                    $this->maybeScopeContext($contextValue)
                );
                if ($result === static::$UNDEFINED) {
                    return $results;
                }

                $promise = $this->getPromise($result);
                if ($promise !== null) {
                    return $promise->then(static function ($resolvedResult) use ($responseName, $results): array {
                        $results[$responseName] = $resolvedResult;

                        return $results;
                    });
                }

                $results[$responseName] = $result;

                return $results;
            },
            []
        );

        $promise = $this->getPromise($result);
        if ($promise !== null) {
            return $result->then(
                static fn ($resolvedResults) => static::fixResultsIfEmptyArray($resolvedResults)
            );
        }

        return static::fixResultsIfEmptyArray($result);
    }

    /**
     * Resolves the field on the given root value.
     *
     * In particular, this figures out the value that the field returns
     * by calling its resolve function, then calls completeValue to complete promises,
     * serialize scalars, or execute the sub-selection-set for objects.
     *
     * @param mixed $rootValue
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $contextValue
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     *
     * @phpstan-param Path                $path
     * @phpstan-param Path                $unaliasedPath
     *
     * @throws Error
     * @throws InvariantViolation
     *
     * @return array<mixed>|\Throwable|mixed|null
     */
    protected function resolveField(
        ObjectType $parentType,
        $rootValue,
        \ArrayObject $fieldNodes,
        string $responseName,
        array $path,
        array $unaliasedPath,
        $contextValue
    ) {
        $exeContext = $this->exeContext;

        $fieldNode = $fieldNodes[0];
        assert($fieldNode instanceof FieldNode, '$fieldNodes is non-empty');

        $fieldName = $fieldNode->name->value;
        $fieldDef = $this->getFieldDef($exeContext->schema, $parentType, $fieldName);
        if ($fieldDef === null || ! $fieldDef->isVisible()) {
            return static::$UNDEFINED;
        }

        $path[] = $responseName;
        $unaliasedPath[] = $fieldName;

        $returnType = $fieldDef->getType();
        // The resolve function's optional 3rd argument is a context value that
        // is provided to every resolve function within an execution. It is commonly
        // used to represent an authenticated user, or request-specific caches.
        // The resolve function's optional 4th argument is a collection of
        // information about the current execution state.
        $info = new ResolveInfo(
            $fieldDef,
            $fieldNodes,
            $parentType,
            $path,
            $exeContext->schema,
            $exeContext->fragments,
            $exeContext->rootValue,
            $exeContext->operation,
            $exeContext->variableValues,
            $unaliasedPath
        );

        $resolveFn = $fieldDef->resolveFn
            ?? $parentType->resolveFieldFn
            ?? $this->exeContext->fieldResolver;

        $argsMapper = $fieldDef->argsMapper
            ?? $parentType->argsMapper
            ?? $this->exeContext->argsMapper;

        // Get the resolve function, regardless of if its result is normal
        // or abrupt (error).
        $result = $this->resolveFieldValueOrError(
            $fieldDef,
            $fieldNode,
            $resolveFn,
            $argsMapper,
            $rootValue,
            $info,
            $contextValue
        );

        return $this->completeValueCatchingError(
            $returnType,
            $fieldNodes,
            $info,
            $path,
            $unaliasedPath,
            $result,
            $contextValue
        );
    }

    /**
     * This method looks up the field on the given type definition.
     *
     * It has special casing for the two introspection fields, __schema
     * and __typename. __typename is special because it can always be
     * queried as a field, even in situations where no other fields
     * are allowed, like on a Union. __schema could get automatically
     * added to the query type, but that would require mutating type
     * definitions, which would cause issues.
     *
     * @throws InvariantViolation
     */
    protected function getFieldDef(Schema $schema, ObjectType $parentType, string $fieldName): ?FieldDefinition
    {
        $this->schemaMetaFieldDef ??= Introspection::schemaMetaFieldDef();
        $this->typeMetaFieldDef ??= Introspection::typeMetaFieldDef();
        $this->typeNameMetaFieldDef ??= Introspection::typeNameMetaFieldDef();

        $queryType = $schema->getQueryType();

        if ($fieldName === $this->schemaMetaFieldDef->name
            && $queryType === $parentType
        ) {
            return $this->schemaMetaFieldDef;
        }

        if ($fieldName === $this->typeMetaFieldDef->name
            && $queryType === $parentType
        ) {
            return $this->typeMetaFieldDef;
        }

        if ($fieldName === $this->typeNameMetaFieldDef->name) {
            return $this->typeNameMetaFieldDef;
        }

        return $parentType->findField($fieldName);
    }

    /**
     * Isolates the "ReturnOrAbrupt" behavior to not de-opt the `resolveField` function.
     * Returns the result of resolveFn or the abrupt-return Error object.
     *
     * @param mixed $rootValue
     * @param mixed $contextValue
     *
     * @phpstan-param FieldResolver $resolveFn
     *
     * @return \Throwable|Promise|mixed
     */
    protected function resolveFieldValueOrError(
        FieldDefinition $fieldDef,
        FieldNode $fieldNode,
        callable $resolveFn,
        callable $argsMapper,
        $rootValue,
        ResolveInfo $info,
        $contextValue
    ) {
        try {
            // Build a map of arguments from the field.arguments AST, using the
            // variables scope to fulfill any variable references.
            // @phpstan-ignore-next-line generics of SplObjectStorage are not inferred from empty instantiation
            $this->fieldArgsCache[$fieldDef] ??= new \SplObjectStorage();

            $args = $this->fieldArgsCache[$fieldDef][$fieldNode] ??= $argsMapper(Values::getArgumentValues(
                $fieldDef,
                $fieldNode,
                $this->exeContext->variableValues,
                $this->exeContext->schema,
            ), $fieldDef, $fieldNode, $contextValue);

            return $resolveFn($rootValue, $args, $contextValue, $info);
        } catch (\Throwable $error) {
            return $error;
        }
    }

    /**
     * This is a small wrapper around completeValue which detects and logs errors
     * in the execution context.
     *
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $contextValue
     * @param mixed $result
     *
     * @phpstan-param Path                $path
     * @phpstan-param Path                $unaliasedPath
     *
     * @throws Error
     *
     * @return array<mixed>|Promise|\stdClass|null
     */
    protected function completeValueCatchingError(
        Type $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        // Otherwise, error protection is applied, logging the error and resolving
        // a null value for this field if one is encountered.
        try {
            $promise = $this->getPromise($result);
            if ($promise !== null) {
                $completed = $promise->then(fn (&$resolved) => $this->completeValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $resolved, $contextValue));
            } else {
                $completed = $this->completeValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result, $contextValue);
            }

            $promise = $this->getPromise($completed);
            if ($promise !== null) {
                return $promise->then(null, function ($error) use ($fieldNodes, $path, $unaliasedPath, $returnType): void {
                    $this->handleFieldError($error, $fieldNodes, $path, $unaliasedPath, $returnType);
                });
            }

            return $completed;
        } catch (\Throwable $err) {
            $this->handleFieldError($err, $fieldNodes, $path, $unaliasedPath, $returnType);

            return null;
        }
    }

    /**
     * @param mixed $rawError
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     *
     * @throws Error
     */
    protected function handleFieldError($rawError, \ArrayObject $fieldNodes, array $path, array $unaliasedPath, Type $returnType): void
    {
        $error = Error::createLocatedError(
            $rawError,
            $fieldNodes,
            $path,
            $unaliasedPath
        );

        // If the field type is non-nullable, then it is resolved without any
        // protection from errors, however it still properly locates the error.
        if ($returnType instanceof NonNull) {
            throw $error;
        }

        // Otherwise, error protection is applied, logging the error and resolving
        // a null value for this field if one is encountered.
        $this->exeContext->addError($error);
    }

    /**
     * Implements the instructions for completeValue as defined in the
     * "Field entries" section of the spec.
     *
     * If the field type is Non-Null, then this recursively completes the value
     * for the inner type. It throws a field error if that completion returns null,
     * as per the "Nullability" section of the spec.
     *
     * If the field type is a List, then this recursively completes the value
     * for the inner type on each item in the list.
     *
     * If the field type is a Scalar or Enum, ensures the completed value is a legal
     * value of the type by calling the `serialize` method of Automattic\WooCommerce\Vendor\GraphQL type
     * definition.
     *
     * If the field is an abstract type, determine the runtime type of the value
     * and then complete based on that type.
     *
     * Otherwise, the field type expects a sub-selection set, and will complete the
     * value by evaluating all sub-selections.
     *
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @throws \Throwable
     * @throws Error
     *
     * @return array<mixed>|mixed|Promise|null
     */
    protected function completeValue(
        Type $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        // If result is an Error, throw a located error.
        if ($result instanceof \Throwable) {
            throw $result;
        }

        // If field type is NonNull, complete for inner type, and throw field error
        // if result is null.
        if ($returnType instanceof NonNull) {
            $completed = $this->completeValue(
                $returnType->getWrappedType(),
                $fieldNodes,
                $info,
                $path,
                $unaliasedPath,
                $result,
                $contextValue
            );
            if ($completed === null) {
                throw new InvariantViolation("Cannot return null for non-nullable field \"{$info->parentType}.{$info->fieldName}\".");
            }

            return $completed;
        }

        if ($result === null) {
            return null;
        }

        // If field type is List, complete each item in the list with the inner type
        if ($returnType instanceof ListOfType) {
            if (! is_iterable($result)) {
                $resultType = gettype($result);

                throw new InvariantViolation("Expected field {$info->parentType}.{$info->fieldName} to return iterable, but got: {$resultType}.");
            }

            return $this->completeListValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result, $contextValue);
        }

        assert($returnType instanceof NamedType, 'Wrapping types should return early');

        // Account for invalid schema definition when typeLoader returns different
        // instance than `resolveType` or $field->getType() or $arg->getType()
        assert(
            $returnType === $this->exeContext->schema->getType($returnType->name)
            || Type::isBuiltInScalar($returnType),
            SchemaValidationContext::duplicateType($this->exeContext->schema, "{$info->parentType}.{$info->fieldName}", $returnType->name)
        );

        if ($returnType instanceof LeafType) {
            if (Type::isBuiltInScalar($returnType)) {
                $schemaType = $this->exeContext->schema->getType($returnType->name);
                assert($schemaType instanceof LeafType, "Schema must provide a LeafType for built-in scalar \"{$returnType->name}\".");
                $returnType = $schemaType;
            }

            return $this->completeLeafValue($returnType, $result);
        }

        if ($returnType instanceof AbstractType) {
            return $this->completeAbstractValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result, $contextValue);
        }

        // Field type must be and Object, Interface or Union and expect sub-selections.
        if ($returnType instanceof ObjectType) {
            return $this->completeObjectValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result, $contextValue);
        }

        $safeReturnType = Utils::printSafe($returnType);
        throw new \RuntimeException("Cannot complete value of unexpected type {$safeReturnType}.");
    }

    /** @param mixed $value */
    protected function isPromise($value): bool
    {
        return $value instanceof Promise
            || $this->exeContext->promiseAdapter->isThenable($value);
    }

    /**
     * Only returns the value if it acts like a Promise, i.e. has a "then" function,
     * otherwise returns null.
     *
     * @param mixed $value
     */
    protected function getPromise($value): ?Promise
    {
        if ($value === null || $value instanceof Promise) {
            return $value;
        }

        $promiseAdapter = $this->exeContext->promiseAdapter;
        if ($promiseAdapter->isThenable($value)) {
            return $promiseAdapter->convertThenable($value);
        }

        return null;
    }

    /**
     * Similar to array_reduce(), however the reducing callback may return
     * a Promise, in which case reduction will continue after each promise resolves.
     *
     * If the callback does not return a Promise, then this function will also not
     * return a Promise.
     *
     * @param array<mixed> $values
     * @param Promise|mixed|null $initialValue
     *
     * @return Promise|mixed|null
     */
    protected function promiseReduce(array $values, callable $callback, $initialValue)
    {
        return array_reduce(
            $values,
            function ($previous, $value) use ($callback) {
                $promise = $this->getPromise($previous);
                if ($promise !== null) {
                    return $promise->then(static fn ($resolved) => $callback($resolved, $value));
                }

                return $callback($previous, $value);
            },
            $initialValue
        );
    }

    /**
     * Complete a list value by completing each item in the list with the inner type.
     *
     * @param ListOfType<Type&OutputType> $returnType
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param iterable<mixed> $results
     * @param mixed $contextValue
     *
     * @throws Error
     *
     * @return array<mixed>|Promise|\stdClass
     */
    protected function completeListValue(
        ListOfType $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        iterable $results,
        $contextValue
    ) {
        $itemType = $returnType->getWrappedType();

        $i = 0;
        $containsPromise = false;
        $completedItems = [];
        foreach ($results as $item) {
            $itemPath = [...$path, $i];
            $info->path = $itemPath;
            $itemUnaliasedPath = [...$unaliasedPath, $i];
            $info->unaliasedPath = $itemUnaliasedPath;
            ++$i;

            $completedItem = $this->completeValueCatchingError($itemType, $fieldNodes, $info, $itemPath, $itemUnaliasedPath, $item, $contextValue);

            if (! $containsPromise && $this->getPromise($completedItem) !== null) {
                $containsPromise = true;
            }

            $completedItems[] = $completedItem;
        }

        return $containsPromise
            ? $this->exeContext->promiseAdapter->all($completedItems)
            : $completedItems;
    }

    /**
     * Complete a Scalar or Enum by serializing to a valid value, throwing if serialization is not possible.
     *
     * @param mixed $result
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function completeLeafValue(LeafType $returnType, $result)
    {
        try {
            return $returnType->serialize($result);
        } catch (\Throwable $error) {
            $safeReturnType = Utils::printSafe($returnType);
            $safeResult = Utils::printSafe($result);
            throw new InvariantViolation("Expected a value of type {$safeReturnType} but received: {$safeResult}. {$error->getMessage()}", 0, $error);
        }
    }

    /**
     * Complete a value of an abstract type by determining the runtime object type
     * of that value, then complete the value for that type.
     *
     * @param AbstractType&Type $returnType
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @throws \Exception
     * @throws Error
     * @throws InvariantViolation
     *
     * @return array<mixed>|Promise|\stdClass
     */
    protected function completeAbstractValue(
        AbstractType $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        $result = $returnType->resolveValue($result, $contextValue, $info);
        $typeCandidate = $returnType->resolveType($result, $contextValue, $info);

        if ($typeCandidate === null) {
            $runtimeType = static::defaultTypeResolver($result, $contextValue, $info, $returnType);
        } elseif (! is_string($typeCandidate) && is_callable($typeCandidate)) {
            $runtimeType = $typeCandidate();
        } else {
            $runtimeType = $typeCandidate;
        }

        $promise = $this->getPromise($runtimeType);
        if ($promise !== null) {
            return $promise->then(fn ($resolvedRuntimeType) => $this->completeObjectValue(
                $this->ensureValidRuntimeType(
                    $resolvedRuntimeType,
                    $returnType,
                    $info,
                    $result
                ),
                $fieldNodes,
                $info,
                $path,
                $unaliasedPath,
                $result,
                $contextValue
            ));
        }

        return $this->completeObjectValue(
            $this->ensureValidRuntimeType(
                $runtimeType,
                $returnType,
                $info,
                $result
            ),
            $fieldNodes,
            $info,
            $path,
            $unaliasedPath,
            $result,
            $contextValue
        );
    }

    /**
     * If a resolveType function is not given, then a default resolve behavior is
     * used which attempts two strategies:.
     *
     * First, See if the provided value has a `__typename` field defined, if so, use
     * that value as name of the resolved type.
     *
     * Otherwise, test each possible type for the abstract type by calling
     * isTypeOf for the object being coerced, returning the first type that matches.
     *
     * @param mixed|null $value
     * @param mixed|null $contextValue
     * @param AbstractType&Type $abstractType
     *
     * @throws InvariantViolation
     *
     * @return Promise|Type|string|null
     */
    protected function defaultTypeResolver($value, $contextValue, ResolveInfo $info, AbstractType $abstractType)
    {
        $typename = Utils::extractKey($value, '__typename');
        if (is_string($typename)) {
            return $typename;
        }

        if ($abstractType instanceof InterfaceType && isset($info->schema->getConfig()->typeLoader)) {
            $safeValue = Utils::printSafe($value);
            Warning::warnOnce(
                "Automattic\WooCommerce\Vendor\GraphQL Interface Type `{$abstractType->name}` returned `null` from its `resolveType` function for value: {$safeValue}. Switching to slow resolution method using `isTypeOf` of all possible implementations. It requires full schema scan and degrades query performance significantly. Make sure your `resolveType` function always returns a valid implementation or throws.",
                Warning::WARNING_FULL_SCHEMA_SCAN
            );
        }

        $possibleTypes = $info->schema->getPossibleTypes($abstractType);
        $promisedIsTypeOfResults = [];
        foreach ($possibleTypes as $index => $type) {
            $isTypeOfResult = $type->isTypeOf($value, $contextValue, $info);
            if ($isTypeOfResult === null) {
                continue;
            }

            $promise = $this->getPromise($isTypeOfResult);
            if ($promise !== null) {
                $promisedIsTypeOfResults[$index] = $promise;
            } elseif ($isTypeOfResult === true) {
                return $type;
            }
        }

        if ($promisedIsTypeOfResults !== []) {
            return $this->exeContext->promiseAdapter
                ->all($promisedIsTypeOfResults)
                ->then(static function ($isTypeOfResults) use ($possibleTypes): ?ObjectType {
                    foreach ($isTypeOfResults as $index => $result) {
                        if ($result) {
                            return $possibleTypes[$index];
                        }
                    }

                    return null;
                });
        }

        return null;
    }

    /**
     * Complete an Object value by executing all sub-selections.
     *
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @throws \Exception
     * @throws Error
     *
     * @return array<mixed>|Promise|\stdClass
     */
    protected function completeObjectValue(
        ObjectType $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        // If there is an isTypeOf predicate function, call it with the
        // current result. If isTypeOf returns false, then raise an error rather
        // than continuing execution.
        $isTypeOf = $returnType->isTypeOf($result, $contextValue, $info);
        if ($isTypeOf !== null) {
            $promise = $this->getPromise($isTypeOf);
            if ($promise !== null) {
                return $promise->then(function ($isTypeOfResult) use (
                    $contextValue,
                    $returnType,
                    $fieldNodes,
                    $path,
                    $unaliasedPath,
                    $result
                ) {
                    if (! $isTypeOfResult) {
                        throw $this->invalidReturnTypeError($returnType, $result, $fieldNodes);
                    }

                    return $this->collectAndExecuteSubfields(
                        $returnType,
                        $fieldNodes,
                        $path,
                        $unaliasedPath,
                        $result,
                        $contextValue
                    );
                });
            }

            assert(is_bool($isTypeOf), 'Promise would return early');
            if (! $isTypeOf) {
                throw $this->invalidReturnTypeError($returnType, $result, $fieldNodes);
            }
        }

        return $this->collectAndExecuteSubfields(
            $returnType,
            $fieldNodes,
            $path,
            $unaliasedPath,
            $result,
            $contextValue
        );
    }

    /**
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param mixed $result
     */
    protected function invalidReturnTypeError(
        ObjectType $returnType,
        $result,
        \ArrayObject $fieldNodes
    ): Error {
        $safeResult = Utils::printSafe($result);

        return new Error(
            "Expected value of type \"{$returnType->name}\" but got: {$safeResult}.",
            $fieldNodes
        );
    }

    /**
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @throws \Exception
     * @throws Error
     *
     * @return array<mixed>|Promise|\stdClass
     */
    protected function collectAndExecuteSubfields(
        ObjectType $returnType,
        \ArrayObject $fieldNodes,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        $subFieldNodes = $this->collectSubFields($returnType, $fieldNodes);

        return $this->executeFields($returnType, $result, $path, $unaliasedPath, $subFieldNodes, $contextValue);
    }

    /**
     * A memoized collection of relevant subfields with regard to the return
     * type. Memoizing ensures the subfields are not repeatedly calculated, which
     * saves overhead when resolving lists of values.
     *
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     *
     * @throws \Exception
     * @throws Error
     *
     * @phpstan-return Fields
     */
    protected function collectSubFields(ObjectType $returnType, \ArrayObject $fieldNodes): \ArrayObject
    {
        // @phpstan-ignore-next-line generics of SplObjectStorage are not inferred from empty instantiation
        $returnTypeCache = $this->subFieldCache[$returnType] ??= new \SplObjectStorage();

        if (! isset($returnTypeCache[$fieldNodes])) {
            // Collect sub-fields to execute to complete this value.
            $subFieldNodes = new \ArrayObject();
            $visitedFragmentNames = new \ArrayObject();
            foreach ($fieldNodes as $fieldNode) {
                if (isset($fieldNode->selectionSet)) {
                    $subFieldNodes = $this->collectFields(
                        $returnType,
                        $fieldNode->selectionSet,
                        $subFieldNodes,
                        $visitedFragmentNames
                    );
                }
            }

            $returnTypeCache[$fieldNodes] = $subFieldNodes;
        }

        return $returnTypeCache[$fieldNodes];
    }

    /**
     * Implements the "Evaluating selection sets" section of the spec for "read" mode.
     *
     * @param mixed $rootValue
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $contextValue
     *
     * @phpstan-param Fields $fields
     *
     * @throws Error
     * @throws InvariantViolation
     *
     * @return Promise|\stdClass|array<mixed>
     */
    protected function executeFields(ObjectType $parentType, $rootValue, array $path, array $unaliasedPath, \ArrayObject $fields, $contextValue)
    {
        $containsPromise = false;
        $results = [];
        foreach ($fields as $responseName => $fieldNodes) {
            $result = $this->resolveField(
                $parentType,
                $rootValue,
                $fieldNodes,
                $responseName,
                $path,
                $unaliasedPath,
                $this->maybeScopeContext($contextValue)
            );
            if ($result === static::$UNDEFINED) {
                continue;
            }

            if (! $containsPromise && $this->isPromise($result)) {
                $containsPromise = true;
            }

            $results[$responseName] = $result;
        }

        // If there are no promises, we can just return the object
        if (! $containsPromise) {
            return static::fixResultsIfEmptyArray($results);
        }

        // Otherwise, results is a map from field name to the result of resolving that
        // field, which is possibly a promise. Return a promise that will return this
        // same map, but with any promises replaced with the values they resolved to.
        return $this->promiseForAssocArray($results);
    }

    /**
     * Differentiate empty objects from empty lists.
     *
     * @see https://github.com/webonyx/graphql-php/issues/59
     *
     * @param array<mixed>|mixed $results
     *
     * @return non-empty-array<mixed>|\stdClass|mixed
     */
    protected static function fixResultsIfEmptyArray($results)
    {
        if ($results === []) {
            return new \stdClass();
        }

        return $results;
    }

    /**
     * Transform an associative array with Promises to a Promise which resolves to an
     * associative array where all Promises were resolved.
     *
     * @param array<string, Promise|mixed> $assoc
     */
    protected function promiseForAssocArray(array $assoc): Promise
    {
        $keys = array_keys($assoc);
        $valuesAndPromises = array_values($assoc);
        $promise = $this->exeContext->promiseAdapter->all($valuesAndPromises);

        return $promise->then(static function ($values) use ($keys) {
            $resolvedResults = [];
            foreach ($values as $i => $value) {
                $resolvedResults[$keys[$i]] = $value;
            }

            return static::fixResultsIfEmptyArray($resolvedResults);
        });
    }

    /**
     * @param mixed $runtimeTypeOrName
     * @param AbstractType&Type $returnType
     * @param mixed $result
     *
     * @throws InvariantViolation
     */
    protected function ensureValidRuntimeType(
        $runtimeTypeOrName,
        AbstractType $returnType,
        ResolveInfo $info,
        $result
    ): ObjectType {
        $runtimeType = is_string($runtimeTypeOrName)
            ? $this->exeContext->schema->getType($runtimeTypeOrName)
            : $runtimeTypeOrName;

        if (! $runtimeType instanceof ObjectType) {
            $safeResult = Utils::printSafe($result);
            $notObjectType = Utils::printSafe($runtimeType);
            throw new InvariantViolation("Abstract type {$returnType} must resolve to an Object type at runtime for field {$info->parentType}.{$info->fieldName} with value {$safeResult}, received \"{$notObjectType}\". Either the {$returnType} type should provide a \"resolveType\" function or each possible type should provide an \"isTypeOf\" function.");
        }

        if (! $this->exeContext->schema->isSubType($returnType, $runtimeType)) {
            throw new InvariantViolation("Runtime Object type \"{$runtimeType}\" is not a possible type for \"{$returnType}\".");
        }

        assert(
            $this->exeContext->schema->getType($runtimeType->name) !== null,
            "Schema does not contain type \"{$runtimeType}\". This can happen when an object type is only referenced indirectly through abstract types and never directly through fields.List the type in the option \"types\" during schema construction, see https://webonyx.github.io/graphql-php/schema-definition/#configuration-options."
        );

        assert(
            $runtimeType === $this->exeContext->schema->getType($runtimeType->name),
            "Schema must contain unique named types but contains multiple types named \"{$runtimeType}\". Make sure that `resolveType` function of abstract type \"{$returnType}\" returns the same type instance as referenced anywhere else within the schema (see https://webonyx.github.io/graphql-php/type-definitions/#type-registry)."
        );

        return $runtimeType;
    }

    /**
     * @param mixed $contextValue
     *
     * @return mixed
     */
    private function maybeScopeContext($contextValue)
    {
        if ($contextValue instanceof ScopedContext) {
            return $contextValue->clone();
        }

        return $contextValue;
    }
}
