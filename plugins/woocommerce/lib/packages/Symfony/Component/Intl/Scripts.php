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

use Automattic\WooCommerce\Vendor\Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Gives access to script-related ICU data.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Roland Franssen <franssen.roland@gmail.com>
 */
final class Scripts extends ResourceBundle
{
    /**
     * @return string[]
     */
    public static function getScriptCodes(): array
    {
        return self::readEntry(['Scripts'], 'meta');
    }

    public static function exists(string $script): bool
    {
        try {
            self::readEntry(['Names', $script]);

            return true;
        } catch (MissingResourceException $e) {
            return false;
        }
    }

    /**
     * @throws MissingResourceException if the script code does not exist
     */
    public static function getName(string $script, ?string $displayLocale = null): string
    {
        return self::readEntry(['Names', $script], $displayLocale);
    }

    /**
     * @return string[]
     */
    public static function getNames(?string $displayLocale = null): array
    {
        return self::asort(self::readEntry(['Names'], $displayLocale), $displayLocale);
    }

    protected static function getPath(): string
    {
        return Intl::getDataDirectory().'/'.Intl::SCRIPT_DIR;
    }
}
