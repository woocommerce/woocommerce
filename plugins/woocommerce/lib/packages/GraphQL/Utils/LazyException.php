<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

/**
 * Allows lazy calculation of a complex message when the exception is used in `assert()`.
 */
class LazyException extends \Exception
{
    /** @param callable(): string $makeMessage */
    public function __construct(callable $makeMessage)
    {
        parent::__construct($makeMessage());
    }
}
