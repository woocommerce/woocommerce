/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { LAST_CART_UPDATE_TIMESTAMP_KEY } from '../data/cart/constants';

/**
 * Hydrate Cart API data.
 *
 * Makes cart data available without an API request to wc/store/cart/.
 */
const useStoreCartApiHydration = () => {
	const preloadedApiRequests = useRef(
		getSetting( 'preloadedApiRequests', {} )
	);
	const { setIsCartDataStale } = useDispatch( CART_STORE_KEY );

	useSelect( ( select, registry ) => {
		const cartData = preloadedApiRequests.current[ '/wc/store/cart' ]?.body;

		if ( ! cartData ) {
			return;
		}

		const { isResolving, hasFinishedResolution, isCartDataStale } = select(
			CART_STORE_KEY
		);

		/**
		 * This should only execute once. When the code further down the file executes
		 * then the condition directly below this comment should never evaluate to true
		 * on subsequent executions. Because of this localStorage.getItem won't be
		 * called multiple times.
		 */
		if (
			! isCartDataStale() &&
			! isResolving( 'getCartData' ) &&
			! hasFinishedResolution( 'getCartData', [] )
		) {
			const lastCartUpdateRaw = window.localStorage.getItem(
				LAST_CART_UPDATE_TIMESTAMP_KEY
			);

			if ( lastCartUpdateRaw ) {
				const lastCartUpdate = parseFloat( lastCartUpdateRaw );
				const cartGeneratedTimestamp = parseFloat(
					cartData.generated_timestamp
				);

				const needsUpdateFromAPI =
					! isNaN( cartGeneratedTimestamp ) &&
					! isNaN( lastCartUpdate ) &&
					lastCartUpdate > cartGeneratedTimestamp;

				if ( needsUpdateFromAPI ) {
					setIsCartDataStale();
				}
			}
		}
		const {
			receiveCart,
			receiveError,
			startResolution,
			finishResolution,
		} = registry.dispatch( CART_STORE_KEY );

		if (
			! isCartDataStale() &&
			! isResolving( 'getCartData', [] ) &&
			! hasFinishedResolution( 'getCartData', [] )
		) {
			startResolution( 'getCartData', [] );
			if ( cartData?.code?.includes( 'error' ) ) {
				receiveError( cartData );
			} else {
				receiveCart( cartData );
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
