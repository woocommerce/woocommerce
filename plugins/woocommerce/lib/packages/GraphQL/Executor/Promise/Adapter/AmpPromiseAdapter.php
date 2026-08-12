<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter;

use Amp\Deferred;
use Amp\Failure;
use Amp\Promise as AmpPromise;
use Amp\Success;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;

use function Amp\Promise\all;

class AmpPromiseAdapter implements PromiseAdapter
{
    public function isThenable($value): bool
    {
        return $value instanceof AmpPromise;
    }

    /** @throws InvariantViolation */
    public function convertThenable($thenable): Promise
    {
        return new Promise($thenable, $this);
    }

    /** @throws InvariantViolation */
    public function then(Promise $promise, ?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        $deferred = new Deferred();
        $onResolve = static function (?\Throwable $reason, $value) use ($onFulfilled, $onRejected, $deferred): void {
            if ($reason === null && $onFulfilled !== null) {
                self::resolveWithCallable($deferred, $onFulfilled, $value);
            } elseif ($reason === null) {
                $deferred->resolve($value);
            } elseif ($onRejected !== null) {
                self::resolveWithCallable($deferred, $onRejected, $reason);
            } else {
                $deferred->fail($reason);
            }
        };

        $ampPromise = $promise->adoptedPromise;
        assert($ampPromise instanceof AmpPromise);
        $ampPromise->onResolve($onResolve);

        return new Promise($deferred->promise(), $this);
    }

    /** @throws InvariantViolation */
    public function create(callable $resolver): Promise
    {
        $deferred = new Deferred();

        $resolver(
            static function ($value) use ($deferred): void {
                $deferred->resolve($value);
            },
            static function (\Throwable $exception) use ($deferred): void {
                $deferred->fail($exception);
            }
        );

        return new Promise($deferred->promise(), $this);
    }

    /**
     * @throws \Error
     * @throws InvariantViolation
     */
    public function createFulfilled($value = null): Promise
    {
        $promise = new Success($value);

        return new Promise($promise, $this);
    }

    /** @throws InvariantViolation */
    public function createRejected(\Throwable $reason): Promise
    {
        $promise = new Failure($reason);

        return new Promise($promise, $this);
    }

    /**
     * @throws \Error
     * @throws InvariantViolation
     */
    public function all(iterable $promisesOrValues): Promise
    {
        /** @var array<AmpPromise<mixed>> $promises */
        $promises = [];
        foreach ($promisesOrValues as $key => $item) {
            if ($item instanceof Promise) {
                $ampPromise = $item->adoptedPromise;
                assert($ampPromise instanceof AmpPromise);
                $promises[$key] = $ampPromise;
            } elseif ($item instanceof AmpPromise) {
                $promises[$key] = $item;
            }
        }

        $deferred = new Deferred();

        all($promises)->onResolve(static function (?\Throwable $reason, ?array $values) use ($promisesOrValues, $deferred): void {
            if ($reason === null) {
                assert(is_array($values), 'Either $reason or $values must be passed');

                $promisesOrValuesArray = is_array($promisesOrValues)
                    ? $promisesOrValues
                    : iterator_to_array($promisesOrValues);
                $resolvedValues = array_replace($promisesOrValuesArray, $values);
                $deferred->resolve($resolvedValues);

                return;
            }

            $deferred->fail($reason);
        });

        return new Promise($deferred->promise(), $this);
    }

    /**
     * @template TArgument
     * @template TResult of AmpPromise<mixed>
     *
     * @param Deferred<TResult> $deferred
     * @param callable(TArgument): TResult $callback
     * @param TArgument $argument
     */
    private static function resolveWithCallable(Deferred $deferred, callable $callback, $argument): void
    {
        try {
            $result = $callback($argument);
        } catch (\Throwable $exception) {
            $deferred->fail($exception);

            return;
        }

        if ($result instanceof Promise) {
            /** @var TResult $result */
            $result = $result->adoptedPromise;
        }

        $deferred->resolve($result);
    }
}
