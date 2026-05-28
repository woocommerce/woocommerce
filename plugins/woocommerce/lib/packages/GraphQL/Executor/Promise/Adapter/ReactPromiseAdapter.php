<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Promise;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\PromiseAdapter;
use React\Promise\Promise as ReactPromise;
use React\Promise\PromiseInterface as ReactPromiseInterface;

use function React\Promise\all;
use function React\Promise\reject;
use function React\Promise\resolve;

class ReactPromiseAdapter implements PromiseAdapter
{
    public function isThenable($value): bool
    {
        return $value instanceof ReactPromiseInterface;
    }

    /** @throws InvariantViolation */
    public function convertThenable($thenable): Promise
    {
        return new Promise($thenable, $this);
    }

    /** @throws InvariantViolation */
    public function then(Promise $promise, ?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        $reactPromise = $promise->adoptedPromise;
        assert($reactPromise instanceof ReactPromiseInterface);

        return new Promise($reactPromise->then($onFulfilled, $onRejected), $this);
    }

    /** @throws InvariantViolation */
    public function create(callable $resolver): Promise
    {
        $reactPromise = new ReactPromise($resolver);

        return new Promise($reactPromise, $this);
    }

    /** @throws InvariantViolation */
    public function createFulfilled($value = null): Promise
    {
        $reactPromise = resolve($value);

        return new Promise($reactPromise, $this);
    }

    /** @throws InvariantViolation */
    public function createRejected(\Throwable $reason): Promise
    {
        $reactPromise = reject($reason);

        return new Promise($reactPromise, $this);
    }

    /** @throws InvariantViolation */
    public function all(iterable $promisesOrValues): Promise
    {
        foreach ($promisesOrValues as &$promiseOrValue) {
            if ($promiseOrValue instanceof Promise) {
                $promiseOrValue = $promiseOrValue->adoptedPromise;
            }
        }

        $promisesOrValuesArray = is_array($promisesOrValues)
            ? $promisesOrValues
            : iterator_to_array($promisesOrValues);
        $reactPromise = all($promisesOrValuesArray)->then(static fn (array $values): array => array_map(
            static fn ($key) => $values[$key],
            array_keys($promisesOrValuesArray),
        ));

        return new Promise($reactPromise, $this);
    }
}
