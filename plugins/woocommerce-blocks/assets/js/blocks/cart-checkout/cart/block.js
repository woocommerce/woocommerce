/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { dispatch } from '@wordpress/data';
import { useStoreCart } from '@woocommerce/base-hooks';
import { useEffect, RawHTML } from '@wordpress/element';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import {
	ValidationContextProvider,
	CartProvider,
} from '@woocommerce/base-context';
import { translateJQueryEventToNative } from '@woocommerce/base-utils';
import withScrollToTop from '@woocommerce/base-hocs/with-scroll-to-top';

/**
 * Internal dependencies
 */
import FullCart from './full-cart';

const Block = ( { emptyCart, attributes, scrollToTop } ) => {
	const { cartItems, cartIsLoading } = useStoreCart();

	useEffect( () => {
		const invalidateCartData = () => {
			dispatch( storeKey ).invalidateResolutionForStore();
			scrollToTop();
		};

		// Make it so we can read jQuery events triggered by WC Core elements.
		const removeJQueryAddedToCartEvent = translateJQueryEventToNative(
			'added_to_cart',
			'wc-blocks_added_to_cart'
		);
		const removeJQueryRemovedFromCartEvent = translateJQueryEventToNative(
			'removed_from_cart',
			'wc-blocks_removed_from_cart'
		);

		document.body.addEventListener(
			'wc-blocks_added_to_cart',
			invalidateCartData
		);
		document.body.addEventListener(
			'wc-blocks_removed_from_cart',
			invalidateCartData
		);

		return () => {
			removeJQueryAddedToCartEvent();
			removeJQueryRemovedFromCartEvent();

			document.body.removeEventListener(
				'wc-blocks_added_to_cart',
				invalidateCartData
			);
			document.body.removeEventListener(
				'wc-blocks_removed_from_cart',
				invalidateCartData
			);
		};
	}, [ scrollToTop ] );

	return (
		<>
			{ ! cartIsLoading && cartItems.length === 0 ? (
				<RawHTML>{ emptyCart }</RawHTML>
			) : (
				<LoadingMask showSpinner={ true } isLoading={ cartIsLoading }>
					<ValidationContextProvider>
						<CartProvider>
							<FullCart attributes={ attributes } />
						</CartProvider>
					</ValidationContextProvider>
				</LoadingMask>
			) }
		</>
	);
};

export default withScrollToTop( Block );
