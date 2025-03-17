/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useRef } from '@wordpress/element';
import { checkoutStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import type { CheckoutState } from '../../../data/checkout/default-state';

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
	const { __internalSetExtensionData } = useDispatch( checkoutStore );
	const extensionData = useSelect( ( select ) =>
		select( checkoutStore ).getExtensionData()
	);
	const extensionDataRef = useRef( extensionData );

	const setExtensionData = useCallback(
		( namespace, key, value ) => {
			__internalSetExtensionData( namespace, {
				[ key ]: value,
			} );
		},
		[ __internalSetExtensionData ]
	);

	return {
		extensionData: extensionDataRef.current,
		setExtensionData,
	};
};
