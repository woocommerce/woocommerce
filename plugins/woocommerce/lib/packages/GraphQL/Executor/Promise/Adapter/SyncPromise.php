<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;

/**
 * Synchronous promise implementation following Promises A+ spec.
 *
 * Uses a hybrid approach for optimal memory and performance:
 * - Lightweight closures in queue (fast execution)
 * - Heavy payload (callbacks) stored on promise objects and cleared after use
 *
 * Library users should use @see \Automattic\WooCommerce\Vendor\GraphQL\Deferred to create promises.
 *
 * @phpstan-type Executor callable(): mixed
 */
class SyncPromise
{
    /**
     * TODO remove in next major version.
     *
     * @deprecated Use SyncPromiseQueue::run() instead
     */
    public static function runQueue(): void
    {
        SyncPromiseQueue::run();
    }

    /**
     * TODO remove in next major version.
     *
     * @deprecated Use SyncPromiseQueue methods instead
     *
     * @return \SplQueue<callable(): void>
     */
    public static function getQueue(): \SplQueue
    {
        return SyncPromiseQueue::queue();
    }

    public const PENDING = 0;
    public const FULFILLED = 1;
    public const REJECTED = 2;

    /**
     * Current promise state.
     *
     * @var 0|1|2
     */
    public int $state = self::PENDING;

    /**
     * Resolved value or rejection reason.
     *
     * @var mixed
     */
    public $result;

    /**
     * Promises created in `then` method awaiting resolution.
     *
     * @var array<
     *     int,
     *     array{
     *         self,
     *         (callable(mixed): mixed)|null,
     *         (callable(\Throwable): mixed)|null,
     *     },
     * >
     */
    protected array $waiting = [];

    /**
     * @param mixed $value
     *
     * @throws \Exception
     */
    public function resolve($value): self
    {
        switch ($this->state) {
            case self::PENDING:
                if ($value === $this) {
                    throw new \Exception('Cannot resolve promise with self.');
                }

                if (is_object($value) && method_exists($value, 'then')) {
                    $value->then(
                        function ($resolvedValue): void {
                            $this->resolve($resolvedValue);
                        },
                        function (\Throwable $reason): void {
                            $this->reject($reason);
                        }
                    );

                    return $this;
                }

                $this->state = self::FULFILLED;
                $this->result = $value;
                $this->enqueueWaitingPromises();
                break;
            case self::FULFILLED:
                if ($this->result !== $value) {
                    throw new \Exception('Cannot change value of fulfilled promise.');
                }

                break;
            case self::REJECTED:
                throw new \Exception('Cannot resolve rejected promise.');
        }

        return $this;
    }

    /**
     * @throws \Exception
     *
     * @return $this
     */
    public function reject(\Throwable $reason): self
    {
        switch ($this->state) {
            case self::PENDING:
                $this->state = self::REJECTED;
                $this->result = $reason;
                $this->enqueueWaitingPromises();
                break;
            case self::REJECTED:
                if ($reason !== $this->result) {
                    throw new \Exception('Cannot change rejection reason.');
                }

                break;
            case self::FULFILLED:
                throw new \Exception('Cannot reject fulfilled promise.');
        }

        return $this;
    }

    /**
     * @param (callable(mixed): mixed)|null $onFulfilled
     * @param (callable(\Throwable): mixed)|null $onRejected
     *
     * @throws InvariantViolation
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
    {
        if ($this->state === self::REJECTED
            && $onRejected === null
        ) {
            return $this;
        }

        if ($this->state === self::FULFILLED
            && $onFulfilled === null
        ) {
            return $this;
        }

        $child = new self();

        $this->waiting[] = [$child, $onFulfilled, $onRejected];

        if ($this->state !== self::PENDING) {
            $this->enqueueWaitingPromises();
        }

        return $child;
    }

    /** @throws InvariantViolation */
    private function enqueueWaitingPromises(): void
    {
        if ($this->state === self::PENDING) {
            throw new InvariantViolation('Cannot enqueue derived promises when parent is still pending.');
        }

        $waiting = $this->waiting;
        if ($waiting === []) {
            return;
        }

        $this->waiting = [];

        $result = $this->result;

        SyncPromiseQueue::enqueue(static function () use ($waiting, $result): void {
            foreach ($waiting as [$child, $onFulfilled, $onRejected]) {
                try {
                    if ($result instanceof \Throwable) {
                        if ($onRejected === null) {
                            $child->reject($result);
                        } else {
                            $child->resolve($onRejected($result));
                        }
                    } else {
                        $child->resolve(
                            $onFulfilled === null
                                ? $result
                                : $onFulfilled($result)
                        );
                    }
                } catch (\Throwable $e) {
                    $child->reject($e);
                }
            }
        });
    }
}
