/**
 * External dependencies
 */
import useCheckoutContext from '@woocommerce/base-context/checkout-context';

export const useCheckoutPlaceOrderLabel = () => {
	const { placeOrderLabel } = useCheckoutContext();
	return placeOrderLabel;
};
