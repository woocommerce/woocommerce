<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\ExecutionResult;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Executor;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Source;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema as SchemaType;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\DocumentValidator;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryComplexity;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ValidationRule;

/**
 * This is the primary facade for fulfilling Automattic\WooCommerce\Vendor\GraphQL operations.
 * See [related documentation](executing-queries.md).
 *
 * @phpstan-import-type ArgsMapper from Executor
 * @phpstan-import-type FieldResolver from Executor
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\GraphQLTest
 */
class GraphQL
{
    /**
     * Executes graphql query.
     *
     * More sophisticated Automattic\WooCommerce\Vendor\GraphQL servers, such as those which persist queries,
     * may wish to separate the validation and execution phases to a static time
     * tooling step, and a server runtime step.
     *
     * Available options:
     *
     * schema:
     *    The Automattic\WooCommerce\Vendor\GraphQL type system to use when validating and executing a query.
     * source:
     *    A Automattic\WooCommerce\Vendor\GraphQL language formatted string representing the requested operation.
     * rootValue:
     *    The value provided as the first argument to resolver functions on the top
     *    level type (e.g. the query object type).
     * contextValue:
     *    The context value is provided as an argument to resolver functions after
     *    field arguments. It is used to pass shared information useful at any point
     *    during executing this query, for example the currently logged in user and
     *    connections to databases or other services.
     *    If the passed object implements the `ScopedContext` interface,
     *    its `clone()` method will be called before passing the context down to a field.
     *    This allows passing information to child fields in the query tree without affecting sibling or parent fields.
     * variableValues:
     *    A mapping of variable name to runtime value to use for all variables
     *    defined in the requestString.
     * operationName:
     *    The name of the operation to use if requestString contains multiple
     *    possible operations. Can be omitted if requestString contains only
     *    one operation.
     * fieldResolver:
     *    A resolver function to use when one is not provided by the schema.
     *    If not provided, the default field resolver is used (which looks for a
     *    value on the source value with the field's name).
     * validationRules:
     *    A set of rules for query validation step. Default value is all available rules.
     *    Empty array would allow to skip query validation (may be convenient for persisted
     *    queries which are validated before persisting and assumed valid during execution)
     *
     * @param string|DocumentNode $source
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param array<string, mixed>|null $variableValues
     * @param array<ValidationRule>|null $validationRules
     *
     * @api
     *
     * @throws \Exception
     * @throws InvariantViolation
     */
    public static function executeQuery(
        SchemaType $schema,
        $source,
        $rootValue = null,
        $contextValue = null,
        ?array $variableValues = null,
        ?string $operationName = null,
        ?callable $fieldResolver = null,
        ?array $validationRules = null
    ): ExecutionResult {
        $promiseAdapter = new SyncPromiseAdapter();

        $promise = self::promiseToExecute(
            $promiseAdapter,
            $schema,
            $source,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName,
            $fieldResolver,
            $validationRules
        );

        return $promiseAdapter->wait($promise);
    }

    /**
     * Same as executeQuery(), but requires PromiseAdapter and always returns a Promise.
     * Useful for Async PHP platforms.
     *
     * @param string|DocumentNode $source
     * @param mixed $rootValue
     * @param mixed $context
     * @param array<string, mixed>|null $variableValues
     * @param array<ValidationRule>|null $validationRules Defaults to using all available rules
     *
     * @api
     *
     * @throws \Exception
     */
    public static function promiseToExecute(
        PromiseAdapter $promiseAdapter,
        SchemaType $schema,
        $source,
        $rootValue = null,
        $context = null,
        ?array $variableValues = null,
        ?string $operationName = null,
        ?callable $fieldResolver = null,
        ?array $validationRules = null
    ): Promise {
        try {
            $documentNode = $source instanceof DocumentNode
                ? $source
                : Parser::parse(new Source($source, 'GraphQL'));

            if ($validationRules === null) {
                $queryComplexity = DocumentValidator::getRule(QueryComplexity::class);
                assert($queryComplexity instanceof QueryComplexity, 'should not register a different rule for QueryComplexity');

                $queryComplexity->setRawVariableValues($variableValues);
            } else {
                foreach ($validationRules as $rule) {
                    if ($rule instanceof QueryComplexity) {
                        $rule->setRawVariableValues($variableValues);
                    }
                }
            }

            $validationErrors = DocumentValidator::validate($schema, $documentNode, $validationRules);

            if ($validationErrors !== []) {
                return $promiseAdapter->createFulfilled(
                    new ExecutionResult(null, $validationErrors)
                );
            }

            return Executor::promiseToExecute(
                $promiseAdapter,
                $schema,
                $documentNode,
                $rootValue,
                $context,
                $variableValues,
                $operationName,
                $fieldResolver
            );
        } catch (Error $e) {
            return $promiseAdapter->createFulfilled(
                new ExecutionResult(null, [$e])
            );
        }
    }

    /**
     * Returns directives defined in Automattic\WooCommerce\Vendor\GraphQL spec.
     *
     * @deprecated use {@see Directive::builtInDirectives()}
     *
     * @throws InvariantViolation
     *
     * @return array<string, Directive>
     *
     * @api
     */
    public static function getStandardDirectives(): array
    {
        return Directive::builtInDirectives();
    }

    /**
     * Returns built-in scalar types defined in Automattic\WooCommerce\Vendor\GraphQL spec.
     *
     * @deprecated use {@see Type::builtInScalars()}
     *
     * @throws InvariantViolation
     *
     * @return array<string, ScalarType>
     *
     * @api
     */
    public static function getStandardTypes(): array
    {
        return Type::builtInScalars();
    }

    /**
     * Replaces standard types with types from this list (matching by name).
     *
     * Standard types not listed here remain untouched.
     *
     * @deprecated prefer per-schema scalar overrides via {@see \Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaConfig::$types} or {@see \Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaConfig::$typeLoader}
     *
     * @param array<string, ScalarType> $types
     *
     * @api
     *
     * @throws InvariantViolation
     */
    public static function overrideStandardTypes(array $types): void
    {
        Type::overrideStandardTypes($types);
    }

    /**
     * Returns standard validation rules implementing Automattic\WooCommerce\Vendor\GraphQL spec.
     *
     * @return array<class-string<ValidationRule>, ValidationRule>
     *
     * @api
     */
    public static function getStandardValidationRules(): array
    {
        return DocumentValidator::defaultRules();
    }

    /**
     * Set default resolver implementation.
     *
     * @phpstan-param FieldResolver $fn
     *
     * @api
     */
    public static function setDefaultFieldResolver(callable $fn): void
    {
        Executor::setDefaultFieldResolver($fn);
    }

    /**
     * Set default args mapper implementation.
     *
     * @phpstan-param ArgsMapper $fn
     *
     * @api
     */
    public static function setDefaultArgsMapper(callable $fn): void
    {
        Executor::setDefaultArgsMapper($fn);
    }
}
