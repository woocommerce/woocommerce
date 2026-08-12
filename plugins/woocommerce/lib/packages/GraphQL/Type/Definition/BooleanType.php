<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\BooleanValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

class BooleanType extends ScalarType
{
    public string $name = Type::BOOLEAN;

    public ?string $description = 'The `Boolean` scalar type represents `true` or `false`.';

    /**
     * Serialize the given value to a Boolean.
     *
     * The Automattic\WooCommerce\Vendor\GraphQL spec leaves this up to the implementations, so we just do what
     * PHP does natively to make this intuitive for developers.
     */
    public function serialize($value): bool
    {
        return (bool) $value;
    }

    /** @throws Error */
    public function parseValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $notBoolean = Utils::printSafeJson($value);
        throw new Error("Boolean cannot represent a non boolean value: {$notBoolean}");
    }

    /**
     * @throws \JsonException
     * @throws Error
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): bool
    {
        if ($valueNode instanceof BooleanValueNode) {
            return $valueNode->value;
        }

        $notBoolean = Printer::doPrint($valueNode);
        throw new Error("Boolean cannot represent a non boolean value: {$notBoolean}", $valueNode);
    }
}
