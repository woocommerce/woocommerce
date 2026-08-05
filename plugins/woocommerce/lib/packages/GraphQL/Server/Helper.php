<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Server;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\FormattedError;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\ExecutionResult;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Executor;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\GraphQL;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\BatchedQueriesAreNotSupported;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\CannotParseJsonBody;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\CannotParseVariables;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\CannotReadBody;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\FailedToDetermineOperationType;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\GetMethodSupportsOnlyQueryOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\HttpMethodNotSupported;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\InvalidOperationParameter;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\InvalidQueryIdParameter;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\InvalidQueryParameter;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\MissingContentTypeHeader;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\MissingQueryOrQueryIdParameter;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\PersistedQueriesAreNotSupported;
use Automattic\WooCommerce\Vendor\GraphQL\Server\Exception\UnexpectedContentType;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\AST;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Contains functionality that could be re-used by various server implementations.
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Server\HelperTest
 */
class Helper
{
    /**
     * Parses HTTP request using PHP globals and returns Automattic\WooCommerce\Vendor\GraphQL OperationParams
     * contained in this request. For batched requests it returns an array of OperationParams.
     *
     * This function does not check validity of these params
     * (validation is performed separately in validateOperationParams() method).
     *
     * If $readRawBodyFn argument is not provided - will attempt to read raw request body
     * from `php://input` stream.
     *
     * Internally it normalizes input to $method, $bodyParams and $queryParams and
     * calls `parseRequestParams()` to produce actual return value.
     *
     * For PSR-7 request parsing use `parsePsrRequest()` instead.
     *
     * @throws RequestError
     *
     * @return OperationParams|array<int, OperationParams>
     *
     * @api
     */
    public function parseHttpRequest(?callable $readRawBodyFn = null)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $bodyParams = [];
        $urlParams = $_GET;

        if ($method === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? null;

            if ($contentType === null) {
                throw new MissingContentTypeHeader('Missing "Content-Type" header');
            }

            if (stripos($contentType, 'application/graphql') !== false) {
                $rawBody = $readRawBodyFn === null
                    ? $this->readRawBody()
                    : $readRawBodyFn();
                $bodyParams = ['query' => $rawBody];
            } elseif (stripos($contentType, 'application/json') !== false) {
                $rawBody = $readRawBodyFn === null
                    ? $this->readRawBody()
                    : $readRawBodyFn();
                $bodyParams = $this->decodeJson($rawBody);

                $this->assertJsonObjectOrArray($bodyParams);
            } elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
                $bodyParams = $_POST;
            } elseif (stripos($contentType, 'multipart/form-data') !== false) {
                $bodyParams = $_POST;
            } else {
                throw new UnexpectedContentType('Unexpected content type: ' . Utils::printSafeJson($contentType));
            }
        }

        return $this->parseRequestParams($method, $bodyParams, $urlParams);
    }

    /**
     * Parses normalized request params and returns instance of OperationParams
     * or array of OperationParams in case of batch operation.
     *
     * Returned value is a suitable input for `executeOperation` or `executeBatch` (if array)
     *
     * @param array<mixed> $bodyParams
     * @param array<mixed> $queryParams
     *
     * @throws RequestError
     *
     * @return OperationParams|array<int, OperationParams>
     *
     * @api
     */
    public function parseRequestParams(string $method, array $bodyParams, array $queryParams)
    {
        if ($method === 'GET') {
            return OperationParams::create($queryParams, true);
        }

        if ($method === 'POST') {
            if (isset($bodyParams[0])) {
                $operations = [];
                foreach ($bodyParams as $entry) {
                    $operations[] = OperationParams::create($entry);
                }

                return $operations;
            }

            return OperationParams::create($bodyParams);
        }

        throw new HttpMethodNotSupported("HTTP Method \"{$method}\" is not supported");
    }

    /**
     * Checks validity of OperationParams extracted from HTTP request and returns an array of errors
     * if params are invalid (or empty array when params are valid).
     *
     * @return list<RequestError>
     *
     * @api
     */
    public function validateOperationParams(OperationParams $params): array
    {
        $errors = [];
        $query = $params->query ?? '';
        $queryId = $params->queryId ?? '';
        if ($query === '' && $queryId === '') {
            $errors[] = new MissingQueryOrQueryIdParameter('Automattic\WooCommerce\Vendor\GraphQL Request must include at least one of those two parameters: "query" or "queryId"');
        }

        if (! is_string($query)) {
            $errors[] = new InvalidQueryParameter(
                'Automattic\WooCommerce\Vendor\GraphQL Request parameter "query" must be string, but got '
                . Utils::printSafeJson($params->query)
            );
        }

        if (! is_string($queryId)) {
            $errors[] = new InvalidQueryIdParameter(
                'Automattic\WooCommerce\Vendor\GraphQL Request parameter "queryId" must be string, but got '
                . Utils::printSafeJson($params->queryId)
            );
        }

        if ($params->operation !== null && ! is_string($params->operation)) {
            $errors[] = new InvalidOperationParameter(
                'Automattic\WooCommerce\Vendor\GraphQL Request parameter "operation" must be string, but got '
                . Utils::printSafeJson($params->operation)
            );
        }

        if ($params->variables !== null && (! is_array($params->variables) || isset($params->variables[0]))) {
            $errors[] = new CannotParseVariables(
                'Automattic\WooCommerce\Vendor\GraphQL Request parameter "variables" must be object or JSON string parsed to object, but got '
                . Utils::printSafeJson($params->originalInput['variables'])
            );
        }

        return $errors;
    }

    /**
     * Executes Automattic\WooCommerce\Vendor\GraphQL operation with given server configuration and returns execution result
     * (or promise when promise adapter is different from SyncPromiseAdapter).
     *
     * @throws \Exception
     * @throws InvariantViolation
     *
     * @return ExecutionResult|Promise
     *
     * @api
     */
    public function executeOperation(ServerConfig $config, OperationParams $op)
    {
        $promiseAdapter = $config->getPromiseAdapter() ?? Executor::getDefaultPromiseAdapter();
        $result = $this->promiseToExecuteOperation($promiseAdapter, $config, $op);

        if ($promiseAdapter instanceof SyncPromiseAdapter) {
            $result = $promiseAdapter->wait($result);
        }

        return $result;
    }

    /**
     * Executes batched Automattic\WooCommerce\Vendor\GraphQL operations with shared promise queue
     * (thus, effectively batching deferreds|promises of all queries at once).
     *
     * @param array<OperationParams> $operations
     *
     * @throws \Exception
     * @throws InvariantViolation
     *
     * @return array<int, ExecutionResult>|Promise
     *
     * @api
     */
    public function executeBatch(ServerConfig $config, array $operations)
    {
        $promiseAdapter = $config->getPromiseAdapter() ?? Executor::getDefaultPromiseAdapter();

        $result = [];
        foreach ($operations as $operation) {
            $result[] = $this->promiseToExecuteOperation($promiseAdapter, $config, $operation, true);
        }

        $result = $promiseAdapter->all($result);

        // Wait for promised results when using sync promises
        if ($promiseAdapter instanceof SyncPromiseAdapter) {
            $result = $promiseAdapter->wait($result);
        }

        return $result;
    }

    /**
     * @throws \Exception
     * @throws InvariantViolation
     */
    protected function promiseToExecuteOperation(
        PromiseAdapter $promiseAdapter,
        ServerConfig $config,
        OperationParams $op,
        bool $isBatch = false
    ): Promise {
        try {
            if ($config->getSchema() === null) {
                throw new InvariantViolation('Schema is required for the server');
            }

            if ($isBatch && ! $config->getQueryBatching()) {
                throw new BatchedQueriesAreNotSupported('Batched queries are not supported by this server');
            }

            $errors = $this->validateOperationParams($op);

            if ($errors !== []) {
                $locatedErrors = array_map(
                    [Error::class, 'createLocatedError'],
                    $errors
                );

                return $promiseAdapter->createFulfilled(
                    new ExecutionResult(null, $locatedErrors)
                );
            }

            $doc = $op->queryId !== null
                ? $this->loadPersistedQuery($config, $op)
                : $op->query;

            if (! $doc instanceof DocumentNode) {
                $doc = Parser::parse($doc);
            }

            $operationAST = AST::getOperationAST($doc, $op->operation);

            if ($operationAST === null) {
                throw new FailedToDetermineOperationType('Failed to determine operation type');
            }

            $operationType = $operationAST->operation;
            if ($operationType !== 'query' && $op->readOnly) {
                throw new GetMethodSupportsOnlyQueryOperation('GET supports only query operation');
            }

            $result = GraphQL::promiseToExecute(
                $promiseAdapter,
                $config->getSchema(),
                $doc,
                $this->resolveRootValue($config, $op, $doc, $operationType),
                $this->resolveContextValue($config, $op, $doc, $operationType),
                $op->variables,
                $op->operation,
                $config->getFieldResolver(),
                $this->resolveValidationRules($config, $op, $doc, $operationType)
            );
        } catch (RequestError $e) {
            $result = $promiseAdapter->createFulfilled(
                new ExecutionResult(null, [Error::createLocatedError($e)])
            );
        } catch (Error $e) {
            $result = $promiseAdapter->createFulfilled(
                new ExecutionResult(null, [$e])
            );
        }

        $applyErrorHandling = static function (ExecutionResult $result) use ($config): ExecutionResult {
            $result->setErrorsHandler($config->getErrorsHandler());

            $result->setErrorFormatter(
                FormattedError::prepareFormatter(
                    $config->getErrorFormatter(),
                    $config->getDebugFlag()
                )
            );

            return $result;
        };

        return $result->then($applyErrorHandling);
    }

    /**
     * @throws RequestError
     *
     * @return mixed
     */
    protected function loadPersistedQuery(ServerConfig $config, OperationParams $operationParams)
    {
        $loader = $config->getPersistedQueryLoader();

        if ($loader === null) {
            throw new PersistedQueriesAreNotSupported('Persisted queries are not supported by this server');
        }

        $source = $loader($operationParams->queryId, $operationParams);

        // @phpstan-ignore-next-line Necessary until PHP gains function types
        if (! is_string($source) && ! $source instanceof DocumentNode) {
            $documentNode = DocumentNode::class;
            $safeSource = Utils::printSafe($source);
            throw new InvariantViolation("Persisted query loader must return query string or instance of {$documentNode} but got: {$safeSource}");
        }

        return $source;
    }

    /** @return array<mixed>|null */
    protected function resolveValidationRules(
        ServerConfig $config,
        OperationParams $params,
        DocumentNode $doc,
        string $operationType
    ): ?array {
        $validationRules = $config->getValidationRules();

        if (is_callable($validationRules)) {
            $validationRules = $validationRules($params, $doc, $operationType);
        }

        // @phpstan-ignore-next-line unless PHP gains function types, we have to check this at runtime
        if ($validationRules !== null && ! is_array($validationRules)) {
            $safeValidationRules = Utils::printSafe($validationRules);
            throw new InvariantViolation("Expecting validation rules to be array or callable returning array, but got: {$safeValidationRules}");
        }

        return $validationRules;
    }

    /** @return mixed */
    protected function resolveRootValue(
        ServerConfig $config,
        OperationParams $params,
        DocumentNode $doc,
        string $operationType
    ) {
        $rootValue = $config->getRootValue();

        if (is_callable($rootValue)) {
            $rootValue = $rootValue($params, $doc, $operationType);
        }

        return $rootValue;
    }

    /** @return mixed user defined */
    protected function resolveContextValue(
        ServerConfig $config,
        OperationParams $params,
        DocumentNode $doc,
        string $operationType
    ) {
        $context = $config->getContext();

        if (is_callable($context)) {
            $context = $context($params, $doc, $operationType);
        }

        return $context;
    }

    /**
     * Send response using standard PHP `header()` and `echo`.
     *
     * @param Promise|ExecutionResult|array<ExecutionResult> $result
     *
     * @api
     *
     * @throws \JsonException
     */
    public function sendResponse($result): void
    {
        if ($result instanceof Promise) {
            $result->then(function ($actualResult): void {
                $this->emitResponse($actualResult);
            });
        } else {
            $this->emitResponse($result);
        }
    }

    /**
     * @param array<mixed>|\JsonSerializable $jsonSerializable
     *
     * @throws \JsonException
     */
    protected function emitResponse($jsonSerializable): void
    {
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($jsonSerializable, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @throws RequestError */
    protected function readRawBody(): string
    {
        $body = file_get_contents('php://input');
        if ($body === false) {
            throw new CannotReadBody('Cannot not read body.');
        }

        return $body;
    }

    /**
     * Converts PSR-7 request to OperationParams or an array thereof.
     *
     * @throws RequestError
     *
     * @return OperationParams|array<OperationParams>
     *
     * @api
     */
    public function parsePsrRequest(RequestInterface $request)
    {
        if ($request->getMethod() === 'GET') {
            $bodyParams = [];
        } else {
            $contentType = $request->getHeader('content-type');

            if (! isset($contentType[0])) {
                throw new MissingContentTypeHeader('Missing "Content-Type" header');
            }

            if (stripos($contentType[0], 'application/graphql') !== false) {
                $bodyParams = ['query' => (string) $request->getBody()];
            } elseif (stripos($contentType[0], 'application/json') !== false) {
                $bodyParams = $request instanceof ServerRequestInterface
                    ? $request->getParsedBody()
                    : $this->decodeJson((string) $request->getBody());

                $this->assertJsonObjectOrArray($bodyParams);
            } else {
                if ($request instanceof ServerRequestInterface) {
                    $bodyParams = $request->getParsedBody();
                }

                $bodyParams ??= $this->decodeContent((string) $request->getBody());
            }
        }

        parse_str(html_entity_decode($request->getUri()->getQuery()), $queryParams);

        return $this->parseRequestParams(
            $request->getMethod(),
            $bodyParams,
            $queryParams
        );
    }

    /**
     * @throws RequestError
     *
     * @return mixed
     */
    protected function decodeJson(string $rawBody)
    {
        $bodyParams = json_decode($rawBody, true);

        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new CannotParseJsonBody('Expected JSON object or array for "application/json" request, but failed to parse because: ' . json_last_error_msg());
        }

        return $bodyParams;
    }

    /** @return array<mixed> */
    protected function decodeContent(string $rawBody): array
    {
        parse_str($rawBody, $bodyParams);

        return $bodyParams;
    }

    /**
     * @param mixed $bodyParams
     *
     * @throws RequestError
     */
    protected function assertJsonObjectOrArray($bodyParams): void
    {
        if (! is_array($bodyParams)) {
            $notArray = Utils::printSafeJson($bodyParams);
            throw new CannotParseJsonBody("Expected JSON object or array for \"application/json\" request, got: {$notArray}");
        }
    }

    /**
     * Converts query execution result to PSR-7 response.
     *
     * @param Promise|ExecutionResult|array<ExecutionResult> $result
     *
     * @throws \InvalidArgumentException
     * @throws \JsonException
     * @throws \RuntimeException
     *
     * @return Promise|ResponseInterface
     *
     * @api
     */
    public function toPsrResponse($result, ResponseInterface $response, StreamInterface $writableBodyStream)
    {
        if ($result instanceof Promise) {
            return $result->then(
                fn ($actualResult): ResponseInterface => $this->doConvertToPsrResponse($actualResult, $response, $writableBodyStream)
            );
        }

        return $this->doConvertToPsrResponse($result, $response, $writableBodyStream);
    }

    /**
     * @param ExecutionResult|array<ExecutionResult> $result
     *
     * @throws \InvalidArgumentException
     * @throws \JsonException
     * @throws \RuntimeException
     */
    protected function doConvertToPsrResponse($result, ResponseInterface $response, StreamInterface $writableBodyStream): ResponseInterface
    {
        $writableBodyStream->write(json_encode($result, JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody($writableBodyStream);
    }
}
