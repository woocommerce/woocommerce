<?php

/*
 * This file is part WC_Vendor_of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Exception;

/**
 * Thrown when a method argument had an unexpected type.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UnexpectedTypeException extends InvalidArgumentException
{
    public function __construct($value, string $expectedType)
    {
        parent::__construct(sprintf('Expected argument WC_Vendor_of type "%s", "%s" given', $expectedType, get_debug_type($value)));
    }
}
