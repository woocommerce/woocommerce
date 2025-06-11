/**
 * External dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';
import type { ProductData } from '@woocommerce/type-defs/product';
import type { SingleProductTemplateStore } from '@woocommerce/base-stores/single-product-template';
import { sanitize } from 'dompurify'; // eslint-disable-line import/named

/**
 * Internal dependencies
 */
import { formatPrice } from '../../../../blocks/product-filters/utils/price-currency';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< SingleProductTemplateStore >(
	'woocommerce',
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

const getProductData = ( key: keyof ProductData ) => {
	const singleProductContext = getContext< {
		productData: ProductData | null;
		originalProductData: ProductData;
	} >( 'woocommerce/single-product' );

	return singleProductContext
		? singleProductContext?.productData?.[ key ]
		: wooState?.singleProductTemplate?.productData?.[ key ];
};

const getOriginalProductData = ( key: keyof ProductData ) => {
	const singleProductContext = getContext< {
		productData: ProductData | null;
		originalProductData: ProductData;
	} >( 'woocommerce/single-product' );

	return singleProductContext
		? singleProductContext?.originalProductData?.[ key ]
		: wooState?.singleProductTemplate?.originalProductData?.[ key ];
};

const sprintf = ( text: string, value: string ) => {
	return text.replace( '%s', value );
};

const productPriceStore = store(
	'woocommerce/product-price',
	{
		callbacks: {
			setNewPrice: () => {
				const element = getElement();

				if ( ! element.ref ) {
					return;
				}

				const newPrice = getProductData( 'display_price' );
				const newRegularPrice = getProductData(
					'display_regular_price'
				);

				const { regularPriceText, currentPriceText } = getContext< {
					regularPriceText: string;
					currentPriceText: string;
				} >( 'woocommerce/product-price' );

				if (
					newPrice &&
					newRegularPrice &&
					newPrice !== newRegularPrice
				) {
					element.ref.innerHTML = `<span class="price"><del aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi>${ formatPrice(
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
					element.ref.innerHTML = `<span class="price"><span class="woocommerce-Price-amount amount"><bdi>${ formatPrice(
						newPrice
					) }</bdi></span></span>`;
				} else {
					const originalPrice = getOriginalProductData(
						'price_html'
					) as string;

					if ( originalPrice ) {
						element.ref.innerHTML = sanitize( originalPrice, {
							ALLOWED_TAGS,
							ALLOWED_ATTR,
						} );
					}
				}
			},
		},
	},
	{ lock: true }
);

export type ProductPriceStore = typeof productPriceStore;
