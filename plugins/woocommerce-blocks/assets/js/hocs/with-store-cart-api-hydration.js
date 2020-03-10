/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

/**
 * Hydrate Cart API data.
 *
 * Makes cart data available without an API request to wc/store/cart/.
 */
const useStoreCartApiHydration = () => {
	const cartData = useRef( getSetting( 'cartData' ) );

	useSelect( ( select, registry ) => {
		if ( ! cartData.current ) {
			return;
		}

		const { isResolving, hasFinishedResolution } = select( CART_STORE_KEY );
		const {
			receiveCart,
			receiveError,
			startResolution,
			finishResolution,
		} = registry.dispatch( CART_STORE_KEY );

		if (
			! isResolving( 'getCartData', [] ) &&
			! hasFinishedResolution( 'getCartData', [] )
		) {
			startResolution( 'getCartData', [] );
			if ( cartData.current?.code?.includes( 'error' ) ) {
				receiveError( cartData.current );
			} else {
				receiveCart( cartData.current );
			}
			finishResolution( 'getCartData', [] );
		}
	}, [] );
};

/**
 * HOC that calls the useStoreCartApiHydration hook.
 *
 * @param {Function} OriginalComponent Component being wrapped.
 */
const withStoreCartApiHydration = ( OriginalComponent ) => {
	return ( props ) => {
		useStoreCartApiHydration();
		return <OriginalComponent { ...props } />;
	};
};

export default withStoreCartApiHydration;
