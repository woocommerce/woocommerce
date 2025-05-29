/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

import { DisplayedProduct } from '../types';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const productPriceStore = store(
	'woocommerce/product-price',
	{
		callbacks: {
			renderContent: () => {
				const context = getContext< {
					displayedProduct: DisplayedProduct;
				} >( 'woocommerce/single-product' );

				const element = getElement();

				if ( context ) {
					element.ref.innerHTML = context.displayedProduct.price_html;
				} else {
					const { state } = store< WooCommerce >(
						'woocommerce',
						{},
						{ lock: universalLock }
					);

					if ( state.displayedProduct ) {
						element.ref.innerHTML =
							state.displayedProduct.price_html;
					}
				}
			},
		},
	},
	{ lock: true }
);
