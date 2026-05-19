<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise;

use Amp\Future as AmpFuture;
use Amp\Promise as AmpPromise;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Executor\Promise\Adapter\SyncPromise;
use React\Promise\PromiseInterface as ReactPromise;

/**
 * Convenience wrapper for promises represented by Promise Adapter.
 */
class Promise
{
    /** @var SyncPromise|ReactPromise<mixed>|AmpFuture<mixed>|AmpPromise<mixed> */
    public $adoptedPromise;

    private PromiseAdapter $adapter;

    /**
     * @param mixed $adoptedPromise
     *
     * @throws InvariantViolation
     */
    public function __construct($adoptedPromise, PromiseAdapter $adapter)
    {
        if ($adoptedPromise instanceof self) {
            $selfClass = self::class;
            throw new InvariantViolation("Expected promise from adapted system, got {$selfClass}.");
        }

        $this->adoptedPromise = $adoptedPromise;
        $this->adapter = $adapter;
    }

    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        return $this->adapter->then($this, $onFulfilled, $onRejected);
    }
}
