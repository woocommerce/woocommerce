/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
 * Computes the total items description text based on which settings are enabled.
 *
 * @return {string} The description text for the total items, or empty string if none are enabled.
 */
export const getTotalItemsDescription = (): string => {
	const taxesEnabled = getSetting( 'taxesEnabled', true );
	const shippingEnabled = getSetting( 'shippingEnabled', true );
	const couponsEnabled = getSetting( 'couponsEnabled', true );

	// All three enabled
	if ( taxesEnabled && shippingEnabled && couponsEnabled ) {
		return __(
			'Shipping, taxes, and discounts calculated at checkout.',
			'woocommerce'
		);
	}

	// Shipping + taxes
	if ( shippingEnabled && taxesEnabled ) {
		return __(
			'Shipping and taxes calculated at checkout.',
			'woocommerce'
		);
	}

	// Shipping + coupons
	if ( shippingEnabled && couponsEnabled ) {
		return __(
			'Shipping and discounts calculated at checkout.',
			'woocommerce'
		);
	}

	// Taxes + coupons
	if ( taxesEnabled && couponsEnabled ) {
		return __(
			'Taxes and discounts calculated at checkout.',
			'woocommerce'
		);
	}

	// Only shipping
	if ( shippingEnabled ) {
		return __( 'Shipping calculated at checkout.', 'woocommerce' );
	}

	// Only taxes
	if ( taxesEnabled ) {
		return __( 'Taxes calculated at checkout.', 'woocommerce' );
	}

	// Only coupons
	if ( couponsEnabled ) {
		return __( 'Discounts calculated at checkout.', 'woocommerce' );
	}

	// None enabled
	return '';
};
