/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { PlainPaymentMethods } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { store as paymentStore } from '../index';

export const setDefaultPaymentMethod = async (
	paymentMethods: PlainPaymentMethods
) => {
	const paymentMethodKeys = Object.keys( paymentMethods );
	const expressPaymentMethodKeys = Object.keys(
		select( paymentStore ).getAvailableExpressPaymentMethods()
	);
	const allPaymentMethodKeys = [
		...paymentMethodKeys,
		...expressPaymentMethodKeys,
	];

	const activePaymentMethod = select( paymentStore ).getActivePaymentMethod();
	// Return if current method is valid.
	if (
		activePaymentMethod &&
		allPaymentMethodKeys.includes( activePaymentMethod )
	) {
		return;
	}

	const savedPaymentMethods = select( paymentStore ).getSavedPaymentMethods();
	const flatSavedPaymentMethods = Object.keys( savedPaymentMethods ).flatMap(
		( type ) => savedPaymentMethods[ type ]
	);
	const savedPaymentMethod =
		flatSavedPaymentMethods.find( ( method ) => method.is_default ) ||
		flatSavedPaymentMethods[ 0 ] ||
		undefined;
	if ( savedPaymentMethod ) {
		const token = savedPaymentMethod.tokenId.toString();
		const paymentMethodSlug = savedPaymentMethod.method.gateway;
		const savedTokenKey = `wc-${ paymentMethodSlug }-payment-token`;

		dispatch( paymentStore ).__internalSetActivePaymentMethod(
			paymentMethodSlug,
			{
				token,
				payment_method: paymentMethodSlug,
				[ savedTokenKey ]: token,
				isSavedToken: true,
			}
		);
		return;
	}

	dispatch( paymentStore ).__internalSetPaymentIdle();
	dispatch( paymentStore ).__internalSetActivePaymentMethod(
		paymentMethodKeys[ 0 ]
	);
};
