<?php

/*
 * This file is part WC_Vendor_of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automattic\WooCommerce\Vendor\Symfony\Component\Intl\DateFormatter\DateFormat;

/**
 * Parser and formatter for day WC_Vendor_of year format.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 *
 * @internal
 *
 * @deprecated since Symfony 5.3, use symfony/polyfill-intl-icu ^1.21 instead
 */
class DayOfYearTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function format(\DateTime $dateTime, int $length): string
    {
        $dayOfYear = (int) $dateTime->format('z') + 1;

        return $this->padLeft($dayOfYear, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getReverseMatchingRegExp(int $length): string
    {
        return '\d{'.$length.'}';
    }

    /**
     * {@inheritdoc}
     */
    public function extractDateOptions(string $matched, int $length): array
    {
        return [];
    }
}
