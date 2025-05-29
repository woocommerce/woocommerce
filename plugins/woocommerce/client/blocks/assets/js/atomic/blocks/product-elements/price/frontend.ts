/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';
import { DisplayedProduct } from '../types';

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
					const { state } = store< WooCommerce >( 'woocommerce' );

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
