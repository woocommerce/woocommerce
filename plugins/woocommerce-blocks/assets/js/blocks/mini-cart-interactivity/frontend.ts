/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

store( 'woocommerce/mini-cart-interactivity', {
	actions: {},
	callbacks: {
		initialize: () => {
			const context = getContext< { cartItemCount: number } >();
			const addItemToCart = () => {
				context.cartItemCount += 1;
			};

			const removeItemFromCart = () => {
				context.cartItemCount -= 1;
			};

			document.body.addEventListener(
				'wc-blocks_added_to_cart',
				addItemToCart
			);
			document.body.addEventListener(
				'wc-blocks_removed_from_cart',
				removeItemFromCart
			);
		},
	},
} );
