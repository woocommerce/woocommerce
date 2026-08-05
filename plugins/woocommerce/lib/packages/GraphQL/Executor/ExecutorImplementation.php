<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor;

use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;

interface ExecutorImplementation
{
    /** Returns promise of {@link ExecutionResult}. Promise should always resolve, never reject. */
    public function doExecute(): Promise;
}
