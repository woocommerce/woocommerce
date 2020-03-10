/**
 * External dependencies
 */
import useCheckoutContext from '@woocommerce/base-context';

export const useCheckoutPlaceOrderLabel = () => {
	const { placeOrderLabel } = useCheckoutContext();
	return placeOrderLabel;
};
