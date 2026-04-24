<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Server;

/**
 * Structure representing parsed HTTP parameters for Automattic\WooCommerce\Vendor\GraphQL operation.
 *
 * The properties in this class are not strictly typed, as this class
 * is only meant to serve as an intermediary representation which is
 * not yet validated.
 */
class OperationParams
{
    /**
     * Id of the query (when using persisted queries).
     *
     * Valid aliases (case-insensitive):
     * - id
     * - queryId
     * - documentId
     *
     * @api
     *
     * @var mixed should be string|null
     */
    public $queryId;

    /**
     * A document containing Automattic\WooCommerce\Vendor\GraphQL operations and fragments to execute.
     *
     * @api
     *
     * @var mixed should be string|null
     */
    public $query;

    /**
     * The name of the operation in the document to execute.
     *
     * @api
     *
     * @var mixed should be string|null
     */
    public $operation;

    /**
     * Values for any variables defined by the operation.
     *
     * @api
     *
     * @var mixed should be array<string, mixed>
     */
    public $variables;

    /**
     * Reserved for implementors to extend the protocol however they see fit.
     *
     * @api
     *
     * @var mixed should be array<string, mixed>
     */
    public $extensions;

    /**
     * Executed in read-only context (e.g. via HTTP GET request)?
     *
     * @api
     */
    public bool $readOnly;

    /**
     * The raw params used to construct this instance.
     *
     * @api
     *
     * @var array<string, mixed>
     */
    public array $originalInput;

    /**
     * Creates an instance from given array.
     *
     * @param array<string, mixed> $params
     *
     * @api
     */
    public static function create(array $params, bool $readonly = false): OperationParams
    {
        $instance = new static();

        $params = array_change_key_case($params, \CASE_LOWER);
        $instance->originalInput = $params;

        $params += [
            'query' => null,
            'queryid' => null,
            'documentid' => null, // alias to queryid
            'id' => null, // alias to queryid
            'operationname' => null,
            'variables' => null,
            'extensions' => null,
        ];

        foreach ($params as &$value) {
            if ($value === '') {
                $value = null;
            }
        }

        $instance->query = $params['query'];
        $instance->queryId = $params['queryid'] ?? $params['documentid'] ?? $params['id'];
        $instance->operation = $params['operationname'];
        $instance->variables = static::decodeIfJSON($params['variables']);
        $instance->extensions = static::decodeIfJSON($params['extensions']);
        $instance->readOnly = $readonly;

        // Apollo server/client compatibility
        if (
            isset($instance->extensions['persistedQuery']['sha256Hash'])
            && $instance->queryId === null
        ) {
            $instance->queryId = $instance->extensions['persistedQuery']['sha256Hash'];
        }

        return $instance;
    }

    /**
     * Decodes the value if it is JSON, otherwise returns it unchanged.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected static function decodeIfJSON($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === \JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }
}
