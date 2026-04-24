<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Server;

use Automattic\WooCommerce\Vendor\GraphQL\Error\DebugFlag;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\ExecutionResult;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ValidationRule;

/**
 * Server configuration class.
 * Could be passed directly to server constructor. List of options accepted by **create** method is
 * [described in docs](executing-queries.md#server-configuration-options).
 *
 * Usage example:
 *
 *     $config = Automattic\WooCommerce\Vendor\GraphQL\Server\ServerConfig::create()
 *         ->setSchema($mySchema)
 *         ->setContext($myContext);
 *
 *     $server = new Automattic\WooCommerce\Vendor\GraphQL\Server\StandardServer($config);
 *
 * @see ExecutionResult
 *
 * @phpstan-type PersistedQueryLoader callable(string $queryId, OperationParams $operation): (string|DocumentNode)
 * @phpstan-type RootValueResolver callable(OperationParams $operation, DocumentNode $doc, string $operationType): mixed
 * @phpstan-type ValidationRulesOption array<ValidationRule>|null|callable(OperationParams $operation, DocumentNode $doc, string $operationType): array<ValidationRule>
 *
 * @phpstan-import-type ErrorsHandler from ExecutionResult
 * @phpstan-import-type ErrorFormatter from ExecutionResult
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Server\ServerConfigTest
 */
class ServerConfig
{
    /**
     * Converts an array of options to instance of ServerConfig
     * (or just returns empty config when array is not passed).
     *
     * @param array<string, mixed> $config
     *
     * @api
     *
     * @throws InvariantViolation
     */
    public static function create(array $config = []): self
    {
        $instance = new static();
        foreach ($config as $key => $value) {
            switch ($key) {
                case 'schema':
                    $instance->setSchema($value);
                    break;
                case 'rootValue':
                    $instance->setRootValue($value);
                    break;
                case 'context':
                    $instance->setContext($value);
                    break;
                case 'fieldResolver':
                    $instance->setFieldResolver($value);
                    break;
                case 'validationRules':
                    $instance->setValidationRules($value);
                    break;
                case 'queryBatching':
                    $instance->setQueryBatching($value);
                    break;
                case 'debugFlag':
                    $instance->setDebugFlag($value);
                    break;
                case 'persistedQueryLoader':
                    $instance->setPersistedQueryLoader($value);
                    break;
                case 'errorFormatter':
                    $instance->setErrorFormatter($value);
                    break;
                case 'errorsHandler':
                    $instance->setErrorsHandler($value);
                    break;
                case 'promiseAdapter':
                    $instance->setPromiseAdapter($value);
                    break;
                default:
                    throw new InvariantViolation("Unknown server config option: {$key}");
            }
        }

        return $instance;
    }

    private ?Schema $schema = null;

    /** @var mixed|callable(self, OperationParams, DocumentNode): mixed|null */
    private $context;

    /**
     * @var mixed|callable
     *
     * @phpstan-var mixed|RootValueResolver
     */
    private $rootValue;

    /**
     * @var callable|null
     *
     * @phpstan-var ErrorFormatter|null
     */
    private $errorFormatter;

    /**
     * @var callable|null
     *
     * @phpstan-var ErrorsHandler|null
     */
    private $errorsHandler;

    private int $debugFlag = DebugFlag::NONE;

    private bool $queryBatching = false;

    /**
     * @var array<ValidationRule>|callable|null
     *
     * @phpstan-var ValidationRulesOption
     */
    private $validationRules;

    /** @var callable|null */
    private $fieldResolver;

    private ?PromiseAdapter $promiseAdapter = null;

    /**
     * @var callable|null
     *
     * @phpstan-var PersistedQueryLoader|null
     */
    private $persistedQueryLoader;

    /** @api */
    public function setSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @param mixed|callable $context
     *
     * @api
     */
    public function setContext($context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param mixed|callable $rootValue
     *
     * @phpstan-param mixed|RootValueResolver $rootValue
     *
     * @api
     */
    public function setRootValue($rootValue): self
    {
        $this->rootValue = $rootValue;

        return $this;
    }

    /**
     * @phpstan-param ErrorFormatter $errorFormatter
     *
     * @api
     */
    public function setErrorFormatter(callable $errorFormatter): self
    {
        $this->errorFormatter = $errorFormatter;

        return $this;
    }

    /**
     * @phpstan-param ErrorsHandler $handler
     *
     * @api
     */
    public function setErrorsHandler(callable $handler): self
    {
        $this->errorsHandler = $handler;

        return $this;
    }

    /**
     * Set validation rules for this server.
     *
     * @param array<ValidationRule>|callable|null $validationRules
     *
     * @phpstan-param ValidationRulesOption $validationRules
     *
     * @api
     */
    public function setValidationRules($validationRules): self
    {
        // @phpstan-ignore-next-line necessary until we can use proper union types
        if (! is_array($validationRules) && ! is_callable($validationRules) && $validationRules !== null) {
            $invalidValidationRules = Utils::printSafe($validationRules);
            throw new InvariantViolation("Server config expects array of validation rules or callable returning such array, but got {$invalidValidationRules}");
        }

        $this->validationRules = $validationRules;

        return $this;
    }

    /** @api */
    public function setFieldResolver(callable $fieldResolver): self
    {
        $this->fieldResolver = $fieldResolver;

        return $this;
    }

    /**
     * @phpstan-param PersistedQueryLoader|null $persistedQueryLoader
     *
     * @api
     */
    public function setPersistedQueryLoader(?callable $persistedQueryLoader): self
    {
        $this->persistedQueryLoader = $persistedQueryLoader;

        return $this;
    }

    /**
     * Set response debug flags.
     *
     * @see \Automattic\WooCommerce\Vendor\GraphQL\Error\DebugFlag class for a list of all available flags
     *
     * @api
     */
    public function setDebugFlag(int $debugFlag = DebugFlag::INCLUDE_DEBUG_MESSAGE): self
    {
        $this->debugFlag = $debugFlag;

        return $this;
    }

    /**
     * Allow batching queries (disabled by default).
     *
     * @api
     */
    public function setQueryBatching(bool $enableBatching): self
    {
        $this->queryBatching = $enableBatching;

        return $this;
    }

    /** @api */
    public function setPromiseAdapter(PromiseAdapter $promiseAdapter): self
    {
        $this->promiseAdapter = $promiseAdapter;

        return $this;
    }

    /** @return mixed|callable */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return mixed|callable
     *
     * @phpstan-return mixed|RootValueResolver
     */
    public function getRootValue()
    {
        return $this->rootValue;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    /** @phpstan-return ErrorFormatter|null */
    public function getErrorFormatter(): ?callable
    {
        return $this->errorFormatter;
    }

    /** @phpstan-return ErrorsHandler|null */
    public function getErrorsHandler(): ?callable
    {
        return $this->errorsHandler;
    }

    public function getPromiseAdapter(): ?PromiseAdapter
    {
        return $this->promiseAdapter;
    }

    /**
     * @return array<ValidationRule>|callable|null
     *
     * @phpstan-return ValidationRulesOption
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    public function getFieldResolver(): ?callable
    {
        return $this->fieldResolver;
    }

    /** @phpstan-return PersistedQueryLoader|null */
    public function getPersistedQueryLoader(): ?callable
    {
        return $this->persistedQueryLoader;
    }

    public function getDebugFlag(): int
    {
        return $this->debugFlag;
    }

    public function getQueryBatching(): bool
    {
        return $this->queryBatching;
    }
}
