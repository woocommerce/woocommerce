<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL;

use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter\SyncPromise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;

/**
 * User-facing promise class for deferred field resolution.
 *
 * @phpstan-type Executor callable(): mixed
 */
class Deferred extends SyncPromise
{
    /**
     * Executor for deferred promises.
     *
     * @var (callable(): mixed)|null
     */
    protected $executor;

    /**
     * Create a new Deferred promise and enqueue its execution.
     *
     * @api
     *
     * @param Executor $executor
     */
    public function __construct(callable $executor)
    {
        $this->executor = $executor;

        SyncPromiseQueue::enqueue(function (): void {
            $executor = $this->executor;
            assert($executor !== null, 'Always set in constructor, this callback runs only once.');
            $this->executor = null;

            try {
                $this->resolve($executor());
            } catch (\Throwable $e) {
                $this->reject($e);
            }
        });
    }

    /**
     * Alias for __construct.
     *
     * @param Executor $executor
     *
     * @deprecated TODO remove in next major version, use new Deferred() instead
     */
    public static function create(callable $executor): self
    {
        return new self($executor);
    }
}
