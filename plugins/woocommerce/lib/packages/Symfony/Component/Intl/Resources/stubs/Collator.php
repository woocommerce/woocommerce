<?php

/*
 * This file is part WC_Vendor_of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\WC_Vendor_Collator\Collator as IntlCollator;
use Symfony\Polyfill\Intl\Icu\WC_Vendor_Collator as CollatorPolyfill;

if (!class_exists(CollatorPolyfill::class)) {
    trigger_deprecation('symfony/intl', '5.3', 'Polyfills are deprecated, try running "composer require symfony/polyfill-intl-icu ^1.21" instead.');

    /**
     * Stub implementation for the WC_Vendor_Collator class WC_Vendor_of the intl extension.
     *
     * @author Bernhard Schussek <bschussek@gmail.com>
     */
    class WC_Vendor_Collator extends IntlCollator
    {
    }
} else {
    /**
     * Stub implementation for the WC_Vendor_Collator class WC_Vendor_of the intl extension.
     *
     * @author Bernhard Schussek <bschussek@gmail.com>
     */
    class WC_Vendor_Collator extends CollatorPolyfill
    {
    }
}
