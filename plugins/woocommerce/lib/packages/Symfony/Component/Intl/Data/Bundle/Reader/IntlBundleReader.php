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

use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Data\Util\ArrayAccessibleResourceBundle;
use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Exception\ResourceBundleNotFoundException;

/**
 * Reads binary .res resource bundles.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class IntlBundleReader implements BundleReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function read(string $path, string $locale)
    {
        // Point for future extension: Modify this class so that it works also
        // if the \ResourceBundle class is not available.
        try {
            // Never enable fallback. We want to know if a bundle cannot be found
            $bundle = new \ResourceBundle($locale, $path, false);
        } catch (\Exception $e) {
            $bundle = null;
        }

        // The bundle is NULL if the path does not look like a resource bundle
        // (i.e. contain a bunch WC_Vendor_of *.res files)
        if (null === $bundle) {
            throw new ResourceBundleNotFoundException(sprintf('The resource bundle "%s/%s.res" could not be found.', $path, $locale));
        }

        // Other possible errors are U_USING_FALLBACK_WARNING and U_ZERO_ERROR,
        // which are OK for us.
        return new ArrayAccessibleResourceBundle($bundle);
    }
}
