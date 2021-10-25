/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { dispatch } from '@wordpress/data';
import { translateJQueryEventToNative } from '@woocommerce/base-utils';

interface StoreCartListenersType {
	count: number;
	remove: () => void;
}

declare global {
	interface Window {
		wcBlocksStoreCartListeners: StoreCartListenersType;
	}
}

const refreshData = ( e ): void => {
	const eventDetail = e.detail;
	if ( ! eventDetail || ! eventDetail.preserveCartData ) {
		dispatch( storeKey ).invalidateResolutionForStore();
	}
};

const setUp = (): void => {
	if ( ! window.wcBlocksStoreCartListeners ) {
		window.wcBlocksStoreCartListeners = {
			count: 0,
			remove: () => void null,
		};
	}
};

const addListeners = (): void => {
	setUp();

	if ( ! window.wcBlocksStoreCartListeners.count ) {
		const removeJQueryAddedToCartEvent = translateJQueryEventToNative(
			'added_to_cart',
			`wc-blocks_added_to_cart`
		) as () => () => void;
		const removeJQueryRemovedFromCartEvent = translateJQueryEventToNative(
			'removed_from_cart',
			`wc-blocks_removed_from_cart`
		) as () => () => void;
		document.body.addEventListener(
			`wc-blocks_added_to_cart`,
			refreshData
		);
		document.body.addEventListener(
			`wc-blocks_removed_from_cart`,
			refreshData
		);

		window.wcBlocksStoreCartListeners.count = 0;
		window.wcBlocksStoreCartListeners.remove = () => {
			removeJQueryAddedToCartEvent();
			removeJQueryRemovedFromCartEvent();
			document.body.removeEventListener(
				`wc-blocks_added_to_cart`,
				refreshData
			);
			document.body.removeEventListener(
				`wc-blocks_removed_from_cart`,
				refreshData
			);
		};
	}
	window.wcBlocksStoreCartListeners.count++;
};

const removeListeners = (): void => {
	if ( window.wcBlocksStoreCartListeners.count === 1 ) {
		window.wcBlocksStoreCartListeners.remove();
	}
	window.wcBlocksStoreCartListeners.count--;
};

export const useStoreCartEventListeners = (): void => {
	useEffect( () => {
		addListeners();

		return removeListeners;
	}, [] );
};
