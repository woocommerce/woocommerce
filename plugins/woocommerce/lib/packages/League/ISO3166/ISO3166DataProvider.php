<?php

declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Automattic\WooCommerce\Vendor\League\ISO3166;

interface ISO3166DataProvider
{
    /**
     * Lookup ISO3166-1 data by name identifier.
     *
     * @api
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\OutOfBoundsException if input does not exist in dataset
     *
     * @return array<string, mixed>
     */
    public function name(string $name): array;

    /**
     * Lookup ISO3166-1 data by alpha2 identifier.
     *
     * @api
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException if input does not look like an alpha2 key
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\OutOfBoundsException if input does not exist in dataset
     *
     * @return array<string, mixed>
     */
    public function alpha2(string $alpha2): array;

    /**
     * Lookup ISO3166-1 data by alpha3 identifier.
     *
     * @api
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException if input does not look like an alpha3 key
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\OutOfBoundsException if input does not exist in dataset
     *
     * @return array<string, mixed>
     */
    public function alpha3(string $alpha3): array;

    /**
     * Lookup ISO3166-1 data by numeric identifier (numerical string, that is).
     *
     * @api
     *
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\DomainException if input does not look like a numeric key
     * @throws \Automattic\WooCommerce\Vendor\League\ISO3166\Exception\OutOfBoundsException if input does not exist in dataset
     *
     * @return array<string, mixed>
     */
    public function numeric(string $numeric): array;
}
