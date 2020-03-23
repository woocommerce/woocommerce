/** @typedef { import('@woocommerce/type-defs/hooks').BillingData } BillingData */

/**
 * External dependencies
 */
import { useCheckoutContext } from '@woocommerce/base-context';
import { useCallback } from '@wordpress/element';
/**
 * Exposes billing data api interface from the payment method data context.
 *
 * @return {BillingData} object containing billing data.
 */
export const useBillingData = () => {
	const {
		billingData,
		dispatchActions: { setBillingData },
	} = useCheckoutContext();
	const { email, ...billingAddress } = billingData;
	const setBillingAddress = useCallback(
		( address ) =>
			setBillingData( {
				...billingData,
				...address,
			} ),
		[ setBillingData ]
	);
	const setEmail = useCallback(
		( address ) =>
			setBillingData( {
				...billingData,
				email: address,
			} ),
		[ setBillingData ]
	);
	return {
		email,
		setEmail,
		billingAddress,
		setBillingAddress,
	};
};
