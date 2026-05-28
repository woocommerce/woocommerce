<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\IntValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

class IntType extends ScalarType
{
    // As per the Automattic\WooCommerce\Vendor\GraphQL Spec, Integers are only treated as valid when a valid
    // 32-bit signed integer, providing the broadest support across platforms.
    //
    // n.b. JavaScript's integers are safe between -(2^53 - 1) and 2^53 - 1 because
    // they are internally represented as IEEE 754 doubles.
    public const MAX_INT = 2147483647;
    public const MIN_INT = -2147483648;

    public string $name = Type::INT;

    public ?string $description
        = 'The `Int` scalar type represents non-fractional signed whole numeric
values. Int can represent values between -(2^31) and 2^31 - 1. ';

    /** @throws SerializationError */
    public function serialize($value): int
    {
        // Fast path for 90+% of cases:
        if (is_int($value) && $value <= self::MAX_INT && $value >= self::MIN_INT) {
            return $value;
        }

        $float = is_numeric($value) || is_bool($value)
            ? (float) $value
            : null;

        if ($float === null || floor($float) !== $float) {
            $notInt = Utils::printSafe($value);
            throw new SerializationError("Int cannot represent non-integer value: {$notInt}");
        }

        if ($float > self::MAX_INT || $float < self::MIN_INT) {
            $outOfRangeInt = Utils::printSafe($value);
            throw new SerializationError("Int cannot represent non 32-bit signed integer value: {$outOfRangeInt}");
        }

        return (int) $float;
    }

    /** @throws Error */
    public function parseValue($value): int
    {
        $isInt = is_int($value)
            || (is_float($value) && floor($value) === $value);

        if (! $isInt) {
            $notInt = Utils::printSafeJson($value);
            throw new Error("Int cannot represent non-integer value: {$notInt}");
        }

        if ($value > self::MAX_INT || $value < self::MIN_INT) {
            $outOfRangeInt = Utils::printSafeJson($value);
            throw new Error("Int cannot represent non 32-bit signed integer value: {$outOfRangeInt}");
        }

        return (int) $value;
    }

    /**
     * @throws \JsonException
     * @throws Error
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): int
    {
        if ($valueNode instanceof IntValueNode) {
            $val = (int) $valueNode->value;
            if ($valueNode->value === (string) $val && $val >= self::MIN_INT && $val <= self::MAX_INT) {
                return $val;
            }
        }

        $notInt = Printer::doPrint($valueNode);
        throw new Error("Int cannot represent non-integer value: {$notInt}", $valueNode);
    }
}
