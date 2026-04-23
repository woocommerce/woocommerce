<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

class StringType extends ScalarType
{
    public string $name = Type::STRING;

    public ?string $description
        = 'The `String` scalar type represents textual data, represented as UTF-8
character sequences. The String type is most often used by Automattic\WooCommerce\Vendor\GraphQL to
represent free-form human-readable text.';

    /** @throws SerializationError */
    public function serialize($value): string
    {
        $canCast = is_scalar($value)
            || (is_object($value) && method_exists($value, '__toString'))
            || $value === null;

        if (! $canCast) {
            $notStringable = Utils::printSafe($value);
            throw new SerializationError("String cannot represent value: {$notStringable}");
        }

        return (string) $value;
    }

    /** @throws Error */
    public function parseValue($value): string
    {
        if (! is_string($value)) {
            $notString = Utils::printSafeJson($value);
            throw new Error("String cannot represent a non string value: {$notString}");
        }

        return $value;
    }

    /**
     * @throws \JsonException
     * @throws Error
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if ($valueNode instanceof StringValueNode) {
            return $valueNode->value;
        }

        $notString = Printer::doPrint($valueNode);
        throw new Error("String cannot represent a non string value: {$notString}", $valueNode);
    }
}
