<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Server;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\ExecutionResult;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Automattic\WooCommerce\Vendor\GraphQL server compatible with both: [express-graphql](https://github.com/graphql/express-graphql)
 * and [Apollo Server](https://github.com/apollographql/graphql-server).
 * Usage Example:.
 *
 *     $server = new StandardServer([
 *       'schema' => $mySchema
 *     ]);
 *     $server->handleRequest();
 *
 * Or using [ServerConfig](class-reference.md#graphqlserverserverconfig) instance:
 *
 *     $config = Automattic\WooCommerce\Vendor\GraphQL\Server\ServerConfig::create()
 *         ->setSchema($mySchema)
 *         ->setContext($myContext);
 *
 *     $server = new Automattic\WooCommerce\Vendor\GraphQL\Server\StandardServer($config);
 *     $server->handleRequest();
 *
 * See [dedicated section in docs](executing-queries.md#using-server) for details.
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Server\StandardServerTest
 */
class StandardServer
{
    protected ServerConfig $config;

    protected Helper $helper;

    /**
     * @param ServerConfig|array<string, mixed> $config
     *
     * @api
     *
     * @throws InvariantViolation
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            $config = ServerConfig::create($config);
        }

        // @phpstan-ignore-next-line necessary until we can use proper union types
        if (! $config instanceof ServerConfig) {
            $safeConfig = Utils::printSafe($config);
            throw new InvariantViolation("Expecting valid server config, but got {$safeConfig}");
        }

        $this->config = $config;
        $this->helper = new Helper();
    }

    /**
     * Parses HTTP request, executes and emits response (using standard PHP `header` function and `echo`).
     *
     * When $parsedBody is not set, it uses PHP globals to parse a request.
     * It is possible to implement request parsing elsewhere (e.g. using framework Request instance)
     * and then pass it to the server.
     *
     * See `executeRequest()` if you prefer to emit the response yourself
     * (e.g. using the Response object of some framework).
     *
     * @param OperationParams|array<OperationParams> $parsedBody
     *
     * @api
     *
     * @throws \Exception
     * @throws InvariantViolation
     * @throws RequestError
     */
    public function handleRequest($parsedBody = null): void
    {
        $result = $this->executeRequest($parsedBody);
        $this->helper->sendResponse($result);
    }

    /**
     * Executes a Automattic\WooCommerce\Vendor\GraphQL operation and returns an execution result
     * (or promise when promise adapter is different from SyncPromiseAdapter).
     *
     * When $parsedBody is not set, it uses PHP globals to parse a request.
     * It is possible to implement request parsing elsewhere (e.g. using framework Request instance)
     * and then pass it to the server.
     *
     * PSR-7 compatible method executePsrRequest() does exactly this.
     *
     * @param OperationParams|array<OperationParams> $parsedBody
     *
     * @throws \Exception
     * @throws InvariantViolation
     * @throws RequestError
     *
     * @return ExecutionResult|array<int, ExecutionResult>|Promise
     *
     * @api
     */
    public function executeRequest($parsedBody = null)
    {
        if ($parsedBody === null) {
            $parsedBody = $this->helper->parseHttpRequest();
        }

        if (is_array($parsedBody)) {
            return $this->helper->executeBatch($this->config, $parsedBody);
        }

        return $this->helper->executeOperation($this->config, $parsedBody);
    }

    /**
     * Executes PSR-7 request and fulfills PSR-7 response.
     *
     * See `executePsrRequest()` if you prefer to create response yourself
     * (e.g. using specific JsonResponse instance of some framework).
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws InvariantViolation
     * @throws RequestError
     *
     * @return ResponseInterface|Promise
     *
     * @api
     */
    public function processPsrRequest(
        RequestInterface $request,
        ResponseInterface $response,
        StreamInterface $writableBodyStream
    ) {
        $result = $this->executePsrRequest($request);

        return $this->helper->toPsrResponse($result, $response, $writableBodyStream);
    }

    /**
     * Executes Automattic\WooCommerce\Vendor\GraphQL operation and returns execution result
     * (or promise when promise adapter is different from SyncPromiseAdapter).
     *
     * @throws \Exception
     * @throws \JsonException
     * @throws InvariantViolation
     * @throws RequestError
     *
     * @return ExecutionResult|array<int, ExecutionResult>|Promise
     *
     * @api
     */
    public function executePsrRequest(RequestInterface $request)
    {
        $parsedBody = $this->helper->parsePsrRequest($request);

        return $this->executeRequest($parsedBody);
    }
}
