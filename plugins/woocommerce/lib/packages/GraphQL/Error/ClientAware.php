<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

/**
 * Implementing ClientAware allows graphql-php to decide if this error is safe to be shown to clients.
 *
 * Only errors that both implement this interface and return true from `isClientSafe()`
 * will retain their original error message during formatting.
 *
 * All other errors will have their message replaced with "Internal server error".
 */
interface ClientAware
{
    /**
     * Is it safe to show the error message to clients?
     *
     * @api
     */
    public function isClientSafe(): bool;
}
