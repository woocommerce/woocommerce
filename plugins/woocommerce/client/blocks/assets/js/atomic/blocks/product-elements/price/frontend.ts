/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';
import type { ProductData } from '@woocommerce/type-defs/product';
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

				const singleProductContext = getContext< {
					productData: ProductData;
				} >( 'woocommerce/single-product' );

				if ( singleProductContext?.productData?.price_html ) {
					element.ref.innerHTML =
						singleProductContext.productData.price_html;
				} else if (
					wooState?.singleProductTemplate.productData?.price_html
				) {
					element.ref.innerHTML =
						wooState.singleProductTemplate.productData.price_html;
				}
			},
		},
	},
	{ lock: true }
);

export type ProductPriceStore = typeof productPriceStore;
