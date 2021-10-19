/**
 * External dependencies
 */
import { useCallback, useEffect, useRef } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import { useCheckoutContext } from '../providers/cart-checkout/checkout-state';
import type { CheckoutStateContextState } from '../providers/cart-checkout/checkout-state/types';

/**
 * Custom hook for setting custom checkout data which is passed to the wc/store/checkout endpoint when processing orders.
 */
export const useCheckoutExtensionData = (): {
	extensionData: CheckoutStateContextState[ 'extensionData' ];
	setExtensionData: (
		namespace: string,
		key: string,
		value: unknown
	) => void;
} => {
	const { dispatchActions, extensionData } = useCheckoutContext();
	const extensionDataRef = useRef( extensionData );

	useEffect( () => {
		if ( ! isShallowEqual( extensionData, extensionDataRef.current ) ) {
			extensionDataRef.current = extensionData;
		}
	}, [ extensionData ] );

	const setExtensionDataWithNamespace = useCallback(
		( namespace, key, value ) => {
			const currentData = extensionDataRef.current[ namespace ] || {};
			dispatchActions.setExtensionData( {
				...extensionDataRef.current,
				[ namespace ]: {
					...currentData,
					[ key ]: value,
				},
			} );
		},
		[ dispatchActions ]
	);

	return {
		extensionData: extensionDataRef.current,
		setExtensionData: setExtensionDataWithNamespace,
	};
};
