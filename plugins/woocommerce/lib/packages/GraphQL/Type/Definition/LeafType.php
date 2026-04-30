<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ValueNode;

/*
export type GraphQLLeafType =
GraphQLScalarType |
GraphQLEnumType;
*/

interface LeafType
{
    /**
     * Serializes an internal value to include in a response.
     *
     * Should throw an exception on invalid values.
     *
     * @param mixed $value
     *
     * @throws SerializationError
     *
     * @return mixed
     */
    public function serialize($value);

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     * Should throw an exception with a client-friendly message on invalid values, @see ClientAware.
     *
     * @param mixed $value
     *
     * @throws Error
     *
     * @return mixed
     */
    public function parseValue($value);

    /**
     * Parses an externally provided literal value (hardcoded in Automattic\WooCommerce\Vendor\GraphQL query) to use as an input.
     *
     * Should throw an exception with a client-friendly message on invalid value nodes, @see ClientAware.
     *
     * @param ValueNode&Node $valueNode
     * @param array<string, mixed>|null $variables
     *
     * @throws Error
     *
     * @return mixed
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null);
}
