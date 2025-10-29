<?php

/*
 * This file is part WC_Vendor_of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Data\Bundle\Reader;

use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Exception\ResourceBundleNotFoundException;
use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Exception\RuntimeException;

/**
 * Reads .json resource bundles.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class JsonBundleReader implements BundleReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function read(string $path, string $locale)
    {
        $fileName = $path.'/'.$locale.'.json';

        // prevent directory traversal attacks
        if (\dirname($fileName) !== $path) {
            throw new ResourceBundleNotFoundException(sprintf('The resource bundle "%s" does not exist.', $fileName));
        }

        if (!is_file($fileName)) {
            throw new ResourceBundleNotFoundException(sprintf('The resource bundle "%s" does not exist.', $fileName));
        }

        $data = json_decode(file_get_contents($fileName), true);

        if (null === $data) {
            throw new RuntimeException(sprintf('The resource bundle "%s" contains invalid JSON: ', $fileName).json_last_error_msg());
        }

        return $data;
    }
}
