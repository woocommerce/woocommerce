<?php

/*
 * This file is part WC_Vendor_of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automattic\WooCommerce\Vendor\Symfony\Component\Intl;

use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Data\Bundle\Reader\BufferedBundleReader;
use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Data\Bundle\Reader\BundleEntryReader;
use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Data\Bundle\Reader\BundleEntryReaderInterface;
use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Data\Bundle\Reader\PhpBundleReader;

/**
 * @author Roland Franssen <franssen.roland@gmail.com>
 *
 * @internal
 */
abstract class ResourceBundle
{
    private static $entryReader;

    abstract protected static function getPath(): string;

    /**
     * Reads an entry from a resource bundle.
     *
     * @see BundleEntryReaderInterface::readEntry()
     *
     * @param string[]    $indices  The indices to read from the bundle
     * @param string|null $locale   The locale to read
     * @param bool        $fallback Whether to merge the value with the value from
     *                              the fallback locale (e.g. "en" for "en_GB").
     *                              Only applicable if the result is multivalued
     *                              (i.e. array or \ArrayAccess) or cannot be found
     *                              in the requested locale.
     *
     * @return mixed returns an array or {@link \ArrayAccess} instance for
     *               complex data and a scalar value for simple data
     */
    final protected static function readEntry(array $indices, ?string $locale = null, bool $fallback = true)
    {
        if (null === self::$entryReader) {
            self::$entryReader = new BundleEntryReader(new BufferedBundleReader(
                new PhpBundleReader(),
                Intl::BUFFER_SIZE
            ));

            $localeAliases = self::$entryReader->readEntry(Intl::getDataDirectory().'/'.Intl::LOCALE_DIR, 'meta', ['Aliases']);
            self::$entryReader->setLocaleAliases($localeAliases instanceof \Traversable ? iterator_to_array($localeAliases) : $localeAliases);
        }

        return self::$entryReader->readEntry(static::getPath(), $locale ?? \WC_Vendor_Locale::getDefault(), $indices, $fallback);
    }

    final protected static function asort(iterable $list, ?string $locale = null): array
    {
        if ($list instanceof \Traversable) {
            $list = iterator_to_array($list);
        }

        $collator = new \WC_Vendor_Collator($locale ?? \WC_Vendor_Locale::getDefault());
        $collator->asort($list);

        return $list;
    }

    private function __construct()
    {
    }
}
