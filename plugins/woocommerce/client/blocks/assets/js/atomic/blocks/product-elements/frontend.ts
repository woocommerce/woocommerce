/**
 * External dependencies
 */
import { getElement, store, getContext } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-context';
import type { ProductContextStore } from '@woocommerce/stores/woocommerce/product-context';
import type { ProductResponseItem } from '@woocommerce/types';
import { sanitizeHTML } from '@woocommerce/sanitize';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productContextState } = store< ProductContextStore >(
	'woocommerce/product-context',
	{},
	{ lock: universalLock }
);

const ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'span',
	'bdi',
	'del',
	'ins',
];
const ALLOWED_ATTR = [
	'class',
	'target',
	'href',
	'rel',
	'name',
	'download',
	'aria-hidden',
];

type Context = {
	productElementKey: keyof ProductResponseItem;
};

const productElementStore = store(
	'woocommerce/product-elements',
	{
		state: {
			get productData() {
				const product =
					productContextState.selectedVariation ||
					productContextState.product;

				if ( ! product ) {
					return undefined;
				}

				const { add_to_cart: addToCart } = product;
				const maximum = addToCart?.maximum ?? 0;

				return {
					...product,
					min: addToCart?.minimum ?? 1,
					max: maximum > 0 ? maximum : null,
					step: addToCart?.multiple_of ?? 1,
				};
			},
		},
		callbacks: {
			updateValue: () => {
				const element = getElement();

				if (
					! element.ref ||
					! productElementStore.state?.productData
				) {
					return;
				}

				const { productElementKey } = getContext< Context >();

				const productElementHtml =
					productElementStore?.state?.productData?.[
						productElementKey
					];

				if ( typeof productElementHtml === 'string' ) {
					element.ref.innerHTML = sanitizeHTML( productElementHtml, {
						tags: ALLOWED_TAGS,
						attr: ALLOWED_ATTR,
					} );
				}
			},
		},
	},
	{ lock: true }
);
