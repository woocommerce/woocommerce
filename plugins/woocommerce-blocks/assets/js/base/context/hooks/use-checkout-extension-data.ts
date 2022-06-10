/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useRef } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import type { CheckoutState } from '../../../data/checkout/types';

/**
 * Custom hook for setting custom checkout data which is passed to the wc/store/checkout endpoint when processing orders.
 */
export const useCheckoutExtensionData = (): {
	extensionData: CheckoutState[ 'extensionData' ];
	setExtensionData: (
		namespace: string,
		key: string,
		value: unknown
	) => void;
} => {
	const { setExtensionData } = useDispatch( CHECKOUT_STORE_KEY );
	const { extensionData } = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).getCheckoutState()
	);
	const extensionDataRef = useRef( extensionData );

	useEffect( () => {
		if ( ! isShallowEqual( extensionData, extensionDataRef.current ) ) {
			extensionDataRef.current = extensionData;
		}
	}, [ extensionData ] );

	const setExtensionDataWithNamespace = useCallback(
		( namespace, key, value ) => {
			const currentData = extensionDataRef.current[ namespace ] || {};
			setExtensionData( {
				...extensionDataRef.current,
				[ namespace ]: {
					...currentData,
					[ key ]: value,
				},
			} );
		},
		[ setExtensionData ]
	);

	return {
		extensionData: extensionDataRef.current,
		setExtensionData: setExtensionDataWithNamespace,
	};
};
