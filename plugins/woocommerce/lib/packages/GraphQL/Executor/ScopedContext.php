<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor;

/**
 * When the object passed as `$contextValue` to Automattic\WooCommerce\Vendor\GraphQL execution implements this,
 * its `clone()` method will be called before passing the context down to a field.
 * This allows passing information to child fields in the query tree without affecting sibling or parent fields.
 */
interface ScopedContext
{
    public function clone(): self;
}
