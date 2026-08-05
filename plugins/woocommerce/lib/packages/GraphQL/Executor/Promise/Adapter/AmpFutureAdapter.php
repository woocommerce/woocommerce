<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter;

use Amp\DeferredFuture;
use Amp\Future;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;

use function Amp\async;
use function Amp\Future\await;

/**
 * Allows integration with amphp/amp v3 (fiber-based futures).
 *
 * @see https://amphp.org/amp
 */
class AmpFutureAdapter implements PromiseAdapter
{
    public function isThenable($value): bool
    {
        return $value instanceof Future;
    }

    /** @throws InvariantViolation */
    public function convertThenable($thenable): Promise
    {
        return new Promise($thenable, $this);
    }

    /** @throws InvariantViolation */
    public function then(Promise $promise, ?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        $future = $promise->adoptedPromise;
        assert($future instanceof Future);

        $next = async(static function () use ($future, $onFulfilled, $onRejected) {
            try {
                $value = $future->await();
            } catch (\Throwable $reason) {
                if ($onRejected === null) {
                    throw $reason;
                }

                return static::unwrapResult($onRejected($reason));
            }

            if ($onFulfilled === null) {
                return $value;
            }

            return static::unwrapResult($onFulfilled($value));
        });

        return new Promise($next, $this);
    }

    /** @throws InvariantViolation */
    public function create(callable $resolver): Promise
    {
        $deferred = new DeferredFuture();

        try {
            $resolver(
                static function ($value) use ($deferred): void {
                    static::resolveDeferred($deferred, $value);
                },
                static function (\Throwable $exception) use ($deferred): void {
                    $deferred->error($exception);
                }
            );
        } catch (\Throwable $exception) {
            $deferred->error($exception);
        }

        return new Promise($deferred->getFuture(), $this);
    }

    /**
     * @throws \Error
     * @throws InvariantViolation
     */
    public function createFulfilled($value = null): Promise
    {
        if ($value instanceof Promise) {
            return $value;
        }

        if ($value instanceof Future) {
            return new Promise($value, $this);
        }

        return new Promise(Future::complete($value), $this);
    }

    /** @throws InvariantViolation */
    public function createRejected(\Throwable $reason): Promise
    {
        return new Promise(Future::error($reason), $this);
    }

    /**
     * @throws \Error
     * @throws InvariantViolation
     */
    public function all(iterable $promisesOrValues): Promise
    {
        $items = is_array($promisesOrValues)
            ? $promisesOrValues
            : iterator_to_array($promisesOrValues);

        /** @var array<Future<mixed>> $futures */
        $futures = [];

        foreach ($items as $key => $item) {
            if ($item instanceof Promise) {
                $item = $item->adoptedPromise;
            }

            if ($item instanceof Future) {
                $futures[$key] = $item;
            }
        }

        $combined = async(static function () use ($items, $futures): array {
            if ($futures === []) {
                return $items;
            }

            $resolved = await($futures);

            return array_replace($items, $resolved);
        });

        return new Promise($combined, $this);
    }

    /**
     * @param DeferredFuture<mixed> $deferred
     * @param mixed $value
     */
    protected static function resolveDeferred(DeferredFuture $deferred, $value): void
    {
        if ($value instanceof Promise) {
            $value = $value->adoptedPromise;
        }

        if ($value instanceof Future) {
            async(static function () use ($deferred, $value): void {
                try {
                    $deferred->complete($value->await());
                } catch (\Throwable $exception) {
                    $deferred->error($exception);
                }
            });

            return;
        }

        $deferred->complete($value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected static function unwrapResult($value)
    {
        if ($value instanceof Promise) {
            $value = $value->adoptedPromise;
        }

        if ($value instanceof Future) {
            return $value->await();
        }

        return $value;
    }
}
