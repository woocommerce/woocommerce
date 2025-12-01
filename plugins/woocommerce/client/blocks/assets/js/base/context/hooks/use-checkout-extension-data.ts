/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useRef } from '@wordpress/element';
import { checkoutStore } from '@woocommerce/block-data';

/**
 * Custom hook for setting custom checkout data which is passed to the wc/store/checkout endpoint when processing orders.
 */
export const useCheckoutExtensionData = () => {
	const { setExtensionData } = useDispatch( checkoutStore );
	const extensionData = useSelect( ( select ) =>
		select( checkoutStore ).getExtensionData()
	);
	const extensionDataRef = useRef( extensionData );

	const setExtensionDataCallback = useCallback(
		( namespace: string, key: string, value: unknown ) => {
			setExtensionData( namespace, {
				[ key ]: value,
			} );
		},
		[ setExtensionData ]
	);

	return {
		extensionData: extensionDataRef.current,
		setExtensionData: setExtensionDataCallback,
	};
};
