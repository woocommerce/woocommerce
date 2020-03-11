/**
 * External dependencies
 */
import { usePaymentMethodDataContext } from '@woocommerce/base-context';

/**
 * Exposes billing data api interface from the payment method data context.
 */
export const useBillingData = () => {
	const { billingData, setBillingData } = usePaymentMethodDataContext();
	return {
		billingData,
		setBillingData,
	};
};
