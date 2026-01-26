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
    public function getLineNumber()
    {
        return $this->lineNumber;
    }

    /**
     * @return int<0, max>
     */
    public function getLineNo()
    {
        $lineNumber = $this->getLineNumber();

        return $lineNumber !== null ? $lineNumber : 0;
    }

    /**
     * @return int<0, max>|null
     */
    public function getColumnNumber()
    {
        return $this->columnNumber;
    }

    /**
     * @return int<0, max>
     */
    public function getColNo()
    {
        $columnNumber = $this->getColumnNumber();

        return $columnNumber !== null ? $columnNumber : 0;
    }

    /**
     * @param int<0, max>|null $lineNumber
     * @param int<0, max>|null $columnNumber
     */
    public function setPosition($lineNumber, $columnNumber = null)
    {
        // The conditional is for backwards compatibility (backcompat); `0` will not be allowed in future.
        $this->lineNumber = $lineNumber !== 0 ? $lineNumber : null;
        $this->columnNumber = $columnNumber;
    }
}
