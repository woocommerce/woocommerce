/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';
import type { DisplayedProduct } from '@woocommerce/type-defs/product';
import type { SingleProductTemplateStore } from '@woocommerce/base-stores/single-product-template';

/**
 * Internal dependencies
 */

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< SingleProductTemplateStore >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const productPriceStore = store(
	'woocommerce/product-price',
	{
		callbacks: {
			setNewPrice: () => {
				const element = getElement();

				if ( ! element.ref ) {
					return;
				}

				const productCollectionContext = getContext< {
					displayedProduct: DisplayedProduct;
				} >( 'woocommerce/product-collection' );

				const singleProductContext = getContext< {
					displayedProduct: DisplayedProduct;
				} >( 'woocommerce/single-product' );

				if ( singleProductContext ) {
					element.ref.innerHTML =
						singleProductContext.displayedProduct.price_html;
				} else if ( productCollectionContext ) {
					element.ref.innerHTML =
						productCollectionContext.displayedProduct.price_html;
				} else if ( wooState.displayedProduct ) {
					element.ref.innerHTML =
						wooState.displayedProduct.price_html;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductPriceStore = typeof productPriceStore;
