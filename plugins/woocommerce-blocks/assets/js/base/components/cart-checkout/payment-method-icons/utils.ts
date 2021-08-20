/**
 * External dependencies
 */
import type { PaymentMethodIcon } from '@woocommerce/type-defs/payment-method-icon';
import { isString } from '@woocommerce/types';

/**
 * For an array of icons, normalize into objects and remove duplicates.
 */
export const normalizeIconConfig = (
	icons: ( PaymentMethodIcon | string )[]
): PaymentMethodIcon[] => {
	const normalizedIcons: Record< string, PaymentMethodIcon > = {};

	icons.forEach( ( raw ) => {
		let icon: Partial< PaymentMethodIcon > = {};

		if ( typeof raw === 'string' ) {
			icon = {
				id: raw,
				alt: raw,
				src: null,
			};
		}

		if ( typeof raw === 'object' ) {
			icon = {
				id: raw.id || '',
				alt: raw.alt || '',
				src: raw.src || null,
			};
		}

		if ( icon.id && isString( icon.id ) && ! normalizedIcons[ icon.id ] ) {
			normalizedIcons[ icon.id ] = <PaymentMethodIcon>icon;
		}
	} );

	return Object.values( normalizedIcons );
};
