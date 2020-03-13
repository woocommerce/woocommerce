/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useStoreCart } from '../cart/use-store-cart';
import { useShippingAddress } from '../shipping/use-shipping-address';
/**
 * This is a custom hook that is wired up to the `wc/store/cart/shipping-rates` route.
 * Given a a set of default fields keys, this will handle shipping form state and load
 * new rates when certain fields change.
 *
 * @return {Object} This hook will return an object with three properties:
 *                 - {Boolean} shippingRatesLoading A boolean indicating whether the shipping
 *                   rates are still loading or not.
 *                 - {Function} setShippingAddress  An function that optimistically
 *                   update shipping address and dispatches async rate fetching.
 *                 - {Object} shippingAddress       An object containing shipping address.
 *                 - {Object} shippingAddress       True when address data exists.
 */
export const useShippingRates = () => {
	const { cartErrors, shippingRates } = useStoreCart();
	const { shippingAddress, setShippingAddress } = useShippingAddress();
	const shippingRatesLoading = useSelect(
		( select ) => select( storeKey ).areShippingRatesLoading(),
		[]
	);
	return {
		shippingRates,
		shippingAddress,
		setShippingAddress,
		shippingRatesLoading,
		shippingRatesErrors: cartErrors,
		hasShippingAddress: !! shippingAddress.country,
	};
};
