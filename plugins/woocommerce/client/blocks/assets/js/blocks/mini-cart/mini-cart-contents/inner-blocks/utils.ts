/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

type Variant = 'text' | 'contained' | 'outlined';

export const getVariant = (
	className = '',
	defaultVariant: Variant
): Variant => {
	if ( className.includes( 'is-style-outline' ) ) {
		return 'outlined';
	}

	if ( className.includes( 'is-style-fill' ) ) {
		return 'contained';
	}

	return defaultVariant;
};

/**
 * Gets the totals item description text from PHP-computed setting.
 *
 * @return {string} The description text for the totals item.
 */
export const getTotalsItemDescription = (): string => {
	return getSetting( 'miniCartFooterDescription', '' );
};
