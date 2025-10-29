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

/**
 * Reads .php resource bundles.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class PhpBundleReader implements BundleReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function read(string $path, string $locale)
    {
        $fileName = $path.'/'.$locale.'.php';

        // prevent directory traversal attacks
        if (\dirname($fileName) !== $path) {
            throw new ResourceBundleNotFoundException(sprintf('The resource bundle "%s" does not exist.', $fileName));
        }

        if (!is_file($fileName)) {
            throw new ResourceBundleNotFoundException(sprintf('The resource bundle "%s" does not exist.', $fileName));
        }

        return include $fileName;
    }
}
