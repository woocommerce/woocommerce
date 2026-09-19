/**
 * External dependencies
 */
import { getElement, store, getContext } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce';
import type { WooCommerceStore } from '@woocommerce/stores/woocommerce';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	swapPreformattedHtml,
	PRODUCT_ELEMENT_HTML_CONFIG,
} from '../../../base/utils/preformatted-html';

const { state: wooState } = store< WooCommerceStore >(
	'woocommerce',
	{},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);

type Context = {
	productElementKey: keyof ProductResponseItem;
};

store(
	'woocommerce/product-elements',
	{
		callbacks: {
			updateValue: () => {
				const product = wooState.productScope.product;

				if ( ! product ) {
					return;
				}

				const { productElementKey } = getContext< Context >();

				swapPreformattedHtml(
					getElement().ref,
					product[ productElementKey ],
					PRODUCT_ELEMENT_HTML_CONFIG
				);
			},
		},
	},
	{ lock: true }
);
