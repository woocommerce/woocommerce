<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

/**
 * Implementing HasExtensions allows this error to provide additional data to clients.
 */
interface ProvidesExtensions
{
    /**
     * Data to include within the "extensions" key of the formatted error.
     *
     * @return array<string, mixed>|null
     */
    public function getExtensions(): ?array;
}
