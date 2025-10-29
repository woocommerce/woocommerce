<?php

/*
 * This file is part WC_Vendor_of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Intl\DateFormatter\WC_Vendor_IntlDateFormatter as BaseIntlDateFormatter;
use Symfony\Polyfill\Intl\Icu\WC_Vendor_IntlDateFormatter as IntlDateFormatterPolyfill;

if (!class_exists(IntlDateFormatterPolyfill::class)) {
    trigger_deprecation('symfony/intl', '5.3', 'Polyfills are deprecated, try running "composer require symfony/polyfill-intl-icu ^1.21" instead.');

    /**
     * Stub implementation for the WC_Vendor_IntlDateFormatter class WC_Vendor_of the intl extension.
     *
     * @author Bernhard Schussek <bschussek@gmail.com>
     *
     * @see BaseIntlDateFormatter
     */
    class WC_Vendor_IntlDateFormatter extends BaseIntlDateFormatter
    {
    }
} else {
    /**
     * Stub implementation for the WC_Vendor_IntlDateFormatter class WC_Vendor_of the intl extension.
     *
     * @author Bernhard Schussek <bschussek@gmail.com>
     *
     * @see BaseIntlDateFormatter
     */
    class WC_Vendor_IntlDateFormatter extends IntlDateFormatterPolyfill
    {
    }
}
