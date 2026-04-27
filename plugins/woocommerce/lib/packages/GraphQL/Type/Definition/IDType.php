<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\IntValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

class IDType extends ScalarType
{
    public string $name = 'ID';

    public ?string $description
        = 'The `ID` scalar type represents a unique identifier, often used to
refetch an object or as key for a cache. The ID type appears in a JSON
response as a String; however, it is not intended to be human-readable.
When expected as an input type, any string (such as `"4"`) or integer
(such as `4`) input value will be accepted as an ID.';

    /** @throws SerializationError */
    public function serialize($value): string
    {
        $canCast = is_string($value)
            || is_int($value)
            || (is_object($value) && method_exists($value, '__toString'));

        if (! $canCast) {
            $notID = Utils::printSafe($value);
            throw new SerializationError("ID cannot represent a non-string and non-integer value: {$notID}");
        }

        return (string) $value;
    }

    /** @throws Error */
    public function parseValue($value): string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        $notID = Utils::printSafeJson($value);
        throw new Error("ID cannot represent a non-string and non-integer value: {$notID}");
    }

    /**
     * @throws \JsonException
     * @throws Error
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if ($valueNode instanceof StringValueNode || $valueNode instanceof IntValueNode) {
            return $valueNode->value;
        }

        $notID = Printer::doPrint($valueNode);
        throw new Error("ID cannot represent a non-string and non-integer value: {$notID}", $valueNode);
    }
}
