/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { dispatch } from '@wordpress/data';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEffect } from '@wordpress/element';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import { ValidationContextProvider } from '@woocommerce/base-context';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import { translateJQueryEventToNative } from '@woocommerce/base-utils';
import withScrollToTop from '@woocommerce/base-hocs/with-scroll-to-top';
import {
	StoreNoticesProvider,
	StoreSnackbarNoticesProvider,
	CartProvider,
} from '@woocommerce/base-context/providers';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';

const reloadPage = () => void window.location.reload( true );

const Cart = ( { children } ) => {
	const { cartIsLoading } = useStoreCart();

	return (
		<LoadingMask showSpinner={ true } isLoading={ cartIsLoading }>
			<ValidationContextProvider>{ children }</ValidationContextProvider>
		</LoadingMask>
	);
};

const ScrollOnError = ( { scrollToTop } ) => {
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

	return null;
};
const Block = ( { attributes, children, scrollToTop } ) => (
	<BlockErrorBoundary
		header={ __( 'Something went wrong…', 'woocommerce' ) }
		text={ __(
			'The cart has encountered an unexpected error. If the error persists, please get in touch with us for help.',
			'woocommerce'
		) }
		button={
			<button className="wc-block-button" onClick={ reloadPage }>
				{ __( 'Reload the page', 'woocommerce' ) }
			</button>
		}
		showErrorMessage={ CURRENT_USER_IS_ADMIN }
	>
		<StoreSnackbarNoticesProvider context="wc/cart">
			<StoreNoticesProvider context="wc/cart">
				<SlotFillProvider>
					<CartProvider>
						<Cart attributes={ attributes }>{ children }</Cart>
						<ScrollOnError scrollToTop={ scrollToTop } />
					</CartProvider>
				</SlotFillProvider>
			</StoreNoticesProvider>
		</StoreSnackbarNoticesProvider>
	</BlockErrorBoundary>
);
export default withScrollToTop( Block );
