<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

/**
 * Collection of flags for [error debugging](error-handling.md#debugging-tools).
 */
final class DebugFlag
{
    public const NONE = 0;
    public const INCLUDE_DEBUG_MESSAGE = 1;
    public const INCLUDE_TRACE = 2;
    public const RETHROW_INTERNAL_EXCEPTIONS = 4;
    public const RETHROW_UNSAFE_EXCEPTIONS = 8;
}
