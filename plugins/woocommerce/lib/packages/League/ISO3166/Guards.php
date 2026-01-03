<?php

declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Automattic\WooCommerce\Vendor\League\ISO3166;

use Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException;

final class Guards
{
    /**
     * Assert that input is not an empty string.
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException if input is an empty string
     */
    public static function guardAgainstInvalidName(string $name): void
    {
        if ('' === trim($name)) {
            throw new DomainException('Expected string, got empty string');
        }
    }

    /**
     * Assert that input looks like an alpha2 key.
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException if input does not look like an alpha2 key
     */
    public static function guardAgainstInvalidAlpha2(string $alpha2): void
    {
        if (1 !== preg_match('/^[a-zA-Z]{2}$/', $alpha2)) {
            throw new DomainException(sprintf('Not a valid alpha2 key: %s', $alpha2));
        }
    }

    /**
     * Assert that input looks like an alpha3 key.
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException if input does not look like an alpha3 key
     */
    public static function guardAgainstInvalidAlpha3(string $alpha3): void
    {
        if (1 !== preg_match('/^[a-zA-Z]{3}$/', $alpha3)) {
            throw new DomainException(sprintf('Not a valid alpha3 key: %s', $alpha3));
        }
    }

    /**
     * Assert that input looks like a numeric key.
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException if input does not look like a numeric key
     */
    public static function guardAgainstInvalidNumeric(string $numeric): void
    {
        if (1 !== preg_match('/^\d{3}$/', $numeric)) {
            throw new DomainException(sprintf('Not a valid numeric key: %s', $numeric));
        }
    }
}
