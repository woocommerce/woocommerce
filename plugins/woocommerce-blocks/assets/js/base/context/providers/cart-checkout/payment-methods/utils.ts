/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { PaymentMethods, CustomerPaymentMethod } from './types';

/**
 * Gets the payment methods saved for the current user after filtering out disabled ones.
 */
export const getCustomerPaymentMethods = (
	availablePaymentMethods: PaymentMethods = {}
): Record< string, CustomerPaymentMethod > => {
	const customerPaymentMethods = getSetting( 'customerPaymentMethods', {} );
	const paymentMethodKeys = Object.keys( customerPaymentMethods );
	const enabledCustomerPaymentMethods = {} as Record<
		string,
		CustomerPaymentMethod
	>;
	paymentMethodKeys.forEach( ( type ) => {
		const methods = customerPaymentMethods[ type ].filter(
			( {
				method: { gateway },
			}: {
				method: {
					gateway: string;
				};
			} ) =>
				gateway in availablePaymentMethods &&
				availablePaymentMethods[ gateway ].supports?.showSavedCards
		);
		if ( methods.length ) {
			enabledCustomerPaymentMethods[ type ] = methods;
		}
	} );
	return enabledCustomerPaymentMethods;
};
