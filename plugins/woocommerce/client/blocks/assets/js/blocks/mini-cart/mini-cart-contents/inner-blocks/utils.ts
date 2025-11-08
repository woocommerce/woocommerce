/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { isObject } from '@woocommerce/types';

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
 * Checks if there are any children that are blocks.
 */
export const hasChildren = ( children ): boolean => {
	return children.some( ( child ) => {
		if ( Array.isArray( child ) ) {
			return hasChildren( child );
		}
		return isObject( child ) && child.key !== null;
	} );
};

/**
 * Gets the total items description text from PHP-computed setting.
 *
 * @return {string} The description text for the total items.
 */
export const getTotalItemsDescription = (): string => {
	return getSetting( 'miniCartFooterDescription', '' );
};
