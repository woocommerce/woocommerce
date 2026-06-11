/**
 * Internal dependencies
 */
import { ImageSizing } from '../../atomic/blocks/product-elements/image/types';

/**
 * The default layout built from the default template.
 */
export const DEFAULT_PRODUCT_LIST_LAYOUT = [
	[ 'woocommerce/product-image', { imageSizing: ImageSizing.THUMBNAIL } ],
	[ 'woocommerce/product-title' ],
	[ 'woocommerce/product-price' ],
	[ 'woocommerce/product-rating' ],
	[ 'woocommerce/product-button' ],
];
