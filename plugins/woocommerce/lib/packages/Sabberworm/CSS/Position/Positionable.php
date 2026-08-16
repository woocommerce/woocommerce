<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position;

/**
 * Represents a CSS item that may have a position in the source CSS document (line number and possibly column number).
 *
 * A standard implementation of this interface is available in the `Position` trait.
 */
interface Positionable
{
    /**
     * @return int<1, max>|null
     */
    public function getLineNumber();

    /**
     * @return int<0, max>
     *
     * @deprecated in version 8.9.0, will be removed in v9.0. Use `getLineNumber()` instead.
     */
    public function getLineNo();

    /**
     * @return int<0, max>|null
     */
    public function getColumnNumber();

    /**
     * @return int<0, max>
     *
     * @deprecated in version 8.9.0, will be removed in v9.0. Use `getColumnNumber()` instead.
     */
    public function getColNo();

    /**
     * @param int<0, max>|null $lineNumber
     *        Providing zero for this parameter is deprecated in version 8.9.0, and will not be supported from v9.0.
     *        Use `null` instead when no line number is available.
     * @param int<0, max>|null $columnNumber
     */
    public function setPosition($lineNumber, $columnNumber = null);
}
