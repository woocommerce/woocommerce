/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useReducer, useEffect } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { useDebounce } from 'use-debounce';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useStoreCart } from '../cart/use-store-cart';
import { pluckAddress } from '../../utils';
/**
 * This is a custom hook that is wired up to the `wc/store/cart/shipping-rates` route.
 * Given a a set of default fields keys, this will handle shipping form state and load
 * new rates when certain fields change.
 *
 * @param {Array} addressFieldsKeys an array containing default fields keys.
 *
 * @return {Object} This hook will return an object with three properties:
 *                 - {Boolean} shippingRatesLoading A boolean indicating whether the shipping
 *                   rates are still loading or not.
 *                 - {Function} setShippingAddress  An function that optimistically
 *                   update shipping address and dispatches async rate fetching.
 *                 - {Object} shippingAddress       An object containing shipping address.
 *                 - {Object} shippingAddress       True when address data exists.
 */
export const useShippingRates = ( addressFieldsKeys ) => {
	const { cartErrors, shippingRates } = useStoreCart();
	const initialAddress = shippingRates[ 0 ]?.destination || {};
	addressFieldsKeys.forEach( ( key ) => {
		initialAddress[ key ] = initialAddress[ key ] || '';
	} );
	const shippingAddressReducer = ( state, address ) => ( {
		...state,
		...address,
	} );
	const [ shippingAddress, setShippingAddress ] = useReducer(
		shippingAddressReducer,
		initialAddress
	);
	const [ debouncedShippingAddress ] = useDebounce( shippingAddress, 400 );
	const shippingRatesLoading = useSelect(
		( select ) => select( storeKey ).areShippingRatesLoading(),
		[]
	);
	const { updateShippingAddress } = useDispatch( storeKey );

	useEffect( () => {
		if (
			! isShallowEqual(
				pluckAddress( debouncedShippingAddress ),
				pluckAddress( initialAddress )
			) &&
			debouncedShippingAddress.country
		) {
			updateShippingAddress( debouncedShippingAddress );
		}
	}, [ debouncedShippingAddress ] );

	return {
		shippingRates,
		shippingAddress,
		setShippingAddress,
		shippingRatesLoading,
		shippingRatesErrors: cartErrors,
		hasShippingAddress: !! shippingAddress.country,
	};
};
