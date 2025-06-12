/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';
import type { SingleProductStore } from '@woocommerce/blocks/single-product/frontend';
import { sanitize } from 'dompurify'; // eslint-disable-line import/named

/**
 * Internal dependencies
 */
import { formatPrice } from '../../../../blocks/product-filters/utils/price-currency';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: singleProductState } = store< SingleProductStore >(
	'woocommerce/single-product',
	{},
	{ lock: universalLock }
);

export type Context = {
	regularPriceText: string;
	currentPriceText: string;
};

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

const sprintf = ( text: string, value: string ) => {
	return text.replace( '%s', value );
};

const productPriceStore = store(
	'woocommerce/product-price',
	{
		state: {
			get formattedPrice(): string {
				if ( ! singleProductState?.productData ) {
					return '';
				}

				const {
					display_price: newPrice,
					display_regular_price: newRegularPrice,
				} = singleProductState.productData;

				if (
					newPrice &&
					newRegularPrice &&
					newPrice !== newRegularPrice
				) {
					const { regularPriceText = '%s', currentPriceText = '%s' } =
						getContext< Context >();
					return `<span class="price"><del aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi>${ formatPrice(
						newRegularPrice
					) }</bdi></span></del><span class="screen-reader-text">${ sprintf(
						regularPriceText,
						formatPrice( newRegularPrice )
					) }</span> <ins aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi>${ formatPrice(
						newPrice
					) }</bdi></span></ins><span class="screen-reader-text">${ sprintf(
						currentPriceText,
						formatPrice( newPrice )
					) }</span></span>`;
				} else if ( newPrice ) {
					return `<span class="price"><span class="woocommerce-Price-amount amount"><bdi>${ formatPrice(
						newPrice
					) }</bdi></span></span>`;
				}

				return '';
			},
			get originalPriceHtml(): string {
				return (
					singleProductState?.originalProductData?.price_html || ''
				);
			},
		},
		callbacks: {
			updatePrice: () => {
				const element = getElement();

				if ( ! element.ref ) {
					return;
				}

				const { formattedPrice, originalPriceHtml } =
					productPriceStore.state;

				if ( ! formattedPrice && ! originalPriceHtml ) {
					return;
				}

				element.ref.innerHTML = sanitize(
					formattedPrice || originalPriceHtml,
					{
						ALLOWED_TAGS,
						ALLOWED_ATTR,
					}
				);
			},
		},
	},
	{ lock: true }
);

export type ProductPriceStore = typeof productPriceStore;
