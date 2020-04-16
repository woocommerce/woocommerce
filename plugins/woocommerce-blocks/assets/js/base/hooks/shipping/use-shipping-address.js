/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { useDebounce } from 'use-debounce';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { useStoreCart } from '../cart/use-store-cart';
import { useStoreNotices } from '../use-store-notices';
import { pluckAddress } from '../../utils';

const shouldUpdateStore = ( oldAddress, newAddress ) =>
	! isShallowEqual( pluckAddress( oldAddress ), pluckAddress( newAddress ) );

export const useShippingAddress = () => {
	const { shippingAddress: initialAddress } = useStoreCart();
	const [ shippingAddress, setShippingAddress ] = useState( initialAddress );
	const [ debouncedShippingAddress ] = useDebounce( shippingAddress, 400 );
	const { updateShippingAddress } = useDispatch( storeKey );
	const { addErrorNotice } = useStoreNotices();

	// Note, we're intentionally not using initialAddress as a dependency here
	// so that the stale (previous) value is being used for comparison.
	useEffect( () => {
		if (
			debouncedShippingAddress.country &&
			shouldUpdateStore( initialAddress, debouncedShippingAddress )
		) {
			updateShippingAddress( debouncedShippingAddress ).catch(
				( error ) => {
					addErrorNotice( error.message, {
						id: 'shipping-form',
					} );
				}
			);
		}
	}, [ debouncedShippingAddress ] );

	const decodedShippingAddress = {};
	Object.keys( shippingAddress ).forEach( ( key ) => {
		decodedShippingAddress[ key ] = decodeEntities(
			shippingAddress[ key ]
		);
	} );

	return {
		shippingAddress: decodedShippingAddress,
		setShippingAddress,
	};
};
