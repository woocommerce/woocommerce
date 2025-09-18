<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position;

/**
 * Provides a standard reusable implementation of `Positionable`.
 *
 * @internal
 *
 * @phpstan-require-implements Positionable
 */
trait Position
{
    /**
     * @var int<1, max>|null
     */
    protected $lineNumber;

    /**
     * @var int<0, max>|null
     */
    protected $columnNumber;

    /**
     * @return int<1, max>|null
     */
    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    /**
     * @return int<0, max>|null
     */
    public function getColumnNumber(): ?int
    {
        return $this->columnNumber;
    }

    /**
     * @param int<1, max>|null $lineNumber
     * @param int<0, max>|null $columnNumber
     *
     * @return $this fluent interface
     */
    public function setPosition(?int $lineNumber, ?int $columnNumber = null): Positionable
    {
        $this->lineNumber = $lineNumber;
        $this->columnNumber = $columnNumber;

        return $this;
    }
}
