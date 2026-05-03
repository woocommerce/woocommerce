<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter;

use Automattic\WooCommerce\Vendor\GraphQL\Deferred;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * Allows changing order of field resolution even in sync environments
 * (by leveraging queue of deferreds and promises).
 */
class SyncPromiseAdapter implements PromiseAdapter
{
    public function isThenable($value): bool
    {
        return $value instanceof SyncPromise;
    }

    /** @throws InvariantViolation */
    public function convertThenable($thenable): Promise
    {
        if (! $thenable instanceof SyncPromise) {
            // End-users should always use Deferred, not SyncPromise directly
            $deferredClass = Deferred::class;
            $safeThenable = Utils::printSafe($thenable);
            throw new InvariantViolation("Expected instance of {$deferredClass}, got {$safeThenable}.");
        }

        return new Promise($thenable, $this);
    }

    /** @throws InvariantViolation */
    public function then(Promise $promise, ?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        $syncPromise = $promise->adoptedPromise;
        assert($syncPromise instanceof SyncPromise);

        return new Promise($syncPromise->then($onFulfilled, $onRejected), $this);
    }

    /**
     * @throws \Exception
     * @throws InvariantViolation
     */
    public function create(callable $resolver): Promise
    {
        $syncPromise = new SyncPromise();

        try {
            $resolver(
                [$syncPromise, 'resolve'],
                [$syncPromise, 'reject']
            );
        } catch (\Throwable $e) {
            $syncPromise->reject($e);
        }

        return new Promise($syncPromise, $this);
    }

    /**
     * @throws \Exception
     * @throws InvariantViolation
     */
    public function createFulfilled($value = null): Promise
    {
        $syncPromise = new SyncPromise();

        return new Promise($syncPromise->resolve($value), $this);
    }

    /**
     * @throws \Exception
     * @throws InvariantViolation
     */
    public function createRejected(\Throwable $reason): Promise
    {
        $syncPromise = new SyncPromise();

        return new Promise($syncPromise->reject($reason), $this);
    }

    /**
     * @throws \Exception
     * @throws InvariantViolation
     */
    public function all(iterable $promisesOrValues): Promise
    {
        $all = new SyncPromise();

        $total = is_array($promisesOrValues)
            ? count($promisesOrValues)
            : iterator_count($promisesOrValues);
        $count = 0;
        $result = [];

        $resolveAllWhenFinished = function () use (&$count, &$total, $all, &$result): void {
            if ($count === $total) {
                $all->resolve($result);
            }
        };

        foreach ($promisesOrValues as $index => $promiseOrValue) {
            if ($promiseOrValue instanceof Promise) {
                $result[$index] = null;
                $promiseOrValue->then(
                    static function ($value) use (&$result, $index, &$count, &$resolveAllWhenFinished): void {
                        $result[$index] = $value;
                        ++$count;
                        $resolveAllWhenFinished();
                    },
                    [$all, 'reject']
                );
                continue;
            }

            $result[$index] = $promiseOrValue;
            ++$count;
        }

        $resolveAllWhenFinished();

        return new Promise($all, $this);
    }

    /**
     * Synchronously wait when promise completes.
     *
     * @throws InvariantViolation
     *
     * @return mixed
     */
    public function wait(Promise $promise)
    {
        $this->beforeWait($promise);

        $syncPromise = $promise->adoptedPromise;
        assert($syncPromise instanceof SyncPromise);

        while ($syncPromise->state === SyncPromise::PENDING) {
            SyncPromiseQueue::run();
            $this->onWait($promise);
        }

        if ($syncPromise->state === SyncPromise::FULFILLED) {
            return $syncPromise->result;
        }

        if ($syncPromise->state === SyncPromise::REJECTED) {
            throw $syncPromise->result;
        }

        throw new InvariantViolation('Could not resolve promise.');
    }

    /** Execute just before starting to run promise completion. */
    protected function beforeWait(Promise $promise): void {}

    /** Execute while running promise completion. */
    protected function onWait(Promise $promise): void {}
}
