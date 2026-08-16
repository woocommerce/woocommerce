<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @phpstan-type InputPath list<string|int>
 */
class CoercionError extends Error
{
    /** @var InputPath|null */
    public ?array $inputPath;

    /** @var mixed whatever invalid value was passed */
    public $invalidValue;

    /**
     * @param InputPath|null $inputPath
     * @param mixed $invalidValue whatever invalid value was passed
     *
     * @return static
     */
    public static function make(
        string $message,
        ?array $inputPath,
        $invalidValue,
        ?\Throwable $previous = null
    ): self {
        $instance = new static($message, null, null, [], null, $previous);
        $instance->inputPath = $inputPath;
        $instance->invalidValue = $invalidValue;

        return $instance;
    }

    public function printInputPath(): ?string
    {
        if ($this->inputPath === null) {
            return null;
        }

        $path = '';
        foreach ($this->inputPath as $segment) {
            $path .= is_int($segment)
                ? "[{$segment}]"
                : ".{$segment}";
        }

        return $path;
    }

    public function printInvalidValue(): string
    {
        return Utils::printSafeJson($this->invalidValue);
    }
}
